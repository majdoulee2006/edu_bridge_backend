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
            if ($user->status !== 'active') {
                Auth::logout();
                return back()->withErrors(['login' => 'عذراً. حسابك موقوف مؤقتاً.']);
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
            'admin_profile_otp'          => $otp,
            'admin_pending_profile_data' => $request->only(['full_name', 'phone', 'email', 'new_password'])
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

        if (session('admin_profile_otp') == $request->otp) {
            $user = Auth::user();
            $data = session('admin_pending_profile_data');

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

            session()->forget(['admin_profile_otp', 'admin_pending_profile_data']);

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
        $currentUserId = Auth::id();

        // Admin can chat with all active users
        $allUsers = \App\Models\User::where('user_id', '!=', $currentUserId)->get();

        return view('admin.messages', compact('allUsers'));
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

    public function deleteMessage($id)
    {
        $message = \App\Models\Message::findOrFail($id);

        if ($message->sender_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        if ($message->attachment) {
            $path = str_replace(asset('storage/'), '', $message->attachment);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        $message->delete();
        return response()->json(['status' => 'success']);
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
        $programs = DB::table('programs')->get();
        return view('admin.accounts.create_student', compact('departments', 'programs'));
    }

    public function storeStudent(Request $request)
    {
        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
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
            'first_name.required'  => 'الاسم الأول مطلوب.',
            'last_name.required'   => 'الاسم الثاني مطلوب.',
            'university_id.unique' => 'الرقم الجامعي مستخدم بالفعل لحساب آخر.',
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل لحساب آخر.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id'          => 3,
            'full_name'        => $fullName,
            'first_name'       => $request->first_name,
            'last_name'        => $request->last_name,
            'username'         => $request->university_id,
            'university_id'    => $request->university_id,
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
        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }

        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'first_name'              => 'required|string|max:100',
            'last_name'               => 'required|string|max:100',
            'phone'                   => 'required|string|max:20',
            'username'                => 'required|string|unique:users,username|max:255',
            'email'                   => 'required|email|unique:users,email|max:255',
            'telegram_id'             => 'nullable|string|max:255',
            'children_university_ids' => 'nullable|array',
            'password'                => 'required|string|min:6|confirmed',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required'  => 'الاسم الثاني مطلوب.',
            'username.unique'     => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed'  => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $userId = DB::table('users')->insertGetId([
            'role_id'          => 4, // parent
            'full_name'        => $fullName,
            'first_name'       => $request->first_name,
            'last_name'        => $request->last_name,
            'username'         => $request->username,
            'email'            => $request->email,
            'phone'            => $request->phone,
            'telegram_id'      => $request->telegram_id,
            'telegram_chat_id' => $request->telegram_id,
            'password'         => bcrypt($request->password),
            'status'           => 'active',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $parentId = DB::table('parents')->insertGetId([
            'user_id'     => $userId,
            'telegram_id' => $request->telegram_id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        if ($request->filled('children_university_ids')) {
            foreach (array_filter($request->children_university_ids) as $universityId) {
                $student = DB::table('students')
                    ->join('users', 'students.user_id', '=', 'users.user_id')
                    ->where('students.student_code', $universityId)
                    ->orWhere('users.username', $universityId)
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
        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'required|email|unique:users,email|max:255',
            'department'     => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'password'       => 'required|string|min:6|confirmed',
            'courses'        => 'nullable|array',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required'  => 'الاسم الثاني مطلوب.',
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
            'full_name'  => $fullName,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
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
                $student = DB::table('students')->where('user_id', $id)->first();
                if ($student) {
                    DB::table('parent_students')->where('student_id', $student->student_id)->delete();
                    DB::table('students')->where('student_id', $student->student_id)->delete();
                }
            } elseif ($roleId == 2) {
                $teacher = DB::table('teachers')->where('user_id', $id)->first();
                if ($teacher) {
                    DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->delete();
                    DB::table('teachers')->where('teacher_id', $teacher->teacher_id)->delete();
                }
            } elseif ($roleId == 5) {
                DB::table('heads')->where('user_id', $id)->delete();
            } elseif ($roleId == 4) {
                $parent = DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    DB::table('parent_students')->where('parent_id', $parent->parent_id)->delete();
                    DB::table('parents')->where('parent_id', $parent->parent_id)->delete();
                }
            }

            // Finally, delete user from users table
            DB::table('users')->where('user_id', $id)->delete();
        }

        return redirect()->route('admin.accounts')->with('success', 'تم حذف الحسابات المحددة نهائياً وبنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  COURSES (PROGRAMS) MANAGEMENT
    // ────────────────────────────────────────────────────────────

    public function courses()
    {
        $departments = DB::table('departments')->orderBy('name')->get();

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

        return view('admin.courses', compact('programs', 'departments'));
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
        foreach ($departments as $dept) {
            $head = DB::table('heads')
                ->join('users', 'heads.user_id', '=', 'users.user_id')
                ->where('heads.department_id', $dept->department_id)
                ->select('users.user_id', 'users.full_name', 'users.email', 'users.phone')
                ->first();

            $dept->current_hod_name = $head ? $head->full_name : 'غير مخصص حالياً';
            $dept->current_hod_user_id = $head ? $head->user_id : null;
        }

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

        return redirect()->route('admin.courses.assign-hod')->with('success', 'تم تعيين ' . $user->full_name . ' رئيساً لقسم ' . $dept->name . ' بنجاح!');
    }

    public function storeNewHOD(Request $request)
    {
        $fullName = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $request->full_name ?? '';
        }
        $request->merge(['full_name' => $fullName]);

        $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'phone'         => 'required|string|max:20',
            'email'         => 'required|email|unique:users,email|max:255',
            'username'      => 'required|string|unique:users,username|max:255',
            'password'      => 'required|string|min:6|confirmed',
        ], [
            'department_id.required' => 'يرجى اختيار القسم.',
            'first_name.required'    => 'الاسم الأول مطلوب.',
            'last_name.required'     => 'الاسم الثاني مطلوب.',
            'phone.required'         => 'رقم الهاتف مطلوب.',
            'email.required'         => 'البريد الإلكتروني مطلوب.',
            'email.unique'           => 'البريد الإلكتروني مستخدم بالفعل.',
            'username.required'      => 'اسم المستخدم مطلوب.',
            'username.unique'        => 'اسم المستخدم مستخدم بالفعل.',
            'password.required'      => 'كلمة المرور مطلوبة.',
            'password.confirmed'     => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();

        // Remove old HOD for this department if exists
        $oldHead = DB::table('heads')->where('department_id', $request->department_id)->first();
        if ($oldHead) {
            DB::table('users')->where('user_id', $oldHead->user_id)->update([
                'role_id'    => 2, // revert to teacher
                'department' => null,
                'updated_at' => now(),
            ]);
            DB::table('heads')->where('department_id', $request->department_id)->delete();
        }

        // Create new HOD user
        $userId = DB::table('users')->insertGetId([
            'role_id'    => 5, // HOD
            'full_name'  => $fullName,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'username'   => $request->username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => bcrypt($request->password),
            'department' => $dept->name,
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert into heads table
        DB::table('heads')->insert([
            'user_id'       => $userId,
            'department_id' => $request->department_id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('admin.courses.assign-hod')->with('success', 'تم إنشاء حساب رئيس القسم الجديد (' . $fullName . ') لقسم ' . $dept->name . ' وتعيينه بنجاح!');
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
                ->select('lesson_id', 'title', 'description', 'file_path', 'file_name', 'file_type', 'content_url', 'created_at')
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
    //  LECTURES (محاضرات المعلمين)
    // ────────────────────────────────────────────────────────────
    public function lectures(Request $request)
    {
        $selectedDept    = $request->query('department_id');
        $selectedProgram = $request->query('program_id');
        $selectedYear    = $request->query('year');
        $selectedCourse  = $request->query('course_id');

        $departments = DB::table('departments')->get();

        $programsQuery = DB::table('programs');
        if ($selectedDept) {
            $programsQuery->where('department_id', $selectedDept);
        }
        $programs = $programsQuery->get();

        $coursesQuery = DB::table('courses');
        if ($selectedYear) {
            $coursesQuery->where('year', $selectedYear);
        }
        if ($selectedProgram) {
            $cIds = DB::table('course_program')->where('program_id', $selectedProgram)->pluck('course_id');
            $coursesQuery->whereIn('course_id', $cIds);
        } elseif ($selectedDept) {
            $pIds = DB::table('programs')->where('department_id', $selectedDept)->pluck('id');
            $cIds = DB::table('course_program')->whereIn('program_id', $pIds)->pluck('course_id');
            $coursesQuery->whereIn('course_id', $cIds);
        }
        $courses = $coursesQuery->get();

        foreach ($courses as $c) {
            $progs = DB::table('course_program')
                ->join('programs', 'course_program.program_id', '=', 'programs.id')
                ->where('course_program.course_id', $c->course_id)
                ->select('programs.id as program_id', 'programs.department_id')
                ->get();

            $c->program_ids = $progs->pluck('program_id')->toArray();
            $c->department_ids = $progs->pluck('department_id')->unique()->toArray();

            // Teachers assigned to this course
            $teacherNames = DB::table('course_teachers')
                ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('course_teachers.course_id', $c->course_id)
                ->pluck('users.full_name')
                ->toArray();

            $c->teacher_names = implode('، ', $teacherNames);
        }

        $query = DB::table('lessons')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->leftJoin('teachers', 'lessons.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.user_id')
            ->where(function($q) {
                $q->whereNull('lessons.type')
                  ->orWhere('lessons.type', '!=', 'session');
            })
            ->where('lessons.title', 'not like', '%حضور%')
            ->where('lessons.title', 'not like', '%غياب%')
            ->where('lessons.title', 'not like', '%تفقد%')
            ->where('lessons.title', 'not like', '%حصة%')
            ->where(function($q) {
                $q->whereNull('lessons.content_url')
                  ->orWhere('lessons.content_url', 'not like', '%attendance%');
            });

        if ($selectedCourse) {
            $query->where('lessons.course_id', $selectedCourse);
        } else {
            if ($selectedYear) {
                $query->where('courses.year', $selectedYear);
            }

            if ($selectedProgram) {
                $courseIds = DB::table('course_program')
                    ->where('program_id', $selectedProgram)
                    ->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($selectedDept) {
                $progIds = DB::table('programs')
                    ->where('department_id', $selectedDept)
                    ->pluck('id');
                $courseIds = DB::table('course_program')
                    ->whereIn('program_id', $progIds)
                    ->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }
        }

        $lectures = $query->select(
            'lessons.*',
            'courses.title as course_title',
            'courses.year as course_year',
            'users.full_name as teacher_name'
        )
        ->orderByDesc('lessons.created_at')
        ->get();

        // Get teacher info for selected course or filtered view
        $assignedTeachers = [];
        if ($selectedCourse) {
            $assignedTeachers = DB::table('course_teachers')
                ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('course_teachers.course_id', $selectedCourse)
                ->pluck('users.full_name')
                ->toArray();
        }

        return view('admin.lectures', compact(
            'lectures', 'courses', 'departments', 'programs',
            'selectedDept', 'selectedProgram', 'selectedYear', 'selectedCourse',
            'assignedTeachers'
        ));
    }

    // ────────────────────────────────────────────────────────────
    //  REPORTS
    // ────────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $departments = DB::table('departments')->get();
        $programs = DB::table('programs')->get();
        $semesters = DB::table('semesters')->orderByDesc('start_date')->get();

        $savedReports = DB::table('admin_generated_reports')->orderByDesc('created_at')->get();

        $previewReport = null;
        $reportData = null;
        $reportType = null;

        if ($request->has('view_id')) {
            $previewReport = DB::table('admin_generated_reports')->where('id', $request->view_id)->first();
            if ($previewReport) {
                $reportType = $previewReport->report_type;
                $reportData = $this->fetchReportData($previewReport);
            }
        }

        return view('admin.reports', compact('departments', 'programs', 'semesters', 'savedReports', 'previewReport', 'reportType', 'reportData'));
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:attendance,performance',
            'from_date'   => 'nullable|date|before_or_equal:today',
            'to_date'     => 'nullable|date|before_or_equal:today|after_or_equal:from_date',
        ], [
            'from_date.before_or_equal' => 'تاريخ البداية لا يمكن أن يكون تاريخاً في المستقبل.',
            'to_date.before_or_equal'   => 'تاريخ النهاية لا يمكن أن يكون تاريخاً في المستقبل.',
            'to_date.after_or_equal'    => 'تاريخ النهاية يجب أن يكون بعد أو يطابق تاريخ البداية.',
        ]);

        $deptName = $request->department_id ? (DB::table('departments')->where('department_id', $request->department_id)->value('name') ?? 'جميع الأقسام') : 'جميع الأقسام';
        $progName = $request->program_id ? (DB::table('programs')->where('id', $request->program_id)->value('name') ?? 'جميع الدورات') : 'جميع الدورات';
        $semName  = $request->semester_id ? (DB::table('semesters')->where('semester_id', $request->semester_id)->value('name') ?? 'جميع الفصول') : 'جميع الفصول';

        $typeName = $request->report_type === 'attendance' ? 'تقرير نسب الحضور والغياب' : 'تقرير أداء ودرجات الطلاب';
        $title = $typeName . ' - قسم ' . $deptName;

        DB::table('admin_generated_reports')->insert([
            'title'           => $title,
            'report_type'     => $request->report_type,
            'department_id'   => $request->department_id,
            'department_name' => $deptName,
            'program_id'      => $request->program_id,
            'program_name'    => $progName,
            'semester_id'     => $request->semester_id,
            'semester_name'   => $semName,
            'from_date'       => $request->from_date,
            'to_date'         => $request->to_date,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return redirect()->route('admin.reports')->with('success', 'تم إنشاء التقرير الإداري وإضافته إلى سجل التقارير بنجاح.');
    }

    public function deleteReport($id)
    {
        DB::table('admin_generated_reports')->where('id', $id)->delete();
        return redirect()->route('admin.reports')->with('success', 'تم حذف التقرير من السجل بنجاح.');
    }

    private function fetchReportData($report)
    {
        if ($report->report_type === 'attendance') {
            $query = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name',
                    DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    DB::raw("COALESCE(semesters.name, 'عام') as semester_name"),
                    DB::raw('COUNT(*) as total_sessions'),
                    DB::raw("SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('users.full_name', 'departments.name', 'programs.name', 'courses.title', 'semesters.name');

            if ($report->from_date) {
                $query->where('attendance.attendance_date', '>=', $report->from_date);
            }
            if ($report->to_date) {
                $query->where('attendance.attendance_date', '<=', $report->to_date);
            }
            if ($report->semester_id) {
                $query->where('courses.semester_id', $report->semester_id);
            }
            if ($report->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $report->program_id)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($report->department_id) {
                $programIds = DB::table('programs')->where('department_id', $report->department_id)->pluck('id');
                $courseIds = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }

            return $query->limit(100)->get();
        } else {
            $query = DB::table('grades')
                ->join('students', 'grades.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name',
                    DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    'grades.score as grade',
                    DB::raw("COALESCE(semesters.name, 'عام') as semester")
                );

            if ($report->semester_id) {
                $query->where('courses.semester_id', $report->semester_id);
            }
            if ($report->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $report->program_id)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            } elseif ($report->department_id) {
                $programIds = DB::table('programs')->where('department_id', $report->department_id)->pluck('id');
                $courseIds = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            }

            return $query->limit(100)->get();
        }
    }

    public function exportReport(Request $request)
    {
        $request->validate(['report_type' => 'required|in:attendance,performance']);

        $reportType   = $request->report_type;
        $exportFormat = $request->input('export_format', 'excel');
        $deptName     = $request->department_id ? (DB::table('departments')->where('department_id', $request->department_id)->value('name') ?? 'جميع الأقسام') : 'جميع الأقسام';
        $progName     = $request->program_id    ? (DB::table('programs')->where('id', $request->program_id)->value('name') ?? 'جميع الدورات') : 'جميع الدورات';
        $semesterName = $request->semester_id   ? (DB::table('semesters')->where('semester_id', $request->semester_id)->value('name') ?? 'جميع الفصول') : 'جميع الفصول';
        $dateLabel    = date('Y-m-d H:i');

        if ($reportType === 'attendance') {
            $query = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name as student_name',
                    DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    DB::raw("COALESCE(semesters.name, 'عام') as semester_name"),
                    DB::raw("COUNT(*) as total_sessions"),
                    DB::raw("SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('users.full_name', 'departments.name', 'programs.name', 'courses.title', 'semesters.name');

            if ($request->semester_id) $query->where('courses.semester_id', $request->semester_id);
            if ($request->from_date)   $query->where('attendance.attendance_date', '>=', $request->from_date);
            if ($request->to_date)     $query->where('attendance.attendance_date', '<=', $request->to_date);
            if ($request->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $request->program_id)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds  = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }

            $currentYear = date('Y');
            $data        = $query->orderBy('departments.name')->orderBy('users.full_name')->get();
            $reportTitle = "تقرير حضور {$currentYear}";
            $filename    = "{$reportTitle}_{$dateLabel}.xls";

            $rowsHtml = '';
            $i = 1;
            foreach ($data as $row) {
                $rate = $row->total_sessions > 0 ? round(($row->present_count / $row->total_sessions) * 100) : 0;

                $rowsHtml .= "<tr>
                    <td style='text-align:center;'>{$i}</td>
                    <td style='font-weight:bold;color:#0f172a;'>{$row->student_name}</td>
                    <td style='color:#2563eb;font-weight:bold;'>{$row->department_name}</td>
                    <td style='color:#475569;'>{$row->program_name}</td>
                    <td style='color:#0f172a;'>{$row->course_title}</td>
                    <td style='text-align:center;'>{$row->semester_name}</td>
                    <td style='color:#15803d;font-weight:bold;text-align:center;'>{$row->present_count}</td>
                    <td style='color:#b91c1c;font-weight:bold;text-align:center;'>{$row->absent_count}</td>
                    <td style='text-align:center;font-weight:bold;'>{$row->total_sessions}</td>
                    <td style='font-weight:bold;text-align:center;'>{$rate}%</td>
                </tr>";
                $i++;
            }

            $headersHtml = "<tr>
                <th style='width:40px;text-align:center;'>#</th>
                <th>اسم الطالب</th>
                <th>القسم</th>
                <th>الدورة / البرنامج</th>
                <th>المادة الدراسية</th>
                <th style='text-align:center;'>الفصل</th>
                <th style='text-align:center;'>حاضر</th>
                <th style='text-align:center;'>غائب</th>
                <th style='text-align:center;'>الإجمالي</th>
                <th style='text-align:center;'>نسبة الحضور</th>
            </tr>";

        } else {
            $query = DB::table('grades')
                ->join('students', 'grades.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name as student_name',
                    DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    'grades.score as grade',
                    DB::raw("COALESCE(semesters.name, 'عام') as semester_name")
                );

            if ($request->semester_id) $query->where('courses.semester_id', $request->semester_id);
            if ($request->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $request->program_id)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            } elseif ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds  = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            }

            $currentYear = date('Y');
            $data        = $query->orderBy('departments.name')->orderBy('users.full_name')->get();
            $reportTitle = "تقرير أداء {$currentYear}";
            $filename    = "{$reportTitle}_{$dateLabel}.xls";

            $rowsHtml = '';
            $i = 1;
            foreach ($data as $row) {
                $g = $row->grade;
                if ($g >= 90) { $rating = 'ممتاز'; $pass = 'ناجح'; $bg = '#dcfce7'; $clr = '#15803d'; }
                elseif ($g >= 80) { $rating = 'جيد جداً'; $pass = 'ناجح'; $bg = '#dbeafe'; $clr = '#1d4ed8'; }
                elseif ($g >= 70) { $rating = 'جيد'; $pass = 'ناجح'; $bg = '#fef3c7'; $clr = '#b45309'; }
                elseif ($g >= 60) { $rating = 'مقبول'; $pass = 'ناجح'; $bg = '#f3e8ff'; $clr = '#6b21a8'; }
                else { $rating = 'راسب'; $pass = 'راسب'; $bg = '#fee2e2'; $clr = '#b91c1c'; }

                $rowsHtml .= "<tr>
                    <td style='text-align:center;'>{$i}</td>
                    <td style='font-weight:bold;color:#0f172a;'>{$row->student_name}</td>
                    <td style='color:#2563eb;font-weight:bold;'>{$row->department_name}</td>
                    <td style='color:#475569;'>{$row->program_name}</td>
                    <td style='color:#0f172a;'>{$row->course_title}</td>
                    <td style='text-align:center;'>{$row->semester_name}</td>
                    <td style='font-weight:bold;text-align:center;color:#0f172a;'>{$row->grade}</td>
                    <td style='background:{$bg};color:{$clr};font-weight:bold;text-align:center;'>{$rating}</td>
                    <td style='background:{$bg};color:{$clr};font-weight:bold;text-align:center;'>{$pass}</td>
                </tr>";
                $i++;
            }

            $headersHtml = "<tr>
                <th style='width:40px;text-align:center;'>#</th>
                <th>اسم الطالب</th>
                <th>القسم</th>
                <th>الدورة / البرنامج</th>
                <th>المادة الدراسية</th>
                <th style='text-align:center;'>الفصل</th>
                <th style='text-align:center;'>الدرجة (/100)</th>
                <th style='text-align:center;'>التقدير</th>
                <th style='text-align:center;'>النتيجة</th>
            </tr>";
        }

        if ($exportFormat === 'pdf') {
            $pdfHtml = "
            <!DOCTYPE html>
            <html lang='ar' dir='rtl'>
            <head>
                <meta charset='UTF-8'>
                <title>{$reportTitle}</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 25px; color: #1e293b; background: #f8fafc; direction: rtl; }
                    .page-wrapper { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
                    .header-banner { text-align: center; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px; }
                    .header-banner h1 { margin: 0; color: #0f172a; font-size: 22px; font-weight: 800; }
                    .meta-grid { display: flex; justify-content: space-between; background: #edf2f7; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 20px; margin-bottom: 20px; font-size: 12px; color: #334155; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #ffffff; border-radius: 8px; overflow: hidden; }
                    th { background-color: #1e293b; color: #f8fafc; text-align: right; padding: 11px 12px; font-size: 12px; font-weight: bold; border: 1px solid #334155; }
                    td { padding: 9px 12px; font-size: 11px; border: 1px solid #e2e8f0; text-align: right; color: #1e293b; }
                    tr:nth-child(even) td { background-color: #f1f5f9; }
                    tr:nth-child(odd) td { background-color: #ffffff; }
                    .footer-note { margin-top: 30px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 12px; }
                    @media print {
                        body { background: #ffffff; padding: 0; }
                        .page-wrapper { border: none; padding: 0; box-shadow: none; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class='no-print' style='margin-bottom: 18px; text-align: left;'>
                    <button onclick='window.print()' style='background: #2563eb; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 13px; box-shadow: 0 4px 12px rgba(37,99,235,0.2);'>طباعة / حفظ كـ PDF</button>
                </div>
                <div class='page-wrapper'>
                    <div class='header-banner'>
                        <h1>{$reportTitle}</h1>
                    </div>
                    <div class='meta-grid'>
                        <span><strong>القسم:</strong> {$deptName}</span>
                        <span><strong>الدورة:</strong> {$progName}</span>
                        <span><strong>الفصل:</strong> {$semesterName}</span>
                        <span><strong>تاريخ الإصدار:</strong> {$dateLabel}</span>
                    </div>
                    <table>
                        <thead>{$headersHtml}</thead>
                        <tbody>{$rowsHtml}</tbody>
                    </table>
                    <div class='footer-note'>تم استخراج التقرير بتاريخ {$dateLabel}</div>
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(function() { window.print(); }, 400);
                    };
                </script>
            </body>
            </html>";

            return response($pdfHtml)->header('Content-Type', 'text/html; charset=utf-8');
        }

        // Excel Export formatting with eye-friendly tones (slate/soft gray)
        $excelHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, sans-serif; direction: rtl; background: #f8fafc; }
            table { border-collapse: collapse; width: 100%; }
            .main-hdr { background: #1e293b; color: #f8fafc; font-size: 15px; font-weight: bold; text-align: center; padding: 14px; }
            .info-hdr { background: #edf2f7; color: #1e293b; font-size: 11px; font-weight: bold; padding: 9px; border: 1px solid #cbd5e1; }
            th { background: #334155; color: #ffffff; font-weight: bold; font-size: 12px; border: 1px solid #475569; padding: 10px; text-align: right; }
            td { border: 1px solid #cbd5e1; padding: 8px; font-size: 11px; text-align: right; color: #1e293b; }
            tr:nth-child(even) td { background: #f1f5f9; }
            tr:nth-child(odd) td { background: #ffffff; }
        </style>
        </head>
        <body>
        <table>
            <tr><td colspan='10' class='main-hdr'>{$reportTitle}</td></tr>
            <tr>
                <td colspan='3' class='info-hdr'><b>القسم المستهدف:</b> {$deptName}</td>
                <td colspan='3' class='info-hdr'><b>الدورة / البرنامج:</b> {$progName}</td>
                <td colspan='2' class='info-hdr'><b>الفصل الدراسي:</b> {$semesterName}</td>
                <td colspan='2' class='info-hdr'><b>تاريخ الإصدار:</b> {$dateLabel}</td>
            </tr>
            {$headersHtml}
            {$rowsHtml}
        </table>
        </body>
        </html>";

        return response("\xEF\xBB\xBF" . $excelHtml)
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
            'level'       => 'nullable|string',
            'year'        => 'required|integer|in:1,2',
            'semester_id' => 'required|integer',
            'program_id'  => 'required|integer',
            'hours'       => 'required|integer|min:1',
        ]);

        // حفظ المادة مع السنة
        $courseId = DB::table('courses')->insertGetId([
            'title'       => $request->title,
            'description' => $request->description,
            'level'       => $request->level ?? 'عام',
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
            'level'       => 'nullable|string',
            'year'        => 'required|integer|in:1,2',
            'semester_id' => 'required|integer',
            'program_id'  => 'required|integer',
            'hours'       => 'required|integer|min:1',
        ]);

        DB::table('courses')->where('course_id', $id)->update([
            'title'       => $request->title,
            'description' => $request->description,
            'level'       => $request->level ?? 'عام',
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
        // جلب الطلبات التي وصلت للإدارة أو انتهت
        $requests = \App\Models\StudentRequest::with(['student.user', 'student.program.department'])
                    ->whereIn('status', ['pending_admin', 'completed'])
                    ->orderBy('created_at', 'desc')
                    ->get();
        return view('admin.student-services', compact('requests'));
    }

    public function processStudentService(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:approved,rejected',
            'notes' => 'required|string' // قرار الإدارة يجب أن يحوي ملاحظات
        ]);

        $studentReq = \App\Models\StudentRequest::findOrFail($id);
        
        $studentReq->admin_decision = $request->decision;
        $studentReq->admin_notes = $request->notes;
        
        // قرار الإدارة هو النهائي
        $studentReq->status = 'completed';
        
        $studentReq->save();

        // إرسال إشعار فوري للطالب بالقرار
        \App\Models\Notification::create([
            'user_id' => $studentReq->student->user_id,
            'title'   => 'تحديث حالة طلبك',
            'message' => 'تم الرد على طلبك من قبل الإدارة بالقرار: ' . ($request->decision === 'approved' ? 'مقبول' : 'مرفوض') . '، يرجى مراجعة تفاصيل الطلب لمعرفة السبب.',
            'type'    => 'system',
            'is_read' => false,
        ]);

        return back()->with('success', 'تم اتخاذ القرار النهائي بنجاح وتم إغلاق الطلب وإرسال إشعار للطالب.');
    }
}
