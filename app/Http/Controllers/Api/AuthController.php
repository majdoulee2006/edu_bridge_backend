<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // --- دالة تسجيل حساب جديد ---
    public function register(Request $request)
    {
        try {
            $role = $request->role;

            $rules = [
                'full_name' => 'required|string|max:255',
                'email'     => 'required|email|unique:users',
                'password'  => 'required|min:6',
                'role'      => 'required|in:student,parent',
            ];

            if ($role == 'student') {
                $rules += [
                    'university_id' => 'required|unique:users',
                    'department'    => 'required',
                    'branch'        => 'required',
                ];
            } else {
                $rules += [
                    'phone'        => 'required|unique:users',
                    'children_ids' => 'required|array',
                ];
            }

            $request->validate($rules);

            $userData = [
                'full_name' => $request->full_name,
                'username'  => $role == 'student' ? (string)$request->university_id : (string)$request->phone,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'phone'     => $request->phone,
                'role'      => $role,
                'status'    => 'inactive', // يفضل البقاء غير نشط حتى تفعيل الـ OTP
            ];

            if ($role == 'student') {
                $userData += [
                    'university_id' => (string)$request->university_id,
                    'gender'        => $request->gender,
                    'birth_date'    => $request->birth_date,
                    'academic_year' => $request->academic_year,
                    'department'    => $request->department,
                    'branch'        => $request->branch,
                ];
            } else {
                $userData['children_ids'] = json_encode($request->children_ids);
            }

            $user = User::create($userData);

            // منطق الـ OTP
            $otpCode = (string)rand(1000, 9999);
            DB::table('otps')->updateOrInsert(
                ['email' => $user->email],
                ['token' => $otpCode, 'expires_at' => now()->addMinutes(15), 'created_at' => now()]
            );

            try {
                Mail::to($user->email)->send(new OtpMail($otpCode));
            } catch (\Exception $e) { }

            return response()->json([
                'message' => 'تم إنشاء الحساب، يرجى التحقق من بريدك',
                'email'   => $user->email,
                'otp_debug' => $otpCode // مفيد جداً للمناقشة لو تعطل الإيميل
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'خطأ في السيرفر: ' . $e->getMessage()], 500);
        }
    }

    // --- دالة التحقق من الرمز ---
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required',
        ]);

        $otpData = DB::table('otps')
            ->where('email', $request->email)
            ->where('token', (string)$request->otp)
            ->first();

        if (!$otpData || now()->gt($otpData->expires_at)) {
            return response()->json(['message' => 'الرمز غير صحيح أو انتهى'], 422);
        }

        User::where('email', $request->email)->update(['status' => 'active']);
        DB::table('otps')->where('email', $request->email)->delete();

        return response()->json(['message' => 'تم التفعيل بنجاح'], 200);
    }

    // --- 🚀 دالة تسجيل الدخول (الحل الجذري) ---
 // --- 🚀 دالة تسجيل الدخول (الحل الجذري والنهائي) ---
    public function login(Request $request)
    {
        try {
            $request->validate([
                'login' => 'required', 
                'password' => 'required',
            ]);

            $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            if (!Auth::attempt([$loginType => $request->login, 'password' => $request->password])) {
                return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            // --- ✨ الإضافة السحرية هنا ✨ ---
            $parentId = null;
            if ($user->role == 'parent') {
                // نبحث عن السجل المرتبط بهذا المستخدم في جدول الأباء
                $parentRecord = \DB::table('parents')->where('user_id', $user->user_id)->first();
                if ($parentRecord) {
                    $parentId = $parentRecord->parent_id;
                }
            }
            // ------------------------------

            return response()->json([
                'access_token' => (string)$token,
                'token_type'   => 'Bearer',
                'user' => [
                    'user_id'   => (string)$user->user_id,
                    'full_name' => (string)$user->full_name,
                    'role'      => (string)$user->role,
                    'username'  => (string)$user->username,
                    'parent_id' => $parentId, // ✅ سيرسل الآن الرقم 1 بدلاً من null
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
        }
    }
    // --- دالة تسجيل الخروج ---
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج']);
    }
}
