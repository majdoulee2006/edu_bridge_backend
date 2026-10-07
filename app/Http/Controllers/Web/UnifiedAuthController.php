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
use App\Traits\FaceRecognitionTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Support\SingleSessionGuard;
use App\Support\LoginThrottleGuard;

class UnifiedAuthController extends Controller
{
    use FaceRecognitionTrait;

    /**
     * بوابة الدخول الموحدة الوحيدة: كل الأدوار تدخل من /login والنظام يوجّه كل مستخدم حسب دوره.
     */
    protected $roleConfigs = [
        'unified' => ['title' => 'بوابة تسجيل الدخول الموحدة', 'icon' => 'fa-shield-halved', 'badge' => 'الدخول الموحد'],
    ];

    /**
     * Show the unified login form.
     */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectUserByRole(Auth::user());
        }

        $role = $this->roleConfigs['unified'];
        $role['key'] = 'unified';
        $role['title'] = __('messages.login_title_unified');
        $role['badge'] = __('messages.login_badge_unified');

        // لا تُخزَّن صفحة الدخول (رمز CSRF قديم في الكاش = خطأ 419 عند الإرسال)
        return response()->view('login', compact('role'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Handle login request for any actor.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required'    => __('messages.login_field_required'),
            'password.required' => __('messages.password_required'),
        ]);

        $input   = trim($request->login);

        // 1. Search in User table by email, phone, username, or university_id
        $user = User::where('email', $input)
            ->orWhere('phone', $input)
            ->orWhere('username', $input)
            ->orWhere('university_id', $input)
            ->orWhere('username', $input . '@edu-bridge.com')
            ->orWhere('email', $input . '@gmail.com')
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
        if ($user && LoginThrottleGuard::isLocked($user)) {
            $minutes = LoginThrottleGuard::lockRemainingMinutes($user);
            UserActivity::log('محاولة دخول مرفوضة', 'الحساب مقفول مؤقتاً بسبب محاولات دخول فاشلة متكررة', $user);
            return back()->withErrors([
                'login' => "🔒 " . __('messages.account_locked_temporary', ['minutes' => $minutes])
            ])->withInput($request->only('login'));
        }

        $passwordValid = $user && Hash::check($request->password, $user->password);

        if ($user && !$passwordValid) {
            LoginThrottleGuard::recordFailure($user);
        }

        if ($passwordValid) {
            LoginThrottleGuard::recordSuccess($user);

            if ($user->status !== 'active') {
                UserActivity::log('محاولة دخول مرفوضة', 'الحساب موقوف مؤقتاً', $user);
                return back()->withErrors(['login' => __('messages.account_inactive')])->withInput($request->only('login'));
            }


            // 5. منع تسجيل الدخول المتزامن على الويب: "القديم بيضل، الجديد بينرفض"
            //    - إلا لو فاتت 20 دقيقة بدون نشاط عالجلسة القديمة (تعتبر منتهية).
            //    - الطالب بس عنده استثناء: بدل الرفض، تحقق بالوجه (خطوة 6).
            $isStudent = ($user->role_id == 3 || strtolower($user->role ?? '') === 'student' || Student::where('user_id', $user->user_id)->exists());
            $currentSessionId = $request->session()->getId();

            if (SingleSessionGuard::isWebOccupied($user, $currentSessionId)) {
                if (!$isStudent) {
                    UserActivity::log('دخول مرفوض (جلسة مسبقة)', 'محاولة تسجيل دخول بينما توجد جلسة نشطة بالفعل على جهاز آخر', $user);
                    SingleSessionGuard::notifyIntrusionAttempt($user, 'web');
                    return back()->withErrors([
                        'login' => __('messages.concurrent_session_warning')
                    ])->withInput($request->only('login'));
                }

                // 6. الطالب: تحقق من الجهاز بدل الرفض المباشر - إلزام التحقق بالوجه
                // عند الدخول من متصفح/جهاز غير معروف.
                $student = Student::where('user_id', $user->user_id)->first();
                if ($student) {
                    $deviceCookie = $request->cookie('edubridge_student_web_device');

                    if (empty($deviceCookie) || empty($student->web_device_token) || $deviceCookie !== $student->web_device_token) {
                        $request->session()->put('pending_face_auth', [
                            'user_id'    => $user->user_id,
                            'student_id' => $student->student_id,
                            'remember'   => $request->has('remember'),
                            'time'       => now()->timestamp,
                        ]);

                        SingleSessionGuard::notifyIntrusionAttempt($user, 'web');
                        UserActivity::log('طلب تحقق بالوجه', 'محاولة دخول طالب من جهاز ويب جديد تتطلب التحقق بالوجه', $user);

                        return redirect()->route('student.face_auth.show');
                    }
                    // نفس الجهاز المعروف مسبقاً (مثلاً تبويب/تحديث جديد) - يكمل الدخول عادي.
                }
            }

            $remember = $request->has('remember');
            Auth::login($user, $remember);
            $request->session()->regenerate();

            SingleSessionGuard::stampWebSession($user, $request->session()->getId());

            UserActivity::log('تسجيل دخول', 'تسجيل دخول ناجح', $user);

            return $this->redirectUserByRole($user);
        }

        return back()->withErrors(['login' => __('messages.invalid_credentials')])->withInput($request->only('login'));
    }

    /**
     * Show Student Face Verification Screen (when switching devices or new web device)
     */
    public function showFaceAuth(Request $request)
    {
        $pending = $request->session()->get('pending_face_auth');
        if (!$pending || empty($pending['user_id']) || empty($pending['student_id'])) {
            return redirect()->route('login')->withErrors(['login' => 'انتهت صلاحية جلسة التحقق، يرجى تسجيل الدخول مجدداً.']);
        }

        $user = User::find($pending['user_id']);
        $student = Student::with('program')->find($pending['student_id']);

        if (!$user || !$student) {
            $request->session()->forget('pending_face_auth');
            return redirect()->route('login');
        }

        $hasReferencePhoto = !empty($student->reference_photo) && Storage::disk('public')->exists($student->reference_photo);

        return view('auth.student_face_verify', compact('user', 'student', 'hasReferencePhoto'));
    }

    /**
     * Verify Live Face Capture against Reference Photo
     */
    public function verifyFaceAuth(Request $request)
    {
        $pending = $request->session()->get('pending_face_auth');
        if (!$pending || empty($pending['user_id']) || empty($pending['student_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية جلسة التحقق، يرجى إعادة تسجيل الدخول.',
            ], 401);
        }

        $request->validate([
            'face_image' => 'required|string',
        ], [
            'face_image.required' => 'صورة الوجه المباشرة مطلوبة للتحقق.',
        ]);

        $user = User::find($pending['user_id']);
        $student = Student::find($pending['student_id']);

        if (!$user || !$student) {
            $request->session()->forget('pending_face_auth');
            return response()->json([
                'success' => false,
                'message' => 'بيانات الحساب غير موجودة.',
            ], 404);
        }

        $rawBase64 = $request->face_image;
        $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $rawBase64);
        $capturedBinary = base64_decode($cleanBase64);

        if (!$capturedBinary) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر قراءة بيانات صورة الوجه، يرجى إعادة المحاولة.',
            ], 422);
        }

        $refPhotoPath = $student->reference_photo;
        $faceScore = 100.0;
        $isFirstTimePhoto = false;

        if ($refPhotoPath && Storage::disk('public')->exists($refPhotoPath)) {
            $refBinary = Storage::disk('public')->get($refPhotoPath);
            $refVector = $this->extractImageVector($refBinary);
            $capVector = $this->extractImageVector($capturedBinary);

            if (!empty($refVector) && !empty($capVector)) {
                $faceScore = $this->calculateFaceSimilarity($refVector, $capVector);

                // حد القبول 80% (تم رفعه من 70% لتقليل احتمال قبول وجه غير مطابق)
                if ($faceScore < 80.0) {
                    UserActivity::log('فشل التحقق بالوجه', "محاولة دخول غير مطابقة لبصمة وجه الطالب (نسبة التطابق: {$faceScore}%)", $user);

                    return response()->json([
                        'success'    => false,
                        'message'    => "فشل التحقق من الوجه: الوجه المصور غير مطابق للصورة الرسمية المعتمدة للطالب ❌ (نسبة التطابق: {$faceScore}%)",
                        'face_score' => $faceScore,
                    ], 403);
                }
            }
        } else {
            // إذا لم يكن لديه صورة مرجعية مسجلة سابقاً، نحفظ هذه الصورة لتصبح صورته المرجعية الرسمية
            $fileName = 'students/reference_photos/ref_' . $student->student_id . '_' . time() . '.jpg';
            Storage::disk('public')->put($fileName, $capturedBinary);
            $student->update(['reference_photo' => $fileName]);
            $isFirstTimePhoto = true;
        }

        // نجاح التحقق بالوجه! تسجيل الدخول الرسمي - الجلسة القديمة (لو
        // موجودة) بتنطرد تلقائياً لما تُستخدم لاحقاً عبر EnsureSingleWebSession
        // (بمجرد ما current_session_id يتحدث تحت، ما بيصير لازم نلمس ملف
        // الجلسة القديمة يدوياً - أيّاً كان session driver المستخدم).
        Auth::login($user, $pending['remember'] ?? false);
        $request->session()->regenerate();
        $request->session()->forget('pending_face_auth');

        // توليد رمز جهاز ويب جديد للطالب وتحديث قاعدة البيانات
        $newDeviceToken = Str::random(64);
        $student->update([
            'web_device_token' => $newDeviceToken,
        ]);

        SingleSessionGuard::stampWebSession($user, $request->session()->getId());

        UserActivity::log('تسجيل دخول بالوجه', "تم التحقق من بصمة الوجه بنجاح وتسجيل الدخول من جهاز ويب جديد (نسبة التطابق: {$faceScore}%)", $user);

        // كوكيز لمدة سنة بدل forever() - جهاز عام/مشترك ما لازم يضل "موثوق" للأبد
        $cookie = cookie('edubridge_student_web_device', $newDeviceToken, 60 * 24 * 365);

        return response()->json([
            'success'      => true,
            'message'      => $isFirstTimePhoto 
                ? 'تم اعتماد بصمة الوجه الأولى وتوثيق الجهاز بنجاح! جاري تحويلك...' 
                : "تم التحقق من بصمة الوجه بنجاح (تطابق {$faceScore}%)! جاري تحويلك...",
            'face_score'   => $faceScore,
            'redirect_url' => route('student.dashboard'),
        ])->withCookie($cookie);
    }

    /**
     * Cancel face auth and go back to login
     */
    public function cancelFaceAuth(Request $request)
    {
        $request->session()->forget('pending_face_auth');
        return redirect()->route('login');
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
        // مسح current_session_id بيصير مركزياً عبر حدث Logout (AppServiceProvider)
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($isInactivity) {
            return redirect()->route('login')->with('warning', '🔒 تم تسجيل الخروج تلقائياً لحماية حسابك بسبب عدم وجود أي نشاط لمدة 20 دقيقة.');
        }

        return redirect()->route('login')->with('success', 'تم تسجيل الخروج بنجاح.');
    }

    /**
     * Helper to redirect users to their respective dashboards based on role.
     */
    public function redirectUserByRole($user)
    {
        $roleId  = (int) ($user->role_id ?? 0);
        $roleStr = strtolower($user->role ?? '');

        if ($roleId === 6 || $roleStr === 'affairs') {
            return redirect()->to(url('/affairs/dashboard'));
        }
        if ($roleId === 5 || $roleStr === 'head' || $roleStr === 'hod') {
            return redirect()->to(url('/hod/dashboard'));
        }
        if ($roleId === 1 || $roleStr === 'admin' || !empty($user->is_admin)) {
            return redirect()->to(url('/admin/dashboard'));
        }
        if ($roleId === 2 || $roleStr === 'teacher' || $roleStr === 'instructor') {
            return redirect()->to(url('/teacher/dashboard'));
        }
        if ($roleId === 3 || $roleStr === 'student' || Student::where('user_id', $user->user_id)->exists()) {
            return redirect()->to(url('/student/dashboard'));
        }
        if ($roleId === 4 || $roleStr === 'parent' || Parents::where('user_id', $user->user_id)->exists()) {
            return redirect()->to(url('/parent/dashboard'));
        }

        return redirect()->route('login');
    }

    /**
     * 1. إرسال رمز OTP لإعادة تعيين كلمة السر عبر تلغرام
     */
    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'identifier'          => 'required|string',
            'telegram_identifier' => 'nullable|string',
            'role'                => 'nullable|string',
        ], [
            'identifier.required' => __('messages.identifier_required'),
        ]);

        $input = trim($request->identifier);
        $role  = $request->input('role', 'unified');

        // البحث عن المستخدم
        $user = User::where('email', $input)
            ->orWhere('phone', $input)
            ->orWhere('username', $input)
            ->orWhere('university_id', $input)
            ->orWhere('username', $input . '@edu-bridge.com')
            ->orWhere('email', $input . '@gmail.com')
            ->first();

        if (!$user && ($role === 'student' || $role === 'unified')) {
            $student = Student::where('student_code', $input)->first();
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
                'message' => __('messages.account_not_found')
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => __('messages.account_inactive_contact_affairs')
            ], 403);
        }

        // الرمز يُرسل فقط إلى حساب تيليغرام المربوط مسبقاً بهذا الحساب (يربطه صاحبه من البوت بكلمة سره).
        // لا نقبل أبداً معرّف تيليغرام قادماً من الطلب: وإلا يستطيع أي شخص يعرف الرقم الجامعي
        // أن يربط حسابه هو ويستلم الرمز ويستولي على الحساب (ويستمر باستلام إشعاراته بعدها).
        $chatId = $user->telegram_chat_id;

        if (!$chatId) {
            return response()->json([
                'success' => false,
                'message' => __('messages.reset_telegram_not_linked'),
            ], 422);
        }

        // توليد رمز OTP مكون من 6 أرقام
        $otp = (string) random_int(100000, 999999);

        $sent = app(\App\Services\TelegramService::class)
            ->sendOtpSync((int) $chatId, $otp, $user->full_name ?? '');

        if (!$sent) {
            // لا نُرجع الرمز في الجواب أبداً، ولا نفتح جلسة استعادة بدون رمز وصل فعلاً
            return response()->json([
                'success' => false,
                'message' => __('messages.otp_send_failed'),
            ], 503);
        }

        // حفظ الرمز في الجلسة بعد نجاح الإرسال فقط
        session([
            'pwd_reset_user_id'    => $user->user_id,
            'pwd_reset_otp'        => $otp,
            'pwd_reset_expires_at' => now()->addMinutes(15)->timestamp,
            'pwd_reset_verified'   => false,
        ]);
        session()->forget('pwd_reset_attempts');

        return response()->json([
            'success' => true,
            'message' => __('messages.otp_sent_success'),
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
            'otp.required' => __('messages.otp_required'),
            'otp.size'     => __('messages.otp_size_exact'),
        ]);

        $sessionOtp     = session('pwd_reset_otp');
        $expiresAt      = session('pwd_reset_expires_at');
        $userId         = session('pwd_reset_user_id');

        if (!$sessionOtp || !$expiresAt || !$userId) {
            return response()->json([
                'success' => false,
                'message' => __('messages.reset_session_expired')
            ], 400);
        }

        if (now()->timestamp > $expiresAt) {
            return response()->json([
                'success' => false,
                'message' => __('messages.otp_expired')
            ], 400);
        }

        if (!hash_equals((string) $sessionOtp, trim($request->otp))) {
            // بعد 5 محاولات خاطئة يُبطَل الرمز ويلزم طلب رمز جديد (منع التخمين)
            $attempts = (int) session('pwd_reset_attempts', 0) + 1;
            if ($attempts >= 5) {
                session()->forget(['pwd_reset_otp', 'pwd_reset_expires_at', 'pwd_reset_user_id', 'pwd_reset_attempts']);
                return response()->json([
                    'success' => false,
                    'message' => __('messages.max_attempts_exceeded')
                ], 429);
            }
            session(['pwd_reset_attempts' => $attempts]);

            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_otp')
            ], 422);
        }

        session()->forget('pwd_reset_attempts');
        session(['pwd_reset_verified' => true]);

        return response()->json([
            'success' => true,
            'message' => __('messages.otp_verified_success')
        ]);
    }

    /**
     * 3. تغيير كلمة المرور وتحديثها في قاعدة البيانات
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ], [
            'password.required'  => __('messages.new_password_required'),
            'password.min'       => __('messages.password_min_length'),
            'password.confirmed' => __('messages.password_confirmation_mismatch'),
        ]);

        $verified = session('pwd_reset_verified');
        $userId   = session('pwd_reset_user_id');

        if (!$verified || !$userId) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized_reset_session')
            ], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.user_not_found')
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
            'message' => __('messages.password_reset_success')
        ]);
    }
}
