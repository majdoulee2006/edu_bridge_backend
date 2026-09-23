<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يرفض أي طلب يستخدم توكن Sanctum لم يعد هو التوكن "الحالي" للحساب
 * (لأنه صار تسجيل دخول جديد من جهاز آخر ألغى صلاحيته - راجع
 * Api\AuthController::issueToken).
 */
class EnsureSingleApiSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $currentToken = $user->currentAccessToken();

            if ($currentToken && $user->current_token_id && (int) $currentToken->id !== (int) $user->current_token_id) {
                $currentToken->delete();

                return response()->json([
                    'success'    => false,
                    'message'    => 'تم تسجيل الدخول لحسابك من جهاز آخر، الرجاء تسجيل الدخول مجدداً.',
                    'error_code' => 'LOGGED_IN_ELSEWHERE',
                ], 401);
            }
        }

        return $next($request);
    }
}
