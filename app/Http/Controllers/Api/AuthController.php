<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Services\TelegramService;
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
            'device_id'  => 'nullable|string|max:255',
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

        // ── ربط الجهاز بحساب الطالب عند أول تسجيل دخول ──────────────────
        if ($user->role_id === 3 && $request->filled('device_id')) {
            $student = \App\Models\Student::where('user_id', $user->user_id)->first();
            if ($student && empty($student->device_id)) {
                $student->update([
                    'device_id'        => $request->device_id,
                    'is_device_locked' => 1,
                ]);
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
                'id'               => $user->user_id,
                'name'             => $user->full_name,
                'username'         => $user->username,
                'email'            => $user->email,
                'role'             => $user->role ?? 'student',
                'role_id'          => $user->role_id,
                'parent_id'        => $parentId,
                'telegram_chat_id' => $user->telegram_chat_id,
            ],
        ], 200);
    }

    // ──────────────────────────────────────────────
    // REGISTER  (يخزّن الحساب بحالة inactive ويرسل OTP)
    // ──────────────────────────────────────────────
    public function register(Request $request)
    {
        // حذف الحساب القديم غير المفعّل قبل التحقق من التفرد
        $existingInactive = User::where('email', $request->email)->where('status', 'inactive')->first();
        if ($existingInactive) {
            OtpCode::where('email', $request->email)->delete();
            Student::where('user_id', $existingInactive->user_id)->delete();
            Parents::where('user_id', $existingInactive->user_id)->delete();
            $existingInactive->delete();
        }

        $existingInactiveByUid = $request->university_id
            ? User::where('university_id', $request->university_id)->where('status', 'inactive')->first()
            : null;
        if ($existingInactiveByUid && (!$existingInactive || $existingInactiveByUid->user_id !== $existingInactive->user_id)) {
            Student::where('user_id', $existingInactiveByUid->user_id)->delete();
            $existingInactiveByUid->delete();
        }

        $validator = Validator::make($request->all(), [
            'full_name'        => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'nullable|string|max:20',
            'telegram_username'=> 'nullable|string|max:100',
            'password'         => 'required|string|min:6',
            'role'             => 'required|in:student,parent',
            'university_id'    => 'required_if:role,student|string|unique:users,university_id',
            'child_university_id' => 'required_if:role,parent|string',
            'gender'           => 'nullable|in:ذكر,أنثى',
            'birth_date'       => 'nullable|date',
            'academic_year'    => 'nullable|string',
            'department'       => 'nullable|string',
            'branch'           => 'nullable|string',
            'children_ids'     => 'nullable|array',
            'fcm_token'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        // تحقق من الرقم الجامعي للطالب
        if ($request->role === 'student') {
            $uid = \DB::table('university_ids')
                ->where('university_id', $request->university_id)
                ->where('role', 'student')
                ->first();
            if (!$uid) {
                return response()->json([
                    'success' => false,
                    'message' => 'الرقم الجامعي غير موجود. تواصل مع موظف الشؤون.',
                ], 422);
            }
            if ($uid->is_used) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الرقم الجامعي مستخدم مسبقاً.',
                ], 422);
            }
        }

        // تحقق من رقم الابن لولي الأمر
        if ($request->role === 'parent' && $request->child_university_id) {
            $childUid = \DB::table('university_ids')
                ->where('university_id', $request->child_university_id)
                ->where('role', 'student')
                ->first();
            if (!$childUid) {
                return response()->json([
                    'success' => false,
                    'message' => 'الرقم الجامعي للطفل غير موجود.',
                ], 422);
            }
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

        $telegramId = $request->telegram_username && is_numeric(trim($request->telegram_username))
            ? trim($request->telegram_username)
            : null;

        $user = User::create([
            'full_name'        => $request->full_name,
            'username'         => $username,
            'email'            => $request->email,
            'phone'            => $request->phone,
            'telegram_chat_id' => $telegramId,
            'password'         => Hash::make($request->password),
            'role_id'          => $role->role_id,
            'status'           => 'inactive',
            'university_id'    => $request->university_id,
            'gender'           => $request->gender,
            'birth_date'       => $request->birth_date,
            'academic_year'    => $request->academic_year,
            'department'       => $request->department,
            'branch'           => $request->branch,
            'children_ids'     => $request->children_ids,
            'device_token'     => $request->fcm_token,
        ]);

        if ($request->role === 'student') {
            $student = Student::create([
                'user_id'      => $user->user_id,
                'student_code' => $request->university_id,
                'level'        => $request->academic_year ?? 'السنة الأولى',
                'birth_date'   => $request->birth_date,
            ]);

            // Auto-enroll: سجّل الطالب بكل مواد برنامجه بناءً على الفرع/التخصص
            $branch = $request->branch ?? $request->department;
            if ($branch) {
                $program = \DB::table('programs')
                    ->where('name', 'LIKE', '%' . $branch . '%')
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

        // علّم الرقم الجامعي كمستخدم
        if ($request->role === 'student') {
            \DB::table('university_ids')
                ->where('university_id', $request->university_id)
                ->update(['is_used' => true]);
        }

        // ── إشعار FCM لجميع موظفي الشؤون ────────────────────────────
        $roleLabel = $request->role === 'student' ? 'طالب' : 'ولي أمر';
        $fcmTitle  = 'طلب تسجيل جديد';
        $fcmBody   = 'قدّم ' . $request->full_name . ' طلب انضمام كـ' . $roleLabel . '. يرجى مراجعة الطلب والموافقة أو الرفض.';

        $affairsUsers = User::where('role_id', 6)->get();
        foreach ($affairsUsers as $affairsUser) {
            // إشعار داخل DB
            DB::table('notifications')->insert([
                'user_id'    => $affairsUser->user_id,
                'sender_id'  => null,
                'title'      => $fcmTitle,
                'message'    => $fcmBody,
                'type'       => 'administrative',
                'category'   => 'administrative',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // FCM push
            \App\Services\FcmService::sendToUser($affairsUser->user_id, $fcmTitle, $fcmBody, [
                'type'   => 'pending_account',
                'screen' => 'pending_accounts',
            ]);
        }

        return response()->json([
            'success'         => true,
            'pending_approval'=> true,
            'message'         => 'تم إرسال طلبك بنجاح. سيتم مراجعته من قِبل موظف الشؤون وسيُفعَّل حسابك قريباً.',
            'email'           => $request->email,
        ], 201);
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

    // ──────────────────────────────────────────────
    // OTP LOGIN — STEP 1: إرسال OTP عبر تيليغرام
    // ──────────────────────────────────────────────
    public function sendLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'يرجى إدخال رقم الهاتف'], 422);
        }

        $phone      = $request->phone;
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);

        $user = User::where('status', 'active')
            ->where(function ($q) use ($phone, $digitsOnly) {
                $q->where('phone', $phone)
                  ->orWhere('phone', '+' . $digitsOnly)
                  ->orWhereRaw("REPLACE(REPLACE(phone, '+', ''), ' ', '') = ?", [$digitsOnly]);
            })->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'لا يوجد حساب مرتبط بهذا الرقم'], 404);
        }

        if (!$user->telegram_chat_id) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب لا يملك Chat ID مرتبط. يرجى تسجيل الدخول بكلمة المرور.',
            ], 400);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('email', $user->email)->delete();
        OtpCode::create([
            'email'      => $user->email,
            'code'       => $otp,
            'expires_at' => now()->addMinutes(5),
        ]);

        $telegram = new TelegramService();
        $sent     = $telegram->sendOtp((int) $user->telegram_chat_id, $otp, $user->full_name);

        if (!$sent) {
            return response()->json(['success' => false, 'message' => 'فشل إرسال الرمز عبر تيليغرام'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق عبر تيليغرام',
            'email'   => $user->email,
        ], 200);
    }

    // ──────────────────────────────────────────────
    // OTP LOGIN — STEP 2: التحقق من OTP وإرجاع التوكن
    // ──────────────────────────────────────────────
    public function verifyLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $record = OtpCode::where('email', $request->email)
            ->where('code', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'الرمز غير صحيح أو منتهي الصلاحية'], 400);
        }

        $user = User::where('email', $request->email)->where('status', 'active')->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'الحساب غير موجود أو غير مفعّل'], 404);
        }

        $record->update(['used' => true]);
        $user->update(['last_login' => now()]);

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
    // SEND PROFILE OTP (عبر تيليغرام لتغيير الهاتف/الإيميل/كلمة المرور)
    // ──────────────────────────────────────────────
    public function sendProfileOtp(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'telegram_chat_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'يرجى إدخال Chat ID الخاص بتيليغرام'], 422);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('email', $user->email)->delete();
        OtpCode::create([
            'email'      => $user->email,
            'code'       => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);

        $telegram = new TelegramService();
        $chatId   = $telegram->findChatIdByUsername($request->telegram_chat_id);

        if (!$chatId) {
            return response()->json(['success' => false, 'message' => 'لم يتم العثور على حساب تيليغرام بهذا الـ Chat ID'], 404);
        }

        $sent = $telegram->sendOtp($chatId, $otp, $user->full_name);

        if (!$sent) {
            return response()->json(['success' => false, 'message' => 'فشل إرسال الرمز عبر تيليغرام'], 500);
        }

        return response()->json(['success' => true, 'message' => 'تم إرسال رمز التحقق عبر تيليغرام'], 200);
    }

    // ──────────────────────────────────────────────
    // VERIFY PROFILE OTP
    // ──────────────────────────────────────────────
    public function verifyProfileOtp(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $record = OtpCode::where('email', $user->email)
            ->where('code', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'الرمز غير صحيح أو منتهي الصلاحية'], 400);
        }

        $record->delete();

        return response()->json(['success' => true, 'message' => 'تم التحقق بنجاح'], 200);
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
