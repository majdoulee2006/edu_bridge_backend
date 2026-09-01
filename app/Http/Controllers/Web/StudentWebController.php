<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;

class StudentWebController extends Controller
{
    use \App\Traits\HandlesMessagesTrait;
    // ────────────────────────────────────────────────────────────
    //  AUTH
    // ────────────────────────────────────────────────────────────

    public function showLoginForm()
    {
        if (Auth::check()) {
            $student = Student::where('user_id', Auth::user()->user_id)->first();
            if ($student) return redirect('/student/dashboard');
        }
        return view('student.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|min:6',
        ], [
            'login.required'    => 'البريد الإلكتروني أو رقم الهاتف مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $input = $request->login;
        $user = \App\Models\User::where('email', $input)
            ->orWhere('username', $input)
            ->orWhere('phone', $input)
            ->orWhere('university_id', $input)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);
            $student = Student::where('user_id', Auth::user()->getKey())->first();
            if (!$student) {
                \App\Models\UserActivity::log('محاولة دخول مرفوضة', 'هذا الحساب ليس حساب طالب', $user);
                Auth::logout();
                return back()->withErrors(['login' => 'هذا الحساب ليس حساب طالب.']);
            }
            if (Auth::user()->status !== 'active') {
                \App\Models\UserActivity::log('محاولة دخول مرفوضة', 'حساب الطالب موقوف مؤقتاً', $user);
                Auth::logout();
                return back()->withErrors(['login' => 'عذراً. حسابك موقوف مؤقتاً.']);
            }
            $request->session()->regenerate();
            \App\Models\UserActivity::log('تسجيل دخول', 'تسجيل دخول ناجح عبر موقع الطالب الإلكتروني');
            return redirect('/student/dashboard');
        }

        return back()->withInput()->withErrors(['login' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.']);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            if ($request->has('is_inactivity_logout')) {
                \App\Models\UserActivity::log('خروج تلقائي (خمول)', 'تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول');
            } else {
                \App\Models\UserActivity::log('تسجيل خروج', 'قام الطالب بتسجيل الخروج يدوياً من الموقع');
            }
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/student/login');
    }

    // ────────────────────────────────────────────────────────────
    //  HELPER
    // ────────────────────────────────────────────────────────────
    private function getStudent()
    {
        return Student::where('user_id', Auth::user()->user_id)->first();
    }

    // ────────────────────────────────────────────────────────────
    //  DASHBOARD
    // ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $student = $this->getStudent();
        $user    = Auth::user();

        // المواد المسجّلة (عبر enrollments أو program+year)
        $courses = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.student_id', $student->student_id)
            ->select('courses.*')
            ->get();

        $courseIds = $courses->pluck('course_id');

        // الواجبات النشطة
        $assignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->whereIn('assignments.course_id', $courseIds)
            ->where('assignments.due_date', '>=', now())
            ->select('assignments.*', 'courses.title as course_title')
            ->orderBy('assignments.due_date')
            ->limit(5)
            ->get();

        // نسبة الحضور
        $totalAttendance = DB::table('attendance')
            ->where('student_id', $student->student_id)
            ->count();
        $presentCount = DB::table('attendance')
            ->where('student_id', $student->student_id)
            ->where('status', 'present')
            ->count();
        $attendanceRate = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100) : null;

        // الإعلانات الأخيرة
        $announcements = DB::table('announcements')
            ->where(function($q) use ($courseIds) {
                $q->where('type', 'general')
                  ->orWhereIn('course_id', $courseIds);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // متوسط الدرجات (يشمل الواجبات المصححة والاختبارات)
        $submissionGrades = DB::table('assignment_submissions')
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.assignment_id')
            ->where('assignment_submissions.student_id', $student->student_id)
            ->whereNotNull('assignment_submissions.grade')
            ->select('assignment_submissions.grade', 'assignments.max_points')
            ->get();

        $submissionPercentages = $submissionGrades->map(function($sub) {
            $max = ($sub->max_points ?? 0) > 0 ? $sub->max_points : 100;
            return ($sub->grade / $max) * 100;
        });

        $examGrades = DB::table('grades')
            ->where('student_id', $student->student_id)
            ->pluck('score');

        $allGrades = $submissionPercentages->concat($examGrades);
        $avgGrade = $allGrades->count() > 0 ? round($allGrades->avg(), 1) : null;

        return view('student.dashboard', compact(
            'student', 'user', 'courses', 'assignments',
            'attendanceRate', 'announcements', 'avgGrade',
            'totalAttendance', 'presentCount'
        ));
    }

    // ────────────────────────────────────────────────────────────
    //  SCHEDULE
    // ────────────────────────────────────────────────────────────

    public function schedule()
    {
        $student = $this->getStudent();
        $user    = Auth::user();

        $enrolledCourseIds = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->pluck('course_id');

        $schedules = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
            ->leftJoin('course_teachers', 'schedules.course_id', '=', 'course_teachers.course_id')
            ->leftJoin('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users as teacher_users', 'teachers.user_id', '=', 'teacher_users.user_id')
            ->whereIn('schedules.course_id', $enrolledCourseIds)
            ->select('schedules.*', 'courses.title as course_title', 'teacher_users.full_name as teacher_name')
            ->orderByRaw("FIELD(schedules.day, 'Sunday','Monday','Tuesday','Wednesday','Thursday')")
            ->orderBy('schedules.start_time')
            ->get();

        $exams = DB::table('exams')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->whereIn('exams.course_id', $enrolledCourseIds)
            ->select('exams.*', 'courses.title as course_title')
            ->orderBy('exams.exam_date')
            ->get();

        $days = ['Sunday' => 'الأحد', 'Monday' => 'الاثنين', 'Tuesday' => 'الثلاثاء',
                 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس'];

        return view('student.schedule', compact('schedules', 'exams', 'days'));
    }

    // ────────────────────────────────────────────────────────────
    //  COURSES & MATERIALS
    // ────────────────────────────────────────────────────────────

    public function courses()
    {
        $student = $this->getStudent();

        $query = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.student_id', $student->student_id);

        // 🎓 تصفية المواد حسب السنة الدراسية للطالب (سنة أولى، سنة ثانية، إلخ)
        $levelMap = [
            'السنة الأولى' => 1, 'السنة الثانية' => 2, 'السنة الثالثة' => 3, 'السنة الرابعة' => 4, 'السنة الخامسة' => 5,
            'الأولى' => 1, 'الثانية' => 2, 'الثالثة' => 3, 'الرابعة' => 4, 'الخامسة' => 5,
            '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5
        ];
        $studentYear = $levelMap[$student->level ?? ''] ?? $levelMap[Auth::user()->academic_year ?? ''] ?? null;

        if ($studentYear) {
            $query->where('courses.year', $studentYear);
        }

        // 📅 تصفية المواد حسب الفصل الدراسي النشط حالياً إن وجد
        $activeSemesterId = DB::table('semesters')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->value('semester_id');

        if ($activeSemesterId) {
            $query->where('courses.semester_id', $activeSemesterId);
        }

        $courses = $query->select('courses.*',
                DB::raw("(SELECT COUNT(*) FROM lessons 
                          WHERE lessons.course_id = courses.course_id 
                            AND (type != 'session' OR type IS NULL) 
                            AND title NOT LIKE '%حضور%' 
                            AND title NOT LIKE '%غياب%' 
                            AND (content_url IS NULL OR content_url NOT LIKE '%attendance%')
                         ) as lessons_count"),
                DB::raw('(SELECT COUNT(*) FROM assignments WHERE assignments.course_id = courses.course_id) as assignments_count')
            )
            ->get();

        foreach ($courses as $course) {
            $teacherName = DB::table('course_teachers')
                ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('course_teachers.course_id', $course->course_id)
                ->value('users.full_name');

            $course->teacher_name = $teacherName ?? 'مدرس غير محدد';
        }

        return view('student.courses', compact('courses'));
    }

    public function courseMaterials($courseId)
    {
        $student = $this->getStudent();

        // التحقق من التسجيل في المادة
        $enrolled = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$enrolled) abort(403, 'غير مسجل في هذه المادة');

        $course = DB::table('courses')->where('course_id', $courseId)->first();
        if (!$course) abort(404);

        $materials = DB::table('lessons')
            ->where('course_id', $courseId)
            ->where(function($q) {
                $q->where('type', '!=', 'session')
                  ->orWhereNull('type');
            })
            ->where('title', 'not like', '%حضور%')
            ->where('title', 'not like', '%غياب%')
            ->where(function($query) {
                $query->whereNull('content_url')
                      ->orWhere('content_url', 'not like', '%attendance%');
            })
            ->orderByDesc('created_at')
            ->get();

        return view('student.course_materials', compact('course', 'materials'));
    }

    // ────────────────────────────────────────────────────────────
    //  ASSIGNMENTS
    // ────────────────────────────────────────────────────────────

    public function assignments()
    {
        $student = $this->getStudent();

        $enrolledCourseIds = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->pluck('course_id');

        $courses = DB::table('courses')
            ->whereIn('course_id', $enrolledCourseIds)
            ->get();

        foreach ($courses as $c) {
            $teacherName = DB::table('course_teachers')
                ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('course_teachers.course_id', $c->course_id)
                ->value('users.full_name');

            $c->teacher_name = $teacherName ?? 'مدرس المادة';
        }

        $assignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->leftJoin('assignment_submissions', function($join) use ($student) {
                $join->on('assignment_submissions.assignment_id', '=', 'assignments.assignment_id')
                     ->where('assignment_submissions.student_id', '=', $student->student_id);
            })
            ->leftJoin('course_teachers', 'courses.course_id', '=', 'course_teachers.course_id')
            ->leftJoin('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users as teacher_users', 'teachers.user_id', '=', 'teacher_users.user_id')
            ->whereIn('assignments.course_id', $enrolledCourseIds)
            ->select(
                'assignments.*',
                'courses.title as course_title',
                'teacher_users.full_name as teacher_name',
                'assignment_submissions.submission_id',
                'assignment_submissions.file_path as submission_file',
                'assignment_submissions.solution_text',
                'assignment_submissions.student_notes',
                'assignment_submissions.grade',
                'assignment_submissions.feedback',
                'assignment_submissions.submitted_at'
            )
            ->orderBy('assignments.due_date')
            ->get();

        return view('student.assignments', compact('assignments', 'courses'));
    }

    public function submitAssignment(Request $request, $assignmentId)
    {
        $request->validate([
            'file'          => 'nullable|file|max:51200|mimes:jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip',
            'solution_text' => 'nullable|string|max:4000',
            'student_notes' => 'nullable|string|max:1000',
        ], [
            'file.max' => 'حجم الملف يجب ألا يتجاوز 50 ميجابايت.',
        ]);

        $student = $this->getStudent();

        $solText   = trim($request->input('solution_text', ''));
        $notesText = trim($request->input('student_notes', ''));

        if (!$request->hasFile('file') && empty($solText)) {
            return back()->withErrors(['file' => 'يرجى كتابة نص الحل أو إرفاق ملف على الأقل لتسليم الواجب.']);
        }

        // التحقق من عدم التسليم المسبق
        $existing = DB::table('assignment_submissions')
            ->where('assignment_id', $assignmentId)
            ->where('student_id', $student->student_id)
            ->first();

        if ($existing) {
            return back()->withErrors(['file' => 'لقد قمت بتسليم هذا الواجب مسبقاً.']);
        }

        $assignment = DB::table('assignments')->where('assignment_id', $assignmentId)->first();
        if (!$assignment) abort(404);

        // التحقق من أن الطالب مسجل فعلاً في هذه المادة
        $enrolled = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->where('course_id', $assignment->course_id)
            ->exists();
            
        if (!$enrolled) {
            abort(403, 'غير مصرح لك بتسليم واجب لمادة غير مسجل بها.');
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $filePath = $file->store('submissions', 'public');
        }

        DB::table('assignment_submissions')->insert([
            'assignment_id' => $assignmentId,
            'student_id'    => $student->student_id,
            'file_path'     => $filePath,
            'solution_text' => $solText !== '' ? $solText : null,
            'student_notes' => $notesText !== '' ? $notesText : null,
            'submitted_at'  => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return back()->with('success', 'تم تسليم الواجب بنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  GRADES
    // ────────────────────────────────────────────────────────────

    public function grades()
    {
        $student = $this->getStudent();

        $enrolledCourses = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.student_id', $student->student_id)
            ->select('courses.course_id', 'courses.title as course_title')
            ->get();

        $courseGradesData = [];
        $allPercentages = [];

        foreach ($enrolledCourses as $c) {
            // 1. الواجبات المصححة للمادة
            $assignments = DB::table('assignment_submissions')
                ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.assignment_id')
                ->where('assignments.course_id', $c->course_id)
                ->where('assignment_submissions.student_id', $student->student_id)
                ->whereNotNull('assignment_submissions.grade')
                ->select(
                    'assignments.title as name',
                    'assignment_submissions.grade as score',
                    'assignments.max_points as max_score',
                    'assignment_submissions.feedback',
                    'assignment_submissions.submitted_at as date'
                )
                ->get();

            // 2. الامتحانات والمذاكرات للمادة
            $examsAndQuizzes = DB::table('grades')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->where('exams.course_id', $c->course_id)
                ->where('grades.student_id', $student->student_id)
                ->select(
                    'exams.exam_name as name',
                    'grades.score',
                    'exams.max_score',
                    'grades.created_at as date'
                )
                ->get();

            $quizzes = $examsAndQuizzes->filter(function($e) {
                $name = mb_strtolower($e->name);
                return str_contains($name, 'مذاكرة') || str_contains($name, 'اختبار قصير') || str_contains($name, 'quiz') || str_contains($name, 'test');
            })->values();

            $exams = $examsAndQuizzes->filter(function($e) {
                $name = mb_strtolower($e->name);
                return !(str_contains($name, 'مذاكرة') || str_contains($name, 'اختبار قصير') || str_contains($name, 'quiz') || str_contains($name, 'test'));
            })->values();

            // حساب النسب المئوية للمعدل الكلي
            foreach ($assignments as $a) {
                $max = ($a->max_score ?? 0) > 0 ? $a->max_score : 100;
                $allPercentages[] = ($a->score / $max) * 100;
            }
            foreach ($examsAndQuizzes as $eq) {
                $max = ($eq->max_score ?? 0) > 0 ? $eq->max_score : 100;
                $allPercentages[] = ($eq->score / $max) * 100;
            }

            $courseGradesData[] = [
                'course_id'    => $c->course_id,
                'course_title' => $c->course_title,
                'assignments'  => $assignments,
                'quizzes'      => $quizzes,
                'exams'        => $exams,
                'total_items'  => $assignments->count() + $quizzes->count() + $exams->count(),
            ];
        }

        $avgGrade = count($allPercentages) > 0 ? round(array_sum($allPercentages) / count($allPercentages), 1) : 0;

        $cardReq = new \Illuminate\Http\Request(['student_id' => $student->student_id]);
        $academicCardResponse = app(\App\Http\Controllers\Api\AffairsController::class)->getStudentAcademicCardForAffairs($cardReq);
        $academicCardData = json_decode($academicCardResponse->getContent(), true);

        return view('student.grades', compact('courseGradesData', 'avgGrade', 'academicCardData'));
    }

    // ────────────────────────────────────────────────────────────
    //  ATTENDANCE (VIEW ONLY)
    // ────────────────────────────────────────────────────────────

    public function attendance()
    {
        $student = $this->getStudent();

        $enrolledCourseIds = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->pluck('course_id');

        $attendanceRecords = DB::table('attendance')
            ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->where('attendance.student_id', $student->student_id)
            ->select('attendance.*', 'courses.title as course_title', 'lessons.title as lesson_title')
            ->orderByDesc('attendance.attendance_date')
            ->get();

        // إحصائيات لكل مادة
        $courseStats = [];
        foreach ($enrolledCourseIds as $courseId) {
            $course = DB::table('courses')->where('course_id', $courseId)->first();
            if (!$course) continue;

            $total   = DB::table('attendance')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->where('attendance.student_id', $student->student_id)
                ->where('lessons.course_id', $courseId)
                ->count();

            $present = DB::table('attendance')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->where('attendance.student_id', $student->student_id)
                ->where('lessons.course_id', $courseId)
                ->where('attendance.status', 'present')
                ->count();

            if ($total > 0) {
                $courseStats[] = [
                    'course_id'    => $courseId,
                    'course_title' => $course->title,
                    'total'        => $total,
                    'present'      => $present,
                    'absent'       => $total - $present,
                    'rate'         => round(($present / $total) * 100),
                ];
            }
        }

        // الجلسات المتاحة حالياً
        $activeSessions = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->whereIn('courses.course_id', $enrolledCourseIds)
            ->where('attendance_sessions.expires_at', '>', now())
            ->select('attendance_sessions.*', 'courses.title as course_title', 'lessons.title as lesson_title')
            ->get();

        return view('student.attendance', compact('attendanceRecords', 'courseStats', 'activeSessions'));
    }

    public function scanAttendanceWeb(Request $request)
    {
        $apiReq = new Request($request->all());
        $apiReq->setUserResolver(fn() => Auth::user());

        $apiController = app(\App\Http\Controllers\Api\StudentController::class);
        return $apiController->scanAttendanceQr($apiReq);
    }

    public function submitExcuse(Request $request, $attendanceId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'file'   => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf',
        ]);

        $student    = $this->getStudent();
        $attendance = DB::table('attendance')->where('id', $attendanceId)->first();

        if (!$attendance || $attendance->student_id != $student->student_id) abort(403);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('excuses', 'public');
        }

        DB::table('attendance')->where('id', $attendanceId)->update([
            'excuse_reason' => $request->reason,
            'excuse_file'   => $filePath,
            'updated_at'    => now(),
        ]);

        return back()->with('success', 'تم تقديم العذر بنجاح.');
    }

    // ────────────────────────────────────────────────────────────
    //  LEAVE REQUESTS
    // ────────────────────────────────────────────────────────────

    public function leaveRequests()
    {
        $student = $this->getStudent();

        $requests = DB::table('absence_requests')
            ->where('student_id', $student->student_id)
            ->orderByDesc('created_at')
            ->get();

        return view('student.leave_requests', compact('requests', 'student'));
    }

    public function storeLeaveRequest(Request $request)
    {
        $request->validate([
            'reason'     => 'required|string|max:500',
            'date'       => 'required|date|after_or_equal:today',
            'document'   => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf',
        ], [
            'date.after_or_equal' => 'تاريخ الغياب يجب أن يكون اليوم أو في المستقبل.',
            'reason.required'    => 'يرجى كتابة سبب الغياب.',
            'document.max'       => 'حجم المستند المرفق يجب ألا يتجاوز 10 ميجابايت.',
        ]);

        $student  = $this->getStudent();
        $filePath = null;

        if ($request->hasFile('document')) {
            $filePath = $request->file('document')->store('leave_requests', 'public');
        }

        // التحقق من الوقت: عدم السماح باختيار وقت سابق لوقتنا الحالي إذا كان التاريخ هو اليوم
        $todayStr = date('Y-m-d');
        $currentTime = date('H:i');
        if ($request->date === $todayStr) {
            $selectedTime = $request->type === 'hourly' ? ($request->from_time ?? $currentTime) : ($request->leave_time ?? $request->time ?? $currentTime);
            if ($selectedTime < $currentTime) {
                return back()->withErrors(['time' => 'عذراً، لا يمكن اختيار وقت سابق لوقتنا الحالي لليوم!'])->withInput();
            }
        }

        $reasonText = $request->reason;
        if ($request->type === 'hourly') {
            $fromTime = $request->from_time ?? date('H:i');
            $toTime = $request->to_time ?? date('H:i', strtotime('+2 hours'));
            $reasonText = "[إذن ساعي - من الساعة: " . $fromTime . " إلى: " . $toTime . "] - " . $request->reason;
        } else {
            $leaveTime = $request->leave_time ?? $request->time ?? date('H:i');
            $reasonText = "[إذن يومي - وقت الإذن: " . $leaveTime . "] - " . $request->reason;
        }

        $requestId = DB::table('absence_requests')->insertGetId([
            'student_id' => $student->student_id,
            'reason'     => $reasonText,
            'date'       => $request->date,
            'document'   => $filePath,
            'status'     => 'pending_parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $authUser    = auth()->user();
        $studentName = $authUser->full_name ?? 'الطالب';
        $notifTitle  = 'طلب إذن جديد من الابن';
        $notifMsg    = 'قام ابنكم ' . $studentName . ' بتقديم طلب إذن غياب بتاريخ ' . $request->date . '، يرجى مراجعته والموافقة عليه.';

        // الخطوة 1 في المسار المتسلسل: إرسال الإشعار لولي الأمر أولاً
        $parentUserIds = DB::table('parent_students')
            ->where('student_id', $student->student_id)
            ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
            ->pluck('parents.user_id');

        foreach ($parentUserIds as $pId) {
            if ($pId) {
                DB::table('notifications')->insert([
                    'user_id'    => $pId,
                    'sender_id'  => $authUser?->user_id,
                    'title'      => $notifTitle,
                    'message'    => $notifMsg,
                    'type'       => 'leave_request',
                    'category'   => 'administrative',
                    'related_id' => $requestId,
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                \App\Services\FcmService::sendToUser(
                    $pId,
                    $notifTitle,
                    $notifMsg,
                    ['type' => 'leave_request', 'related_id' => (string)$requestId]
                );
            }
        }

        return back()->with('success', 'تم تقديم طلب الإذن بنجاح، وهو بانتظار موافقة ولي الأمر أولاً.');
    }

    // ────────────────────────────────────────────────────────────
    //  ANNOUNCEMENTS
    // ────────────────────────────────────────────────────────────

    public function announcements()
    {
        $student = $this->getStudent();

        $courseIds = DB::table('enrollments')
            ->where('student_id', $student->student_id)
            ->pluck('course_id');

        $announcements = DB::table('announcements')
            ->where(function($q) use ($courseIds) {
                $q->where('type', 'general')
                  ->orWhereIn('course_id', $courseIds);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('student.announcements', compact('announcements'));
    }

    // ────────────────────────────────────────────────────────────
    //  REPORT REQUEST
    // ────────────────────────────────────────────────────────────

    public function reportRequest()
    {
        $student = $this->getStudent();

        $reports = DB::table('report_requests')
            ->where('student_id', $student->student_id)
            ->orderByDesc('created_at')
            ->get();

        return view('student.report_request', compact('reports'));
    }

    public function storeReportRequest(Request $request)
    {
        $request->validate([
            'type'  => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $student = $this->getStudent();

        DB::table('report_requests')->insert([
            'student_id' => $student->student_id,
            'type'       => $request->type,
            'notes'      => $request->notes,
            'status'     => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم تقديم طلب التقرير بنجاح.');
    }

    // ────────────────────────────────────────────────────────────
    //  PROFILE
    // ────────────────────────────────────────────────────────────

    public function profile()
    {
        $student = $this->getStudent();
        $user    = Auth::user();
        $activeSemester = DB::table('semesters')->where('is_active', true)->first();
        return view('student.profile', compact('student', 'user', 'activeSemester'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email'     => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
            'phone'     => 'nullable|string|max:20',
        ]);

        DB::table('users')->where('user_id', $user->user_id)->update([
            'email'      => $request->email,
            'phone'      => $request->phone,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'يرجى إدخال كلمة المرور الحالية.',
            'new_password.required'     => 'يرجى إدخال كلمة المرور الجديدة.',
            'new_password.min'          => 'كلمة المرور الجديدة يجب ألا تقل عن 6 خانات.',
            'new_password.confirmed'    => 'تأكيد كلمة المرور الجديدة غير متطابق.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        DB::table('users')->where('user_id', $user->user_id)->update([
            'password'   => Hash::make($request->new_password),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح.');
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
            'student_profile_otp'          => $otp,
            'student_pending_profile_data' => $request->only(['full_name', 'phone', 'email', 'new_password'])
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

        if (session('student_profile_otp') == $request->otp) {
            $user = Auth::user();
            $data = session('student_pending_profile_data');

            $updates = ['updated_at' => now()];

            if (!empty($data['full_name'])) {
                $updates['full_name'] = $data['full_name'];
            }
            if (!empty($data['email'])) {
                $updates['email'] = $data['email'];
            }
            if (!empty($data['phone'])) {
                $updates['phone'] = $data['phone'];
            }
            if (!empty($data['new_password'])) {
                $updates['password'] = Hash::make($data['new_password']);
            }

            DB::table('users')
                ->where('user_id', $user->user_id)
                ->update($updates);

            session()->forget(['student_profile_otp', 'student_pending_profile_data']);

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

    // ────────────────────────────────────────────────────────────
    //  NOTIFICATIONS
    // ────────────────────────────────────────────────────────────
    public function notifications(Request $request)
    {
        $student = $this->getStudent();
        
        $query = \App\Models\Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($request->has('filter')) {
            if ($request->filter == 'unread') {
                $query->where('is_read', false);
            } elseif ($request->filter == 'read') {
                $query->where('is_read', true);
            }
        }

        $notifications = $query->paginate(15);

        $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count();

        return view('student.notifications', compact('notifications', 'unreadCount'));
    }

    public function markNotificationRead(Request $request, $id)
    {
        \App\Models\Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success']);
        }

        return back()->with('success', 'تم تحديد الإشعار كمقروء.');
    }

    public function markAllNotificationsRead()
    {
        \App\Models\Notification::where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
    }

    // ────────────────────────────────────────────────────────────
    //  MESSAGES / CHAT SYSTEM
    // ────────────────────────────────────────────────────────────
    public function messages()
    {
        $currentUserId = Auth::id();

        // Student can only start chats with Teachers, HOD, and Affairs. (roles 2, 5, 6)
        $allUsers = \App\Models\User::where('user_id', '!=', $currentUserId)
                        ->whereIn('role_id', [2, 5, 6])
                        ->get();

        return view('student.messages', compact('allUsers'));
    }



    public function getConversation($userId)
    {
        $currentUserId = Auth::id();
        $messages = \App\Models\Message::with(['sender', 'receiver'])
            ->where('deleted_for_everyone', false)
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where(function ($sub) use ($currentUserId, $userId) {
                    $sub->where('sender_id', $currentUserId)->where('receiver_id', $userId)->where('deleted_for_sender', false);
                })->orWhere(function ($sub) use ($currentUserId, $userId) {
                    $sub->where('sender_id', $userId)->where('receiver_id', $currentUserId)->where('deleted_for_receiver', false);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

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
            ->where('deleted_for_everyone', false)
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where(function($q2) use ($currentUserId, $userId) {
                    $q2->where('sender_id', $currentUserId)->where('receiver_id', $userId)->where('deleted_for_sender', false);
                })
                ->orWhere(function($q2) use ($currentUserId, $userId) {
                    $q2->where('sender_id', $userId)->where('receiver_id', $currentUserId)->where('deleted_for_receiver', false);
                });
            })
            ->where('message', 'LIKE', '%' . $query . '%')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $messages]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'message'     => 'required|string|max:2000',
            'attachment'  => 'nullable|file|max:51200',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $folder = 'chat_attachments';
            
            if ($request->message === '[Voice Note]' || strpos($file->getMimeType(), 'audio') !== false) {
                $folder = 'chat_voice_notes';
            }
            
            $attachmentPath = $file->store($folder, 'public');
            $attachmentPath = asset('storage/' . $attachmentPath);
        }

        $message = \App\Models\Message::create([
            'sender_id'   => Auth::user()->user_id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'attachment'  => $attachmentPath,
            'is_read'     => false,
        ]);

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

    public function deleteMessage(Request $request, $id)
    {
        $currentUserId = Auth::id();
        $type = $request->input('type', 'me');

        $message = \App\Models\Message::findOrFail($id);

        if ((int)$message->sender_id !== (int)$currentUserId && (int)$message->receiver_id !== (int)$currentUserId) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        if ($type === 'everyone') {
            if ((int)$message->sender_id !== (int)$currentUserId) {
                return response()->json(['status' => 'error', 'message' => 'يمكن لمراسل الرسالة فقط حذفها لدى الجميع'], 403);
            }
            $message->deleted_for_everyone = true;
            $message->save();

            if ($message->attachment) {
                $path = str_replace(asset('storage/'), '', $message->attachment);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        } else {
            if ((int)$message->sender_id === (int)$currentUserId) {
                $message->deleted_for_sender = true;
            }
            if ((int)$message->receiver_id === (int)$currentUserId) {
                $message->deleted_for_receiver = true;
            }
            $message->save();

            if (($message->deleted_for_sender && $message->deleted_for_receiver) || $message->deleted_for_everyone) {
                if ($message->attachment) {
                    $path = str_replace(asset('storage/'), '', $message->attachment);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    // ─────────────────────────── Academic Card Exports ───────────────────────────
    public function exportAcademicCardPdf(Request $request)
    {
        $student = $this->getStudent();
        if (!$student) abort(403);
        $request->merge(['student_id' => $student->student_id]);
        $apiController = app(\App\Http\Controllers\Api\AffairsController::class);
        return $apiController->exportStudentAcademicCardPdf($request);
    }

    public function exportAcademicCardExcel(Request $request)
    {
        $student = $this->getStudent();
        if (!$student) abort(403);
        $request->merge(['student_id' => $student->student_id]);
        $apiController = app(\App\Http\Controllers\Api\AffairsController::class);
        return $apiController->exportStudentAcademicCardExcel($request);
    }

    // ─────────────────────────── STUDENT SERVICES ───────────────────────────
    public function studentServices()
    {
        $student = $this->getStudent();

        $requests = \App\Models\StudentRequest::where('student_id', $student->student_id)
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total'     => $requests->count(),
            'pending'   => $requests->filter(fn($r) => str_starts_with($r->status, 'pending'))->count(),
            'approved'  => $requests->filter(fn($r) => $r->status === 'completed' && ($r->admin_decision === 'approved' || $r->affairs_decision === 'approved' || $r->hod_decision === 'approved'))->count(),
            'rejected'  => $requests->filter(fn($r) => $r->status === 'completed' && ($r->admin_decision === 'rejected' || $r->affairs_decision === 'rejected' || $r->hod_decision === 'rejected'))->count(),
        ];

        // جلب كشف علامات الطالب لاستخراج المواد الراسب فيها فقط لامتحان الإكمال
        $cardReq = new Request(['student_id' => $student->student_id]);
        $academicCardResponse = app(\App\Http\Controllers\Api\AffairsController::class)->getStudentAcademicCardForAffairs($cardReq);
        $cardData = json_decode($academicCardResponse->getContent(), true);

        $academicCard = $cardData['data']['academic_card'] ?? [];

        // تصفية المواد الراسب فيها فقط (status === 'راسب' أو المجموع أقل من 50)
        $failedCourses = array_values(array_filter($academicCard, function($item) {
            $status = $item['status'] ?? '';
            $score = $item['total_score'] ?? null;
            return $status === 'راسب' || ($score !== null && $score < 50);
        }));

        // جميع المواد المسجلة كـ Fallback في حال عدم تسجيل علامات سابقة
        $enrolledCourses = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.student_id', $student->student_id)
            ->select('courses.course_id', 'courses.title', 'courses.year')
            ->get();

        return view('student.services', compact('requests', 'stats', 'failedCourses', 'enrolledCourses'));
    }

    public function storeStudentService(Request $request)
    {
        $request->validate([
            'type'    => 'required|in:document,mercy,makeup,device_reset,face_photo,general',
            'details' => 'required|string|max:1500',
        ], [
            'type.required'    => 'يرجى اختيار نوع الخدمة الطلابية.',
            'details.required' => 'يرجى التعبير عن تفاصيل الطلب بشكل كافٍ.',
            'details.max'      => 'التفاصيل يجب ألا تتجاوز 1500 حرف.',
        ]);

        $student = $this->getStudent();

        $detailsText = trim($request->details);
        if ($request->type === 'makeup' && $request->filled('subject_name')) {
            $detailsText = "المادة المطلوبة للإكمال: " . $request->subject_name . "\nالسبب والتفاصيل: " . $detailsText;
        }

        $studentReq = \App\Models\StudentRequest::create([
            'student_id' => $student->student_id,
            'type'       => $request->type,
            'details'    => $detailsText,
            'status'     => 'pending_affairs',
        ]);

        // إرسال إشعار لموظفي الشؤون الطلابية بوجود طلب جديد
        $studentUser = DB::table('users')->where('user_id', $student->user_id)->first();
        $studentName = $studentUser?->full_name ?? 'الطالب';

        $typeNames = [
            'mercy'        => 'طلب استرحام',
            'document'     => 'طلب وثيقة طلابية',
            'makeup'       => 'طلب امتحان إكمال',
            'device_reset' => 'طلب فك قفل الجهاز',
            'face_photo'   => 'طلب بصمة الوجه',
            'general'      => 'طلب خدمة عامة',
        ];
        $typeName = $typeNames[$request->type] ?? 'طلب خدمة طلابية';

        $affairsUserIds = DB::table('users')->where('role_id', 6)->pluck('user_id');
        foreach ($affairsUserIds as $affairsUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $affairsUserId,
                'title'      => "طلب خدمة جديد ($typeName)",
                'message'    => "قام الطالب $studentName بتقديم $typeName جديد وهو بحاجة لمراجعتك.",
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $studentReq->id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'تم إرسال طلب الخدمة الطلابية بنجاح، وستتم مراجعته من قبل شؤون الطلاب والإدارة قريباً.');
    }
}
