<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        $grades = DB::table('grades')
            ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->where('grades.student_id', $studentId)
            ->select('grades.*', 'exams.exam_name', 'exams.max_score', 'courses.title as course_title', 'exams.exam_date')
            ->orderBy('exams.exam_date', 'desc')
            ->get()
            ->groupBy('course_title');

        $overallAverage = DB::table('grades')->where('student_id', $studentId)->avg('score');
        $overallAverage = $overallAverage ? round($overallAverage, 1) : 0;

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

        // Get student's user record (leave_requests table uses user_id as student_id)
        $studentUserId = DB::table('students')->where('student_id', $studentId)->value('user_id');

        $requests = DB::table('leave_requests')
            ->where('student_id', $studentUserId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->parentView('parent.permissions', compact('requests', 'student'));
    }

    public function respondPermission(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);

        $leaveRequest = DB::table('leave_requests')->where('id', $id)->first();
        if (!$leaveRequest) {
            return back()->with('error', 'الطلب غير موجود.');
        }

        if ($request->status === 'approved') {
            DB::table('leave_requests')
                ->where('id', $id)
                ->update(['status' => 'pending_hod', 'updated_at' => now()]);

            $studentUser = DB::table('users')->where('user_id', $leaveRequest->student_id)->first();
            $studentName = $studentUser->full_name ?? 'الطالب';

            $headUserId = DB::table('heads')->value('user_id')
                ?? DB::table('users')->where('role_id', 5)->value('user_id');
                
            if ($headUserId) {
                DB::table('notifications')->insert([
                    'user_id'    => $headUserId,
                    'title'      => 'طلب إجازة بانتظار موافقتك',
                    'message'    => 'وافق ولي أمر الطالب ' . $studentName . ' على طلب إجازة بتاريخ ' . $leaveRequest->date . '، يرجى مراجعته',
                    'type'       => 'leave_request',
                    'related_id' => $id,
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return back()->with('success', 'تمت الموافقة على الإجازة وإحالتها لرئيس القسم.');
        } else {
            DB::table('leave_requests')
                ->where('id', $id)
                ->update(['status' => 'rejected', 'updated_at' => now()]);

            if ($leaveRequest->student_id) {
                $typeText = $leaveRequest->type === 'hourly' ? 'الساعية' : 'اليومية';
                DB::table('notifications')->insert([
                    'user_id'    => $leaveRequest->student_id,
                    'title'      => 'تم رفض طلب الإجازة',
                    'message'    => 'تم رفض طلب إجازتك ' . $typeText . ' بتاريخ ' . $leaveRequest->date . ' من قِبل ولي الأمر',
                    'type'       => 'leave_request',
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return back()->with('success', 'تم رفض طلب الإجازة بنجاح.');
        }
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
            $advisorTeacher = DB::table('teachers')
                ->where('advisor_branch', $studentData->branch_name)
                ->where('advisor_year', $studentData->academic_year)
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

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح!');
    }
}
