<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // دالة تسجيل الدخول
    public function login(Request $request)
    {
        // 1. التأكد من أن المستخدم أدخل إيميل وباسورد
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. محاولة مطابقة البيانات في قاعدة البيانات
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'خطأ في البريد الإلكتروني أو كلمة المرور'
            ], 401);
        }

        // 3. إذا البيانات صحيحة، نجلب بيانات اليوزر وننشئ له "توكن"
        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح ✅',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    // دالة تسجيل الخروج
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }
}
