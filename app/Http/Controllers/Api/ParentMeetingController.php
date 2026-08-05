<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentMeetingRequest;
use App\Models\ParentSummon;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ParentMeetingController extends Controller
{
    // ==========================================
    // 1. صلاحيات ولي الأمر (Parent)
    // ==========================================

    /**
     * رد ولي الأمر على استدعاء من المدرسة
     */
    public function respondToSummon(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:acknowledged,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $summon = ParentSummon::where('id', $id)
            ->where('parent_user_id', $user->user_id)
            ->first();

        if (!$summon) {
            return response()->json([
                'success' => false,
                'message' => 'الاستدعاء غير موجود أو غير مصرح لك بالوصول إليه.'
            ], 404);
        }

        $summon->update([
            'status' => $request->status,
        ]);

        // إرسال إشعار للمستدعي (المعلم أو رئيس القسم)
        $sender = User::find($summon->sender_user_id);
        if ($sender) {
            $statusText = $request->status === 'acknowledged' ? 'تأكيد الحضور والاطلاع' : 'اعتذار عن الحضور';
            DB::table('notifications')->insert([
                'user_id'    => $sender->user_id,
                'sender_id'  => $user->user_id,
                'title'      => 'رد على استدعاء ولي أمر',
                'message'    => "قام ولي الأمر بالرد على الاستدعاء بـ: ({$statusText})",
                'type'       => 'summon_response',
                'category'   => 'administrative',
                'related_id' => $summon->id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل ردك بنجاح ونقله للمدرسة.',
            'data' => $summon
        ]);
    }


    // ==========================================
    // 2. صلاحيات المدرسة (Admin / HOD / Teacher)
    // ==========================================

    /**
     * جلب قائمة طلبات الاجتماعات الواردة من الأهالي
     */
    public function listMeetingRequests(Request $request)
    {
        $user = $request->user();
        $query = ParentMeetingRequest::with(['parent', 'student.user']);

        // إذا كان المستخدم رئيس قسم، نجلب فقط طلاب قسمه
        if ($user->role === 'head') {
            $department = $user->department;
            $query->whereHas('student.user', function ($q) use ($department) {
                $q->where('department', 'LIKE', '%' . $department . '%');
            });
        }

        $requests = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * رد الإدارة/رئيس القسم على طلب موعد من ولي الأمر
     */
    public function respondToMeetingRequest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status'          => 'required|in:approved,rejected,completed',
            'admin_response'  => 'nullable|string',
            'scheduled_at'    => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $meetingRequest = ParentMeetingRequest::find($id);

        if (!$meetingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'طلب اللقاء غير موجود.'
            ], 404);
        }

        $meetingRequest->update([
            'status'         => $request->status,
            'admin_response' => $request->admin_response,
            'scheduled_at'   => $request->scheduled_at,
        ]);

        // إرسال إشعار لولي الأمر
        $statusText = match ($request->status) {
            'approved' => 'تمت الموافقة وتحديد الموعد',
            'rejected' => 'تم الاعتذار عن الموعد',
            'completed'=> 'تم اكتمال المقابلة بنجاح',
        };

        DB::table('notifications')->insert([
            'user_id'    => $meetingRequest->parent_user_id,
            'sender_id'  => $request->user()->user_id,
            'title'      => 'تحديث على طلب اللقاء مع الإدارة',
            'message'    => "الحالة الجديدة لطلب اللقاء: ({$statusText}) " . ($request->admin_response ? " - ملاحظة: " . $request->admin_response : ""),
            'type'       => 'meeting_response',
            'category'   => 'administrative',
            'related_id' => $meetingRequest->id,
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الرد وإشعار ولي الأمر بنجاح.',
            'data' => $meetingRequest
        ]);
    }

    /**
     * جلب قائمة الاستدعاءات المرسلة من المدرسة للأهالي
     */
    public function listSummons(Request $request)
    {
        $user = $request->user();
        $query = ParentSummon::with(['sender', 'student.user', 'parent']);

        // إذا كان معلماً، يرى فقط الاستدعاءات التي أرسلها هو
        if ($user->role === 'teacher') {
            $query->where('sender_user_id', $user->user_id);
        } 
        // إذا كان رئيس قسم، يرى استدعاءات طلاب قسمه
        elseif ($user->role === 'head') {
            $department = $user->department;
            $query->whereHas('student.user', function ($q) use ($department) {
                $q->where('department', 'LIKE', '%' . $department . '%');
            });
        }

        $summons = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $summons
        ]);
    }

    /**
     * إرسال استدعاء جديد لولي أمر (نسخة محسنة تدعم الإدارة ورؤساء الأقسام والمعلمين)
     */
    public function sendSummon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id'   => 'required|exists:students,student_id',
            'reason_title' => 'required|string|max:255',
            'details'      => 'required|string',
            'summon_date'  => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $sender = $request->user();
        $student = Student::find($request->student_id);

        // جلب ولي أمر الطالب
        $parentUserId = DB::table('parent_students')
            ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
            ->where('parent_students.student_id', $student->student_id)
            ->value('parents.user_id');

        if (!$parentUserId) {
            return response()->json([
                'success' => false,
                'message' => 'لم نتمكن من العثور على ولي أمر مرتبط بهذا الطالب.'
            ], 404);
        }

        $summon = ParentSummon::create([
            'sender_user_id' => $sender->user_id,
            'student_id'     => $student->student_id,
            'parent_user_id' => $parentUserId,
            'reason_title'   => $request->reason_title,
            'details'        => $request->details,
            'summon_date'    => $request->summon_date,
            'status'         => 'sent',
        ]);

        // إرسال إشعار لولي الأمر
        DB::table('notifications')->insert([
            'user_id'    => $parentUserId,
            'sender_id'  => $sender->user_id,
            'title'      => 'استدعاء ولي أمر رسمي',
            'message'    => "لديك استدعاء من المدرسة بخصوص الطالب: ({$student->user->full_name}) - السبب: " . $request->reason_title,
            'type'       => 'summon',
            'category'   => 'administrative',
            'related_id' => $summon->id,
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الاستدعاء لولي الأمر بنجاح.',
            'data' => $summon
        ], 201);
    }
}
