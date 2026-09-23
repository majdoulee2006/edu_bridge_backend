<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * يطرد المستخدم إذا صار تسجيل دخول جديد لنفس الحساب من متصفح/جهاز آخر،
 * عن طريق مقارنة معرّف الجلسة الحالية بآخر معرّف جلسة محفوظ على الحساب
 * (يُحدَّث بحدث Login - راجع AppServiceProvider).
 */
class EnsureSingleWebSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->current_session_id && $user->current_session_id !== $request->session()->getId()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'تم تسجيل الدخول لحسابك من مكان آخر، تم إنهاء هذه الجلسة.');
            }
        }

        return $next($request);
    }
}
