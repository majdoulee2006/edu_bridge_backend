<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnforceSingleWebSession
{
    /**
     * Handle an incoming request.
     * يضمن أن لكل مستخدم في النظام (أدمن، شؤون، رئيس قسم، أستاذ، طالب، ولي أمر)
     * جلسة نشطة واحدة فقط في نفس الوقت على الويب.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = $request->session()->getId();

            // فحص تطابق الجلسة
            if (!empty($user->active_web_session_id) && $user->active_web_session_id !== $currentSessionId) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'login' => '🔒 تم إنهاء هذه الجلسة تلقائياً نظراً لبدء جلسة جديدة من جهاز آخر أو لانتهاء صلاحيتها.'
                ]);
            }

            // تحديث نبض النشاط الدوري كل 30 ثانية
            $lastActive = $user->web_last_active_at ? \Carbon\Carbon::parse($user->web_last_active_at) : null;
            if (!$lastActive || $lastActive->diffInSeconds(now()) >= 30) {
                $user->update([
                    'active_web_session_id' => $currentSessionId,
                    'web_last_active_at'    => now(),
                    'web_active_device_ip'  => $request->ip(),
                ]);
            }
        }

        return $next($request);
    }
}
