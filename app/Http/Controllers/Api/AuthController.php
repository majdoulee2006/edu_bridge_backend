<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\Parents;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // ──────────────────────────────────────────────
    // LOGIN
    // ──────────────────────────────────────────────
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'   => 'required|string',
            'password'   => 'required|string',
            'is_student' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $input      = $request->username;
        $isStudent  = filter_var($request->input('is_student', false), FILTER_VALIDATE_BOOLEAN);
        $digitsOnly = preg_replace('/[^0-9]/', '', $input);

        if ($isStudent) {
            // وضع الطالب: البحث عبر الرقم الجامعي فقط
            $user = User::where('university_id', $input)->where('role_id', 3)->first();
        } else {
            // الوضع العادي: بحث عبر الهاتف أو الإيميل أو username — مع استثناء الطلاب
            $user = User::where('role_id', '!=', 3)
                ->where(function ($q) use ($input, $digitsOnly) {
                    $q->where('username', $input)
                      ->orWhere('email', $input)
                      ->orWhere('phone', $input)
                      ->orWhere('phone', '+' . $digitsOnly)
                      ->orWhereRaw("REPLACE(REPLACE(phone, '+', ''), ' ', '') = ?", [$digitsOnly])
                      ->when(strlen($digitsOnly) >= 7, function ($q2) use ($digitsOnly) {
                          $q2->orWhereRaw("REPLACE(REPLACE(phone, '+', ''), ' ', '') LIKE ?", ['%' . $digitsOnly]);
                      });
                })
                ->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'], 401);
        }

        // منع الطالب من الدخول بدون تفعيل زر "طالب"
        if (!$isStudent && $user->role_id === 3) {
            return response()->json(['success' => false, 'message' => 'يرجى تفعيل خيار "طالب" لتسجيل الدخول برقمك الجامعي'], 403);
        }

        if ($user->status === 'inactive') {
            return response()->json(['success' => false, 'message' => 'الحساب غير مفعّل. يرجى التحقق من بريدك الإلكتروني'], 403);
        }

        $user->update(['last_login' => now()]);

        // ── Single-Device Login (طالب + ولي أمر) ──────────────────
        if (in_array($user->role_id, [3, 4])) {
            $deviceToken = $request->input('device_token');

            if ($deviceToken && $user->device_token && $user->device_token !== $deviceToken) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'هذا الحساب مسجّل دخول بالفعل من جهاز آخر. يُسمح بجهاز واحد فقط.',
                    'error_code' => 'DEVICE_CONFLICT',
                ], 409);
            }

            $user->tokens()->delete();
            if ($deviceToken) {
                $user->update(['device_token' => $deviceToken]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $parentId = null;
        if ($user->role_id == 4) {
            $parent   = DB::table('parents')->where('user_id', $user->user_id)->first();
            $parentId = $parent?->parent_id;
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'user'    => [
                'id'        => $user->user_id,
                'name'      => $user->full_name,
                'username'  => $user->username,
                'email'     => $user->email,
                'role'      => $user->role ?? 'student',
                'role_id'   => $user->role_id,
                'parent_id' => $parentId,
            ],
        ], 200);
    }

    // ──────────────────────────────────────────────
    // REGISTER  (يخزّن الحساب بحالة inactive ويرسل OTP)
    // ──────────────────────────────────────────────
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'phone'         => 'nullable|string|max:20',
            'password'      => 'required|string|min:6',
            'role'          => 'required|in:student,parent',
            // حقول الطالب
            'university_id' => 'required_if:role,student|string|unique:users,university_id',
            'gender'        => 'nullable|in:ذكر,أنثى',
            'birth_date'    => 'nullable|date',
            'academic_year' => 'nullable|string',
            'department'    => 'nullable|string',
            'branch'        => 'nullable|string',
            // حقول ولي الأمر
            'children_ids'  => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $role = Role::where('name', $request->role)->first();
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'الدور غير موجود'], 400);
        }

        // توليد username فريد من الإيميل
        $base     = strtolower(explode('@', $request->email)[0]);
        $username = $base;
        $i        = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        $user = User::create([
            'full_name'     => $request->full_name,
            'username'      => $username,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'password'      => Hash::make($request->password),
            'role_id'       => $role->role_id,
            'status'        => 'inactive',   // يصبح active بعد التحقق
            'university_id' => $request->university_id,
            'gender'        => $request->gender,
            'birth_date'    => $request->birth_date,
            'academic_year' => $request->academic_year,
            'department'    => $request->department,
            'branch'        => $request->branch,
            'children_ids'  => $request->children_ids,
        ]);

        if ($request->role === 'student') {
            $student = Student::create([
                'user_id'      => $user->user_id,
                'student_code' => $request->university_id,
                'level'        => $request->academic_year ?? 'السنة الأولى',
                'birth_date'   => $request->birth_date,
            ]);

            // Auto-enroll: سجّل الطالب بكل مواد برنامجه بناءً على القسم
            $department = $request->department;
            if ($department) {
                $program = \DB::table('programs')
                    ->where('name', 'LIKE', '%' . $department . '%')
                    ->first();
                if ($program) {
                    $courseIds = \DB::table('course_program')
                        ->where('program_id', $program->id)
                        ->pluck('course_id');
                    foreach ($courseIds as $courseId) {
                        \DB::table('enrollments')->insertOrIgnore([
                            'student_id'      => $student->student_id,
                            'course_id'       => $courseId,
                            'status'          => 'active',
                            'enrollment_date' => now(),
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }
            }
        } elseif ($request->role === 'parent') {
            Parents::create(['user_id' => $user->user_id]);
        }

        // في التطوير نستخدم OTP ثابت للتجربة السريعة
        $otp = app()->environment('local') ? '123456' : str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('email', $request->email)->delete(); // حذف أي OTP قديم

        OtpCode::create([
            'email'      => $request->email,
            'code'       => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);

        // إرسال الإيميل
        try {
            Mail::to($request->email)->send(new OtpMail($otp, $request->full_name));
        } catch (\Exception $e) {
            Log::error('OTP Mail Error: ' . $e->getMessage());
        }

        $response = [
            'success' => true,
            'message' => 'تم إنشاء الحساب. تحقق من بريدك الإلكتروني للحصول على رمز التحقق.',
            'email'   => $request->email,
        ];

        // في بيئة التطوير نرجّع الـ OTP مباشرة للتجربة
        if (app()->environment('local')) {
            $response['otp_dev'] = $otp;
        }

        return response()->json($response, 201);
    }

    // ──────────────────────────────────────────────
    // VERIFY OTP
    // ──────────────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $record = OtpCode::where('email', $request->email)
            ->where('code', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'الرمز غير صحيح أو منتهي الصلاحية',
            ], 400);
        }

        // تفعيل الحساب
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }

        $user->update(['status' => 'active']);
        $record->update(['used' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق بنجاح! يمكنك الآن تسجيل الدخول.',
        ], 200);
    }

    // ──────────────────────────────────────────────
    // RESEND OTP
    // ──────────────────────────────────────────────
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->status === 'active') {
            return response()->json(['success' => false, 'message' => 'الحساب مفعّل بالفعل'], 400);
        }

        $otp = app()->environment('local') ? '123456' : str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('email', $request->email)->delete();

        OtpCode::create([
            'email'      => $request->email,
            'code'       => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            Mail::to($request->email)->send(new OtpMail($otp, $user->full_name));
        } catch (\Exception $e) {
            Log::error('Resend OTP Mail Error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة إرسال رمز التحقق',
        ], 200);
    }

    // ──────────────────────────────────────────────
    // LOGOUT
    // ──────────────────────────────────────────────
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        // مسح device_token عند تسجيل الخروج
        if (in_array($user->role_id, [3, 4])) {
            $user->update(['device_token' => null]);
        }

        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح'], 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load(['role', 'student', 'teacher', 'parent']);

        return response()->json(['success' => true, 'data' => $user], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name'        => 'sometimes|string|max:255',
            'email'            => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
            'phone'            => 'sometimes|string|max:20',
            'current_password' => 'sometimes|string',
            'password'         => 'sometimes|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // التحقق من كلمة المرور الحالية إذا أُرسلت مع كلمة مرور جديدة
        if ($request->filled('password')) {
            if ($request->filled('current_password') && !Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة',
                ], 400);
            }
            $user->update(['password' => Hash::make($request->password)]);
        }

        $fields = $request->only(['full_name', 'email', 'phone']);

        // لما يتغير رقم الهاتف يتغير الـ username معه تلقائياً
        if ($request->filled('phone')) {
            $fields['username'] = $request->phone;
        }

        $user->update($fields);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data'    => $user,
        ], 200);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg|max:5120']);

        $user = $request->user();

        if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
            \Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الصورة الشخصية',
            'avatar'  => storageUrl($path),
        ]);
    }

    // ──────────────────────────────────────────────
    // REQUEST CHANGE EMAIL (يرسل OTP للإيميل الجديد)
    // ──────────────────────────────────────────────
    public function requestChangeEmail(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا البريد الإلكتروني مستخدم بالفعل',
            ], 422);
        }

        $otp = app()->environment('local') ? '123456' : str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('email', $request->email)->delete();
        OtpCode::create([
            'email'      => $request->email,
            'code'       => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            Mail::to($request->email)->send(new OtpMail($otp, $user->full_name));
        } catch (\Exception $e) {
            Log::error('Change Email OTP Error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق إلى البريد الجديد',
        ], 200);
    }

    // ──────────────────────────────────────────────
    // CONFIRM CHANGE EMAIL (يتحقق من OTP ويحدّث الإيميل)
    // ──────────────────────────────────────────────
    public function confirmChangeEmail(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'otp'   => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $record = OtpCode::where('email', $request->email)
            ->where('code', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'الرمز غير صحيح أو منتهي الصلاحية',
            ], 400);
        }

        $user->update(['email' => $request->email]);
        $record->update(['used' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البريد الإلكتروني بنجاح',
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني غير مسجّل لدينا',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        $otp  = app()->environment('local') ? '123456' : str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('email', $request->email)->delete();
        OtpCode::create([
            'email'      => $request->email,
            'code'       => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            Mail::to($request->email)->send(new OtpMail($otp, $user->full_name));
        } catch (\Exception $e) {
            Log::error('Forgot Password Mail Error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $record = OtpCode::where('email', $request->email)
            ->where('code', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'الرمز غير صحيح أو منتهي الصلاحية',
            ], 400);
        }

        User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        $record->update(['used' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح! يمكنك تسجيل الدخول الآن.',
        ], 200);
    }
}
