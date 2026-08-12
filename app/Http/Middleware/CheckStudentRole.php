<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Web\UnifiedAuthController;

class CheckStudentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login')->withErrors(['login' => 'يرجى تسجيل الدخول أولاً.']);
        }

        $user = Auth::user();
        if ($user->role_id != 3 && strtolower($user->role ?? '') !== 'student') {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        $student = \App\Models\Student::where('user_id', $user->user_id)->first();
        if (!$student) {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        $request->merge(['student_record' => $student]);

        return $next($request);
    }
}
