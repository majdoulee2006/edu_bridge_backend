<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * شبكة أمان: تطرد جلسة الويب لو صار تسجيل دخول جديد ناجح لنفس الحساب من
 * مكان تاني (بيصير هذا بعد ما تفوت الجلسة القديمة نافذة الخمول 20 دقيقة -
 * راجع SingleSessionGuard::isOccupied لمنطق الرفض وقت تسجيل الدخول نفسه).
 * كمان بتحدّث "نبض" النشاط الدوري (session_last_active_at) المستخدم
 * لحساب نافذة الـ20 دقيقة هاي، كل 30 ثانية بدل كل request.
 */
class EnsureSingleWebSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = $request->session()->getId();

            if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'تم تسجيل الدخول لحسابك من مكان آخر، تم إنهاء هذه الجلسة.');
            }

            if (!$user->session_last_active_at || \Carbon\Carbon::parse($user->session_last_active_at)->diffInSeconds(now()) >= 30) {
                $user->forceFill(['session_last_active_at' => now()])->save();
            }
        }

        return $next($request);
    }
}
