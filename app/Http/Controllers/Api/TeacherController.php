<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Course;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Announcement;
use App\Models\Student;

class TeacherController extends Controller
{
    /**
     * لوحة تحكم المدرس - Dashboard
     */
    public function dashboard(Request $request)
    {
        $teacher = $request->user()->teacher;
        $courses = $teacher->courses()->withCount('students')->get();

        // إحصائيات
        $totalStudents = $courses->sum('students_count');
        $totalCourses = $courses->count();

        // آخر 5 واجبات
        $recentAssignments = Assignment::whereIn('course_id', $courses->pluck('course_id'))
            ->with('course')
            ->latest()
            ->limit(5)
            ->get();

        // آخر 5 إعلانات
        $recentAnnouncements = Announcement::whereIn('course_id', $courses->pluck('course_id'))
                ->orWhere('type', 'general')
                ->latest()
                ->limit(5)
                ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'teacher' => [
                    'id' => $teacher->teacher_id,
                    'name' => $request->user()->full_name,
                    'specialization' => $teacher->specialization,
                ],
                'statistics' => [
                    'total_courses' => $totalCourses,
                    'total_students' => $totalStudents,
                    'total_assignments' => Assignment::whereIn('course_id', $courses->pluck('course_id'))->count(),
                    'total_announcements' => Announcement::where('user_id', $request->user()->user_id)->count(),
                ],
                'recent_assignments' => $recentAssignments->map(function($assignment) {
                    return [
                        'id' => $assignment->assignment_id,
                        'title' => $assignment->title,
                        'course_name' => $assignment->course->title,
                        'due_date' => $assignment->due_date->format('Y-m-d'),
                        'submissions_count' => $assignment->submissions()->count(),
                    ];
                }),
                'recent_announcements' => $recentAnnouncements->map(function($announcement) {
                    return [
                        'id' => $announcement->announcement_id,
                        'title' => $announcement->title,
                        'content' => substr($announcement->content, 0, 100),
                        'created_at' => $announcement->created_at->diffForHumans(),
                    ];
                }),
                'courses' => $courses->map(function($course) {
                    return [
                        'id' => $course->course_id,
                        'title' => $course->title,
                        'level' => $course->level,
                        'students_count' => $course->students_count,
                    ];
                }),
            ]
        ], 200);
    }

    /**
     * جلب جميع دورات المدرس
     */
    public function myCourses(Request $request)
    {
        $teacher = $request->user()->teacher;
        $courses = $teacher->courses()
            ->with(['students.user', 'schedule'])
            ->get()
            ->map(function($course) {
                return [
                    'id' => $course->course_id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'level' => $course->level,
                    'students' => $course->students->map(function($student) {
                        return [
                            'id' => $student->student_id,
                            'name' => $student->user->full_name,
                            'student_code' => $student->student_code,
                            'email' => $student->user->email,
                        ];
                    }),
                    'schedule' => $course->schedule ? [
                        'day' => $course->schedule->day,
                        'start_time' => $course->schedule->start_time,
                        'end_time' => $course->schedule->end_time,
                        'room' => $course->schedule->room,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $courses
        ], 200);
    }

    /**
     * جلب طلاب دورة معينة
     */
    public function courseStudents(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        // التأكد أن هذه الدورة تخص المدرس
        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الدورة غير مرتبطة بك'
            ], 403);
        }

        $students = $course->students()
            ->with('user')
            ->get()
            ->map(function($student) use ($courseId) {
                // إحصائيات الطالب في هذه الدورة
                $attendanceCount = Attendance::where('student_id', $student->student_id)
                    ->whereHas('lesson', function($query) use ($courseId) {
                        $query->where('course_id', $courseId);
                    })->count();

                $presentCount = Attendance::where('student_id', $student->student_id)
                    ->whereHas('lesson', function($query) use ($courseId) {
                        $query->where('course_id', $courseId);
                    })->where('status', 'present')->count();

                $averageGrade = Grade::where('student_id', $student->student_id)
                    ->whereHas('exam', function($query) use ($courseId) {
                        $query->where('course_id', $courseId);
                    })->avg('score');

                return [
                    'id' => $student->student_id,
                    'name' => $student->user->full_name,
                    'student_code' => $student->student_code,
                    'email' => $student->user->email,
                    'phone' => $student->user->phone,
                    'attendance' => [
                        'total' => $attendanceCount,
                        'present' => $presentCount,
                        'percentage' => $attendanceCount > 0 ? round(($presentCount / $attendanceCount) * 100, 1) : 0,
                    ],
                    'average_grade' => round($averageGrade ?? 0, 1),
                ];
            });

        return response()->json([
            'success' => true,
            'course' => [
                'id' => $course->course_id,
                'title' => $course->title,
            ],
            'data' => $students
        ], 200);
    }

    /**
     * تسجيل الحضور والغياب
     */
    public function markAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,course_id',
            'date' => 'required|date',
            'lesson_id' => 'required|exists:lessons,lesson_id',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,student_id',
            'attendance.*.status' => 'required|in:present,absent,late'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $teacher = $request->user()->teacher;

        // التأكد أن هذه الدورة تخص المدرس
        $course = $teacher->courses()->where('course_id', $request->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تسجيل حضور في هذه الدورة'
            ], 403);
        }

        $saved = 0;
        foreach ($request->attendance as $record) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $record['student_id'],
                    'lesson_id' => $request->lesson_id,
                    'attendance_date' => $request->date,
                ],
                [
                    'status' => $record['status'],
                ]
            );
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "تم تسجيل حضور $saved طالب بنجاح"
        ], 200);
    }

    /**
     * جلب سجل الحضور لدورة معينة
     */
    public function getAttendance(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الدورة غير مرتبطة بك'
            ], 403);
        }

        $attendances = Attendance::whereHas('lesson', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->with(['student.user', 'lesson'])
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy('attendance_date')
            ->map(function($items, $date) {
                return [
                    'date' => $date,
                    'students' => $items->map(function($item) {
                        return [
                            'student_id' => $item->student_id,
                            'student_name' => $item->student->user->full_name,
                            'status' => $item->status,
                            'lesson' => $item->lesson->title,
                        ];
                    }),
                    'summary' => [
                        'total' => $items->count(),
                        'present' => $items->where('status', 'present')->count(),
                        'absent' => $items->where('status', 'absent')->count(),
                        'late' => $items->where('status', 'late')->count(),
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $attendances
        ], 200);
    }

    /**
     * إدخال العلامات للطلاب
     */
    public function enterGrades(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,exam_id',
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:students,student_id',
            'grades.*.score' => 'required|numeric|min:0|max:100',
            'grades.*.remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $teacher = $request->user()->teacher;

        // التأكد أن هذا الامتحان يخص المدرس
        $exam = \App\Models\Exam::where('exam_id', $request->exam_id)
            ->whereHas('course', function($query) use ($teacher) {
                $query->whereHas('teachers', function($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->teacher_id);
                });
            })->first();

        if (!$exam) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إدخال علامات لهذا الامتحان'
            ], 403);
        }

        $saved = 0;
        foreach ($request->grades as $grade) {
            \App\Models\Grade::updateOrCreate(
                [
                    'student_id' => $grade['student_id'],
                    'exam_id' => $request->exam_id,
                ],
                [
                    'score' => $grade['score'],
                    'remarks' => $grade['remarks'] ?? null,
                ]
            );
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "تم إدخال علامات $saved طالب بنجاح"
        ], 200);
    }

    /**
     * جلب العلامات لدورة معينة
     */
    public function getGrades(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الدورة غير مرتبطة بك'
            ], 403);
        }

        $grades = Grade::whereHas('exam', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->with(['student.user', 'exam'])
            ->get()
            ->groupBy('exam.exam_name')
            ->map(function($items, $examName) {
                return [
                    'exam_name' => $examName,
                    'grades' => $items->map(function($item) {
                        return [
                            'student_id' => $item->student_id,
                            'student_name' => $item->student->user->full_name,
                            'score' => $item->score,
                            'remarks' => $item->remarks,
                        ];
                    }),
                    'average' => round($items->avg('score'), 1),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $grades
        ], 200);
    }

    /**
     * إنشاء واجب جديد
     */
    public function createAssignment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,course_id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date|after:now',
            'max_points' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $teacher = $request->user()->teacher;

        // التأكد أن هذه الدورة تخص المدرس
        $course = $teacher->courses()->where('course_id', $request->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إنشاء واجب في هذه الدورة'
            ], 403);
        }

        $assignment = Assignment::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'max_points' => $request->max_points,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الواجب بنجاح',
            'data' => $assignment
        ], 201);
    }

    /**
     * تحديث واجب
     */
    public function updateAssignment(Request $request, $assignmentId)
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'الواجب غير موجود'
            ], 404);
        }

        $teacher = $request->user()->teacher;

        // التأكد أن هذا الواجب يخص المدرس
        $course = $teacher->courses()->where('course_id', $assignment->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعديل هذا الواجب'
            ], 403);
        }

        $assignment->update($request->only(['title', 'description', 'due_date', 'max_points']));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الواجب بنجاح',
            'data' => $assignment
        ], 200);
    }

    /**
     * حذف واجب
     */
    public function deleteAssignment(Request $request, $assignmentId)
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'الواجب غير موجود'
            ], 404);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $assignment->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف هذا الواجب'
            ], 403);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الواجب بنجاح'
        ], 200);
    }

    /**
     * تصحيح واجب طالب
     */
    public function gradeAssignment(Request $request, $submissionId)
    {
        $validator = Validator::make($request->all(), [
            'grade' => 'required|numeric|min:0',
            'feedback' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $submission = AssignmentSubmission::with('assignment')->find($submissionId);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'التسليم غير موجود'
            ], 404);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $submission->assignment->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تصحيح هذا الواجب'
            ], 403);
        }

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تصحيح الواجب بنجاح',
            'data' => $submission
        ], 200);
    }

    /**
     * إنشاء إعلان جديد
     */
    public function createAnnouncement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'course_id' => 'nullable|exists:courses,course_id',
            'type' => 'required|in:general,course_specific'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $teacher = $request->user()->teacher;

        // إذا كان الإعلان خاص بدورة، تأكد أن الدورة تخص المدرس
        if ($request->type == 'course_specific' && $request->course_id) {
            $course = $teacher->courses()->where('course_id', $request->course_id)->first();
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك إنشاء إعلان في هذه الدورة'
                ], 403);
            }
        }

        $announcement = Announcement::create([
            'user_id' => $request->user()->user_id,
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'course_id' => $request->course_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الإعلان بنجاح',
            'data' => $announcement
        ], 201);
    }

    /**
     * جلب إعلانات المدرس
     */
    public function getAnnouncements(Request $request)
    {
        $announcements = Announcement::where('user_id', $request->user()->user_id)
            ->with('course')
            ->latest()
            ->get()
            ->map(function($announcement) {
                return [
                    'id' => $announcement->announcement_id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'type' => $announcement->type,
                    'course' => $announcement->course ? $announcement->course->title : null,
                    'created_at' => $announcement->created_at->format('Y-m-d H:i'),
                    'time_ago' => $announcement->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $announcements
        ], 200);
    }
}
