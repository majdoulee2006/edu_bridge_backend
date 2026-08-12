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
            return redirect('/login')->withErrors(['login' => 'يرجى تسجيل الدخول كمدير أولاً.']);
        }

        $user = Auth::user();
        if ($user->role_id != 1 && strtolower($user->role ?? '') !== 'admin' && empty($user->is_admin)) {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        return $next($request);
    }
}
