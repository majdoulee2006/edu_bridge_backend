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
            ->orderBy('start_date')
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
        return view('admin.profile', compact('user'));
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
        ]);

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'full_name'  => $request->full_name,
                'phone'      => $request->phone,
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
}
