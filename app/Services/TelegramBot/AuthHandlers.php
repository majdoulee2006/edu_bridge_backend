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
 * Telegram bot: account linking (login / logout) and callback role guard
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
trait AuthHandlers
{
    // ==========================================
    // Auth Handlers
    // ==========================================

    private function handleLogout($user, $chatId, $stateKey)
    {
        if ($user) {
            $user->telegram_chat_id = null;
            $user->save();
        }
        Cache::forget($stateKey);
        Cache::forget("telegram_auth_{$chatId}_user_id");
        
        $this->sendMessage($chatId, "تم تسجيل الخروج بنجاح 👋\nللبدء من جديد وتسجيل الدخول بحساب آخر، اضغط على /start", ['remove_keyboard' => true]);
    }

    private function handleUniversityIdInput($chatId, $text)
    {
        // 🔓 نفس منطق تحديد الهوية المستخدم بتسجيل الدخول الرئيسي بالتطبيق
        // (AuthController@login) بالظبط، بدل الاقتصار على الرقم الجامعي بس
        // — هيك ولي الأمر والمعلم ورئيس القسم والإدارة (يلي ما إلهم رقم
        // جامعي) فيهم يربطوا حسابهم كمان عبر اسم المستخدم أو الإيميل أو الهاتف.
        $input = trim($text);
        $digitsOnly = preg_replace('/[^0-9]/', '', $input);

        $user = User::where(function ($q) use ($input, $digitsOnly) {
                $q->where('username', $input)
                  ->orWhere('email', $input)
                  ->orWhere('phone', $input)
                  ->orWhere('university_id', $input)
                  ->orWhereHas('student', function ($sq) use ($input) {
                      $sq->where('student_code', $input);
                  });
                if (!empty($digitsOnly)) {
                    $q->orWhere('university_id', $digitsOnly)
                      ->orWhere('phone', '+' . $digitsOnly)
                      ->orWhereRaw("REPLACE(REPLACE(phone, '+', ''), ' ', '') = ?", [$digitsOnly]);
                }
            })
            ->first();

        if (!$user) {
            $this->sendMessage($chatId, "❌ لم يتم العثور على حساب بهذه البيانات. تأكد من إدخال اسم المستخدم أو الإيميل أو رقم الهاتف أو الرقم الجامعي بشكل صحيح.");
            return;
        }

        Cache::put("telegram_auth_{$chatId}_user_id", $user->user_id, 3600);
        Cache::put("telegram_state_{$chatId}", 'awaiting_password', 3600);
        
        $this->sendMessage($chatId, "ممتاز! تم العثور على الحساب باسم ({$user->full_name}).\nالآن، يرجى إدخال **كلمة المرور** الخاصة بحسابك:");
    }

    private function handlePasswordInput($chatId, $text)
    {
        $userId = Cache::get("telegram_auth_{$chatId}_user_id");
        if (!$userId) {
            $this->sendMessage($chatId, "انتهت مهلة التسجيل. يرجى البدء من جديد عبر /start.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $user = User::find($userId);
        if (!$user) return;

        // نفس قفل المحاولات المستخدم بتسجيل الدخول على الويب (5 محاولات فاشلة = قفل 15 دقيقة)،
        // وإلا يمكن تخمين كلمة السر عبر البوت بلا حد.
        if (LoginThrottleGuard::isLocked($user)) {
            $this->sendLoginLockedMessage($chatId, $user);
            return;
        }

        if (Hash::check($text, $user->password)) {
            LoginThrottleGuard::recordSuccess($user);

            // Success! Link telegram_chat_id
            $user->telegram_chat_id = $chatId;
            $user->save();

            Cache::forget("telegram_state_{$chatId}");
            Cache::forget("telegram_auth_{$chatId}_user_id");

            // Delete the password message for security if possible (optional)
            // Telegram API supports deleteMessage but we need message_id

            if ($user->role === 'student') {
                $this->sendStudentMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك **{$user->full_name}** 🎓");
            } elseif ($user->role === 'parent') {
                $this->sendParentMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك **{$user->full_name}** 👨‍👩‍👧‍👦");
            } elseif ($user->role === 'teacher') {
                $this->sendTeacherMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك الأستاذ/ة **{$user->full_name}** 👨‍🏫");
            } elseif ($user->role === 'head' || $user->role_id == 5) {
                $dept = $user->department ?? 'الأكاديمي';
                $this->sendHodMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك الأستاذ/ة **{$user->full_name}** 🏛️\nرئيس قسم ({$dept})");
            } elseif ($user->role === 'affairs' || $user->role_id == 6) {
                $this->sendAffairsMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك الأستاذ/ة **{$user->full_name}** 🏢\nإدارة شؤون الطلاب");
            } elseif ($user->role === 'admin' || $user->role_id == 1) {
                $this->sendAdminMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك سعادة المدير العام **{$user->full_name}** 👑\nإدارة منظومة Edu Bridge");
            } else {
                $this->sendMessage($chatId, "✅ **تم ربط حسابك بنجاح!**\nمرحباً بك **{$user->full_name}** 🎓\n\nمن الآن، أي رمز تحقق (OTP) — لتغيير كلمة السر أو البريد أو رقم الهاتف — رح يوصلك مباشرة هون على هالمحادثة.");
            }
        } else {
            LoginThrottleGuard::recordFailure($user);
            $user->refresh();

            if (LoginThrottleGuard::isLocked($user)) {
                $this->sendLoginLockedMessage($chatId, $user);
                return;
            }

            $this->sendMessage($chatId, "❌ كلمة المرور غير صحيحة. يرجى المحاولة مرة أخرى:");
        }
    }

    /** الدور المطلوب لزر معيّن حسب بادئته، أو null للأزرار العامة (الطالب/التسجيل...). */
    private function requiredRoleForCallback(string $data): ?string
    {
        foreach (['admin_' => 'admin', 'affairs_' => 'affairs', 'hod_' => 'head', 'teacher_' => 'teacher', 'parent_' => 'parent'] as $prefix => $role) {
            if (str_starts_with($data, $prefix)) {
                return $role;
            }
        }

        return null;
    }

    private function userHasBotRole(User $user, string $role): bool
    {
        $ids = ['admin' => 1, 'teacher' => 2, 'student' => 3, 'parent' => 4, 'head' => 5, 'affairs' => 6];

        return (int) $user->role_id === ($ids[$role] ?? -1);
    }

    private function sendLoginLockedMessage($chatId, User $user): void
    {
        $minutes = LoginThrottleGuard::lockRemainingMinutes($user);
        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_auth_{$chatId}_user_id");

        $this->sendMessage(
            $chatId,
            "🔒 تم قفل الحساب مؤقتاً بسبب محاولات دخول فاشلة متكررة. حاول مرة أخرى بعد {$minutes} دقيقة."
        );
    }
}
