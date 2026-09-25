<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;
use App\Services\FcmService;
use Carbon\Carbon;

/**
 * يفرض جلسة واحدة نشطة فقط لكل قناة (ويب أو موبايل) لكل حساب. الويب عنده
 * نافذة سماح 20 دقيقة خمول (الجلسة القديمة تعتبر منتهية بعدها وبيسمح
 * بدخول جديد)، الموبايل بدون نافذة سماح - لازم تسجيل خروج صريح أو device
 * reset. ويب وموبايل مستقلين عن بعض تماماً - يوزر واحد فيه يكون عندو
 * جلسة ويب + جلسة موبايل شغالتين بنفس الوقت بشكل طبيعي.
 */
class SingleSessionGuard
{
    private const WEB_STALE_MINUTES = 20;

    public static function isWebOccupied(User $user, string $currentSessionId): bool
    {
        if (empty($user->current_session_id) || $user->current_session_id === $currentSessionId) {
            return false;
        }

        return self::isRecentlyActive($user);
    }

    public static function isApiOccupied(User $user): bool
    {
        return !empty($user->current_token_id);
    }

    /**
     * يسجّل جلسة الويب الحالية كـ"الجلسة الوحيدة الصالحة" لهذا الحساب.
     * لازم تُستدعى بعد آخر استدعاء لـ session()->regenerate() بالـ request
     * (راجع ملاحظة AppServiceProvider::boot() لسبب عدم فعل هذا عبر حدث Login).
     */
    public static function stampWebSession(User $user, string $sessionId): void
    {
        $user->forceFill([
            'current_session_id'     => $sessionId,
            'session_last_active_at' => now(),
        ])->save();
    }

    private static function isRecentlyActive(User $user): bool
    {
        if (!$user->session_last_active_at) {
            return false;
        }

        return Carbon::parse($user->session_last_active_at)->diffInMinutes(now()) < self::WEB_STALE_MINUTES;
    }

    /**
     * ينبّه صاحب الحساب (بكل القنوات المتاحة له) إنه في محاولة تسجيل دخول
     * جديدة لحسابه تم رفضها أو تتطلب تحقق إضافي.
     */
    public static function notifyIntrusionAttempt(User $user, string $channel): void
    {
        $title = '⚠️ محاولة تسجيل دخول لحسابك';
        $message = $channel === 'web'
            ? 'حاول حد يسجل دخول لحسابك من متصفح أو جهاز آخر بينما جلستك الحالية ما زالت نشطة. إذا مو انت، يرجى تغيير كلمة المرور فوراً.'
            : 'حاول حد يسجل دخول لحسابك من جهاز موبايل آخر بينما جلستك الحالية ما زالت نشطة. إذا مو انت، يرجى تغيير كلمة المرور فوراً.';

        Notification::create([
            'user_id'  => $user->user_id,
            'title'    => $title,
            'message'  => $message,
            'type'     => 'alert',
            // category عمود enum('academic','administrative','chat') فقط - ما في قيمة
            // مخصصة للتنبيهات الأمنية، فـ 'administrative' أقرب قيمة متاحة دلالياً.
            'category' => 'administrative',
            'is_read'  => 0,
        ]);

        FcmService::sendToUser($user->user_id, $title, $message, ['type' => 'alert']);
    }
}
