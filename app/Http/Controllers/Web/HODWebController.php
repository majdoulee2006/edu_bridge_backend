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
        $announcements = DB::table('announcements')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // إحصائيات رئيس القسم
        $teachersCount = \App\Models\User::where('role_id', 2)->count();
        $studentsCount = \App\Models\User::where('role_id', 3)->count();
        $coursesCount = \App\Models\Course::count();

        return view('hod.dashboard', compact('announcements', 'teachersCount', 'studentsCount', 'coursesCount'));
    }

    public function notifications()
    {
        $notifications = DB::table('notifications')
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        // تحديد كل الإشعارات كمقروءة
        DB::table('notifications')
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'updated_at' => now()]);

        return view('hod.notifications', compact('notifications'));
    }

    /**
     * إرسال إشعار لمجموعة من المستخدمين
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'target'  => 'required|in:students,students_teachers,all',
        ]);

        $category = 'administrative';

        // تحديد المستخدمين المستهدفين
        $query = DB::table('users')->where('status', 'active');

        if ($request->target === 'students') {
            $query->where('role_id', 3);
        } elseif ($request->target === 'students_teachers') {
            $query->whereIn('role_id', [2, 3]);
        }
        // 'all' → كل المستخدمين

        $users = $query->get(['user_id', 'device_token']);

        $senderId = auth()->id();
        $now = now();

        foreach ($users as $u) {
            DB::table('notifications')->insert([
                'user_id'    => $u->user_id,
                'sender_id'  => $senderId,
                'title'      => $request->title,
                'message'    => $request->message,
                'type'       => $category === 'academic' ? 'academic' : 'administrative',
                'category'   => $category,
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // FCM
            if (!empty($u->device_token)) {
                \App\Services\FcmService::send($u->device_token, $request->title, $request->message, [
                    'type'     => $category,
                    'category' => $category,
                ]);
            }
        }

        return redirect()->back()->with('success', 'تم إرسال الإشعار بنجاح لـ ' . $users->count() . ' مستخدم!');
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
        $allLeaves = DB::table('leave_requests')
            ->join('users', 'leave_requests.student_id', '=', 'users.user_id')
            ->leftJoin('students', 'students.user_id', '=', 'users.user_id')
            ->select(
                'leave_requests.*',
                'users.full_name as student_name',
                'students.level',
                'students.student_code'
            )
            ->orderBy('leave_requests.created_at', 'desc')
            ->get();

        return view('hod.leaves', compact('allLeaves'));
    }

    /**
     * تحديث حالة الإجازة
     */
    public function updateLeaveStatus(Request $request, $id)
    {
        $status = $request->input('status'); // 'approved' or 'rejected'

        $leaveRequest = DB::table('leave_requests')->where('id', $id)->first();

        DB::table('leave_requests')
            ->where('id', $id)
            ->update(['status' => $status, 'updated_at' => now()]);

        // إشعار الطالب بالنتيجة
        if ($leaveRequest && $leaveRequest->student_id) {
            $title   = $status === 'approved' ? 'تمت الموافقة على طلب الإجازة' : 'تم رفض طلب الإجازة';
            $message = $status === 'approved'
                ? 'وافق رئيس القسم على طلب إجازتك بتاريخ ' . $leaveRequest->date
                : 'تم رفض طلب إجازتك بتاريخ ' . $leaveRequest->date . ' من قِبل رئيس القسم';

            DB::table('notifications')->insert([
                'user_id'    => $leaveRequest->student_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'leave_request',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'تم تحديث حالة الإجازة بنجاح.');
    }

    /**
     * Accounts (إدارة الحسابات)
     */
    public function accounts(Request $request)
    {
        // 1. المدربين
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'teachers.specialization', 'users.user_id', 'users.full_name', 'users.username', 'users.email', 'users.phone', 'users.department')
            ->get();

        foreach ($teachers as $teacher) {
            $teacher->courses = DB::table('course_teachers')
                ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
                ->where('course_teachers.teacher_id', $teacher->teacher_id)
                ->pluck('courses.title');
                
            // Check if teacher is an advisor
            $advisorCourse = DB::table('course_teachers')
                ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
                ->where('course_teachers.teacher_id', $teacher->teacher_id)
                ->where('course_teachers.role', 'advisor')
                ->select('courses.course_id', 'courses.title')
                ->first();
                
            $teacher->is_advisor = $advisorCourse ? true : false;
            $teacher->advisor_course_title = $advisorCourse ? $advisorCourse->title : null;
        }

        // 2. الطلاب
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'students.student_code', 'students.level', 'students.birth_date', 'users.user_id', 'users.full_name', 'users.university_id', 'users.email', 'users.phone', 'users.department', 'users.gender')
            ->get();
            
        // 3. جلب كل الدورات لتعيين المربي
        $all_courses = DB::table('courses')->select('course_id', 'title')->get();

        // 3. الأهل
        $parents = DB::table('parents')
            ->join('users', 'parents.user_id', '=', 'users.user_id')
            ->select('parents.parent_id', 'users.user_id', 'users.full_name', 'users.username', 'users.email', 'users.phone')
            ->get();

        foreach ($parents as $parent) {
            $parent->children = DB::table('parent_students')
                ->join('students', 'parent_students.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('parent_students.parent_id', $parent->parent_id)
                ->pluck('users.full_name');
        }

        // 4. بيانات النماذج
        $departments = DB::table('departments')->orderBy('name')->get();
        $courses     = DB::table('courses')->orderBy('title')->get();

        return view('hod.accounts', compact('teachers', 'students', 'parents', 'departments', 'courses', 'all_courses'));
    }

    /**
     * إضافة مدرب جديد — مطابق للأدمن
     */
    public function storeTeacher(Request $request)
    {
        $request->validate([
            'full_name'      => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'required|email|unique:users,email|max:255',
            'department'     => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'password'       => 'required|string|min:6|confirmed',
            'courses'        => 'nullable|array',
        ], [
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        // توليد username تلقائياً من الإيميل
        $base     = strtolower(explode('@', $request->email)[0]);
        $username = $base;
        $i = 1;
        while (DB::table('users')->where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        $userId = DB::table('users')->insertGetId([
            'role_id'        => 2,
            'full_name'      => $request->full_name,
            'username'       => $username,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'department'     => $request->department,
            'password'       => bcrypt($request->password),
            'status'         => 'active',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $teacherId = DB::table('teachers')->insertGetId([
            'user_id'        => $userId,
            'specialization' => $request->specialization,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        if ($request->filled('courses')) {
            foreach ($request->courses as $courseId) {
                DB::table('course_teachers')->insert([
                    'teacher_id' => $teacherId,
                    'course_id'  => $courseId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'تمت إضافة حساب المدرب بنجاح!');
    }

    public function assignAdvisor(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,teacher_id',
            'course_id' => 'nullable|exists:courses,course_id',
            'action' => 'required|in:assign,remove'
        ]);

        $teacherId = $request->input('teacher_id');
        $action = $request->input('action');

        // Remove any existing advisor role for this teacher
        DB::table('course_teachers')
            ->where('teacher_id', $teacherId)
            ->where('role', 'advisor')
            ->delete();

        if ($action === 'assign') {
            $courseId = $request->input('course_id');
            if (!$courseId) {
                return back()->with('error', 'الرجاء اختيار الدورة لتفعيل المربي.');
            }

            // Also ensure no one else is advisor for this course (1-to-1 rule)
            DB::table('course_teachers')
                ->where('course_id', $courseId)
                ->where('role', 'advisor')
                ->delete();

            // Insert new advisor role
            DB::table('course_teachers')->insert([
                'course_id' => $courseId,
                'teacher_id' => $teacherId,
                'role' => 'advisor',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('success', 'تم تعيين المربي بنجاح.');
        }

        return back()->with('success', 'تم إزالة صفة المربي عن المعلم.');
    }

    /**
     * إضافة طالب جديد — مطابق للأدمن
     */
    public function storeStudent(Request $request)
    {
        $request->validate([
            'full_name'     => 'required|string|max:255',
            'university_id' => 'required|string|unique:users,university_id|max:255',
            'email'         => 'required|email|unique:users,email|max:255',
            'phone'         => 'nullable|string|max:20',
            'department'    => 'required|string|max:255',
            'level'         => 'required|string|max:255',
            'birth_date'    => 'required|date',
            'gender'        => 'required|in:ذكر,أنثى',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'university_id.unique' => 'الرقم الجامعي مستخدم بالفعل.',
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id'       => 3,
            'full_name'     => $request->full_name,
            'username'      => $request->university_id,
            'university_id' => $request->university_id,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'department'    => $request->department,
            'gender'        => $request->gender,
            'birth_date'    => $request->birth_date,
            'academic_year' => $request->level,
            'password'      => bcrypt($request->password),
            'status'        => 'active',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('students')->insert([
            'user_id'      => $userId,
            'student_code' => $request->university_id,
            'level'        => $request->level,
            'birth_date'   => $request->birth_date,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()->back()->with('success', 'تمت إضافة حساب الطالب بنجاح!');
    }

    /**
     * إضافة ولي أمر جديد — مطابق للأدمن
     */
    public function storeParent(Request $request)
    {
        $request->validate([
            'full_name'               => 'required|string|max:255',
            'phone'                   => 'required|string|max:20',
            'username'                => 'required|string|unique:users,username|max:255',
            'email'                   => 'required|email|unique:users,email|max:255',
            'children_university_ids' => 'nullable|array',
            'password'                => 'required|string|min:6|confirmed',
        ], [
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id'    => 4,
            'full_name'  => $request->full_name,
            'username'   => $request->username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => bcrypt($request->password),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $parentId = DB::table('parents')->insertGetId([
            'user_id'    => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->filled('children_university_ids')) {
            foreach (array_filter($request->children_university_ids) as $universityId) {
                $student = DB::table('students')
                    ->join('users', 'students.user_id', '=', 'users.user_id')
                    ->where('students.student_code', $universityId)
                    ->select('students.student_id')
                    ->first();
                if (!$student) continue;
                DB::table('parent_students')->insert([
                    'parent_id'    => $parentId,
                    'student_id'   => $student->student_id,
                    'relationship' => 'والد / ولي أمر',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'تمت إضافة حساب ولي الأمر بنجاح!');
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
     * Show form to create an announcement
     */
    public function showCreateAnnouncementForm()
    {
        // Get list of courses for optional course-specific announcement
        $courses = \App\Models\Course::select('course_id', 'title')->get();
        return view('hod.create_announcement', compact('courses'));
    }

    /**
     * Store a new announcement
     */
    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'required|string',
            'type'             => 'required|in:general,course_specific',
            'course_id'        => 'nullable|exists:courses,course_id',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url'         => 'nullable|url|max:500',
            'target_audience'  => 'nullable|in:all,students,teachers',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $announcement = \App\Models\Announcement::create([
            'user_id'          => auth()->id(),
            'title'            => $request->title,
            'content'          => $request->content,
            'type'             => $request->type,
            'course_id'        => $request->type === 'course_specific' ? $request->course_id : null,
            'target_audience'  => $request->input('target_audience', 'all'),
            'link_url'         => $request->input('link_url'),
            'image'            => $imagePath,
        ]);

        // ── إشعار FCM للطلاب والمعلمين ──────────────────────────────
        $target    = $request->input('target_audience', 'all');
        $roleIds   = match($target) {
            'students' => [3],
            'teachers' => [2],
            default    => [2, 3],
        };
        $userIds = \App\Models\User::whereIn('role_id', $roleIds)
            ->where('status', 'active')
            ->pluck('user_id');

        $now = now();
        $notifRows = $userIds->map(fn($uid) => [
            'user_id'    => $uid,
            'sender_id'  => auth()->id(),
            'title'      => 'إعلان جديد من رئيس القسم',
            'message'    => $request->title,
            'type'       => 'announcement',
            'category'   => 'administrative',
            'related_id' => $announcement->id ?? $announcement->announcement_id,
            'is_read'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($notifRows)) {
            \Illuminate\Support\Facades\DB::table('notifications')->insert($notifRows);
            foreach ($userIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, 'إعلان جديد من رئيس القسم', $request->title, [
                    'type' => 'announcement',
                ]);
            }
        }

        return redirect()->route('hod.dashboard')
                         ->with('success', 'تم إضافة الإعلان بنجاح!');
    }

    public function editAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', auth()->id())->firstOrFail();
        return view('hod.announcement_edit', compact('announcement'));
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', auth()->id())->firstOrFail();
        $request->validate(['title' => 'required|string|max:255', 'content' => 'required|string']);
        $updates = ['title' => $request->title, 'content' => $request->content, 'updated_at' => now()];
        if ($request->hasFile('image')) {
            if ($announcement->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
            $updates['image'] = $request->file('image')->store('announcements', 'public');
        }
        $announcement->update($updates);
        return redirect()->route('hod.dashboard')->with('success', 'تم تحديث الإعلان!');
    }

    public function deleteAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', auth()->id())->firstOrFail();
        if ($announcement->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
        $announcement->delete();
        return redirect()->route('hod.dashboard')->with('success', 'تم حذف الإعلان.');
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
            'period' => 'required|integer|between:1,5',
            'department' => 'required|string',
            'year' => 'required|string',
            'room' => 'required|string',
        ]);

        // تعيين أوقات الحصص بناءً على الرقم
        $periods = [
            1 => ['start' => '08:00', 'end' => '09:30'],
            2 => ['start' => '09:30', 'end' => '11:00'],
            3 => ['start' => '11:00', 'end' => '12:30'],
            4 => ['start' => '12:30', 'end' => '14:00'],
            5 => ['start' => '14:00', 'end' => '15:30'],
        ];

        $startTime = $periods[$request->period]['start'];
        $endTime = $periods[$request->period]['end'];
        
        // دمج القسم والسنة في حقل الشعبة
        $classGroup = $request->department . ' - ' . $request->year;

        // التحقق من تضارب المواعيد للمدرب (لا يمكن إضافة نفس الأستاذ بنفس الوقت)
        if ($request->teacher_id) {
            $conflict = DB::table('schedules')
                ->where('teacher_id', $request->teacher_id)
                ->where('day', $request->day)
                ->where('start_time', 'like', $startTime . '%')
                ->first();

            if ($conflict) {
                return redirect()->back()->with('error', 'عذراً! لا يمكن إضافة الحصة. هذا الأستاذ لديه حصة أخرى في نفس اليوم ونفس الوقت في قسم آخر.');
            }
        }

        DB::table('schedules')->insert([
            'course_id' => $request->course_id,
            'teacher_id' => $request->teacher_id,
            'day' => $request->day,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room' => $request->room,
            'class_group' => $classGroup,
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
            ->where('messages.sender_id', \Illuminate\Support\Facades\Auth::id())
            ->orWhere('messages.receiver_id', \Illuminate\Support\Facades\Auth::id())
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
            'receiver_id' => 'required',
            'message' => 'required',
        ]);

        DB::table('messages')->insert([
            'sender_id' => \Illuminate\Support\Facades\Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
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
    public function createReport()
    {
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'users.full_name')
            ->get();

        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'users.full_name')
            ->get();

        return view('hod.report_create', compact('teachers', 'students'));
    }

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

        // رئيس القسم الحالي
        $head = DB::table('heads')->where('user_id', auth()->id() ?? 0)->first();

        // نحتاج teacher_id — نأخذ من الطلب إذا أُرسل أو أول مدرب
        $teacherId = $request->input('teacher_id')
            ?? DB::table('teachers')->value('teacher_id');

        $requestId = DB::table('report_requests')->insertGetId([
            'head_id'     => $head->id ?? null,
            'teacher_id'  => $teacherId,
            'student_id'  => $request->student_id,
            'report_type' => $request->report_type,
            'notes'       => $request->recommendations,
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // إشعار المدرب
        $teacherUserId = DB::table('teachers')->where('teacher_id', $teacherId)->value('user_id');
        $studentName   = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $request->student_id)
            ->value('users.full_name') ?? 'طالب';

        $typLabel = $request->report_type === 'behavioral' ? 'سلوكي' : 'أكاديمي';
        $title    = 'طلب تقرير من رئيس القسم';
        $message  = 'طُلب منك تقرير ' . $typLabel . ' عن الطالب ' . $studentName;

        if ($teacherUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $teacherUserId,
                'sender_id'  => auth()->id(),
                'title'      => $title,
                'message'    => $message,
                'type'       => 'report_request',
                'related_id' => $requestId,
                'category'   => 'academic',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser($teacherUserId, $title, $message, [
                'type'       => 'report_request',
                'request_id' => (string) $requestId,
            ]);
        }

        return redirect()->back()->with('success', 'تم إرسال طلب التقرير للمدرب بنجاح!');
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
