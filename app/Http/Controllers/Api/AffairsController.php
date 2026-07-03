<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CalendarEvent;
use App\Models\AbsenceRequest;
use App\Models\Announcement;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AffairsController extends Controller
{
    // ── Dashboard Stats ───────────────────────────────────────────
    public function getDashboardStats(Request $request)
    {
        $totalStudents = User::where('role_id', 3)->count();
        $totalTeachers = User::where('role_id', 2)->count();
        $totalStaff    = User::whereIn('role_id', [2, 5, 6])->count();
        $pendingLeaves = AbsenceRequest::where('status', 'pending')->count();
        $totalUsers    = User::count();

        // 6 recent announcements
        $posts = Announcement::with('user')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'title'     => $p->title,
                'content'   => $p->content,
                'type'      => $p->type,
                'user_name' => $p->user?->full_name ?? 'المدير',
                'created_at'=> $p->created_at?->format('Y-m-d H:i'),
                'image_url' => $p->image ? url('storage/' . $p->image) : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'totalStudents' => $totalStudents,
                    'totalTeachers' => $totalTeachers,
                    'totalStaff'    => $totalStaff,
                    'pendingLeaves' => $pendingLeaves,
                    'totalUsers'    => $totalUsers,
                ],
                'posts' => $posts,
            ]
        ]);
    }

    // ── توليد رقم جامعي تلقائي ────────────────────────────────────
    public function nextUniversityId()
    {
        $base = 2026100;
        $last = DB::table('university_ids')
            ->whereRaw("CAST(university_id AS UNSIGNED) >= ? AND CAST(university_id AS UNSIGNED) <= 9999999", [$base])
            ->orderByDesc(DB::raw('CAST(university_id AS UNSIGNED)'))
            ->value('university_id');

        $nextId = $last ? ((int)$last + 1) : $base;
        return response()->json(['success' => true, 'university_id' => (string)$nextId]);
    }

    // ── الأرقام الجامعية ──────────────────────────────────────────
    public function listUniversityIds(Request $request)
    {
        $ids = DB::table('university_ids')->orderByDesc('created_at')->get()->map(function ($item) {
            $item->photo_url = $item->photo ? url('storage/' . $item->photo) : null;
            return $item;
        });
        return response()->json(['success' => true, 'data' => $ids]);
    }

    public function addUniversityId(Request $request)
    {
        $v = Validator::make($request->all(), [
            'university_id'    => 'required|string|unique:university_ids,university_id',
            'full_name'        => 'nullable|string|max:255',
            'first_name'       => 'nullable|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'date_of_birth'    => 'nullable|date',
            'phone'            => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:50',
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $fullName = $request->full_name ?? trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('student_photos', 'public');
        }

        $telegramChatId = $request->telegram_chat_id ? trim($request->telegram_chat_id) : null;

        DB::table('university_ids')->insert([
            'university_id'    => $request->university_id,
            'full_name'        => $fullName,
            'first_name'       => $request->first_name,
            'last_name'        => $request->last_name,
            'date_of_birth'    => $request->date_of_birth,
            'phone'            => $request->phone,
            'photo'            => $photoPath,
            'telegram_chat_id' => $telegramChatId,
            'role'             => 'student',
            'is_used'          => false,
            'created_by'       => $request->user()->user_id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // إرسال رسالة تليجرام
        if ($telegramChatId) {
            try {
                $telegram = new \App\Services\TelegramService();
                $telegram->sendCredentials(
                    (int) $telegramChatId,
                    $request->university_id,
                    $request->university_id,
                    $fullName,
                    '',
                    $request->date_of_birth ?? ''
                );
            } catch (\Exception $e) {
                \Log::error('Telegram sendCredentials error: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => 'تم إضافة الرقم الجامعي بنجاح']);
    }

    public function deleteUniversityId(Request $request, $id)
    {
        $uid = DB::table('university_ids')->find($id);
        if (!$uid) return response()->json(['success' => false, 'message' => 'غير موجود'], 404);
        if ($uid->is_used) return response()->json(['success' => false, 'message' => 'الرقم مستخدم، لا يمكن حذفه'], 422);

        DB::table('university_ids')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'تم الحذف']);
    }

    public function updateUniversityId(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'full_name'        => 'required|string|max:255',
            'university_id'    => 'required|string|unique:university_ids,university_id,' . $id,
            'date_of_birth'    => 'nullable|date',
            'phone'            => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:50',
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);

        $uid = DB::table('university_ids')->where('id', $id)->first();
        if (!$uid) return response()->json(['success' => false, 'message' => 'الرقم جامعى غير موجود.'], 404);

        $updates = [
            'full_name'        => $request->full_name,
            'university_id'    => $request->university_id,
            'date_of_birth'    => $request->date_of_birth,
            'phone'            => $request->phone,
            'telegram_chat_id' => $request->telegram_chat_id ? trim($request->telegram_chat_id) : null,
            'updated_at'       => now(),
        ];

        if ($request->hasFile('photo')) {
            if ($uid->photo) {
                Storage::disk('public')->delete($uid->photo);
            }
            $updates['photo'] = $request->file('photo')->store('student_photos', 'public');
        }

        DB::table('university_ids')->where('id', $id)->update($updates);

        return response()->json(['success' => true, 'message' => 'تم تحديث البيانات بنجاح.']);
    }

    // ── طلبات الحسابات المعلّقة ───────────────────────────────────
    public function pendingAccounts()
    {
        $users = User::whereIn('role_id', [3, 4]) // student + parent
            ->where('status', 'inactive')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($u) => [
                'user_id'       => $u->user_id,
                'full_name'     => $u->full_name,
                'email'         => $u->email,
                'role'          => $u->role,
                'university_id' => $u->university_id,
                'created_at'    => $u->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function approveAccount(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);

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
        $notifMsg   = 'مرحباً ' . $user->full_name . '! تم مراجعة طلبك وتفعيل حسابك. يمكنك الآن تسجيل الدخول.';

        // إشعار داخل DB
        DB::table('notifications')->insert([
            'user_id'    => $user->user_id,
            'sender_id'  => $request->user()->user_id,
            'title'      => $notifTitle,
            'message'    => $notifMsg,
            'type'       => 'administrative',
            'category'   => 'administrative',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // FCM push
        \App\Services\FcmService::sendToUser($user->user_id, $notifTitle, $notifMsg, [
            'type'   => 'account_approved',
            'screen' => 'login',
        ]);

        // Telegram Notification
        if ($user->telegram_chat_id) {
            try {
                $telegram = new \App\Services\TelegramService();
                $text = "🎓 <b>تفعيل الحساب - Edu Bridge</b>\n\n"
                      . "مرحباً <b>{$user->full_name}</b>،\n\n"
                      . "🎉 لقد تم <b>الموافقة وتفعيل حسابك بنجاح</b> من قِبل إدارة شؤون الطلاب!\n\n"
                      . "📲 يمكنك الآن فتح التطبيق وتسجيل الدخول مباشرة.";
                $telegram->sendMessage((int) $user->telegram_chat_id, $text);
            } catch (\Exception $e) {
                \Log::error('Telegram approveAccount API notification error: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => 'تم تفعيل الحساب']);
    }

    public function rejectAccount(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);

        // إرسال إشعار تليجرام للرفض قبل الحذف
        if ($user->telegram_chat_id) {
            try {
                $telegram = new \App\Services\TelegramService();
                $text = "🎓 <b>طلب التسجيل - Edu Bridge</b>\n\n"
                      . "مرحباً <b>{$user->full_name}</b>،\n\n"
                      . "⚠️ نأسف لإعلامك بأنه تم <b>رفض طلب إنشاء وتفعيل حسابك</b> من قِبل إدارة شؤون الطلاب.\n\n"
                      . "يرجى مراجعة شؤون الطلاب لمزيد من التفاصيل.";
                $telegram->sendMessage((int) $user->telegram_chat_id, $text);
            } catch (\Exception $e) {
                \Log::error('Telegram rejectAccount API notification error: ' . $e->getMessage());
            }
        }

        // إلغاء استخدام الرقم الجامعي
        if ($user->university_id) {
            DB::table('university_ids')
                ->where('university_id', $user->university_id)
                ->update(['is_used' => false]);
        }

        // حذف الطالب/الولي والحساب
        DB::table('students')->where('user_id', $userId)->delete();
        DB::table('parents')->where('user_id', $userId)->delete();
        $user->delete();

        return response()->json(['success' => true, 'message' => 'تم رفض وحذف الطلب']);
    }

    // ── إعادة تسجيل جهاز الطالب ──────────────────────────────────────
    public function resetDevice(Request $request, int $studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'الطالب غير موجود'], 404);
        }

        $student->update([
            'device_id'        => null,
            'is_device_locked' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تسجيل الجهاز بنجاح، يمكن للطالب الآن تسجيل الدخول من جهاز جديد.',
        ]);
    }

    // ── Accounts Management ───────────────────────────────────────
    public function listAccounts(Request $request)
    {
        $users = User::whereIn('role_id', [2, 3, 4, 5, 6])
            ->with(['student'])
            ->latest()
            ->get()
            ->map(fn($u) => [
                'user_id' => $u->user_id,
                'full_name' => $u->full_name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'status' => $u->status,
                'department' => $u->department,
                'student_id' => $u->student?->student_id,
                'device_id' => $u->student?->device_id,
                'is_device_locked' => $u->student?->is_device_locked ?? 0,
            ]);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function getMetadata(Request $request)
    {
        $departments = DB::table('departments')->orderBy('name')->get();
        $courses = DB::table('courses')->orderBy('title')->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'departments' => $departments,
                'courses' => $courses,
            ]
        ]);
    }

    public function createAccount(Request $request)
    {
        $v = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'role_id'   => 'required|integer|in:2,5', // معلم أو رئيس قسم فقط
            'password'  => 'required|min:6',
            'phone'     => 'nullable|string|max:20',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        if ($request->role_id == 5) {
            $v2 = Validator::make($request->all(), [
                'department_id' => 'required|exists:departments,department_id'
            ]);
            if ($v2->fails()) return response()->json(['success' => false, 'message' => $v2->errors()->first()], 422);
        } elseif ($request->role_id == 2) {
            $v3 = Validator::make($request->all(), [
                'department_id'  => 'required|exists:departments,department_id',
                'specialization' => 'required|string|max:255',
                'courses'        => 'nullable|array'
            ]);
            if ($v3->fails()) return response()->json(['success' => false, 'message' => $v3->errors()->first()], 422);
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

        return response()->json(['success' => true, 'message' => 'تم إنشاء الحساب بنجاح.']);
    }

    public function updateAccount(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود.'], 404);
        }

        $v = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'required|email|max:255|unique:users,email,' . $id . ',user_id',
            'password'  => 'nullable|string|min:6|confirmed',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $updates = [
            'full_name'  => $request->full_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'updated_at' => now(),
        ];

        if ($request->filled('password')) {
            $updates['password'] = Hash::make($request->password);
        }

        $user->update($updates);

        return response()->json(['success' => true, 'message' => 'تم تحديث بيانات الحساب بنجاح!']);
    }

    public function toggleAccountStatus($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'المستخدم غير موجود.'], 404);

        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الحساب.', 'status' => $user->status]);
    }

    public function deleteAccount($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'المستخدم غير موجود.'], 404);

        $user->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الحساب بنجاح.']);
    }

    // ── Leaves / Vacations ─────────────────────────────────────────
    public function listLeaves()
    {
        $leaves = AbsenceRequest::with('student.user')
            ->latest()
            ->get()
            ->map(fn($l) => [
                'id' => $l->request_id,
                'student_id' => $l->student_id,
                'student_name' => $l->student?->user?->full_name ?? 'غير معروف',
                'reason' => $l->reason,
                'date' => $l->date ? $l->date->format('Y-m-d') : null,
                'status' => $l->status,
                'created_at' => $l->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['success' => true, 'data' => $leaves]);
    }

    public function updateLeaveStatus(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected'
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);

        $leave = AbsenceRequest::find($id);
        if (!$leave) return response()->json(['success' => false, 'message' => 'الطلب غير موجود.'], 404);

        $leave->status      = $request->status;
        $leave->reviewed_by = $request->user()->user_id;
        $leave->save();

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة طلب الإجازة.']);
    }

    // ── Calendar Events ────────────────────────────────────────────
    public function listCalendarEvents(Request $request)
    {
        $events = CalendarEvent::where('user_id', $request->user()->user_id)
            ->orderBy('event_date', 'asc')
            ->get();
        return response()->json(['success' => true, 'data' => $events]);
    }

    public function storeCalendarEvent(Request $request)
    {
        $v = Validator::make($request->all(), [
            'event_date' => 'required|date',
            'title'      => 'required|string|max:255',
            'event_time' => 'nullable',
            'location'   => 'nullable|string|max:255',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);

        $event = CalendarEvent::create([
            'user_id'    => $request->user()->user_id,
            'event_date' => $request->event_date,
            'title'      => $request->title,
            'event_time' => $request->event_time,
            'location'   => $request->location,
        ]);

        return response()->json(['success' => true, 'message' => 'تم إضافة الحدث بنجاح.', 'data' => $event]);
    }

    public function updateCalendarEvent(Request $request, $id)
    {
        $event = CalendarEvent::where('id', $id)->where('user_id', $request->user()->user_id)->first();
        if (!$event) return response()->json(['success' => false, 'message' => 'الحدث غير موجود.'], 404);

        $v = Validator::make($request->all(), [
            'event_date' => 'required|date',
            'title'      => 'required|string|max:255',
            'event_time' => 'nullable',
            'location'   => 'nullable|string|max:255',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);

        $event->update([
            'event_date' => $request->event_date,
            'title'      => $request->title,
            'event_time' => $request->event_time,
            'location'   => $request->location,
        ]);

        return response()->json(['success' => true, 'message' => 'تم تحديث الحدث بنجاح.', 'data' => $event]);
    }

    public function deleteCalendarEvent(Request $request, $id)
    {
        $event = CalendarEvent::where('id', $id)->where('user_id', $request->user()->user_id)->first();
        if (!$event) return response()->json(['success' => false, 'message' => 'الحدث غير موجود.'], 404);

        $event->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الحدث بنجاح.']);
    }

    // ── Messages ───────────────────────────────────────────────────
    public function listMessages(Request $request)
    {
        $currentUserId = $request->user()->user_id;

        $conversations = Message::with(['sender', 'receiver'])
            ->where('sender_id', $currentUserId)
            ->orWhere('receiver_id', $currentUserId)
            ->latest()
            ->get()
            ->map(function ($msg) use ($currentUserId) {
                return ($msg->sender_id == $currentUserId) ? $msg->receiver_id : $msg->sender_id;
            })
            ->unique()
            ->values();

        $contacts = User::whereIn('user_id', $conversations)->get();
        $allUsers = User::where('user_id', '!=', $currentUserId)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'contacts' => $contacts,
                'allUsers' => $allUsers,
            ]
        ]);
    }

    public function getConversation(Request $request, $userId)
    {
        $currentUserId = $request->user()->user_id;
        $messages = Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)->where('receiver_id', $userId);
            })
            ->orWhere(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function sendMessage(Request $request)
    {
        $v = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,user_id',
            'message'     => 'required|string|max:2000',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);

        $message = Message::create([
            'sender_id'   => $request->user()->user_id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'is_read'     => false,
        ]);

        Notification::create([
            'user_id' => $request->receiver_id,
            'title'   => 'رسالة جديدة',
            'message' => 'لقد تلقيت رسالة جديدة من ' . $request->user()->full_name,
            'type'    => 'message',
            'is_read' => false,
        ]);

        return response()->json(['success' => true, 'data' => $message->load('sender')]);
    }

    // ── Notifications ──────────────────────────────────────────────
    public function listNotifications(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->user_id)
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function markNotificationRead(Request $request, $id)
    {
        Notification::where('id', $id)
            ->where('user_id', $request->user()->user_id)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'تم تحديد الإشعار كمقروء.']);
    }

    public function markAllNotificationsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->user_id)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'تم تحديد جميع الإشعارات كمقروءة.']);
    }

    // ── Profile ────────────────────────────────────────────────────
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $reviewedLeaves = AbsenceRequest::where('reviewed_by', $user->user_id)->count();
        $sentMessages   = Message::where('sender_id', $user->user_id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'reviewedLeaves' => $reviewedLeaves,
                'sentMessages' => $sentMessages,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $v = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);

        $user->update([
            'full_name' => $request->full_name,
            'phone'     => $request->phone,
            'email'     => $request->email,
        ]);

        return response()->json(['success' => true, 'message' => 'تم تحديث الملف الشخصي بنجاح.']);
    }

    public function updatePassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return response()->json(['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح.']);
    }

    // ── طلبات تغيير الصورة ────────────────────────────────────────
    public function listPhotoChangeRequests()
    {
        $requests = DB::table('photo_change_requests')
            ->join('users', 'photo_change_requests.user_id', '=', 'users.user_id')
            ->where('photo_change_requests.status', 'pending')
            ->select(
                'photo_change_requests.id',
                'photo_change_requests.user_id',
                'photo_change_requests.old_photo',
                'photo_change_requests.new_photo',
                'photo_change_requests.status',
                'photo_change_requests.created_at',
                'users.full_name',
                'users.user_id as student_code'
            )
            ->orderByDesc('photo_change_requests.created_at')
            ->get()
            ->map(function ($r) {
                $r->old_photo_url = $r->old_photo ? url('storage/' . $r->old_photo) : null;
                $r->new_photo_url = $r->new_photo ? url('storage/' . $r->new_photo) : null;
                return $r;
            });

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function approvePhotoChange($id)
    {
        $req = DB::table('photo_change_requests')->where('id', $id)->where('status', 'pending')->first();
        if (!$req) return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);

        // حذف الصورة القديمة وتحديث الـ avatar
        if ($req->old_photo) Storage::disk('public')->delete($req->old_photo);
        DB::table('users')->where('user_id', $req->user_id)->update(['avatar' => $req->new_photo]);
        DB::table('photo_change_requests')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'تمت الموافقة على تغيير الصورة']);
    }

    public function rejectPhotoChange($id)
    {
        $req = DB::table('photo_change_requests')->where('id', $id)->where('status', 'pending')->first();
        if (!$req) return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);

        Storage::disk('public')->delete($req->new_photo);
        DB::table('photo_change_requests')->where('id', $id)->update(['status' => 'rejected', 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'تم رفض طلب تغيير الصورة']);
    }
}
