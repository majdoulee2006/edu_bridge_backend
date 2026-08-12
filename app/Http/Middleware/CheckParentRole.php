<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Web\UnifiedAuthController;

class CheckParentRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login')->withErrors(['login' => 'يرجى تسجيل الدخول أولاً.']);
        }

        $user = Auth::user();
        if ($user->role_id != 4 && strtolower($user->role ?? '') !== 'parent') {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }
        
        $parentExists = DB::table('parents')->where('user_id', $user->user_id)->exists();
        if (!$parentExists) {
            return (new UnifiedAuthController)->redirectUserByRole($user);
        }

        return $next($request);
    }
}
