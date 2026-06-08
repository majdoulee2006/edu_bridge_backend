<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Announcement;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\AbsenceRequest;
use App\Models\Message;

class TeacherController extends Controller
{
    /**
     * Ã™â€žÃ™�حة تح�™Æ’Ã™& ا�™â€žÃ™&درس - Dashboard
     */
    public function dashboard(Request $request)
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json([
                'success' => true,
                'data' => [
                    'teacher' => ['id' => null, 'name' => $request->user()->full_name, 'specialization' => null],
                    'statistics' => ['total_courses' => 0, 'total_students' => 0, 'total_assignments' => 0, 'total_announcements' => 0],
                    'recent_assignments' => [],
                    'recent_announcements' => [],
                    'courses' => [],
                ]
            ], 200);
        }

        $courses = $teacher->courses()->withCount('students')->get();

        // إحصائ�™`ات
        $totalStudents = $courses->sum('students_count');
        $totalCourses = $courses->count();

        // آخر 5 Ã™�اجبات
        $recentAssignments = Assignment::whereIn('course_id', $courses->pluck('course_id'))
            ->with('course')
            ->latest()
            ->limit(5)
            ->get();

        // آخر 5 إع�™ا�™ ات
        $recentAnnouncements = Announcement::with('user')
            ->where(function($q) use ($courses) {
                $q->whereIn('course_id', $courses->pluck('course_id'))
                  ->orWhere('type', 'general')
                  ->orWhereNull('course_id');
            })
            ->latest()
            ->limit(5)
            ->get();

        $responseData = [
            'success' => true,
            'data' => [
                'teacher' => [
                    'id'             => $teacher->teacher_id,
                    'name'           => $request->user()->full_name ?? '',
                    'specialization' => $teacher->specialization ?? '',
                ],
                'statistics' => [
                    'total_courses'       => $totalCourses,
                    'total_students'      => $totalStudents,
                    'total_assignments'   => Assignment::whereIn('course_id', $courses->pluck('course_id'))->count(),
                    'total_announcements' => Announcement::where('user_id', $request->user()->user_id)->count(),
                ],
                'recent_assignments' => $recentAssignments->map(function($assignment) {
                    return [
                        'id'               => $assignment->assignment_id,
                        'title'            => $assignment->title ?? '',
                        'course_name'      => $assignment->course->title ?? '',
                        'due_date'         => $assignment->due_date->format('Y-m-d'),
                        'submissions_count'=> $assignment->submissions()->count(),
                    ];
                })->values(),
                'recent_announcements' => $recentAnnouncements->map(function($announcement) {
                    return [
                        'id'          => $announcement->announcement_id,
                        'title'       => $announcement->title ?? '',
                        'content'     => substr($announcement->content ?? '', 0, 100),
                        'body'        => $announcement->content ?? '',
                        'author_name' => $announcement->user ? $announcement->user->full_name : 'ا�إدارة',
                        'image_url'   => $announcement->image ? url('storage/' . $announcement->image) : null,
                        'link_url'    => $announcement->link_url ?? null,
                        'created_at'  => $announcement->created_at->diffForHumans(),
                        'time_ago'    => $announcement->created_at->diffForHumans(),
                    ];
                })->values(),
                'courses' => $courses->map(function($course) {
                    return [
                        'id'             => $course->course_id,
                        'title'          => $course->title ?? '',
                        'level'          => $course->level ?? '',
                        'students_count' => $course->students_count,
                    ];
                })->values(),
            ],
        ];

        return response(
            json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            200,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    /**
     * ج�™ب ج�™â€¦Ã™`ع د�™�رات ا�™â€žÃ™&درس
     */
    public function myDepartmentPrograms(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        // جلب البرامج المرتبطة بمواد المعلم مباشرة
        $programIds = \DB::table('course_teachers')
            ->join('course_program', 'course_teachers.course_id', '=', 'course_program.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->pluck('course_program.program_id')
            ->unique();

        $programs = \App\Models\Program::whereIn('id', $programIds)->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => $programs,
        ]);
    }

    public function myCourses(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $query = $teacher->courses()->with(['students.user', 'schedule', 'programs']);

        // Ã™ÂÃ™ترة حسب ا�™بر�™ ا�™&ج/ا�™د�™�رة
        if ($request->filled('program_id')) {
            $query->whereHas('programs', function ($q) use ($request) {
                $q->where('programs.id', $request->program_id);
            });
        }

        // Ã™ÂÃ™ترة حسب ا�™س�™ ة ا�™دراس�™`ة
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $courses = $query->get()->map(function ($course) {
            $program = $course->programs->first();
            return [
                'id'           => $course->course_id,
                'title'        => $course->title,
                'description'  => $course->description,
                'level'        => $course->level,
                'year'         => $course->year ?? 1,
                'program_id'   => $program?->id ?? null,
                'program_name' => $program?->name ?? $course->level ?? '',
                'students'     => $course->students->map(function ($student) {
                    return [
                        'id'           => $student->student_id,
                        'name'         => $student->user->full_name,
                        'student_code' => $student->student_code,
                        'email'        => $student->user->email,
                    ];
                }),
                'schedule' => $course->schedule ? [
                    'day'        => $course->schedule->day,
                    'start_time' => $course->schedule->start_time,
                    'end_time'   => $course->schedule->end_time,
                    'room'       => $course->schedule->room,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $courses,
        ], 200);
    }

    /**
     * ج�™ب ط�™اب د�™�رة �™&ع�™Å Ã™ ة
     */
    public function courseStudents(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        // ا�™تأ�™�د أ�™â€  Ã™!ذ�™! ا�™د�™�رة تخص ا�™â€žÃ™&درس
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
                // إحصائ�™`ات ا�™طا�™ب �™ÂÃ™Å  Ã™!ذ�™! ا�™د�™�رة
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
     * تسج�™Å Ã™ ا�™حض�™�ر �™�ا�™غ�™`اب
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

        // ا�™تأ�™�د أ�™â€  Ã™!ذ�™! ا�™د�™�رة تخص ا�™â€žÃ™&درس
        $course = $teacher->courses()->where('course_id', $request->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
            'message' => 'لا يمكن تسجيل حضور في هذه الدورة'
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
     * ج�™ب سج�™ ا�™حض�™�ر �™د�™�رة �™&ع�™Å Ã™ ة
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
     * ت�™Ë†Ã™â€žÃ™`د QR Ã™ج�™سة حض�™�ر
     */
    public function generateQrSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,course_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $teacher = $request->user()->teacher;
        $course = $teacher->courses()->where('courses.course_id', $request->course_id)->first();

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'الدورة غير مرتبطة بك'], 403);
        }

        // إ�™ شاء درس �™&ؤ�™ت �™â€žÃ™!ذ�™! ا�™ج�™سة
        $lesson = Lesson::create([
            'course_id' => $course->course_id,
            'teacher_id' => $teacher->teacher_id,
            'title' => 'حصة ' . now()->format('Y-m-d H:i'),
            'type' => 'session',
        ]);

        // ت�™Ë†Ã™â€žÃ™`د ت�™Ë†Ã™Æ’Ã™  عش�™�ائ�™Å  Ã™�إ�™ شاء ا�™ج�™سة
        $token = \Illuminate\Support\Str::random(32);

        $session = \App\Models\AttendanceSession::create([
            'lesson_id' => $lesson->lesson_id,
            'qr_token'  => $token,
            'expires_at' => now()->addMinutes(60),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'qr_token'  => $token,
                'expires_at' => $session->expires_at,
                'lesson_id' => $lesson->lesson_id,
                'course_name' => $course->title,
            ]
        ], 200);
    }

    /**
     * ج�™ب �™ائ�™&ة ا�™حاضر�™Å Ã™â€  Ã™�ا�™غائب�™Å Ã™â€  Ã™ج�™سة �™&ع�™Å Ã™ ة
     */
    public function getSessionAttendance(Request $request, $sessionId)
    {
        $session = \App\Models\AttendanceSession::find($sessionId);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        $lesson = $session->lesson;
        $course = $lesson->course;

        $enrolledStudents = $course->students()->with('user')->get();

        $presentIds = Attendance::where('lesson_id', $lesson->lesson_id)
            ->where('status', 'present')
            ->pluck('student_id')
            ->toArray();

        // إضا�™Âة ا�™ط�™اب ا�™ذ�™Å Ã™â€  Ã™&سح�™�ا QR Ã™â€žÃ™Æ’Ã™  غ�™`ر �™&سج�™â€˜Ã™â€žÃ™Å Ã™â€  Ã™ÂÃ™` ا�™â€žÃ™Æ’Ã™�رس
        $enrolledIds = $enrolledStudents->pluck('student_id')->toArray();
        $extraIds = array_diff($presentIds, $enrolledIds);
        $extraStudents = !empty($extraIds)
            ? \App\Models\Student::with('user')->whereIn('student_id', $extraIds)->get()
            : collect();

        $allStudents = $enrolledStudents->merge($extraStudents);

        $students = $allStudents->map(function ($student) use ($presentIds) {
            return [
                'student_id' => $student->student_id,
                    'name'   => $student->user->full_name ?? $student->user->name ?? 'غير معروف',
                'status' => in_array($student->student_id, $presentIds) ? 'present' : 'absent',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'session_active' => $session->is_active,
                'present_count' => count($presentIds),
                'total_count'   => $allStudents->count(),
                'students' => $students,
            ]
        ], 200);
    }

    /**
     * إ�™â€ Ã™!اء ج�™سة ا�™حض�™�ر �™�تسج�™Å Ã™ ا�™غ�™`اب
     */
    public function endSession(Request $request, $sessionId)
    {
        $session = \App\Models\AttendanceSession::find($sessionId);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة'], 404);
        }

        $lesson = $session->lesson;
        $course = $lesson->course;
        $allStudents = $course->students()->get();

        $presentIds = Attendance::where('lesson_id', $lesson->lesson_id)
            ->where('status', 'present')
            ->pluck('student_id')
            ->toArray();

        foreach ($allStudents as $student) {
            if (!in_array($student->student_id, $presentIds)) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->student_id,
                        'lesson_id'  => $lesson->lesson_id,
                        'attendance_date' => now()->toDateString(),
                    ],
                    ['status' => 'absent']
                );
            }
        }

        $session->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الواجب بنجاح',
        ], 200);
    }

    /**
     * إدخا�™ ا�™ع�™ا�™&ات �™â€žÃ™ط�™اب
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

        // ا�™تأ�™�د أ�™â€  Ã™!ذا ا�™ا�™&تحا�™â€  Ã™`خص ا�™â€žÃ™&درس
        $exam = \App\Models\Exam::where('exam_id', $request->exam_id)
            ->whereHas('course', function($query) use ($teacher) {
                $query->whereHas('teachers', function($q) use ($teacher) {
                    $q->where('teachers.teacher_id', $teacher->teacher_id);
                });
            })->first();

        if (!$exam) {
            return response()->json([
                'success' => false,
            'message' => 'لا يمكن إدخال علامات لهذا الامتحان'
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
     * ج�™ب ا�™ع�™ا�™&ات �™د�™�رة �™&ع�™Å Ã™ ة
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
     * ج�™ب �™�اجبات ا�™â€žÃ™&درس
     */
    public function getAssignments(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $courseIds = $teacher->courses()->pluck('courses.course_id');

        $assignments = Assignment::whereIn('course_id', $courseIds)
            ->with(['course', 'submissions'])
            ->latest()
            ->get()
            ->map(function ($assignment) {
                $submissionsCount = $assignment->submissions->count();
                $gradedCount      = $assignment->submissions->whereNotNull('grade')->count();
                $isExpired        = $assignment->due_date->isPast();

                if ($isExpired && $gradedCount >= $submissionsCount && $submissionsCount > 0) {
                    $status = 'مكتمل';
                } elseif ($isExpired && $submissionsCount > 0) {
                    $status = 'قيد التصحيح';
                } elseif (!$isExpired && $submissionsCount === 0) {
                    $status = 'قيد الانتظار';
                } else {
                    $status = 'نشط';
                }

                return [
                    'id'              => $assignment->assignment_id,
                    'title'           => $assignment->title,
                    'description'     => $assignment->description ?? '',
                    'course_id'       => $assignment->course_id,
                    'course_name'     => $assignment->course->title ?? '',
                    'due_date'        => $assignment->due_date->format('Y-m-d H:i:s'),
                    'max_points'      => $assignment->max_points,
                    'attachment_path' => $assignment->attachment_path,
                    'file_url'        => $assignment->attachment_path ? storageUrl($assignment->attachment_path) : null,
                    'file_name'       => $assignment->attachment_path ? basename($assignment->attachment_path) : null,
                    'submissions_count' => $submissionsCount,
                    'status'          => $status,
                ];
            });

        return response()->json(['success' => true, 'data' => $assignments], 200);
    }

    /**
     * إ�™ شاء �™�اجب جد�™`د
     */
    public function createAssignment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id'  => 'required|exists:courses,course_id',
            'title'      => 'required|string|max:255',
            'description'=> 'required|string',
            'due_date'   => 'required|date|after:now',
            'max_points' => 'required|integer|min:1|max:100',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('courses.course_id', $request->course_id)->first();
        if (!$course) {
            return response()->json([
                'success' => false,
            'message' => 'لا يمكن إنشاء واجب في هذه الدورة'
            ], 403);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->storeAs(
                'assignments',
                time() . '_' . $file->getClientOriginalName(),
                'public'
            );
        }

        $assignment = Assignment::create([
            'course_id'       => $request->course_id,
            'teacher_id'      => $teacher->teacher_id,
            'title'           => $request->title,
            'description'     => $request->description,
            'due_date'        => $request->due_date,
            'max_points'      => $request->max_points,
            'attachment_path' => $attachmentPath,
        ]);

        // إشعار ا�ط�اب ا��&سج��`�  ��` ا��&ادة
        $teacherUser = $request->user();
        $studentIds = DB::table('enrollments')
            ->where('course_id', $request->course_id)
            ->pluck('student_id');
        $studentUserIds = DB::table('students')
            ->whereIn('student_id', $studentIds)
            ->pluck('user_id');
        $now = now();
        $rows = $studentUserIds->map(fn($uid) => [
            'user_id'    => $uid,
            'sender_id'  => $teacherUser->user_id,
            'title'      => 'واجب جديد — ' . $course->name,
            'message'    => 'رفع المعلم ' . $teacherUser->full_name . ' واجباً جديداً: ' . $request->title,
            'type'       => 'assignment',
            'category'   => 'academic',
            'related_id' => $assignment->assignment_id,
            'is_read'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        if (!empty($rows)) {
            DB::table('notifications')->insert($rows);
            // FCM push notifications
            $fcmTitle = 'واجب جديد — ' . $course->name;
            $fcmBody  = 'رفع المعلم ' . $teacherUser->full_name . ' واجباً جديداً: ' . $request->title;
            foreach ($studentUserIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, $fcmTitle, $fcmBody, [
                    'type' => 'assignment',
                    'related_id' => (string) $assignment->assignment_id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الواجب بنجاح',
            'data'    => $assignment
        ], 201);
    }

    /**
     * تحد�`ث ��اجب
     */
    public function updateAssignment(Request $request, $assignmentId)
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'الواجب غير موجود'], 404);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $assignment->course_id)->first();

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'لا يمكن تعديل هذا الواجب'], 403);
        }

        $validator = Validator::make($request->all(), [
            'course_id'   => 'sometimes|exists:courses,course_id',
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'due_date'    => 'sometimes|date',
            'max_points'  => 'sometimes|integer|min:1|max:100',
            'attachment'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['title', 'description', 'due_date', 'max_points']);

        if ($request->filled('course_id')) {
            $newCourse = $teacher->courses()->where('course_id', $request->course_id)->first();
            if ($newCourse) {
                $data['course_id'] = $request->course_id;
            }
        }

        if ($request->hasFile('attachment')) {
            if ($assignment->attachment_path) {
                Storage::disk('public')->delete($assignment->attachment_path);
            }
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->storeAs(
                'assignments',
                time() . '_' . $file->getClientOriginalName(),
                'public'
            );
        }

        $assignment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الواجب بنجاح',
            'data'    => $assignment
        ], 200);
    }

    /**
     * حذ�™Â Ã™�اجب
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
                'message' => 'لا يمكن حذف هذا الواجب'
            ], 403);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الواجب بنجاح'
        ], 200);
    }

    /**
     * تصح�™`ح �™�اجب طا�™ب
     */
    public function gradeAssignment(Request $request, $submissionId)
    {
        $submission = AssignmentSubmission::with('assignment')->find($submissionId);

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'التسليم غير موجود'], 404);
        }

        $maxPoints = $submission->assignment->max_points ?? 100;

        $validator = Validator::make($request->all(), [
            'grade'    => "required|numeric|min:0|max:{$maxPoints}",
            'feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('courses.course_id', $submission->assignment->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تصحيح هذا الواجب'
            ], 403);
        }

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
        ]);

        // إشعار ا�طا�ب بتصح�`ح ��اجب�!
        $studentUserId = DB::table('students')
            ->where('student_id', $submission->student_id)
            ->value('user_id');
        if ($studentUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $studentUserId,
                'sender_id'  => $request->user()->user_id,
                'title'      => 'ت�& تصح�`ح ��اجبْ',
                'message'    => 'صح�ح ا��&ع��& ��اجب "' . $submission->assignment->title . '" � ع�ا�&تْ: ' . $request->grade . '/' . ($submission->assignment->max_points ?? 100),
                'type'       => 'assignment',
                'related_id' => $submission->assignment->assignment_id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser($studentUserId, 'Grade', (string)$request->grade, ['type' => 'assignment', 'related_id' => (string)$submission->assignment->assignment_id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الواجب بنجاح',
            'data' => $submission
        ], 200);
    }

    /**
     * إ�™ شاء إع�™ا�™  جد�™`د
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

        // إذا �™�ا�™  ا�™إع�™ا�™  خاص بد�™�رة�R تأ�™�د أ�™  ا�™د�™�رة تخص ا�™â€žÃ™&درس
        if ($request->type == 'course_specific' && $request->course_id) {
            $course = $teacher->courses()->where('course_id', $request->course_id)->first();
            if (!$course) {
                return response()->json([
                    'success' => false,
                'message' => 'لا يمكن إنشاء إعلان في هذه الدورة'
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
            'message' => 'تم إنشاء الواجب بنجاح',
            'data' => $announcement
        ], 201);
    }

    // â”€â”€â”€ Report Requests from HoD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getReportRequests(Request $request)
    {
        $teacher = DB::table('teachers')->where('user_id', $request->user()->user_id)->first();
        if (!$teacher) return response()->json(['success' => true, 'data' => []]);

        $requests = DB::table('report_requests')
            ->join('students', 'report_requests.student_id', '=', 'students.student_id')
            ->join('users as su', 'students.user_id', '=', 'su.user_id')
            ->leftJoin('courses', 'report_requests.course_id', '=', 'courses.course_id')
            ->where('report_requests.teacher_id', $teacher->teacher_id)
            ->orderBy('report_requests.created_at', 'desc')
            ->get([
                'report_requests.id',
                'report_requests.report_type',
                'report_requests.notes',
                'report_requests.status',
                'report_requests.year',
                'report_requests.student_id',
                'report_requests.course_id',
                'report_requests.created_at',
                'su.full_name as student_name',
                'courses.title as course_name',
            ]);

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function getStudentAcademicStats(Request $request, $id)
    {
        $reportRequest = DB::table('report_requests')->where('id', $id)->first();
        if (!$reportRequest) {
            return response()->json(['success' => false, 'message' => 'ا�ط�ب غ�`ر �&��ج��د'], 404);
        }

        $studentId = $reportRequest->student_id;
        $courseId  = $reportRequest->course_id;

        // �&ت��سط ا�ع�ا�&ات
        $gradesQuery = DB::table('grades')->where('student_id', $studentId);
        if ($courseId) $gradesQuery->where('course_id', $courseId);
        $grades    = $gradesQuery->get(['score']);
        $avgGrade  = $grades->count() > 0 ? round($grades->avg('score'), 1) : null;

        // � سبة ا�حض��ر
        $attQuery = DB::table('attendances')->where('student_id', $studentId);
        if ($courseId) $attQuery->where('course_id', $courseId);
        $att          = $attQuery->get(['status']);
        $total        = $att->count();
        $present      = $att->where('status', 'present')->count();
        $attRate      = $total > 0 ? round(($present / $total) * 100, 1) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'avg_grade'       => $avgGrade,
                'attendance_rate' => $attRate,
                'present'         => $present,
                'total'           => $total,
            ],
        ]);
    }

    public function submitEvaluation(Request $request, $id)
    {
        $request->validate(['notes' => 'required|string|min:3']);

        $reportRequest = DB::table('report_requests')->find($id);
        if (!$reportRequest) {
            return response()->json(['success' => false, 'message' => 'ا�ط�ب غ�`ر �&��ج��د'], 404);
        }

        DB::table('report_requests')->where('id', $id)->update([
            'notes'      => $request->notes,
            'status'     => 'completed',
            'updated_at' => now(),
        ]);

        $studentName = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $reportRequest->student_id)
            ->value('users.full_name');

        // إشعار رئ�`س ا��س�& (ا�أساس�`)
        DB::table('notifications')->insert([
            'user_id'    => $reportRequest->head_id,
            'title'      => 'ت�& إرسا� ت��`�`�& ا�طا�ب',
            'message'    => '�د��& ا��&ع��& ت��`�`�&�! ��طا�ب ' . ($studentName ?? ''),
            'type'       => 'report',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser($reportRequest->head_id, 'Report', 'Evaluation submitted', ['type' => 'report']);

        // ح�ظ ا�ت�ر�`ر �ي performance_reports + إشعار ����` ا�أ�&ر (ثا� ���` � �ا �`ْسر ا�ع�&��`ة)
        try {
            DB::table('performance_reports')->insert([
                'student_id'      => $reportRequest->student_id,
                'report_type'     => $reportRequest->report_type ?? 'behavioral',
                'attendance_rate' => 0,
                'average_grade'   => 0,
                'recommendations' => $request->notes,
                'generated_at'    => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $parentUserId = DB::table('parent_students')
                ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
                ->where('parent_students.student_id', $reportRequest->student_id)
                ->value('parents.user_id');

            if ($parentUserId) {
                DB::table('notifications')->insert([
                    'user_id'    => $parentUserId,
                    'title'      => 'ت�ر�`ر ا�طا�ب جا�!ز',
                    'message'    => 'أرس� ا��&ع��& ا�ت�ر�`ر ا�س���ْ�` ��طا�ب ' . ($studentName ?? '') . '�R �`�&ْ� ْ ا�اط�اع ع��`�! ا�آ� .',
                    'type'       => 'report',
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('submitEvaluation side-effects failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'ت�& إرسا� ا�ت��`�`�& ب� جاح']);
    }

    /**
     * ج�ب إع�ا� ات ا��&درس
     */
    public function getAnnouncements(Request $request)
    {
        // إع�ا� ات ا��&ع��& � �س�! + إع�ا� ات رئ�`س ا��س�& ا��&��ج�!ة ���&ع��&�`�  أ�� ��ج�&�`ع
        $headUserIds = \DB::table('users')->where('role_id', 5)->pluck('user_id');

        $announcements = Announcement::where(function($q) use ($request, $headUserIds) {
                $q->where('user_id', $request->user()->user_id)
                  ->orWhere(function($q2) use ($headUserIds) {
                      $q2->whereIn('user_id', $headUserIds)
                         ->where(function($q3) {
                             $q3->whereNull('target_role')
                                ->orWhere('target_role', 'teacher');
                         });
                  });
            })
            ->with(['course', 'user'])
            ->latest()
            ->get()
            ->map(function($announcement) use ($headUserIds) {
                $isFromHead = $headUserIds->contains($announcement->user_id);
                return [
                    'id'          => $announcement->announcement_id,
                    'title'       => $announcement->title,
                    'content'     => $announcement->content,
                    'type'        => $announcement->type,
                    'course'      => $announcement->course ? $announcement->course->title : null,
                    'from_head'   => $isFromHead,
                    'author_name' => $announcement->user ? $announcement->user->full_name : null,
                    'image_url'   => $announcement->image ? url('storage/' . $announcement->image) : null,
                    'link_url'    => $announcement->link_url ?? null,
                    'created_at'  => $announcement->created_at->format('Y-m-d H:i'),
                    'time_ago'    => $announcement->created_at->diffForHumans(),
                ];
            });

        return response()->json(['success' => true, 'data' => $announcements], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  ا�™جد�™Ë†Ã™ ا�™دراس�™Å 
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getSchedule(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['success' => true, 'data' => (object)[]], 200);
        }
        $courseIds = $teacher->courses()->pluck('courses.course_id');

        $schedules = Schedule::whereIn('course_id', $courseIds)
            ->with('course')
            ->get()
            ->groupBy('day')
            ->map(function($items) {
                return $items->map(function($item) {
                    return [
                        'id'          => $item->schedule_id,
                        'course_id'   => $item->course_id,
                        'course_name' => $item->course->title,
                        'start_time'  => $item->start_time,
                        'end_time'    => $item->end_time,
                        'room'        => $item->room,
                    ];
                });
            });

        return response()->json(['success' => true, 'data' => $schedules], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  ا�™â€žÃ™&حاضرات
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getLessons(Request $request)
    {
        $teacher = $request->user()->teacher;

        $lessons = Lesson::where('teacher_id', $teacher->teacher_id)
            ->with('course')
            ->latest()
            ->get()
            ->map(function($lesson) {
                return [
                    'id'          => $lesson->lesson_id,
                    'title'       => $lesson->title,
                    'description' => $lesson->description,
                    'content_url' => $lesson->content_url,
                    'course_id'   => $lesson->course_id,
                    'course_name' => $lesson->course ? $lesson->course->title : null,
                    'created_at'  => $lesson->created_at->format('Y-m-d'),
                ];
            });

        return response()->json(['success' => true, 'data' => $lessons], 200);
    }

    public function createLesson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'course_id'    => 'required|exists:courses,course_id',
            'description'  => 'nullable|string',
            'content_file' => 'required|file|mimes:pdf,mp4,mov,avi,mkv|max:102400',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('courses.course_id', $request->course_id)->first();
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'هذه الدورة غير مرتبطة بك'], 403);
        }

        $file     = $request->file('content_file');
        $filePath = $file->storeAs('lectures', time() . '_' . $file->getClientOriginalName(), 'public');

        $lesson = Lesson::create([
            'title'       => $request->title,
            'course_id'   => $request->course_id,
            'teacher_id'  => $teacher->teacher_id,
            'description' => $request->description,
            'content_url' => $filePath,
        ]);

        // notify students of new lecture
        $teacherUser    = $request->user();
        $courseName     = $course->name ?? $course->title ?? 'ا��&ادة';
        $studentIds     = DB::table('enrollments')->where('course_id', $request->course_id)->pluck('student_id');
        $studentUserIds = DB::table('students')->whereIn('student_id', $studentIds)->pluck('user_id');
        $notifNow = now();
        $notifRows = $studentUserIds->map(fn($uid) => [
            'user_id'    => $uid,
            'sender_id'  => $teacherUser->user_id,
            'title'      => 'محاضرة جديدة — ' . $courseName,
            'message'    => 'رفع المعلم ' . $teacherUser->full_name . ' محاضرة جديدة: ' . $request->title,
            'type'       => 'lecture',
            'category'   => 'academic',
            'related_id' => $lesson->lesson_id,
            'is_read'    => 0,
            'created_at' => $notifNow,
            'updated_at' => $notifNow,
        ])->all();
        if (!empty($notifRows)) {
            DB::table('notifications')->insert($notifRows);
            $fcmTitle = 'محاضرة جديدة — ' . $courseName;
            $fcmBody  = 'رفع المعلم ' . $teacherUser->full_name . ' محاضرة جديدة: ' . $request->title;
            foreach ($studentUserIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, $fcmTitle, $fcmBody, ['type' => 'lecture']);
            }
        }

        return response()->json(['success' => true, 'message' => 'تم رفع المحاضرة بنجاح', 'data' => $lesson], 201);
    }


    public function updateLesson(Request $request, $lessonId)
    {
        $teacher = $request->user()->teacher;

        $lesson = Lesson::where('lesson_id', $lessonId)
            ->where('teacher_id', $teacher->teacher_id)
            ->first();

        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'ا��&حاضرة غ�`ر �&��ج��دة أ�� �ا تخصْ'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'course_id'    => 'required|exists:courses,course_id',
            'description'  => 'nullable|string',
            'content_file' => 'nullable|file|mimes:pdf,mp4,mov,avi,mkv|max:102400',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $lesson->title       = $request->title;
        $lesson->course_id   = $request->course_id;
        $lesson->description = $request->description;

        if ($request->hasFile('content_file')) {
            if ($lesson->content_url) Storage::disk('public')->delete($lesson->content_url);
            $file = $request->file('content_file');
            $lesson->content_url = $file->storeAs('lectures', time() . '_' . $file->getClientOriginalName(), 'public');
        }

        $lesson->save();

        return response()->json(['success' => true, 'message' => 'ت�& تعد�`� ا��&حاضرة ب� جاح', 'data' => $lesson], 200);
    }
    public function deleteLesson(Request $request, $lessonId)
    {
        $teacher = $request->user()->teacher;

        $lesson = Lesson::where('lesson_id', $lessonId)
            ->where('teacher_id', $teacher->teacher_id)
            ->first();

        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'المحاضرة غير موجودة أو لا تخصك'], 404);
        }

        try {
            if ($lesson->content_url) Storage::disk('public')->delete($lesson->content_url);
            $lesson->delete();
        } catch (\Exception $e) {
            \Log::error('Delete lesson: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'تم حذف المحاضرة بنجاح'], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  ا�™إشعارات
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getNotifications(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($n) {
                $imageUrl = null;
                $data = [];
                if ($n->type === 'announcement' && $n->related_id) {
                    $ann = \DB::table('announcements')
                        ->leftJoin('users', 'announcements.user_id', '=', 'users.user_id')
                        ->where('announcements.announcement_id', $n->related_id)
                        ->first(['announcements.image', 'announcements.content', 'announcements.link_url', 'users.full_name as author_name']);
                    $imageUrl = $ann && $ann->image ? url('storage/' . $ann->image) : null;
                    $data = [
                        'image_url'   => $imageUrl,
                        'content'     => $ann->content ?? '',
                        'author_name' => $ann->author_name ?? 'ا�إدارة',
                        'link_url'    => $ann->link_url ?? null,
                    ];
                }
                return [
                    'id'         => $n->id,
                    'title'      => $n->title,
                    'message'    => $n->message,
                    'type'       => $n->type,
                    'is_read'    => $n->is_read,
                    'related_id' => $n->related_id,
                    'image_url'  => $imageUrl,
                    'data'       => $data,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            });

        return response()->json(['success' => true, 'data' => $notifications], 200);
    }

    public function markNotificationRead(Request $request, $notificationId)
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $request->user()->user_id)
            ->first();

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'الإشعار غير موجود'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'تم تحديد الإشعار كمقروء'], 200);
    }

    public function markAllNotificationsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'تم تحديد كل الإشعارات كمقروءة'], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  تس�™â€žÃ™Å Ã™&ات ا�™â€žÃ™�اجبات
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    /**
     * ج�™ب �™Æ’Ã™ تس�™â€žÃ™Å Ã™&ات �™â€¦Ã™�اد ا�™â€žÃ™&درس
     */
    public function getSubmissions(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $courseIds     = $teacher->courses()->pluck('courses.course_id');
        $assignmentIds = Assignment::whereIn('course_id', $courseIds)->pluck('assignment_id');

        $submissions = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
            ->with(['assignment.course', 'student.user'])
            ->latest()
            ->get()
            ->map(function ($sub) {
                return [
                    'submission_id'    => $sub->submission_id,
                    'student_name'     => $sub->student->user->full_name ?? 'غير معروف',
                    'assignment_title' => $sub->assignment->title ?? '',
                    'course_name'      => $sub->assignment->course->title ?? '',
                    'student_notes'    => $sub->notes ?? '',
                    'file_path'        => $sub->file_path,
                    'grade'            => $sub->grade,
                    'feedback'         => $sub->feedback,
                    'max_points'       => $sub->assignment->max_points ?? 100,
                    'submitted_at'     => $sub->submitted_at ? \Carbon\Carbon::parse($sub->submitted_at)->format('Y-m-d H:i') : ($sub->created_at ? $sub->created_at->format('Y-m-d H:i') : null),
                    'is_graded'        => !is_null($sub->grade),
                ];
            });

        return response()->json(['success' => true, 'data' => $submissions], 200);
    }

    public function getAssignmentSubmissions(Request $request, $assignmentId)
    {
        $teacher    = $request->user()->teacher;
        $assignment = Assignment::where('assignment_id', $assignmentId)
            ->whereHas('course', function($q) use ($teacher) {
                $q->whereHas('teachers', function($q2) use ($teacher) {
                    $q2->where('teachers.teacher_id', $teacher->teacher_id);
                });
            })->first();

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'الواجب غير موجود أو لا يخصك'], 404);
        }

        $submissions = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->with('student.user')
            ->get()
            ->map(function($sub) use ($assignment) {
                return [
                    'submission_id'    => $sub->submission_id,
                    'student_id'       => $sub->student_id,
                    'student_name'     => $sub->student->user->full_name,
                    'assignment_title' => $assignment->title,
                    'course_name'      => $assignment->course->title ?? '',
                    'max_points'       => $assignment->max_points,
                    'file_path'        => $sub->file_path,
                    'student_notes'    => $sub->notes ?? '',
                    'grade'            => $sub->grade,
                    'feedback'         => $sub->feedback,
                    'is_graded'        => !is_null($sub->grade),
                    'submitted_at'     => $sub->submitted_at
                        ? \Carbon\Carbon::parse($sub->submitted_at)->format('Y-m-d H:i')
                        : ($sub->created_at ? $sub->created_at->format('Y-m-d H:i') : null),
                ];
            });

        return response()->json([
            'success'    => true,
            'assignment' => ['id' => $assignment->assignment_id, 'title' => $assignment->title],
            'data'       => $submissions,
        ], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  ا�™ا�™&تحا�™ ات
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getExams(Request $request)
    {
        $teacher   = $request->user()->teacher;
        $courseIds = $teacher->courses()->pluck('course_id');

        $exams = \App\Models\Exam::whereIn('course_id', $courseIds)
            ->with('course')
            ->get()
            ->map(function($exam) {
                return [
                    'id'        => $exam->exam_id,
                    'exam_name' => $exam->exam_name,
                    'course_id' => $exam->course_id,
                    'course'    => $exam->course->title,
                    'exam_date' => $exam->exam_date,
                    'max_score' => $exam->max_score,
                ];
            });

        return response()->json(['success' => true, 'data' => $exams], 200);
    }

    public function createExam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,course_id',
            'exam_name' => 'required|string|max:255',
            'exam_date' => 'required|date',
            'max_score' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $teacher = $request->user()->teacher;
        $course  = $teacher->courses()->where('course_id', $request->course_id)->first();

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'هذه الدورة غير مرتبطة بك'], 403);
        }

        $exam = \App\Models\Exam::create([
            'course_id' => $request->course_id,
            'exam_name' => $request->exam_name,
            'exam_date' => $request->exam_date,
            'max_score' => $request->max_score ?? 100,
        ]);

        return response()->json(['success' => true, 'message' => 'تم إنشاء الامتحان بنجاح', 'data' => $exam], 201);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  ا�™â€žÃ™â€¦Ã™â€žÃ™Â ا�™شخص�™Å 
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getTeacherProfile(Request $request)
    {
        $user    = $request->user();
        $teacher = $user->teacher;

        return response()->json([
            'success' => true,
            'data'    => [
                'user_id'        => $user->user_id,
                'full_name'      => $user->full_name,
                'username'       => $user->username,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'specialization' => $teacher ? $teacher->specialization : null,
                'teacher_id'     => $teacher ? $teacher->teacher_id : null,
                'avatar'         => $user->avatar ? storageUrl($user->avatar) : null,
            ],
        ], 200);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg|max:5120']);

        $user = $request->user();

        if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
            \Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الواجب بنجاح',
            'avatar'  => storageUrl($path),
        ]);
    }

    public function updateTeacherProfile(Request $request)
    {
        $user      = $request->user();
        $validator = Validator::make($request->all(), [
            'full_name'      => 'sometimes|string|max:255',
            'email'          => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
            'phone'          => 'sometimes|nullable|string|max:20',
            'specialization' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user->update($request->only(['full_name', 'email', 'phone']));

        if ($request->filled('specialization') && $user->teacher) {
            $user->teacher->update(['specialization' => $request->specialization]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data'    => $user->fresh(),
        ], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  ا�™رسائ�™â€ž
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getMessages(Request $request)
    {
        $userId = $request->user()->user_id;

        $messages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($msg) use ($userId) {
                return [
                    'id'          => $msg->id,
                    'message'     => $msg->message,
                    'is_read'     => $msg->is_read,
                    'sent_at'     => $msg->created_at->format('Y-m-d H:i'),
                    'direction'   => $msg->sender_id == $userId ? 'sent' : 'received',
                    'other_party' => $msg->sender_id == $userId
                        ? $msg->receiver->full_name
                        : $msg->sender->full_name,
                ];
            });

        return response()->json(['success' => true, 'data' => $messages], 200);
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,user_id',
            'message'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $msg = Message::create([
            'sender_id'   => $request->user()->user_id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
        ]);

        return response()->json(['success' => true, 'message' => 'تم إرسال الرسالة بنجاح', 'data' => $msg], 201);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  ط�™بات ا�™غ�™`اب
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getAbsenceRequests(Request $request)
    {
        $teacher    = $request->user()->teacher;
        $studentIds = $teacher->courses()
            ->with('students')
            ->get()
            ->pluck('students')
            ->flatten()
            ->pluck('student_id')
            ->unique();

        $requests = AbsenceRequest::whereIn('student_id', $studentIds)
            ->with('student.user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($req) {
                return [
                    'id'           => $req->request_id,
                    'student_id'   => $req->student_id,
                    'student_name' => $req->student->user->full_name,
                    'date'         => $req->date->format('Y-m-d'),
                    'reason'       => $req->reason,
                    'status'       => $req->status,
                    'created_at'   => $req->created_at->format('Y-m-d'),
                ];
            });

        return response()->json(['success' => true, 'data' => $requests], 200);
    }

    public function respondAbsenceRequest(Request $request, $requestId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $absenceRequest = AbsenceRequest::find($requestId);

        if (!$absenceRequest) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        $absenceRequest->update([
            'status'      => $request->status,
            'reviewed_by' => $request->user()->user_id,
        ]);

        return response()->json(['success' => true, 'message' => 'تم الرد على الطلب بنجاح'], 200);
    }
}

