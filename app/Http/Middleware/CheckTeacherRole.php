<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTeacherRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/teacher/login')->withErrors(['login' => 'يرجى تسجيل الدخول أولاً.']);
        }

        // role_id = 3 للمعلم (تحقق من الداتا بيز وعدّل إذا لزم)
        $user = Auth::user();
        $teacher = \App\Models\Teacher::where('user_id', $user->user_id)->first();

        if (!$teacher) {
            Auth::logout();
            return redirect('/teacher/login')->withErrors(['login' => 'هذا الحساب ليس حساب معلم.']);
        }

        // نضع بيانات المعلم في الـ request لاستخدامها لاحقاً
        $request->merge(['teacher_record' => $teacher]);

        return $next($request);
    }
}
