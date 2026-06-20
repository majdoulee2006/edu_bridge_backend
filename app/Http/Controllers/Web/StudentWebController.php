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
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $loginField = 'email';
        } elseif (preg_match('/^\+?[0-9]{7,15}$/', $input)) {
            $loginField = 'phone';
        } else {
            $loginField = 'username';
        }

        if (Auth::attempt([$loginField => $input, 'password' => $request->password])) {
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
        $attendanceRate = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100) : 0;

        // الإعلانات الأخيرة
        $announcements = DB::table('announcements')
            ->where(function($q) use ($courseIds) {
                $q->where('type', 'general')
                  ->orWhereIn('course_id', $courseIds);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // متوسط الدرجات
        $avgGrade = DB::table('grades')
            ->where('student_id', $student->student_id)
            ->avg('score');
        $avgGrade = $avgGrade ? round($avgGrade, 1) : 0;

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

        $courses = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('course_teachers', function($join) {
                $join->on('courses.course_id', '=', 'course_teachers.course_id');
            })
            ->leftJoin('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users as teacher_users', 'teachers.user_id', '=', 'teacher_users.user_id')
            ->where('enrollments.student_id', $student->student_id)
            ->select('courses.*', 'teacher_users.full_name as teacher_name',
                DB::raw('(SELECT COUNT(*) FROM lessons WHERE lessons.course_id = courses.course_id) as lessons_count'),
                DB::raw('(SELECT COUNT(*) FROM assignments WHERE assignments.course_id = courses.course_id) as assignments_count')
            )
            ->distinct()
            ->get();

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

        $grades = DB::table('grades')
            ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->where('grades.student_id', $student->student_id)
            ->select('grades.*', 'courses.title as course_title', 'exams.exam_name as exam_title', 'exams.max_score')
            ->orderBy('courses.title')
            ->get();

        $avgGrade = $grades->avg('score') ? round($grades->avg('score'), 1) : 0;

        return view('student.grades', compact('grades', 'avgGrade'));
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
            'date'       => 'required|date',
            'document'   => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf',
        ]);

        $student  = $this->getStudent();
        $filePath = null;

        if ($request->hasFile('document')) {
            $filePath = $request->file('document')->store('leave_requests', 'public');
        }

        DB::table('absence_requests')->insert([
            'student_id' => $student->student_id,
            'reason'     => $request->reason,
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
            'new_password'     => 'required|min:8|confirmed',
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
}
