<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAffairsRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return redirect('/affairs/login')->withErrors(['login' => 'يرجى تسجيل الدخول أولاً.']);
        }

        if (\Illuminate\Support\Facades\Auth::user()->role_id !== 6) {
            \Illuminate\Support\Facades\Auth::logout();
            return redirect('/affairs/login')->withErrors(['login' => 'ليس لديك صلاحية الوصول لهذه الصفحة.']);
        }

        return $next($request);
    }
}
