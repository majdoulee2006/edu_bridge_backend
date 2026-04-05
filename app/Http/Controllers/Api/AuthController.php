<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp; // تأكدي من وجود هذا الموديل
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $role = $request->role;

        // 1. التحقق من البيانات (Validation)
        $rules = [
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users', // الإيميل مطلوب للكل الآن
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

        // 2. إنشاء المستخدم
        $userData = [
            'full_name' => $request->full_name,
            'username'  => $role == 'student' ? $request->university_id : $request->phone,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'phone'     => $request->phone,
            'role'      => $role,
            'status'    => 'inactive', // غير نشط حتى يدخل الـ OTP
        ];

        if ($role == 'student') {
            $userData += [
                'university_id' => $request->university_id,
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

        // 3. منطق الـ OTP
        $otpCode = rand(1000, 9999);

        // تخزين الرمز في جدول الـ otps
        DB::table('otps')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $otpCode,
                'expires_at' => now()->addMinutes(15),
                'created_at' => now()
            ]
        );

        // 4. إرسال الإيميل
        try {
            Mail::to($user->email)->send(new OtpMail($otpCode));
        } catch (\Exception $e) {
            // سجل الخطأ إذا فشل الإرسال لكن استمر في العملية
        }

        return response()->json([
            'message' => 'تم إنشاء الحساب بنجاح، يرجى التحقق من بريدك الإلكتروني',
            'email'   => $user->email,
            // 'otp_debug' => $otpCode // اختياري: أظهري الرمز هنا لو حابة تتأكدي بالفرونت بدون فتح الإيميل
        ], 201);
    }

    // ... باقي الدوال (login, logout)
}
