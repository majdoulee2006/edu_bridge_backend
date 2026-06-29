<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckParentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/student/login')->withErrors(['login' => 'يرجى تسجيل الدخول أولاً.']);
        }

        $user = Auth::user();
        
        // التحقق من أن المستخدم مسجل في جدول parents
        $parentExists = DB::table('parents')->where('user_id', $user->user_id)->exists();

        if (!$parentExists) {
            Auth::logout();
            return redirect('/student/login')->withErrors(['login' => 'هذا الحساب ليس حساب ولي أمر.']);
        }

        return $next($request);
    }
}
