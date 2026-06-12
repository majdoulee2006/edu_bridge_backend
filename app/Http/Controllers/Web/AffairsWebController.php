<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Notification;
use App\Models\Message;
use App\Models\AbsenceRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CalendarEvent;
use App\Models\Announcement;
use App\Services\TelegramService;

class AffairsWebController extends Controller
{
    // ─────────────────────────── Auth ───────────────────────────
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('affairs.dashboard');
        }
        return view('affairs.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            // تحقق أن المستخدم لديه دور موظف الشؤون
            if (Auth::user()->role_id !== 6) {
                Auth::logout();
                return back()->withErrors(['email' => 'هذا الحساب ليس حساب موظف شؤون.']);
            }

            // تحقق أن الحساب نشط
            if (Auth::user()->status !== 'active') {
                Auth::logout();
                return back()->withErrors(['email' => 'حسابك موقوف. يرجى التواصل مع الإدارة.']);
            }

            $request->session()->regenerate();

            // تحديث آخر تسجيل دخول
            Auth::user()->update(['last_login' => now()]);

            return redirect()->route('affairs.dashboard');
        }

        return back()->withErrors(['email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('affairs.login');
    }

    // ─────────────────────────── Dashboard ───────────────────────────
    public function dashboard()
    {
        // role_id: 2=teacher, 3=student, 5=head, 6=affairs
        $totalStudents = User::where('role_id', 3)->count();
        $totalTeachers = User::where('role_id', 2)->count();
        $totalStaff    = User::whereIn('role_id', [2, 5, 6])->count();
        $pendingLeaves = AbsenceRequest::where('status', 'pending')->count();
        $totalUsers    = User::count();

        // آخر 5 طلبات إجازة
        $recentLeaves = AbsenceRequest::with('student.user')
            ->latest()
            ->take(5)
            ->get();

        // إعلانات الكاروسيل — آخر 5 إعلانات عامة
        $carouselAnnouncements = Announcement::with('user')
            ->where('type', 'general')
            ->latest()
            ->take(5)
            ->get();

        // منشورات الإدارة — آخر 6 إعلانات
        $posts = Announcement::with('user')
            ->latest()
            ->take(6)
            ->get();

        // إشعارات المستخدم الحالي
        $recentNotifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->take(5)
            ->get();

        return view('affairs.dashboard', compact(
            'totalStudents',
            'totalTeachers',
            'totalStaff',
            'pendingLeaves',
            'totalUsers',
            'recentLeaves',
            'carouselAnnouncements',
            'posts',
            'recentNotifications'
        ));
    }

    // ─────────────────────────── Calendar ───────────────────────────
    public function calendar()
    {
        $events = CalendarEvent::where('user_id', Auth::id())
            ->orderBy('event_date', 'asc')
            ->get();
        return view('affairs.calendar', compact('events'));
    }

    public function storeCalendarEvent(Request $request)
    {
        $request->validate([
            'event_date' => 'required|date',
            'title'      => 'required|string|max:255',
            'event_time' => 'nullable',
            'location'   => 'nullable|string|max:255',
        ]);

        CalendarEvent::create([
            'user_id'    => Auth::id(),
            'event_date' => $request->event_date,
            'title'      => $request->title,
            'event_time' => $request->event_time,
            'location'   => $request->location,
        ]);

        return back()->with('success', 'تم إضافة الحدث بنجاح إلى قاعدة البيانات.');
    }

    public function updateCalendarEvent(Request $request, $id)
    {
        $request->validate([
            'event_date' => 'required|date',
            'title'      => 'required|string|max:255',
            'event_time' => 'nullable',
            'location'   => 'nullable|string|max:255',
        ]);

        $event = CalendarEvent::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $event->update([
            'event_date' => $request->event_date,
            'title'      => $request->title,
            'event_time' => $request->event_time,
            'location'   => $request->location,
        ]);

        return back()->with('success', 'تم تحديث الحدث بنجاح.');
    }

    public function deleteCalendarEvent($id)
    {
        $event = CalendarEvent::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $event->delete();

        return back()->with('success', 'تم حذف الحدث بنجاح.');
    }

    // ─────────────────────────── Activities ───────────────────────────
    public function activities()
    {
        $events = CalendarEvent::where('user_id', Auth::id())
            ->orderBy('event_date', 'asc')
            ->get();

        return view('affairs.activities', compact('events'));
    }

    // ─────────────────────────── Accounts (معلم + رئيس قسم فقط) ────
    public function accounts()
    {
        $users = User::whereIn('role_id', [2, 3, 4, 5, 6])->with('student')->latest()->get();
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
        
        $courses = DB::table('courses')->orderBy('title')->get();
        return view('affairs.accounts', compact('users', 'departments', 'courses', 'deptCourses', 'deptBranches'));
    }

    public function resetStudentDevice(Request $request, int $studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return back()->with('error', 'الطالب غير موجود.');
        }

        $student->update([
            'device_id'        => null,
            'is_device_locked' => 0,
        ]);

        return back()->with('success', 'تم إعادة تسجيل الجهاز بنجاح. يمكن للطالب الآن تسجيل الدخول من جهاز جديد.');
    }

    public function updateAccount(Request $request, $id)
    {
        $user = DB::table('users')->where('user_id', $id)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'المستخدم غير موجود.');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'required|email|max:255|unique:users,email,' . $id . ',user_id',
            'password'  => 'nullable|string|min:6|confirmed',
        ], [
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $updates = [
            'full_name'  => $request->full_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'updated_at' => now(),
        ];

        if ($request->filled('password')) {
            $updates['password'] = bcrypt($request->password);
        }

        DB::table('users')->where('user_id', $id)->update($updates);

        return redirect()->back()->with('success', 'تم تحديث بيانات الحساب بنجاح!');
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'role_id'   => 'required|integer|in:2,5', // معلم أو رئيس قسم فقط
            'password'  => 'required|min:6',
            'phone'     => 'nullable|string|max:20',
        ], [
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'role_id.in'   => 'يمكن إنشاء حسابات للمعلمين ورؤساء الأقسام فقط.',
        ]);

        if ($request->role_id == 5) {
            $request->validate([
                'department_id' => 'required|exists:departments,department_id'
            ]);
        } elseif ($request->role_id == 2) {
            $request->validate([
                'department_id'  => 'required|exists:departments,department_id',
                'specialization' => 'required|string|max:255',
                'courses'        => 'nullable|array'
            ]);
        }

        $baseUsername = explode('@', $request->email)[0];
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter++;
        }

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();

        $user = User::create([
            'full_name'  => $request->full_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'role_id'    => $request->role_id,
            'department' => $dept ? $dept->name : null,
            'password'   => Hash::make($request->password),
            'status'     => 'active',
            'username'   => $username,
        ]);

        if ((int) $request->role_id === 2) {
            $teacherId = DB::table('teachers')->insertGetId([
                'user_id'        => $user->user_id,
                'specialization' => $request->specialization ?? 'عام',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

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
        } elseif ((int) $request->role_id === 5) {
            DB::table('heads')->insert([
                'user_id'       => $user->user_id,
                'department_id' => $request->department_id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        return back()->with('success', 'تم إنشاء الحساب بنجاح.');
    }

    // ─────────────────────────── الأرقام الجامعية ────────────────────
    public function universityIds()
    {
        $ids = DB::table('university_ids')->orderByDesc('created_at')->get();
        return view('affairs.university_ids', compact('ids'));
    }

    public function storeUniversityId(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
        ]);

        $year = date('Y');
        $lastId = DB::table('university_ids')
            ->where('university_id', 'like', $year . '%')
            ->orderBy('university_id', 'desc')
            ->value('university_id');

        if ($lastId) {
            $increment = intval(substr($lastId, 4)) + 1;
            $newId = $year . str_pad($increment, 2, '0', STR_PAD_LEFT);
        } else {
            $newId = $year . '01';
        }

        $telegramChatId = $request->telegram_chat_id ? trim($request->telegram_chat_id) : null;

        DB::table('university_ids')->insert([
            'university_id'    => $newId,
            'full_name'        => $request->full_name,
            'role'             => 'student',
            'is_used'          => false,
            'telegram_chat_id' => $telegramChatId,
            'created_by'       => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return back()->with('success', 'تم إضافة الرقم الجامعي بنجاح وتوليد الرقم: ' . $newId);
    }

    public function updateUniversityId(Request $request, $id)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
        ]);

        $uid = DB::table('university_ids')->where('id', $id)->first();
        if (!$uid) {
            return back()->with('error', 'الرقم الجامعي غير موجود.');
        }

        DB::table('university_ids')->where('id', $id)->update([
            'full_name' => $request->full_name,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم تحديث الاسم بنجاح.');
    }

    public function deleteUniversityId($id)
    {
        $uid = DB::table('university_ids')->where('id', $id)->first();
        if ($uid && $uid->is_used) {
            return back()->with('error', 'لا يمكن حذف رقم مستخدم.');
        }
        DB::table('university_ids')->where('id', $id)->delete();
        return back()->with('success', 'تم الحذف.');
    }

    // ─────────────────────────── طلبات الحسابات المعلّقة ─────────────
    public function pendingAccounts()
    {
        $pending = User::whereIn('role_id', [3, 4])
            ->where('status', 'inactive')
            ->orderByDesc('created_at')
            ->get();
        return view('affairs.pending_accounts', compact('pending'));
    }

    public function approveAccount($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        // ---- إضافة ربط الأبناء بولي الأمر عند الموافقة ----
        if ($user->role_id == 4 && !empty($user->children_ids)) {
            $parent = DB::table('parents')->where('user_id', $user->user_id)->first();
            if ($parent) {
                foreach ($user->children_ids as $universityId) {
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
        // ---------------------------------------------------

        $notifTitle = 'تم تفعيل حسابك ✓';
        $notifMsg   = 'مرحباً ' . $user->full_name . '! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.';
        DB::table('notifications')->insert([
            'user_id'    => $user->user_id,
            'sender_id'  => Auth::user()->user_id,
            'title'      => $notifTitle,
            'message'    => $notifMsg,
            'type'       => 'administrative',
            'category'   => 'administrative',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser($user->user_id, $notifTitle, $notifMsg, ['type' => 'administrative']);

        // إرسال إشعار تليجرام للموافقة والتفعيل
        if ($user->telegram_chat_id) {
            try {
                $telegram = new TelegramService();
                $text = "🎓 <b>تفعيل الحساب - Edu Bridge</b>\n\n"
                      . "مرحباً <b>{$user->full_name}</b>،\n\n"
                      . "🎉 لقد تم <b>الموافقة وتفعيل حسابك بنجاح</b> من قِبل إدارة شؤون الطلاب!\n\n"
                      . "📲 يمكنك الآن فتح التطبيق وتسجيل الدخول مباشرة.";
                $telegram->sendMessage((int) $user->telegram_chat_id, $text);
            } catch (\Exception $e) {
                Log::error('Telegram approveAccount notification error: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'تم تفعيل الحساب.');
    }

    public function rejectAccount($id)
    {
        $user = User::findOrFail($id);

        // إرسال إشعار تليجرام للرفض قبل الحذف
        if ($user->telegram_chat_id) {
            try {
                $telegram = new TelegramService();
                $text = "🎓 <b>طلب التسجيل - Edu Bridge</b>\n\n"
                      . "مرحباً <b>{$user->full_name}</b>،\n\n"
                      . "⚠️ نأسف لإعلامك بأنه تم <b>رفض طلب إنشاء وتفعيل حسابك</b> من قِبل إدارة شؤون الطلاب.\n\n"
                      . "يرجى مراجعة شؤون الطلاب لمزيد من التفاصيل.";
                $telegram->sendMessage((int) $user->telegram_chat_id, $text);
            } catch (\Exception $e) {
                Log::error('Telegram rejectAccount notification error: ' . $e->getMessage());
            }
        }

        if ($user->university_id) {
            DB::table('university_ids')
                ->where('university_id', $user->university_id)
                ->update(['is_used' => false]);
        }
        DB::table('students')->where('user_id', $id)->delete();
        DB::table('parents')->where('user_id', $id)->delete();
        $user->delete();
        return back()->with('success', 'تم رفض الطلب وحذفه.');
    }

    public function toggleAccountStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();
        return back()->with('success', 'تم تحديث حالة الحساب.');
    }

    public function deleteAccount($id)
    {
        User::findOrFail($id)->delete();
        return back()->with('success', 'تم حذف الحساب بنجاح.');
    }

    // ─────────────────────────── Leaves ───────────────────────────
    public function leaves()
    {
        $leaves = AbsenceRequest::with('student.user')
            ->latest()
            ->get();

        $pendingCount  = $leaves->where('status', 'pending')->count();
        $approvedCount = $leaves->where('status', 'approved')->count();
        $rejectedCount = $leaves->where('status', 'rejected')->count();

        return view('affairs.leaves', compact('leaves', 'pendingCount', 'approvedCount', 'rejectedCount'));
    }

    public function updateLeaveStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);

        $leave = AbsenceRequest::findOrFail($id);
        $leave->status      = $request->status;
        $leave->reviewed_by = Auth::id();
        $leave->save();

        return back()->with('success', 'تم تحديث حالة طلب الإجازة.');
    }

    // ─────────────────────────── Messages ───────────────────────────
    public function messages()
    {
        $currentUserId = Auth::id();

        // جلب كل المحادثات الفريدة
        $conversations = Message::with(['sender', 'receiver'])
            ->where('sender_id', $currentUserId)
            ->orWhere('receiver_id', $currentUserId)
            ->latest()
            ->get()
            ->map(function ($msg) use ($currentUserId) {
                $contactId = ($msg->sender_id == $currentUserId) ? $msg->receiver_id : $msg->sender_id;
                return $contactId;
            })
            ->unique()
            ->values();

        $contacts = User::whereIn('user_id', $conversations)->get();

        // قائمة كل المستخدمين للرسالة الجديدة
        $allUsers = User::where('user_id', '!=', $currentUserId)->get();

        return view('affairs.messages', compact('contacts', 'allUsers'));
    }

    public function getConversation($userId)
    {
        $currentUserId = Auth::id();
        $messages = Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)->where('receiver_id', $userId);
            })
            ->orWhere(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // تحديد الرسائل كمقروءة
        Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'message'     => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'is_read'     => false,
        ]);

        Notification::create([
            'user_id' => $request->receiver_id,
            'title'   => 'رسالة جديدة',
            'message' => 'لقد تلقيت رسالة جديدة من ' . Auth::user()->full_name,
            'type'    => 'message',
            'is_read' => false,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message->load('sender')]);
        }

        return back()->with('success', 'تم إرسال الرسالة.');
    }

    // ─────────────────────────── Notifications ───────────────────────────
    public function notifications()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        return view('affairs.notifications', compact('notifications', 'unreadCount'));
    }

    public function markNotificationRead($id)
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return back()->with('success', 'تم تحديد الإشعار كمقروء.');
    }

    public function markAllNotificationsRead()
    {
        Notification::where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
    }

    // ─────────────────────────── Profile ───────────────────────────
    public function profile()
    {
        $user = Auth::user();

        // إحصائيات بسيطة
        $reviewedLeaves = AbsenceRequest::where('reviewed_by', $user->user_id)->count();
        $sentMessages   = Message::where('sender_id', $user->user_id)->count();

        return view('affairs.profile', compact('user', 'reviewedLeaves', 'sentMessages'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
        ]);

        $user->update([
            'full_name' => $request->full_name,
            'phone'     => $request->phone,
            'email'     => $request->email,
        ]);

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }

    // ─────────────────────────── Settings ───────────────────────────
    public function settings()
    {
        return view('affairs.settings');
    }

    // ─────────────────────────── Announcements ───────────────────────────
    public function announcements()
    {
        return view('affairs.announcements');
    }

    // ===== التقارير =====

    public function reports()
    {
        // التقارير المنجزة (الصادرة)
        $reports = DB::table('performance_reports')
            ->join('students', 'performance_reports.student_id', '=', 'students.student_id')
            ->join('users as su', 'students.user_id', '=', 'su.user_id')
            ->leftJoin('report_requests', 'performance_reports.report_request_id', '=', 'report_requests.id')
            ->leftJoin('teachers', 'report_requests.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users as tu', 'teachers.user_id', '=', 'tu.user_id')
            ->select(
                'performance_reports.*',
                'su.full_name as student_name',
                'tu.full_name as teacher_name'
            )
            ->orderByDesc('performance_reports.created_at')
            ->get();

        // للنموذج: قائمة الطلاب والمدربين
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'users.full_name')
            ->orderBy('users.full_name')
            ->get();

        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'users.full_name')
            ->orderBy('users.full_name')
            ->get();

        return view('affairs.reports', compact('reports', 'students', 'teachers'));
    }

    public function storeReport(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,student_id',
            'teacher_id'  => 'required|exists:teachers,teacher_id',
            'report_type' => 'required|in:academic,behavioral',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $requestId = DB::table('report_requests')->insertGetId([
            'head_id'     => null,
            'teacher_id'  => $request->teacher_id,
            'student_id'  => $request->student_id,
            'report_type' => $request->report_type,
            'notes'       => $request->notes,
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // إشعار المدرب (داخلي + FCM)
        $teacherUserId = DB::table('teachers')->where('teacher_id', $request->teacher_id)->value('user_id');
        $studentName   = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $request->student_id)
            ->value('users.full_name') ?? 'طالب';

        $typLabel = $request->report_type === 'behavioral' ? 'سلوكي' : 'أكاديمي';
        $title    = 'طلب تقرير جديد';
        $message  = 'طُلب منك تقرير ' . $typLabel . ' عن الطالب ' . $studentName;

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

        return redirect()->back()->with('success', 'تم إرسال طلب التقرير للمدرب وتم إشعاره بنجاح!');
    }
}
