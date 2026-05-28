<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Admin;

class AdminWebController extends Controller
{
    // ────────────────────────────────────────────────────────────
    //  AUTHENTICATION
    // ────────────────────────────────────────────────────────────

    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->role_id == 1) {
            return redirect('/admin/dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|min:6',
        ], [
            'login.required'    => 'اسم المستخدم أو البريد الإلكتروني مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min'      => 'يجب ألا تقل كلمة المرور عن 6 أحرف.',
        ]);

        // Support login via email or username
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$loginField => $request->login, 'password' => $request->password])) {
            $user = Auth::user();
            if ($user->role_id != 1) {
                Auth::logout();
                return back()->withErrors(['login' => 'عذراً! هذا الحساب لا يملك صلاحيات الإدارة.']);
            }
            $request->session()->regenerate();
            return redirect('/admin/dashboard');
        }

        return back()->withInput()->withErrors(['login' => 'البيانات المدخلة غير صحيحة، يرجى المحاولة مرة أخرى.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }

    // ────────────────────────────────────────────────────────────
    //  DASHBOARD & PAGES
    // ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        // 1. Fetch latest announcements
        $announcements = DB::table('announcements')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 2. Fetch upcoming events (from calendar events)
        $events = DB::table('calendar_events')
            ->orderBy('event_date')
            ->limit(3)
            ->get();

        return view('admin.dashboard', compact('announcements', 'events'));
    }

    // ────────────────────────────────────────────────────────────
    //  PROFILE & SETTINGS
    // ────────────────────────────────────────────────────────────

    public function profile()
    {
        $user = Auth::user();
        $totalUsers = DB::table('users')->count();
        $totalCourses = DB::table('courses')->count();
        return view('admin.profile', compact('user', 'totalUsers', 'totalCourses'));
    }

    public function settings()
    {
        $user = Auth::user();
        return view('admin.settings', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
        ]);

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'full_name'  => $request->full_name,
                'phone'      => $request->phone,
                'email'      => $request->email,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'تم تحديث الملف الشخصي بنجاح!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'new_password.required'     => 'كلمة المرور الجديدة مطلوبة.',
            'new_password.min'          => 'كلمة المرور الجديدة يجب ألا تقل عن 6 أحرف.',
            'new_password.confirmed'    => 'تأكيد كلمة المرور الجديدة غير متطابق.',
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
    //  MESSAGES & ANNOUNCEMENTS
    // ────────────────────────────────────────────────────────────

    public function messages()
    {
        $users = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.role_id')
            ->where('users.user_id', '!=', Auth::id())
            ->select('users.*', 'roles.name as role_name')
            ->get();

        $departments = DB::table('departments')->get();

        return view('admin.messages', compact('users', 'departments'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required|in:all,departments,individuals',
            'subject'        => 'required|string|max:255',
            'message'        => 'required|string',
            'attachment'     => 'nullable|file|max:51200',
        ]);

        $recipientType = $request->recipient_type;
        $recipientsQuery = DB::table('users')->where('user_id', '!=', Auth::id());

        if ($recipientType === 'departments') {
            $request->validate([
                'target_departments' => 'required|array',
            ]);
            // Fetch names of selected departments
            $deptNames = DB::table('departments')
                ->whereIn('department_id', $request->target_departments)
                ->pluck('name')
                ->toArray();
            
            $recipientsQuery->whereIn('department', $deptNames);
        } elseif ($recipientType === 'individuals') {
            $request->validate([
                'target_users' => 'required|array',
            ]);
            $recipientsQuery->whereIn('user_id', $request->target_users);
        }

        $recipientIds = $recipientsQuery->pluck('user_id')->toArray();

        if (empty($recipientIds)) {
            return redirect()->back()->with('error', 'لم يتم العثور على مستخدمين لإرسال الرسالة إليهم.');
        }

        $fullMessage = "📌 [ " . $request->subject . " ]\n\n" . $request->message;

        // Insert messages & notifications for all recipients
        foreach ($recipientIds as $receiverId) {
            DB::table('messages')->insert([
                'sender_id'   => Auth::id(),
                'receiver_id' => $receiverId,
                'message'     => $fullMessage,
                'is_read'     => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('notifications')->insert([
                'user_id'    => $receiverId,
                'title'      => 'تعميم إداري: ' . $request->subject,
                'message'    => 'تلقيت تعميماً إدارياً جديداً من الإدارة العامة.',
                'type'       => 'message',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'تم إرسال التعميم الإداري بنجاح إلى (' . count($recipientIds) . ') مستخدم!');
    }

    public function notifications()
    {
        $notifications = DB::table('notifications')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('admin.notifications', compact('notifications'));
    }

    public function markNotificationRead($id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        DB::table('notifications')
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return redirect()->back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
    }

    // ────────────────────────────────────────────────────────────
    //  ACCOUNTS MANAGEMENT
    // ────────────────────────────────────────────────────────────

    public function accounts()
    {
        $pendingUsers = DB::table('users')
            ->where('status', 'inactive')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.accounts', compact('pendingUsers'));
    }

    public function approveAccount($id)
    {
        DB::table('users')
            ->where('user_id', $id)
            ->update(['status' => 'active', 'updated_at' => now()]);

        // Add welcome notification
        DB::table('notifications')->insert([
            'user_id'    => $id,
            'title'      => 'تم تفعيل الحساب',
            'message'    => 'تهانينا! قامت الإدارة بتفعيل حسابك بنجاح. يمكنك الآن استخدام كافة الميزات.',
            'type'       => 'system',
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم قبول وتفعيل حساب المستخدم بنجاح!');
    }

    public function rejectAccount($id)
    {
        // Fetch user first to see their role
        $usr = DB::table('users')->where('user_id', $id)->first();
        if ($usr) {
            // Delete dynamic children mapping or role table details
            if ($usr->role_id == 3) {
                DB::table('students')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 2) {
                DB::table('teachers')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 5) {
                DB::table('heads')->where('user_id', $id)->delete();
            } elseif ($usr->role_id == 4) {
                $parent = DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    DB::table('parent_students')->where('parent_id', $parent->parent_id)->delete();
                    DB::table('parents')->where('parent_id', $parent->parent_id)->delete();
                }
            }
            DB::table('users')->where('user_id', $id)->delete();
        }

        return redirect()->back()->with('success', 'تم رفض وحذف طلب الحساب بنجاح.');
    }

    // ─── Student Create & Store ───
    public function createStudent()
    {
        $departments = DB::table('departments')->get();
        return view('admin.accounts.create_student', compact('departments'));
    }

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
            'university_id.unique' => 'الرقم الجامعي مستخدم بالفعل لحساب آخر.',
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل لحساب آخر.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id'       => 3, // student
            'full_name'     => $request->full_name,
            'username'      => $request->university_id, // Username matches university ID
            'university_id' => $request->university_id,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'password'      => bcrypt($request->password),
            'department'    => $request->department,
            'gender'        => $request->gender,
            'birth_date'    => $request->birth_date,
            'academic_year' => $request->level,
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

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب الطالب بنجاح!');
    }

    // ─── Parent Create & Store ───
    public function createParent()
    {
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'students.student_code', 'students.level', 'users.full_name')
            ->get();

        return view('admin.accounts.create_parent', compact('students'));
    }

    public function storeParent(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'required|string|max:20',
            'username'  => 'required|string|unique:users,username|max:255',
            'email'     => 'required|email|unique:users,email|max:255',
            'children'  => 'nullable|array',
            'password'  => 'required|string|min:6|confirmed',
        ], [
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id'    => 4, // parent
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

        if ($request->has('children')) {
            foreach ($request->children as $studentId) {
                DB::table('parent_students')->insert([
                    'parent_id'    => $parentId,
                    'student_id'  => $studentId,
                    'relationship' => 'والد / ولي أمر',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب ولي الأمر بنجاح ربطاً بالأبناء المحددين!');
    }

    // ─── Teacher Create & Store ───
    public function createTeacher()
    {
        return view('admin.accounts.create_teacher');
    }

    public function storeTeacher(Request $request)
    {
        $request->validate([
            'full_name'      => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'username'       => 'required|string|unique:users,username|max:255',
            'email'          => 'required|email|unique:users,email|max:255',
            'specialization' => 'required|string|max:255',
            'password'       => 'required|string|min:6|confirmed',
        ], [
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id'    => 2, // teacher
            'full_name'  => $request->full_name,
            'username'   => $request->username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => bcrypt($request->password),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teachers')->insert([
            'user_id'        => $userId,
            'specialization' => $request->specialization,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب المدرب / المعلم بنجاح!');
    }

    // ─── HOD Create & Store ───
    public function createHOD()
    {
        $departments = DB::table('departments')->get();
        return view('admin.accounts.create_hod', compact('departments'));
    }

    public function storeHOD(Request $request)
    {
        $request->validate([
            'full_name'     => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'username'      => 'required|string|unique:users,username|max:255',
            'email'         => 'required|email|unique:users,email|max:255',
            'department_id' => 'required|exists:departments,department_id',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();

        $userId = DB::table('users')->insertGetId([
            'role_id'    => 5, // head of department
            'full_name'  => $request->full_name,
            'username'   => $request->username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'department' => $dept ? $dept->name : null,
            'password'   => bcrypt($request->password),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('heads')->insert([
            'user_id'       => $userId,
            'department_id' => $request->department_id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب رئيس القسم بنجاح وتخصيص القسم له!');
    }

    // ─── Affairs Create & Store ───
    public function createAffairs()
    {
        return view('admin.accounts.create_affairs');
    }

    public function storeAffairs(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'username'  => 'required|string|unique:users,username|max:255',
            'email'     => 'required|email|unique:users,email|max:255',
            'password'  => 'required|string|min:6|confirmed',
        ], [
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        DB::table('users')->insert([
            'role_id'    => 6, // affairs employee
            'full_name'  => $request->full_name,
            'username'   => $request->username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => bcrypt($request->password),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب موظف الشؤون بنجاح!');
    }

    // ─── Dynamic Deletion Lists ───
    public function deleteList($role_id)
    {
        $roleId = intval($role_id);
        
        // Define role titles and UI configs
        $roleTitlePlural = '';
        $searchPlaceholder = '';
        $cardIcon = '';
        $cardIconColor = '';

        if ($roleId == 3) {
            $roleTitlePlural = 'الطلاب';
            $searchPlaceholder = 'بحث عن طالب بالاسم أو البريد...';
            $cardIcon = 'school';
            $cardIconColor = 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400';
            
            $users = DB::table('users')
                ->join('students', 'users.user_id', '=', 'students.user_id')
                ->select('users.*', 'students.student_code', 'students.level')
                ->get();
        } elseif ($roleId == 2) {
            $roleTitlePlural = 'المدربين والمعلمين';
            $searchPlaceholder = 'بحث عن مدرب بالاسم أو الاختصاص...';
            $cardIcon = 'sports';
            $cardIconColor = 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400';

            $users = DB::table('users')
                ->join('teachers', 'users.user_id', '=', 'teachers.user_id')
                ->select('users.*', 'teachers.specialization')
                ->get();
        } elseif ($roleId == 5) {
            $roleTitlePlural = 'رؤساء الأقسام';
            $searchPlaceholder = 'بحث عن رئيس قسم بالاسم...';
            $cardIcon = 'supervisor_account';
            $cardIconColor = 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400';

            $users = DB::table('users')
                ->join('heads', 'users.user_id', '=', 'heads.user_id')
                ->select('users.*')
                ->get();
        } elseif ($roleId == 4) {
            $roleTitlePlural = 'أولياء الأمور';
            $searchPlaceholder = 'بحث عن ولي أمر بالاسم أو رقم الهاتف...';
            $cardIcon = 'family_restroom';
            $cardIconColor = 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400';

            $users = DB::table('users')
                ->join('parents', 'users.user_id', '=', 'parents.user_id')
                ->select('users.*')
                ->get();
        } elseif ($roleId == 6) {
            $roleTitlePlural = 'موظفي الشؤون';
            $searchPlaceholder = 'بحث عن موظف بالاسم...';
            $cardIcon = 'badge';
            $cardIconColor = 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400';

            $users = DB::table('users')
                ->where('role_id', 6)
                ->get();
        } else {
            return redirect()->route('admin.accounts')->with('error', 'فئة الصلاحية المحددة غير صالحة.');
        }

        return view('admin.accounts.delete_list', compact('users', 'roleId', 'roleTitlePlural', 'searchPlaceholder', 'cardIcon', 'cardIconColor'));
    }

    public function deleteAccounts(Request $request, $role_id)
    {
        $roleId = intval($role_id);
        
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,user_id',
        ], [
            'user_ids.required' => 'يرجى تحديد مستخدم واحد على الأقل للحذف.',
        ]);

        $userIds = $request->user_ids;

        foreach ($userIds as $id) {
            // Delete dependent records first to maintain foreign key integrity
            if ($roleId == 3) {
                DB::table('students')->where('user_id', $id)->delete();
                DB::table('parent_students')->where('student_id', function($q) use ($id) {
                    $q->select('student_id')->from('students')->where('user_id', $id);
                })->delete();
            } elseif ($roleId == 2) {
                DB::table('teachers')->where('user_id', $id)->delete();
                DB::table('course_teachers')->where('teacher_id', function($q) use ($id) {
                    $q->select('teacher_id')->from('teachers')->where('user_id', $id);
                })->delete();
            } elseif ($roleId == 5) {
                DB::table('heads')->where('user_id', $id)->delete();
            } elseif ($roleId == 4) {
                $parent = DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    DB::table('parent_students')->where('parent_id', $parent->parent_id)->delete();
                    DB::table('parents')->where('parent_id', $parent->parent_id)->delete();
                }
            }

            // Finally, delete user
            DB::table('users')->where('user_id', $id)->delete();
        }

        return redirect()->route('admin.accounts')->with('success', 'تم حذف الحسابات المحددة نهائياً وبنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  COURSES (PROGRAMS) MANAGEMENT
    // ────────────────────────────────────────────────────────────

    public function courses()
    {
        $programs = DB::table('programs')
            ->join('departments', 'programs.department_id', '=', 'departments.department_id')
            ->select('programs.*', 'departments.name as department_name')
            ->orderByDesc('programs.created_at')
            ->get();

        // Enrich with course count, total hours, and subjects list
        foreach ($programs as $program) {
            $coursesInProgram = DB::table('course_program')
                ->join('courses', 'course_program.course_id', '=', 'courses.course_id')
                ->where('course_program.program_id', $program->id)
                ->select('courses.title as course_name')
                ->get();

            $program->course_count = count($coursesInProgram);
            $program->total_hours = $program->course_count * 4; // estimate 4h per course
            $program->courses_list = $coursesInProgram;
        }

        return view('admin.courses', compact('programs'));
    }

    public function createCourse()
    {
        $departments = DB::table('departments')->get();
        return view('admin.courses.create', compact('departments'));
    }

    public function storeCourse(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,department_id',
            'description'   => 'nullable|string',
            'duration'      => 'nullable|string|max:100',
            'start_date'    => 'nullable|date',
        ], [
            'name.required'          => 'اسم الدورة مطلوب.',
            'department_id.required' => 'يرجى اختيار القسم.',
        ]);

        DB::table('programs')->insert([
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.courses')->with('success', 'تم إضافة الدورة الجديدة بنجاح!');
    }

    public function deleteCourse($id)
    {
        DB::table('course_program')->where('program_id', $id)->delete();
        DB::table('programs')->where('id', $id)->delete();

        return redirect()->route('admin.courses')->with('success', 'تم حذف الدورة بنجاح.');
    }

    // ────────────────────────────────────────────────────────────
    //  ASSIGN HEAD OF DEPARTMENT
    // ────────────────────────────────────────────────────────────

    public function assignHODForm()
    {
        $departments = DB::table('departments')->get();

        // Get users who could be HODs (teachers and existing HODs)
        $availableUsers = DB::table('users')
            ->whereIn('role_id', [2, 5]) // teachers and HODs
            ->where('status', 'active')
            ->get();

        return view('admin.courses.assign_hod', compact('departments', 'availableUsers'));
    }

    public function assignHOD(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'user_id'       => 'required|exists:users,user_id',
        ], [
            'department_id.required' => 'يرجى اختيار القسم.',
            'user_id.required'       => 'يرجى اختيار رئيس القسم.',
        ]);

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();
        $user = DB::table('users')->where('user_id', $request->user_id)->first();

        // Remove old HOD for this department if any
        $oldHead = DB::table('heads')->where('department_id', $request->department_id)->first();
        if ($oldHead) {
            // Reset old HOD's role back to teacher
            DB::table('users')->where('user_id', $oldHead->user_id)->update([
                'role_id'    => 2,
                'department' => null,
                'updated_at' => now(),
            ]);
            DB::table('heads')->where('department_id', $request->department_id)->delete();
        }

        // Update the selected user's role to HOD
        DB::table('users')->where('user_id', $request->user_id)->update([
            'role_id'    => 5,
            'department' => $dept->name,
            'updated_at' => now(),
        ]);

        // Insert into heads table
        DB::table('heads')->insert([
            'user_id'       => $request->user_id,
            'department_id' => $request->department_id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Send notification
        DB::table('notifications')->insert([
            'user_id'    => $request->user_id,
            'title'      => 'تعيين رئيس قسم',
            'message'    => 'تم تعيينك رئيساً لقسم ' . $dept->name . '. مبارك!',
            'type'       => 'system',
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.courses')->with('success', 'تم تعيين ' . $user->full_name . ' رئيساً لقسم ' . $dept->name . ' بنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  SEMESTERS & SUBJECTS
    // ────────────────────────────────────────────────────────────

    public function semestersSubjects(Request $request)
    {
        $departments = DB::table('departments')->get();
        $semesters = DB::table('semesters')->orderByDesc('start_date')->get();
        
        // Fetch programs with their associated department names
        $programs = DB::table('programs')
            ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
            ->select('programs.*', 'departments.name as department_name')
            ->get();
        
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'users.full_name')
            ->get();

        // Default filters
        $selectedDept = $request->get('department_id');
        $selectedProgram = $request->get('program_id');
        $selectedSemester = $request->get('semester_id');

        // Build subjects query
        $coursesQuery = DB::table('courses')
            ->leftJoin('course_teachers', 'courses.course_id', '=', 'course_teachers.course_id')
            ->leftJoin('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('courses.*', 'users.full_name as teacher_name');

        if ($selectedSemester) {
            $coursesQuery->where('courses.semester_id', $selectedSemester);
        }

        if ($selectedProgram) {
            $courseIds = DB::table('course_program')
                ->where('program_id', $selectedProgram)
                ->pluck('course_id');
            $coursesQuery->whereIn('courses.course_id', $courseIds);
        } elseif ($selectedDept) {
            $programIds = DB::table('programs')
                ->where('department_id', $selectedDept)
                ->pluck('id');
            $courseIds = DB::table('course_program')
                ->whereIn('program_id', $programIds)
                ->pluck('course_id');
            $coursesQuery->whereIn('courses.course_id', $courseIds);
        }

        $courses = $coursesQuery->get();

        foreach ($courses as $course) {
            $lessons = DB::table('lessons')
                ->where('course_id', $course->course_id)
                ->select('title', 'description')
                ->get();
            $course->lessons_list = $lessons;

            $semInfo = DB::table('semesters')
                ->where('semester_id', $course->semester_id)
                ->first();
            $course->semester_name = $semInfo ? $semInfo->name : 'غير محدد';

            // Get department names for this course via the programs relationship
            $coursePrograms = DB::table('course_program')
                ->join('programs', 'course_program.program_id', '=', 'programs.id')
                ->where('course_program.course_id', $course->course_id)
                ->select('programs.department_id')
                ->get();
            
            $deptIds = $coursePrograms->pluck('department_id')->unique();
            $courseDepts = DB::table('departments')
                ->whereIn('department_id', $deptIds)
                ->pluck('name');
            $course->departments_list = $courseDepts;
        }

        return view('admin.semesters_subjects', compact(
            'departments', 'semesters', 'programs', 'courses', 'teachers',
            'selectedDept', 'selectedProgram', 'selectedSemester'
        ));
    }

    // ────────────────────────────────────────────────────────────
    //  REPORTS
    // ────────────────────────────────────────────────────────────

    public function reports()
    {
        $departments = DB::table('departments')->get();
        $programs = DB::table('programs')->get();
        $semesters = DB::table('semesters')->orderByDesc('start_date')->get();

        return view('admin.reports', compact('departments', 'programs', 'semesters'));
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:attendance,performance',
        ]);

        $departments = DB::table('departments')->get();
        $programs = DB::table('programs')->get();
        $semesters = DB::table('semesters')->orderByDesc('start_date')->get();

        $reportType = $request->report_type;
        $reportData = [];

        if ($reportType === 'attendance') {
            // Attendance report
            $query = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
                ->select(
                    'users.full_name',
                    'courses.title as course_title',
                    DB::raw('COUNT(*) as total_sessions'),
                    DB::raw("SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('users.full_name', 'courses.title');

            if ($request->from_date) {
                $query->where('attendance.attendance_date', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $query->where('attendance.attendance_date', '<=', $request->to_date);
            }
            if ($request->semester_id) {
                $query->where('courses.semester_id', $request->semester_id);
            }
            if ($request->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $request->program_id)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }

            $reportData = $query->limit(50)->get();
        } else {
            // Performance report
            $query = DB::table('grades')
                ->join('students', 'grades.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name',
                    'courses.title as course_title',
                    'grades.score as grade',
                    'semesters.name as semester'
                );

            if ($request->semester_id) {
                $query->where('courses.semester_id', $request->semester_id);
            }
            if ($request->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $request->program_id)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            } elseif ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            }

            $reportData = $query->limit(50)->get();
        }

        return view('admin.reports', compact('departments', 'programs', 'semesters', 'reportType', 'reportData'));
    }

    public function exportReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:attendance,performance',
        ]);

        $reportType = $request->report_type;

        if ($reportType === 'attendance') {
            $query = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
                ->select(
                    'users.full_name',
                    'courses.title as course_title',
                    DB::raw('COUNT(*) as total_sessions'),
                    DB::raw("SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('users.full_name', 'courses.title');

            if ($request->from_date) {
                $query->where('attendance.attendance_date', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $query->where('attendance.attendance_date', '<=', $request->to_date);
            }
            if ($request->semester_id) {
                $query->where('courses.semester_id', $request->semester_id);
            }
            if ($request->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $request->program_id)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }

            $data = $query->get();

            $filename = "تقرير_الحضور_" . date('Y-m-d') . ".xls";
            $headers = [
                "Content-type"        => "application/vnd.ms-excel; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = ['اسم الطالب', 'المادة الدراسية', 'عدد جلسات الحضور', 'عدد جلسات الغياب', 'نسبة الحضور'];

            $callback = function() use($data, $columns) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM to display Arabic characters correctly in Excel
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($file, $columns, "\t");

                foreach ($data as $row) {
                    $rate = $row->total_sessions > 0 ? round(($row->present_count / $row->total_sessions) * 100) : 0;
                    fputcsv($file, [
                        $row->full_name,
                        $row->course_title,
                        $row->present_count,
                        $row->absent_count,
                        $rate . "%"
                    ], "\t");
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } else {
            // Performance report
            $query = DB::table('grades')
                ->join('students', 'grades.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name',
                    'courses.title as course_title',
                    'grades.score as grade',
                    'semesters.name as semester'
                );

            if ($request->semester_id) {
                $query->where('courses.semester_id', $request->semester_id);
            }
            if ($request->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $request->program_id)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            } elseif ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            }

            $data = $query->get();

            $filename = "تقرير_الأداء_" . date('Y-m-d') . ".xls";
            $headers = [
                "Content-type"        => "application/vnd.ms-excel; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = ['اسم الطالب', 'المادة الدراسية', 'الدرجة', 'الفصل الدراسي'];

            $callback = function() use($data, $columns) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM to display Arabic characters correctly in Excel
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($file, $columns, "\t");

                foreach ($data as $row) {
                    fputcsv($file, [
                        $row->full_name,
                        $row->course_title,
                        $row->grade,
                        $row->semester ?? '-'
                    ], "\t");
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
    }

    public function storeCalendarEvent(Request $request)
    {
        $request->validate([
            'event_date' => 'required|date',
            'title'      => 'required|string|max:255',
            'event_time' => 'nullable',
            'location'   => 'nullable|string|max:255',
        ]);

        \App\Models\CalendarEvent::create([
            'user_id'    => Auth::id(),
            'event_date' => $request->event_date,
            'title'      => $request->title,
            'event_time' => $request->event_time,
            'location'   => $request->location,
        ]);

        return back()->with('success', 'تم إضافة الحدث بنجاح إلى التقويم.');
    }

    public function storeSubject(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'level'       => 'required|string',
            'semester_id' => 'required|integer',
            'teacher_id'  => 'required|integer',
            'program_id'  => 'required|integer',
            'hours'       => 'required|integer|min:1',
        ]);

        $courseId = DB::table('courses')->insertGetId([
            'title'       => $request->title,
            'description' => $request->description,
            'level'       => $request->level,
            'semester_id' => $request->semester_id,
            'hours'       => $request->hours,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Assign teacher
        DB::table('course_teachers')->insert([
            'course_id'  => $courseId,
            'teacher_id' => $request->teacher_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign program (which belongs to a department)
        DB::table('course_program')->insert([
            'course_id'  => $courseId,
            'program_id' => $request->program_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم إضافة المادة وتعيين المدرس والمسار بنجاح!');
    }
}
