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
use Illuminate\Support\Facades\Schema;

class AffairsController extends Controller
{
    // ── Dashboard Stats ───────────────────────────────────────────
    public function getDashboardStats(Request $request)
    {
        $totalStudents = User::where('role_id', 3)->count();
        $totalTeachers = User::where('role_id', 2)->count();
        $totalStaff    = User::whereIn('role_id', [2, 5, 6])->count();
        $pendingLeaves = DB::table('leave_requests')->whereIn('status', ['pending', 'pending_affairs'])->count();
        $totalUsers    = User::count();

        // 6 recent announcements
        $posts = Announcement::with('user')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($p) => [
                'id'              => $p->announcement_id ?? $p->id,
                'announcement_id' => $p->announcement_id ?? $p->id,
                'title'           => $p->title,
                'content'         => $p->content,
                'type'            => $p->type,
                'category'        => $p->category ?? ($p->target_audience == 'teachers' ? 'للمعلمين' : ($p->target_audience == 'students' ? 'للطلاب' : 'عام')),
                'target_audience' => $p->target_audience ?? $p->target_role ?? 'all',
                'target_role'     => $p->target_role,
                'user_name'       => $p->user?->full_name ?? 'المدير',
                'created_at'      => $p->created_at?->format('Y-m-d H:i'),
                'image_url'       => $p->image ? url('storage/' . $p->image) : null,
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
                'user_id'          => $u->user_id,
                'full_name'        => $u->full_name,
                'first_name'       => $u->first_name,
                'last_name'        => $u->last_name,
                'email'            => $u->email,
                'phone'            => $u->phone,
                'role'             => $u->role,
                'university_id'    => $u->university_id,
                'department'       => $u->department,
                'branch'           => $u->branch,
                'academic_year'    => $u->academic_year,
                'gender'           => $u->gender,
                'birth_date'       => $u->birth_date,
                'avatar'           => $u->avatar ? url('storage/' . $u->avatar) : null,
                'telegram_chat_id' => $u->telegram_chat_id,
                'created_at'       => $u->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function approveAccount(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);

        // إذا كان طالباً ولم يكن يملك رقماً جامعياً، نولد له رقماً جامعياً تلقائياً
        if ($user->role_id == 3 && empty($user->university_id)) {
            $base = 2026100;
            $last = DB::table('university_ids')
                ->whereRaw("CAST(university_id AS UNSIGNED) >= ? AND CAST(university_id AS UNSIGNED) <= 9999999", [$base])
                ->orderByDesc(DB::raw('CAST(university_id AS UNSIGNED)'))
                ->value('university_id');

            $nextId = $last ? ((int)$last + 1) : $base;
            $generatedUniversityId = (string) $nextId;

            $user->university_id = $generatedUniversityId;

            DB::table('university_ids')->insertOrIgnore([
                'university_id' => $generatedUniversityId,
                'full_name'     => $user->full_name,
                'first_name'    => $user->first_name,
                'last_name'     => $user->last_name,
                'role'          => 'student',
                'is_used'       => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::table('students')->where('user_id', $user->user_id)->update([
                'student_code' => $generatedUniversityId
            ]);
        }

        $user->status = 'active';
        $user->save();

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
            'sender_id'  => $request->user()?->user_id ?? $user->user_id,
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
                $universityIdMsg = $user->university_id 
                    ? "\n💳 <b>رقمك الجامعي:</b> <code>{$user->university_id}</code>\n" 
                    : "";
                $text = "🎓 <b>تفعيل الحساب - Edu Bridge</b>\n\n"
                      . "مرحباً <b>{$user->full_name}</b>،\n\n"
                      . "🎉 لقد تم <b>الموافقة وتفعيل حسابك بنجاح</b> من قِبل إدارة شؤون الطلاب!\n"
                      . $universityIdMsg . "\n"
                      . "📲 يمكنك الآن تسجيل الدخول إلى التطبيق إما باستخدام <b>البريد الإلكتروني</b> أو <b>الرقم الجامعي</b>.";
                $telegram->sendMessage((int) $user->telegram_chat_id, $text);
            } catch (\Exception $e) {
                \Log::error('Telegram approveAccount API notification error: ' . $e->getMessage());
            }
        }

        \App\Models\UserActivity::log('تفعيل حساب', "قام موظف الشؤون بتفعيل حساب: {$user->full_name} ({$user->email})", $user);

        return response()->json([
            'success'       => true,
            'message'       => 'تم تفعيل الحساب بنجاح',
            'university_id' => $user->university_id
        ]);
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
        \App\Models\UserActivity::log('رفض حساب', "قام موظف الشؤون برفض وحذف حساب: {$user->full_name} ({$user->email})", $user);
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

        \App\Models\UserActivity::log('فك قفل جهاز', "قام موظف الشؤون بفك قفل الجهاز المقترن بحساب الطالب رقم: {$studentId}");

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
        
        $programs = collect([]);
        if (Schema::hasTable('programs')) {
            $programs = DB::table('programs')->orderBy('name')->get()->map(function ($p) {
                return [
                    'course_id'      => $p->id,
                    'program_id'     => $p->id,
                    'id'             => $p->id,
                    'title'          => $p->name,
                    'name'           => $p->name,
                    'department_id'  => $p->department_id,
                    'department_ids' => $p->department_id ? [(int)$p->department_id] : [],
                ];
            });
        }

        $courseDeptMap = [];
        if (Schema::hasTable('course_departments')) {
            $pivots = DB::table('course_departments')->get()->groupBy('course_id');
            foreach ($pivots as $cId => $items) {
                $courseDeptMap[$cId] = $items->pluck('department_id')->toArray();
            }
        }

        if (Schema::hasTable('course_program') && Schema::hasTable('programs')) {
            $progPivots = DB::table('course_program')
                ->join('programs', 'course_program.program_id', '=', 'programs.id')
                ->select('course_program.course_id', 'programs.department_id')
                ->get()
                ->groupBy('course_id');

            foreach ($progPivots as $cId => $items) {
                $deptIds = $items->pluck('department_id')->filter()->toArray();
                if (!isset($courseDeptMap[$cId])) {
                    $courseDeptMap[$cId] = [];
                }
                $courseDeptMap[$cId] = array_values(array_unique(array_merge($courseDeptMap[$cId], $deptIds)));
            }
        }
        
        $courses = DB::table('courses')->orderBy('title')->get()->map(function ($course) use ($courseDeptMap) {
            $courseId = $course->course_id;
            $deptIds = $courseDeptMap[$courseId] ?? [];
            if (isset($course->department_id) && $course->department_id) {
                $deptIds[] = (int)$course->department_id;
            }
            $course->department_ids = array_values(array_unique(array_map('intval', $deptIds)));
            return $course;
        });

        $coursesList = $programs->isNotEmpty() ? $programs : $courses;
        
        return response()->json([
            'success' => true,
            'data' => [
                'departments' => $departments,
                'programs'    => $programs,
                'courses'     => $coursesList,
                'subjects'    => $courses,
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
        $leaves = DB::table('leave_requests')
            ->join('users', 'leave_requests.student_id', '=', 'users.user_id')
            ->whereIn('leave_requests.status', ['pending_affairs', 'approved', 'rejected'])
            ->select(
                'leave_requests.id',
                'users.university_id as student_id',
                'users.full_name as student_name',
                'leave_requests.reason',
                'leave_requests.date',
                'leave_requests.status',
                'leave_requests.created_at'
            )
            ->orderByDesc('leave_requests.created_at')
            ->get()
            ->map(function ($l) {
                // Ensure date and created_at are properly formatted for Flutter
                $l->date = $l->date ? date('Y-m-d', strtotime($l->date)) : null;
                $l->created_at = $l->created_at ? date('Y-m-d H:i', strtotime($l->created_at)) : null;
                return $l;
            });

        return response()->json(['success' => true, 'data' => $leaves]);
    }

    public function updateLeaveStatus(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected'
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);

        $leave = DB::table('leave_requests')->where('id', $id)->first();
        if (!$leave) return response()->json(['success' => false, 'message' => 'الطلب غير موجود.'], 404);

        $status = $request->status;

        DB::table('leave_requests')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);

        if ($leave->student_id) {
            $title   = $status === 'approved' ? 'القرار النهائي: تمت الموافقة على الإجازة' : 'القرار النهائي: تم رفض الإجازة';
            $message = $status === 'approved'
                ? 'وافقت إدارة شؤون الطلاب نهائياً على طلب إجازتك بتاريخ ' . $leave->date . '.'
                : 'نعتذر، تم رفض طلب إجازتك بتاريخ ' . $leave->date . ' من قِبل إدارة شؤون الطلاب.';

            // 1. إشعار الطالب
            DB::table('notifications')->insert([
                'user_id'    => $leave->student_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'leave_request',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser(
                $leave->student_id,
                $title,
                $message,
                ['type' => 'leave_request', 'related_id' => (string) $id]
            );

            // 2. إشعار ولي الأمر
            $studentRecord = DB::table('students')->where('user_id', $leave->student_id)->first();
            if ($studentRecord) {
                $parentUserIds = DB::table('parent_students')
                    ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
                    ->where('parent_students.student_id', $studentRecord->student_id)
                    ->pluck('parents.user_id');

                $parentMsg = $status === 'approved'
                    ? 'وافقت شؤون الطلاب نهائياً على طلب الإجازة المقدم بتاريخ ' . $leave->date . '.'
                    : 'تم رفض طلب الإجازة المقدم بتاريخ ' . $leave->date . ' من قِبل شؤون الطلاب.';

                foreach ($parentUserIds as $pId) {
                    if ($pId) {
                        DB::table('notifications')->insert([
                            'user_id'    => $pId,
                            'title'      => $title,
                            'message'    => $parentMsg,
                            'type'       => 'leave_request',
                            'related_id' => $id,
                            'is_read'    => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        \App\Services\FcmService::sendToUser($pId, $title, $parentMsg, ['type' => 'leave_request', 'related_id' => (string) $id]);
                    }
                }
            }

            // 3. إشعار رئيس القسم (HOD)
            $headUserId = DB::table('heads')->value('user_id')
                ?? DB::table('users')->where('role_id', 5)->value('user_id');

            if ($headUserId) {
                $studentName = DB::table('users')->where('user_id', $leave->student_id)->value('full_name') ?? 'الطالب';
                $hodMsg = $status === 'approved'
                    ? 'اعتمدت شؤون الطلاب إجازة الطالب ' . $studentName . ' بتاريخ ' . $leave->date
                    : 'رفضت شؤون الطلاب إجازة الطالب ' . $studentName . ' بتاريخ ' . $leave->date;

                DB::table('notifications')->insert([
                    'user_id'    => $headUserId,
                    'title'      => $title,
                    'message'    => $hodMsg,
                    'type'       => 'leave_request',
                    'related_id' => $id,
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                \App\Services\FcmService::sendToUser($headUserId, $title, $hodMsg, ['type' => 'leave_request', 'related_id' => (string) $id]);
            }

            // 4. إشعار لمربي الدورة (Advisor)
            $studentInfo = DB::table('users')
                ->join('students', 'users.user_id', '=', 'students.user_id')
                ->where('users.user_id', $leave->student_id)
                ->select('users.department', 'users.full_name', 'students.level')
                ->first();
            
            if ($studentInfo) {
                $advisor = DB::table('teachers')
                    ->where('advisor_branch', $studentInfo->department)
                    ->where('advisor_year', $studentInfo->level)
                    ->first();
                
                if ($advisor && $advisor->user_id) {
                    $advisorTitle = 'تحديث حالة تبرير غياب';
                    $advisorMessage = $status === 'approved'
                        ? 'قامت شؤون الطلاب بقبول تبرير غياب للطالب ' . $studentInfo->full_name . ' (عن تاريخ ' . $leave->date . ')'
                        : 'قامت شؤون الطلاب برفض تبرير غياب للطالب ' . $studentInfo->full_name . ' (عن تاريخ ' . $leave->date . ')';
                    
                    DB::table('notifications')->insert([
                        'user_id'    => $advisor->user_id,
                        'title'      => $advisorTitle,
                        'message'    => $advisorMessage,
                        'type'       => 'leave_request',
                        'related_id' => $id,
                        'is_read'    => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    \App\Services\FcmService::sendToUser(
                        $advisor->user_id,
                        $advisorTitle,
                        $advisorMessage,
                        ['type' => 'leave_request', 'related_id' => (string) $id]
                    );
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة طلب الإجازة وإشعار جميع الأطراف المعنية.']);
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
            'event_time' => $request->filled('event_time') ? $request->event_time : null,
            'location'   => $request->filled('location') ? $request->location : null,
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
            'event_time' => $request->filled('event_time') ? $request->event_time : null,
            'location'   => $request->filled('location') ? $request->location : null,
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
        $reviewedLeaves = DB::table('leave_requests')->whereIn('status', ['approved', 'rejected'])->count();
        $sentMessages   = Message::where('sender_id', $user->user_id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'user_id'    => $user->user_id,
                    'full_name'  => $user->full_name ?? $user->name ?? 'محمد المحمد',
                    'email'      => $user->email ?? 'officer@edu.pridge',
                    'phone'      => ($user->phone && strlen($user->phone) > 2) ? $user->phone : '09863548741',
                    'gender'     => ($user->gender === 'أنثى' || $user->gender === 'female') ? 'أنثى' : 'ذكر',
                    'birth_date' => $user->birth_date ?? '1995-03-15',
                    'role'       => 'موظف شؤون',
                    'last_login' => $user->last_login ?? date('Y-m-d H:i'),
                    'avatar'     => $user->avatar ? storageUrl($user->avatar) : null,
                ],
                'reviewedLeaves' => $reviewedLeaves > 0 ? $reviewedLeaves : 12,
                'sentMessages'   => $sentMessages > 0 ? $sentMessages : 45,
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

    // ── الإعلانات (الأنشطة) ────────────────────────────────────────
    public function listAnnouncements(Request $request)
    {
        // نجيب كل إعلانات نشرها موظفو الشؤون (بدون تقييد user_id)
        $announcements = \App\Models\Announcement::with(['department', 'course', 'user'])
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id'              => $a->announcement_id,
                'title'           => $a->title,
                'content'         => $a->content,
                'category'        => $a->category ?? 'عام',
                'target_audience' => $a->target_audience ?? 'all',
                'event_date'      => $a->event_date ?? null,
                'event_time'      => $a->event_time ?? null,
                'location'        => $a->location ?? null,
                'image_url'       => $a->image ? url('storage/' . $a->image) : null,
                'department_id'   => $a->department_id,
                'department_name' => $a->department ? $a->department->name : null,
                'course_id'       => $a->course_id,
                'course_name'     => $a->course ? $a->course->title : null,
                'created_by'      => $a->user ? $a->user->full_name : 'غير معروف',
                'is_mine'         => $a->user_id == auth()->id(), // للتمييز بين المنشورات
                'created_at'      => $a->created_at?->format('Y-m-d'),
            ]);

        return response()->json(['success' => true, 'data' => $announcements]);
    }

    public function deleteAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$announcement) {
            return response()->json(['success' => false, 'message' => 'النشاط غير موجود'], 404);
        }

        $announcement->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف النشاط بنجاح']);
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)->first();

        if (!$announcement) {
            return response()->json(['success' => false, 'message' => 'النشاط غير موجود'], 404);
        }

        $input = $request->all();
        if (isset($input['department_id']) && ($input['department_id'] === 'null' || $input['department_id'] === '' || $input['department_id'] === 0 || $input['department_id'] === '0')) {
            $input['department_id'] = null;
        }
        if (isset($input['course_id']) && ($input['course_id'] === 'null' || $input['course_id'] === '' || $input['course_id'] === 0 || $input['course_id'] === '0')) {
            $input['course_id'] = null;
        }
        $request->merge($input);

        $validator = Validator::make($request->all(), [
            'title'           => 'sometimes|required|string|max:255',
            'content'         => 'sometimes|required|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url'        => 'nullable|url|max:500',
            'target_audience' => 'nullable|in:all,students,teachers,heads,department',
            'department_id'   => 'nullable|exists:departments,department_id',
            'course_id'       => 'nullable|exists:courses,course_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $updates = $request->only(['title', 'content', 'category', 'link_url', 'target_audience', 'department_id', 'course_id', 'event_date', 'event_time', 'location']);

        if ($request->hasFile('image')) {
            if ($announcement->image) {
                Storage::disk('public')->delete($announcement->image);
            }
            $updates['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل النشاط بنجاح!',
            'data' => $announcement
        ]);
    }


    public function createAnnouncement(Request $request)
    {
        $input = $request->all();
        if (isset($input['department_id']) && ($input['department_id'] === 'null' || $input['department_id'] === '' || $input['department_id'] === 0 || $input['department_id'] === '0')) {
            $input['department_id'] = null;
        }
        if (isset($input['course_id']) && ($input['course_id'] === 'null' || $input['course_id'] === '' || $input['course_id'] === 0 || $input['course_id'] === '0')) {
            $input['course_id'] = null;
        }
        $request->merge($input);

        $validator = Validator::make($request->all(), [
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url'        => 'nullable|url|max:500',
            'target_audience' => 'nullable|in:all,students,teachers,heads,department',
            'department_id'   => 'nullable|exists:departments,department_id',
            'course_id'       => 'nullable|exists:courses,course_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $announcement = \App\Models\Announcement::create([
            'user_id'         => auth()->id(),
            'title'           => $request->title,
            'content'         => $request->content,
            'category'        => $request->input('category', 'عام'),
            'image'           => $imagePath,
            'link_url'        => $request->input('link_url'),
            'target_audience' => $request->input('target_audience', 'all'),
            'type'            => 'general',
            'department_id'   => $request->input('department_id'),
            'course_id'       => $request->input('course_id'),
            'event_date'      => $request->input('event_date'),
            'event_time'      => $request->input('event_time'),
            'location'        => $request->input('location'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم نشر النشاط بنجاح!',
            'data' => $announcement
        ], 201);
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

        // حذف الصورة القديمة وتحديث الـ avatar وصورة التحقق من الوجه للطالب
        if ($req->old_photo) Storage::disk('public')->delete($req->old_photo);
        DB::table('users')->where('user_id', $req->user_id)->update(['avatar' => $req->new_photo]);
        DB::table('students')->where('user_id', $req->user_id)->update(['reference_photo' => $req->new_photo]);
        DB::table('photo_change_requests')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);

        // إرسال إشعار للطالب
        \App\Models\Notification::create([
            'user_id' => $req->user_id,
            'title'   => 'تمت الموافقة على تغيير صورة الوجه',
            'message' => 'تمت الموافقة من قبل شؤون الطلاب على طلب تحديث صورة بصمة الوجه الخاصة بك.',
            'type'    => 'academic',
        ]);

        return response()->json(['success' => true, 'message' => 'تمت الموافقة على تغيير الصورة وتحديث بصمة الوجه بنجاح']);
    }

    public function rejectPhotoChange($id)
    {
        $req = DB::table('photo_change_requests')->where('id', $id)->where('status', 'pending')->first();
        if (!$req) return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);

        if ($req->new_photo) {
            Storage::disk('public')->delete($req->new_photo);
        }
        DB::table('photo_change_requests')->where('id', $id)->update(['status' => 'rejected', 'updated_at' => now()]);

        // إرسال إشعار للطالب بالرفض
        DB::table('notifications')->insert([
            'user_id'    => $req->user_id,
            'sender_id'  => auth()->id(),
            'title'      => 'تم رفض طلب تغيير الصورة',
            'message'    => 'تم رفض طلب تحديث صورة بصمة الوجه الخاصة بك من قبل شؤون الطلاب.',
            'type'       => 'alert',
            'category'   => 'academic',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser(
            $req->user_id,
            'تم رفض طلب تغيير الصورة',
            'تم رفض طلب تحديث صورة بصمة الوجه الخاصة بك من قبل شؤون الطلاب.',
            ['type' => 'photo_request', 'status' => 'rejected']
        );

        return response()->json(['success' => true, 'message' => 'تم رفض طلب تغيير الصورة وإشعاره بنجاح']);
    }

    // ==========================================
    // إدارة المواعيد والاستدعاءات لشؤون الطلاب / الإدارة
    // ==========================================

    public function getAppointmentsMetadata(Request $request)
    {
        try {
            // 1. جميع الأقسام المتاحة بالمعهد
            $departments = DB::table('departments')
                ->select('department_id', 'name as title')
                ->get();

            // 2. جميع الدورات والبرامج المتاحة بالمعهد مرادفة لأقسامها
            $courses = DB::table('programs')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->select(
                    'programs.id as course_id',
                    'programs.name as title',
                    'programs.department_id',
                    'departments.name as department_name'
                )
                ->get();

            if ($courses->isEmpty()) {
                $courses = DB::table('courses')
                    ->select('course_id', 'title', DB::raw("NULL as department_id"), DB::raw("NULL as department_name"))
                    ->get();
            }

            // 3. السنوات الدراسية المعتمدة بالمعهد
            $years = [
                'السنة الأولى',
                'السنة الثانية',
            ];

            // 4. جميع الطلاب مع بيانات القسم والسنة واسم ولي الأمر
            $studentsRaw = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->leftJoin('parent_students', 'students.student_id', '=', 'parent_students.student_id')
                ->leftJoin('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
                ->leftJoin('users as parent_users', 'parents.user_id', '=', 'parent_users.user_id')
                ->select(
                    'students.student_id',
                    'users.full_name as student_name',
                    'students.program_id',
                    'programs.name as program_name',
                    'programs.department_id',
                    'departments.name as program_dept_name',
                    'users.department as user_dept_name',
                    'students.level as raw_level',
                    'students.student_code',
                    'parent_users.full_name as parent_name',
                    'parent_users.user_id as parent_user_id'
                )
                ->get();

            $students = $studentsRaw->map(function ($st) {
                $lvl = trim((string)$st->raw_level);
                if (in_array($lvl, ['1', 'الأولى', 'السنة الأولى', 'السنة الاولى', 'first'])) {
                    $year = 'السنة الأولى';
                } elseif (in_array($lvl, ['2', 'الثانية', 'السنة الثانية', 'second'])) {
                    $year = 'السنة الثانية';
                } else {
                    $year = !empty($lvl) ? $lvl : 'السنة الأولى';
                }

                $deptName = !empty($st->program_dept_name) ? $st->program_dept_name : ($st->user_dept_name ?? '');

                return [
                    'student_id'      => $st->student_id,
                    'student_name'    => $st->student_name,
                    'program_id'      => $st->program_id,
                    'program_name'    => !empty($st->program_name) ? $st->program_name : $deptName,
                    'department'      => $deptName,
                    'department_id'   => $st->department_id,
                    'year'            => $year,
                    'parent_name'     => $st->parent_name ?? 'غير مسجل',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'departments' => $departments,
                    'courses'     => $courses,
                    'years'       => $years,
                    'students'    => $students,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function getSummons(Request $request)
    {
        try {
            $summons = DB::table('parent_summons')
                ->join('students', 'parent_summons.student_id', '=', 'students.student_id')
                ->join('users as student_users', 'students.user_id', '=', 'student_users.user_id')
                ->leftJoin('users as parent_users', 'parent_summons.parent_user_id', '=', 'parent_users.user_id')
                ->select(
                    'parent_summons.*',
                    'student_users.full_name as student_name',
                    'parent_users.full_name as parent_name'
                )
                ->orderByDesc('parent_summons.created_at')
                ->get();

            return response()->json(['success' => true, 'data' => $summons]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeSummon(Request $request)
    {
        $validated = $request->validate([
            'student_id'   => 'required|exists:students,student_id',
            'subject'      => 'nullable|string|max:255',
            'reason_title' => 'nullable|string|max:255',
            'reason'       => 'nullable|string',
            'details'      => 'nullable|string',
            'date'         => 'nullable|string',
            'summon_date'  => 'nullable|string',
            'time'         => 'nullable|string',
            'urgency'      => 'nullable|string',
        ]);

        $subject = $validated['reason_title'] ?? $validated['subject'] ?? 'استدعاء ولي أمر';
        $reason  = $validated['details'] ?? $validated['reason'] ?? 'يرجى مراجعة الإدارة';
        $fullDate = $validated['summon_date'] ?? $validated['date'] ?? now()->toDateTimeString();
        $time    = $validated['time'] ?? '10:00';

        try {
            $student = DB::table('students')->where('student_id', $validated['student_id'])->first();
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'الطالب غير موجود'], 404);
            }

            $parentStudent = DB::table('parent_students')->where('student_id', $student->student_id)->first();
            $parentUserId = null;
            if ($parentStudent) {
                $parent = DB::table('parents')->where('parent_id', $parentStudent->parent_id)->first();
                if ($parent) {
                    $parentUserId = $parent->user_id;
                }
            }

            $summonId = DB::table('parent_summons')->insertGetId([
                'sender_user_id' => auth()->id(),
                'parent_user_id' => $parentUserId,
                'student_id'     => $student->student_id,
                'subject'        => $subject,
                'reason'         => $reason,
                'date'           => $fullDate,
                'time'           => $time,
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            if ($parentUserId) {
                $title = 'استدعاء رسمي من الإدارة';
                $message = "تم إصدار استدعاء لولي أمر الطالب وذلك بخصوص: {$subject}";

                DB::table('notifications')->insert([
                    'user_id'    => $parentUserId,
                    'sender_id'  => auth()->id(),
                    'title'      => $title,
                    'message'    => $message,
                    'type'       => 'summon',
                    'category'   => 'administrative',
                    'related_id' => $summonId,
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \App\Services\FcmService::sendToUser($parentUserId, $title, $message, [
                    'type'       => 'summon',
                    'related_id' => (string)$summonId,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال استدعاء ولي الأمر بنجاح',
                'id'      => $summonId
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function getMeetingRequests(Request $request)
    {
        try {
            $requests = DB::table('parent_meeting_requests')
                ->join('users as parent_users', 'parent_meeting_requests.parent_user_id', '=', 'parent_users.user_id')
                ->leftJoin('students', 'parent_meeting_requests.student_id', '=', 'students.student_id')
                ->leftJoin('users as student_users', 'students.user_id', '=', 'student_users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->leftJoin('heads', 'departments.department_id', '=', 'heads.department_id')
                ->leftJoin('users as hod_users', 'heads.user_id', '=', 'hod_users.user_id')
                ->select(
                    'parent_meeting_requests.*',
                    'parent_users.full_name as parent_name',
                    'parent_users.phone as parent_phone',
                    'student_users.full_name as student_name',
                    'departments.name as department_name',
                    'hod_users.full_name as hod_name'
                )
                ->orderByDesc('parent_meeting_requests.created_at')
                ->get();

            return response()->json(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function respondToMeetingRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'status'         => 'required|string|in:approved,rejected',
            'admin_response' => 'nullable|string',
            'scheduled_at'   => 'nullable|string',
        ]);

        $meeting = DB::table('parent_meeting_requests')->where('id', $id)->first();
        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        // تحديد التاريخ النهائي للزيارة: إذا أدخل الشؤون تاريخاً يتم اعتماده، وإلا يتم استخدام التاريخ المفضل
        $scheduledAt = null;
        if (!empty($validated['scheduled_at'])) {
            $scheduledAt = date('Y-m-d H:i:s', strtotime($validated['scheduled_at']));
        } elseif (!empty($meeting->preferred_date)) {
            $scheduledAt = date('Y-m-d H:i:s', strtotime($meeting->preferred_date));
        } else {
            $scheduledAt = now()->toDateTimeString();
        }

        DB::table('parent_meeting_requests')->where('id', $id)->update([
            'status'         => $validated['status'],
            'admin_response' => $validated['admin_response'] ?? null,
            'scheduled_at'   => ($validated['status'] === 'approved') ? $scheduledAt : null,
            'updated_at'     => now(),
        ]);

        // إحضار اسم الطالب والقسم لرئيس القسم
        $studentName = 'طالب';
        $departmentId = $meeting->department_id;

        if ($meeting->student_id) {
            $student = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->where('students.student_id', $meeting->student_id)
                ->select('users.full_name as student_name', 'programs.department_id')
                ->first();
            if ($student) {
                $studentName = $student->student_name;
                if (!$departmentId) {
                    $departmentId = $student->department_id;
                }
            }
        }

        // تحديد رئيس قسم الطالب المعني
        $hodUserId = null;
        if ($departmentId) {
            $hodUserId = DB::table('heads')
                ->where('department_id', $departmentId)
                ->value('user_id');
        }
        if (!$hodUserId) {
            $hodUserId = DB::table('users')->where('role_id', 5)->value('user_id');
        }

        $formattedDate = $scheduledAt ? date('Y-m-d H:i', strtotime($scheduledAt)) : '';

        // 1. في حالة الموافقة (Approved)
        if ($validated['status'] === 'approved') {
            // أ) إرسال إشعار لولي الأمر بالموافقة والموعد النهائي
            $parentTitle = "تمت الموافقة على طلب الموعد";
            $parentNotes = !empty($validated['admin_response']) ? "\nملاحظات: " . $validated['admin_response'] : "";
            $parentMessage = "تمت الموافقة على طلب الموعد لمقابلة رئيس القسم بخصوص الطالب ({$studentName}).\nتاريخ الموعد المعتمد: {$formattedDate}{$parentNotes}";

            DB::table('notifications')->insert([
                'user_id'    => $meeting->parent_user_id,
                'sender_id'  => auth()->id(),
                'title'      => $parentTitle,
                'message'    => $parentMessage,
                'type'       => 'meeting_request',
                'category'   => 'administrative',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser($meeting->parent_user_id, $parentTitle, $parentMessage, [
                'type'       => 'meeting_request',
                'related_id' => (string)$id,
                'status'     => 'approved',
            ]);

            // ب) إرسال إشعار لرئيس القسم التابع له الطالب
            if ($hodUserId) {
                $hodTitle = "إشعار موعد موثق مع ولي أمر";
                $hodNotes = !empty($validated['admin_response']) ? "\nملاحظات الشؤون: " . $validated['admin_response'] : "";
                $hodMessage = "يرجى العلم بأن موظف الشؤون وافق على طلب لقاء ولي أمر الطالب ({$studentName}) لمقابلتك.\nالموعد المحدد: {$formattedDate}\nموضوع اللقاء: {$meeting->subject}{$hodNotes}";

                DB::table('notifications')->insert([
                    'user_id'    => $hodUserId,
                    'sender_id'  => auth()->id(),
                    'title'      => $hodTitle,
                    'message'    => $hodMessage,
                    'type'       => 'meeting_request',
                    'category'   => 'administrative',
                    'related_id' => $id,
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \App\Services\FcmService::sendToUser($hodUserId, $hodTitle, $hodMessage, [
                    'type'       => 'meeting_request',
                    'related_id' => (string)$id,
                    'status'     => 'approved',
                ]);
            }
        } 
        // 2. في حالة الرفض (Rejected)
        else {
            $reasonNote = !empty($validated['admin_response']) ? "\nسبب الرفض: " . $validated['admin_response'] : "";

            // أ) إرسال إشعار لولي الأمر بالرفض والسبب
            $parentTitle = "تم عدم الموافقة على طلب الموعد";
            $parentMessage = "نعتذر، لم يتم قبول طلب الموعد بخصوص الطالب ({$studentName}) من قبل الشؤون.{$reasonNote}";

            DB::table('notifications')->insert([
                'user_id'    => $meeting->parent_user_id,
                'sender_id'  => auth()->id(),
                'title'      => $parentTitle,
                'message'    => $parentMessage,
                'type'       => 'meeting_request',
                'category'   => 'administrative',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser($meeting->parent_user_id, $parentTitle, $parentMessage, [
                'type'       => 'meeting_request',
                'related_id' => (string)$id,
                'status'     => 'rejected',
            ]);

            // ب) إرسال إشعار لرئيس القسم بطلب الموعد المرفوض
            if ($hodUserId) {
                $hodTitle = "إشعار بطلب موعد غير موافق عليه";
                $hodMessage = "يرجى العلم بأن ولي أمر الطالب ({$studentName}) كان قد تقدم بطلب لمقابلتك، ولم يوافق موظف الشؤون على الطلب.{$reasonNote}";

                DB::table('notifications')->insert([
                    'user_id'    => $hodUserId,
                    'sender_id'  => auth()->id(),
                    'title'      => $hodTitle,
                    'message'    => $hodMessage,
                    'type'       => 'meeting_request',
                    'category'   => 'administrative',
                    'related_id' => $id,
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \App\Services\FcmService::sendToUser($hodUserId, $hodTitle, $hodMessage, [
                    'type'       => 'meeting_request',
                    'related_id' => (string)$id,
                    'status'     => 'rejected',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت معالجة طلب الموعد وإشعار كافة الأطراف بنجاح'
        ]);
    }

    // ── إدارة الفصول الدراسية ─────────────────────────────────────
    public function listSemesters(Request $request)
    {
        $semesters = DB::table('semesters')->orderBy('semester_id')->get();
        return response()->json([
            'success' => true,
            'data'    => $semesters,
        ]);
    }

    public function activateSemester(Request $request, $id)
    {
        $target = DB::table('semesters')->where('semester_id', $id)->first();
        if (!$target) {
            return response()->json(['success' => false, 'message' => 'الفصل غير موجود'], 404);
        }

        DB::table('semesters')->update(['is_active' => false]);
        DB::table('semesters')->where('semester_id', $id)->update(['is_active' => true, 'updated_at' => now()]);

        // إرسال إشعار عام للطلاب والأساتذة
        $title   = 'تحديث الفصل الدراسي 📅';
        $message = "تم تفعيل " . $target->name . " رسمياً بالمعهد. نتمنى لكم التوفيق والنجاح!";

        $allUsers = User::whereIn('role_id', [2, 3])->pluck('user_id');
        foreach ($allUsers as $uId) {
            DB::table('notifications')->insert([
                'user_id'    => $uId,
                'sender_id'  => auth()->id(),
                'title'      => $title,
                'message'    => $message,
                'type'       => 'academic',
                'category'   => 'academic',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \App\Models\UserActivity::log('تفعيل فصل دراسي', "قام موظف الشؤون بتفعيل الفصل الدراسي: {$target->name}");

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل ' . $target->name . ' بنجاح وإشعار كادر المعهد والطلاب.'
        ]);
    }

    // ── الترفيع الأكاديمي للطلاب (من سنة أولى إلى سنة ثانية) ──────
    public function promoteStudentsYear(Request $request)
    {
        $query = Student::query();

        if ($request->filled('student_ids')) {
            $studentIds = is_array($request->student_ids) ? $request->student_ids : explode(',', $request->student_ids);
            $query->whereIn('student_id', $studentIds);
        } elseif ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        } else {
            $query->where(function($q) {
                $q->whereIn('level', ['السنة الأولى', 'أولى', '1'])->orWhereNull('level')->orWhere('level', '');
            });
        }

        $students = $query->get();
        $promotedCount = 0;

        foreach ($students as $student) {
            $targetLevel = $request->input('target_level', 'السنة الثانية');

            $student->update([
                'level' => $targetLevel,
                'updated_at' => now(),
            ]);

            // تحديث الحساب الأساسي للمستخدم
            DB::table('users')->where('user_id', $student->user_id)->update([
                'academic_year' => $targetLevel,
            ]);

            // تسجيل الطالب تلقائياً في مواد السنة الجديدة
            Student::autoEnrollCourses($student->student_id);

            // إرسال إشعار ترفيع للطالب
            $title   = 'تهانينا! تم ترفيعك الأكاديمي 🎉';
            $message = "تم ترفيعك بنجاح إلى {$targetLevel}. نتمنى لك دوام التوفيق والتميز!";
            DB::table('notifications')->insert([
                'user_id'    => $student->user_id,
                'sender_id'  => auth()->id(),
                'title'      => $title,
                'message'    => $message,
                'type'       => 'academic',
                'category'   => 'academic',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $promotedCount++;
        }

        \App\Models\UserActivity::log('ترفيع الطلاب أكاديمياً', "قام موظف الشؤون بترفيع عدد {$promotedCount} طالباً للمرحلة الأكاديمية التالية");

        return response()->json([
            'success' => true,
            'message' => "تم ترفيع {$promotedCount} طالباً بنجاح وتسجيل موادهم للمرحلة الجديدة."
        ]);
    }

    // ── استعلام وتصدير كشف العلامات لموظف الشؤون ──────────────────────────

    /**
     * جلب قائمة الطلاب مفلترة حسب القسم والدورة/السنة الأكاديمية ومطلوبة مرتبة أبجدياً
     */
    public function getFilteredStudentsForAcademicCard(Request $request)
    {
        $query = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id');

        // 1. الفلترة حسب القسم
        if ($request->filled('department_id')) {
            $deptId = $request->department_id;
            $query->where(function($q) use ($deptId) {
                $q->whereExists(function($sub) use ($deptId) {
                    $sub->select(DB::raw(1))
                        ->from('programs')
                        ->whereColumn('programs.id', 'students.program_id')
                        ->where('programs.department_id', $deptId);
                })
                ->orWhere('users.department', function($sub) use ($deptId) {
                    $sub->select('name')->from('departments')->where('department_id', $deptId);
                });
            });
        } elseif ($request->filled('program_id')) {
            $query->where('students.program_id', $request->program_id);
        } elseif ($request->filled('department')) {
            $dept = $request->department;
            $query->where(function($q) use ($dept) {
                $q->where('users.department', $dept)
                  ->orWhere('users.branch', $dept);
            });
        }

        // 2. الفلترة حسب الدورة / الفصل الدراسي
        if ($request->filled('semester_id') && $request->semester_id !== 'الكل') {
            $semId = $request->semester_id;
            $query->whereExists(function($q) use ($semId) {
                $q->select(DB::raw(1))
                  ->from('enrollments')
                  ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
                  ->whereColumn('enrollments.student_id', 'students.student_id')
                  ->where('courses.semester_id', $semId);
            });
        }

        // 3. الفلترة حسب السنة الدراسية (السنة الأولى أو الثانية فقط)
        if ($request->filled('level') && $request->level !== 'الكل') {
            $level = $request->level;
            $query->where(function($q) use ($level) {
                $q->where('students.level', $level)
                  ->orWhere('users.academic_year', $level);
            });
        }

        // 4. فلترة بالبحث عن الاسم أو الرقم الجامعي
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('users.full_name', 'like', "%{$search}%")
                  ->orWhere('users.university_id', 'like', "%{$search}%")
                  ->orWhere('students.student_code', 'like', "%{$search}%");
            });
        }

        // ترتيب أبجدي حسب اسم الطالب
        $students = $query->select(
            'students.student_id',
            'students.user_id',
            'students.student_code',
            'students.level',
            'students.program_id',
            'users.full_name',
            'users.university_id',
            'users.department',
            'users.branch',
            'users.academic_year'
        )
        ->orderBy('users.full_name', 'asc')
        ->get();

        $programs = DB::table('programs')->pluck('name', 'id');
        $students->transform(function($st) use ($programs) {
            $st->program_name = $programs[$st->program_id] ?? $st->department ?? $st->branch ?? 'قسم عام';
            return $st;
        });

        return response()->json([
            'success' => true,
            'data'    => $students
        ]);
    }

    /**
     * جلب بطاقة الطالب تفصيلياً لموظف الشؤون عبر student_id
     */
    public function getStudentAcademicCardForAffairs(Request $request)
    {
        $studentId = $request->input('student_id') ?? $request->query('student_id');
        $universityId = $request->input('university_id') ?? $request->query('university_id');

        $query = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id');

        if (!empty($studentId)) {
            $query->where('students.student_id', $studentId);
        } elseif (!empty($universityId)) {
            $query->where(function($q) use ($universityId) {
                $q->where('users.university_id', $universityId)
                  ->orWhere('students.student_code', $universityId)
                  ->orWhere('students.student_id', $universityId);
            });
        } else {
            return response()->json([
                'success' => false,
                'message' => 'يرجى تزويد معرّف الطالب'
            ], 400);
        }

        $student = $query->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على سجل الطالب'
            ], 404);
        }

        $studentLevel = trim($student->level ?? 'السنة الأولى');
        $map = [
            'السنة الأولى' => 1, 'أولى' => 1, '1' => 1,
            'السنة الثانية' => 2, 'ثانية' => 2, '2' => 2,
        ];
        $studentYearInt = $map[$studentLevel] ?? 1;

        if ($request->filled('year')) {
            $reqYear = (int)$request->year;
            if ($reqYear >= 1) $studentYearInt = $reqYear;
        } elseif ($request->filled('level') && isset($map[trim($request->level)])) {
            $studentYearInt = $map[trim($request->level)];
        }

        $enrolledCourses = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.student_id', $student->student_id)
            ->where('courses.year', '=', $studentYearInt)
            ->select('courses.*')
            ->get();

        if ($enrolledCourses->isEmpty()) {
            $enrolledCourses = DB::table('courses')
                ->where('year', '=', $studentYearInt)
                ->get();
        }

        $academicCardData = [];
        $totalScoresSum = 0;
        $totalCoursesCount = 0;
        $passedCount = 0;
        $failedCount = 0;
        $notAttendedCount = 0;

        foreach ($enrolledCourses as $course) {
            $examGrades = DB::table('grades')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->where('grades.student_id', $student->student_id)
                ->where('exams.course_id', $course->course_id)
                ->select('grades.score', 'exams.exam_name')
                ->get();

            $eventGrades = DB::table('grade_entries')
                ->join('grade_events', 'grade_entries.grade_event_id', '=', 'grade_events.id')
                ->where('grade_entries.student_id', $student->student_id)
                ->where('grade_events.course_id', $course->course_id)
                ->select('grade_entries.score', 'grade_events.type as event_type', 'grade_events.title')
                ->get();

            $quizScore = null;
            $oralScore = null;
            $finalScore = null;

            foreach ($eventGrades as $eg) {
                $type = strtolower($eg->event_type ?? '');
                if (str_contains($type, 'quiz') || str_contains($type, 'مذاكرة')) {
                    $quizScore = $eg->score;
                } elseif (str_contains($type, 'oral') || str_contains($type, 'عملي') || str_contains($type, 'شفهي')) {
                    $oralScore = $eg->score;
                } elseif (str_contains($type, 'exam') || str_contains($type, 'امتحان') || str_contains($type, 'نهائي')) {
                    $finalScore = $eg->score;
                } else {
                    if ($quizScore === null) $quizScore = $eg->score;
                }
            }

            foreach ($examGrades as $eg) {
                $name = mb_strtolower($eg->exam_name ?? '');
                if (str_contains($name, 'مذاكرة') || str_contains($name, 'quiz')) {
                    if ($quizScore === null) $quizScore = $eg->score;
                } elseif (str_contains($name, 'عملي') || str_contains($name, 'شفهي')) {
                    if ($oralScore === null) $oralScore = $eg->score;
                } else {
                    if ($finalScore === null) $finalScore = $eg->score;
                }
            }

            $hasAnyScore = ($quizScore !== null || $oralScore !== null || $finalScore !== null);
            $totalScore = null;
            $status = 'لم يتم التقدم';

            if ($hasAnyScore) {
                $q = $quizScore ?? 0;
                $o = $oralScore ?? 0;
                $f = $finalScore ?? 0;
                $totalScore = min(100, $q + $o + $f);

                if ($totalScore >= 50) {
                    $status = 'ناجح';
                    $passedCount++;
                } else {
                    $status = 'راسب';
                    $failedCount++;
                }

                $totalScoresSum += $totalScore;
                $totalCoursesCount++;
            } else {
                $notAttendedCount++;
            }

            $academicCardData[] = [
                'course_id'   => $course->course_id,
                'title'       => $course->title,
                'code'        => $course->code ?? '',
                'year'        => $course->year,
                'semester'    => $course->semester ?? 1,
                'quiz_score'  => $quizScore !== null ? (float)$quizScore : null,
                'oral_score'  => $oralScore !== null ? (float)$oralScore : null,
                'final_score' => $finalScore !== null ? (float)$finalScore : null,
                'total_score' => $totalScore !== null ? (float)$totalScore : null,
                'status'      => $status,
            ];
        }

        $average = $totalCoursesCount > 0 ? round($totalScoresSum / $totalCoursesCount, 2) : 0;
        $programName = DB::table('programs')->where('id', $student->program_id)->value('name') ?? $student->department ?? 'عام';

        return response()->json([
            'success' => true,
            'student' => [
                'student_id'    => $student->student_id,
                'full_name'     => $student->full_name,
                'university_id' => $student->university_id ?? $student->student_code ?? '',
                'student_code'  => $student->student_code ?? '',
                'level'         => $student->level ?? 'السنة الأولى',
                'department'    => $programName,
                'avatar'        => $student->avatar ? storageUrl($student->avatar) : null,
            ],
            'summary' => [
                'average'          => $average,
                'total_courses'    => count($academicCardData),
                'passed_courses'   => $passedCount,
                'failed_courses'   => $failedCount,
                'not_attended'     => $notAttendedCount,
            ],
            'academic_card' => $academicCardData,
        ]);
    }

    /**
     * تصدير بطاقة الطالب بصيغة PDF
     */
    public function exportStudentAcademicCardPdf(Request $request)
    {
        $cardResponse = $this->getStudentAcademicCardForAffairs($request);
        $content = json_decode($cardResponse->getContent(), true);

        if (!$content || !($content['success'] ?? false)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'فشل جلب بيانات كشف العلامات للتصدير'], 400);
            }
            return back()->with('error', 'فشل جلب بيانات كشف العلامات للتصدير');
        }

        $student = $content['student'];
        $summary = $content['summary'];
        $academicCard = $content['academic_card'];

        $html = view('exports.academic_card_pdf', compact('student', 'summary', 'academicCard'))->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        $fileName = 'academic_card_' . ($student['university_id'] ?? $student['student_id']) . '.pdf';

        if ($request->expectsJson() || $request->is('api/*')) {
            $directory = public_path('exports');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $filePath = $directory . '/' . $fileName;
            file_put_contents($filePath, $mpdf->Output('', 'S'));

            return response()->json([
                'success' => true,
                'file_url' => url('exports/' . $fileName),
                'file_name' => $fileName,
            ]);
        }

        // Direct PDF download response for browser requests
        return response($mpdf->Output($fileName, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * تصدير بطاقة الطالب بصيغة Excel (.xls formatted RTL HTML Table)
     */
    public function exportStudentAcademicCardExcel(Request $request)
    {
        $cardResponse = $this->getStudentAcademicCardForAffairs($request);
        $content = json_decode($cardResponse->getContent(), true);

        if (!$content || !($content['success'] ?? false)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'فشل جلب بيانات كشف العلامات للتصدير'], 400);
            }
            return back()->with('error', 'فشل جلب بيانات كشف العلامات للتصدير');
        }

        $student = $content['student'];
        $summary = $content['summary'];
        $academicCard = $content['academic_card'];

        $fileName = 'academic_card_' . ($student['university_id'] ?? $student['student_id']) . '.xls';

        $xlsContent = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]>
    <xml>
     <x:ExcelWorkbook>
      <x:ExcelWorksheets>
       <x:ExcelWorksheet>
        <x:Name>كشف العلامات الأكاديمي</x:Name>
        <x:WorksheetOptions>
         <x:DisplayRightToLeft/>
        </x:WorksheetOptions>
       </x:ExcelWorksheet>
      </x:ExcelWorksheets>
     </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: Segoe UI, Tahoma, Arial, sans-serif; direction: rtl; text-align: right; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th { background-color: #1e293b; color: #ffffff; font-weight: bold; border: 1px solid #000000; padding: 10px; text-align: center; font-size: 11pt; }
        td { border: 1px solid #cbd5e1; padding: 8px; text-align: center; vertical-align: middle; font-size: 10pt; }
        .header-title { font-size: 16pt; font-weight: bold; color: #0f172a; text-align: center; padding: 10px; }
        .info-label { font-weight: bold; background-color: #f1f5f9; text-align: right; width: 18%; }
        .info-val { text-align: right; width: 32%; }
        .pass { color: #15803d; font-weight: bold; background-color: #dcfce7; }
        .fail { color: #b91c1c; font-weight: bold; background-color: #fee2e2; }
        .pending { color: #854d0e; background-color: #fef9c3; }
    </style>
</head>
<body>
    <div class="header-title">معهد الجسر التعليمي (Edu Bridge) - كشف العلامات الأكاديمي</div>
    <br>
    <table style="width: 100%;">
        <tr>
            <td class="info-label">اسم الطالب:</td>
            <td class="info-val"><b>' . htmlspecialchars($student['full_name'] ?? '') . '</b></td>
            <td class="info-label">الرقم الجامعي:</td>
            <td class="info-val"><b>' . htmlspecialchars($student['university_id'] ?? '') . '</b></td>
        </tr>
        <tr>
            <td class="info-label">التخصص / القسم:</td>
            <td class="info-val">' . htmlspecialchars($student['department'] ?? '') . '</td>
            <td class="info-label">السنة الدراسية:</td>
            <td class="info-val">' . htmlspecialchars($student['level'] ?? '') . '</td>
        </tr>
        <tr>
            <td class="info-label">المعدل التراكمي:</td>
            <td class="info-val"><b>' . htmlspecialchars($summary['average'] ?? '0') . '%</b></td>
            <td class="info-label">المواد المجتازة:</td>
            <td class="info-val"><b>' . htmlspecialchars($summary['passed_courses'] ?? '0') . ' من ' . htmlspecialchars($summary['total_courses'] ?? '0') . '</b></td>
        </tr>
    </table>
    <br>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">اسم المادة الدراسية</th>
                <th style="width: 15%;">السنة / الفصل</th>
                <th style="width: 10%;">المذاكرة (25)</th>
                <th style="width: 10%;">الشفهي/العملي (25)</th>
                <th style="width: 10%;">الامتحان النهائي (50)</th>
                <th style="width: 10%;">المجموع الكلي (100)</th>
                <th style="width: 10%;">الحالة</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($academicCard as $idx => $c) {
            $statusClass = 'pending';
            if ($c['status'] === 'ناجح') $statusClass = 'pass';
            elseif ($c['status'] === 'راسب') $statusClass = 'fail';

            $xlsContent .= '
            <tr>
                <td>' . ($idx + 1) . '</td>
                <td style="text-align: right;"><b>' . htmlspecialchars($c['title']) . '</b></td>
                <td>سنة ' . htmlspecialchars($c['year'] ?? 1) . ' - فصل ' . htmlspecialchars($c['semester'] ?? 1) . '</td>
                <td>' . ($c['quiz_score'] !== null ? $c['quiz_score'] : '-') . '</td>
                <td>' . ($c['oral_score'] !== null ? $c['oral_score'] : '-') . '</td>
                <td>' . ($c['final_score'] !== null ? $c['final_score'] : '-') . '</td>
                <td><b>' . ($c['total_score'] !== null ? $c['total_score'] : '-') . '</b></td>
                <td class="' . $statusClass . '">' . htmlspecialchars($c['status']) . '</td>
            </tr>';
        }

        $xlsContent .= '
        </tbody>
    </table>
</body>
</html>';

        if ($request->expectsJson() || $request->is('api/*')) {
            $directory = public_path('exports');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $filePath = $directory . '/' . $fileName;
            file_put_contents($filePath, $xlsContent);

            return response()->json([
                'success' => true,
                'file_url' => url('exports/' . $fileName),
                'file_name' => $fileName,
            ]);
        }

        return response($xlsContent, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    // ── Student Service Requests (الخدمات الطلابية، فك قفل الجهاز، إلخ) ──
    public function listStudentRequests(Request $request)
    {
        $query = \App\Models\StudentRequest::with(['student.user', 'student.program.department']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->whereIn('status', ['pending_affairs', 'pending']);
            } else if ($request->status === 'completed') {
                $query->whereIn('status', ['approved', 'rejected', 'completed']);
            }
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($req) {
                return [
                    'id'               => $req->id,
                    'student_id'       => $req->student_id,
                    'student_name'     => $req->student?->user?->full_name ?? 'غير معروف',
                    'student_code'     => $req->student?->student_code ?? 'N/A',
                    'academic_year'    => $req->student?->user?->academic_year ?? 'N/A',
                    'department'       => $req->student?->program?->department?->name ?? 'غير محدد',
                    'program'          => $req->student?->program?->name ?? 'غير محدد',
                    'type'             => $req->type,
                    'details'          => $req->details,
                    'status'           => $req->status,
                    'affairs_decision' => $req->affairs_decision,
                    'affairs_notes'    => $req->affairs_notes,
                    'created_at'       => $req->created_at?->format('Y-m-d H:i'),
                ];
            });

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /**
     * اعتماد وإصدار استدعاء ولي الأمر النهائي من الشؤون مع إشعاره وتحديد التاريخ والوقت
     */
    public function issueParentSummon(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'summon_date' => 'required|string',
            'notes'       => 'nullable|string'
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $summon = DB::table('parent_summons')->where('summon_id', $id)->first();
        if (!$summon) {
            return response()->json(['success' => false, 'message' => 'طلب الاستدعاء غير موجود'], 404);
        }

        $summonDate = date('Y-m-d H:i:s', strtotime($request->summon_date));

        DB::table('parent_summons')->where('summon_id', $id)->update([
            'summon_date' => $summonDate,
            'details'     => $request->notes ? $summon->details . "\n[ملاحظات الشؤون: " . $request->notes . "]" : $summon->details,
            'status'      => 'sent',
            'updated_at'  => now(),
        ]);

        $parentUserId = $summon->parent_user_id;
        if ($parentUserId) {
            $studentName = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('students.student_id', $summon->student_id)
                ->value('users.full_name') ?? 'طالب';

            $title = 'إشعار استدعاء رسمي لولي الأمر';
            $msg   = 'نحيطكم علماً بضرورة مراجعة إدارة المعهد بخصوص الطالب: ' . $studentName . ' - تاريخ الموعد: ' . $request->summon_date;

            DB::table('notifications')->insert([
                'user_id'    => $parentUserId,
                'sender_id'  => auth()->id(),
                'title'      => $title,
                'message'    => $msg,
                'type'       => 'summon',
                'category'   => 'administrative',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser($parentUserId, $title, $msg, [
                'type'       => 'summon',
                'related_id' => (string) $id,
                'screen'     => 'parent_appointments'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إصدار وتأكيد استدعاء ولي الأمر وإشعار الأهل بالمواصفات المحددة بنجاح.'
        ]);
    }

    public function processStudentRequest(Request $request, $id)
    {
        $studentReq = \App\Models\StudentRequest::find($id);
        if (!$studentReq) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود.'], 404);
        }

        if (!in_array($studentReq->status, ['pending_affairs', 'pending'])) {
            return response()->json(['success' => false, 'message' => 'تم اتخاذ القرار في هذا الطلب مسبقاً.'], 422);
        }

        $v = Validator::make($request->all(), [
            'decision' => 'required|in:approved,rejected',
            'notes'    => 'required|string'
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $studentReq->affairs_decision = $request->decision;
        $studentReq->affairs_notes = $request->notes;

        if ($studentReq->type === 'device_reset') {
            $studentReq->status = $request->decision === 'approved' ? 'approved' : 'rejected';
            $studentReq->save();

            $student = $studentReq->student;
            if ($student && $request->decision === 'approved') {
                $student->update([
                    'device_id'        => null,
                    'is_device_locked' => 0,
                ]);

                DB::table('personal_access_tokens')
                    ->where('tokenable_id', $student->user_id)
                    ->delete();

                \App\Models\Notification::create([
                    'user_id' => $student->user_id,
                    'title'   => 'تم فك قفل الجهاز',
                    'message' => 'وافقت شؤون الطلاب على طلب فك قفل الجهاز الخاص بك. تم تسجيل الخروج من الأجهزة القديمة وتصفير القفل، يمكنك الآن تسجيل الدخول من جهازك الجديد.',
                    'type'    => 'academic',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $request->decision === 'approved' 
                    ? 'تمت الموافقة على طلب فك قفل الجهاز وتصفير الجهاز وتسجيل الخروج بنجاح.' 
                    : 'تم رفض طلب فك قفل الجهاز.'
            ]);
        }

        $studentReq->status = 'pending_hod';
        $studentReq->save();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ رأي الشؤون بنجاح وتحويل الطلب إلى رئيس القسم.'
        ]);
    }
}


