<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;
use App\Services\FcmService;
use Carbon\Carbon;

/**
 * قفل مؤقت لحساب معيّن بعد عدد محاولات دخول فاشلة متتالية - حماية إضافية
 * فوق تحديد معدّل الطلبات بالـ IP (throttle:login بـ RateLimiter)، لأنه
 * مهاجم موزّع على أكثر من IP بيتجاوز حد الـ IP لحاله بسهولة.
 */
class LoginThrottleGuard
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function isLocked(User $user): bool
    {
        return $user->locked_until && Carbon::parse($user->locked_until)->isFuture();
    }

    public static function lockRemainingMinutes(User $user): int
    {
        if (!$user->locked_until) {
            return 0;
        }

        return max(1, (int) ceil(Carbon::now()->diffInSeconds(Carbon::parse($user->locked_until), false) / 60));
    }

    public static function recordFailure(User $user): void
    {
        $attempts = $user->failed_login_attempts + 1;
        $update = ['failed_login_attempts' => $attempts];

        if ($attempts >= self::MAX_ATTEMPTS) {
            $update['locked_until'] = Carbon::now()->addMinutes(self::LOCKOUT_MINUTES);
            $user->forceFill($update)->save();
            self::notifyLockout($user);
            return;
        }

        $user->forceFill($update)->save();
    }

    private static function notifyLockout(User $user): void
    {
        $title = '🔒 تم قفل حسابك مؤقتًا';
        $message = 'حد حاول يخمّن كلمة مرور حسابك ' . self::MAX_ATTEMPTS . ' مرات متتالية وفشل، فتم قفل تسجيل الدخول مؤقتًا لمدة ' . self::LOCKOUT_MINUTES . ' دقيقة كإجراء أمان. إذا مو انت، يرجى تغيير كلمة المرور فور ما ينفتح الحساب.';

        Notification::create([
            'user_id'  => $user->user_id,
            'title'    => $title,
            'message'  => $message,
            'type'     => 'alert',
            'category' => 'administrative',
            'is_read'  => 0,
        ]);

        FcmService::sendToUser($user->user_id, $title, $message, ['type' => 'alert']);
    }

    public static function recordSuccess(User $user): void
    {
        if ($user->failed_login_attempts > 0 || $user->locked_until) {
            $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
        }
    }
}
