<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentParentController extends Controller
{
    public function requestReport(Request $request)
    {
        try {
            $request->validate([
                'student_id'  => 'required|exists:students,student_id',
                'report_type' => 'required|in:academic,behavioral',
            ]);

            $studentId = $request->student_id;
            $reportType = $request->report_type;

            // 1. جلب بيانات الطالب واسمه الحقيقي من جدول users
            $student = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('students.student_id', $studentId)
                ->select('students.*', 'users.full_name as student_name')
                ->first();

            // 2. شرط الـ 15 يوم
            $existingReport = DB::table('performance_reports')
                ->where('student_id', $studentId)
                ->where('report_type', $reportType)
                ->where('created_at', '>', Carbon::now()->subDays(15))
                ->first();

            if ($existingReport) {
                return response()->json([
                    'message' => 'لقد طلبت تقريراً لهذا الطالب مؤخراً، يمكنك طلب تقرير جديد بعد 15 يوماً.'
                ], 422);
            }

            // --- بداية حساب النسب الحقيقية من الجداول ---

            // 3. حساب نسبة الحضور الحقيقية من جدول attendances
            $totalAttendance = DB::table('attendance')
                ->where('student_id', $studentId)
                ->count();

            $presentCount = DB::table('attendance')
                ->where('student_id', $studentId)
                ->where('status', 'present') 
                ->count();

            $attendanceRate = ($totalAttendance > 0) 
                ? round(($presentCount / $totalAttendance) * 100, 1) 
                : 100; // قيمة افتراضية إذا لم يوجد سجلات بعد

            // 4. حساب متوسط الدرجات الحقيقي من جدول grades
            $averageGrade = DB::table('grades')
                ->where('student_id', $studentId)
                ->avg('score');

            $averageGrade = $averageGrade ? round($averageGrade, 1) : 0;

            // --- نهاية حساب النسب الحقيقية ---

            // 5. حفظ التقرير في قاعدة البيانات بالنسب الحقيقية
            $reportId = DB::table('performance_reports')->insertGetId([
                'student_id'      => $studentId,
                'report_type'     => $reportType,
                'attendance_rate' => $attendanceRate, // النسبة الحقيقية
                'average_grade'   => $averageGrade,   // المعدل الحقيقي
                'recommendations' => 'أداء الطالب مستقر بناءً على البيانات الحالية، ننصح بالاستمرار.',
                'generated_at'    => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // 6. إرسال الإشعار للأب
            $currentUserId = auth()->id(); 

            DB::table('notifications')->insert([
                'user_id'    => $currentUserId, 
                'title'      => 'تقرير جديد جاهز 📄',
                'message'    => 'تم إصدار تقرير للابن: ' . ($student->student_name ?? 'الطالب') . " بمعدل " . $averageGrade . "%",
                'type'       => 'report',
                'is_read'    => 0,
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'تم إنشاء التقرير بنجاح لـ ' . ($student->student_name ?? 'الطالب'),
                'report_id' => $reportId
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

public function getFullPerformance($studentId)
{
    $grades = DB::table('grades')
        ->leftJoin('exams', 'grades.exam_id', '=', 'exams.exam_id')
        ->leftJoin('courses', 'exams.course_id', '=', 'courses.course_id')
        ->where('grades.student_id', $studentId)
        ->select(
            DB::raw('COALESCE(courses.title, "مادة غير محددة") as name'), 
            'grades.score'
        )
        ->get();

    // 2. حساب الحضور لهذا الطالب حصراً
    $totalDays = DB::table('attendance')->where('student_id', $studentId)->count();
    $presentDays = DB::table('attendance')->where('student_id', $studentId)->where('status', 'present')->count();
    $attendanceRate = ($totalDays > 0) ? round(($presentDays / $totalDays) * 100) : 0;

    // 3. سجل الحضور
    $attendanceLogs = DB::table('attendance')
        ->leftJoin('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
        ->leftJoin('courses', 'lessons.course_id', '=', 'courses.course_id')
        ->where('attendance.student_id', $studentId)
        ->select(
            DB::raw('COALESCE(courses.title, "درس") as name'),
            'attendance.status',
            'attendance.attendance_date'
        )
        ->get();

    return response()->json([
        'gpa' => round(($grades->avg('score') / 100) * 4, 2) ?: 0,
        'attendance_rate' => $attendanceRate,
        'present_count' => $presentDays,
        'absent_count' => $totalDays - $presentDays,
        'grades' => $grades,
        'attendance_logs' => $attendanceLogs
    ]);
}

    // جلب واجبات الطالب
    public function getAssignments($studentId)
    {
        // Get all courses the student is enrolled in
        $courseIds = DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('course_id');

        $assignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->leftJoin('assignment_submissions', function ($join) use ($studentId) {
                $join->on('assignments.assignment_id', '=', 'assignment_submissions.assignment_id')
                     ->where('assignment_submissions.student_id', '=', $studentId);
            })
            ->whereIn('assignments.course_id', $courseIds)
            ->select(
                'assignments.assignment_id',
                'assignments.title',
                'courses.title as course_name',
                'assignments.due_date',
                'assignments.max_points',
                'assignment_submissions.submission_id',
                'assignment_submissions.grade',
                'assignment_submissions.submitted_at',
                DB::raw('CASE 
                    WHEN assignment_submissions.submission_id IS NOT NULL THEN "مكتملة"
                    WHEN assignments.due_date < NOW() THEN "فائتة"
                    ELSE "جاري"
                END as status')
            )
            ->orderBy('assignments.due_date', 'desc')
            ->get();

        return response()->json($assignments);
    }

    // جلب أذونات الطالب (طلبات الغياب/الخروج المبكر)
    public function getPermissions($studentId)
    {
        $permissions = DB::table('absence_requests')
            ->where('student_id', $studentId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($permissions);
    }

    // موافقة أو رفض إذن الطالب من قبل الأب
    public function respondPermission(Request $request, $requestId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $affected = DB::table('absence_requests')
            ->where('request_id', $requestId)
            ->update([
                'status' => $request->status,
                'reviewed_by' => auth()->id(),
                'updated_at' => now()
            ]);

        if ($affected) {
            return response()->json(['message' => 'تم تحديث حالة الطلب بنجاح']);
        }

        return response()->json(['message' => 'الطلب غير موجود'], 404);
    }
}