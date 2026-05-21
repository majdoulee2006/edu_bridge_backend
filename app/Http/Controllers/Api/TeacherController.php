<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
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
     * Ù„ÙˆØ­Ø© ØªØ­ÙƒÙ… Ø§Ù„Ù…Ø¯Ø±Ø³ - Dashboard
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

        // Ø¥Ø­ØµØ§Ø¦ÙŠØ§Øª
        $totalStudents = $courses->sum('students_count');
        $totalCourses = $courses->count();

        // Ø¢Ø®Ø± 5 ÙˆØ§Ø¬Ø¨Ø§Øª
        $recentAssignments = Assignment::whereIn('course_id', $courses->pluck('course_id'))
            ->with('course')
            ->latest()
            ->limit(5)
            ->get();

        // Ø¢Ø®Ø± 5 Ø¥Ø¹Ù„Ø§Ù†Ø§Øª
        $recentAnnouncements = Announcement::where(function($q) use ($courses) {
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
                        'id'         => $announcement->announcement_id,
                        'title'      => $announcement->title ?? '',
                        'content'    => substr($announcement->content ?? '', 0, 100),
                        'created_at' => $announcement->created_at->diffForHumans(),
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
     * Ø¬Ù„Ø¨ Ø¬Ù…ÙŠØ¹ Ø¯ÙˆØ±Ø§Øª Ø§Ù„Ù…Ø¯Ø±Ø³
     */
    public function myDepartmentPrograms(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher || !$teacher->specialization) {
            return response()->json(['success' => true, 'data' => [], 'specialization' => ''], 200);
        }

        $department = \App\Models\Department::where('name', $teacher->specialization)->first();
        if (!$department) {
            return response()->json([
                'success' => true,
                'data' => [],
                'specialization' => $teacher->specialization,
            ], 200);
        }

        $programs = \App\Models\Program::where('department_id', $department->department_id)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $programs,
            'specialization' => $teacher->specialization,
        ]);
    }

    public function myCourses(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $query = $teacher->courses()->with(['students.user', 'schedule', 'programs']);

        // ÙÙ„ØªØ±Ø© Ø­Ø³Ø¨ Ø§Ù„Ø¨Ø±Ù†Ø§Ù…Ø¬/Ø§Ù„Ø¯ÙˆØ±Ø©
        if ($request->filled('program_id')) {
            $query->whereHas('programs', function ($q) use ($request) {
                $q->where('programs.id', $request->program_id);
            });
        }

        // ÙÙ„ØªØ±Ø© Ø­Ø³Ø¨ Ø§Ù„Ø³Ù†Ø© Ø§Ù„Ø¯Ø±Ø§Ø³ÙŠØ©
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
     * Ø¬Ù„Ø¨ Ø·Ù„Ø§Ø¨ Ø¯ÙˆØ±Ø© Ù…Ø¹ÙŠÙ†Ø©
     */
    public function courseStudents(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        // Ø§Ù„ØªØ£ÙƒØ¯ Ø£Ù† Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø© ØªØ®Øµ Ø§Ù„Ù…Ø¯Ø±Ø³
        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø© ØºÙŠØ± Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ùƒ'
            ], 403);
        }

        $students = $course->students()
            ->with('user')
            ->get()
            ->map(function($student) use ($courseId) {
                // Ø¥Ø­ØµØ§Ø¦ÙŠØ§Øª Ø§Ù„Ø·Ø§Ù„Ø¨ ÙÙŠ Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø©
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
     * ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø­Ø¶ÙˆØ± ÙˆØ§Ù„ØºÙŠØ§Ø¨
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

        // Ø§Ù„ØªØ£ÙƒØ¯ Ø£Ù† Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø© ØªØ®Øµ Ø§Ù„Ù…Ø¯Ø±Ø³
        $course = $teacher->courses()->where('course_id', $request->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ†Ùƒ ØªØ³Ø¬ÙŠÙ„ Ø­Ø¶ÙˆØ± ÙÙŠ Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø©'
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
            'message' => "ØªÙ… ØªØ³Ø¬ÙŠÙ„ Ø­Ø¶ÙˆØ± $saved Ø·Ø§Ù„Ø¨ Ø¨Ù†Ø¬Ø§Ø­"
        ], 200);
    }

    /**
     * Ø¬Ù„Ø¨ Ø³Ø¬Ù„ Ø§Ù„Ø­Ø¶ÙˆØ± Ù„Ø¯ÙˆØ±Ø© Ù…Ø¹ÙŠÙ†Ø©
     */
    public function getAttendance(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø© ØºÙŠØ± Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ùƒ'
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
     * ØªÙˆÙ„ÙŠØ¯ QR Ù„Ø¬Ù„Ø³Ø© Ø­Ø¶ÙˆØ±
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
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ø¯ÙˆØ±Ø© ØºÙŠØ± Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ùƒ'], 403);
        }

        // Ø¥Ù†Ø´Ø§Ø¡ Ø¯Ø±Ø³ Ù…Ø¤Ù‚Øª Ù„Ù‡Ø°Ù‡ Ø§Ù„Ø¬Ù„Ø³Ø©
        $lesson = Lesson::create([
            'course_id' => $course->course_id,
            'teacher_id' => $teacher->teacher_id,
            'title' => 'Ø­ØµØ© ' . now()->format('Y-m-d H:i'),
            'type' => 'session',
        ]);

        // ØªÙˆÙ„ÙŠØ¯ ØªÙˆÙƒÙ† Ø¹Ø´ÙˆØ§Ø¦ÙŠ ÙˆØ¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ø¬Ù„Ø³Ø©
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
     * Ø¬Ù„Ø¨ Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ø­Ø§Ø¶Ø±ÙŠÙ† ÙˆØ§Ù„ØºØ§Ø¦Ø¨ÙŠÙ† Ù„Ø¬Ù„Ø³Ø© Ù…Ø¹ÙŠÙ†Ø©
     */
    public function getSessionAttendance(Request $request, $sessionId)
    {
        $session = \App\Models\AttendanceSession::find($sessionId);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ø¬Ù„Ø³Ø© ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯Ø©'], 404);
        }

        $lesson = $session->lesson;
        $course = $lesson->course;

        $enrolledStudents = $course->students()->with('user')->get();

        $presentIds = Attendance::where('lesson_id', $lesson->lesson_id)
            ->where('status', 'present')
            ->pluck('student_id')
            ->toArray();

        // Ø¥Ø¶Ø§ÙØ© Ø§Ù„Ø·Ù„Ø§Ø¨ Ø§Ù„Ø°ÙŠÙ† Ù…Ø³Ø­ÙˆØ§ QR Ù„ÙƒÙ† ØºÙŠØ± Ù…Ø³Ø¬Ù‘Ù„ÙŠÙ† ÙÙŠ Ø§Ù„ÙƒÙˆØ±Ø³
        $enrolledIds = $enrolledStudents->pluck('student_id')->toArray();
        $extraIds = array_diff($presentIds, $enrolledIds);
        $extraStudents = !empty($extraIds)
            ? \App\Models\Student::with('user')->whereIn('student_id', $extraIds)->get()
            : collect();

        $allStudents = $enrolledStudents->merge($extraStudents);

        $students = $allStudents->map(function ($student) use ($presentIds) {
            return [
                'student_id' => $student->student_id,
                'name'   => $student->user->full_name ?? $student->user->name ?? 'ØºÙŠØ± Ù…Ø¹Ø±ÙˆÙ',
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
     * Ø¥Ù†Ù‡Ø§Ø¡ Ø¬Ù„Ø³Ø© Ø§Ù„Ø­Ø¶ÙˆØ± ÙˆØªØ³Ø¬ÙŠÙ„ Ø§Ù„ØºÙŠØ§Ø¨
     */
    public function endSession(Request $request, $sessionId)
    {
        $session = \App\Models\AttendanceSession::find($sessionId);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ø¬Ù„Ø³Ø© ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯Ø©'], 404);
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
            'message' => 'ØªÙ… Ø¥Ù†Ù‡Ø§Ø¡ Ø§Ù„Ø¬Ù„Ø³Ø© ÙˆØªØ³Ø¬ÙŠÙ„ Ø§Ù„ØºÙŠØ§Ø¨ Ø¨Ù†Ø¬Ø§Ø­',
        ], 200);
    }

    /**
     * Ø¥Ø¯Ø®Ø§Ù„ Ø§Ù„Ø¹Ù„Ø§Ù…Ø§Øª Ù„Ù„Ø·Ù„Ø§Ø¨
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

        // Ø§Ù„ØªØ£ÙƒØ¯ Ø£Ù† Ù‡Ø°Ø§ Ø§Ù„Ø§Ù…ØªØ­Ø§Ù† ÙŠØ®Øµ Ø§Ù„Ù…Ø¯Ø±Ø³
        $exam = \App\Models\Exam::where('exam_id', $request->exam_id)
            ->whereHas('course', function($query) use ($teacher) {
                $query->whereHas('teachers', function($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->teacher_id);
                });
            })->first();

        if (!$exam) {
            return response()->json([
                'success' => false,
                'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ†Ùƒ Ø¥Ø¯Ø®Ø§Ù„ Ø¹Ù„Ø§Ù…Ø§Øª Ù„Ù‡Ø°Ø§ Ø§Ù„Ø§Ù…ØªØ­Ø§Ù†'
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
            'message' => "ØªÙ… Ø¥Ø¯Ø®Ø§Ù„ Ø¹Ù„Ø§Ù…Ø§Øª $saved Ø·Ø§Ù„Ø¨ Ø¨Ù†Ø¬Ø§Ø­"
        ], 200);
    }

    /**
     * Ø¬Ù„Ø¨ Ø§Ù„Ø¹Ù„Ø§Ù…Ø§Øª Ù„Ø¯ÙˆØ±Ø© Ù…Ø¹ÙŠÙ†Ø©
     */
    public function getGrades(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø© ØºÙŠØ± Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ùƒ'
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
     * Ø¬Ù„Ø¨ ÙˆØ§Ø¬Ø¨Ø§Øª Ø§Ù„Ù…Ø¯Ø±Ø³
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
                    $status = 'Ù…ÙƒØªÙ…Ù„';
                } elseif ($isExpired && $submissionsCount > 0) {
                    $status = 'Ù‚ÙŠØ¯ Ø§Ù„ØªØµØ­ÙŠØ­';
                } elseif (!$isExpired && $submissionsCount === 0) {
                    $status = 'Ù‚ÙŠØ¯ Ø§Ù„Ø¥Ù†ØªØ¸Ø§Ø±';
                } else {
                    $status = 'Ù†Ø´Ø·';
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
                    'submissions_count' => $submissionsCount,
                    'status'          => $status,
                ];
            });

        return response()->json(['success' => true, 'data' => $assignments], 200);
    }

    /**
     * Ø¥Ù†Ø´Ø§Ø¡ ÙˆØ§Ø¬Ø¨ Ø¬Ø¯ÙŠØ¯
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
                'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ†Ùƒ Ø¥Ù†Ø´Ø§Ø¡ ÙˆØ§Ø¬Ø¨ ÙÙŠ Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø©'
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

        return response()->json([
            'success' => true,
            'message' => 'ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„ÙˆØ§Ø¬Ø¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data'    => $assignment
        ], 201);
    }

    /**
     * ØªØ­Ø¯ÙŠØ« ÙˆØ§Ø¬Ø¨
     */
    public function updateAssignment(Request $request, $assignmentId)
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Ø§Ù„ÙˆØ§Ø¬Ø¨ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'], 404);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $assignment->course_id)->first();

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ†Ùƒ ØªØ¹Ø¯ÙŠÙ„ Ù‡Ø°Ø§ Ø§Ù„ÙˆØ§Ø¬Ø¨'], 403);
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
            'message' => 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„ÙˆØ§Ø¬Ø¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data'    => $assignment
        ], 200);
    }

    /**
     * Ø­Ø°Ù ÙˆØ§Ø¬Ø¨
     */
    public function deleteAssignment(Request $request, $assignmentId)
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Ø§Ù„ÙˆØ§Ø¬Ø¨ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'
            ], 404);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $assignment->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ†Ùƒ Ø­Ø°Ù Ù‡Ø°Ø§ Ø§Ù„ÙˆØ§Ø¬Ø¨'
            ], 403);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'ØªÙ… Ø­Ø°Ù Ø§Ù„ÙˆØ§Ø¬Ø¨ Ø¨Ù†Ø¬Ø§Ø­'
        ], 200);
    }

    /**
     * ØªØµØ­ÙŠØ­ ÙˆØ§Ø¬Ø¨ Ø·Ø§Ù„Ø¨
     */
    public function gradeAssignment(Request $request, $submissionId)
    {
        $submission = AssignmentSubmission::with('assignment')->find($submissionId);

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Ø§Ù„ØªØ³Ù„ÙŠÙ… ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'], 404);
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
                'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ†Ùƒ ØªØµØ­ÙŠØ­ Ù‡Ø°Ø§ Ø§Ù„ÙˆØ§Ø¬Ø¨'
            ], 403);
        }

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ØªÙ… ØªØµØ­ÙŠØ­ Ø§Ù„ÙˆØ§Ø¬Ø¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data' => $submission
        ], 200);
    }

    /**
     * Ø¥Ù†Ø´Ø§Ø¡ Ø¥Ø¹Ù„Ø§Ù† Ø¬Ø¯ÙŠØ¯
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

        // Ø¥Ø°Ø§ ÙƒØ§Ù† Ø§Ù„Ø¥Ø¹Ù„Ø§Ù† Ø®Ø§Øµ Ø¨Ø¯ÙˆØ±Ø©ØŒ ØªØ£ÙƒØ¯ Ø£Ù† Ø§Ù„Ø¯ÙˆØ±Ø© ØªØ®Øµ Ø§Ù„Ù…Ø¯Ø±Ø³
        if ($request->type == 'course_specific' && $request->course_id) {
            $course = $teacher->courses()->where('course_id', $request->course_id)->first();
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ†Ùƒ Ø¥Ù†Ø´Ø§Ø¡ Ø¥Ø¹Ù„Ø§Ù† ÙÙŠ Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø©'
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
            'message' => 'ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ø¥Ø¹Ù„Ø§Ù† Ø¨Ù†Ø¬Ø§Ø­',
            'data' => $announcement
        ], 201);
    }

    /**
     * Ø¬Ù„Ø¨ Ø¥Ø¹Ù„Ø§Ù†Ø§Øª Ø§Ù„Ù…Ø¯Ø±Ø³
     */
    public function getAnnouncements(Request $request)
    {
        $announcements = Announcement::where('user_id', $request->user()->user_id)
            ->with('course')
            ->latest()
            ->get()
            ->map(function($announcement) {
                return [
                    'id'         => $announcement->announcement_id,
                    'title'      => $announcement->title,
                    'content'    => $announcement->content,
                    'type'       => $announcement->type,
                    'course'     => $announcement->course ? $announcement->course->title : null,
                    'created_at' => $announcement->created_at->format('Y-m-d H:i'),
                    'time_ago'   => $announcement->created_at->diffForHumans(),
                ];
            });

        return response()->json(['success' => true, 'data' => $announcements], 200);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Ø§Ù„Ø¬Ø¯ÙˆÙ„ Ø§Ù„Ø¯Ø±Ø§Ø³ÙŠ
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Ø§Ù„Ù…Ø­Ø§Ø¶Ø±Ø§Øª
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            return response()->json(['success' => false, 'message' => 'Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø© ØºÙŠØ± Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ùƒ'], 403);
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

        return response()->json(['success' => true, 'message' => 'ØªÙ… Ø±ÙØ¹ Ø§Ù„Ù…Ø­Ø§Ø¶Ø±Ø© Ø¨Ù†Ø¬Ø§Ø­', 'data' => $lesson], 201);
    }

    public function deleteLesson(Request $request, $lessonId)
    {
        $teacher = $request->user()->teacher;

        $lesson = Lesson::where('lesson_id', $lessonId)
            ->where('teacher_id', $teacher->teacher_id)
            ->first();

        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ù…Ø­Ø§Ø¶Ø±Ø© ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯Ø© Ø£Ùˆ Ù„Ø§ ØªØ®ØµÙƒ'], 404);
        }

        Storage::disk('public')->delete($lesson->content_url);
        $lesson->delete();

        return response()->json(['success' => true, 'message' => 'ØªÙ… Ø­Ø°Ù Ø§Ù„Ù…Ø­Ø§Ø¶Ø±Ø© Ø¨Ù†Ø¬Ø§Ø­'], 200);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Ø§Ù„Ø¥Ø´Ø¹Ø§Ø±Ø§Øª
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getNotifications(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($n) {
                return [
                    'id'         => $n->id,
                    'title'      => $n->title,
                    'message'    => $n->message,
                    'type'       => $n->type,
                    'is_read'    => $n->is_read,
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
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ø¥Ø´Ø¹Ø§Ø± ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'ØªÙ… ØªØ­Ø¯ÙŠØ¯ Ø§Ù„Ø¥Ø´Ø¹Ø§Ø± ÙƒÙ…Ù‚Ø±ÙˆØ¡'], 200);
    }

    public function markAllNotificationsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'ØªÙ… ØªØ­Ø¯ÙŠØ¯ ÙƒÙ„ Ø§Ù„Ø¥Ø´Ø¹Ø§Ø±Ø§Øª ÙƒÙ…Ù‚Ø±ÙˆØ¡Ø©'], 200);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  ØªØ³Ù„ÙŠÙ…Ø§Øª Ø§Ù„ÙˆØ§Ø¬Ø¨Ø§Øª
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Ø¬Ù„Ø¨ ÙƒÙ„ ØªØ³Ù„ÙŠÙ…Ø§Øª Ù…ÙˆØ§Ø¯ Ø§Ù„Ù…Ø¯Ø±Ø³
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
                    'student_name'     => $sub->student->user->full_name ?? 'ØºÙŠØ± Ù…Ø¹Ø±ÙˆÙ',
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
                    $q2->where('teacher_id', $teacher->teacher_id);
                });
            })->first();

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Ø§Ù„ÙˆØ§Ø¬Ø¨ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯ Ø£Ùˆ Ù„Ø§ ÙŠØ®ØµÙƒ'], 404);
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Ø§Ù„Ø§Ù…ØªØ­Ø§Ù†Ø§Øª
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            return response()->json(['success' => false, 'message' => 'Ù‡Ø°Ù‡ Ø§Ù„Ø¯ÙˆØ±Ø© ØºÙŠØ± Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ùƒ'], 403);
        }

        $exam = \App\Models\Exam::create([
            'course_id' => $request->course_id,
            'exam_name' => $request->exam_name,
            'exam_date' => $request->exam_date,
            'max_score' => $request->max_score ?? 100,
        ]);

        return response()->json(['success' => true, 'message' => 'ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ø§Ù…ØªØ­Ø§Ù† Ø¨Ù†Ø¬Ø§Ø­', 'data' => $exam], 201);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Ø§Ù„Ù…Ù„Ù Ø§Ù„Ø´Ø®ØµÙŠ
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            'message' => 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„ØµÙˆØ±Ø© Ø§Ù„Ø´Ø®ØµÙŠØ©',
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

        return response()->json(['success' => true, 'message' => 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„Ù…Ù„Ù Ø§Ù„Ø´Ø®ØµÙŠ Ø¨Ù†Ø¬Ø§Ø­'], 200);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Ø§Ù„Ø±Ø³Ø§Ø¦Ù„
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

        return response()->json(['success' => true, 'message' => 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø§Ù„Ø±Ø³Ø§Ù„Ø© Ø¨Ù†Ø¬Ø§Ø­', 'data' => $msg], 201);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Ø·Ù„Ø¨Ø§Øª Ø§Ù„ØºÙŠØ§Ø¨
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ø·Ù„Ø¨ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'], 404);
        }

        $absenceRequest->update([
            'status'      => $request->status,
            'reviewed_by' => $request->user()->user_id,
        ]);

        return response()->json(['success' => true, 'message' => 'ØªÙ… Ø§Ù„Ø±Ø¯ Ø¹Ù„Ù‰ Ø§Ù„Ø·Ù„Ø¨ Ø¨Ù†Ø¬Ø§Ø­'], 200);
    }
}
