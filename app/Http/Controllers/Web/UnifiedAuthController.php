<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Parents;
use App\Models\UserActivity;

class UnifiedAuthController extends Controller
{
    /**
     * Role configurations for dedicated login routes.
     */
    protected $roleConfigs = [
        'admin'   => ['title' => 'تسجيل دخول إدارة النظام', 'icon' => 'fa-user-shield', 'badge' => 'مدير النظام'],
        'affairs' => ['title' => 'تسجيل دخول شؤون الطلاب', 'icon' => 'fa-clipboard-user', 'badge' => 'موظف الشؤون'],
        'hod'     => ['title' => 'تسجيل دخول رئيس القسم', 'icon' => 'fa-user-tie', 'badge' => 'رئيس القسم'],
        'teacher' => ['title' => 'تسجيل دخول الكادر التدريسي', 'icon' => 'fa-chalkboard-user', 'badge' => 'المعلم'],
        'student' => ['title' => 'تسجيل دخول الطالب', 'icon' => 'fa-graduation-cap', 'badge' => 'الطالب'],
        'parent'  => ['title' => 'تسجيل دخول ولي الأمر', 'icon' => 'fa-users', 'badge' => 'ولي الأمر'],
        'unified' => ['title' => 'بوابة تسجيل الدخول الموحدة', 'icon' => 'fa-shield-halved', 'badge' => 'الدخول الموحد'],
    ];

    /**
     * Show the login form for specific role or unified.
     */
    public function showLoginForm(Request $request, $roleKey = 'unified')
    {
        if (Auth::check()) {
            return $this->redirectUserByRole(Auth::user());
        }

        $role = $this->roleConfigs[$roleKey] ?? $this->roleConfigs['unified'];
        $role['key'] = $roleKey;

        return view('login', compact('role'));
    }

    /**
     * Handle login request for any actor.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
            'role_key' => 'nullable|string',
        ], [
            'login.required'    => 'يرجى إدخال اسم المستخدم، البريد الإلكتروني، أو الرقم الجامعي.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $input   = trim($request->login);
        $roleKey = $request->input('role_key', 'unified');

        // 1. Search in User table by email, phone, username, or university_id
        $user = User::where('email', $input)
            ->orWhere('phone', $input)
            ->orWhere('username', $input)
            ->orWhere('university_id', $input)
            ->first();

        // 2. If not found, search in Student table by student_code
        if (!$user) {
            $student = Student::where('student_code', $input)
                ->first();
            if ($student && $student->user) {
                $user = $student->user;
            }
        }

        // 3. Validate credentials
        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->status !== 'active') {
                UserActivity::log('محاولة دخول مرفوضة', 'الحساب موقوف مؤقتاً', $user);
                return back()->withErrors(['login' => 'عذراً، هذا الحساب موقوف مؤقتاً.'])->withInput($request->only('login'));
            }

            // 4. If logged in through a dedicated role route, ensure role matches
            if ($roleKey !== 'unified') {
                $userRoleId = (int) ($user->role_id ?? 0);
                $userRole   = strtolower($user->role ?? '');

                $isMatch = false;
                switch ($roleKey) {
                    case 'admin':
                        $isMatch = ($userRoleId === 1 || $userRole === 'admin' || !empty($user->is_admin));
                        break;
                    case 'affairs':
                        $isMatch = ($userRoleId === 6 || $userRole === 'affairs');
                        break;
                    case 'hod':
                        $isMatch = ($userRoleId === 5 || $userRole === 'head' || $userRole === 'hod');
                        break;
                    case 'teacher':
                        $isMatch = ($userRoleId === 2 || $userRole === 'teacher' || $userRole === 'instructor');
                        break;
                    case 'student':
                        $isMatch = ($userRoleId === 3 || $userRole === 'student' || Student::where('user_id', $user->user_id)->exists());
                        break;
                    case 'parent':
                        $isMatch = ($userRoleId === 4 || $userRole === 'parent' || Parents::where('user_id', $user->user_id)->exists());
                        break;
                    default:
                        $isMatch = true;
                }

                if (!$isMatch) {
                    $expectedBadge = $this->roleConfigs[$roleKey]['badge'] ?? '';
                    return back()->withErrors(['login' => "هذا الحساب غير مسجل بصفة ($expectedBadge). يرجى التأكد من رابط الدخول الصحيح."])->withInput($request->only('login'));
                }
            }

            $remember = $request->has('remember');
            Auth::login($user, $remember);
            $request->session()->regenerate();
            UserActivity::log('تسجيل دخول', 'تسجيل دخول ناجح', $user);

            return $this->redirectUserByRole($user);
        }

        return back()->withErrors(['login' => 'بيانات الدخول غير صحيحة، يرجى التأكد من اسم المستخدم وكلمة المرور.'])->withInput($request->only('login'));
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        $isInactivity = $request->has('is_inactivity_logout');
        if (Auth::check()) {
            if ($isInactivity) {
                UserActivity::log('خروج تلقائي (خمول)', 'تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول');
            } else {
                UserActivity::log('تسجيل خروج', 'قام المستخدم بتسجيل الخروج يدوياً');
            }
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($isInactivity) {
            return redirect('/login')->with('warning', '🔒 تم تسجيل الخروج تلقائياً لحماية حسابك بسبب عدم وجود أي نشاط لمدة 20 دقيقة.');
        }

        return redirect('/login')->with('success', 'تم تسجيل الخروج بنجاح.');
    }

    /**
     * Helper to redirect users to their respective dashboards based on role.
     */
    public function redirectUserByRole($user)
    {
        $roleId  = (int) ($user->role_id ?? 0);
        $roleStr = strtolower($user->role ?? '');

        if ($roleId === 6 || $roleStr === 'affairs') {
            return redirect('/affairs/dashboard');
        }
        if ($roleId === 5 || $roleStr === 'head' || $roleStr === 'hod') {
            return redirect('/hod/dashboard');
        }
        if ($roleId === 1 || $roleStr === 'admin' || !empty($user->is_admin)) {
            return redirect('/admin/dashboard');
        }
        if ($roleId === 2 || $roleStr === 'teacher' || $roleStr === 'instructor') {
            return redirect('/teacher/dashboard');
        }
        if ($roleId === 3 || $roleStr === 'student' || Student::where('user_id', $user->user_id)->exists()) {
            return redirect('/student/dashboard');
        }
        if ($roleId === 4 || $roleStr === 'parent' || Parents::where('user_id', $user->user_id)->exists()) {
            return redirect('/parent/dashboard');
        }

        return redirect('/login');
    }

    /**
     * 1. إرسال رمز OTP لإعادة تعيين كلمة السر عبر تلغرام
     */
    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'identifier'          => 'required|string',
            'telegram_identifier' => 'required|string',
            'role'                => 'nullable|string',
        ], [
            'identifier.required'          => 'يرجى إدخال البيانات المطلوبة (الرقم الجامعي أو رقم الجوال).',
            'telegram_identifier.required' => 'يرجى إدخال معرف التليجرام أو Chat ID.',
        ]);

        $input        = trim($request->identifier);
        $telegramInput = trim($request->telegram_identifier);
        $role         = $request->input('role', 'unified');

        // البحث عن المستخدم
        $user = User::where('email', $input)
            ->orWhere('phone', $input)
            ->orWhere('username', $input)
            ->orWhere('university_id', $input)
            ->first();

        if (!$user && ($role === 'student' || $role === 'unified')) {
            $student = Student::where('student_code', $input)->orWhere('university_id', $input)->first();
            if ($student && $student->user) {
                $user = $student->user;
            }
        }

        if (!$user && ($role === 'parent' || $role === 'unified')) {
            $parent = Parents::where('phone', $input)->first();
            if ($parent && $parent->user) {
                $user = $parent->user;
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'لم نتمكن من العثور على حساب مطابِق للبيانات المدخلة. يرجى التأكد من الرقم الجامعي أو رقم الجوال.'
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب غير نشط أو موقوف مؤقتاً. يرجى مراجعة إدارة الشؤون.'
            ], 403);
        }

        // جلب Chat ID من التليجرام
        $telegramService = app(\App\Services\TelegramService::class);
        $chatId = $user->telegram_chat_id;

        if (!$chatId) {
            $foundId = $telegramService->findChatIdByUsername($telegramInput);
            if ($foundId) {
                $chatId = $foundId;
                $user->update(['telegram_chat_id' => $chatId]);
            } elseif (is_numeric($telegramInput)) {
                $chatId = (int) $telegramInput;
                $user->update(['telegram_chat_id' => $chatId]);
            }
        }

        // توليد رمز OTP مكون من 6 أرقام
        $otp = (string) random_int(100000, 999999);

        // حفظ الرمز في الجلسة
        session([
            'pwd_reset_user_id'    => $user->user_id,
            'pwd_reset_otp'        => $otp,
            'pwd_reset_expires_at' => now()->addMinutes(15)->timestamp,
            'pwd_reset_verified'   => false,
        ]);

        // إرسال الرسالة عبر التليجرام
        $sent = false;
        if ($chatId) {
            $sent = $telegramService->sendOtpSync((int) $chatId, $otp, $user->full_name ?? '');
        }

        if (!$sent) {
            // في حال عدم توفر البوت أو عدم العثور على Chat ID، يتم إبلاغ المستخدم بضرورة بدء المحادثة مع البوت
            return response()->json([
                'success' => true,
                'message' => "تم توليد رمز التحقق OTP 🔐 (الرمز التجريبي: {$otp}). يرجى التأكد من بدء محادثة مع بوت تليجرام الجامعة لاستلام الرسائل تلقائياً.",
                'chat_id' => $chatId
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز OTP إلى حسابك في تليجرام بنجاح! 📲 يرجى فحص تطبيق تليجرام.'
        ]);
    }

    /**
     * 2. التحقق من رمز الـ OTP
     */
    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ], [
            'otp.required' => 'يرجى إدخال رمز OTP المكون من 6 أرقام.',
            'otp.size'     => 'رمز OTP يجب أن يتكون من 6 أرقام تماماً.',
        ]);

        $sessionOtp     = session('pwd_reset_otp');
        $expiresAt      = session('pwd_reset_expires_at');
        $userId         = session('pwd_reset_user_id');

        if (!$sessionOtp || !$expiresAt || !$userId) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت جلسة استعادة كلمة السر. يرجى إعادة الطلب من جديد.'
            ], 400);
        }

        if (now()->timestamp > $expiresAt) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية رمز OTP (مرت 15 دقيقة). يرجى طلب رمز جديد.'
            ], 400);
        }

        if (trim($request->otp) !== (string) $sessionOtp) {
            return response()->json([
                'success' => false,
                'message' => 'رمز OTP المدخل غير صحيح. يرجى التأكد وإعادة المحاولة.'
            ], 422);
        }

        session(['pwd_reset_verified' => true]);

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق من الرمز بنجاح! يمكنك الآن كتابة كلمة المرور الجديدة.'
        ]);
    }

    /**
     * 3. تغيير كلمة المرور وتحديثها في قاعدة البيانات
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ], [
            'password.required'  => 'يرجى إدخال كلمة المرور الجديدة.',
            'password.min'       => 'كلمة المرور يجب أن لا تقل عن 6 أحرف/أرقام.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابِق.',
        ]);

        $verified = session('pwd_reset_verified');
        $userId   = session('pwd_reset_user_id');

        if (!$verified || !$userId) {
            return response()->json([
                'success' => false,
                'message' => 'جلسة غير مصرح بها. يرجى التحقق من كود OTP أولاً.'
            ], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود في النظام.'
            ], 404);
        }

        // تحديث كلمة السر في قاعدة البيانات بعد تشفيرها
        $user->password = Hash::make($request->password);
        $user->save();

        UserActivity::log('إعادة تعيين كلمة السر', 'تم استعادة وتحديث كلمة السر بنجاح عبر تليجرام OTP', $user);

        // مسح بيانات الاستعادة من الجلسة
        session()->forget(['pwd_reset_user_id', 'pwd_reset_otp', 'pwd_reset_expires_at', 'pwd_reset_verified']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث كلمة المرور بنجاح! ✨ يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة.'
        ]);
    }
}
