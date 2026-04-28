<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Parents;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('username', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'الحساب غير نشط'], 403);
        }

        $user->update(['last_login' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'user' => [
                'id' => $user->user_id,
                'name' => $user->full_name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->name ?? 'unknown',
                'role_id' => $user->role_id,
            ]
        ], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:student,parent',
            'student_code' => 'required_if:role,student|string|unique:students,student_code',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $role = Role::where('name', $request->role)->first();
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'الدور غير موجود'], 400);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $role->role_id,
            'status' => 'active',
        ]);

        if ($request->role == 'student') {
            Student::create([
                'user_id' => $user->user_id,
                'student_code' => $request->student_code,
                'level' => $request->input('level', 'السنة الأولى'),
            ]);
        } elseif ($request->role == 'parent') {
            Parents::create(['user_id' => $user->user_id]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'token' => $token,
            'user' => $user->load('role'),
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ], 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load(['role', 'student', 'teacher', 'parent']);

        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user->update($request->only(['full_name', 'email', 'phone']));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => $user
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        return response()->json(['success' => false, 'message' => 'قيد التطوير'], 501);
    }

    public function resetPassword(Request $request)
    {
        return response()->json(['success' => false, 'message' => 'قيد التطوير'], 501);
    }

    public function verifyOtp(Request $request)
    {
        return response()->json(['success' => false, 'message' => 'قيد التطوير'], 501);
    }
}