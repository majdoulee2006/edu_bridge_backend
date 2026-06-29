<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\AssignmentSubmission;
use App\Models\Schedule;
use App\Models\Announcement;
use App\Models\Parents;
use App\Models\ParentStudent;
use App\Models\StudentParent;

class ParentController extends Controller
{
    public function getChildren(Request $request)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'هذه الخدمة متاحة فقط لأولياء الأمور'], 403);
        }

        $children = $parent->students()
            ->with(['user', 'attendances', 'grades'])
            ->get()
            ->map(function($student) {
                $attendances = $student->attendances;
                
                $total = $attendances->count();
                $present = $attendances->where('status', 'present')->count();
                
                $pending = 0;
                foreach ($attendances as $att) {
                    $isToday = \Carbon\Carbon::parse($att->attendance_date)->isToday();
                    if ($att->status === 'absent' && $isToday) {
                        $pending++;
                    }
                }
                
                $effectiveTotal = $total - $pending;
                $attendanceRate = $effectiveTotal > 0
                    ? round(($present / $effectiveTotal) * 100, 1)
                    : 100;

                return [
                    'id'              => $student->user_id,
                    'student_id'      => $student->student_id,
                    'name'            => $student->user->full_name,
                    'full_name'       => $student->user->full_name,
                    'student_code'    => $student->student_code ?? '',
                    'level'           => $student->level ?? '',
                    'total_courses'   => $student->courses->count(),
                    'attendance_rate' => $attendanceRate,
                    'average_grade'   => round($student->grades()->avg('score') ?? 0, 1),
                ];
            });

        return response()->json(['success' => true, 'data' => $children], 200);
    }

    public function getChildDetails(Request $request, $childId)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $child = $parent->students()->where('students.student_id', $childId)->first();

        if (!$child) {
            return response()->json(['success' => false, 'message' => 'الطفل غير موجود أو غير مرتبط بحسابك'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $child->user_id,
                'name' => $child->user->full_name,
                'student_code' => $child->student_code ?? '',
                'email' => $child->user->email,
                'phone' => $child->user->phone,
                'level' => $child->level ?? '',
                'academic_year' => $child->user->academic_year,
            ]
        ], 200);
    }

    public function getChildAttendance(Request $request, $childId)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $child = $parent->students()->where('students.student_id', $childId)->first();

        if (!$child) {
            return response()->json(['success' => false, 'message' => 'الطفل غير موجود'], 404);
        }

        $attendances = Attendance::where('student_id', $child->student_id)
            ->with(['lesson.course'])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(function($attendance) {
                $isToday = \Carbon\Carbon::parse($attendance->attendance_date)->isToday();
                $status = $attendance->status;
                if ($status === 'absent' && $isToday) {
                    $status = 'pending';
                    $statusText = 'قيد الانتظار';
                } else {
                    $statusText = $attendance->status == 'present' ? 'حاضر' : ($attendance->status == 'absent' ? 'غائب' : 'متأخر');
                }
                
                $dateFormatted = $attendance->attendance_date instanceof \Carbon\Carbon 
                    ? $attendance->attendance_date->format('Y-m-d')
                    : \Carbon\Carbon::parse($attendance->attendance_date)->format('Y-m-d');
                    
                return [
                    'id' => $attendance->attendance_id,
                    'date' => $dateFormatted,
                    'status' => $status,
                    'status_text' => $statusText,
                    'course_name' => $attendance->lesson->course->title ?? '',
                    'lesson_title' => $attendance->lesson->title ?? '',
                ];
            });

        $statistics = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'percentage' => $attendances->count() > 0
                ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1)
                : 0,
        ];

        return response()->json(['success' => true, 'statistics' => $statistics, 'data' => $attendances], 200);
    }

    public function getChildGrades(Request $request, $childId)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $child = $parent->students()->where('students.student_id', $childId)->first();

        if (!$child) {
            return response()->json(['success' => false, 'message' => 'الطفل غير موجود'], 404);
        }

        $grades = Grade::where('student_id', $child->student_id)
            ->with(['exam.course'])
            ->get()
            ->groupBy(function($grade) {
                return $grade->exam->course->title ?? 'غير مصنف';
            })
            ->map(function($grades, $courseName) {
                return [
                    'course_name' => $courseName,
                    'grades' => $grades->map(function($grade) {
                        return [
                            'id' => $grade->grade_id,
                            'exam_name' => $grade->exam->exam_name,
                            'score' => $grade->score,
                            'max_score' => $grade->exam->max_score,
                            'percentage' => round(($grade->score / $grade->exam->max_score) * 100, 1),
                            'date' => $grade->exam->exam_date->format('Y-m-d'),
                        ];
                    }),
                    'average' => round($grades->avg('score'), 1),
                ];
            })->values();

        $overallAverage = Grade::where('student_id', $child->student_id)->avg('score');

        return response()->json([
            'success' => true,
            'overall_average' => round($overallAverage ?? 0, 1),
            'data' => $grades
        ], 200);
    }

    public function getChildSchedule(Request $request, $childId)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $child = $parent->students()->where('students.student_id', $childId)->first();

        if (!$child) {
            return response()->json(['success' => false, 'message' => 'الطفل غير موجود'], 404);
        }

        $schedules = Schedule::whereHas('course', function($query) use ($child) {
                $query->whereHas('students', function($q) use ($child) {
                    $q->where('student_id', $child->student_id);
                });
            })
            ->with('course')
            ->orderByRaw("CASE day WHEN 'Saturday' THEN 1 WHEN 'Sunday' THEN 2 WHEN 'Monday' THEN 3 WHEN 'Tuesday' THEN 4 WHEN 'Wednesday' THEN 5 WHEN 'Thursday' THEN 6 WHEN 'Friday' THEN 7 ELSE 8 END")
            ->orderBy('start_time')
            ->get()
            ->groupBy('day')
            ->map(function($items, $day) {
                $dayNames = [
                    'Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء',
                    'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت', 'Sunday' => 'الأحد',
                ];

                return [
                    'day' => $dayNames[$day] ?? $day,
                    'lectures' => $items->map(function($item) {
                        return [
                            'id' => $item->schedule_id,
                            'course_name' => $item->course->title,
                            'start_time' => date('h:i A', strtotime($item->start_time)),
                            'end_time' => date('h:i A', strtotime($item->end_time)),
                            'room' => $item->room,
                        ];
                    })
                ];
            })->values();

        return response()->json(['success' => true, 'data' => $schedules], 200);
    }

    public function getChildAssignments(Request $request, $childId)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $child = $parent->students()->where('students.student_id', $childId)->first();

        if (!$child) {
            return response()->json(['success' => false, 'message' => 'الطفل غير موجود'], 404);
        }

        $submissions = AssignmentSubmission::where('student_id', $child->student_id)
            ->with(['assignment.course'])
            ->get()
            ->map(function($submission) {
                return [
                    'id' => $submission->submission_id,
                    'title' => $submission->assignment->title,
                    'course_name' => $submission->assignment->course->title ?? '',
                    'due_date' => $submission->assignment->due_date->format('Y-m-d'),
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d') : null,
                    'grade' => $submission->grade,
                    'max_points' => $submission->assignment->max_points,
                    'status' => $submission->grade ? 'مصحح' : ($submission->submitted_at ? 'تم التسليم' : 'قيد الانتظار'),
                    'feedback' => $submission->feedback,
                ];
            });

        $statistics = [
            'total' => $submissions->count(),
            'submitted' => $submissions->where('submitted_at', '!=', null)->count(),
            'graded' => $submissions->where('grade', '!=', null)->count(),
            'pending' => $submissions->where('submitted_at', null)->count(),
        ];

        return response()->json(['success' => true, 'statistics' => $statistics, 'data' => $submissions], 200);
    }

    public function getAnnouncements(Request $request)
    {
        $announcements = Announcement::latest()->limit(20)->get()->map(function($announcement) {
            return [
                'id' => $announcement->announcement_id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'type' => $announcement->type,
                'created_at' => $announcement->created_at ? $announcement->created_at->format('Y-m-d H:i') : null,
                'time_ago' => $announcement->created_at ? $announcement->created_at->diffForHumans() : 'منذ قليل',
            ];
        });

        return response()->json(['success' => true, 'data' => $announcements], 200);
    }

    public function linkStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_code' => 'required|string|exists:students,student_code',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'هذه الخدمة متاحة فقط لأولياء الأمور'], 403);
        }

        $student = Student::where('student_code', $request->student_code)->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'الكود الجامعي غير صحيح'], 404);
        }

        // ── التحقق من تطابق الاسم الأخير (lastname) بين الوالد والطالب ──
        $parentUser  = $request->user();
        $studentUser = User::find($student->user_id);

        // جلب الكنية من عمود last_name إذا كان موجوداً، وإلا أخذ آخر كلمة من full_name
        $parentParts = preg_split('/\s+/', trim($parentUser->full_name ?? ''));
        $studentParts = preg_split('/\s+/', trim($studentUser->full_name ?? ''));

        $parentLastName  = $parentUser->last_name ?? end($parentParts);
        $studentLastName = $studentUser->last_name ?? end($studentParts);

        // تجاهل المسافات وحالة الأحرف
        $parentLastName  = mb_strtolower(trim($parentLastName), 'UTF-8');
        $studentLastName = mb_strtolower(trim($studentLastName), 'UTF-8');

        if (empty($studentLastName) || $parentLastName !== $studentLastName) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن ربط هذا الطالب. اسم العائلة لا يتطابق مع اسم عائلتك.',
            ], 403);
        }

        // التأكد من عدم وجود الرابط مسبقاً
        $exists = StudentParent::where('parent_id', $parent->parent_id)
            ->where('student_id', $student->student_id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'هذا الطالب مرتبط بالفعل بحسابك'], 400);
        }

        StudentParent::create([
            'parent_id'    => $parent->parent_id,
            'student_id'   => $student->student_id,
            'relationship' => $request->input('relationship', 'father'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم ربط الطالب بنجاح',
            'data' => [
                'id'           => $studentUser->user_id,
                'name'         => $studentUser->full_name,
                'student_code' => $student->student_code,
            ]
        ], 200);
    }

    public function dashboard(Request $request)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $children = $parent->students()->with(['attendances', 'grades'])->get();

        $totalChildren = $children->count();
        $totalAbsences = 0;
        $totalLate = 0;
        $averageGrades = [];

        foreach ($children as $child) {
            $attendances = $child->attendances;
            $absentCount = 0;
            foreach ($attendances as $att) {
                $isToday = \Carbon\Carbon::parse($att->attendance_date)->isToday();
                if ($att->status === 'absent' && !$isToday) {
                    $absentCount++;
                }
            }
            $totalAbsences += $absentCount;
            $totalLate += $attendances->where('status', 'late')->count();
            $averageGrades[] = $child->grades->avg('score') ?? 0;
        }

        $recentAnnouncements = Announcement::latest()->limit(5)->get()->map(function($ann) {
            return [
                'id' => $ann->announcement_id,
                'title' => $ann->title,
                'time_ago' => $ann->created_at ? $ann->created_at->diffForHumans() : 'منذ قليل',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_children' => $totalChildren,
                'total_absences' => $totalAbsences,
                'total_late' => $totalLate,
                'average_children_grades' => round(collect($averageGrades)->avg(), 1),
                'recent_announcements' => $recentAnnouncements,
                'children' => $children->map(function($child) {
                    $attendances = $child->attendances;
                    
                    $total = $attendances->count();
                    $present = $attendances->where('status', 'present')->count();
                    
                    $pending = 0;
                    foreach ($attendances as $att) {
                        $isToday = \Carbon\Carbon::parse($att->attendance_date)->isToday();
                        if ($att->status === 'absent' && $isToday) {
                            $pending++;
                        }
                    }
                    
                    $effectiveTotal = $total - $pending;
                    $attendanceRate = $effectiveTotal > 0
                        ? round(($present / $effectiveTotal) * 100, 1)
                        : 100;
                        
                    return [
                        'student_id' => $child->student_id,
                        'full_name' => $child->user->full_name,
                        'attendance_rate' => $attendanceRate,
                        'average_grade' => round($child->grades->avg('score') ?? 0, 1),
                        'level' => $child->level ?? 'غير محدد'
                    ];
                }),
            ]
        ], 200);
    }

    public function requestReport(Request $request)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $studentId = $request->input('student_id');
        $reportType = $request->input('report_type');

        $child = $parent->students()->where('students.student_id', $studentId)->first();

        if (!$child) {
            return response()->json(['success' => false, 'message' => 'الطفل غير موجود أو غير مرتبط بحسابك'], 404);
        }

        // إيجاد المربي المخصص لهذا الطالب بناءً على الفرع والسنة
        $studentData = \Illuminate\Support\Facades\DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->where('students.student_id', $child->student_id)
            ->select('users.academic_year', 'programs.name as branch_name')
            ->first();

        $advisorTeacher = null;
        if ($studentData && $studentData->branch_name && $studentData->academic_year) {
            $advisorTeacher = \Illuminate\Support\Facades\DB::table('teachers')
                ->where('advisor_branch', $studentData->branch_name)
                ->where('advisor_year', $studentData->academic_year)
                ->first();
        }

        // تحقق من التقرير الأكاديمي إذا كان مطلوباً
        if ($reportType === 'academic') {
            // تحقق من الـ 7 أيام للتقرير الأكاديمي
            $recentAcademic = \Illuminate\Support\Facades\DB::table('report_requests')
                ->where('head_id', $request->user()->user_id)
                ->where('student_id', $child->student_id)
                ->where('report_type', 'academic')
                ->where('created_at', '>', now()->subDays(7))
                ->first();

            if ($recentAcademic) {
                return response()->json(['success' => false, 'message' => 'لا يمكنك طلب تقرير أكاديمي أكثر من مرة خلال 7 أيام.'], 400);
            }

            // حساب نسبة الحضور
            $attendances = Attendance::where('student_id', $child->student_id)->get();
            $attendanceRate = $attendances->count() > 0
                ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1)
                : 0;

            // حساب المعدل
            $averageGrade = round(Grade::where('student_id', $child->student_id)->avg('score') ?? 0, 1);

            // إنشاء الطلب بحالة مكتمل
            $requestId = \Illuminate\Support\Facades\DB::table('report_requests')->insertGetId([
                'head_id' => $request->user()->user_id,
                'teacher_id' => $advisorTeacher ? $advisorTeacher->teacher_id : null,
                'student_id' => $child->student_id,
                'report_type' => 'academic',
                'notes' => 'طلب تقرير أكاديمي من النظام تلقائياً',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // إنشاء التقرير فوراً
            \Illuminate\Support\Facades\DB::table('performance_reports')->insert([
                'student_id' => $child->student_id,
                'report_type' => 'academic',
                'attendance_rate' => $attendanceRate,
                'average_grade' => $averageGrade,
                'recommendations' => 'تم إصدار هذا التقرير الأكاديمي آلياً من النظام بناءً على أحدث علامات وحضور للطالب.',
                'generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'تم إصدار التقرير الأكاديمي بنجاح.',
                'request_id' => $requestId
            ], 200);
        }

        // ==========================================
        // إذا كان التقرير سلوكياً (Behavioral)
        // ==========================================
        if (!$advisorTeacher) {
            return response()->json(['success' => false, 'message' => 'لم يتم تعيين مربي دائم لهذا الطالب بعد لطلب تقرير سلوكي.'], 404);
        }

        // تحقق من الـ 15 يوماً للتقرير السلوكي
        $recentBehavioral = \Illuminate\Support\Facades\DB::table('report_requests')
            ->where('head_id', $request->user()->user_id)
            ->where('student_id', $child->student_id)
            ->where('report_type', 'behavioral')
            ->where('created_at', '>', now()->subDays(15))
            ->first();

        if ($recentBehavioral) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك طلب تقرير سلوكي أكثر من مرة خلال 15 يوماً.'], 400);
        }

        $requestId = \Illuminate\Support\Facades\DB::table('report_requests')->insertGetId([
            'head_id' => $request->user()->user_id, // Parent's User ID
            'teacher_id' => $advisorTeacher->teacher_id,
            'student_id' => $child->student_id,
            'report_type' => 'behavioral',
            'notes' => 'طلب تقرير سلوكي من ولي الأمر',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'تم إرسال طلب التقرير بنجاح.',
            'request_id' => $requestId
        ], 200); // 200 instead of 201 for standard flutter dio expecting 200
    }

    public function getReportsHistory(Request $request)
    {
        $parent = Parents::where('user_id', $request->user()->user_id)->first();

        if (!$parent) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $query = \Illuminate\Support\Facades\DB::table('report_requests')
            ->join('students', 'report_requests.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('report_requests.head_id', $request->user()->user_id)
            ->select(
                'report_requests.*',
                'users.full_name as student_name',
                \Illuminate\Support\Facades\DB::raw("IF(report_requests.status = 'pending', 'pending_teacher', report_requests.status) as status")
            )
            ->orderByDesc('report_requests.created_at');

        if ($request->has('student_id')) {
            $query->where('report_requests.student_id', $request->input('student_id'));
        }

        $history = $query->get();

        // لجلب التقارير السابقة من performance_reports
        // سنفترض أن الطلبات المكتملة تعود بمعلومات التقرير
        $completedRequests = $history->where('status', 'completed');
        foreach ($completedRequests as $req) {
            $report = \Illuminate\Support\Facades\DB::table('performance_reports')
                ->where('student_id', $req->student_id)
                ->where('report_type', $req->report_type)
                ->where('created_at', '>=', $req->updated_at) // تقريبي
                ->orderByDesc('created_at')
                ->first();
                
            if ($report) {
                $req->average_grade = $report->average_grade;
                $req->attendance_rate = $report->attendance_rate;
                $req->recommendations = $report->recommendations;
            }
        }

        return response()->json(['success' => true, 'data' => $history], 200);
    }
}
