<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // 🌟 دالة إنشاء حساب جديد (Register)
    public function register(Request $request)
    {
        // 1. التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'full_name'     => 'required|string|max:255',
            'email'         => 'nullable|string|email|max:255|unique:users',
            'password'      => 'required|string|min:6',
            'role'          => 'required|string|in:student,parent',
            'phone'         => 'required|string', // الهاتف ضروري الآن كمعرف
            'university_id' => 'nullable|string', // للطالب فقط
            'department'    => 'nullable|string',
            'branch'        => 'nullable|string',
            'children_ids'  => 'nullable|array',  // للأهل فقط
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 💡 منطق تحديد اسم المستخدم (Username) تلقائياً
        // إذا طالب نستخدم رقمه الجامعي، إذا ولي أمر نستخدم رقم هاتفه
        $generatedUsername = ($request->role === 'student')
                             ? $request->university_id
                             : $request->phone;

        // التحقق إذا كان اسم المستخدم محجوز مسبقاً
        if (User::where('username', $generatedUsername)->exists()) {
            return response()->json(['message' => 'اسم المستخدم أو الرقم الجامعي مسجل مسبقاً'], 422);
        }

        // 2. إنشاء المستخدم
        $user = User::create([
            'full_name'     => $request->full_name,
            'username'      => $generatedUsername, // الحقل الجديد
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
            'phone'         => $request->phone,
            'university_id' => $request->university_id,
            'department'    => $request->department,
            'branch'        => $request->branch,
            'children_ids'  => $request->children_ids,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'تم إنشاء الحساب بنجاح ✅',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'       => $user->user_id,
                'name'     => $user->full_name,
                'username' => $user->username,
                'role'     => $user->role,
            ]
        ], 201);
    }

    // 🌟 دالة تسجيل الدخول باستخدام (Username)
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string', // الطالب يضع رقمه الجامعي، والأب يضع هاتفه
            'password' => 'required',
        ]);

        // نبحث عن المستخدم في قاعدة البيانات بناءً على الـ username
        $user = User::where('username', $request->username)->first();

        // التحقق من وجود المستخدم وصحة كلمة المرور
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة (اسم المستخدم أو كلمة المرور)'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'تم تسجيل الدخول بنجاح ✅',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user' => [
                'id'       => $user->user_id,
                'name'     => $user->full_name,
                'username' => $user->username,
                'role'     => $user->role,
            ]
        ]);
    }

    // دالة تسجيل الخروج
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }
}
