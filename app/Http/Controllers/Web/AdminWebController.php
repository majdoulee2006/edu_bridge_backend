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

        // Support login via email or username (using str_contains to support domains with underscores)
        $loginField = str_contains($request->login, '@') ? 'email' : 'username';

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

    public function createAnnouncement()
    {
        return view('admin.announcements.create');
    }

    public function storeAnnouncement(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url'        => 'nullable|url|max:500',
            'target_audience' => 'nullable|in:all,students,teachers,department',
            'department_id'   => 'nullable|exists:departments,department_id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $announcement = \App\Models\Announcement::create([
            'user_id'         => Auth::id(),
            'title'           => $request->title,
            'content'         => $request->content,
            'image'           => $imagePath,
            'link_url'        => $request->input('link_url'),
            'target_audience' => $request->input('target_audience', 'all'),
            'type'            => 'general',
        ]);

        // FCM حسب الجمهور المستهدف
        $target  = $request->input('target_audience', 'all');
        $roleIds = match($target) { 'students'=>[3], 'teachers'=>[2], 'department'=>[2,3], default=>[2,3] };
        $query   = \App\Models\User::whereIn('role_id', $roleIds)->where('status','active');

        if ($target === 'department' && $request->filled('department_id')) {
            $deptName = \App\Models\Department::find($request->department_id)?->name;
            if ($deptName) $query->where('department', $deptName);
        }
        $userIds = $query->pluck('user_id');
        $now     = now();
        $rows    = $userIds->map(fn($uid) => [
            'user_id'=>$uid, 'sender_id'=>Auth::id(),
            'title'=>'إعلان جديد من الإدارة', 'message'=>$request->title,
            'type'=>'announcement', 'category'=>'administrative',
            'related_id'=>$announcement->id ?? $announcement->announcement_id,
            'is_read'=>0, 'created_at'=>$now, 'updated_at'=>$now,
        ])->all();
        if (!empty($rows)) {
            DB::table('notifications')->insert($rows);
            foreach ($userIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, 'إعلان جديد من الإدارة', $request->title, ['type'=>'announcement']);
            }
        }

        return redirect()->route('admin.dashboard')->with('success', 'تم نشر الإعلان بنجاح!');
    }

    public function editAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function updateAnnouncement(\Illuminate\Http\Request $request, $id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $updates = [
            'title'      => $request->title,
            'content'    => $request->content,
            'updated_at' => now(),
        ];

        if ($request->hasFile('image')) {
            if ($announcement->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
            }
            $updates['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($updates);

        return redirect()->route('admin.dashboard')->with('success', 'تم تحديث الإعلان بنجاح!');
    }

    public function deleteAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($announcement->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
        }
        $announcement->delete();

        return redirect()->route('admin.dashboard')->with('success', 'تم حذف الإعلان.');
    }

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

        // Insert messages & notifications + FCM for all recipients
        $notifTitle = 'تعميم إداري: ' . $request->subject;
        $notifMsg   = 'تلقيت تعميماً إدارياً جديداً من الإدارة العامة.';

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
                'sender_id'  => Auth::id(),
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'message',
                'category'   => 'administrative',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser($receiverId, $notifTitle, $notifMsg, ['type' => 'message']);
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

        // ---- إضافة ربط الأبناء بولي الأمر عند الموافقة ----
        $user = DB::table('users')->where('user_id', $id)->first();
        if ($user && $user->role_id == 4 && !empty($user->children_ids)) {
            $childrenIds = is_string($user->children_ids) ? json_decode($user->children_ids, true) : $user->children_ids;
            if (is_array($childrenIds)) {
                $parent = DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    foreach ($childrenIds as $universityId) {
                        $student = DB::table('students')
                            ->where('student_code', $universityId)
                            ->select('student_id')
                            ->first();
                        if ($student) {
                            DB::table('parent_students')->insertOrIgnore([
                                'parent_id'    => $parent->parent_id,
                                'student_id'   => $student->student_id,
                                'relationship' => 'والد / ولي أمر',
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ]);
                        }
                    }
                }
            }
        }
        // ---------------------------------------------------

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
        \App\Services\FcmService::sendToUser(
            $id,
            'تم تفعيل الحساب',
            'تهانينا! قامت الإدارة بتفعيل حسابك بنجاح. يمكنك الآن استخدام كافة الميزات.',
            ['type' => 'system']
        );

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
            'full_name'        => 'required|string|max:255',
            'university_id'    => 'required|string|unique:users,university_id|max:255',
            'email'            => 'required|email|unique:users,email|max:255',
            'phone'            => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:100',
            'department'       => 'required|string|max:255',
            'program_id'       => 'required|integer|exists:programs,id',
            'level'            => 'required|string|max:255',
            'birth_date'       => 'required|date',
            'gender'           => 'required|in:ذكر,أنثى',
            'password'         => 'required|string|min:6|confirmed',
        ], [
            'university_id.unique' => 'الرقم الجامعي مستخدم بالفعل لحساب آخر.',
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل لحساب آخر.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id'          => 3,
            'full_name'        => $request->full_name,
            'username'         => $request->university_id,
            'university_id'    => $request->university_id,
            'email'            => $request->email,
            'phone'            => $request->phone,
            'telegram_chat_id' => $request->telegram_chat_id,
            'password'         => bcrypt($request->password),
            'department'       => $request->department,
            'gender'           => $request->gender,
            'birth_date'       => $request->birth_date,
            'academic_year'    => $request->level,
            'status'           => 'active',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // إرسال بيانات الطالب عبر تليجرام مباشرة
        if ($request->filled('telegram_chat_id')) {
            try {
                $botToken = '8729068851:AAHILif3EtFWGKaTLgYxm7ZPuw6uqXV0A2k';
                $programName = DB::table('programs')->where('id', $request->program_id)->value('name') ?? $request->program_id;
                $message = "🎓 <b>مرحباً بك في جامعة Edu-Bridge!</b> 🎉\n\n"
                         . "تم إنشاء حساب الطالب الخاص بك بنجاح. إليك كافة التفاصيل والمعلومات:\n\n"
                         . "👤 <b>الاسم الكامل:</b> {$request->full_name}\n"
                         . "🔑 <b>الرقم الجامعي (اسم المستخدم):</b> <code>{$request->university_id}</code>\n"
                         . "🔒 <b>كلمة المرور:</b> <code>{$request->password}</code>\n"
                         . "📧 <b>البريد الإلكتروني:</b> <code>{$request->email}</code>\n"
                         . "📞 <b>رقم الهاتف:</b> <code>" . ($request->phone ?? '—') . "</code>\n"
                         . "🏢 <b>القسم:</b> <code>{$request->department}</code>\n"
                         . "💻 <b>البرنامج الدراسي:</b> <code>{$programName}</code>\n"
                         . "📚 <b>المستوى الدراسي:</b> <code>{$request->level}</code>\n"
                         . "📅 <b>تاريخ الميلاد:</b> <code>{$request->birth_date}</code>\n"
                         . "🚻 <b>الجنس:</b> <code>{$request->gender}</code>\n\n"
                         . "📲 يمكنك الآن تسجيل الدخول مباشرة إلى تطبيق الجامعة باستخدام رقمك الجامعي وكلمة المرور أعلاه.";

                \Illuminate\Support\Facades\Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $request->telegram_chat_id,
                    'text'    => $message,
                    'parse_mode' => 'HTML',
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Telegram Bot Error: ' . $e->getMessage());
            }
        }

        $studentId = DB::table('students')->insertGetId([
            'user_id'      => $userId,
            'program_id'   => $request->program_id,
            'student_code' => $request->university_id,
            'level'        => $request->level,
            'birth_date'   => $request->birth_date,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        \App\Models\Student::autoAssignAdvisor($studentId);

        // تسجيل تلقائي بكل مواد الدورة والسنة
        $yearNum = $request->level === 'السنة الأولى' ? 1 : 2;
        $courseIds = DB::table('course_program')
            ->where('program_id', $request->program_id)
            ->join('courses', 'course_program.course_id', '=', 'courses.course_id')
            ->where('courses.year', $yearNum)
            ->pluck('course_program.course_id');

        foreach ($courseIds as $courseId) {
            DB::table('enrollments')->insert([
                'student_id'      => $studentId,
                'course_id'       => $courseId,
                'enrollment_date' => now()->toDateString(),
                'status'          => 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب الطالب وتسجيله في ' . $courseIds->count() . ' مادة تلقائياً!');
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

        if ($request->filled('children_university_ids')) {
            foreach (array_filter($request->children_university_ids) as $universityId) {
                $student = DB::table('students')
                    ->join('users', 'students.user_id', '=', 'users.user_id')
                    ->where('students.student_code', $universityId)
                    ->select('students.student_id')
                    ->first();
                if (!$student) continue;
                $studentId = $student->student_id;
                DB::table('parent_students')->insert([
                    'parent_id'    => $parentId,
                    'student_id'   => $studentId,
                    'relationship' => 'guardian',
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
        $departments = DB::table('departments')->orderBy('name')->get();
        
        $coursesList = DB::table('courses')
            ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
            ->join('programs', 'course_program.program_id', '=', 'programs.id')
            ->select('courses.course_id', 'courses.title', 'programs.department_id')
            ->distinct()
            ->get();
            
        $deptCourses = [];
        foreach ($coursesList as $c) {
            $deptCourses[$c->department_id][] = ['id' => $c->course_id, 'title' => $c->title];
        }
        
        $branchesList = DB::table('programs')->select('id', 'name', 'department_id')->get();
        $deptBranches = [];
        foreach ($branchesList as $b) {
            $deptBranches[$b->department_id][] = ['id' => $b->id, 'name' => $b->name];
        }
        
        $courses     = DB::table('courses')->orderBy('title')->get();
        return view('admin.accounts.create_teacher', compact('departments', 'courses', 'deptCourses', 'deptBranches'));
    }

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
        $base = strtolower(explode('@', $request->email)[0]);
        $username = $base;
        $i = 1;
        while (DB::table('users')->where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        $userId = DB::table('users')->insertGetId([
            'role_id'    => 2,
            'full_name'  => $request->full_name,
            'username'   => $username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'department' => $request->department,
            'password'   => bcrypt($request->password),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacherId = DB::table('teachers')->insertGetId([
            'user_id'        => $userId,
            'specialization' => $request->specialization,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // ربط المواد
        if ($request->filled('courses')) {
            foreach ($request->courses as $courseId) {
                DB::table('course_teachers')->insertOrIgnore([
                    'teacher_id' => $teacherId,
                    'course_id'  => $courseId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('admin.accounts')->with('success', 'تم إنشاء حساب المعلم بنجاح!');
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
            'email'         => 'required|email|unique:users,email|max:255',
            'department_id' => 'required|exists:departments,department_id',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        // توليد username تلقائياً من الإيميل
        $base = strtolower(explode('@', $request->email)[0]);
        $username = $base;
        $i = 1;
        while (DB::table('users')->where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();

        $userId = DB::table('users')->insertGetId([
            'role_id'    => 5,
            'full_name'  => $request->full_name,
            'username'   => $username,
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
            'email'     => 'required|email|unique:users,email|max:255',
            'password'  => 'required|string|min:6|confirmed',
        ], [
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $base = strtolower(explode('@', $request->email)[0]);
        $username = $base;
        $i = 1;
        while (DB::table('users')->where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        DB::table('users')->insert([
            'role_id'    => 6,
            'full_name'  => $request->full_name,
            'username'   => $username,
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
        \App\Services\FcmService::sendToUser(
            $request->user_id,
            'تعيين رئيس قسم',
            'تم تعيينك رئيساً لقسم ' . $dept->name . '. مبارك!',
            ['type' => 'system']
        );

        return redirect()->route('admin.courses')->with('success', 'تم تعيين ' . $user->full_name . ' رئيساً لقسم ' . $dept->name . ' بنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  SEMESTERS & SUBJECTS
    // ────────────────────────────────────────────────────────────

    public function semestersSubjects(Request $request)
    {
        $departments = DB::table('departments')->get();
        $semesters   = DB::table('semesters')->orderByDesc('start_date')->get();

        // السنوات الأكاديمية: من عمود year في المواد (1 = السنة الأولى، 2 = السنة الثانية...)
        $academicYears = DB::table('courses')
            ->whereNotNull('year')->where('year', '!=', '')
            ->distinct()->orderBy('year')->pluck('year');

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
        $selectedDept     = $request->get('department_id');
        $selectedProgram  = $request->get('program_id');
        $selectedSemester = $request->get('semester_id');
        $selectedYear     = $request->get('year');

        // Build subjects query
        $coursesQuery = DB::table('courses')
            ->leftJoin('course_teachers', 'courses.course_id', '=', 'course_teachers.course_id')
            ->leftJoin('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('courses.*', 'users.full_name as teacher_name');

        if ($selectedSemester) {
            $coursesQuery->where('courses.semester_id', $selectedSemester);
        }

        if ($selectedYear) {
            $coursesQuery->where('courses.year', $selectedYear);
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

            $coursePrograms = DB::table('course_program')
                ->join('programs', 'course_program.program_id', '=', 'programs.id')
                ->where('course_program.course_id', $course->course_id)
                ->select('programs.department_id', 'programs.id as program_id')
                ->get();

            $course->program_id = $coursePrograms->first()->program_id ?? null;

            $deptIds     = $coursePrograms->pluck('department_id')->unique();
            $courseDepts = DB::table('departments')
                ->whereIn('department_id', $deptIds)
                ->pluck('name');
            $course->departments_list = $courseDepts;
        }

        return view('admin.semesters_subjects', compact(
            'departments', 'semesters', 'programs', 'courses', 'teachers',
            'selectedDept', 'selectedProgram', 'selectedSemester',
            'selectedYear', 'academicYears'
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
        $request->validate(['report_type' => 'required|in:attendance,performance']);

        $reportType   = $request->report_type;
        $deptName     = $request->department_id ? (DB::table('departments')->where('department_id', $request->department_id)->value('name') ?? 'كل الأقسام') : 'كل الأقسام';
        $semesterName = $request->semester_id   ? (DB::table('semesters')->where('semester_id', $request->semester_id)->value('name') ?? 'كل الفصول') : 'كل الفصول';
        $dateLabel    = date('Y-m-d');

        if ($reportType === 'attendance') {
            $query = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
                ->select(
                    'users.full_name as student_name',
                    'courses.title as course',
                    DB::raw("COUNT(*) as total"),
                    DB::raw("SUM(attendance.status='present') as present"),
                    DB::raw("SUM(attendance.status='absent') as absent"),
                    'attendance.attendance_date'
                )
                ->groupBy('users.full_name', 'courses.title', 'attendance.attendance_date');

            if ($request->semester_id) $query->where('courses.semester_id', $request->semester_id);
            if ($request->from_date)   $query->where('attendance.attendance_date', '>=', $request->from_date);
            if ($request->to_date)     $query->where('attendance.attendance_date', '<=', $request->to_date);
            if ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds  = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }

            $data     = $query->orderBy('courses.title')->orderBy('users.full_name')->get();
            $filename = "حضور_{$dateLabel}.xls";

            $rows = '';
            foreach ($data as $row) {
                $rate  = $row->total > 0 ? round(($row->present / $row->total) * 100) : 0;
                $rows .= "<tr>
                  <td>{$row->student_name}</td><td>{$row->course}</td>
                  <td>{$row->attendance_date}</td>
                  <td style='color:#166534;font-weight:bold'>{$row->present}</td>
                  <td style='color:#b91c1c;font-weight:bold'>{$row->absent}</td>
                  <td>{$row->total}</td><td>{$rate}%</td>
                </tr>";
            }

            $html = "<html xmlns:o='urn:schemas-microsoft-com:office:office'
                          xmlns:x='urn:schemas-microsoft-com:office:excel'
                          xmlns='http://www.w3.org/TR/REC-html40'>
<head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
<style>
body{font-family:'Segoe UI',Tahoma,sans-serif;direction:rtl}
table{border-collapse:collapse;width:100%}
th{background:#1e293b;color:#f2f20d;font-weight:bold;border:1px solid #ccc;padding:8px;text-align:right}
td{border:1px solid #ddd;padding:7px;text-align:right}
tr:nth-child(even) td{background:#f8fafc}
.hdr td{background:#0f172a;color:#f2f20d;font-size:15px;font-weight:bold;padding:12px}
.inf td{background:#f1f5f9;color:#334155;font-size:11px;padding:6px}
</style></head><body>
<table>
<tr class='hdr'><td colspan='7'>تقرير الحضور والغياب</td></tr>
<tr class='inf'><td>القسم: {$deptName}</td><td>الفصل: {$semesterName}</td><td colspan='5'>التاريخ: {$dateLabel}</td></tr>
<tr><th>اسم الطالب</th><th>المادة</th><th>التاريخ</th><th>حاضر</th><th>غائب</th><th>الإجمالي</th><th>نسبة الحضور</th></tr>
{$rows}
</table></body></html>";

        } else {
            $query = DB::table('grades')
                ->join('students', 'grades.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name as student_name',
                    'courses.title as course',
                    'grades.score as grade',
                    'semesters.name as semester',
                    DB::raw("COALESCE(courses.year,'') as academic_year")
                );

            if ($request->semester_id) $query->where('courses.semester_id', $request->semester_id);
            if ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds  = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            }

            $data          = $query->orderBy('courses.title')->orderBy('users.full_name')->get();
            $courseSummary = $data->groupBy('course')->map(fn($rows) => [
                'count' => $rows->count(),
                'avg'   => round($rows->avg('grade'), 1),
                'max'   => $rows->max('grade'),
                'min'   => $rows->min('grade'),
            ]);
            $filename = "اداء_{$dateLabel}.xls";

            $rows = '';
            foreach ($data as $row) {
                $sem = $row->semester ?? '-';
                $yr  = $row->academic_year ?: '-';
                $rows .= "<tr>
                  <td>{$row->student_name}</td><td>{$row->course}</td>
                  <td style='font-weight:bold;color:#1d4ed8'>{$row->grade}</td>
                  <td>{$sem}</td><td>{$yr}</td>
                </tr>";
            }

            $sumRows = '';
            foreach ($courseSummary as $course => $s) {
                $sumRows .= "<tr>
                  <td>{$course}</td><td>{$s['count']}</td>
                  <td style='color:#1d4ed8;font-weight:bold'>{$s['avg']}</td>
                  <td style='color:#166534;font-weight:bold'>{$s['max']}</td>
                  <td style='color:#b91c1c;font-weight:bold'>{$s['min']}</td>
                </tr>";
            }

            $html = "<html xmlns:o='urn:schemas-microsoft-com:office:office'
                          xmlns:x='urn:schemas-microsoft-com:office:excel'
                          xmlns='http://www.w3.org/TR/REC-html40'>
<head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
<style>
body{font-family:'Segoe UI',Tahoma,sans-serif;direction:rtl}
table{border-collapse:collapse;width:100%}
th{background:#1e293b;color:#f2f20d;font-weight:bold;border:1px solid #ccc;padding:8px;text-align:right}
td{border:1px solid #ddd;padding:7px;text-align:right}
tr:nth-child(even) td{background:#f8fafc}
.hdr td{background:#0f172a;color:#f2f20d;font-size:15px;font-weight:bold;padding:12px}
.inf td{background:#f1f5f9;color:#334155;font-size:11px;padding:6px}
.sh th{background:#334155;color:#f2f20d}
</style></head><body>
<table>
<tr class='hdr'><td colspan='5'>تقرير الأداء الأكاديمي</td></tr>
<tr class='inf'><td>القسم: {$deptName}</td><td>الفصل: {$semesterName}</td><td colspan='3'>التاريخ: {$dateLabel}</td></tr>
<tr><th>اسم الطالب</th><th>المادة</th><th>الدرجة</th><th>الفصل</th><th>السنة الدراسية</th></tr>
{$rows}
<tr><td colspan='5'></td></tr>
<tr class='sh'><th colspan='5'>ملخص المواد</th></tr>
<tr><th>المادة</th><th>عدد الطلاب</th><th>المعدل</th><th>أعلى درجة</th><th>أدنى درجة</th></tr>
{$sumRows}
</table></body></html>";
        }

        return response("\xEF\xBB\xBF" . $html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Cache-Control', 'must-revalidate');
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
            'year'        => 'required|integer|in:1,2',
            'semester_id' => 'required|integer',
            'program_id'  => 'required|integer',
            'hours'       => 'required|integer|min:1',
        ]);

        // حفظ المادة مع السنة
        $courseId = DB::table('courses')->insertGetId([
            'title'       => $request->title,
            'description' => $request->description,
            'level'       => $request->level,
            'year'        => $request->year,
            'semester_id' => $request->semester_id,
            'hours'       => $request->hours,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // ربط الدورة (البرنامج)
        DB::table('course_program')->insert([
            'course_id'  => $courseId,
            'program_id' => $request->program_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // تسجيل تلقائي لكل الطلاب اللي في نفس الدورة والسنة
        $levelLabel = $request->year == 1 ? 'السنة الأولى' : 'السنة الثانية';
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.program_id', $request->program_id)
            ->where(function($q) use ($request, $levelLabel) {
                $q->where('students.level', (string) $request->year)
                  ->orWhere('students.level', $levelLabel)
                  ->orWhere('users.academic_year', (string) $request->year)
                  ->orWhere('users.academic_year', $levelLabel);
            })
            ->pluck('students.student_id');

        foreach ($students as $studentId) {
            $exists = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->exists();
            if (!$exists) {
                DB::table('enrollments')->insert([
                    'student_id'      => $studentId,
                    'course_id'       => $courseId,
                    'enrollment_date' => now()->toDateString(),
                    'status'          => 'active',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        return back()->with('success', 'تم إضافة المادة وتسجيل ' . $students->count() . ' طالب تلقائياً!');
    }

    public function updateSubject(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'level'       => 'required|string',
            'year'        => 'required|integer|in:1,2',
            'semester_id' => 'required|integer',
            'program_id'  => 'required|integer',
            'hours'       => 'required|integer|min:1',
        ]);

        DB::table('courses')->where('course_id', $id)->update([
            'title'       => $request->title,
            'description' => $request->description,
            'level'       => $request->level,
            'year'        => $request->year,
            'semester_id' => $request->semester_id,
            'hours'       => $request->hours,
            'updated_at'  => now(),
        ]);

        DB::table('course_program')->where('course_id', $id)->update([
            'program_id' => $request->program_id,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم تحديث المادة بنجاح!');
    }

    public function deleteSubject($id)
    {
        DB::table('courses')->where('course_id', $id)->delete();
        return back()->with('success', 'تم حذف المادة بنجاح!');
    }

    /**
     * الخدمات الطلابية للإدارة
     */
    public function studentServices()
    {
        return view('admin.student-services');
    }
}
