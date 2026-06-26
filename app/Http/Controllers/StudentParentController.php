<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            $studentId     = $request->student_id;
            $reportType    = $request->report_type;
            $currentUserId = Auth::user()?->user_id;

            $student = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('students.student_id', $studentId)
                ->select('students.*', 'users.full_name as student_name')
                ->first();

            // ─── تقرير سلوكي: يُحال إلى المعلم أولاً ───
            if ($reportType === 'behavioral') {
                // منع الطلب المكرر إذا كان هناك طلب معلق بالفعل
                $hasPending = DB::table('report_requests')
                    ->where('student_id', $studentId)
                    ->where('report_type', 'behavioral')
                    ->where('status', 'pending')
                    ->exists();
                if ($hasPending) {
                    return response()->json(['message' => 'يوجد طلب تقرير سلوكي قيد المراجعة حالياً.'], 422);
                }

                // إيجاد معلم مرتبط بالطالب عبر enrollments → course_teachers
                $teacherId = DB::table('enrollments')
                    ->join('course_teachers', 'enrollments.course_id', '=', 'course_teachers.course_id')
                    ->where('enrollments.student_id', $studentId)
                    ->where('enrollments.status', 'active')
                    ->value('course_teachers.teacher_id');

                if (!$teacherId) {
                    return response()->json(['message' => 'لا يوجد معلم مرتبط بهذا الطالب حالياً.'], 422);
                }

                $headUserId = DB::table('heads')->value('user_id') ?? $currentUserId;

                DB::table('report_requests')->insert([
                    'head_id'     => $headUserId,
                    'teacher_id'  => $teacherId,
                    'student_id'  => $studentId,
                    'report_type' => 'behavioral',
                    'status'      => 'pending',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // إشعار المعلم
                $teacherUserId = DB::table('teachers')->where('teacher_id', $teacherId)->value('user_id');
                if ($teacherUserId) {
                    DB::table('notifications')->insert([
                        'user_id'    => $teacherUserId,
                        'title'      => 'طلب تقرير سلوكي',
                        'message'    => 'طلب ولي أمر الطالب ' . ($student->student_name ?? 'الطالب') . ' تقريراً سلوكياً، يُرجى المراجعة.',
                        'type'       => 'report',
                        'is_read'    => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم إرسال طلب التقرير السلوكي للمعلم، سيتم إشعارك عند إتمامه.',
                ], 200);
            }

            // ─── تقرير أكاديمي: يُولَّد تلقائياً ───
            $existingReport = DB::table('performance_reports')
                ->where('student_id', $studentId)
                ->where('report_type', 'academic')
                ->where('created_at', '>', Carbon::now()->subDays(15))
                ->first();

            if ($existingReport) {
                return response()->json([
                    'message' => 'لقد طلبت تقريراً أكاديمياً لهذا الطالب مؤخراً، يمكنك طلب تقرير جديد بعد 15 يوماً.'
                ], 422);
            }

            $totalAttendance = DB::table('attendance')->where('student_id', $studentId)->count();
            $presentCount    = DB::table('attendance')->where('student_id', $studentId)->where('status', 'present')->count();
            $attendanceRate  = ($totalAttendance > 0) ? round(($presentCount / $totalAttendance) * 100, 1) : 100;

            $averageGrade = DB::table('grades')->where('student_id', $studentId)->avg('score');
            $averageGrade = $averageGrade ? round($averageGrade, 1) : 0;

            $reportId = DB::table('performance_reports')->insertGetId([
                'student_id'      => $studentId,
                'report_type'     => 'academic',
                'attendance_rate' => $attendanceRate,
                'average_grade'   => $averageGrade,
                'recommendations' => 'أداء الطالب مستقر بناءً على البيانات الحالية، ننصح بالاستمرار.',
                'generated_at'    => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('notifications')->insert([
                'user_id'    => $currentUserId,
                'title'      => 'تقرير أكاديمي جاهز',
                'message'    => 'تم إصدار التقرير الأكاديمي للابن: ' . ($student->student_name ?? 'الطالب') . ' بمعدل ' . $averageGrade . '%',
                'type'       => 'report',
                'is_read'    => 0,
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء التقرير الأكاديمي بنجاح لـ ' . ($student->student_name ?? 'الطالب'),
                'report_id' => $reportId,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function getFullPerformance($studentId)
    {
        // بيانات العلامات من النظام الجديد (grade_entries + grade_events)
        // $studentId هنا هو students.student_id (يأتي من /parent/children)
        $student = DB::table('students')->where('student_id', $studentId)->first();
        $internalStudentId = $student?->student_id ?? $studentId;

        $newGrades = $internalStudentId
            ? DB::table('grade_entries')
                ->join('grade_events', 'grade_entries.grade_event_id', '=', 'grade_events.id')
                ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
                ->leftJoin('programs', 'grade_events.program_id', '=', 'programs.id')

                ->where('grade_entries.student_id', $internalStudentId)
                ->whereNotNull('grade_entries.score')
                ->select(
                    DB::raw("COALESCE(courses.title, programs.name, 'تقييم شفهي') as course_name"),
                    'courses.course_id',
                    DB::raw("CASE
                        WHEN grade_events.type = 'exam'  THEN CONCAT('امتحان: ', grade_events.title)
                        WHEN grade_events.type = 'quiz'  THEN CONCAT('مذاكرة: ', grade_events.title)
                        WHEN grade_events.type = 'oral'  THEN CONCAT('شفهي: ',   grade_events.title)
                        ELSE grade_events.title
                    END as exam_name"),
                    'grade_events.max_score',
                    'grade_entries.score',
                    'grade_entries.notes'
                )
                ->get()
            : collect();

        // بيانات العلامات من النظام القديم (grades + exams)
        $oldGrades = DB::table('grades')
            ->leftJoin('exams', 'grades.exam_id', '=', 'exams.exam_id')
            ->leftJoin('courses', 'exams.course_id', '=', 'courses.course_id')
            ->where('grades.student_id', $studentId)
            ->select(
                DB::raw('COALESCE(courses.title, "مادة غير محددة") as course_name'),
                'courses.course_id',
                DB::raw('COALESCE(exams.exam_name, "اختبار") as exam_name'),
                DB::raw('COALESCE(exams.max_score, 100) as max_score'),
                'grades.score'
            )
            ->get();

        $gradesRaw = $newGrades->merge($oldGrades);

        // تجميع حسب المادة
        $grouped = [];
        foreach ($gradesRaw as $g) {
            $key = $g->course_id ?? ('unknown_' . $g->course_name);
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['name' => $g->course_name, 'exams' => [], 'total' => 0, 'count' => 0];
            }
            $grouped[$key]['exams'][] = [
                'exam_name' => $g->exam_name,
                'score'     => (float) $g->score,
                'max_score' => (float) ($g->max_score ?? 100),
            ];
            $maxSc = (float) ($g->max_score ?? 100);
            $grouped[$key]['total'] += $maxSc > 0 ? ((float) $g->score / $maxSc) * 100 : 0;
            $grouped[$key]['count']++;
        }

        $grades = array_values(array_map(function ($c) {
            return [
                'name'  => $c['name'],
                'score' => $c['count'] > 0 ? round($c['total'] / $c['count'], 1) : 0,
                'exams' => $c['exams'],
            ];
        }, $grouped));

        $totalDays      = DB::table('attendance')->where('student_id', $studentId)->count();
        $presentDays    = DB::table('attendance')->where('student_id', $studentId)->where('status', 'present')->count();
        $attendanceRate = ($totalDays > 0) ? round(($presentDays / $totalDays) * 100) : 0;

        $attendanceLogs = DB::table('attendance')
            ->leftJoin('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
            ->leftJoin('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->where('attendance.student_id', $studentId)
            ->select(DB::raw('COALESCE(courses.title, "درس") as name'), 'attendance.status', 'attendance.attendance_date')
            ->get();

        return response()->json([
            'gpa'             => count($grades) > 0 ? round((array_sum(array_column($grades, 'score')) / count($grades) / 100) * 4, 2) : 0,
            'attendance_rate' => $attendanceRate,
            'present_count'   => $presentDays,
            'absent_count'    => $totalDays - $presentDays,
            'grades'          => $grades,
            'attendance_logs' => $attendanceLogs,
        ]);
    }

    public function getAssignments($studentId)
    {
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
                'assignments.file_path',
                'assignments.file_name',
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

    public function getPermissions($studentId)
    {
        $permissions = DB::table('absence_requests')
            ->where('student_id', $studentId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($permissions);
    }

    public function getLeaveRequests(Request $request)
    {
        $parent = DB::table('parents')->where('user_id', $request->user()->user_id)->first();
        if (!$parent) return response()->json(['success' => true, 'data' => []]);

        $studentIds = DB::table('parent_students')
            ->where('parent_id', $parent->parent_id)
            ->pluck('student_id');

        $userIds = DB::table('students')
            ->whereIn('student_id', $studentIds)
            ->pluck('user_id');

        $query = DB::table('leave_requests')
            ->join('users', 'leave_requests.student_id', '=', 'users.user_id')
            ->whereIn('leave_requests.student_id', $userIds);

        // فلتر بابن محدد إذا تم تمريره
        if ($request->filled('student_id')) {
            $studentUserId = DB::table('students')
                ->where('student_id', $request->student_id)
                ->value('user_id');
            if ($studentUserId) {
                $query->where('leave_requests.student_id', $studentUserId);
            }
        }

        $requests = $query->select(
                'leave_requests.id',
                'leave_requests.type',
                'leave_requests.date',
                'leave_requests.reason',
                'leave_requests.status',
                'users.full_name as student_name'
            )
            ->orderBy('leave_requests.created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function respondLeaveRequest(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);

        $leaveRequest = DB::table('leave_requests')->where('id', $id)->first();
        if (!$leaveRequest) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        if ($request->status === 'approved') {
            // ولي الأمر وافق → ينتقل لرئيس القسم
            DB::table('leave_requests')
                ->where('id', $id)
                ->update(['status' => 'pending_hod', 'updated_at' => now()]);

            $studentUser = DB::table('users')->where('user_id', $leaveRequest->student_id)->first();
            $studentName = $studentUser->full_name ?? 'الطالب';

            $headUserId = DB::table('heads')->value('user_id')
                ?? DB::table('users')->where('role_id', 5)->value('user_id');
            if ($headUserId) {
                $alreadyNotified = DB::table('notifications')
                    ->where('user_id', $headUserId)
                    ->where('type', 'leave_request')
                    ->where('related_id', $id)
                    ->exists();
                if (!$alreadyNotified) {
                    DB::table('notifications')->insert([
                        'user_id'    => $headUserId,
                        'title'      => 'طلب إجازة بانتظار موافقتك',
                        'message'    => 'وافق ولي أمر الطالب ' . $studentName . ' على طلب إجازة بتاريخ ' . $leaveRequest->date . '، يرجى مراجعته',
                        'type'       => 'leave_request',
                        'related_id' => $id,
                        'is_read'    => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } else {
            // ولي الأمر رفض → إشعار الطالب
            DB::table('leave_requests')
                ->where('id', $id)
                ->update(['status' => 'rejected', 'updated_at' => now()]);

            if ($leaveRequest->student_id) {
                $typeText = $leaveRequest->type === 'hourly' ? 'الساعية' : 'اليومية';
                DB::table('notifications')->insert([
                    'user_id'    => $leaveRequest->student_id,
                    'title'      => 'تم رفض طلب الإجازة',
                    'message'    => 'تم رفض طلب إجازتك ' . $typeText . ' بتاريخ ' . $leaveRequest->date . ' من قِبل ولي الأمر',
                    'type'       => 'leave_request',
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الطلب']);
    }

    public function getReportHistory(Request $request)
    {
        $parent = DB::table('parents')->where('user_id', $request->user()->user_id)->first();
        if (!$parent) return response()->json(['success' => true, 'data' => []]);

        $allStudentIds = DB::table('parent_students')
            ->where('parent_id', $parent->parent_id)
            ->pluck('student_id');

        // فلترة اختيارية حسب طالب محدد
        $filterStudentId = $request->query('student_id');
        $studentIds = ($filterStudentId && $allStudentIds->contains($filterStudentId))
            ? collect([$filterStudentId])
            : $allStudentIds;

        // التقارير المكتملة من performance_reports
        $completed = DB::table('performance_reports')
            ->whereIn('performance_reports.student_id', $studentIds)
            ->join('students', 'performance_reports.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select(
                'performance_reports.report_id as id',
                'performance_reports.report_type',
                'performance_reports.attendance_rate',
                'performance_reports.average_grade',
                'performance_reports.recommendations',
                'performance_reports.created_at',
                'users.full_name as student_name',
                DB::raw("'completed' as status")
            )
            ->get()
            ->map(fn($r) => (array) $r);

        // طلبات التقارير السلوكية المعلقة عند المعلم
        $pendingBehavioral = DB::table('report_requests')
            ->whereIn('report_requests.student_id', $studentIds)
            ->where('report_requests.status', 'pending')
            ->where('report_requests.report_type', 'behavioral')
            ->join('students', 'report_requests.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select(
                'report_requests.id',
                DB::raw("'behavioral' as report_type"),
                DB::raw('null as attendance_rate'),
                DB::raw('null as average_grade'),
                DB::raw("null as recommendations"),
                'report_requests.created_at',
                'users.full_name as student_name',
                DB::raw("'pending_teacher' as status")
            )
            ->get()
            ->map(fn($r) => (array) $r);

        $all = collect($completed)
            ->concat($pendingBehavioral)
            ->sortByDesc('created_at')
            ->values();

        return response()->json(['success' => true, 'data' => $all]);
    }

    public function submitParentLeaveRequest(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'type'       => 'required|in:full_day,hourly',
            'date'       => 'required|date',
            'reason'     => 'required|string|min:3',
        ]);

        $parent = DB::table('parents')->where('user_id', $request->user()->user_id)->first();
        if (!$parent) return response()->json(['message' => 'ولي الأمر غير موجود'], 404);

        $linked = DB::table('parent_students')
            ->where('parent_id', $parent->parent_id)
            ->where('student_id', $request->student_id)
            ->exists();

        if (!$linked) return response()->json(['message' => 'الطالب غير مرتبط بهذا الحساب'], 403);

        // Get student user_id (leave_requests uses user_id as student_id column)
        $studentUser = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $request->student_id)
            ->select('users.user_id', 'users.full_name')
            ->first();

        if (!$studentUser) return response()->json(['message' => 'الطالب غير موجود'], 404);

        $leaveId = DB::table('leave_requests')->insertGetId([
            'student_id' => $studentUser->user_id,
            'type'       => $request->type,
            'date'       => $request->date,
            'reason'     => $request->reason,
            'status'     => 'pending_hod',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Notify dept head
        $headUserId = DB::table('heads')->value('user_id');

        if ($headUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $headUserId,
                'title'      => 'طلب إجازة من ولي الأمر',
                'message'    => 'قدّم ولي أمر الطالب ' . ($studentUser->full_name ?? 'الطالب') . ' طلب إجازة بتاريخ ' . $request->date,
                'type'       => 'leave_request',
                'related_id' => $leaveId,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'تم إرسال طلب الإجازة بنجاح، بانتظار موافقة رئيس القسم']);
    }

    public function respondPermission(Request $request, $requestId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $affected = DB::table('absence_requests')
            ->where('request_id', $requestId)
            ->update([
                'status'      => $request->status,
                'reviewed_by' => Auth::user()?->user_id,
                'updated_at'  => now(),
            ]);

        if ($affected) {
            return response()->json(['message' => 'تم تحديث حالة الطلب بنجاح']);
        }

        return response()->json(['message' => 'الطلب غير موجود'], 404);
    }
}
