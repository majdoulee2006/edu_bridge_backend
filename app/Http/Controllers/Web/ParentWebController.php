<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Models\User;
use Carbon\Carbon;

class ParentWebController extends Controller
{
    private function getParentRecord()
    {
        return DB::table('parents')->where('user_id', auth()->user()->user_id)->first();
    }

    private function getCommonData()
    {
        $parent = $this->getParentRecord();
        $user = auth()->user();

        if (!$parent) {
            return [
                'parent_children' => collect(),
                'selected_child_id' => null,
                'selected_child' => null,
                'user' => $user,
                'parent' => null
            ];
        }

        // Get children linked to parent
        $children = DB::table('parent_students')
            ->join('students', 'parent_students.student_id', '=', 'students.student_id')
            ->join('users as student_users', 'students.user_id', '=', 'student_users.user_id')
            ->where('parent_students.parent_id', $parent->parent_id)
            ->select('students.student_id', 'students.level', 'student_users.full_name', 'student_users.department', 'student_users.avatar', 'students.student_code')
            ->get();

        $selectedChildId = session('selected_child_id');
        if (!$selectedChildId && $children->isNotEmpty()) {
            $selectedChildId = $children->first()->student_id;
            session(['selected_child_id' => $selectedChildId]);
        }

        $selectedChild = $children->firstWhere('student_id', $selectedChildId);

        return [
            'parent_children' => $children,
            'selected_child_id' => $selectedChildId,
            'selected_child' => $selectedChild,
            'user' => $user,
            'parent' => $parent
        ];
    }

    private function parentView($view, $data = [])
    {
        return view($view, array_merge($this->getCommonData(), $data));
    }

    // ────────────────────────────────────────────────────────────
    //  AUTH
    // ────────────────────────────────────────────────────────────
    public function showLoginForm()
    {
        if (Auth::check()) {
            $parent = DB::table('parents')->where('user_id', Auth::user()->user_id)->first();
            if ($parent) return redirect('/parent/dashboard');
        }
        return view('parent.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|min:6',
        ], [
            'login.required'    => 'اسم المستخدم أو البريد الإلكتروني مطلوب.',
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
            $parent = DB::table('parents')->where('user_id', Auth::user()->getKey())->first();
            if (!$parent) {
                Auth::logout();
                return back()->withErrors(['login' => 'هذا الحساب ليس حساب ولي أمر.']);
            }
            if (Auth::user()->status !== 'active') {
                Auth::logout();
                return back()->withErrors(['login' => 'عذراً. حسابك موقوف مؤقتاً.']);
            }
            $request->session()->regenerate();
            return redirect('/parent/dashboard');
        }

        return back()->withInput()->withErrors(['login' => 'بيانات الدخول غير صحيحة.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/parent/login');
    }

    // ────────────────────────────────────────────────────────────
    //  SELECT CHILD
    // ────────────────────────────────────────────────────────────
    public function selectChild(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer'
        ]);

        $parent = $this->getParentRecord();
        $exists = DB::table('parent_students')
            ->where('parent_id', $parent->parent_id)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($exists) {
            session(['selected_child_id' => $request->student_id]);
        }

        return redirect()->back();
    }

    // ────────────────────────────────────────────────────────────
    //  DASHBOARD
    // ────────────────────────────────────────────────────────────
    public function dashboard()
    {
        $common = $this->getCommonData();
        $children = $common['parent_children'];
        $parent = $common['parent'];

        $totalAbsences = 0;
        $totalLate = 0;
        $averageGrades = [];

        foreach ($children as $child) {
            $attendances = DB::table('attendance')->where('student_id', $child->student_id)->get();
            $totalAbsences += $attendances->where('status', 'absent')->count();
            $totalLate += $attendances->where('status', 'late')->count();
            
            $avgGrade = DB::table('grades')->where('student_id', $child->student_id)->avg('score');
            $averageGrades[] = $avgGrade ?? 0;
        }

        $overallAverage = count($averageGrades) > 0 ? round(array_sum($averageGrades) / count($averageGrades), 1) : 0;

        $announcements = DB::table('announcements')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $this->parentView('parent.dashboard', compact(
            'children', 'totalAbsences', 'totalLate', 'overallAverage', 'announcements'
        ));
    }

    // ────────────────────────────────────────────────────────────
    //  CHILDREN MANAGEMENT & LINK STUDENT
    // ────────────────────────────────────────────────────────────
    public function children()
    {
        return $this->parentView('parent.children');
    }

    public function linkStudent(Request $request)
    {
        $request->validate([
            'student_code' => 'required|string',
        ], [
            'student_code.required' => 'الرقم الجامعي مطلوب.'
        ]);

        $parent = $this->getParentRecord();
        $student = DB::table('students')->where('student_code', $request->student_code)->first();

        if (!$student) {
            return back()->with('error', 'الرقم الجامعي المدخل غير صحيح أو غير مسجل في النظام.');
        }

        // Check last name matching
        $parentUser = auth()->user();
        $studentUser = DB::table('users')->where('user_id', $student->user_id)->first();

        $parentParts = preg_split('/\s+/', trim($parentUser->full_name ?? ''));
        $studentParts = preg_split('/\s+/', trim($studentUser->full_name ?? ''));

        $parentLastName  = $parentParts ? end($parentParts) : '';
        $studentLastName = $studentParts ? end($studentParts) : '';

        $parentLastName  = mb_strtolower(trim($parentLastName), 'UTF-8');
        $studentLastName = mb_strtolower(trim($studentLastName), 'UTF-8');

        if (empty($studentLastName) || $parentLastName !== $studentLastName) {
            return back()->with('error', 'لا يمكن ربط هذا الطالب. اسم العائلة لا يتطابق مع اسم عائلتك (' . $parentLastName . ').');
        }

        // Check if already linked
        $exists = DB::table('parent_students')
            ->where('parent_id', $parent->parent_id)
            ->where('student_id', $student->student_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'هذا الطالب مرتبط بحسابك بالفعل.');
        }

        // Link student
        DB::table('parent_students')->insert([
            'parent_id'  => $parent->parent_id,
            'student_id' => $student->student_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Clear session so it updates selection to the new child if needed
        session()->forget('selected_child_id');

        return back()->with('success', 'تم ربط الابن ' . $studentUser->full_name . ' بنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  SCHEDULE (الجدول الدراسي)
    // ────────────────────────────────────────────────────────────
    public function schedule()
    {
        $common = $this->getCommonData();
        $studentId = $common['selected_child_id'];

        if (!$studentId) {
            return $this->parentView('parent.schedule', ['schedules' => collect()]);
        }

        // Get student's enrolled course ids
        $enrolledCourseIds = DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('course_id');

        // Fetch weekly schedule for these courses
        $schedules = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
            ->leftJoin('teachers', 'schedules.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.user_id')
            ->whereIn('schedules.course_id', $enrolledCourseIds)
            ->select('schedules.*', 'courses.title as course_title', 'users.full_name as teacher_name')
            ->get()
            ->groupBy('day');

        return $this->parentView('parent.schedule', compact('schedules'));
    }

    // ────────────────────────────────────────────────────────────
    //  ASSIGNMENTS (الواجبات)
    // ────────────────────────────────────────────────────────────
    public function assignments()
    {
        $common = $this->getCommonData();
        $studentId = $common['selected_child_id'];

        if (!$studentId) {
            return $this->parentView('parent.assignments', ['assignments' => collect()]);
        }

        $courseIds = DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('course_id');

        $assignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->leftJoin('assignment_submissions', function ($join) use ($studentId) {
                $join->on('assignments.assignment_id', '=', 'assignment_submissions.assignment_id')
                     ->where('assignment_submissions.student_id', '=', $studentId);
            })
            ->whereIn('assignments.course_id', $courseIds)
            ->select(
                'assignments.assignment_id',
                'assignments.title',
                'courses.title as course_name',
                'assignments.due_date',
                'assignments.max_points',
                'assignments.file_path',
                'assignments.file_name',
                'assignment_submissions.submission_id',
                'assignment_submissions.grade',
                'assignment_submissions.submitted_at',
                'assignment_submissions.feedback',
                DB::raw('CASE
                    WHEN assignment_submissions.submission_id IS NOT NULL THEN "completed"
                    WHEN assignments.due_date < NOW() THEN "missed"
                    ELSE "pending"
                END as status')
            )
            ->orderBy('assignments.due_date', 'desc')
            ->get();

        return $this->parentView('parent.assignments', compact('assignments'));
    }

    // ────────────────────────────────────────────────────────────
    //  GRADES (الدرجات والأداء)
    // ────────────────────────────────────────────────────────────
    public function grades()
    {
        $common = $this->getCommonData();
        $studentId = $common['selected_child_id'];

        if (!$studentId) {
            return $this->parentView('parent.grades', ['grades' => collect(), 'overallAverage' => 0]);
        }

        $student = DB::table('students')->where('student_id', $studentId)->first();
        $internalStudentId = $student?->student_id ?? $studentId;

        $newGrades = $internalStudentId
            ? DB::table('grade_entries')
                ->join('grade_events', 'grade_entries.grade_event_id', '=', 'grade_events.id')
                ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
                ->leftJoin('programs', 'grade_events.program_id', '=', 'programs.id')
                ->where('grade_entries.student_id', $internalStudentId)
                ->whereNotNull('grade_entries.score')
                ->select(
                    DB::raw("COALESCE(courses.title, programs.name, 'تقييم عام') as course_title"),
                    DB::raw("CASE
                        WHEN grade_events.type = 'exam'  THEN CONCAT('امتحان: ', grade_events.title)
                        WHEN grade_events.type = 'quiz'  THEN CONCAT('مذاكرة: ', grade_events.title)
                        WHEN grade_events.type = 'oral'  THEN CONCAT('شفهي: ',   grade_events.title)
                        ELSE grade_events.title
                    END as exam_name"),
                    'grade_events.max_score',
                    'grade_entries.score',
                    DB::raw("DATE(grade_entries.created_at) as exam_date")
                )
                ->get()
            : collect();

        $oldGrades = DB::table('grades')
            ->leftJoin('exams', 'grades.exam_id', '=', 'exams.exam_id')
            ->leftJoin('courses', 'exams.course_id', '=', 'courses.course_id')
            ->where('grades.student_id', $studentId)
            ->select(
                DB::raw('COALESCE(courses.title, "مادة غير محددة") as course_title'),
                DB::raw('COALESCE(exams.exam_name, "اختبار") as exam_name'),
                DB::raw('COALESCE(exams.max_score, 100) as max_score'),
                'grades.score',
                'exams.exam_date'
            )
            ->get();

        $allGrades = $newGrades->merge($oldGrades);

        // Calculate average using percentage out of 4 GPA logic
        $totalScores = 0;
        $totalMax = 0;
        foreach ($allGrades as $g) {
            $totalScores += (float)$g->score;
            $totalMax += (float)($g->max_score ?? 100);
        }
        $overallAverage = $totalMax > 0 ? round(($totalScores / $totalMax) * 100, 1) : 0;

        $grades = $allGrades->groupBy('course_title');

        return $this->parentView('parent.grades', compact('grades', 'overallAverage'));
    }

    // ────────────────────────────────────────────────────────────
    //  PERMISSIONS (الأذونات والطلبات)
    // ────────────────────────────────────────────────────────────
    public function permissions()
    {
        $common = $this->getCommonData();
        $studentId = $common['selected_child_id'];
        $student = $common['selected_child'];

        if (!$studentId || !$student) {
            return $this->parentView('parent.permissions', ['requests' => collect()]);
        }

        $requests = DB::table('absence_requests')
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->parentView('parent.permissions', compact('requests', 'student'));
    }

    public function respondPermission(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);

        $absenceRequest = DB::table('absence_requests')->where('request_id', $id)->first();
        if (!$absenceRequest) {
            return back()->with('error', 'الطلب غير موجود.');
        }

        DB::table('absence_requests')
            ->where('request_id', $id)
            ->update([
                'status'     => $request->status,
                'updated_at' => now(),
            ]);

        $msg = $request->status === 'approved' ? 'تمت الموافقة على طلب الإذن بنجاح.' : 'تم رفض طلب الإذن.';
        return back()->with('success', $msg);
    }

    public function submitLeaveRequest(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'type'       => 'required|in:full_day,hourly',
            'date'       => 'required|date',
            'reason'     => 'required|string|min:3',
        ], [
            'student_id.required' => 'يجب اختيار الابن.',
            'reason.required' => 'سبب الإجازة مطلوب.'
        ]);

        $parent = $this->getParentRecord();
        $linked = DB::table('parent_students')
            ->where('parent_id', $parent->parent_id)
            ->where('student_id', $request->student_id)
            ->exists();

        if (!$linked) {
            return back()->with('error', 'هذا الطالب غير مرتبط بحسابك.');
        }

        $studentUser = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $request->student_id)
            ->select('users.user_id', 'users.full_name')
            ->first();

        $leaveId = DB::table('leave_requests')->insertGetId([
            'student_id' => $studentUser->user_id,
            'type'       => $request->type,
            'date'       => $request->date,
            'reason'     => $request->reason,
            'status'     => 'pending_hod',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Notify HOD
        $headUserId = DB::table('heads')->value('user_id');
        if ($headUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $headUserId,
                'title'      => 'طلب إجازة من ولي الأمر',
                'message'    => 'قدّم ولي أمر الطالب ' . ($studentUser->full_name ?? 'الطالب') . ' طلب إجازة بتاريخ ' . $request->date,
                'type'       => 'leave_request',
                'related_id' => $leaveId,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser(
                $headUserId,
                'طلب إجازة من ولي الأمر',
                'قدّم ولي أمر الطالب ' . ($studentUser->full_name ?? 'الطالب') . ' طلب إجازة بتاريخ ' . $request->date,
                ['type' => 'leave_request', 'related_id' => (string)$leaveId]
            );
        }

        return back()->with('success', 'تم تقديم طلب الإجازة بنجاح، وهو قيد المراجعة حالياً من قِبل إدارة القسم.');
    }

    // ────────────────────────────────────────────────────────────
    //  REPORTS (تقارير الأداء)
    // ────────────────────────────────────────────────────────────
    public function reports()
    {
        $common = $this->getCommonData();
        $studentId = $common['selected_child_id'];

        if (!$studentId) {
            return $this->parentView('parent.reports', ['reports' => collect()]);
        }

        // Get completed performance reports
        $completed = DB::table('performance_reports')
            ->where('student_id', $studentId)
            ->select('report_id as id', 'report_type', 'attendance_rate', 'average_grade', 'recommendations', 'created_at', DB::raw("'completed' as status"))
            ->get();

        // Get pending requests
        $pending = DB::table('report_requests')
            ->where('student_id', $studentId)
            ->where('status', 'pending')
            ->select('id', 'report_type', DB::raw('NULL as attendance_rate'), DB::raw('NULL as average_grade'), DB::raw('NULL as recommendations'), 'created_at', DB::raw("'pending' as status"))
            ->get();

        $reports = $completed->concat($pending)->sortByDesc('created_at');

        return $this->parentView('parent.reports', compact('reports'));
    }

    public function requestReport(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,student_id',
            'report_type' => 'required|in:academic,behavioral',
        ]);

        $parent = $this->getParentRecord();
        $studentId = $request->student_id;
        $reportType = $request->report_type;

        $linked = DB::table('parent_students')
            ->where('parent_id', $parent->parent_id)
            ->where('student_id', $studentId)
            ->exists();

        if (!$linked) {
            return back()->with('error', 'هذا الطالب غير مرتبط بحسابك.');
        }

        $student = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $studentId)
            ->select('students.*', 'users.full_name as student_name')
            ->first();

        // Check limit: 7 days for academic, 15 days for behavioral
        $limitDays = ($reportType === 'academic') ? 7 : 15;
        $recent = DB::table('report_requests')
            ->where('student_id', $studentId)
            ->where('report_type', $reportType)
            ->where('created_at', '>', now()->subDays($limitDays))
            ->first();

        if ($recent) {
            return back()->with('error', 'لا يمكنك طلب تقرير ' . ($reportType === 'academic' ? 'أكاديمي' : 'سلوكي') . ' أكثر من مرة خلال ' . $limitDays . ' يوماً.');
        }

        if ($reportType === 'academic') {
            // Generate Academic Report Automatically
            $totalAttendance = DB::table('attendance')->where('student_id', $studentId)->count();
            $presentCount    = DB::table('attendance')->where('student_id', $studentId)->where('status', 'present')->count();
            $attendanceRate  = ($totalAttendance > 0) ? round(($presentCount / $totalAttendance) * 100, 1) : 100;

            $averageGrade = DB::table('grades')->where('student_id', $studentId)->avg('score');
            $averageGrade = $averageGrade ? round($averageGrade, 1) : 0;

            $requestId = DB::table('report_requests')->insertGetId([
                'head_id'     => auth()->user()->user_id,
                'teacher_id'  => null,
                'student_id'  => $studentId,
                'report_type' => 'academic',
                'notes'       => 'طلب تقرير أكاديمي من النظام تلقائياً',
                'status'      => 'completed',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('performance_reports')->insert([
                'student_id'      => $studentId,
                'report_type'     => 'academic',
                'attendance_rate' => $attendanceRate,
                'average_grade'   => $averageGrade,
                'recommendations' => 'تم توليد هذا التقرير تلقائياً. معدل الطالب ' . $averageGrade . '% ونسبة الحضور ' . $attendanceRate . '%.',
                'generated_at'    => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return back()->with('success', 'تم إصدار التقرير الأكاديمي للابن ' . $student->student_name . ' بنجاح وعرضه بالجدول أدناه.');
        }

        // Behavioral Report requires Advisor Teacher
        // Find advisor teacher based on advisor_branch and advisor_year
        $studentData = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->where('students.student_id', $studentId)
            ->select('users.academic_year', 'programs.name as branch_name')
            ->first();

        $advisorTeacher = null;
        if ($studentData && $studentData->branch_name && $studentData->academic_year) {
            $academicYear = trim($studentData->academic_year);
            if ($academicYear === 'أولى' || $academicYear === 'السنة الأولى' || $academicYear === '1') {
                $academicYear = 'السنة الأولى';
            } elseif ($academicYear === 'ثانية' || $academicYear === 'السنة الثانية' || $academicYear === '2') {
                $academicYear = 'السنة الثانية';
            } elseif ($academicYear === 'ثالثة' || $academicYear === 'السنة الثالثة' || $academicYear === '3') {
                $academicYear = 'السنة الثالثة';
            } elseif ($academicYear === 'رابعة' || $academicYear === 'السنة الرابعة' || $academicYear === '4') {
                $academicYear = 'السنة الرابعة';
            } elseif ($academicYear === 'خامسة' || $academicYear === 'السنة الخامسة' || $academicYear === '5') {
                $academicYear = 'السنة الخامسة';
            }

            $advisorTeacher = DB::table('teachers')
                ->where('advisor_branch', $studentData->branch_name)
                ->where('advisor_year', $academicYear)
                ->first();
        }

        if (!$advisorTeacher) {
            return back()->with('error', 'لم يتم تعيين مربي دائم لصف الطالب حالياً لتلقي الطلب السلوكي.');
        }

        $requestId = DB::table('report_requests')->insertGetId([
            'head_id'     => auth()->user()->user_id,
            'teacher_id'  => $advisorTeacher->teacher_id,
            'student_id'  => $studentId,
            'report_type' => 'behavioral',
            'notes'       => 'طلب تقرير سلوكي من ولي الأمر',
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Notify teacher
        $teacherUserId = DB::table('teachers')->where('teacher_id', $advisorTeacher->teacher_id)->value('user_id');
        if ($teacherUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $teacherUserId,
                'title'      => 'طلب تقرير سلوكي جديد',
                'message'    => 'طلب ولي أمر الطالب ' . $student->student_name . ' تقريراً سلوكياً، يرجى تعبئته.',
                'type'       => 'report',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser(
                $teacherUserId,
                'طلب تقرير سلوكي جديد',
                'طلب ولي أمر الطالب ' . $student->student_name . ' تقريراً سلوكياً، يرجى تعبئته.',
                ['type' => 'report', 'related_id' => (string)$requestId]
            );
        }

        return back()->with('success', 'تم إرسال طلب التقرير السلوكي للمربي بنجاح. سيظهر بالجدول فور تعبئته.');
    }

    // ────────────────────────────────────────────────────────────
    //  PROFILE (الملف الشخصي)
    // ────────────────────────────────────────────────────────────
    public function profile()
    {
        return $this->parentView('parent.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'phone'     => 'required|string|max:20|unique:users,phone,' . $user->user_id . ',user_id',
        ], [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.'
        ]);

        DB::table('users')->where('user_id', $user->user_id)->update([
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'updated_at'=> now()
        ]);

        return back()->with('success', 'تم تحديث البيانات الشخصية بنجاح!');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        if (!session('parent_otp_verified')) {
            return back()->withErrors(['current_password' => 'يرجى التحقق من هويتك أولاً عبر رمز التحقق (OTP).']);
        }

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 6 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.'
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        DB::table('users')->where('user_id', $user->user_id)->update([
            'password'   => Hash::make($request->password),
            'updated_at' => now()
        ]);

        session()->forget('parent_otp_verified');

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح!');
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
            'parent_profile_otp'          => $otp,
            'parent_pending_profile_data' => $request->only(['full_name', 'phone', 'email', 'new_password'])
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

        if (session('parent_profile_otp') == $request->otp) {
            $user = Auth::user();
            $data = session('parent_pending_profile_data');

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

            session()->forget(['parent_profile_otp', 'parent_pending_profile_data']);

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
    //  MESSAGES / CHAT SYSTEM
    // ────────────────────────────────────────────────────────────
    public function messages()
    {
        $currentUserId = Auth::id();

        // Allowed targets for parent: Admin, HOD, Affairs, Teachers
        $allUsers = \App\Models\User::where('user_id', '!=', $currentUserId)
            ->whereIn('role', ['admin', 'head', 'affairs', 'teacher'])
            ->get();

        return $this->parentView('parent.messages', compact('allUsers'));
    }

    public function getContacts()
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

        $contactsRaw = \App\Models\User::whereIn('user_id', $conversations)->get();

        $contacts = [];
        foreach ($contactsRaw as $c) {
            $unread = \App\Models\Message::where('sender_id', $c->user_id)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->count();

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
            return response()->json(['status' => 'success', 'data' => $message], 201);
        }

        return redirect()->back()->with('success', 'تم إرسال الرسالة بنجاح!');
    }

    public function updateMessage(Request $request, $id)
    {
        $message = \App\Models\Message::findOrFail($id);
        
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $message->update([
            'message' => $request->message,
        ]);

        return response()->json(['status' => 'success', 'data' => $message]);
    }

    public function deleteMessage($id)
    {
        $message = \App\Models\Message::findOrFail($id);
        
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        $message->delete();

        return response()->json(['status' => 'success', 'message' => 'تم حذف الرسالة بنجاح']);
    }
}
