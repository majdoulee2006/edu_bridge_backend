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
        // 🔍 البحث عن المستخدم بواسطة البريد، اسم المستخدم، أو الهاتف لتجنب تصنيف الأرقام كأرقام هواتف بالخطأ
        $user = \App\Models\User::where('email', $input)
            ->orWhere('username', $input)
            ->orWhere('phone', $input)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);
            $student = Student::where('user_id', Auth::user()->getKey())->first();
            if (!$student) {
                Auth::logout();
                return back()->withErrors(['login' => 'هذا الحساب ليس حساب طالب.']);
            }
            $request->session()->regenerate();
            return redirect('/student/dashboard');
        }

        return back()->withInput()->withErrors(['login' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.']);
    }

    public function logout(Request $request)
    {
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

        $assignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->leftJoin('assignment_submissions', function($join) use ($student) {
                $join->on('assignment_submissions.assignment_id', '=', 'assignments.assignment_id')
                     ->where('assignment_submissions.student_id', '=', $student->student_id);
            })
            ->whereIn('assignments.course_id', $enrolledCourseIds)
            ->select(
                'assignments.*',
                'courses.title as course_title',
                'assignment_submissions.submission_id',
                'assignment_submissions.file_path as submission_file',
                'assignment_submissions.grade',
                'assignment_submissions.feedback',
                'assignment_submissions.submitted_at'
            )
            ->orderBy('assignments.due_date')
            ->get();

        return view('student.assignments', compact('assignments'));
    }

    public function submitAssignment(Request $request, $assignmentId)
    {
        $request->validate([
            'file' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip',
        ], [
            'file.required' => 'يرجى اختيار ملف للتسليم.',
            'file.max'      => 'حجم الملف يجب ألا يتجاوز 50 ميجابايت.',
        ]);

        $student = $this->getStudent();

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

        $file     = $request->file('file');
        $filePath = $file->store('submissions', 'public');

        DB::table('assignment_submissions')->insert([
            'assignment_id' => $assignmentId,
            'student_id'    => $student->student_id,
            'file_path'     => $filePath,
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

        return view('student.grades', compact('courseGradesData', 'avgGrade'));
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

        return view('student.attendance', compact('attendanceRecords', 'courseStats'));
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

        return view('student.leave_requests', compact('requests'));
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

        $reasonText = $request->reason;
        if ($request->type === 'hourly') {
            if ($request->from_time && $request->to_time) {
                $reasonText = "[إذن ساعي: من " . $request->from_time . " إلى " . $request->to_time . "] - " . $request->reason;
            } else {
                $reasonText = "[إذن ساعي] - " . $request->reason;
            }
        } else {
            $reasonText = "[إذن يومي] - " . $request->reason;
        }

        DB::table('absence_requests')->insert([
            'student_id' => $student->student_id,
            'reason'     => $reasonText,
            'date'       => $request->date,
            'document'   => $filePath,
            'status'     => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم تقديم طلب الإذن بنجاح، في انتظار الموافقة.');
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
        return view('student.profile', compact('student', 'user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
            'phone'     => 'nullable|string|max:20',
        ]);

        DB::table('users')->where('user_id', $user->user_id)->update([
            'full_name'  => $request->full_name,
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

    // ────────────────────────────────────────────────────────────
    //  MESSAGES / CHAT SYSTEM
    // ────────────────────────────────────────────────────────────
    public function messages()
    {
        $currentUserId = Auth::id();

        $conversations = \App\Models\Message::where('sender_id', $currentUserId)
            ->orWhere('receiver_id', $currentUserId)
            ->latest()
            ->get()
            ->map(function ($msg) use ($currentUserId) {
                return ($msg->sender_id == $currentUserId) ? $msg->receiver_id : $msg->sender_id;
            })
            ->unique()
            ->values();

        $contacts = \App\Models\User::whereIn('user_id', $conversations)->get();
        $allUsers = \App\Models\User::where('user_id', '!=', $currentUserId)->get();

        return view('student.messages', compact('contacts', 'allUsers'));
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

        \App\Models\Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required',
            'message'     => 'nullable|string',
            'attachment'  => 'nullable|file|max:51200',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat_attachments', 'public');
            $attachmentPath = asset('storage/' . $path);
        }

        $message = \App\Models\Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'attachment'  => $attachmentPath,
            'is_read'     => 0,
        ]);

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json(['status' => 'success', 'data' => $message], 201);
    }
}
