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

class ParentController extends Controller
{
    /**
     * جلب أبناء ولي الأمر
     * GET /api/parent/children
     */
    public function getChildren(Request $request)
    {
        $parent = $request->user();

        if (!$parent->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الخدمة متاحة فقط لأولياء الأمور'
            ], 403);
        }

        $children = $parent->children()
            ->with(['student.courses', 'student.attendances'])
            ->get()
            ->map(function($child) {
                $attendances = $child->student->attendances;

                return [
                    'id' => $child->user_id,
                    'name' => $child->full_name,
                    'student_code' => $child->student->student_code ?? '',
                    'level' => $child->student->level ?? '',
                    'total_courses' => $child->student->courses->count(),
                    'attendance_rate' => $attendances->count() > 0
                        ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1)
                        : 0,
                    'last_login' => $child->last_login,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $children
        ], 200);
    }

    /**
     * جلب تفاصيل طفل محدد
     * GET /api/parent/child/{childId}
     */
    public function getChildDetails(Request $request, $childId)
    {
        $parent = $request->user();

        $child = $parent->children()->where('user_id', $childId)->first();

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'الطفل غير موجود أو غير مرتبط بحسابك'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $child->user_id,
                'name' => $child->full_name,
                'student_code' => $child->student->student_code ?? '',
                'email' => $child->email,
                'phone' => $child->phone,
                'level' => $child->student->level ?? '',
                'department' => $child->department,
                'academic_year' => $child->academic_year,
            ]
        ], 200);
    }

    /**
     * جلب حضور طفل
     * GET /api/parent/child/{childId}/attendance
     */
    public function getChildAttendance(Request $request, $childId)
    {
        $parent = $request->user();

        $child = $parent->children()->where('user_id', $childId)->first();

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'الطفل غير موجود أو غير مرتبط بحسابك'
            ], 404);
        }

        $attendances = Attendance::where('student_id', $child->student->student_id)
            ->with(['lesson.course'])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(function($attendance) {
                return [
                    'id' => $attendance->attendance_id,
                    'date' => $attendance->attendance_date->format('Y-m-d'),
                    'status' => $attendance->status,
                    'status_text' => $attendance->status == 'present' ? 'حاضر' : ($attendance->status == 'absent' ? 'غائب' : 'متأخر'),
                    'course_name' => $attendance->lesson->course->title ?? '',
                    'lesson_title' => $attendance->lesson->title ?? '',
                ];
            });

        // إحصائيات
        $statistics = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'percentage' => $attendances->count() > 0
                ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1)
                : 0,
        ];

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
            'data' => $attendances
        ], 200);
    }

    /**
     * جلب علامات طفل
     * GET /api/parent/child/{childId}/grades
     */
    public function getChildGrades(Request $request, $childId)
    {
        $parent = $request->user();

        $child = $parent->children()->where('user_id', $childId)->first();

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'الطفل غير موجود أو غير مرتبط بحسابك'
            ], 404);
        }

        $grades = Grade::where('student_id', $child->student->student_id)
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

        $overallAverage = Grade::where('student_id', $child->student->student_id)->avg('score');

        return response()->json([
            'success' => true,
            'overall_average' => round($overallAverage ?? 0, 1),
            'data' => $grades
        ], 200);
    }

    /**
     * جلب جدول طفل
     * GET /api/parent/child/{childId}/schedule
     */
    public function getChildSchedule(Request $request, $childId)
    {
        $parent = $request->user();

        $child = $parent->children()->where('user_id', $childId)->first();

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'الطفل غير موجود أو غير مرتبط بحسابك'
            ], 404);
        }

        $schedules = Schedule::whereHas('course', function($query) use ($child) {
                $query->whereHas('students', function($q) use ($child) {
                    $q->where('student_id', $child->student->student_id);
                });
            })
            ->with('course')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->orderBy('start_time')
            ->get()
            ->groupBy('day')
            ->map(function($items, $day) {
                // ترجمة اسم اليوم
                $dayNames = [
                    'Monday' => 'الإثنين',
                    'Tuesday' => 'الثلاثاء',
                    'Wednesday' => 'الأربعاء',
                    'Thursday' => 'الخميس',
                    'Friday' => 'الجمعة',
                    'Saturday' => 'السبت',
                    'Sunday' => 'الأحد',
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

        return response()->json([
            'success' => true,
            'data' => $schedules
        ], 200);
    }

    /**
     * جلب واجبات طفل
     * GET /api/parent/child/{childId}/assignments
     */
    public function getChildAssignments(Request $request, $childId)
    {
        $parent = $request->user();

        $child = $parent->children()->where('user_id', $childId)->first();

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'الطفل غير موجود أو غير مرتبط بحسابك'
            ], 404);
        }

        $submissions = AssignmentSubmission::where('student_id', $child->student->student_id)
            ->with(['assignment.course'])
            ->get()
            ->map(function($submission) {
                return [
                    'id' => $submission->submission_id,
                    'title' => $submission->assignment->title,
                    'course_name' => $submission->assignment->course->title ?? '',
                    'description' => $submission->assignment->description,
                    'due_date' => $submission->assignment->due_date->format('Y-m-d'),
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d') : null,
                    'grade' => $submission->grade,
                    'max_points' => $submission->assignment->max_points,
                    'status' => $submission->grade ? 'مصحح' : ($submission->submitted_at ? 'تم التسليم' : 'قيد الانتظار'),
                    'feedback' => $submission->feedback,
                ];
            });

        // إحصائيات
        $statistics = [
            'total' => $submissions->count(),
            'submitted' => $submissions->where('submitted_at', '!=', null)->count(),
            'graded' => $submissions->where('grade', '!=', null)->count(),
            'pending' => $submissions->where('submitted_at', null)->count(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
            'data' => $submissions
        ], 200);
    }

    /**
     * جلب إعلانات المدرسة
     * GET /api/parent/announcements
     */
    public function getAnnouncements(Request $request)
    {
        $announcements = Announcement::latest()
            ->limit(20)
            ->get()
            ->map(function($announcement) {
                return [
                    'id' => $announcement->announcement_id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'type' => $announcement->type,
                    'created_at' => $announcement->created_at->format('Y-m-d H:i'),
                    'time_ago' => $announcement->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $announcements
        ], 200);
    }

    /**
     * ربط طالب جديد (لولي الأمر)
     * POST /api/parent/link-student
     */
    public function linkStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_code' => 'required|string|exists:students,student_code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $parent = $request->user();

        if (!$parent->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الخدمة متاحة فقط لأولياء الأمور'
            ], 403);
        }

        $student = Student::where('student_code', $request->student_code)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'الكود الجامعي غير صحيح'
            ], 404);
        }

        // التأكد أن الطالب غير مرتبط بالفعل بولي أمر آخر
        if ($student->parent_id) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطالب مرتبط بالفعل بولي أمر آخر'
            ], 400);
        }

        // جلب سجل ولي الأمر من جدول parents
        $parentRecord = \App\Models\Parents::where('user_id', $parent->user_id)->first();

        if (!$parentRecord) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات ولي الأمر غير مكتملة'
            ], 400);
        }

        // ربط الطالب بولي الأمر
        $student->parent_id = $parentRecord->parent_id;
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'تم ربط الطالب بنجاح',
            'data' => [
                'id' => $student->user->user_id,
                'name' => $student->user->full_name,
                'student_code' => $student->student_code,
            ]
        ], 200);
    }

    /**
     * Dashboard لولي الأمر (إحصائيات سريعة)
     * GET /api/parent/dashboard
     */
    public function dashboard(Request $request)
    {
        $parent = $request->user();

        if (!$parent->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الخدمة متاحة فقط لأولياء الأمور'
            ], 403);
        }

        $children = $parent->children()->with(['student.attendances', 'student.grades'])->get();

        $totalChildren = $children->count();
        $totalAbsences = 0;
        $totalLate = 0;
        $averageGrades = [];

        foreach ($children as $child) {
            $attendances = $child->student->attendances;
            $totalAbsences += $attendances->where('status', 'absent')->count();
            $totalLate += $attendances->where('status', 'late')->count();
            $averageGrades[] = $child->student->grades->avg('score') ?? 0;
        }

        $recentAnnouncements = Announcement::latest()->limit(5)->get()->map(function($ann) {
            return [
                'id' => $ann->announcement_id,
                'title' => $ann->title,
                'time_ago' => $ann->created_at->diffForHumans(),
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
                    $attendances = $child->student->attendances;
                    return [
                        'id' => $child->user_id,
                        'name' => $child->full_name,
                        'attendance_rate' => $attendances->count() > 0
                            ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1)
                            : 0,
                        'average_grade' => round($child->student->grades->avg('score') ?? 0, 1),
                    ];
                }),
            ]
        ], 200);
    }
}
