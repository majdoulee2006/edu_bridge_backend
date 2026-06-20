<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckStudentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/student/login')->withErrors(['login' => 'يرجى تسجيل الدخول أولاً.']);
        }

        $user = Auth::user();
        $student = \App\Models\Student::where('user_id', $user->user_id)->first();

        if (!$student) {
            Auth::logout();
            return redirect('/student/login')->withErrors(['login' => 'هذا الحساب ليس حساب طالب.']);
        }

        $request->merge(['student_record' => $student]);

        return $next($request);
    }
}
