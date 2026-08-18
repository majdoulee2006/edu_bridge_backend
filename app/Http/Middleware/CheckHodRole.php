<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\UnifiedAuthController;

class CheckHodRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login')->withErrors(['login' => 'يرجى تسجيل الدخول كرئيس قسم أولاً.']);
        }

        $user = Auth::user();
        if ($user->role_id != 5 && strtolower($user->role ?? '') !== 'head' && strtolower($user->role ?? '') !== 'hod') {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        return $next($request);
    }
}
