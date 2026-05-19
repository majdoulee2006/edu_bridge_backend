<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HODWebController extends Controller
{
    /**
     * Show Login Form
     */
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->role_id == 5) {
            return redirect('/hod/dashboard');
        }
        return view('auth.hod-login');
    }

    /**
     * Handle Login Request
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required', // يمكن أن يكون إيميل أو يوزرنيم
            'password' => 'required',
        ]);

        $login_type = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL ) 
            ? 'email' 
            : 'username';

        $credentials = [
            $login_type => $request->input('login'),
            'password' => $request->input('password')
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            // 5 represents Head of Department
            if ($user->role_id == 5) {
                return redirect('/hod/dashboard');
            } else {
                Auth::logout();
                return back()->withErrors(['login' => 'ليس لديك صلاحية الدخول كـ رئيس قسم.']);
            }
        }

        return back()->withErrors(['login' => 'البيانات المدخلة غير صحيحة.']);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/hod/login');
    }

    /**
     * Dashboard (الرئيسية)
     */
    public function dashboard()
    {
        // آخر الإعلانات والأخبار
        $announcements = DB::table('announcements')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('hod.dashboard', compact('announcements'));
    }

    /**
     * Profile (الملف الشخصي)
     */
    public function profile()
    {
        // استخدام المستخدم الحالي، وفي حال لم يكن مسجلاً نأخذ أول مستخدم للتجربة
        $user = auth()->user() ?? User::first();
        
        // معلومات القسم المرتبط برئيس القسم
        $departmentName = 'غير محدد';
        if ($user) {
            $headInfo = DB::table('heads')->where('user_id', $user->user_id)->first();
            if ($headInfo) {
                $dept = DB::table('departments')->where('department_id', $headInfo->department_id)->first();
                $departmentName = $dept ? $dept->name : 'غير محدد';
            }
        }

        return view('hod.profile', compact('user', 'departmentName'));
    }

    /**
     * تحديث الملف الشخصي
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
        ]);

        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }

        if ($request->has('birth_date')) {
            $user->birth_date = $request->birth_date;
        }

        $user->save();

        return redirect()->back()->with('success', 'تم تحديث البيانات بنجاح!');
    }

    /**
     * إرسال رمز التحقق OTP
     */
    public function sendOTP(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'email' => 'nullable|email|max:255|unique:users,email,' . Auth::id() . ',user_id',
            'password' => 'nullable|string|min:6',
        ]);

        $otp = rand(1000, 9999);
        
        session([
            'profile_otp' => $otp,
            'pending_profile_data' => $request->only(['phone', 'birth_date', 'email', 'password'])
        ]);

        return response()->json([
            'success' => true,
            'otp' => $otp,
            'message' => 'تم إرسال رمز التحقق بنجاح!'
        ]);
    }

    /**
     * التحقق من الـ OTP وحفظ التغييرات
     */
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);

        if (session('profile_otp') == $request->otp) {
            $user = Auth::user();
            $data = session('pending_profile_data');

            if (isset($data['phone'])) {
                $user->phone = $data['phone'];
            }

            if (isset($data['birth_date'])) {
                $user->birth_date = $data['birth_date'];
            }

            if (isset($data['email'])) {
                $user->email = $data['email'];
            }

            if (isset($data['password'])) {
                $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
            }

            $user->save();

            session()->forget(['profile_otp', 'pending_profile_data']);

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

    /**
     * Leaves (طلبات الإجازة)
     */
    public function leaves()
    {
        // جلب الإجازات المعلقة اليومية والساعية مع بيانات المدربين
        $pendingLeaves = DB::table('leave_requests')
            ->join('teachers', 'leave_requests.teacher_id', '=', 'teachers.teacher_id')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('leave_requests.*', 'users.full_name as teacher_name', 'teachers.specialization')
            ->where('leave_requests.status', 'pending')
            ->orderBy('leave_requests.created_at', 'desc')
            ->get();

        return view('hod.leaves', compact('pendingLeaves'));
    }

    /**
     * تحديث حالة الإجازة
     */
    public function updateLeaveStatus(Request $request, $id)
    {
        $status = $request->input('status'); // 'approved' or 'rejected'
        
        DB::table('leave_requests')
            ->where('id', $id)
            ->update(['status' => $status, 'updated_at' => now()]);
            
        return redirect()->back()->with('success', 'تم تحديث حالة الإجازة بنجاح.');
    }

    /**
     * Accounts (إدارة الحسابات)
     */
    public function accounts(Request $request)
    {
        // 1. جلب المدربين
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'teachers.specialization', 'users.user_id', 'users.full_name', 'users.username', 'users.email', 'users.phone')
            ->get();
             
        foreach ($teachers as $teacher) {
            $teacher->courses = DB::table('course_teachers')
                ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
                ->where('course_teachers.teacher_id', $teacher->teacher_id)
                ->pluck('courses.title');
        }

        // 2. جلب الطلاب
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'students.student_code', 'students.level', 'students.birth_date', 'users.user_id', 'users.full_name', 'users.username', 'users.email', 'users.phone')
            ->get();

        return view('hod.accounts', compact('teachers', 'students'));
    }

    /**
     * إضافة مدرب جديد
     */
    public function storeTeacher(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'email' => 'nullable|email|unique:users,email|max:255',
            'phone' => 'nullable|string|max:20',
            'specialization' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id' => 2, // teacher
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teachers')->insert([
            'user_id' => $userId,
            'specialization' => $request->specialization,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تمت إضافة حساب المدرب بنجاح!');
    }

    /**
     * إضافة طالب جديد
     */
    public function storeStudent(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'email' => 'nullable|email|unique:users,email|max:255',
            'phone' => 'nullable|string|max:20',
            'student_code' => 'required|string|unique:students,student_code|max:255',
            'level' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'password' => 'required|string|min:6',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id' => 3, // student
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('students')->insert([
            'user_id' => $userId,
            'student_code' => $request->student_code,
            'level' => $request->level,
            'birth_date' => $request->birth_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تمت إضافة حساب الطالب بنجاح!');
    }

    /**
     * حذف حساب مستخدم
     */
    public function deleteAccount($id)
    {
        DB::table('users')->where('user_id', $id)->delete();
        return redirect()->back()->with('success', 'تم حذف الحساب بنجاح.');
    }

    /**
     * Organization (التنظيم / الجداول)
     */
    public function organization()
    {
        // 1. جلب الجدول الدراسي الأسبوعي
        $schedules = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
            ->leftJoin('teachers', 'schedules.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('schedules.*', 'courses.title as course_title', 'users.full_name as teacher_name')
            ->orderByRaw("CASE day WHEN 'Sunday' THEN 1 WHEN 'Monday' THEN 2 WHEN 'Tuesday' THEN 3 WHEN 'Wednesday' THEN 4 WHEN 'Thursday' THEN 5 WHEN 'Friday' THEN 6 WHEN 'Saturday' THEN 7 ELSE 8 END")
            ->orderBy('start_time')
            ->get();

        // 2. جلب جدول الامتحانات
        $exams = DB::table('exams')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->select('exams.*', 'courses.title as course_title')
            ->orderBy('exam_date')
            ->get();

        // 3. جلب المدربين والكورسات لملء نماذج الإضافة
        $courses = DB::table('courses')->select('course_id', 'title')->get();
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'users.full_name')
            ->get();

        return view('hod.organization', compact('schedules', 'exams', 'courses', 'teachers'));
    }

    /**
     * حفظ حصة دراسية جديدة
     */
    public function storeSchedule(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'teacher_id' => 'nullable|exists:teachers,teacher_id',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'room' => 'required|string',
            'class_group' => 'nullable|string|max:100',
        ]);

        DB::table('schedules')->insert([
            'course_id' => $request->course_id,
            'teacher_id' => $request->teacher_id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'room' => $request->room,
            'class_group' => $request->class_group,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تمت إضافة الحصة الدراسية بنجاح!');
    }

    /**
     * حذف حصة دراسية
     */
    public function deleteSchedule($id)
    {
        DB::table('schedules')->where('schedule_id', $id)->delete();
        return redirect()->back()->with('success', 'تم حذف الحصة الدراسية بنجاح.');
    }

    /**
     * حفظ امتحان جديد
     */
    public function storeExam(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'exam_name' => 'required|string|max:255',
            'exam_date' => 'required',
            'max_score' => 'required|integer',
            'room' => 'nullable|string',
            'class_group' => 'nullable|string|max:100',
        ]);

        DB::table('exams')->insert([
            'course_id' => $request->course_id,
            'exam_name' => $request->exam_name,
            'exam_date' => $request->exam_date,
            'max_score' => $request->max_score,
            'room' => $request->room,
            'class_group' => $request->class_group,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تمت إضافة الامتحان بنجاح!');
    }

    /**
     * حذف امتحان
     */
    public function deleteExam($id)
    {
        DB::table('exams')->where('exam_id', $id)->delete();
        return redirect()->back()->with('success', 'تم حذف الامتحان بنجاح.');
    }

    /**
     * Messages (الرسائل)
     */
    public function messages()
    {
        $messages = DB::table('messages')
            ->leftJoin('users as senders', 'messages.sender_id', '=', 'senders.user_id')
            ->leftJoin('users as receivers', 'messages.receiver_id', '=', 'receivers.user_id')
            ->select('messages.*', 'senders.full_name as sender_name', 'receivers.full_name as receiver_name')
            ->orderBy('created_at', 'desc')
            ->get();

        $users = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.role_id')
            ->select('users.user_id', 'users.full_name', 'roles.name as role_name')
            ->get();

        return view('hod.messages', compact('messages', 'users'));
    }

    /**
     * إرسال رسالة جديدة
     */
    public function storeMessage(Request $request)
    {
        $request->validate([
            'sender_id' => 'required',
            'receiver_id' => 'required',
            'message' => 'required|string',
        ]);

        DB::table('messages')->insert([
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم إرسال الرسالة بنجاح!');
    }

    /**
     * حذف رسالة
     */
    public function deleteMessage($id)
    {
        DB::table('messages')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'تم حذف الرسالة بنجاح.');
    }

    /**
     * Reports (طلب التقارير)
     */
    public function reports()
    {
        // جلب قائمة المدربين والطلاب لإنشاء تقرير
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'users.full_name')
            ->get();

        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'users.full_name')
            ->get();

        // جلب التقارير المنشأة لعرضها في جدول
        $reports = DB::table('performance_reports')
            ->join('students', 'performance_reports.student_id', '=', 'students.student_id')
            ->join('users as student_users', 'students.user_id', '=', 'student_users.user_id')
            ->select('performance_reports.*', 'student_users.full_name as student_name')
            ->orderBy('performance_reports.created_at', 'desc')
            ->get();

        return view('hod.reports', compact('teachers', 'students', 'reports'));
    }

    /**
     * حفظ طلب تقرير الأداء
     */
    public function storeReport(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'report_type' => 'required|in:academic,behavioral',
            'recommendations' => 'nullable|string',
        ]);

        DB::table('performance_reports')->insert([
            'student_id' => $request->student_id,
            'report_type' => $request->report_type,
            'attendance_rate' => rand(75, 100),
            'average_grade' => rand(60, 99),
            'recommendations' => $request->recommendations ?? 'تم إنشاء التقرير بناءً على طلب رئيس القسم.',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم طلب وإنشاء التقرير بنجاح!');
    }

    /**
     * حذف تقرير
     */
    public function deleteReport($id)
    {
        DB::table('performance_reports')->where('report_id', $id)->delete();
        return redirect()->back()->with('success', 'تم حذف التقرير بنجاح.');
    }
}
