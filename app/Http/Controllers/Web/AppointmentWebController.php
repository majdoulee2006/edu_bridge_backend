<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ParentMeetingRequest;
use App\Models\ParentSummon;
use App\Models\Student;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentWebController extends Controller
{
    /**
     * عرض المواعيد والاستدعاءات للإدارة أو رئيس القسم
     */
    public function index()
    {
        $user = Auth::user();
        $isHOD = ($user->role_id == 5); // 5 is head/HOD
        
        $meetingsQuery = ParentMeetingRequest::with(['parent', 'student.user']);
        $summonsQuery = ParentSummon::with(['sender', 'student.user', 'parent']);

        if ($isHOD) {
            $dept = $user->department;
            // تصفية اللقاءات لطلاب القسم فقط
            $meetingsQuery->whereHas('student.user', function ($q) use ($dept) {
                $q->where('department', 'LIKE', '%' . $dept . '%');
            });
            // تصفية الاستدعاءات لطلاب القسم فقط
            $summonsQuery->whereHas('student.user', function ($q) use ($dept) {
                $q->where('department', 'LIKE', '%' . $dept . '%');
            });

            // جلب الطلاب في هذا القسم مع اختصاصاتهم
            $students = Student::with(['user', 'program'])
                ->whereHas('user', function($q) use ($dept) {
                    $q->where('department', 'LIKE', '%' . $dept . '%');
                })->get();

            // جلب الدورات / الاختصاصات التابعة لقسم رئيس القسم
            $departmentId = DB::table('departments')
                ->where('name', 'LIKE', '%' . $dept . '%')
                ->orWhere('description', 'LIKE', '%' . $dept . '%')
                ->value('department_id');

            if ($departmentId) {
                $programs = DB::table('programs')->where('department_id', $departmentId)->get();
            } else {
                $programs = DB::table('programs')->get();
            }

            if ($programs->isEmpty()) {
                $programs = DB::table('programs')->get();
            }
        } else {
            // الأدمن يرى كل شيء
            $students = Student::with(['user', 'program'])->get();
            $programs = DB::table('programs')->get();
        }

        $meetings = $meetingsQuery->orderByDesc('created_at')->get();
        $summons = $summonsQuery->orderByDesc('created_at')->get();

        $isAffairs = ($user->role_id == 4);
        if ($isHOD) {
            $viewName = 'hod.appointments';
        } elseif ($isAffairs) {
            $viewName = 'affairs.appointments';
        } else {
            $viewName = 'admin.appointments';
        }

        return view($viewName, compact('meetings', 'summons', 'students', 'programs'));
    }

    /**
     * الرد على طلب موعد من ولي الأمر
     */
    public function respondToMeeting(Request $request, $id)
    {
        $request->validate([
            'status'         => 'required|in:approved,rejected,completed',
            'admin_response' => 'nullable|string',
            'scheduled_at'   => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        if ($request->status === 'approved' && $request->scheduled_at) {
            $scheduledTime = strtotime($request->scheduled_at);
            if ($scheduledTime < time() - 60) {
                return redirect()->back()->with('error', 'خطأ: لا يمكن تحديد تاريخ ووقت سابق عن الوقت الحالي.');
            }
        }

        $meeting = ParentMeetingRequest::findOrFail($id);
        
        $scheduledAt = $request->scheduled_at 
            ? date('Y-m-d H:i:s', strtotime($request->scheduled_at)) 
            : null;

        $meeting->update([
            'status'         => $request->status,
            'admin_response' => $request->admin_response,
            'scheduled_at'   => $scheduledAt,
        ]);

        // إرسال إشعار لولي الأمر
        $statusText = match ($request->status) {
            'approved' => 'تمت الموافقة وتحديد الموعد',
            'rejected' => 'تم الاعتذار عن الموعد',
            'completed'=> 'تم اكتمال المقابلة بنجاح',
        };

        DB::table('notifications')->insert([
            'user_id'    => $meeting->parent_user_id,
            'sender_id'  => Auth::id(),
            'title'      => 'تحديث على طلب اللقاء مع الإدارة',
            'message'    => "الحالة الجديدة لطلب اللقاء: ({$statusText}) " . ($request->admin_response ? " - ملاحظة: " . $request->admin_response : ""),
            'type'       => 'meeting_response',
            'category'   => 'administrative',
            'related_id' => $meeting->id,
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم حفظ الرد وإشعار ولي الأمر بنجاح.');
    }

    /**
     * إرسال استدعاء جديد لولي أمر طالب
     */
    public function storeSummon(Request $request)
    {
        $request->validate([
            'student_id'   => 'required|exists:students,student_id',
            'reason_title' => 'required|string|max:255',
            'details'      => 'required|string',
            'summon_date'  => 'nullable|date',
        ]);

        if ($request->summon_date) {
            $summonTime = strtotime($request->summon_date);
            if ($summonTime < strtotime(date('Y-m-d'))) {
                return redirect()->back()->with('error', 'خطأ: لا يمكن إرسال استدعاء في تاريخ سابق عن اليوم الحالي.');
            }
        }

        $student = Student::findOrFail($request->student_id);

        // جلب ولي أمر الطالب
        $parentUserId = DB::table('parent_students')
            ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
            ->where('parent_students.student_id', $student->student_id)
            ->value('parents.user_id');

        if (!$parentUserId) {
            return redirect()->back()->with('error', 'خطأ: لم نجد ولي أمر مرتبط بهذا الطالب في قاعدة البيانات.');
        }

        $summon = ParentSummon::create([
            'sender_user_id' => Auth::id(),
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
            'sender_id'  => Auth::id(),
            'title'      => 'استدعاء ولي أمر رسمي',
            'message'    => "لديك استدعاء رسمي من إدارة المعهد بخصوص الطالب: ({$student->user->full_name}) - السبب: " . $request->reason_title,
            'type'       => 'summon',
            'category'   => 'administrative',
            'related_id' => $summon->id,
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم إرسال طلب الاستدعاء وإشعار ولي الأمر بنجاح.');
    }
}
