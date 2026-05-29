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
     * Ã™â€žÃ™Ë†Ã˜Â­Ã˜Â© Ã˜ÂªÃ˜Â­Ã™Æ’Ã™â€¦ Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â¯Ã˜Â±Ã˜Â³ - Dashboard
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

        // Ã˜Â¥Ã˜Â­Ã˜ÂµÃ˜Â§Ã˜Â¦Ã™Å Ã˜Â§Ã˜Âª
        $totalStudents = $courses->sum('students_count');
        $totalCourses = $courses->count();

        // Ã˜Â¢Ã˜Â®Ã˜Â± 5 Ã™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨Ã˜Â§Ã˜Âª
        $recentAssignments = Assignment::whereIn('course_id', $courses->pluck('course_id'))
            ->with('course')
            ->latest()
            ->limit(5)
            ->get();

        // Ã˜Â¢Ã˜Â®Ã˜Â± 5 Ã˜Â¥Ã˜Â¹Ã™â€žÃ˜Â§Ã™â€ Ã˜Â§Ã˜Âª
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
                        'author_name' => $announcement->user ? $announcement->user->full_name : 'Ø§Ù„Ø¥Ø¯Ø§Ø±Ø©',
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
     * Ã˜Â¬Ã™â€žÃ˜Â¨ Ã˜Â¬Ã™â€¦Ã™Å Ã˜Â¹ Ã˜Â¯Ã™Ë†Ã˜Â±Ã˜Â§Ã˜Âª Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â¯Ã˜Â±Ã˜Â³
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

        // Ã™ÂÃ™â€žÃ˜ÂªÃ˜Â±Ã˜Â© Ã˜Â­Ã˜Â³Ã˜Â¨ Ã˜Â§Ã™â€žÃ˜Â¨Ã˜Â±Ã™â€ Ã˜Â§Ã™â€¦Ã˜Â¬/Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â©
        if ($request->filled('program_id')) {
            $query->whereHas('programs', function ($q) use ($request) {
                $q->where('programs.id', $request->program_id);
            });
        }

        // Ã™ÂÃ™â€žÃ˜ÂªÃ˜Â±Ã˜Â© Ã˜Â­Ã˜Â³Ã˜Â¨ Ã˜Â§Ã™â€žÃ˜Â³Ã™â€ Ã˜Â© Ã˜Â§Ã™â€žÃ˜Â¯Ã˜Â±Ã˜Â§Ã˜Â³Ã™Å Ã˜Â©
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
     * Ã˜Â¬Ã™â€žÃ˜Â¨ Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¨ Ã˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã™â€¦Ã˜Â¹Ã™Å Ã™â€ Ã˜Â©
     */
    public function courseStudents(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        // Ã˜Â§Ã™â€žÃ˜ÂªÃ˜Â£Ã™Æ’Ã˜Â¯ Ã˜Â£Ã™â€  Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂªÃ˜Â®Ã˜Âµ Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â¯Ã˜Â±Ã˜Â³
        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã˜Â·Ã˜Â© Ã˜Â¨Ã™Æ’'
            ], 403);
        }

        $students = $course->students()
            ->with('user')
            ->get()
            ->map(function($student) use ($courseId) {
                // Ã˜Â¥Ã˜Â­Ã˜ÂµÃ˜Â§Ã˜Â¦Ã™Å Ã˜Â§Ã˜Âª Ã˜Â§Ã™â€žÃ˜Â·Ã˜Â§Ã™â€žÃ˜Â¨ Ã™ÂÃ™Å  Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â©
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
     * Ã˜ÂªÃ˜Â³Ã˜Â¬Ã™Å Ã™â€ž Ã˜Â§Ã™â€žÃ˜Â­Ã˜Â¶Ã™Ë†Ã˜Â± Ã™Ë†Ã˜Â§Ã™â€žÃ˜ÂºÃ™Å Ã˜Â§Ã˜Â¨
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

        // Ã˜Â§Ã™â€žÃ˜ÂªÃ˜Â£Ã™Æ’Ã˜Â¯ Ã˜Â£Ã™â€  Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂªÃ˜Â®Ã˜Âµ Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â¯Ã˜Â±Ã˜Â³
        $course = $teacher->courses()->where('course_id', $request->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ã™â€žÃ˜Â§ Ã™Å Ã™â€¦Ã™Æ’Ã™â€ Ã™Æ’ Ã˜ÂªÃ˜Â³Ã˜Â¬Ã™Å Ã™â€ž Ã˜Â­Ã˜Â¶Ã™Ë†Ã˜Â± Ã™ÂÃ™Å  Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â©'
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
            'message' => "Ã˜ÂªÃ™â€¦ Ã˜ÂªÃ˜Â³Ã˜Â¬Ã™Å Ã™â€ž Ã˜Â­Ã˜Â¶Ã™Ë†Ã˜Â± $saved Ã˜Â·Ã˜Â§Ã™â€žÃ˜Â¨ Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­"
        ], 200);
    }

    /**
     * Ã˜Â¬Ã™â€žÃ˜Â¨ Ã˜Â³Ã˜Â¬Ã™â€ž Ã˜Â§Ã™â€žÃ˜Â­Ã˜Â¶Ã™Ë†Ã˜Â± Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã™â€¦Ã˜Â¹Ã™Å Ã™â€ Ã˜Â©
     */
    public function getAttendance(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã˜Â·Ã˜Â© Ã˜Â¨Ã™Æ’'
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
     * Ã˜ÂªÃ™Ë†Ã™â€žÃ™Å Ã˜Â¯ QR Ã™â€žÃ˜Â¬Ã™â€žÃ˜Â³Ã˜Â© Ã˜Â­Ã˜Â¶Ã™Ë†Ã˜Â±
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
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã˜Â·Ã˜Â© Ã˜Â¨Ã™Æ’'], 403);
        }

        // Ã˜Â¥Ã™â€ Ã˜Â´Ã˜Â§Ã˜Â¡ Ã˜Â¯Ã˜Â±Ã˜Â³ Ã™â€¦Ã˜Â¤Ã™â€šÃ˜Âª Ã™â€žÃ™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¬Ã™â€žÃ˜Â³Ã˜Â©
        $lesson = Lesson::create([
            'course_id' => $course->course_id,
            'teacher_id' => $teacher->teacher_id,
            'title' => 'Ã˜Â­Ã˜ÂµÃ˜Â© ' . now()->format('Y-m-d H:i'),
            'type' => 'session',
        ]);

        // Ã˜ÂªÃ™Ë†Ã™â€žÃ™Å Ã˜Â¯ Ã˜ÂªÃ™Ë†Ã™Æ’Ã™â€  Ã˜Â¹Ã˜Â´Ã™Ë†Ã˜Â§Ã˜Â¦Ã™Å  Ã™Ë†Ã˜Â¥Ã™â€ Ã˜Â´Ã˜Â§Ã˜Â¡ Ã˜Â§Ã™â€žÃ˜Â¬Ã™â€žÃ˜Â³Ã˜Â©
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
     * Ã˜Â¬Ã™â€žÃ˜Â¨ Ã™â€šÃ˜Â§Ã˜Â¦Ã™â€¦Ã˜Â© Ã˜Â§Ã™â€žÃ˜Â­Ã˜Â§Ã˜Â¶Ã˜Â±Ã™Å Ã™â€  Ã™Ë†Ã˜Â§Ã™â€žÃ˜ÂºÃ˜Â§Ã˜Â¦Ã˜Â¨Ã™Å Ã™â€  Ã™â€žÃ˜Â¬Ã™â€žÃ˜Â³Ã˜Â© Ã™â€¦Ã˜Â¹Ã™Å Ã™â€ Ã˜Â©
     */
    public function getSessionAttendance(Request $request, $sessionId)
    {
        $session = \App\Models\AttendanceSession::find($sessionId);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ˜Â¬Ã™â€žÃ˜Â³Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯Ã˜Â©'], 404);
        }

        $lesson = $session->lesson;
        $course = $lesson->course;

        $enrolledStudents = $course->students()->with('user')->get();

        $presentIds = Attendance::where('lesson_id', $lesson->lesson_id)
            ->where('status', 'present')
            ->pluck('student_id')
            ->toArray();

        // Ã˜Â¥Ã˜Â¶Ã˜Â§Ã™ÂÃ˜Â© Ã˜Â§Ã™â€žÃ˜Â·Ã™â€žÃ˜Â§Ã˜Â¨ Ã˜Â§Ã™â€žÃ˜Â°Ã™Å Ã™â€  Ã™â€¦Ã˜Â³Ã˜Â­Ã™Ë†Ã˜Â§ QR Ã™â€žÃ™Æ’Ã™â€  Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â³Ã˜Â¬Ã™â€˜Ã™â€žÃ™Å Ã™â€  Ã™ÂÃ™Å  Ã˜Â§Ã™â€žÃ™Æ’Ã™Ë†Ã˜Â±Ã˜Â³
        $enrolledIds = $enrolledStudents->pluck('student_id')->toArray();
        $extraIds = array_diff($presentIds, $enrolledIds);
        $extraStudents = !empty($extraIds)
            ? \App\Models\Student::with('user')->whereIn('student_id', $extraIds)->get()
            : collect();

        $allStudents = $enrolledStudents->merge($extraStudents);

        $students = $allStudents->map(function ($student) use ($presentIds) {
            return [
                'student_id' => $student->student_id,
                'name'   => $student->user->full_name ?? $student->user->name ?? 'Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â¹Ã˜Â±Ã™Ë†Ã™Â',
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
     * Ã˜Â¥Ã™â€ Ã™â€¡Ã˜Â§Ã˜Â¡ Ã˜Â¬Ã™â€žÃ˜Â³Ã˜Â© Ã˜Â§Ã™â€žÃ˜Â­Ã˜Â¶Ã™Ë†Ã˜Â± Ã™Ë†Ã˜ÂªÃ˜Â³Ã˜Â¬Ã™Å Ã™â€ž Ã˜Â§Ã™â€žÃ˜ÂºÃ™Å Ã˜Â§Ã˜Â¨
     */
    public function endSession(Request $request, $sessionId)
    {
        $session = \App\Models\AttendanceSession::find($sessionId);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ˜Â¬Ã™â€žÃ˜Â³Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯Ã˜Â©'], 404);
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
            'message' => 'Ã˜ÂªÃ™â€¦ Ã˜Â¥Ã™â€ Ã™â€¡Ã˜Â§Ã˜Â¡ Ã˜Â§Ã™â€žÃ˜Â¬Ã™â€žÃ˜Â³Ã˜Â© Ã™Ë†Ã˜ÂªÃ˜Â³Ã˜Â¬Ã™Å Ã™â€ž Ã˜Â§Ã™â€žÃ˜ÂºÃ™Å Ã˜Â§Ã˜Â¨ Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­',
        ], 200);
    }

    /**
     * Ã˜Â¥Ã˜Â¯Ã˜Â®Ã˜Â§Ã™â€ž Ã˜Â§Ã™â€žÃ˜Â¹Ã™â€žÃ˜Â§Ã™â€¦Ã˜Â§Ã˜Âª Ã™â€žÃ™â€žÃ˜Â·Ã™â€žÃ˜Â§Ã˜Â¨
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

        // Ã˜Â§Ã™â€žÃ˜ÂªÃ˜Â£Ã™Æ’Ã˜Â¯ Ã˜Â£Ã™â€  Ã™â€¡Ã˜Â°Ã˜Â§ Ã˜Â§Ã™â€žÃ˜Â§Ã™â€¦Ã˜ÂªÃ˜Â­Ã˜Â§Ã™â€  Ã™Å Ã˜Â®Ã˜Âµ Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â¯Ã˜Â±Ã˜Â³
        $exam = \App\Models\Exam::where('exam_id', $request->exam_id)
            ->whereHas('course', function($query) use ($teacher) {
                $query->whereHas('teachers', function($q) use ($teacher) {
                    $q->where('teachers.teacher_id', $teacher->teacher_id);
                });
            })->first();

        if (!$exam) {
            return response()->json([
                'success' => false,
                'message' => 'Ã™â€žÃ˜Â§ Ã™Å Ã™â€¦Ã™Æ’Ã™â€ Ã™Æ’ Ã˜Â¥Ã˜Â¯Ã˜Â®Ã˜Â§Ã™â€ž Ã˜Â¹Ã™â€žÃ˜Â§Ã™â€¦Ã˜Â§Ã˜Âª Ã™â€žÃ™â€¡Ã˜Â°Ã˜Â§ Ã˜Â§Ã™â€žÃ˜Â§Ã™â€¦Ã˜ÂªÃ˜Â­Ã˜Â§Ã™â€ '
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
            'message' => "Ã˜ÂªÃ™â€¦ Ã˜Â¥Ã˜Â¯Ã˜Â®Ã˜Â§Ã™â€ž Ã˜Â¹Ã™â€žÃ˜Â§Ã™â€¦Ã˜Â§Ã˜Âª $saved Ã˜Â·Ã˜Â§Ã™â€žÃ˜Â¨ Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­"
        ], 200);
    }

    /**
     * Ã˜Â¬Ã™â€žÃ˜Â¨ Ã˜Â§Ã™â€žÃ˜Â¹Ã™â€žÃ˜Â§Ã™â€¦Ã˜Â§Ã˜Âª Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã™â€¦Ã˜Â¹Ã™Å Ã™â€ Ã˜Â©
     */
    public function getGrades(Request $request, $courseId)
    {
        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $courseId)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã˜Â·Ã˜Â© Ã˜Â¨Ã™Æ’'
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
     * Ã˜Â¬Ã™â€žÃ˜Â¨ Ã™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨Ã˜Â§Ã˜Âª Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â¯Ã˜Â±Ã˜Â³
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
                    $status = 'Ã™â€¦Ã™Æ’Ã˜ÂªÃ™â€¦Ã™â€ž';
                } elseif ($isExpired && $submissionsCount > 0) {
                    $status = 'Ã™â€šÃ™Å Ã˜Â¯ Ã˜Â§Ã™â€žÃ˜ÂªÃ˜ÂµÃ˜Â­Ã™Å Ã˜Â­';
                } elseif (!$isExpired && $submissionsCount === 0) {
                    $status = 'Ã™â€šÃ™Å Ã˜Â¯ Ã˜Â§Ã™â€žÃ˜Â¥Ã™â€ Ã˜ÂªÃ˜Â¸Ã˜Â§Ã˜Â±';
                } else {
                    $status = 'Ã™â€ Ã˜Â´Ã˜Â·';
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
     * Ã˜Â¥Ã™â€ Ã˜Â´Ã˜Â§Ã˜Â¡ Ã™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨ Ã˜Â¬Ã˜Â¯Ã™Å Ã˜Â¯
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
                'message' => 'Ã™â€žÃ˜Â§ Ã™Å Ã™â€¦Ã™Æ’Ã™â€ Ã™Æ’ Ã˜Â¥Ã™â€ Ã˜Â´Ã˜Â§Ã˜Â¡ Ã™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨ Ã™ÂÃ™Å  Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â©'
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

        // Ø¥Ø´Ø¹Ø§Ø± Ø§Ù„Ø·Ù„Ø§Ø¨ Ø§Ù„Ù…Ø³Ø¬Ù„ÙŠÙ† ÙÙŠ Ø§Ù„Ù…Ø§Ø¯Ø©
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
            'title'      => 'ÙˆØ§Ø¬Ø¨ Ø¬Ø¯ÙŠØ¯ â€” ' . $course->name,
            'message'    => 'Ø±ÙØ¹ Ø§Ù„Ù…Ø¹Ù„Ù… ' . $teacherUser->full_name . ' ÙˆØ§Ø¬Ø¨Ø§Ù‹ Ø¬Ø¯ÙŠØ¯Ø§Ù‹: ' . $request->title,
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
            $fcmTitle = 'ÙˆØ§Ø¬Ø¨ Ø¬Ø¯ÙŠØ¯ â€” ' . $course->name;
            $fcmBody  = 'Ø±ÙØ¹ Ø§Ù„Ù…Ø¹Ù„Ù… ' . $teacherUser->full_name . ' ÙˆØ§Ø¬Ø¨Ø§Ù‹ Ø¬Ø¯ÙŠØ¯Ø§Ù‹: ' . $request->title;
            foreach ($studentUserIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, $fcmTitle, $fcmBody, [
                    'type' => 'assignment',
                    'related_id' => (string) $assignment->assignment_id,
                ]);
            }
        }

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
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨ Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯'], 404);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $assignment->course_id)->first();

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Ã™â€žÃ˜Â§ Ã™Å Ã™â€¦Ã™Æ’Ã™â€ Ã™Æ’ Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã™Å Ã™â€ž Ã™â€¡Ã˜Â°Ã˜Â§ Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨'], 403);
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
            'message' => 'Ã˜ÂªÃ™â€¦ Ã˜ÂªÃ˜Â­Ã˜Â¯Ã™Å Ã˜Â« Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨ Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­',
            'data'    => $assignment
        ], 200);
    }

    /**
     * Ã˜Â­Ã˜Â°Ã™Â Ã™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨
     */
    public function deleteAssignment(Request $request, $assignmentId)
    {
        $assignment = Assignment::find($assignmentId);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨ Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯'
            ], 404);
        }

        $teacher = $request->user()->teacher;

        $course = $teacher->courses()->where('course_id', $assignment->course_id)->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Ã™â€žÃ˜Â§ Ã™Å Ã™â€¦Ã™Æ’Ã™â€ Ã™Æ’ Ã˜Â­Ã˜Â°Ã™Â Ã™â€¡Ã˜Â°Ã˜Â§ Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨'
            ], 403);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ã˜ÂªÃ™â€¦ Ã˜Â­Ã˜Â°Ã™Â Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨ Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­'
        ], 200);
    }

    /**
     * Ã˜ÂªÃ˜ÂµÃ˜Â­Ã™Å Ã˜Â­ Ã™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨ Ã˜Â·Ã˜Â§Ã™â€žÃ˜Â¨
     */
    public function gradeAssignment(Request $request, $submissionId)
    {
        $submission = AssignmentSubmission::with('assignment')->find($submissionId);

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ˜ÂªÃ˜Â³Ã™â€žÃ™Å Ã™â€¦ Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯'], 404);
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
                'message' => 'Ã™â€žÃ˜Â§ Ã™Å Ã™â€¦Ã™Æ’Ã™â€ Ã™Æ’ Ã˜ÂªÃ˜ÂµÃ˜Â­Ã™Å Ã˜Â­ Ã™â€¡Ã˜Â°Ã˜Â§ Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨'
            ], 403);
        }

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
        ]);

        // Ø¥Ø´Ø¹Ø§Ø± Ø§Ù„Ø·Ø§Ù„Ø¨ Ø¨ØªØµØ­ÙŠØ­ ÙˆØ§Ø¬Ø¨Ù‡
        $studentUserId = DB::table('students')
            ->where('student_id', $submission->student_id)
            ->value('user_id');
        if ($studentUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $studentUserId,
                'sender_id'  => $request->user()->user_id,
                'title'      => 'ØªÙ… ØªØµØ­ÙŠØ­ ÙˆØ§Ø¬Ø¨Ùƒ',
                'message'    => 'ØµØ­Ù‘Ø­ Ø§Ù„Ù…Ø¹Ù„Ù… ÙˆØ§Ø¬Ø¨ "' . $submission->assignment->title . '" â€” Ø¹Ù„Ø§Ù…ØªÙƒ: ' . $request->grade . '/' . ($submission->assignment->max_points ?? 100),
                'type'       => 'assignment',
                'related_id' => $submission->assignment->assignment_id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'ØªÙ… ØªØµØ­ÙŠØ­ Ø§Ù„ÙˆØ§Ø¬Ø¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data' => $submission
        ], 200);
    }

    /**
     * Ã˜Â¥Ã™â€ Ã˜Â´Ã˜Â§Ã˜Â¡ Ã˜Â¥Ã˜Â¹Ã™â€žÃ˜Â§Ã™â€  Ã˜Â¬Ã˜Â¯Ã™Å Ã˜Â¯
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

        // Ã˜Â¥Ã˜Â°Ã˜Â§ Ã™Æ’Ã˜Â§Ã™â€  Ã˜Â§Ã™â€žÃ˜Â¥Ã˜Â¹Ã™â€žÃ˜Â§Ã™â€  Ã˜Â®Ã˜Â§Ã˜Âµ Ã˜Â¨Ã˜Â¯Ã™Ë†Ã˜Â±Ã˜Â©Ã˜Å’ Ã˜ÂªÃ˜Â£Ã™Æ’Ã˜Â¯ Ã˜Â£Ã™â€  Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂªÃ˜Â®Ã˜Âµ Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â¯Ã˜Â±Ã˜Â³
        if ($request->type == 'course_specific' && $request->course_id) {
            $course = $teacher->courses()->where('course_id', $request->course_id)->first();
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ã™â€žÃ˜Â§ Ã™Å Ã™â€¦Ã™Æ’Ã™â€ Ã™Æ’ Ã˜Â¥Ã™â€ Ã˜Â´Ã˜Â§Ã˜Â¡ Ã˜Â¥Ã˜Â¹Ã™â€žÃ˜Â§Ã™â€  Ã™ÂÃ™Å  Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â©'
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
            'message' => 'Ã˜ÂªÃ™â€¦ Ã˜Â¥Ã™â€ Ã˜Â´Ã˜Â§Ã˜Â¡ Ã˜Â§Ã™â€žÃ˜Â¥Ã˜Â¹Ã™â€žÃ˜Â§Ã™â€  Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­',
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
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ø·Ù„Ø¨ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'], 404);
        }

        $studentId = $reportRequest->student_id;
        $courseId  = $reportRequest->course_id;

        // Ù…ØªÙˆØ³Ø· Ø§Ù„Ø¹Ù„Ø§Ù…Ø§Øª
        $gradesQuery = DB::table('grades')->where('student_id', $studentId);
        if ($courseId) $gradesQuery->where('course_id', $courseId);
        $grades    = $gradesQuery->get(['score']);
        $avgGrade  = $grades->count() > 0 ? round($grades->avg('score'), 1) : null;

        // Ù†Ø³Ø¨Ø© Ø§Ù„Ø­Ø¶ÙˆØ±
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
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ø·Ù„Ø¨ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'], 404);
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

        // Ø¥Ø´Ø¹Ø§Ø± Ø±Ø¦ÙŠØ³ Ø§Ù„Ù‚Ø³Ù… (Ø§Ù„Ø£Ø³Ø§Ø³ÙŠ)
        DB::table('notifications')->insert([
            'user_id'    => $reportRequest->head_id,
            'title'      => 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ ØªÙ‚ÙŠÙŠÙ… Ø§Ù„Ø·Ø§Ù„Ø¨',
            'message'    => 'Ù‚Ø¯Ù‘Ù… Ø§Ù„Ù…Ø¹Ù„Ù… ØªÙ‚ÙŠÙŠÙ…Ù‡ Ù„Ù„Ø·Ø§Ù„Ø¨ ' . ($studentName ?? ''),
            'type'       => 'report',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ø­ÙØ¸ Ø§Ù„ØªÙ‚Ø±ÙŠØ± ÙÙŠ performance_reports + Ø¥Ø´Ø¹Ø§Ø± ÙˆÙ„ÙŠ Ø§Ù„Ø£Ù…Ø± (Ø«Ø§Ù†ÙˆÙŠ â€” Ù„Ø§ ÙŠÙƒØ³Ø± Ø§Ù„Ø¹Ù…Ù„ÙŠØ©)
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
                    'title'      => 'ØªÙ‚Ø±ÙŠØ± Ø§Ù„Ø·Ø§Ù„Ø¨ Ø¬Ø§Ù‡Ø²',
                    'message'    => 'Ø£Ø±Ø³Ù„ Ø§Ù„Ù…Ø¹Ù„Ù… Ø§Ù„ØªÙ‚Ø±ÙŠØ± Ø§Ù„Ø³Ù„ÙˆÙƒÙŠ Ù„Ù„Ø·Ø§Ù„Ø¨ ' . ($studentName ?? '') . 'ØŒ ÙŠÙ…ÙƒÙ†Ùƒ Ø§Ù„Ø§Ø·Ù„Ø§Ø¹ Ø¹Ù„ÙŠÙ‡ Ø§Ù„Ø¢Ù†.',
                    'type'       => 'report',
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('submitEvaluation side-effects failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø§Ù„ØªÙ‚ÙŠÙŠÙ… Ø¨Ù†Ø¬Ø§Ø­']);
    }

    /**
     * Ø¬Ù„Ø¨ Ø¥Ø¹Ù„Ø§Ù†Ø§Øª Ø§Ù„Ù…Ø¯Ø±Ø³
     */
    public function getAnnouncements(Request $request)
    {
        // Ø¥Ø¹Ù„Ø§Ù†Ø§Øª Ø§Ù„Ù…Ø¹Ù„Ù… Ù†ÙØ³Ù‡ + Ø¥Ø¹Ù„Ø§Ù†Ø§Øª Ø±Ø¦ÙŠØ³ Ø§Ù„Ù‚Ø³Ù… Ø§Ù„Ù…ÙˆØ¬Ù‡Ø© Ù„Ù„Ù…Ø¹Ù„Ù…ÙŠÙ† Ø£Ùˆ Ù„Ù„Ø¬Ù…ÙŠØ¹
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
    //  Ã˜Â§Ã™â€žÃ˜Â¬Ã˜Â¯Ã™Ë†Ã™â€ž Ã˜Â§Ã™â€žÃ˜Â¯Ã˜Â±Ã˜Â§Ã˜Â³Ã™Å 
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
    //  Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â­Ã˜Â§Ã˜Â¶Ã˜Â±Ã˜Â§Ã˜Âª
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
            return response()->json(['success' => false, 'message' => 'Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã˜Â·Ã˜Â© Ã˜Â¨Ã™Æ’'], 403);
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
        $courseName     = $course->name ?? $course->title ?? 'Ø§Ù„Ù…Ø§Ø¯Ø©';
        $studentIds     = DB::table('enrollments')->where('course_id', $request->course_id)->pluck('student_id');
        $studentUserIds = DB::table('students')->whereIn('student_id', $studentIds)->pluck('user_id');
        $notifNow = now();
        $notifRows = $studentUserIds->map(fn($uid) => [
            'user_id'    => $uid,
            'sender_id'  => $teacherUser->user_id,
            'title'      => 'Ù…Ø­Ø§Ø¶Ø±Ø© Ø¬Ø¯ÙŠØ¯Ø© â€” ' . $courseName,
            'message'    => 'Ø±ÙØ¹ Ø§Ù„Ù…Ø¹Ù„Ù… ' . $teacherUser->full_name . ' Ù…Ø­Ø§Ø¶Ø±Ø© Ø¬Ø¯ÙŠØ¯Ø©: ' . $request->title,
            'type'       => 'lecture',
            'related_id' => $lesson->lesson_id,
            'is_read'    => 0,
            'created_at' => $notifNow,
            'updated_at' => $notifNow,
        ])->all();
        if (!empty($notifRows)) {
            DB::table('notifications')->insert($notifRows);
            $fcmTitle = 'محاضرة جديدة  — ' . $courseName;
            $fcmBody  = 'رفع المعلم ' . $teacherUser->full_name . ' محاضرة جديدة: ' . $request->title;
            foreach ($studentUserIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, $fcmTitle, $fcmBody, ['type' => 'lecture']);
            }
        }

        return response()->json(['success' => true, 'message' => 'Ã˜ÂªÃ™â€¦ Ã˜Â±Ã™ÂÃ˜Â¹ Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â­Ã˜Â§Ã˜Â¶Ã˜Â±Ã˜Â© Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­', 'data' => $lesson], 201);
    }


    public function updateLesson(Request $request, $lessonId)
    {
        $teacher = $request->user()->teacher;

        $lesson = Lesson::where('lesson_id', $lessonId)
            ->where('teacher_id', $teacher->teacher_id)
            ->first();

        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'Ø§Ù„Ù…Ø­Ø§Ø¶Ø±Ø© ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯Ø© Ø£Ùˆ Ù„Ø§ ØªØ®ØµÙƒ'], 404);
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

        return response()->json(['success' => true, 'message' => 'ØªÙ… ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„Ù…Ø­Ø§Ø¶Ø±Ø© Ø¨Ù†Ø¬Ø§Ø­', 'data' => $lesson], 200);
    }
    public function deleteLesson(Request $request, $lessonId)
    {
        $teacher = $request->user()->teacher;

        $lesson = Lesson::where('lesson_id', $lessonId)
            ->where('teacher_id', $teacher->teacher_id)
            ->first();

        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â­Ã˜Â§Ã˜Â¶Ã˜Â±Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯Ã˜Â© Ã˜Â£Ã™Ë† Ã™â€žÃ˜Â§ Ã˜ÂªÃ˜Â®Ã˜ÂµÃ™Æ’'], 404);
        }

        try {
            if ($lesson->content_url) Storage::disk('public')->delete($lesson->content_url);
            $lesson->delete();
        } catch (\Exception $e) {
            \Log::error('Delete lesson: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Ã˜ÂªÃ™â€¦ Ã˜Â­Ã˜Â°Ã™Â Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â­Ã˜Â§Ã˜Â¶Ã˜Â±Ã˜Â© Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­'], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  Ã˜Â§Ã™â€žÃ˜Â¥Ã˜Â´Ã˜Â¹Ã˜Â§Ã˜Â±Ã˜Â§Ã˜Âª
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
                        'author_name' => $ann->author_name ?? 'Ø§Ù„Ø¥Ø¯Ø§Ø±Ø©',
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
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ˜Â¥Ã˜Â´Ã˜Â¹Ã˜Â§Ã˜Â± Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Ã˜ÂªÃ™â€¦ Ã˜ÂªÃ˜Â­Ã˜Â¯Ã™Å Ã˜Â¯ Ã˜Â§Ã™â€žÃ˜Â¥Ã˜Â´Ã˜Â¹Ã˜Â§Ã˜Â± Ã™Æ’Ã™â€¦Ã™â€šÃ˜Â±Ã™Ë†Ã˜Â¡'], 200);
    }

    public function markAllNotificationsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Ã˜ÂªÃ™â€¦ Ã˜ÂªÃ˜Â­Ã˜Â¯Ã™Å Ã˜Â¯ Ã™Æ’Ã™â€ž Ã˜Â§Ã™â€žÃ˜Â¥Ã˜Â´Ã˜Â¹Ã˜Â§Ã˜Â±Ã˜Â§Ã˜Âª Ã™Æ’Ã™â€¦Ã™â€šÃ˜Â±Ã™Ë†Ã˜Â¡Ã˜Â©'], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  Ã˜ÂªÃ˜Â³Ã™â€žÃ™Å Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨Ã˜Â§Ã˜Âª
    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    /**
     * Ã˜Â¬Ã™â€žÃ˜Â¨ Ã™Æ’Ã™â€ž Ã˜ÂªÃ˜Â³Ã™â€žÃ™Å Ã™â€¦Ã˜Â§Ã˜Âª Ã™â€¦Ã™Ë†Ã˜Â§Ã˜Â¯ Ã˜Â§Ã™â€žÃ™â€¦Ã˜Â¯Ã˜Â±Ã˜Â³
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
                    'student_name'     => $sub->student->user->full_name ?? 'Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â¹Ã˜Â±Ã™Ë†Ã™Â',
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
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ™Ë†Ã˜Â§Ã˜Â¬Ã˜Â¨ Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â£Ã™Ë† Ã™â€žÃ˜Â§ Ã™Å Ã˜Â®Ã˜ÂµÃ™Æ’'], 404);
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
    //  Ã˜Â§Ã™â€žÃ˜Â§Ã™â€¦Ã˜ÂªÃ˜Â­Ã˜Â§Ã™â€ Ã˜Â§Ã˜Âª
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
            return response()->json(['success' => false, 'message' => 'Ã™â€¡Ã˜Â°Ã™â€¡ Ã˜Â§Ã™â€žÃ˜Â¯Ã™Ë†Ã˜Â±Ã˜Â© Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã˜Â·Ã˜Â© Ã˜Â¨Ã™Æ’'], 403);
        }

        $exam = \App\Models\Exam::create([
            'course_id' => $request->course_id,
            'exam_name' => $request->exam_name,
            'exam_date' => $request->exam_date,
            'max_score' => $request->max_score ?? 100,
        ]);

        return response()->json(['success' => true, 'message' => 'Ã˜ÂªÃ™â€¦ Ã˜Â¥Ã™â€ Ã˜Â´Ã˜Â§Ã˜Â¡ Ã˜Â§Ã™â€žÃ˜Â§Ã™â€¦Ã˜ÂªÃ˜Â­Ã˜Â§Ã™â€  Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­', 'data' => $exam], 201);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  Ã˜Â§Ã™â€žÃ™â€¦Ã™â€žÃ™Â Ã˜Â§Ã™â€žÃ˜Â´Ã˜Â®Ã˜ÂµÃ™Å 
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
            'message' => 'Ã˜ÂªÃ™â€¦ Ã˜ÂªÃ˜Â­Ã˜Â¯Ã™Å Ã˜Â« Ã˜Â§Ã™â€žÃ˜ÂµÃ™Ë†Ã˜Â±Ã˜Â© Ã˜Â§Ã™â€žÃ˜Â´Ã˜Â®Ã˜ÂµÃ™Å Ã˜Â©',
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

        return response()->json(['success' => true, 'message' => 'Ã˜ÂªÃ™â€¦ Ã˜ÂªÃ˜Â­Ã˜Â¯Ã™Å Ã˜Â« Ã˜Â§Ã™â€žÃ™â€¦Ã™â€žÃ™Â Ã˜Â§Ã™â€žÃ˜Â´Ã˜Â®Ã˜ÂµÃ™Å  Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­'], 200);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  Ã˜Â§Ã™â€žÃ˜Â±Ã˜Â³Ã˜Â§Ã˜Â¦Ã™â€ž
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

        return response()->json(['success' => true, 'message' => 'Ã˜ÂªÃ™â€¦ Ã˜Â¥Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ž Ã˜Â§Ã™â€žÃ˜Â±Ã˜Â³Ã˜Â§Ã™â€žÃ˜Â© Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­', 'data' => $msg], 201);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    //  Ã˜Â·Ã™â€žÃ˜Â¨Ã˜Â§Ã˜Âª Ã˜Â§Ã™â€žÃ˜ÂºÃ™Å Ã˜Â§Ã˜Â¨
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
            return response()->json(['success' => false, 'message' => 'Ã˜Â§Ã™â€žÃ˜Â·Ã™â€žÃ˜Â¨ Ã˜ÂºÃ™Å Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯'], 404);
        }

        $absenceRequest->update([
            'status'      => $request->status,
            'reviewed_by' => $request->user()->user_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Ã˜ÂªÃ™â€¦ Ã˜Â§Ã™â€žÃ˜Â±Ã˜Â¯ Ã˜Â¹Ã™â€žÃ™â€° Ã˜Â§Ã™â€žÃ˜Â·Ã™â€žÃ˜Â¨ Ã˜Â¨Ã™â€ Ã˜Â¬Ã˜Â§Ã˜Â­'], 200);
    }
}

