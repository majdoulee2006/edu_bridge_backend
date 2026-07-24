<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;
use App\Models\User;

class TeacherWebController extends Controller
{
    // ────────────────────────────────────────────────────────────
    //  AUTH
    // ────────────────────────────────────────────────────────────

    public function showLoginForm()
    {
        if (Auth::check()) {
            $teacher = Teacher::where('user_id', Auth::user()->user_id)->first();
            if ($teacher) return redirect('/teacher/dashboard');
        }
        return view('teacher.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required',
        ], [
            'login.required'    => 'اسم المستخدم أو البريد الإلكتروني مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $input = trim($request->login);

        // محاولة الدخول بـ email أو username أو phone أو full_name
        $fields = ['email', 'username', 'phone', 'full_name'];
        $authenticated = false;

        foreach ($fields as $field) {
            if (Auth::attempt([$field => $input, 'password' => $request->password], true)) {
                $authenticated = true;
                break;
            }
        }

        if ($authenticated) {
            $user = Auth::user();
            $teacher = Teacher::where('user_id', $user->user_id)->first();
            
            if (!$teacher) {
                if ($user->role === 'teacher') {
                    $teacher = Teacher::create(['user_id' => $user->user_id]);
                } else {
                    Auth::logout();
                    return back()->withErrors(['login' => 'هذا الحساب ليس حساب معلم.']);
                }
            }
            if (Auth::user()->status !== 'active') {
                Auth::logout();
                return back()->withErrors(['login' => 'عذراً. حسابك موقوف مؤقتاً.']);
            }
            $request->session()->regenerate();
            return redirect('/teacher/dashboard');
        }

        return back()->withInput()->withErrors(['login' => 'اسم المستخدم/البريد الإلكتروني أو كلمة المرور غير صحيحة.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/teacher/login');
    }

    // ────────────────────────────────────────────────────────────
    //  HELPER: get current teacher record
    // ────────────────────────────────────────────────────────────
    private function getTeacher()
    {
        return Teacher::where('user_id', Auth::user()->user_id)->first();
    }

    // ────────────────────────────────────────────────────────────
    //  DASHBOARD
    // ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $teacher = $this->getTeacher();

        // المواد التي يدرسها المعلم
        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.*')
            ->get();

        $courseIds = $courses->pluck('course_id');

        // عدد الواجبات النشطة
        $recentAssignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->whereIn('assignments.course_id', $courseIds)
            ->select(
                'assignments.*',
                'courses.title as course_title',
                DB::raw('(SELECT COUNT(*) FROM assignment_submissions WHERE assignment_submissions.assignment_id = assignments.assignment_id) as submissions_count'),
                DB::raw('(SELECT COUNT(*) FROM assignment_submissions WHERE assignment_submissions.assignment_id = assignments.assignment_id AND grade IS NOT NULL) as graded_count')
            )
            ->orderByDesc('assignments.created_at')
            ->get();

        // حصص اليوم (للكارت فقط - العدد)
        $today = now()->locale('en')->dayName;
        $todayCount = DB::table('schedules')
            ->where('teacher_id', $teacher->teacher_id)
            ->where('day', $today)
            ->count();

        // الإعلانات من رئيس القسم (العامة + المتعلقة بمواد المعلم)
        $announcements = DB::table('announcements')
            ->where(function($q) use ($courseIds) {
                $q->where('type', 'general')
                  ->orWhereIn('course_id', $courseIds);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // المحاضرات الخاصة بالمواد
        $lectures = DB::table('lessons')
            ->whereIn('course_id', $courseIds)
            ->where('lessons.type', '!=', 'session')
            ->where('lessons.title', 'not like', '%حضور%')
            ->where('lessons.title', 'not like', '%غياب%')
            ->where('lessons.title', 'not like', '%تفقد%')
            ->where('lessons.title', 'not like', '%حصة%')
            ->where(function($query) {
                $query->whereNull('lessons.content_url')
                      ->orWhere('lessons.content_url', 'not like', '%attendance%');
            })
            ->orderByDesc('created_at')
            ->get();

        return view('teacher.dashboard', compact(
            'teacher', 'courses',
            'recentAssignments', 'todayCount',
            'announcements', 'lectures'
        ));
    }

    // ────────────────────────────────────────────────────────────
    //  SCHEDULE
    // ────────────────────────────────────────────────────────────

    public function schedule()
    {
        $teacher = $this->getTeacher();

        $schedules = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
            ->where('schedules.teacher_id', $teacher->teacher_id)
            ->select('schedules.*', 'courses.title as course_title')
            ->orderByRaw("FIELD(schedules.day, 'Sunday','Monday','Tuesday','Wednesday','Thursday')")
            ->orderBy('schedules.start_time')
            ->get();

        // الامتحانات الخاصة بمواد المعلم
        $courseIds = DB::table('course_teachers')
            ->where('teacher_id', $teacher->teacher_id)
            ->pluck('course_id');

        $exams = DB::table('exams')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->whereIn('exams.course_id', $courseIds)
            ->select('exams.*', 'courses.title as course_title')
            ->orderBy('exams.exam_date')
            ->get();

        return view('teacher.schedule', compact('schedules', 'exams'));
    }

    // ────────────────────────────────────────────────────────────
    //  ATTENDANCE
    // ────────────────────────────────────────────────────────────

    public function attendance()
    {
        $teacher = $this->getTeacher();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title', 'courses.level')
            ->get();

        // جلسات الحضور الأخيرة (عبر lessons)
        $recentSessions = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->where('lessons.teacher_id', $teacher->teacher_id)
            ->select('attendance_sessions.*', 'courses.title as course_title', 'lessons.title as lesson_title')
            ->orderByDesc('attendance_sessions.created_at')
            ->limit(10)
            ->get();

        $isAdvisor = !empty($teacher->advisor_branch) && !empty($teacher->advisor_year);

        return view('teacher.attendance', compact('courses', 'recentSessions', 'isAdvisor', 'teacher'));
    }

    public function storeAttendanceSession(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'room'      => 'nullable|string|max:100',
        ]);

        $teacher = $this->getTeacher();

        $lessonId = DB::table('lessons')->insertGetId([
            'course_id'   => $request->course_id,
            'teacher_id'  => $teacher->teacher_id,
            'title'       => 'جلسة حضور - ' . now()->format('Y-m-d H:i'),
            'type'        => 'session',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('attendance_sessions')->insert([
            'lesson_id'          => $lessonId,
            'qr_token'           => \Illuminate\Support\Str::random(32),
            'expires_at'         => now()->addSeconds(30),
            'session_expires_at' => now()->addMinutes(10),
            'is_active'          => 1,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return redirect()->back()->with('success', 'تم بدء جلسة الحضور بنجاح لمدة 10 دقائق!');
    }

    public function refreshSessionQr($id)
    {
        $session = DB::table('attendance_sessions')->where('id', $id)->first();
        if (!$session || !$session->is_active) {
            return response()->json(['success' => false, 'message' => 'الجلسة غير موجودة أو منتهية'], 404);
        }

        if ($session->session_expires_at && now()->gt($session->session_expires_at)) {
            DB::table('attendance_sessions')->where('id', $id)->update(['is_active' => 0]);
            return response()->json(['success' => false, 'message' => 'انتهت مدة الجلسة (10 دقائق)', 'session_ended' => true], 200);
        }

        $newToken = \Illuminate\Support\Str::random(32);
        
        DB::table('attendance_sessions')
            ->where('id', $id)
            ->update([
                'qr_token'   => $newToken,
                'expires_at' => now()->addSeconds(30),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'qr_token' => $newToken,
            'expires_in_seconds' => 30,
        ]);
    }

    public function endSession($id)
    {
        $session = DB::table('attendance_sessions')->where('id', $id)->first();
        if (!$session) {
            return redirect()->back()->with('error', 'الجلسة غير موجودة');
        }

        DB::table('attendance_sessions')
            ->where('id', $id)
            ->update([
                'is_active' => 0,
                'expires_at' => now(), // إنهاء الصلاحية فوراً
                'closed_at' => now(),
                'updated_at' => now()
            ]);

        // ==========================================
        // إضافة سجل "غائب" لجميع الطلاب الذين لم يحضروا
        // ==========================================
        $sessionData = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->where('attendance_sessions.id', $id)
            ->select('attendance_sessions.*', 'lessons.course_id', 'lessons.lesson_id')
            ->first();

        if ($sessionData) {
            $course = DB::table('courses')->where('course_id', $sessionData->course_id)->first();
            $coursePrograms = DB::table('course_program')->where('course_id', $sessionData->course_id)->pluck('program_id')->toArray();
            $yearMap = [1 => 'السنة الأولى', 2 => 'السنة الثانية', 3 => 'السنة الثالثة', 4 => 'السنة الرابعة', 5 => 'السنة الخامسة'];
            $courseYearStr = $yearMap[$course->year] ?? null;

            // جلب كل الطلاب المطابقين للمادة
            $students = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('enrollments', function($join) use ($sessionData) {
                    $join->on('students.student_id', '=', 'enrollments.student_id')
                         ->where('enrollments.course_id', '=', $sessionData->course_id);
                })
                ->where(function($query) use ($coursePrograms, $courseYearStr) {
                    $query->whereNotNull('enrollments.enrollment_id');
                    if (!empty($coursePrograms) && $courseYearStr) {
                        $query->orWhere(function($q) use ($coursePrograms, $courseYearStr) {
                            $q->whereIn('students.program_id', $coursePrograms)
                              ->where('users.academic_year', $courseYearStr);
                        });
                    }
                })
                ->pluck('students.student_id')
                ->toArray();

            // جلب الطلاب اللي حضروا فعلاً
            $attendedStudents = DB::table('attendance')
                ->where('lesson_id', $sessionData->lesson_id)
                ->pluck('student_id')
                ->toArray();

            // الطلاب الغائبين = كل الطلاب - اللي حضروا
            $absentStudents = array_diff($students, $attendedStudents);

            $absentRecords = [];
            $now = now();
            $sessionDate = \Carbon\Carbon::parse($sessionData->created_at)->format('Y-m-d');

            foreach ($absentStudents as $absentId) {
                $absentRecords[] = [
                    'student_id' => $absentId,
                    'lesson_id' => $sessionData->lesson_id,
                    'status' => 'absent',
                    'attendance_date' => $sessionDate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($absentRecords)) {
                DB::table('attendance')->insert($absentRecords);
            }
        }

        return redirect()->back()->with('success', 'تم إيقاف الجلسة بنجاح! وتم تسجيل غياب الطلاب المتخلفين عن الحضور.');
    }

    public function exportAttendance($sessionId)
    {
        $session = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->where('attendance_sessions.id', $sessionId)
            ->select('attendance_sessions.*', 'courses.course_id', 'courses.title as course_title')
            ->first();

        if (!$session) abort(404);

        $course = DB::table('courses')->where('course_id', $session->course_id)->first();
        $coursePrograms = DB::table('course_program')->where('course_id', $session->course_id)->pluck('program_id')->toArray();
        $yearMap = [1 => 'السنة الأولى', 2 => 'السنة الثانية', 3 => 'السنة الثالثة', 4 => 'السنة الرابعة', 5 => 'السنة الخامسة'];
        $courseYearStr = $yearMap[$course->year] ?? null;

        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('enrollments', function($join) use ($session) {
                $join->on('students.student_id', '=', 'enrollments.student_id')
                     ->where('enrollments.course_id', '=', $session->course_id);
            })
            ->where(function($query) use ($coursePrograms, $courseYearStr) {
                $query->whereNotNull('enrollments.enrollment_id');
                if (!empty($coursePrograms) && $courseYearStr) {
                    $query->orWhere(function($q) use ($coursePrograms, $courseYearStr) {
                        $q->whereIn('students.program_id', $coursePrograms)
                          ->where('users.academic_year', $courseYearStr);
                    });
                }
            })
            ->select('students.student_id', 'users.full_name', 'users.academic_year as level')
            ->distinct()
            ->get();

        $attendances = DB::table('attendance')
            ->where('lesson_id', $session->lesson_id)
            ->get(['student_id', 'status', 'created_at'])
            ->keyBy('student_id');

        $csvData = "
        <html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">
        <head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
        <style>
            table { direction: rtl; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; border-collapse: collapse; }
            th { background-color: #f2f2f2; font-weight: bold; border: 1px solid #ddd; padding: 8px; }
            td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        </style>
        </head>
        <body>
            <table>
                <tr>
                    <th>اسم الطالب</th>
                    <th>القسم/السنة</th>
                    <th>حالة الحضور</th>
                    <th>تاريخ ووقت التسجيل</th>
                </tr>";

        $isToday = \Carbon\Carbon::parse($session->created_at)->isToday();
        foreach ($students as $student) {
            $att = $attendances->get($student->student_id);
            $statusRaw = $att ? $att->status : 'absent';
            
            if ($statusRaw === 'absent' && $isToday) {
                $statusText = 'قيد الانتظار';
                $color = '#d97706';
            } else {
                $statusText = ($statusRaw === 'present') ? 'حاضر' : 'غائب';
                $color = ($statusRaw === 'present') ? '#166534' : '#b91c1c';
            }
            
            $timeText = ($statusRaw === 'present') ? \Carbon\Carbon::parse($att->created_at)->format('Y-m-d H:i') : '-';
            
            $csvData .= "
                <tr>
                    <td>{$student->full_name}</td>
                    <td>{$student->level}</td>
                    <td style=\"color: {$color}; font-weight: bold;\">{$statusText}</td>
                    <td>{$timeText}</td>
                </tr>";
        }

        $csvData .= "
            </table>
        </body>
        </html>";

        $date = \Carbon\Carbon::parse($session->created_at)->format('Y-m-d');
        $timestamp = now()->format('H-i-s');
        $fileName = "attendance_{$session->course_id}_{$date}_{$timestamp}.xls";
        
        return response("\xEF\xBB\xBF" . $csvData)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"$fileName\"")
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
    }

    public function exportFilteredAttendance(Request $request)
    {
        $teacher = $this->getTeacher();
        
        $scope = $request->input('scope', 'my_courses');
        $courseId = $request->input('course_id');
        $period = $request->input('period', 'today');

        $startDate = null;
        $endDate = null;

        if ($period === 'today') {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($period === 'week') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } elseif ($period === 'semester') {
            $startDate = null;
            $endDate = null;
        }

        $sessionQuery = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->select('attendance_sessions.*', 'courses.course_id', 'courses.title as course_title', 'courses.year as course_year', 'lessons.lesson_id');

        if ($startDate && $endDate) {
            $sessionQuery->whereBetween('attendance_sessions.created_at', [$startDate, $endDate]);
        }

        if ($scope === 'my_courses') {
            $myCourseIds = DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->pluck('course_id')->toArray();
            if ($courseId) {
                if (!in_array($courseId, $myCourseIds)) {
                    abort(403, 'ليس لديك صلاحية على هذه المادة');
                }
                $sessionQuery->where('courses.course_id', $courseId);
            } else {
                $sessionQuery->whereIn('courses.course_id', $myCourseIds);
            }
        } elseif ($scope === 'advisor_class') {
            $advisorBranch = $teacher->advisor_branch;
            $advisorYear = $teacher->advisor_year;
            
            if (!$advisorBranch || !$advisorYear) {
                abort(403, 'لست مربياً لأي دورة');
            }

            // للمربي، يتم جلب الجلسات المتعلقة بالمواد التي تخص فرع وسنة الدورة
            $programIds = DB::table('programs')->where('name', $advisorBranch)->pluck('id')->toArray();
            
            $yearMapRev = ['السنة الأولى' => 1, 'السنة الثانية' => 2, 'السنة الثالثة' => 3, 'السنة الرابعة' => 4, 'السنة الخامسة' => 5];
            $courseYearNum = $yearMapRev[$advisorYear] ?? null;

            if ($courseId) {
                $sessionQuery->where('courses.course_id', $courseId);
            } else {
                // نفلتر المواد اللي بتخص الفرع والسنة الخاصة بالمربي
                $validCourses = DB::table('courses')
                    ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
                    ->whereIn('course_program.program_id', $programIds)
                    ->where('courses.year', $courseYearNum)
                    ->pluck('courses.course_id')->toArray();
                
                $sessionQuery->whereIn('courses.course_id', $validCourses);
            }
        }

        $sessions = $sessionQuery->orderBy('attendance_sessions.created_at')->get();
        // Collect courses to get all relevant students
        $relevantCourses = DB::table('courses')
            ->whereIn('course_id', function($query) use ($sessionQuery) {
                // Same base constraints as $sessionQuery but without the date filter
                $query->select('courses.course_id')->from('courses');
            })->get(); // Actually, we already applied the filters to $sessionQuery, but it's joining attendance_sessions.

        // Simpler: Just get the courses from the scope
        $courseQuery = DB::table('courses');
        if ($scope === 'my_courses') {
            $myCourseIds = DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->pluck('course_id')->toArray();
            if ($courseId) {
                $courseQuery->where('course_id', $courseId);
            } else {
                $courseQuery->whereIn('course_id', $myCourseIds);
            }
        } elseif ($scope === 'advisor_class') {
            $programIds = DB::table('programs')->where('name', $teacher->advisor_branch)->pluck('id')->toArray();
            $yearMapRev = ['السنة الأولى' => 1, 'السنة الثانية' => 2, 'السنة الثالثة' => 3, 'السنة الرابعة' => 4, 'السنة الخامسة' => 5];
            $courseYearNum = $yearMapRev[$teacher->advisor_year] ?? null;
            
            if ($courseId) {
                $courseQuery->where('course_id', $courseId);
            } else {
                $courseQuery->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
                    ->whereIn('course_program.program_id', $programIds)
                    ->where('courses.year', $courseYearNum)
                    ->select('courses.*')->distinct();
            }
        }

        $coursesList = $courseQuery->get();
        $allStudents = []; // student_id => ['name' => ..., 'level' => ...]
        $matrix = []; // student_id => [lesson_id => 'حاضر/غائب']

        $yearMap = [1 => 'السنة الأولى', 2 => 'السنة الثانية', 3 => 'السنة الثالثة', 4 => 'السنة الرابعة', 5 => 'السنة الخامسة'];

        // Populate students from all relevant courses
        foreach ($coursesList as $c) {
            $coursePrograms = DB::table('course_program')->where('course_id', $c->course_id)->pluck('program_id')->toArray();
            $courseYearStr = $yearMap[$c->year] ?? null;

            $students = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('enrollments', function($join) use ($c) {
                    $join->on('students.student_id', '=', 'enrollments.student_id')
                         ->where('enrollments.course_id', '=', $c->course_id);
                })
                ->where(function($query) use ($coursePrograms, $courseYearStr) {
                    $query->whereNotNull('enrollments.enrollment_id');
                    if (!empty($coursePrograms) && $courseYearStr) {
                        $query->orWhere(function($q) use ($coursePrograms, $courseYearStr) {
                            $q->whereIn('students.program_id', $coursePrograms)
                              ->where('users.academic_year', $courseYearStr);
                        });
                    }
                })
                ->select('students.student_id', 'users.full_name', 'users.academic_year', 'programs.name as branch_name')
                ->distinct()
                ->get();

            foreach ($students as $student) {
                if (!isset($allStudents[$student->student_id])) {
                    $allStudents[$student->student_id] = [
                        'name' => $student->full_name,
                        'branch' => $student->branch_name ?? 'عام',
                        'year' => $student->academic_year,
                    ];
                }
            }
        }

        // Now process sessions for matrix
        foreach ($sessions as $session) {
            $attendances = DB::table('attendance')
                ->where('lesson_id', $session->lesson_id)
                ->get(['student_id', 'status'])
                ->keyBy('student_id');

            foreach ($allStudents as $studentId => $studentInfo) {
                $att = $attendances->get($studentId);
                $statusRaw = $att ? $att->status : 'absent';
                
                $isToday = \Carbon\Carbon::parse($session->created_at)->isToday();
                if ($statusRaw === 'absent' && $isToday) {
                    $statusRaw = 'pending';
                }
                
                $matrix[$studentId][$session->lesson_id] = $statusRaw;
            }
        }

        $exportType = $request->input('export_type', 'excel');
        $dateStr = now()->format('Y-m-d_H-i-s');

        // ── Build the HTML table used by both formats ──
        $htmlStyle = '
        <style>
            body { direction: rtl; font-family: "Segoe UI", Tahoma, sans-serif; margin: 0; padding: 20px; }
            h2 { text-align: center; margin-bottom: 5px; }
            .meta { text-align: center; color: #666; font-size: 13px; margin-bottom: 15px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background: #2d3748; color: #fff; font-weight: bold; padding: 10px 8px; border: 1px solid #4a5568; font-size: 13px; }
            td { border: 1px solid #ddd; padding: 8px; text-align: right; font-size: 12px; }
            tr:nth-child(even) { background: #f7fafc; }
            .present { color: #166534; font-weight: bold; }
            .absent  { color: #b91c1c; font-weight: bold; }
            .pending { color: #d97706; font-weight: bold; }
            .late    { color: #7c3aed; font-weight: bold; }
            .summary { margin-top: 10px; font-size: 13px; background: #f0f4f8; padding: 10px; border-radius: 8px; }
            @media print { body { margin: 0; } .no-print { display: none; } }
        </style>';

        // Sort students
        uasort($allStudents, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        // Build session columns
        $sessionHeaders = '';
        foreach ($sessions as $session) {
            $d = \Carbon\Carbon::parse($session->created_at)->format('m/d H:i');
            $sessionHeaders .= "<th>{$session->course_title}<br><small>{$d}</small></th>";
        }

        $rows = '';
        $num = 0;
        foreach ($allStudents as $studentId => $info) {
            $num++;
            $rows .= "<tr><td>{$num}</td><td>{$info['name']}</td><td>{$info['branch']}</td><td>{$info['year']}</td>";
            $presentCount = 0;
            $totalCount = 0;
            foreach ($sessions as $session) {
                $status = $matrix[$studentId][$session->lesson_id] ?? '-';
                $totalCount++;
                $class = '';
                $text = '-';
                if ($status === 'present') { $class = 'present'; $text = 'حاضر'; $presentCount++; }
                elseif ($status === 'absent') { $class = 'absent'; $text = 'غائب'; }
                elseif ($status === 'pending') { $class = 'pending'; $text = 'قيد الانتظار'; }
                elseif ($status === 'late') { $class = 'late'; $text = 'متأخر'; $presentCount++; }
                $rows .= "<td class=\"{$class}\">{$text}</td>";
            }
            $percentage = $totalCount > 0 ? round(($presentCount / $totalCount) * 100) : 0;
            $rows .= "<td class=\"percentage-col\"><strong>{$percentage}%</strong></td></tr>";
        }

        // Summary
        $totalStudents = count($allStudents);
        $totalSessions = count($sessions);

        $htmlBody = "
        <h2>تقرير الحضور والغياب</h2>
        <div class=\"meta\">تاريخ التصدير: " . now()->format('Y-m-d H:i') . " — عدد الطلاب: {$totalStudents} — عدد الجلسات: {$totalSessions}</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الطالب</th>
                    <th>الفرع</th>
                    <th>السنة</th>
                    {$sessionHeaders}
                    <th>نسبة الحضور</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>";

        if ($exportType === 'pdf') {
            // Return printable HTML page with auto-print dialog
            // Return beautifully styled printable HTML page with auto-print dialog
            $html = "
            <!DOCTYPE html>
            <html dir=\"rtl\" lang=\"ar\">
            <head>
                <meta charset=\"UTF-8\">
                <title>تقرير الحضور</title>
                <!-- Include Google Fonts for better typography -->
                <link href=\"https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;800&display=swap\" rel=\"stylesheet\">
                <style>
                    :root {
                        --primary: #facc15; /* Edu-Bridge yellow */
                        --primary-dark: #eab308;
                        --text-main: #1f2937;
                        --text-muted: #4b5563;
                        --border: #e5e7eb;
                        --bg-light: #f9fafb;
                    }
                    body { 
                        direction: rtl; 
                        font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif; 
                        margin: 0; 
                        padding: 0;
                        color: var(--text-main);
                        background: #f3f4f6;
                    }
                    .report-container {
                        max-width: 297mm; /* A4 landscape */
                        margin: 20px auto;
                        background: #fff;
                        padding: 40px;
                        border-radius: 8px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                        border-top: 5px solid var(--primary);
                    }
                    .report-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-end;
                        border-bottom: 1px solid var(--border);
                        padding-bottom: 15px;
                        margin-bottom: 25px;
                    }
                    .header-title { margin: 0; }
                    .header-title h2 { 
                        margin: 0 0 5px 0; 
                        color: #1a1a1a; 
                        font-weight: 800;
                        font-size: 24px;
                    }
                    .header-title p { 
                        margin: 0; 
                        color: var(--text-muted); 
                        font-size: 14px;
                        font-weight: 600;
                    }
                    .header-meta {
                        text-align: left;
                    }
                    .header-meta div {
                        font-size: 13px;
                        color: var(--text-muted);
                        margin-bottom: 4px;
                    }
                    .header-meta span { font-weight: 800; color: #1a1a1a; }
                    
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-top: 10px;
                    }
                    th, td { 
                        padding: 10px 8px; 
                        text-align: right; 
                        font-size: 13px;
                        border-bottom: 1px solid var(--border);
                    }
                    th { 
                        background: var(--bg-light); 
                        color: #1a1a1a; 
                        font-weight: 800; 
                        border-bottom: 2px solid var(--border);
                    }
                    th small { color: var(--text-muted); font-weight: 600; display: block; margin-top: 2px; }
                    
                    /* Simple Badges */
                    .present, .absent, .pending, .late {
                        display: inline-block;
                        padding: 3px 8px;
                        border-radius: 4px;
                        font-size: 11px;
                        font-weight: 800;
                        text-align: center;
                        min-width: 50px;
                    }
                    .present { background: #dcfce7; color: #166534; }
                    .absent  { background: #fee2e2; color: #991b1b; }
                    .pending { background: #fef9c3; color: #854d0e; }
                    .late    { background: #f3e8ff; color: #6b21a8; }
                    
                    .percentage-col {
                        font-weight: 800;
                        font-size: 14px;
                        text-align: center !important;
                    }
                    
                    @media print { 
                        body { background: #fff; }
                        .report-container { box-shadow: none; margin: 0; padding: 0; max-width: 100%; border-top: 3px solid var(--primary); }
                        .no-print { display: none !important; }
                        @page { size: A4 landscape; margin: 15mm; }
                    }
                    
                    .no-print { 
                        padding: 15px; 
                        text-align: center; 
                        background: #fff;
                        border-bottom: 1px solid var(--border);
                        margin-bottom: 20px;
                    }
                    .no-print button { 
                        padding: 10px 25px; 
                        background: var(--primary); 
                        color: #1a1a1a; 
                        border: none; 
                        border-radius: 6px; 
                        font-size: 14px; 
                        font-weight: 800;
                        cursor: pointer; 
                        font-family: inherit; 
                        margin: 0 10px;
                        transition: background 0.2s;
                    }
                    .no-print button:hover { background: var(--primary-dark); }
                    .no-print button.btn-secondary { background: #e5e7eb; color: #1f2937; }
                    .no-print button.btn-secondary:hover { background: #d1d5db; }
                </style>
            </head>
            <body>
                <div class=\"no-print\">
                    <button onclick=\"window.print()\">🖨️ طباعة / حفظ كـ PDF</button>
                    <button class=\"btn-secondary\" onclick=\"window.close()\">إغلاق</button>
                </div>
                
                <div class=\"report-container\">
                    <div class=\"report-header\">
                        <div class=\"header-title\">
                            <h2>تقرير الحضور والغياب المجمع</h2>
                            <p>نظام Edu-Bridge لإدارة الحضور</p>
                        </div>
                        <div class=\"header-meta\">
                            <div>تاريخ التصدير: <span>" . now()->format('Y-m-d H:i') . "</span></div>
                            <div>إجمالي الطلاب: <span>{$totalStudents}</span></div>
                            <div>إجمالي الجلسات: <span>{$totalSessions}</span></div>
                        </div>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الطالب</th>
                                <th>الفرع</th>
                                <th>السنة</th>
                                {$sessionHeaders}
                                <th style=\"text-align: center;\">نسبة الحضور</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>
                </div>
                
                <script>
                    setTimeout(function() { window.print(); }, 800);
                </script>
            </body>
            </html>";

            return response($html)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        // ── Excel Export (HTML table based - opens natively in Excel) ──
        $fileName = "filtered_attendance_report_{$dateStr}.xls";

        $excelHtml = "
        <html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">
        <head>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
            {$htmlStyle}
        </head>
        <body>
            {$htmlBody}
        </body>
        </html>";

        return response("\xEF\xBB\xBF" . $excelHtml)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"")
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
    }

    public function getAbsentees($sessionId)
    {
        $session = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->where('attendance_sessions.id', $sessionId)
            ->select('attendance_sessions.*', 'lessons.course_id')
            ->first();

        if (!$session) return response()->json([]);

        // Course details
        $course = DB::table('courses')->where('course_id', $session->course_id)->first();
        $coursePrograms = DB::table('course_program')->where('course_id', $session->course_id)->pluck('program_id')->toArray();

        // Convert course year to string
        $yearMap = [1 => 'السنة الأولى', 2 => 'السنة الثانية', 3 => 'السنة الثالثة', 4 => 'السنة الرابعة', 5 => 'السنة الخامسة'];
        $courseYearStr = $yearMap[$course->year] ?? null;

        // Get students either explicitly enrolled OR matching the program and year
        $studentsQuery = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('enrollments', function($join) use ($session) {
                $join->on('students.student_id', '=', 'enrollments.student_id')
                     ->where('enrollments.course_id', '=', $session->course_id);
            })
            ->where(function($query) use ($coursePrograms, $courseYearStr) {
                $query->whereNotNull('enrollments.enrollment_id'); // explicitly enrolled
                if (!empty($coursePrograms) && $courseYearStr) {
                    $query->orWhere(function($q) use ($coursePrograms, $courseYearStr) {
                        $q->whereIn('students.program_id', $coursePrograms)
                          ->where('users.academic_year', $courseYearStr);
                    });
                }
            })
            ->select('students.student_id', 'users.full_name', 'users.academic_year as level')
            ->distinct()
            ->get();

        $attendances = DB::table('attendance')
            ->where('lesson_id', $session->lesson_id)
            ->where('status', 'present')
            ->pluck('student_id')->toArray();

        $absentees = [];
        foreach ($studentsQuery as $student) {
            if (!in_array($student->student_id, $attendances)) {
                $absentees[] = $student;
            }
        }

        return response()->json($absentees);
    }

    // ────────────────────────────────────────────────────────────
    //  ASSIGNMENTS
    // ────────────────────────────────────────────────────────────

    public function assignments()
    {
        $teacher = $this->getTeacher();

        $courseIds = DB::table('course_teachers')
            ->where('teacher_id', $teacher->teacher_id)
            ->pluck('course_id');

        $assignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->whereIn('assignments.course_id', $courseIds)
            ->select(
                'assignments.*',
                'courses.title as course_title',
                DB::raw('(SELECT COUNT(*) FROM assignment_submissions WHERE assignment_submissions.assignment_id = assignments.assignment_id) as submissions_count'),
                DB::raw('(SELECT COUNT(*) FROM assignment_submissions WHERE assignment_submissions.assignment_id = assignments.assignment_id AND assignment_submissions.grade IS NOT NULL) as graded_count')
            )
            ->orderByDesc('assignments.created_at')
            ->get();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();

        $assignmentIds = $assignments->pluck('assignment_id');

        $allSubmissions = DB::table('assignment_submissions')
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.assignment_id')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->join('students', 'assignment_submissions.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->whereIn('assignments.assignment_id', $assignmentIds)
            ->select(
                'assignment_submissions.*',
                'users.full_name as student_name',
                'assignments.title as assignment_title',
                'assignments.max_points',
                'courses.title as course_title'
            )
            ->orderByDesc('assignment_submissions.submitted_at')
            ->get();

        return view('teacher.assignments', compact('assignments', 'courses', 'allSubmissions'));
    }

    public function storeAssignment(Request $request)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'due_date'    => 'required|date|after_or_equal:today',
            'max_points'  => 'required|integer|min:1',
            'attachment'  => 'nullable|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv,webm,pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip',
        ], [
            'due_date.after_or_equal' => 'تاريخ التسليم يجب أن يكون اليوم أو في المستقبل.',
            'attachment.max'   => 'حجم الملف يجب ألا يتجاوز 50 ميجابايت.',
            'attachment.mimes' => 'نوع الملف غير مدعوم.',
        ]);

        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file     = $request->file('attachment');
            $fileName = $file->getClientOriginalName();
            $mime     = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $fileType = 'image';
                $folder   = 'assignments/images';
            } elseif (str_starts_with($mime, 'video/')) {
                $fileType = 'video';
                $folder   = 'assignments/videos';
            } else {
                $fileType = 'document';
                $folder   = 'assignments/documents';
            }

            $filePath = $file->store($folder, 'public');
        }

        $teacher = $this->getTeacher();

        $assignmentId = DB::table('assignments')->insertGetId([
            'course_id'       => $request->course_id,
            'teacher_id'      => $teacher->teacher_id,
            'title'           => $request->title,
            'description'     => $request->description,
            'due_date'        => $request->due_date,
            'max_points'      => $request->max_points,
            'file_path'       => $filePath,
            'attachment_path' => $filePath,
            'file_name'       => $fileName,
            'file_type'       => $fileType,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // ── إشعار الطلاب المسجلين في المادة ─────────────────────────
        $course       = DB::table('courses')->where('course_id', $request->course_id)->first();
        $teacherUser  = DB::table('users')->where('user_id', $teacher->user_id)->first();
        $courseName   = $course->title ?? $course->name ?? 'المادة';
        $fcmTitle     = 'واجب جديد — ' . $courseName;
        $fcmBody      = 'رفع المعلم ' . ($teacherUser->full_name ?? '') . ' واجباً جديداً: ' . $request->title;

        $studentUserIds = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->where('enrollments.course_id', $request->course_id)
            ->where('enrollments.status', 'active')
            ->pluck('students.user_id');

        $now = now();
        $notifRows = $studentUserIds->map(fn($uid) => [
            'user_id'    => $uid,
            'sender_id'  => $teacher->user_id,
            'title'      => $fcmTitle,
            'message'    => $fcmBody,
            'type'       => 'assignment',
            'category'   => 'academic',
            'related_id' => $assignmentId,
            'is_read'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($notifRows)) {
            DB::table('notifications')->insert($notifRows);
            foreach ($studentUserIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, $fcmTitle, $fcmBody, [
                    'type' => 'assignment', 'related_id' => (string) $assignmentId,
                ]);
            }
        }

        return redirect()->back()->with('success', 'تم إضافة الواجب بنجاح!');
    }

    public function deleteAssignment($id)
    {
        $teacher = $this->getTeacher();
        
        $assignment = DB::table('assignments')
            ->where('assignment_id', $id)
            ->first();

        if (!$assignment) {
            return redirect()->back()->with('error', 'الواجب غير موجود.');
        }

        // التأكد من أن المادة تتبع للمعلم
        $assigned = DB::table('course_teachers')
            ->where('teacher_id', $teacher->teacher_id)
            ->where('course_id', $assignment->course_id)
            ->exists();

        if (!$assigned) {
            return redirect()->back()->with('error', 'غير مصرح لك بحذف هذا الواجب.');
        }

        DB::transaction(function () use ($id, $assignment) {
            // حذف ملفات تسليمات الطلاب
            $submissions = DB::table('assignment_submissions')
                ->where('assignment_id', $id)
                ->get();

            foreach ($submissions as $sub) {
                if ($sub->file_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($sub->file_path);
                }
            }

            // حذف تسليمات الطلاب من قاعدة البيانات
            DB::table('assignment_submissions')
                ->where('assignment_id', $id)
                ->delete();

            // حذف الملف المرفق بالواجب إن وُجد
            if ($assignment->file_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($assignment->file_path);
            }
            if (isset($assignment->attachment_path) && $assignment->attachment_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($assignment->attachment_path);
            }

            // حذف الواجب نفسه
            DB::table('assignments')
                ->where('assignment_id', $id)
                ->delete();
        });

        return redirect()->back()->with('success', 'تم حذف الواجب بنجاح.');
    }

    // عرض تفاصيل التسليمات لواجب معين
    public function assignmentSubmissions($assignmentId)
    {
        $teacher  = $this->getTeacher();
        $assignment = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->where('assignments.assignment_id', $assignmentId)
            ->select('assignments.*', 'courses.title as course_title')
            ->first();

        $submissions = DB::table('assignment_submissions')
            ->join('students', 'assignment_submissions.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('assignment_submissions.assignment_id', $assignmentId)
            ->select('assignment_submissions.*', 'users.full_name as student_name')
            ->orderByDesc('assignment_submissions.submitted_at')
            ->get();

        return view('teacher.submissions', compact('assignment', 'submissions'));
    }

    public function gradeSubmission(Request $request, $submissionId)
    {
        $request->validate([
            'grade'    => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        DB::table('assignment_submissions')
            ->where('submission_id', $submissionId)
            ->update([
                'grade'      => $request->grade,
                'feedback'   => $request->feedback,
                'updated_at' => now(),
            ]);

        // ── إشعار الطالب بتصحيح واجبه ───────────────────────────────
        $submission = DB::table('assignment_submissions')
            ->join('students', 'assignment_submissions.student_id', '=', 'students.student_id')
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.assignment_id')
            ->where('assignment_submissions.submission_id', $submissionId)
            ->select('students.user_id', 'assignments.title as assignment_title', 'assignments.max_points')
            ->first();

        if ($submission) {
            $notifTitle = 'تم تصحيح واجبك';
            $notifMsg   = 'صحّح المعلم واجب "' . $submission->assignment_title . '" — علامتك: ' . $request->grade . '/' . ($submission->max_points ?? 100);
            DB::table('notifications')->insert([
                'user_id'    => $submission->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'assignment',
                'category'   => 'academic',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser($submission->user_id, $notifTitle, $notifMsg, ['type' => 'assignment']);
        }

        return redirect()->back()->with('success', 'تم حفظ التصحيح بنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  LECTURES
    // ────────────────────────────────────────────────────────────

    public function lectures()
    {
        $teacher = $this->getTeacher();

        $courseIds = DB::table('course_teachers')
            ->where('teacher_id', $teacher->teacher_id)
            ->pluck('course_id');

        $lectures = DB::table('lessons')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->whereIn('lessons.course_id', $courseIds)
            ->where(function($q) {
                $q->where('lessons.type', '!=', 'session')
                  ->orWhereNull('lessons.type');
            })
            ->where('lessons.title', 'not like', '%حضور%')
            ->where('lessons.title', 'not like', '%غياب%')
            ->where(function($query) {
                $query->whereNull('lessons.content_url')
                      ->orWhere('lessons.content_url', 'not like', '%attendance%');
            })
            ->select('lessons.*', 'courses.title as course_title')
            ->orderByDesc('lessons.created_at')
            ->get();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();

        return view('teacher.lectures', compact('lectures', 'courses'));
    }

    public function storeLecture(Request $request)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'attachment'  => 'nullable|file|max:2097152|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv,webm,pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip',
        ], [
            'attachment.max'   => 'حجم الملف يجب ألا يتجاوز 2 جيجابايت.',
            'attachment.mimes' => 'نوع الملف غير مدعوم.',
        ]);

        $teacher = $this->getTeacher();

        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file     = $request->file('attachment');
            $fileName = $file->getClientOriginalName();
            $mime     = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $fileType = 'image';
                $folder   = 'lectures/images';
            } elseif (str_starts_with($mime, 'video/')) {
                $fileType = 'video';
                $folder   = 'lectures/videos';
            } else {
                $fileType = 'document';
                $folder   = 'lectures/documents';
            }

            $filePath = $file->store($folder, 'public');
        }

        DB::table('lessons')->insert([
            'course_id'   => $request->course_id,
            'teacher_id'  => $teacher->teacher_id,
            'title'       => $request->title,
            'description' => $request->description,
            'file_path'   => $filePath,
            'file_name'   => $fileName,
            'file_type'   => $fileType,
            'type'        => 'lecture',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->back()->with('success', 'تمت إضافة المحاضرة بنجاح!');
    }

    public function updateLecture(Request $request, $id)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::table('lessons')->where('lesson_id', $id)->update([
            'course_id'   => $request->course_id,
            'title'       => $request->title,
            'description' => $request->description,
            'updated_at'  => now(),
        ]);

        return redirect()->back()->with('success', 'تم تحديث المحاضرة بنجاح!');
    }

    public function deleteLecture($id)
    {
        // حذف الملف المرفق إن وُجد
        $lesson = DB::table('lessons')->where('lesson_id', $id)->first();
        if ($lesson && $lesson->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($lesson->file_path);
        }
        DB::table('lessons')->where('lesson_id', $id)->delete();
        return redirect()->back()->with('success', 'تم حذف المحاضرة.');
    }

    // ────────────────────────────────────────────────────────────
    //  ANNOUNCEMENTS
    // ────────────────────────────────────────────────────────────

    public function createAnnouncement()
    {
        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $this->getTeacher()->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();
        return view('teacher.announcements_create', compact('courses'));
    }

    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image'   => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        \App\Models\Announcement::create([
            'user_id' => Auth::id(),
            'title'   => $request->title,
            'content' => $request->content,
            'image'   => $imagePath,
            'type'    => 'general',
        ]);

        return redirect()->route('teacher.dashboard')->with('success', 'تم نشر الإعلان بنجاح!');
    }

    public function editAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())->firstOrFail();
        return view('teacher.announcements_edit', compact('announcement'));
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())->firstOrFail();

        $request->validate(['title' => 'required|string|max:255', 'content' => 'required|string']);

        $updates = ['title' => $request->title, 'content' => $request->content, 'updated_at' => now()];

        if ($request->hasFile('image')) {
            if ($announcement->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
            $updates['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($updates);
        return redirect()->route('teacher.dashboard')->with('success', 'تم تحديث الإعلان!');
    }

    public function deleteAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())->firstOrFail();
        if ($announcement->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
        $announcement->delete();
        return redirect()->route('teacher.dashboard')->with('success', 'تم حذف الإعلان.');
    }

    // ────────────────────────────────────────────────────────────
    //  MESSAGES
    // ────────────────────────────────────────────────────────────

    public function messages()
    {
        $currentUserId = Auth::id();
        // Exclude parents (role_id = 4) from the list of users the teacher can start a chat with
        $allUsers = User::where('user_id', '!=', $currentUserId)
                        ->where('role_id', '!=', 4)
                        ->get();
        return view('teacher.messages', compact('allUsers'));
    }

    public function getContacts()
    {
        $currentUserId = Auth::id();

        // Get users we have conversations with
        $conversations = \App\Models\Message::where('sender_id', $currentUserId)
            ->orWhere('receiver_id', $currentUserId)
            ->latest()
            ->get()
            ->map(function ($msg) use ($currentUserId) {
                return ($msg->sender_id == $currentUserId) ? $msg->receiver_id : $msg->sender_id;
            })
            ->unique()
            ->values();

        $contactsRaw = User::whereIn('user_id', $conversations)->get();

        $contacts = [];
        foreach ($contactsRaw as $c) {
            // Get unread count
            $unread = \App\Models\Message::where('sender_id', $c->user_id)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->count();

            // Get last message
            $lastMsg = \App\Models\Message::where(function ($q) use ($currentUserId, $c) {
                    $q->where('sender_id', $currentUserId)->where('receiver_id', $c->user_id);
                })
                ->orWhere(function ($q) use ($currentUserId, $c) {
                    $q->where('sender_id', $c->user_id)->where('receiver_id', $currentUserId);
                })
                ->latest()
                ->first();

            $contacts[] = [
                'id' => $c->user_id,
                'name' => $c->full_name,
                'role' => $c->role,
                'image' => $c->profile_picture ? asset('storage/' . $c->profile_picture) : null,
                'unread' => $unread,
                'last_message' => $lastMsg ? $lastMsg->message : '',
                'time' => $lastMsg ? $lastMsg->created_at->diffForHumans() : '',
                'updated_at' => $lastMsg ? $lastMsg->created_at : now()
            ];
        }

        // Sort by last message time
        usort($contacts, function($a, $b) {
            return $b['updated_at'] <=> $a['updated_at'];
        });

        return response()->json([
            'status' => 'success',
            'data' => $contacts
        ]);
    }

    public function getConversation($userId)
    {
        $currentUserId = Auth::id();
        $messages = \App\Models\Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)->where('receiver_id', $userId);
            })
            ->orWhere(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // تحديد الرسائل كمقروءة
        \App\Models\Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function searchMessages(Request $request, $userId)
    {
        $currentUserId = Auth::id();
        $query = $request->query('q');

        $messages = \App\Models\Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where(function($q2) use ($currentUserId, $userId) {
                    $q2->where('sender_id', $currentUserId)->where('receiver_id', $userId);
                })
                ->orWhere(function($q2) use ($currentUserId, $userId) {
                    $q2->where('sender_id', $userId)->where('receiver_id', $currentUserId);
                });
            })
            ->where('message', 'LIKE', '%' . $query . '%')
            ->orderBy('created_at', 'desc') // For search we might want recent matches
            ->get();

        return response()->json(['status' => 'success', 'data' => $messages]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'message'     => 'required|string|max:2000',
            'attachment'  => 'nullable|file|max:51200', // max 50MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $folder = 'chat_attachments';
            
            // If it's a voice note, save in a specific subfolder maybe
            if ($request->message === '[Voice Note]' || strpos($file->getMimeType(), 'audio') !== false) {
                $folder = 'chat_voice_notes';
            }
            
            $attachmentPath = $file->store($folder, 'public');
            // Usually we prepend the storage URL for easy frontend access
            $attachmentPath = asset('storage/' . $attachmentPath);
        }

        $message = \App\Models\Message::create([
            'sender_id'   => Auth::user()->user_id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'attachment'  => $attachmentPath,
            'is_read'     => false,
        ]);

        // إضافة إشعار للمستلم
        DB::table('notifications')->insert([
            'user_id' => $request->receiver_id,
            'title'   => 'رسالة جديدة',
            'message' => 'لقد تلقيت رسالة جديدة من ' . Auth::user()->full_name,
            'type'    => 'message',
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser(
            $request->receiver_id,
            'رسالة جديدة',
            'لقد تلقيت رسالة جديدة من ' . Auth::user()->full_name,
            ['type' => 'message']
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', 'تم إرسال الرسالة بنجاح!');
    }

    public function updateMessage(Request $request, $id)
    {
        $message = \App\Models\Message::findOrFail($id);
        
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        if ($message->attachment || $message->message === '[Voice Note]') {
            return response()->json(['status' => 'error', 'message' => 'لا يمكن تعديل المرفقات'], 400);
        }

        $request->validate(['message' => 'required|string|max:2000']);
        $message->update(['message' => $request->message]);

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    public function deleteMessage($id)
    {
        $message = \App\Models\Message::findOrFail($id);

        if ($message->sender_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        if ($message->attachment) {
            // Remove full URL part if it exists
            $path = str_replace(asset('storage/'), '', $message->attachment);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        $message->delete();
        return response()->json(['status' => 'success']);
    }

    // ────────────────────────────────────────────────────────────
    //  NOTIFICATIONS
    // ────────────────────────────────────────────────────────────

    public function notifications()
    {
        $notifications = DB::table('notifications')
            ->where('user_id', Auth::user()->user_id)
            ->orderByDesc('created_at')
            ->get();

        return view('teacher.notifications', compact('notifications'));
    }

    // ────────────────────────────────────────────────────────────
    //  PROFILE
    // ────────────────────────────────────────────────────────────

    public function profile()
    {
        $teacher = $this->getTeacher();
        $user    = Auth::user();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();

        $courseIds = $courses->pluck('course_id');
        $totalStudents = DB::table('enrollments')
            ->whereIn('course_id', $courseIds)
            ->distinct('student_id')
            ->count('student_id');

        return view('teacher.profile', compact('teacher', 'user', 'courses', 'totalStudents'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
        ]);

        DB::table('users')
            ->where('user_id', Auth::user()->user_id)
            ->update([
                'full_name'  => $request->full_name,
                'phone'      => $request->phone,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'تم تحديث الملف الشخصي بنجاح!');
    }

    public function sendOTP(Request $request)
    {
        $request->validate([
            'full_name'        => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255|unique:users,email,' . Auth::id() . ',user_id',
            'current_password' => 'nullable|string',
            'new_password'     => 'nullable|string|min:6',
            'telegram_chat_id' => 'nullable|string',
        ]);

        $user = Auth::user();

        // If changing password, verify current password first
        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة.'
                ]);
            }
        }

        $otp = (string) rand(100000, 999999);
        
        $telegramService = new \App\Services\TelegramService();
        $telegramResult  = $telegramService->sendProfileOtpToUser($user, $otp, $request->input('telegram_chat_id'));

        if (!$telegramResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $telegramResult['message']
            ]);
        }

        session([
            'teacher_profile_otp' => $otp,
            'teacher_pending_profile_data' => $request->only(['full_name', 'phone', 'email', 'new_password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق (OTP) إلى حسابك في بوت تيليغرام بنجاح!'
        ]);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);

        if (session('teacher_profile_otp') == $request->otp) {
            $user = Auth::user();
            $data = session('teacher_pending_profile_data');

            $updates = ['updated_at' => now()];

            if (isset($data['full_name']) && $data['full_name']) {
                $updates['full_name'] = $data['full_name'];
            }

            if (isset($data['phone'])) {
                $updates['phone'] = $data['phone'];
            }

            if (isset($data['email']) && $data['email']) {
                $updates['email'] = $data['email'];
            }

            if (isset($data['new_password']) && $data['new_password']) {
                $updates['password'] = Hash::make($data['new_password']);
            }

            DB::table('users')
                ->where('user_id', $user->user_id)
                ->update($updates);

            session()->forget(['teacher_profile_otp', 'teacher_pending_profile_data']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'رمز التحقق غير صحيح، يرجى المحاولة مرة أخرى.'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'password'   => Hash::make($request->new_password),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'تم تغيير كلمة المرور بنجاح!');
    }
    // ────────────────────────────────────────────────────────────
    //  ADVISOR TOOLS (أدوات المربي)
    // ────────────────────────────────────────────────────────────

    public function advisorTools()
    {
        $teacher = $this->getTeacher();
        
        $students = [];
        $isAdvisor = !empty($teacher->advisor_branch) && !empty($teacher->advisor_year);
        
        // Create a dummy collection so the view doesn't break if it expects $advisorCourses
        $advisorCourses = collect();
        
        if ($isAdvisor) {
            $courseTitle = "{$teacher->advisor_branch} - {$teacher->advisor_year}" . ($teacher->advisor_section ? " - {$teacher->advisor_section}" : "");
            
            // Check if this dummy course exists, if not create it to satisfy foreign key constraints
            $dummyCourse = DB::table('courses')->where('title', 'سجل المربي: ' . $courseTitle)->first();
            if (!$dummyCourse) {
                $dummyCourseId = DB::table('courses')->insertGetId([
                    'title' => 'سجل المربي: ' . $courseTitle,
                    'level' => $teacher->advisor_year,
                    'hours' => 0,
                    'year' => 1,
                    'semester_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $dummyCourseId = $dummyCourse->course_id;
            }

            $advisorCourses->push((object)[
                'course_id' => $dummyCourseId, 
                'title' => $courseTitle
            ]);
            
            $query = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('users.department', $teacher->advisor_branch)
                ->where('students.level', $teacher->advisor_year);
                
            // If section is implemented in students, we
            $students = $query->select('students.student_id', 'users.full_name', 'users.university_id')
                ->distinct()
                ->get();
        }

        return view('teacher.advisor', compact('advisorCourses', 'students'));
    }

    public function storeAdvisorReport(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'behavioral_notes' => 'required|string|max:2000'
        ]);

        $teacher = $this->getTeacher();

        // 1. Create a simulated report request to tie it to
        $requestId = DB::table('report_requests')->insertGetId([
            'student_id' => $request->student_id,
            'teacher_id' => $teacher->teacher_id,
            'report_type' => 'behavioral',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Insert performance report
        DB::table('performance_reports')->insert([
            'report_request_id' => $requestId,
            'student_id'        => $request->student_id,
            'report_type'       => 'behavioral',
            'recommendations'   => $request->behavioral_notes,
            'generated_at'      => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // 3. Notify parents
        $studentName = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $request->student_id)
            ->value('users.full_name') ?? 'الطالب';

        $parentRows = DB::table('parent_students')
            ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
            ->where('parent_students.student_id', $request->student_id)
            ->pluck('parents.user_id');

        $notifTitle = 'تقرير سلوكي جديد';
        $notifBody  = 'تم إضافة تقرير سلوكي جديد عن ابنك/ابنتك ' . $studentName . ' من قبل مربي الدورة.';

        foreach ($parentRows as $parentUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $parentUserId,
                'sender_id'  => auth()->id(),
                'title'      => $notifTitle,
                'message'    => $notifBody,
                'type'       => 'report',
                'related_id' => $requestId,
                'category'   => 'academic',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser($parentUserId, $notifTitle, $notifBody, [
                'type'       => 'behavioral_report',
                'student_id' => (string) $request->student_id,
            ]);
        }

        return redirect()->back()->with('success', 'تم رفع التقرير السلوكي وإشعار ولي الأمر بنجاح!');
    }

    public function storeAdvisorAttendance(Request $request)
    {
        $teacher = $this->getTeacher();
        
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'attendance' => 'required|array',
            'date' => 'required|date'
        ]);

        $courseId = $request->input('course_id');
        $date = $request->input('date');
        $attendances = $request->input('attendance');

        $isAdvisor = !empty($teacher->advisor_branch) && !empty($teacher->advisor_year);

        if (!$isAdvisor) {
            return back()->with('error', 'ليس لديك صلاحية مربي لهذه الدورة.');
        }

        $lesson = DB::table('lessons')
            ->where('course_id', $courseId)
            ->where('title', 'الحضور اليومي للقاعة')
            ->first();

        if (!$lesson) {
            $lessonId = DB::table('lessons')->insertGetId([
                'course_id' => $courseId,
                'title' => 'الحضور اليومي للقاعة',
                'description' => 'سجل خاص بتفقد المربي',
                'content_url' => 'يومي',
                'teacher_id' => $teacher->teacher_id,
                'department_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $lessonId = $lesson->lesson_id;
        }

        $sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $lessonId,
            'qr_token' => 'DAILY_' . $courseId . '_' . uniqid(),
            'expires_at' => now()->addHours(24),
            'is_active' => false,
            'created_at' => $date . ' 00:00:00',
            'updated_at' => now(),
        ]);

        $attendanceRecords = [];
        foreach ($attendances as $studentId => $status) {
            $attendanceRecords[] = [
                'session_id' => $sessionId,
                'student_id' => $studentId,
                'status' => $status,
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($attendanceRecords)) {
            DB::table('attendance')->insert($attendanceRecords);
        }

        return back()->with('success', 'تم حفظ الحضور اليومي للقاعة بنجاح.');
    }

    // ===== التقارير =====

    public function reports()
    {
        $teacher = DB::table('teachers')
            ->where('user_id', auth()->id())
            ->first();

        if (!$teacher) {
            return view('teacher.reports', ['requests' => collect()]);
        }

        $requests = DB::table('report_requests')
            ->where('report_requests.teacher_id', $teacher->teacher_id)
            ->join('students', 'report_requests.student_id', '=', 'students.student_id')
            ->join('users as su', 'students.user_id', '=', 'su.user_id')
            ->leftJoin('users as requesters', 'report_requests.head_id', '=', 'requesters.user_id')
            ->leftJoin('performance_reports', 'performance_reports.report_request_id', '=', 'report_requests.id')
            ->select(
                'report_requests.*',
                'su.full_name as student_name',
                'students.student_code',
                'requesters.full_name as requester_name',
                'requesters.role_id as requester_role_id',
                'performance_reports.attendance_rate',
                'performance_reports.average_grade',
                'performance_reports.recommendations as submitted_notes',
                'performance_reports.generated_at'
            )
            ->orderByDesc('report_requests.created_at')
            ->get();

        return view('teacher.reports', compact('requests'));
    }

    public function submitReport(Request $request, $id)
    {
        $request->validate([
            'behavioral_notes' => 'nullable|string|max:2000',
        ]);

        $reportRequest = DB::table('report_requests')->where('id', $id)->firstOrFail();
        $studentId     = $reportRequest->student_id;
        $isBehavioral  = $reportRequest->report_type === 'behavioral';

        // ===== حساب البيانات الأكاديمية الحقيقية =====
        $attendanceRate = null;
        $avgGrade       = null;
        $recommendations = $request->behavioral_notes ?? '';

        if (!$isBehavioral) {
            // نسبة الحضور الفعلية
            $totalSessions   = DB::table('attendance')->where('student_id', $studentId)->count();
            $presentSessions = DB::table('attendance')->where('student_id', $studentId)->where('status', 'present')->count();
            $attendanceRate  = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100, 1) : 0;

            // المعدل من الامتحانات
            $avgGrade = DB::table('grades')->where('student_id', $studentId)->avg('score');

            // إذا ما في درجات امتحان، نأخذ من الواجبات
            if ($avgGrade === null) {
                $avgGrade = DB::table('assignment_submissions')
                    ->where('student_id', $studentId)
                    ->whereNotNull('grade')
                    ->avg('grade');
            }

            $avgGrade = $avgGrade !== null ? round($avgGrade, 1) : null;

            // توليد التوصية تلقائياً
            $attendancePart = '';
            if ($attendanceRate >= 90)      $attendancePart = 'نسبة الحضور ممتازة (' . $attendanceRate . '%)';
            elseif ($attendanceRate >= 75)  $attendancePart = 'نسبة الحضور جيدة (' . $attendanceRate . '%)';
            elseif ($attendanceRate > 0)    $attendancePart = 'نسبة الحضور تحتاج تحسيناً (' . $attendanceRate . '%)';

            $gradePart = '';
            if ($avgGrade !== null) {
                if ($avgGrade >= 85)      $gradePart = 'مستوى أكاديمي ممتاز (المعدل: ' . $avgGrade . ')';
                elseif ($avgGrade >= 70)  $gradePart = 'مستوى أكاديمي جيد (المعدل: ' . $avgGrade . ')';
                elseif ($avgGrade >= 60)  $gradePart = 'مستوى أكاديمي مقبول (المعدل: ' . $avgGrade . ')';
                else                      $gradePart = 'يحتاج دعماً أكاديمياً (المعدل: ' . $avgGrade . ')';
            }

            $parts = array_filter([$attendancePart, $gradePart]);
            $recommendations = $parts ? implode(' — ', $parts) : 'لا توجد بيانات كافية لتوليد توصية.';
        }

        // حفظ التقرير
        DB::table('performance_reports')->insert([
            'report_request_id' => $id,
            'student_id'        => $studentId,
            'report_type'       => $reportRequest->report_type,
            'attendance_rate'   => $attendanceRate ?? 0,
            'average_grade'     => $avgGrade ?? 0,
            'recommendations'   => $recommendations,
            'generated_at'      => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('report_requests')->where('id', $id)->update([
            'status'     => 'completed',
            'updated_at' => now(),
        ]);

        $studentName = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $studentId)
            ->value('users.full_name') ?? 'الطالب';

        $notifTitle = $isBehavioral ? 'تقرير سلوكي جديد' : 'تقرير أكاديمي جديد';

        // ===== إشعار الأهل (تم تعطيله ليرسل فقط عندما يوافق رئيس القسم) =====
        /*
        $notifBody  = 'تم إرسال تقرير ' . ($isBehavioral ? 'سلوكي' : 'أكاديمي') . ' عن ابنك/ابنتك ' . $studentName;

        $parentRows = DB::table('parent_students')
            ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
            ->where('parent_students.student_id', $studentId)
            ->pluck('parents.user_id');

        foreach ($parentRows as $parentUserId) {
            // إشعار داخلي
            DB::table('notifications')->insert([
                'user_id'    => $parentUserId,
                'sender_id'  => auth()->id(),
                'title'      => $notifTitle,
                'message'    => $notifBody,
                'type'       => 'report',
                'related_id' => $id,
                'category'   => 'academic',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // FCM
            \App\Services\FcmService::sendToUser($parentUserId, $notifTitle, $notifBody, [
                'type'       => $reportRequest->report_type . '_report',
                'student_id' => (string) $studentId,
            ]);
        }
        */

        // إشعار صاحب الطلب (موظف الشؤون أو غيره) إذا كان موجوداً
        $notifiedUsers = [];
        if ($reportRequest->head_id) {
            $requesterId = $reportRequest->head_id;
            $notifiedUsers[] = $requesterId;
            
            DB::table('notifications')->insert([
                'user_id'    => $requesterId,
                'sender_id'  => auth()->id(),
                'title'      => 'تقرير جاهز',
                'message'    => 'تم رفع التقرير الخاص بالطالب ' . $studentName . ' بواسطة ' . auth()->user()->full_name,
                'type'       => 'report',
                'related_id' => $id,
                'category'   => 'academic',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser(
                $requesterId,
                'تقرير جاهز',
                'تم رفع التقرير الخاص بالطالب ' . $studentName . ' بواسطة ' . auth()->user()->full_name,
                ['type' => 'report', 'related_id' => (string)$id]
            );
        }

        // إشعار رئيس القسم الخاص بالطالب (في حال لم يكن هو صاحب الطلب)
        $studentData = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $studentId)
            ->select('users.department')
            ->first();

        if ($studentData && $studentData->department) {
            $headId = DB::table('users')
                ->where('role_id', 5) // HOD
                ->where('department', $studentData->department)
                ->value('user_id');

            if ($headId && !in_array($headId, $notifiedUsers)) {
                DB::table('notifications')->insert([
                    'user_id'    => $headId,
                    'sender_id'  => auth()->id(),
                    'title'      => $notifTitle,
                    'message'    => 'تم رفع تقرير عن الطالب ' . $studentName . ' بواسطة ' . auth()->user()->full_name,
                    'type'       => 'report',
                    'related_id' => $id,
                    'category'   => 'academic',
                    'is_read'    => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                \App\Services\FcmService::sendToUser(
                    $headId,
                    $notifTitle,
                    'تم رفع تقرير عن الطالب ' . $studentName . ' بواسطة ' . auth()->user()->full_name,
                    ['type' => 'report', 'related_id' => (string)$id]
                );
            }
        }

        $msg = $isBehavioral
            ? 'تم إرسال التقرير السلوكي بنجاح!'
            : 'تم توليد التقرير الأكاديمي بنجاح!';

        return redirect()->back()->with('success', $msg);
    }

    // ────────────────────────────────────────────────────────────
    //  GRADE EVENTS (الاختبارات والتقييمات)
    // ────────────────────────────────────────────────────────────

    public function gradeEvents()
    {
        $teacher = $this->getTeacher();

        $events = DB::table('grade_events')
            ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
            ->where('grade_events.teacher_id', $teacher->teacher_id)
            ->select('grade_events.*', 'courses.title as course_title')
            ->orderByDesc('grade_events.date')
            ->get();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();

        return view('teacher.grade_events', compact('events', 'courses'));
    }

    public function storeGradeEvent(Request $request)
    {
        $request->validate([
            'type'      => 'required|in:exam,quiz,oral',
            'course_id' => 'required|exists:courses,course_id',
            'title'     => 'required|string|max:255',
            'max_score' => 'required|numeric|min:1',
            'date'      => 'required|date|after_or_equal:today',
            'time'      => 'nullable|string|max:255',
            'duration'  => 'nullable|string|max:255',
            'notes'     => 'nullable|string|max:500',
        ]);

        $teacher = $this->getTeacher();

        DB::table('grade_events')->insert([
            'teacher_id' => $teacher->teacher_id,
            'course_id'  => $request->course_id,
            'type'       => $request->type,
            'title'      => $request->title,
            'max_score'  => $request->max_score,
            'date'       => $request->date,
            'time'       => $request->time,
            'duration'   => $request->duration,
            'notes'      => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تمت إضافة التقييم بنجاح!');
    }

    public function gradeEventStudents($id)
    {
        $teacher = $this->getTeacher();
        
        $event = DB::table('grade_events')
            ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
            ->where('grade_events.id', $id)
            ->where('grade_events.teacher_id', $teacher->teacher_id)
            ->select('grade_events.*', 'courses.title as course_title')
            ->first();

        if (!$event) {
            return back()->with('error', 'التقييم غير موجود أو لا تملك صلاحية الوصول إليه.');
        }

        // Get all students enrolled in this course
        $enrolledStudentIds = DB::table('enrollments')
            ->where('course_id', $event->course_id)
            ->where('status', 'active')
            ->pluck('student_id');

        // Get students with their current grade entry if exists
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('grade_entries', function ($join) use ($id) {
                $join->on('students.student_id', '=', 'grade_entries.student_id')
                     ->where('grade_entries.grade_event_id', '=', $id);
            })
            ->whereIn('students.student_id', $enrolledStudentIds)
            ->select('students.student_id', 'users.full_name', 'students.student_code', 'grade_entries.score', 'grade_entries.notes', 'grade_entries.id as entry_id')
            ->orderBy('users.full_name')
            ->get();

        return view('teacher.grade_event_students', compact('event', 'students'));
    }

    public function saveGradeEntries(Request $request, $id)
    {
        $teacher = $this->getTeacher();
        
        $event = DB::table('grade_events')
            ->where('id', $id)
            ->where('teacher_id', $teacher->teacher_id)
            ->first();

        if (!$event) {
            return back()->with('error', 'التقييم غير موجود.');
        }

        $scores = $request->input('scores', []);
        $notes = $request->input('notes', []);

        foreach ($scores as $studentId => $score) {
            if ($score === null || $score === '') continue; // Skip empty inputs

            $existing = DB::table('grade_entries')
                ->where('grade_event_id', $id)
                ->where('student_id', $studentId)
                ->first();

            $note = $notes[$studentId] ?? null;

            if ($existing) {
                DB::table('grade_entries')->where('id', $existing->id)->update([
                    'score' => $score,
                    'notes' => $note,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('grade_entries')->insert([
                    'grade_event_id' => $id,
                    'student_id' => $studentId,
                    'score' => $score,
                    'notes' => $note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('teacher.grade_events')->with('success', 'تم حفظ الدرجات بنجاح!');
    }

    public function deleteGradeEvent($id)
    {
        $teacher = $this->getTeacher();

        DB::table('grade_events')
            ->where('id', $id)
            ->where('teacher_id', $teacher->teacher_id)
            ->delete();

        return redirect()->back()->with('success', 'تم حذف التقييم.');
    }

    // ────────────────────────────────────────────────────────────
    //  QUIZZES
    // ────────────────────────────────────────────────────────────

    public function quizzes()
    {
        $teacher = $this->getTeacher();
        $quizzes = \App\Models\Quiz::where('teacher_id', $teacher->teacher_id)
            ->with('course')
            ->withCount('questions')
            ->latest()
            ->get();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();

        return view('teacher.quizzes', compact('quizzes', 'courses'));
    }

    public function storeQuiz(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'course_id'        => 'required|exists:courses,course_id',
            'description'      => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'total_marks'      => 'required|integer|min:1',
            'start_at'         => 'nullable|date',
            'end_at'           => 'nullable|date|after_or_equal:start_at',
        ]);

        $teacher = $this->getTeacher();
        $quiz = \App\Models\Quiz::create([
            'teacher_id'       => $teacher->teacher_id,
            'course_id'        => $request->course_id,
            'title'            => $request->title,
            'description'      => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'total_marks'      => $request->total_marks,
            'start_at'         => $request->start_at,
            'end_at'           => $request->end_at,
            'is_published'     => false,
        ]);

        return redirect()->route('teacher.quizzes.builder', $quiz->id)
            ->with('success', 'تم إنشاء الاختبار، أضف الأسئلة الآن');
    }

    public function quizBuilder($id)
    {
        $teacher = $this->getTeacher();
        $quiz = \App\Models\Quiz::where('id', $id)
            ->where('teacher_id', $teacher->teacher_id)
            ->with(['questions.options', 'course'])
            ->firstOrFail();

        return view('teacher.quiz_builder', compact('quiz'));
    }

    public function storeQuestion(Request $request, $quizId)
    {
        $teacher = $this->getTeacher();
        $quiz = \App\Models\Quiz::where('id', $quizId)
            ->where('teacher_id', $teacher->teacher_id)
            ->firstOrFail();

        $request->validate([
            'question_text' => 'required|string',
            'type'          => 'required|in:mcq,text',
            'marks'         => 'required|integer|min:1',
            'options'       => 'required_if:type,mcq|array|min:2',
            'options.*'     => 'required_if:type,mcq|string',
            'correct'       => 'required_if:type,mcq|integer',
        ]);

        $order = $quiz->questions()->count() + 1;

        $question = $quiz->questions()->create([
            'question_text' => $request->question_text,
            'type'          => $request->type,
            'marks'         => $request->marks,
            'order_num'     => $order,
        ]);

        if ($request->type === 'mcq' && $request->has('options')) {
            foreach ($request->options as $i => $opt) {
                if (trim($opt) === '') continue;
                $question->options()->create([
                    'option_text' => $opt,
                    'is_correct'  => ($request->correct == $i),
                ]);
            }
        }

        return redirect()->route('teacher.quizzes.builder', $quizId)
            ->with('success', 'تمت إضافة السؤال');
    }

    public function deleteQuestion($quizId, $questionId)
    {
        $teacher = $this->getTeacher();
        $quiz = \App\Models\Quiz::where('id', $quizId)
            ->where('teacher_id', $teacher->teacher_id)
            ->firstOrFail();

        $quiz->questions()->where('id', $questionId)->delete();

        return redirect()->route('teacher.quizzes.builder', $quizId)
            ->with('success', 'تم حذف السؤال');
    }

    public function publishQuiz($id)
    {
        $teacher = $this->getTeacher();
        $quiz = \App\Models\Quiz::where('id', $id)
            ->where('teacher_id', $teacher->teacher_id)
            ->firstOrFail();

        $quiz->update(['is_published' => !$quiz->is_published]);

        return redirect()->route('teacher.quizzes')
            ->with('success', $quiz->is_published ? 'تم نشر الاختبار' : 'تم إلغاء نشر الاختبار');
    }

    public function deleteQuiz($id)
    {
        $teacher = $this->getTeacher();
        $quiz = \App\Models\Quiz::where('id', $id)
            ->where('teacher_id', $teacher->teacher_id)
            ->firstOrFail();

        $quiz->delete();
        return redirect()->route('teacher.quizzes')->with('success', 'تم حذف الاختبار');
    }
}
