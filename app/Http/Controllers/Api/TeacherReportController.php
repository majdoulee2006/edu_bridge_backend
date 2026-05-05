<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherReportController extends Controller
{
    /**
     * جلب طلبات التقارير الموجهة للأستاذ الحالي
     */
    public function getMyPendingReportRequests()
    {
        try {
            // جلب الـ teacher_id الخاص بالمستخدم المسجل حالياً
            $teacher = DB::table('teachers')->where('user_id', auth()->id())->first();
            if (!$teacher) {
                return response()->json(['message' => 'هذا المستخدم ليس مدرباً'], 403);
            }

            $requests = DB::table('report_requests')
                ->join('students', 'report_requests.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->select('report_requests.*', 'users.full_name as student_name')
                ->where('report_requests.teacher_id', $teacher->teacher_id)
                ->where('report_requests.status', 'pending')
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * إرسال التقرير من قبل الأستاذ
     */
    public function submitReport(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:report_requests,id',
            'recommendations' => 'required|string', // محتوى التقرير
            'attendance_rate' => 'nullable|numeric',
            'average_grade' => 'nullable|numeric',
        ]);

        try {
            // 1. جلب بيانات الطلب للتأكد من المالك
            $reportRequest = DB::table('report_requests')->where('id', $request->request_id)->first();
            
            // 2. إدخال التقرير في جدول performance_reports
            $reportId = DB::table('performance_reports')->insertGetId([
                'student_id' => $reportRequest->student_id,
                'report_type' => $reportRequest->report_type,
                'attendance_rate' => $request->attendance_rate ?? 0,
                'average_grade' => $request->average_grade ?? 0,
                'recommendations' => $request->recommendations,
                'generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. تحديث حالة الطلب الأصلي
            DB::table('report_requests')
                ->where('id', $request->request_id)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);

            // 4. إرسال إشعار لرئيس القسم (Head)
            $teacherName = auth()->user()->full_name;
            $studentName = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('students.student_id', $reportRequest->student_id)
                ->value('users.full_name');

            DB::table('notifications')->insert([
                'user_id' => $reportRequest->head_id, // إرسال الإشعار لرئيس القسم
                'title' => 'تم تسليم تقرير جديد',
                'message' => "قام المدرب $teacherName بتسليم التقرير المطلوب عن الطالب $studentName",
                'type' => 'report_submitted',
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'تم تسليم التقرير بنجاح وإرسال إشعار لرئيس القسم',
                'report_id' => $reportId
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
