<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Web\UnifiedAuthController;

class CheckAffairsRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return redirect('/login')->withErrors(['login' => 'يرجى تسجيل الدخول أولاً.']);
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->role_id !== 6 && strtolower($user->role ?? '') !== 'affairs') {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        return $next($request);
    }
}
