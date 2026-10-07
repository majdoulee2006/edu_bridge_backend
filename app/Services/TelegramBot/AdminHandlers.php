<?php

namespace App\Services\TelegramBot;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Parents;
use App\Models\Schedule;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Exam;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\AbsenceRequest;
use App\Models\StudentRequest;
use App\Models\Program;
use App\Services\FcmService;
use App\Support\LoginThrottleGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Telegram bot: super admin services
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
trait AdminHandlers
{
    // ==========================================
    // Super Admin / General Manager Services Handlers (المدير العام / الإدارة)
    // ==========================================

    private function sendAdminMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '📊 لوحة المؤشرات الشاملة'], ['text' => '👥 تدقيق وتفعيل الحسابات']],
                [['text' => '📑 الطلبات والقرارات الإدارية'], ['text' => '🔍 إدارة واستعلام المستخدمين']],
                [['text' => '📋 سجل الأمان والنشاطات'], ['text' => '📢 بث وتعميم إداري شامل']],
                [['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'persistent' => true
        ];

        $text = $headerText ?? "👑 **لوحة قيادة وإدارة منظومة Edu Bridge**\nيرجى اختيار الخدمة أو الإجراء المطلوب:";
        $this->sendMessage($chatId, $text, null, $keyboard);
    }

    private function handleAdminOverview(User $user, $chatId)
    {
        $totalStudents = User::where('role_id', 3)->count();
        $totalTeachers = User::where('role_id', 2)->count();
        $totalParents  = User::where('role_id', 4)->count();
        $totalHods     = User::where('role_id', 5)->count();
        $totalAffairs  = User::where('role_id', 6)->count();
        $totalAdmins   = User::where('role_id', 1)->count();
        $totalUsers    = User::count();

        $activeUsers   = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', 'inactive')->count();

        $totalDepts    = DB::table('departments')->count();
        $totalPrograms = DB::table('programs')->count();
        $totalCourses  = DB::table('courses')->count();

        $pendingAccounts = User::where('status', 'inactive')->where('role_id', '!=', 1)->count();
        $pendingReqs     = StudentRequest::where('status', 'pending_admin')->count();

        $activeSemester  = DB::table('semesters')->where('is_active', true)->first();
        $semesterName    = $activeSemester->semester_name ?? $activeSemester->name ?? 'الفصل الحالي نشط';

        $msg = "👑 **التقرير الشامل ولوحة مؤشرات الإدارة العليا**\n\n";
        $msg .= "👤 **المدير العام:** {$user->full_name}\n";
        $msg .= "🗓️ **الفصل الدراسي المعتمد:** `{$semesterName}`\n";
        $msg .= "─────────────\n";
        $msg .= "👥 **إحصائيات المستخدمين الكلية ({$totalUsers}):**\n";
        $msg .= "  🎓 الطلاب: `{$totalStudents}` | 👨‍🏫 المعلمون: `{$totalTeachers}`\n";
        $msg .= "  👨‍👩‍👧 أولياء الأمور: `{$totalParents}` | 🏛️ رؤساء الأقسام: `{$totalHods}`\n";
        $msg .= "  🏢 شؤون الطلاب: `{$totalAffairs}` | 👑 الإدارة: `{$totalAdmins}`\n";
        $msg .= "  🟢 الحسابات النشطة: `{$activeUsers}` | 🔴 غير المفعلة/الموقوفة: `{$inactiveUsers}`\n";
        $msg .= "─────────────\n";
        $msg .= "🏛️ **الهيكل الأكاديمي:**\n";
        $msg .= "  🏢 الأقسام: `{$totalDepts}` | 📚 التخصصات: `{$totalPrograms}` | 📖 المقررات: `{$totalCourses}`\n";
        $msg .= "─────────────\n";
        $msg .= "⚡ **إجراءات الإدارة وقرارات الموافقة المعلقة:**\n";
        $msg .= "  👥 حسابات جديدة بانتظار التفعيل: `{$pendingAccounts}`\n";
        $msg .= "  📑 طلبات خدمات وقرارات بانتظار توقيع الإدارة: `{$pendingReqs}`\n";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👥 تفعيل الحسابات (' . $pendingAccounts . ')', 'callback_data' => 'admin_action_pending_accounts'],
                    ['text' => '📑 قرارات الإدارة (' . $pendingReqs . ')', 'callback_data' => 'admin_action_requests'],
                ],
                [
                    ['text' => '📋 سجل الأمان اللحظي', 'callback_data' => 'admin_action_activities'],
                    ['text' => '📢 بث تعميم إداري', 'callback_data' => 'admin_action_broadcast'],
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleAdminPendingAccounts(User $user, $chatId)
    {
        $pendingUsers = User::where('status', 'inactive')
            ->where('role_id', '!=', 1)
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        if ($pendingUsers->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي حسابات معلقة بانتظار التفعيل حالياً.**\nكافة الحسابات مفعلة ونشطة.");
            return;
        }

        $this->sendMessage($chatId, "👥 **الحسابات الجديدة المعلقة بانتظار تدقيق واعتماد الإدارة ({$pendingUsers->count()}):**");

        foreach ($pendingUsers as $u) {
            $roleLabel = match((int)$u->role_id) {
                2 => '👨‍🏫 كادر المعلمين / المدربين',
                3 => '🎓 طالب مسجل',
                4 => '👨‍👩‍👧 ولي أمر',
                5 => '🏛️ رئيس قسم أكاديمي',
                6 => '🏢 موظف شؤون طلاب',
                default => 'مستخدم',
            };

            $date = date('Y-m-d h:i A', strtotime($u->created_at));
            $uid = $u->university_id ?? $u->username ?? '-';
            $phone = $u->phone ?? 'غير متوفر';

            $msg = "👤 **الاسم:** {$u->full_name}\n";
            $msg .= "📌 **الدور:** {$roleLabel}\n";
            $msg .= "🆔 **المعرف / الرقم:** `{$uid}`\n";
            $msg .= "📞 **الهاتف:** `{$phone}` | 📧 **الإيميل:** `{$u->email}`\n";
            $msg .= "🕒 **تاريخ التسجيل:** `{$date}`\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ تفعيل وقبول الحساب', 'callback_data' => "admin_approve_user_{$u->user_id}"],
                        ['text' => '❌ رفض وحذف', 'callback_data' => "admin_reject_user_{$u->user_id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $msg, null, $keyboard);
        }
    }

    /** طلب خدمة طالب يحق للإدارة البتّ فيه الآن: pending_admin فقط (القرار النهائي بعد الشؤون ورئيس القسم). */
    private function adminFindActionableRequest($chatId, int $reqId): ?StudentRequest
    {
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return null;
        }

        if ($studentReq->status !== 'pending_admin') {
            $this->sendMessage($chatId, "ℹ️ تم البتّ في هذا الطلب مسبقاً أو لم يصل مرحلة الإدارة بعد، ولا يمكن تعديله.");
            return null;
        }

        return $studentReq;
    }

    private function handleAdminApproveUser(User $user, $chatId, int $targetUserId)
    {
        $targetUser = User::find($targetUserId);
        // حساب معلّق فقط (نفس شرط قائمة التدقيق). بدون هذا الفحص كان زر قديم يحذف حساباً فعالاً (الرفض يحذف المستخدم وبياناته).
        if (!$targetUser || $targetUser->status !== 'inactive' || (int) $targetUser->role_id === 1) {
            $this->sendMessage($chatId, "❌ الحساب غير موجود أو تمت معالجته.");
            return;
        }

        $targetUser->status = 'active';
        $targetUser->updated_at = now();
        $targetUser->save();

        // تفعيل تسجيل المواد التلقائي إن كان طالباً
        if ($targetUser->role_id == 3) {
            $student = Student::where('user_id', $targetUserId)->first();
            if ($student) {
                Student::autoEnrollCourses($student->student_id);
            }
        }

        \App\Models\UserActivity::log('تفعيل حساب', "قام المدير العام ({$user->full_name}) بتفعيل وقبول حساب: {$targetUser->full_name} ({$targetUser->username})", $user);

        // إشعار داخلي و FCM
        $notifTitle = 'تم تفعيل حسابك بنجاح ✓';
        $notifMsg   = "مرحباً {$targetUser->full_name}! تم اعتماد وتفعيل حسابك رسمياً من قِبل إدارة المعهد.";

        Notification::create([
            'user_id'    => $targetUserId,
            'sender_id'  => $user->user_id,
            'title'      => $notifTitle,
            'message'    => $notifMsg,
            'type'       => 'administrative',
            'category'   => 'administrative',
            'is_read'    => false,
        ]);

        FcmService::sendToUser($targetUserId, $notifTitle, $notifMsg, ['type' => 'administrative']);

        // إشعار تليجرام للمستخدم المفعّل إن كان حسابه مربوطاً
        if ($targetUser->telegram_chat_id) {
            $idText = $targetUser->university_id ?? $targetUser->username ?? '';
            $welcomeText = "🎓 <b>تفعيل الحساب الرسمي - Edu Bridge</b>\n\n"
                . "مرحباً <b>{$targetUser->full_name}</b>،\n\n"
                . "🎉 لقد تم <b>الموافقة وتفعيل حسابك بنجاح</b> من قِبل إدارة المعهد!\n"
                . ($idText ? "🆔 <b>المعرف الجامعي:</b> <code>{$idText}</code>\n\n" : "\n")
                . "📲 يمكنك الآن تسجيل الدخول مباشرة والوصول لكافة الخدمات والمحاضرات.";
            $this->sendMessage((int)$targetUser->telegram_chat_id, $welcomeText);
        }

        $this->sendMessage($chatId, "✅ **تم تفعيل وقبول حساب ({$targetUser->full_name}) بنجاح وإشعاره فورياً!**");
    }

    private function handleAdminRejectUser(User $user, $chatId, int $targetUserId)
    {
        $targetUser = User::find($targetUserId);
        // حساب معلّق فقط (نفس شرط قائمة التدقيق). بدون هذا الفحص كان زر قديم يحذف حساباً فعالاً (الرفض يحذف المستخدم وبياناته).
        if (!$targetUser || $targetUser->status !== 'inactive' || (int) $targetUser->role_id === 1) {
            $this->sendMessage($chatId, "❌ الحساب غير موجود أو تمت معالجته مسبقاً.");
            return;
        }

        $userName = $targetUser->full_name;

        // إشعار تليجرام بالرفض قبل الحذف
        if ($targetUser->telegram_chat_id) {
            $rejectText = "🎓 <b>طلب التسجيل - Edu Bridge</b>\n\n"
                . "مرحباً <b>{$userName}</b>،\n\n"
                . "⚠️ نأسف لإعلامك بأنه تم <b>رفض طلب إنشاء الحساب</b> من قِبل إدارة المعهد.\n"
                . "يرجى مراجعة إدارة شؤون الطلاب للمزيد من التفاصيل.";
            $this->sendMessage((int)$targetUser->telegram_chat_id, $rejectText);
        }

        if ($targetUser->university_id) {
            DB::table('university_ids')
                ->where('university_id', $targetUser->university_id)
                ->update(['is_used' => false]);
        }

        DB::table('students')->where('user_id', $targetUserId)->delete();
        DB::table('parents')->where('user_id', $targetUserId)->delete();
        DB::table('teachers')->where('user_id', $targetUserId)->delete();
        DB::table('heads')->where('user_id', $targetUserId)->delete();
        $targetUser->delete();

        \App\Models\UserActivity::log('رفض حساب', "قام المدير العام ({$user->full_name}) برفض وحذف حساب: {$userName}", $user);

        $this->sendMessage($chatId, "🛑 **تم رفض وحذف حساب ({$userName}) بنجاح.**");
    }

    private function handleAdminStudentRequests(User $user, $chatId)
    {
        $requests = StudentRequest::with(['student.user', 'student.program.department'])
            ->where('status', 'pending_admin')
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        if ($requests->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي طلبات خدمات طلابية معلقة بانتظار قرار الإدارة حالياً.**");
            return;
        }

        $this->sendMessage($chatId, "📑 **طلبات الخدمات المرفوعة لقرار الإدارة العليا ({$requests->count()}):**");

        foreach ($requests as $req) {
            $student = $req->student;
            $stUser = $student->user ?? null;
            $stName = $stUser->full_name ?? 'طالب';
            $code = $student && $student->student_code ? " (`{$student->student_code}`)" : "";
            $program = $student->program->name ?? 'عام';

            $typeLabel = match($req->type) {
                'document'   => '📜 طلب وثيقة / كشف علامات رسمي',
                'transcript' => '📜 كشف درجات وسجل أكاديمي',
                'freeze'     => '❄️ طلب تجميد / إيقاف تسجيل',
                'transfer'   => '🔄 طلب انتقال أو تعديل تخصص',
                default      => '📑 ' . ($req->type ?? 'طلب خدمة طلابية'),
            };

            $details = $req->formatted_details ?: ($req->details ?: 'لا توجد تفاصيل');
            $affDecision = $req->affairs_decision ? ($req->affairs_decision === 'approved' ? '✅ موافقة الشؤون' : '❌ تحفظ الشؤون') : 'لم تُبدَ';
            $hodDecision = $req->hod_decision ? ($req->hod_decision === 'approved' ? '✅ توصية رئيس القسم بالقبول' : '❌ عدم قبول رئيس القسم') : 'بانتظار الرأي';

            $msg = "🎓 **الطالب:** {$stName}{$code}\n";
            $msg .= "🏛️ **التخصص:** {$program}\n";
            $msg .= "📌 **نوع الطلب:** {$typeLabel}\n";
            $msg .= "📝 **التفاصيل والبيان:** {$details}\n";
            $msg .= "─────────────\n";
            $msg .= "🏢 **رأي الشؤون:** {$affDecision}" . ($req->affairs_notes ? " ({$req->affairs_notes})" : "") . "\n";
            $msg .= "🏛️ **رأي رئيس القسم:** {$hodDecision}" . ($req->hod_notes ? " ({$req->hod_notes})" : "") . "\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ اعتماد وموافقة نهائية', 'callback_data' => "admin_approve_req_{$req->id}"],
                        ['text' => '❌ رفض الطلب', 'callback_data' => "admin_reject_req_{$req->id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $msg, null, $keyboard);
        }
    }

    private function handleAdminApproveStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = $this->adminFindActionableRequest($chatId, $reqId);
        if (!$studentReq) {
            return;
        }

        $studentReq->admin_decision = 'approved';
        $studentReq->admin_notes = 'تمت الموافقة والاعتماد النهائي من قِبل المدير العام عبر بوت تليجرام.';
        $studentReq->status = 'completed';
        $studentReq->save();

        $stUser = $studentReq->student->user ?? null;
        $studentName = $stUser->full_name ?? 'الطالب';

        $isTranscript = $studentReq->type === 'document' ||
                        str_contains($studentReq->details ?? '', 'كشف علامات') ||
                        str_contains($studentReq->details ?? '', 'كشف درجات');

        if ($isTranscript) {
            // إشعار موظفي الشؤون لإصدار النسخة الرقمية
            $affairsUsers = User::where('role_id', 6)->where('status', 'active')->pluck('user_id');
            foreach ($affairsUsers as $affUserId) {
                Notification::create([
                    'user_id'    => $affUserId,
                    'sender_id'  => $user->user_id,
                    'title'      => "موافقة الإدارة على كشف علامات: {$studentName} 🎓",
                    'message'    => "وافقت إدارة المعهد على طلب كشف العلامات (#{$studentReq->id}) للطالب ({$studentName}). يرجى إصدار ومشاركة كشف الدرجات الرقمي المعتمد معه.",
                    'type'       => 'transcript_approved',
                    'category'   => 'administrative',
                    'related_id' => $studentReq->student_id,
                    'is_read'    => false,
                ]);
            }
        }

        if ($stUser) {
            $title = 'الموافقة على طلبك الإداري ✅';
            $message = "صدر القرار النهائي من قِبل إدارة المعهد باعتماد والموافقة على طلبك (#{$studentReq->id}).";

            Notification::create([
                'user_id'    => $stUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $studentReq->id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($stUser->user_id, $title, $message, ['type' => 'student_service']);

            if ($stUser->telegram_chat_id) {
                $this->sendMessage(
                    $stUser->telegram_chat_id,
                    "🎉 **إشعار من الإدارة العليا** 👑\n\nصدر القرار النهائي بـ **الموافقة والاعتماد الرسمي** على طلبك (#{$studentReq->id})."
                );
            }
        }

        \App\Models\UserActivity::log('قرار إداري', "اعتمد المدير العام بالموافقة النهائية طلب الطالب: {$studentName} (#{$studentReq->id})", $user);

        $this->sendMessage($chatId, "✅ **تم اعتماد وموافقة الإدارة على طلب الطالب ({$studentName}) بنجاح!**");
    }

    private function handleAdminRejectStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = $this->adminFindActionableRequest($chatId, $reqId);
        if (!$studentReq) {
            return;
        }

        $studentReq->admin_decision = 'rejected';
        $studentReq->admin_notes = 'تم الرفض النهائي من قِبل المدير العام عبر بوت تليجرام.';
        $studentReq->status = 'completed';
        $studentReq->save();

        $stUser = $studentReq->student->user ?? null;
        $studentName = $stUser->full_name ?? 'الطالب';

        if ($stUser) {
            $title = 'قرار الإدارة بشأن طلبك ❌';
            $message = "صدر القرار النهائي من قِبل إدارة المعهد بعدم الموافقة على طلبك (#{$studentReq->id}).";

            Notification::create([
                'user_id'    => $stUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $studentReq->id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($stUser->user_id, $title, $message, ['type' => 'student_service']);

            if ($stUser->telegram_chat_id) {
                $this->sendMessage(
                    $stUser->telegram_chat_id,
                    "⚠️ **إشعار من الإدارة العليا** 👑\n\nنعتذر، صدر قرار الإدارة بعدم الموافقة على طلبك (#{$studentReq->id})."
                );
            }
        }

        \App\Models\UserActivity::log('قرار إداري', "رفض المدير العام طلب الطالب: {$studentName} (#{$studentReq->id})", $user);

        $this->sendMessage($chatId, "🛑 **تم رفض الطلب وإشعار الطالب بنتيجة القرار.**");
    }

    private function handleAdminUserSearchStart(User $user, $chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'awaiting_admin_user_search', 1800);
        $this->sendMessage(
            $chatId,
            "🔍 **استعلام وإدارة حسابات المستخدمين** 👥\n\nيرجى إرسال **اسم المستخدم / الرقم الجامعي / الاسم الكامل / البريد الإلكتروني / رقم الهاتف** للبحث والتحكم الفوري:"
        );
    }

    private function handleAdminUserSearchInput(User $user, $chatId, string $query)
    {
        $q = trim($query);
        if (empty($q)) {
            $this->sendMessage($chatId, "يرجى كتابة نص للبحث.");
            return;
        }

        $users = User::where(function($b) use ($q) {
                $b->where('full_name', 'LIKE', "%{$q}%")
                  ->orWhere('username', 'LIKE', "%{$q}%")
                  ->orWhere('email', 'LIKE', "%{$q}%")
                  ->orWhere('phone', 'LIKE', "%{$q}%")
                  ->orWhere('university_id', 'LIKE', "%{$q}%");
            })
            ->take(5)
            ->get();

        if ($users->isEmpty()) {
            $this->sendMessage($chatId, "❌ لم يتم العثور على أي مستخدم يطابق: `{$q}`\n\nيمكنك تجربة اسم أو رقم جامعي آخر.");
            return;
        }

        Cache::forget("telegram_state_{$chatId}");

        $this->sendMessage($chatId, "🎯 **نتائج البحث والتحكم في الحسابات ({$users->count()}):**");

        foreach ($users as $u) {
            $roleLabel = match((int)$u->role_id) {
                1 => '👑 مدير عام / إدارة',
                2 => '👨‍🏫 معلم / مدرب',
                3 => '🎓 طالب',
                4 => '👨‍👩‍👧 ولي أمر',
                5 => '🏛️ رئيس قسم',
                6 => '🏢 شؤون طلاب',
                default => 'مستخدم',
            };

            $statusLabel = $u->status === 'active' ? '🟢 نشط ومفعل' : '🔴 موقوف / غير مفعل';
            $tgLinked = $u->telegram_chat_id ? "✅ مربوط (`{$u->telegram_chat_id}`)" : "❌ غير مربوط";

            $card = "👤 **الاسم الكامل:** {$u->full_name}\n";
            $card .= "📌 **الدور والصلاحية:** {$roleLabel}\n";
            $card .= "⚡ **الحالة الحالية:** {$statusLabel}\n";
            $card .= "🆔 **المعرف الجامعي:** `{$u->university_id}` | اسم الدخول: `{$u->username}`\n";
            $card .= "📞 **الهاتف:** `{$u->phone}` | 📧 **الإيميل:** `{$u->email}`\n";
            $card .= "📲 **حساب التيليجرام:** {$tgLinked}\n";

            $toggleBtnText = $u->status === 'active' ? '🔴 إيقاف الحساب' : '🟢 تفعيل الحساب';

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => $toggleBtnText, 'callback_data' => "admin_toggle_status_{$u->user_id}"],
                        ['text' => '🔓 فك ربط الجلسات والجهاز', 'callback_data' => "admin_unlink_user_{$u->user_id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $card, null, $keyboard);
        }
    }

    private function handleAdminToggleStatus(User $user, $chatId, int $targetUserId)
    {
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $this->sendMessage($chatId, "❌ الحساب غير موجود.");
            return;
        }

        if ($targetUser->role_id == 1 && $targetUser->user_id !== $user->user_id) {
            $this->sendMessage($chatId, "⚠️ لا يمكن تعديل حالة حساب مدير عام آخر.");
            return;
        }

        $newStatus = ($targetUser->status === 'active') ? 'inactive' : 'active';
        $targetUser->status = $newStatus;
        $targetUser->updated_at = now();
        $targetUser->save();

        $statusText = ($newStatus === 'active') ? 'تفعيل' : 'إيقاف';
        \App\Models\UserActivity::log('تغيير حالة حساب', "قام المدير العام بـ {$statusText} حساب: {$targetUser->full_name}", $user);

        $this->sendMessage($chatId, "✅ **تم {$statusText} حساب ({$targetUser->full_name}) بنجاح!**");
    }

    private function handleAdminUnlinkUser(User $user, $chatId, int $targetUserId)
    {
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $this->sendMessage($chatId, "❌ الحساب غير موجود.");
            return;
        }

        // فك قفل جهاز الطالب
        if ($targetUser->role_id == 3) {
            DB::table('students')->where('user_id', $targetUserId)->update([
                'device_id'        => null,
                'is_device_locked' => 0,
            ]);
        }

        // إنهاء جميع التوكنات والجلسات النشطة
        DB::table('personal_access_tokens')->where('tokenable_id', $targetUserId)->delete();

        \App\Models\UserActivity::log('فك ربط حساب', "قام المدير العام بفك ربط الجهاز والجلسات لحساب: {$targetUser->full_name}", $user);

        $this->sendMessage($chatId, "🔓 **تم فك ربط الجهاز وإنهاء كافة الجلسات النشطة لحساب ({$targetUser->full_name}) بنجاح!**");
    }

    private function handleAdminLiveActivities(User $user, $chatId)
    {
        $activities = \App\Models\UserActivity::orderByDesc('created_at')
            ->take(10)
            ->get();

        if ($activities->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي نشاطات أمنية مسجلة حالياً.**");
            return;
        }

        $msg = "📋 **سجل العمليات والنشاطات الأمنية اللحظية (آخر 10 أحداث):**\n\n";

        foreach ($activities as $idx => $act) {
            $num = $idx + 1;
            $time = date('m-d h:i A', strtotime($act->created_at));
            $uName = $act->user_name ?? 'مجهول / نظام';
            $role = $act->role_name ?? 'عام';
            $ip = $act->ip_address ? " (`{$act->ip_address}`)" : "";

            $msg .= "{$num}. ⏱️ `{$time}` | 👤 **{$uName}** [{$role}]\n";
            $msg .= "   ⚡ **العملية:** {$act->action}\n";
            $msg .= "   📝 **التفاصيل:** {$act->description}{$ip}\n";
            $msg .= "─────────────\n";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleAdminBroadcastStart(User $user, $chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'awaiting_admin_ann_title', 1800);
        $this->sendMessage($chatId, "📢 **بث وتعميم إداري شامل من المدير العام** 👑\n\nيرجى كتابة **عنوان التعميم أو الإعلان**:");
    }

    private function handleAdminAnnTitleInput($chatId, $text)
    {
        $title = trim($text);
        Cache::put("telegram_admin_ann_title_{$chatId}", $title, 1800);
        Cache::put("telegram_state_{$chatId}", 'awaiting_admin_ann_content', 1800);

        $this->sendMessage($chatId, "📝 **نص وتفاصيل التعميم الإداري**\n\nالعنوان: **{$title}**\n\nالآن يرجى كتابة نص وتفاصيل التعميم بالكامل:");
    }

    private function handleAdminAnnContentInput($chatId, $text)
    {
        $content = trim($text);
        Cache::put("telegram_admin_ann_content_{$chatId}", $content, 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🌐 الجميع بلا استثناء (كافة المستخدمين)', 'callback_data' => 'admin_ann_target_all'],
                ],
                [
                    ['text' => '🎓 الطلاب فقط', 'callback_data' => 'admin_ann_target_students'],
                    ['text' => '👨‍🏫 كادر المعلمين والمدربين', 'callback_data' => 'admin_ann_target_teachers'],
                ],
                [
                    ['text' => '🏛️ الكادر الإداري ورؤساء الأقسام والشؤون', 'callback_data' => 'admin_ann_target_staff'],
                    ['text' => '👨‍👩‍👧 أولياء الأمور', 'callback_data' => 'admin_ann_target_parents'],
                ]
            ]
        ];

        $title = Cache::get("telegram_admin_ann_title_{$chatId}", 'تعميم إداري رسمي');
        $this->sendMessage(
            $chatId,
            "🎯 **تحديد الفئة المستهدفة للتعميم**\n\n📌 **العنوان:** {$title}\n📝 **المحتوى:**\n\"{$content}\"\n\nاختر الجمهور المستهدف لبث ونشر التعميم فورياً:",
            null,
            $keyboard
        );
    }

    private function handleAdminAnnPublish(User $user, $chatId, string $target)
    {
        $title = Cache::get("telegram_admin_ann_title_{$chatId}", 'تعميم من المدير العام');
        $content = Cache::get("telegram_admin_ann_content_{$chatId}", '');

        $announcement = Announcement::create([
            'user_id'         => $user->user_id,
            'title'           => $title,
            'content'         => $content,
            'type'            => 'general',
            'target_audience' => $target,
            'department_id'   => null,
        ]);

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_admin_ann_title_{$chatId}");
        Cache::forget("telegram_admin_ann_content_{$chatId}");

        $roleIds = match($target) {
            'students' => [3],
            'teachers' => [2],
            'staff'    => [5, 6],
            'parents'  => [4],
            default    => [1, 2, 3, 4, 5, 6],
        };

        $recipients = User::whereIn('role_id', $roleIds)
            ->where('status', 'active')
            ->get();

        $notifTitle = "📢 تعميم رسمي من إدارة المعهد العليا";

        foreach ($recipients as $rUser) {
            Notification::create([
                'user_id'    => $rUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $title,
                'type'       => 'announcement',
                'category'   => 'administrative',
                'related_id' => $announcement->announcement_id ?? $announcement->id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($rUser->user_id, $notifTitle, $title, ['type' => 'announcement']);

            if ($rUser->telegram_chat_id) {
                $this->sendMessage(
                    $rUser->telegram_chat_id,
                    "👑 **تعميم رسمي صادر عن إدارة المعهد**\n\n📌 **العنوان:** {$title}\n📝 **التفاصيل:**\n{$content}\n\n👤 **المرسل:** المدير العام {$user->full_name}"
                );
            }
        }

        \App\Models\UserActivity::log('بث تعميم إداري', "نشر المدير العام تعميماً رسمياً بعنوان: {$title} (المستهدفين: {$target})", $user);

        $targetLabel = match($target) {
            'students' => 'كافة الطلاب',
            'teachers' => 'كادر المعلمين والمدربين',
            'staff'    => 'الكادر الإداري ورؤساء الأقسام والشؤون',
            'parents'  => 'أولياء الأمور',
            default    => 'الجميع (كافة مستخدمي المعهد)',
        };

        $this->sendMessage(
            $chatId,
            "✅ **تم نشر وبث التعميم الإداري بنجاح!** 📢\n\n📌 **العنوان:** {$title}\n🎯 **الجمهور المستهدف:** {$targetLabel}\n👥 **عدد المستلمين:** `{$recipients->count()}` مستخدم\n\n📨 تم إرسال إشعارات التطبيق والرسائل الفورية عبر التيليجرام لكافة المستهدفين."
        );
    }
}
