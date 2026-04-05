<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User; // <--- تأكدنا من الاستيراد
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // <--- تأكدنا من الاستيراد
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required',
                'password' => 'required',
            ]);

            // البحث عن اليوزر
            $user = User::where('username', $request->username)->first();

            if (!$user) {
                return response()->json(['message' => 'اسم المستخدم غير موجود'], 404);
            }

            // فحص الباسورد
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'كلمة المرور خاطئة'], 401);
            }

            // إنشاء التوكن
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'تم تسجيل الدخول بنجاح ✅',
                'access_token' => $token,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            // في حال وجود أي خطأ برمج، رح يبعتلك الرسالة هون
            return response()->json([
                'message' => 'حدث خطأ في السيرفر',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|unique:users,username',
                'full_name' => 'required|string',
                'password' => 'required|min:6',
                'role' => 'required',
            ]);

            $user = User::create([
                'username' => $request->username,
                'full_name' => $request->full_name,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => 'active',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'تم إنشاء الحساب بنجاح',
                'access_token' => $token,
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج']);
    }
}