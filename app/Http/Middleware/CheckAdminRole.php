<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\UnifiedAuthController;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->withErrors(['login' => 'يرجى تسجيل الدخول كمدير أولاً.']);
        }

        $user = Auth::user();
        if ($user->role_id != 1 && strtolower($user->role ?? '') !== 'admin' && empty($user->is_admin)) {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        // التحقق من الجلسة النشطة الوحيدة للأدمن
        $currentSessionId = $request->session()->getId();
        if (!empty($user->active_web_session_id) && $user->active_web_session_id !== $currentSessionId) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')->withErrors([
                'login' => '🔒 تم إنهاء هذه الجلسة تلقائياً لعدم تطابق الجلسة النشطة المعتمدة.'
            ]);
        }

        // تحديث توقيت آخر نشاط للجلسة الحالية كل 30 ثانية
        if (!$user->web_last_active_at || \Carbon\Carbon::parse($user->web_last_active_at)->diffInSeconds(now()) >= 30) {
            $user->update([
                'active_web_session_id' => $currentSessionId,
                'web_last_active_at'    => now(),
                'web_active_device_ip'  => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
