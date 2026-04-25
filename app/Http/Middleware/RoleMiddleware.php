<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        if (!$user->role) {
            return response()->json(['success' => false, 'message' => 'لا يوجد دور للمستخدم'], 403);
        }

        $allowedRoles = explode(',', $role);
        $userRole = $user->role->name;

        if (!in_array($userRole, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول لهذه الخدمة'
            ], 403);
        }

        return $next($request);
    }
}
