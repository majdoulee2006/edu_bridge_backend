<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Web\UnifiedAuthController;

class CheckTeacherRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login')->withErrors(['login' => 'يرجى تسجيل الدخول أولاً.']);
        }

        $user = Auth::user();
        if ($user->role_id != 2 && strtolower($user->role ?? '') !== 'teacher') {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        $teacher = \App\Models\Teacher::where('user_id', $user->user_id)->first();
        if (!$teacher) {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        $request->merge(['teacher_record' => $teacher]);

        return $next($request);
    }
}
