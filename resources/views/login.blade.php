<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $role['title'] ?? 'EduBridge - تسجيل الدخول' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #09090b; /* Very dark zinc */
            --bg-card: #18181b; /* Dark zinc for card */
            --border-color: #27272a;
            --accent-yellow: #facc15; /* Clean web yellow */
            --accent-hover: #eab308;
            --text-primary: #fafafa;
            --text-secondary: #a1a1aa;
            --input-bg: #09090b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(circle at 50% 0%, #1f2937 0%, transparent 50%);
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }
            .login-wrapper {
                padding: 0.25rem;
            }
            .login-card {
                padding: 1.5rem 1rem !important;
                border-radius: 14px !important;
            }
            .brand-name {
                font-size: 1.5rem !important;
            }
            .remember-row {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.75rem !important;
            }
            .reset-modal-card {
                padding: 1.25rem 1rem !important;
                border-radius: 16px !important;
                max-width: 95vw !important;
            }
            .otp-digit-input {
                width: 36px !important;
                height: 44px !important;
                font-size: 1.2rem !important;
            }
        }


        /* Top yellow accent line */
        .login-card::before {
            content: '';
            position: absolute;
            top: -1px; left: -1px; right: -1px; height: 3px;
            background: var(--accent-yellow);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            font-size: 2.5rem;
            color: var(--accent-yellow);
            margin-bottom: 0.8rem;
        }

        .brand-name {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .brand-name span {
            color: var(--accent-yellow);
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(250, 204, 21, 0.1);
            color: var(--accent-yellow);
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-top: 0.5rem;
        }

        .page-title {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #e4e4e7;
            margin-bottom: 0.5rem;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            color: #71717a;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .form-control {
            width: 100%;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-yellow);
            box-shadow: 0 0 0 1px var(--accent-yellow);
        }

        .input-group:focus-within .input-icon {
            color: var(--accent-yellow);
        }

        .password-toggle {
            position: absolute;
            left: 1rem;
            background: none;
            border: none;
            color: #71717a;
            cursor: pointer;
            padding: 0;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--accent-yellow);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            background-color: var(--accent-hover);
        }

        .alert-message {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #52525b;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="logo-section">
                <i class="fa-solid {{ $role['icon'] ?? 'fa-graduation-cap' }} logo-icon"></i>
                <h1 class="brand-name">Edu<span>Bridge</span></h1>
                
                @if(isset($role['badge']))
                    <div class="role-badge">
                        <i class="fa-solid {{ $role['icon'] ?? 'fa-user' }}"></i>
                        <span>{{ $role['badge'] }}</span>
                    </div>
                @endif
                
                <p class="page-title">{{ $role['title'] ?? 'بوابة تسجيل الدخول الموحدة' }}</p>
            </div>

            @if (session('success'))
                <div class="alert-message alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert-message alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert-message alert-error" style="background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.2); color: #fcd34d;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-message alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ url('/login') }}" method="POST">
                @csrf
                <input type="hidden" name="role_key" value="{{ $role['key'] ?? 'unified' }}">

                <div class="form-group">
                    <label class="form-label">بيانات الحساب</label>
                    <div class="input-group">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" name="login" class="form-control" 
                               placeholder="{{ ($role['key'] ?? '') === 'student' ? 'الرقم الجامعي أو اسم المستخدم' : 'اسم المستخدم، البريد، أو الهاتف' }}" 
                               value="{{ old('login') }}" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="password" id="password_input" class="form-control" 
                               placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility()">
                            <i class="fa-solid fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="remember-row" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; margin-bottom: 0.5rem; gap: 0.5rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="remember" id="remember" style="accent-color: var(--accent-yellow); width: 16px; height: 16px; cursor: pointer;">
                        <label for="remember" style="color: #e4e4e7; font-size: 0.85rem; cursor: pointer; user-select: none;">تذكرني (Remember Me)</label>
                    </div>
                    <a href="javascript:void(0)" onclick="openResetPasswordModal()" style="color: var(--accent-yellow); font-size: 0.85rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                        <i class="fa-solid fa-key"></i>
                        <span>نسيت كلمة المرور؟</span>
                    </a>
                </div>

                <button type="submit" class="btn-submit">
                    تسجيل الدخول
                    <i class="fa-solid fa-arrow-left"></i>
                </button>

                <div style="text-align: center; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                    <span style="color: #a1a1aa; font-size: 0.88rem;">ليس لديك حساب؟ </span>
                    <a href="javascript:void(0)" onclick="openRegisterModal()" style="color: var(--accent-yellow); font-weight: 700; text-decoration: none; font-size: 0.92rem; margin-right: 4px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                        <i class="fa-solid fa-user-plus" style="margin-left: 3px;"></i>
                        إنشاء حساب جديد
                    </a>
                </div>
            </form>
            
        </div>
        
        <div class="footer">
            جميع الحقوق محفوظة &copy; {{ date('Y') }} EduBridge System
        </div>
    </div>

    <!-- Modal استعادة كلمة المرور عبر تليجرام OTP -->
    <div id="resetPasswordModal" class="reset-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(10px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
        <div class="reset-modal-card" style="background: #18181b; border: 1px solid #27272a; border-radius: 20px; width: 100%; max-width: 460px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); position: relative; animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            
            <!-- الشريط العلوي للأنيق -->
            <div style="height: 4px; background: linear-gradient(90deg, #facc15, #eab308);"></div>
            
            <div style="padding: 1.8rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(250, 204, 21, 0.1); color: var(--accent-yellow); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-brands fa-telegram"></i>
                        </div>
                        <div>
                            <h3 style="color: #fff; font-size: 1.1rem; font-weight: 800; margin: 0;">استعادة كلمة المرور</h3>
                            <p style="color: #a1a1aa; font-size: 0.8rem; margin: 0;">عبر رمز OTP التليجرام الآمن</p>
                        </div>
                    </div>
                    <button onclick="closeResetPasswordModal()" style="background: rgba(255, 255, 255, 0.05); border: none; color: #a1a1aa; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='#a1a1aa';">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- رسائل التنبيه بالأخطاء والنجاح داخل المودال -->
                <div id="reset-alert-box" style="display: none; padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1.2rem; align-items: center; gap: 0.6rem;">
                    <i id="reset-alert-icon" class="fa-solid fa-circle-info"></i>
                    <span id="reset-alert-text"></span>
                </div>

                <!-- الخطوة 1: اختيار الصفة وإدخال المعرفات -->
                <div id="step-1-form">
                    <div style="margin-bottom: 1.2rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #e4e4e7; margin-bottom: 0.6rem;">اختر صفة الحساب:</label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; background: #09090b; padding: 4px; border-radius: 10px; border: 1px solid #27272a;">
                            <button type="button" class="role-tab-btn active" data-role="student" onclick="selectRoleForReset('student')" style="padding: 0.5rem; border: none; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.2s; background: var(--accent-yellow); color: #000;">
                                <i class="fa-solid fa-graduation-cap"></i> طالب
                            </button>
                            <button type="button" class="role-tab-btn" data-role="parent" onclick="selectRoleForReset('parent')" style="padding: 0.5rem; border: none; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.2s; background: transparent; color: #a1a1aa;">
                                <i class="fa-solid fa-users"></i> ولي أمر
                            </button>
                            <button type="button" class="role-tab-btn" data-role="teacher" onclick="selectRoleForReset('teacher')" style="padding: 0.5rem; border: none; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.2s; background: transparent; color: #a1a1aa;">
                                <i class="fa-solid fa-chalkboard-user"></i> أستاذ
                            </button>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.2rem;">
                        <label id="identifier-label" style="display: block; font-size: 0.85rem; font-weight: 700; color: #e4e4e7; margin-bottom: 0.4rem;">الرقم الجامعي أو رقم الجوال:</label>
                        <div class="input-group">
                            <i id="identifier-icon" class="fa-solid fa-id-card input-icon"></i>
                            <input type="text" id="reset-identifier" class="form-control" placeholder="أدخل الرقم الجامعي أو رقم الجوال..." required>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #e4e4e7; margin-bottom: 0.4rem;">معرف التليجرام أو Chat ID:</label>
                        <div class="input-group">
                            <i class="fa-brands fa-telegram input-icon" style="color: #38bdf8;"></i>
                            <input type="text" id="reset-telegram" class="form-control" placeholder="مثال: @username أو Chat ID" required>
                        </div>
                        <p style="font-size: 0.75rem; color: #71717a; margin-top: 0.4rem;">
                            💡 سيتم إرسال رمز OTP مكون من 6 أرقام إلى حسابك عبر بوت التليجرام.
                        </p>
                    </div>

                    <button type="button" id="btn-send-otp" onclick="submitSendOtp()" class="btn-submit" style="margin-top: 0;">
                        <span>إرسال رمز OTP عبر تليجرام</span>
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>

                <!-- الخطوة 2: إدخال رمز OTP (6 أرقام) -->
                <div id="step-2-form" style="display: none;">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(34, 197, 94, 0.1); color: #4ade80; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.6rem;">
                            <i class="fa-solid fa-shield-cat"></i>
                        </div>
                        <h4 style="color: #fff; font-size: 1rem; font-weight: 700; margin-bottom: 0.2rem;">أدخل رمز التحقق (OTP)</h4>
                        <p style="color: #a1a1aa; font-size: 0.8rem;">أدخل الرمز المكون من 6 أرقام المرسل إلى تليجرام</p>
                    </div>

                    <!-- 6 حقول إدخال للأرقام -->
                    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem; dir: ltr;" id="otp-inputs-container">
                        <input type="text" maxlength="1" class="otp-digit-input" data-index="0" style="width: 44px; height: 50px; text-align: center; font-size: 1.4rem; font-weight: 800; background: #09090b; border: 1px solid #3f3f46; border-radius: 10px; color: var(--accent-yellow); outline: none;">
                        <input type="text" maxlength="1" class="otp-digit-input" data-index="1" style="width: 44px; height: 50px; text-align: center; font-size: 1.4rem; font-weight: 800; background: #09090b; border: 1px solid #3f3f46; border-radius: 10px; color: var(--accent-yellow); outline: none;">
                        <input type="text" maxlength="1" class="otp-digit-input" data-index="2" style="width: 44px; height: 50px; text-align: center; font-size: 1.4rem; font-weight: 800; background: #09090b; border: 1px solid #3f3f46; border-radius: 10px; color: var(--accent-yellow); outline: none;">
                        <input type="text" maxlength="1" class="otp-digit-input" data-index="3" style="width: 44px; height: 50px; text-align: center; font-size: 1.4rem; font-weight: 800; background: #09090b; border: 1px solid #3f3f46; border-radius: 10px; color: var(--accent-yellow); outline: none;">
                        <input type="text" maxlength="1" class="otp-digit-input" data-index="4" style="width: 44px; height: 50px; text-align: center; font-size: 1.4rem; font-weight: 800; background: #09090b; border: 1px solid #3f3f46; border-radius: 10px; color: var(--accent-yellow); outline: none;">
                        <input type="text" maxlength="1" class="otp-digit-input" data-index="5" style="width: 44px; height: 50px; text-align: center; font-size: 1.4rem; font-weight: 800; background: #09090b; border: 1px solid #3f3f46; border-radius: 10px; color: var(--accent-yellow); outline: none;">
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; font-size: 0.8rem; color: #a1a1aa;">
                        <span id="resend-timer-text">إعادة الإرسال خلال: <b id="countdown-sec" style="color: var(--accent-yellow);">60</b> ثانية</span>
                        <button type="button" id="btn-resend-otp" onclick="submitSendOtp()" disabled style="background: none; border: none; color: #71717a; font-weight: 700; cursor: not-allowed;">إعادة إرسال الرمز</button>
                    </div>

                    <button type="button" id="btn-verify-otp" onclick="submitVerifyOtp()" class="btn-submit" style="margin-top: 0;">
                        <span>تأكيد الرمز وتغيير كلمة السر</span>
                        <i class="fa-solid fa-check-double"></i>
                    </button>
                </div>

                <!-- الخطوة 3: تعيين كلمة المرور الجديدة -->
                <div id="step-3-form" style="display: none;">
                    <div style="margin-bottom: 1.2rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #e4e4e7; margin-bottom: 0.4rem;">كلمة المرور الجديدة:</label>
                        <div class="input-group">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="new-password-input" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #e4e4e7; margin-bottom: 0.4rem;">تأكيد كلمة المرور الجديدة:</label>
                        <div class="input-group">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input type="password" id="new-password-confirm" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="button" id="btn-save-password" onclick="submitResetPassword()" class="btn-submit" style="margin-top: 0;">
                        <span>تحديث كلمة المرور في النظام</span>
                        <i class="fa-solid fa-floppy-disk"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal إنشاء حساب جديد -->
    <div id="registerModal" class="reset-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(10px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
        <div class="reset-modal-card" style="background: #18181b; border: 1px solid #27272a; border-radius: 20px; width: 100%; max-width: 540px; max-height: 92vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); position: relative; animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            
            <div style="height: 4px; background: linear-gradient(90deg, #facc15, #eab308);"></div>
            
            <div style="padding: 1.8rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="background: rgba(250, 204, 21, 0.15); width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #facc15;">
                            <i class="fa-solid fa-user-plus" style="font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: #fff; font-size: 1.15rem; font-weight: 700;">إنشاء حساب جديد</h3>
                            <p style="margin: 2px 0 0 0; color: #71717a; font-size: 0.8rem;">سجل حساب جديد للوصول إلى المنصة</p>
                        </div>
                    </div>
                    <button onclick="closeRegisterModal()" style="background: rgba(255, 255, 255, 0.05); border: none; color: #a1a1aa; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- اختيار نوع الحساب -->
                <div style="display: flex; background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 4px; margin-bottom: 1.2rem;">
                    <button type="button" id="regTabStudent" onclick="switchRegRole('student')" style="flex: 1; padding: 8px; border: none; border-radius: 9px; background: #facc15; color: #000; font-weight: bold; cursor: pointer; font-family: inherit; transition: all 0.2s;">
                        <i class="fa-solid fa-graduation-cap"></i> طالب
                    </button>
                    <button type="button" id="regTabParent" onclick="switchRegRole('parent')" style="flex: 1; padding: 8px; border: none; border-radius: 9px; background: transparent; color: #a1a1aa; font-weight: bold; cursor: pointer; font-family: inherit; transition: all 0.2s;">
                        <i class="fa-solid fa-users"></i> ولي أمر
                    </button>
                </div>

                <div id="regAlertMsg" style="display: none; padding: 10px 14px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 1rem;"></div>

                <form id="registerWebForm" onsubmit="handleWebRegister(event)">
                    <input type="hidden" id="regRole" value="student">

                    <!-- صورة البروفايل (إجبارية للطالب) -->
                    <div id="avatarContainer" style="text-align: center; margin-bottom: 1.2rem;">
                        <label style="cursor: pointer; display: inline-block; position: relative;">
                            <div style="width: 95px; height: 95px; border-radius: 50%; background: #27272a; border: 3px solid #facc15; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; box-shadow: 0 4px 15px rgba(250, 204, 21, 0.2);">
                                <i id="avatarPlaceholderIcon" class="fa-solid fa-user" style="font-size: 2.5rem; color: #71717a;"></i>
                                <img id="avatarPreviewImg" src="" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="position: absolute; bottom: 0; right: 0; background: #facc15; color: #000; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.5);">
                                <i class="fa-solid fa-camera" style="font-size: 0.85rem;"></i>
                            </div>
                            <input type="file" id="regAvatar" accept="image/*" onchange="previewRegAvatar(event)" style="display: none;">
                        </label>
                        <p style="margin: 6px 0 0 0; color: #ef4444; font-size: 0.78rem; font-weight: 600;">* صورة البروفايل إجبارية</p>
                    </div>

                    <!-- الاسم الأول والاسم الأخير -->
                    <div style="display: flex; gap: 0.8rem; margin-bottom: 0.8rem;">
                        <div style="flex: 1;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">الاسم الأول <span style="color:#ef4444">*</span></label>
                            <input type="text" id="regFirstName" required placeholder="أحمد" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">الاسم الأخير <span style="color:#ef4444">*</span></label>
                            <input type="text" id="regLastName" required placeholder="محمد" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                        </div>
                    </div>

                    <!-- معرف التليغرام -->
                    <div style="margin-bottom: 0.8rem;">
                        <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">معرّف التليغرام (Telegram ID) <span style="color:#ef4444">*</span></label>
                        <input type="text" id="regTelegramId" required value="7821980919" placeholder="7821980919" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                    </div>

                    <!-- الجنس وتاريخ الميلاد -->
                    <div style="display: flex; gap: 0.8rem; margin-bottom: 0.8rem;">
                        <div style="flex: 1;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">الجنس <span style="color:#ef4444">*</span></label>
                            <select id="regGender" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                                <option value="ذكر">ذكر</option>
                                <option value="أنثى">أنثى</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">تاريخ الميلاد (العمر 18 - 23) <span style="color:#ef4444">*</span></label>
                            <input type="date" id="regBirthDate" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 8px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                        </div>
                    </div>

                    <!-- رقم الهاتف والبريد الإلكتروني -->
                    <div style="display: flex; gap: 0.8rem; margin-bottom: 0.8rem;">
                        <div style="flex: 1;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">رقم الهاتف <span style="color:#ef4444">*</span></label>
                            <input type="text" id="regPhone" required placeholder="09xxxxxxx" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">البريد الإلكتروني <span style="color:#ef4444">*</span></label>
                            <input type="email" id="regEmail" required placeholder="example@domain.com" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                        </div>
                    </div>

                    <!-- القسم والدورة / الفرع (خاص بالطالب) -->
                    <div id="studentRegFields">
                        <div style="display: flex; gap: 0.8rem; margin-bottom: 0.8rem;">
                            <div style="flex: 1;">
                                <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">القسم <span style="color:#ef4444">*</span></label>
                                <select id="regDept" onchange="updateRegBranches()" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                                    <option value="نظم معلومات">نظم معلومات</option>
                                    <option value="طبي">طبي</option>
                                    <option value="تجاري">تجاري</option>
                                    <option value="هندسي">هندسي</option>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">الدورة / الفرع <span style="color:#ef4444">*</span></label>
                                <select id="regBranch" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                                    <option value="معلوماتي">معلوماتي</option>
                                    <option value="اتصالات">اتصالات</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- حقول خاصة بولي الأمر -->
                    <div id="parentRegFields" style="display: none; margin-bottom: 0.8rem;">
                        <div style="margin-bottom: 0.8rem;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">عدد الأبناء في الجامعة / المعهد <span style="color:#ef4444">*</span></label>
                            <select id="regChildCount" onchange="updateParentChildFields()" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                                <option value="1">ولد واحد (1)</option>
                                <option value="2">ابنان (2)</option>
                                <option value="3">ثلاثة أبناء (3)</option>
                                <option value="4">أربعة أبناء (4)</option>
                                <option value="5">خمسة أبناء (5)</option>
                            </select>
                        </div>
                        <div id="parentChildIdsContainer">
                            <div style="margin-bottom: 0.6rem;">
                                <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">الرقم الجامعي للابن 1 <span style="color:#ef4444">*</span></label>
                                <input type="text" class="reg-child-id-input" placeholder="مثال: 2026101" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                            </div>
                        </div>
                    </div>

                    <!-- كلمة المرور وتأكيدها -->
                    <div style="display: flex; gap: 0.8rem; margin-bottom: 1.2rem;">
                        <div style="flex: 1;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">كلمة المرور <span style="color:#ef4444">*</span></label>
                            <input type="password" id="regPassword" required placeholder="••••••••" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">تأكيد كلمة المرور <span style="color:#ef4444">*</span></label>
                            <input type="password" id="regConfirmPassword" required placeholder="••••••••" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                        </div>
                    </div>

                    <button type="submit" id="btnSubmitRegister" style="width: 100%; background: #facc15; color: #000; border: none; padding: 11px; border-radius: 10px; font-weight: bold; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; font-family: inherit;">
                        <span>تقديم طلب إنشاء الحساب</span>
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .otp-digit-input:focus {
            border-color: var(--accent-yellow) !important;
            box-shadow: 0 0 10px rgba(250, 204, 21, 0.3) !important;
        }
    </style>

    <script>
        let selectedRole = "{{ $role['key'] ?? 'student' }}";
        if (selectedRole === 'unified') selectedRole = 'student';
        let countdownInterval = null;

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password_input');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        function openResetPasswordModal() {
            document.getElementById('resetPasswordModal').style.display = 'flex';
            selectRoleForReset(selectedRole);
            showStep(1);
        }

        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').style.display = 'none';
            if (countdownInterval) clearInterval(countdownInterval);
        }

        function selectRoleForReset(role) {
            selectedRole = role;
            document.querySelectorAll('.role-tab-btn').forEach(btn => {
                if (btn.dataset.role === role) {
                    btn.style.background = 'var(--accent-yellow)';
                    btn.style.color = '#000';
                } else {
                    btn.style.background = 'transparent';
                    btn.style.color = '#a1a1aa';
                }
            });

            const label = document.getElementById('identifier-label');
            const placeholder = document.getElementById('reset-identifier');
            
            if (role === 'student') {
                label.textContent = 'الرقم الجامعي أو رقم الجوال:';
                placeholder.placeholder = 'أدخل الرقم الجامعي أو رقم الجوال...';
            } else if (role === 'parent') {
                label.textContent = 'رقم الجوال الخاص بولي الأمر:';
                placeholder.placeholder = 'أدخل رقم الجوال...';
            } else {
                label.textContent = 'رقم الجوال أو اسم المستخدم للأستاذ:';
                placeholder.placeholder = 'أدخل الرقم أو البريد...';
            }
        }

        function showStep(stepNum) {
            document.getElementById('step-1-form').style.display = stepNum === 1 ? 'block' : 'none';
            document.getElementById('step-2-form').style.display = stepNum === 2 ? 'block' : 'none';
            document.getElementById('step-3-form').style.display = stepNum === 3 ? 'block' : 'none';
            hideResetAlert();
        }

        function showResetAlert(msg, type = 'error') {
            const box = document.getElementById('reset-alert-box');
            const text = document.getElementById('reset-alert-text');
            const icon = document.getElementById('reset-alert-icon');
            box.style.display = 'flex';
            text.textContent = msg;

            if (type === 'success') {
                box.style.background = 'rgba(34, 197, 94, 0.15)';
                box.style.border = '1px solid rgba(34, 197, 94, 0.3)';
                box.style.color = '#86efac';
                icon.className = 'fa-solid fa-circle-check';
            } else {
                box.style.background = 'rgba(239, 68, 68, 0.15)';
                box.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                box.style.color = '#fca5a5';
                icon.className = 'fa-solid fa-triangle-exclamation';
            }
        }

        function hideResetAlert() {
            document.getElementById('reset-alert-box').style.display = 'none';
        }

        // إرسال كود OTP عبر تليجرام
        async function submitSendOtp() {
            const identifier = document.getElementById('reset-identifier').value.trim();
            const telegramIdentifier = document.getElementById('reset-telegram').value.trim();
            const btn = document.getElementById('btn-send-otp');

            if (!identifier || !telegramIdentifier) {
                showResetAlert('يرجى ملء كافة الحقول المطلوبة (الرقم/الجوال ومعرف تليجرام).');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span>جاري الإرسال عبر تليجرام...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
            hideResetAlert();

            try {
                const response = await fetch("{{ route('password.forgot.send_otp') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        identifier: identifier,
                        telegram_identifier: telegramIdentifier,
                        role: selectedRole
                    })
                });

                const res = await response.json();
                btn.disabled = false;
                btn.innerHTML = '<span>إرسال رمز OTP عبر تليجرام</span> <i class="fa-solid fa-paper-plane"></i>';

                if (res.success) {
                    showStep(2);
                    showResetAlert(res.message, 'success');
                    startCountdownTimer();
                    setupOtpInputs();
                } else {
                    showResetAlert(res.message || 'حدث خطأ أثناء إرسال رمز OTP.');
                }
            } catch (e) {
                btn.disabled = false;
                btn.innerHTML = '<span>إرسال رمز OTP عبر تليجرام</span> <i class="fa-solid fa-paper-plane"></i>';
                showResetAlert('فشل الاتصال بالسيرفر: ' + e.message);
            }
        }

        // إعداد مربعات الإدخال التلقائية لـ OTP (6 أرقام)
        function setupOtpInputs() {
            const inputs = document.querySelectorAll('.otp-digit-input');
            inputs.forEach((input, idx) => {
                input.value = '';
                input.oninput = (e) => {
                    if (input.value && idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                    }
                };
                input.onkeydown = (e) => {
                    if (e.key === 'Backspace' && !input.value && idx > 0) {
                        inputs[idx - 1].focus();
                    }
                };
                input.onpaste = (e) => {
                    e.preventDefault();
                    const pasted = (e.clipboardData || window.clipboardData).getData('text').trim();
                    if (/^\d{6}$/.test(pasted)) {
                        pasted.split('').forEach((char, i) => {
                            if (inputs[i]) inputs[i].value = char;
                        });
                        inputs[5].focus();
                    }
                };
            });
            setTimeout(() => inputs[0].focus(), 100);
        }

        function startCountdownTimer() {
            let left = 60;
            const sec = document.getElementById('countdown-sec');
            const resendBtn = document.getElementById('btn-resend-otp');
            resendBtn.disabled = true;
            resendBtn.style.color = '#71717a';
            resendBtn.style.cursor = 'not-allowed';

            if (countdownInterval) clearInterval(countdownInterval);

            countdownInterval = setInterval(() => {
                left--;
                sec.textContent = left;
                if (left <= 0) {
                    clearInterval(countdownInterval);
                    resendBtn.disabled = false;
                    resendBtn.style.color = 'var(--accent-yellow)';
                    resendBtn.style.cursor = 'pointer';
                }
            }, 1000);
        }

        // التحقق من رمز OTP
        async function submitVerifyOtp() {
            const inputs = document.querySelectorAll('.otp-digit-input');
            let otp = "";
            inputs.forEach(i => otp += i.value.trim());

            if (otp.length !== 6) {
                showResetAlert('يرجى كتابة رمز OTP الكامل المكون من 6 أرقام.');
                return;
            }

            const btn = document.getElementById('btn-verify-otp');
            btn.disabled = true;
            btn.innerHTML = '<span>جاري التحقق من الرمز...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
            hideResetAlert();

            try {
                const response = await fetch("{{ route('password.forgot.verify_otp') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ otp: otp })
                });

                const res = await response.json();
                btn.disabled = false;
                btn.innerHTML = '<span>تأكيد الرمز وتغيير كلمة السر</span> <i class="fa-solid fa-check-double"></i>';

                if (res.success) {
                    showStep(3);
                    showResetAlert(res.message, 'success');
                } else {
                    showResetAlert(res.message || 'رمز OTP غير صحيح.');
                }
            } catch (e) {
                btn.disabled = false;
                btn.innerHTML = '<span>تأكيد الرمز وتغيير كلمة السر</span> <i class="fa-solid fa-check-double"></i>';
                showResetAlert('خطأ في الاتصال بالسيرفر: ' + e.message);
            }
        }

        // تحديث كلمة المرور في النظام
        async function submitResetPassword() {
            const pass = document.getElementById('new-password-input').value;
            const confirm = document.getElementById('new-password-confirm').value;

            if (!pass || pass.length < 6) {
                showResetAlert('كلمة المرور يجب أن لا تقل عن 6 أحرف أو أرقام.');
                return;
            }

            if (pass !== confirm) {
                showResetAlert('تأكيد كلمة المرور غير مطابِق لكلمة المرور المدخلة.');
                return;
            }

            const btn = document.getElementById('btn-save-password');
            btn.disabled = true;
            btn.innerHTML = '<span>جاري تحديث كلمة المرور في قاعدة البيانات...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
            hideResetAlert();

            try {
                const response = await fetch("{{ route('password.forgot.reset') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        password: pass,
                        password_confirmation: confirm
                    })
                });

                const res = await response.json();
                btn.disabled = false;
                btn.innerHTML = '<span>تحديث كلمة المرور في النظام</span> <i class="fa-solid fa-floppy-disk"></i>';

                if (res.success) {
                    showResetAlert(res.message, 'success');
                    setTimeout(() => {
                        closeResetPasswordModal();
                        window.location.reload();
                    }, 2000);
                } else {
                    showResetAlert(res.message || 'حدث خطأ أثناء تحديث كلمة المرور.');
                }
            } catch (e) {
                btn.disabled = false;
                btn.innerHTML = '<span>تحديث كلمة المرور في النظام</span> <i class="fa-solid fa-floppy-disk"></i>';
                showResetAlert('خطأ في الاتصال بالسيرفر: ' + e.message);
            }
        }

        const deptBranchesMap = {
            'نظم معلومات': ['معلوماتي', 'اتصالات'],
            'طبي': ['صيدلة', 'مخابر'],
            'تجاري': ['ادارة اعمال', 'محاسبة'],
            'هندسي': ['هندسة عمارة', 'ديكور']
        };

        function updateRegBranches() {
            const deptSelect = document.getElementById('regDept');
            if (!deptSelect) return;
            const dept = deptSelect.value;
            const branchSelect = document.getElementById('regBranch');
            if (!branchSelect) return;
            const branches = deptBranchesMap[dept] || [];
            branchSelect.innerHTML = '';
            branches.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b;
                opt.textContent = b;
                branchSelect.appendChild(opt);
            });
        }

        function previewRegAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreviewImg').src = e.target.result;
                    document.getElementById('avatarPreviewImg').style.display = 'block';
                    document.getElementById('avatarPlaceholderIcon').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        function updateParentChildFields() {
            const countSelect = document.getElementById('regChildCount');
            if (!countSelect) return;
            const count = parseInt(countSelect.value) || 1;
            const container = document.getElementById('parentChildIdsContainer');
            if (!container) return;
            container.innerHTML = '';
            for (let i = 1; i <= count; i++) {
                const div = document.createElement('div');
                div.style.marginBottom = '0.6rem';
                div.innerHTML = `
                    <label style="display: block; color: #d4d4d8; font-size: 0.8rem; margin-bottom: 4px;">الرقم الجامعي للابن ${i} <span style="color:#ef4444">*</span></label>
                    <input type="text" class="reg-child-id-input" placeholder="مثال: 202610${i}" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; color: #fff; padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; outline: none; font-family: inherit;">
                `;
                container.appendChild(div);
            }
        }

        function openRegisterModal() {
            document.getElementById('registerModal').style.display = 'flex';
            updateRegBranches();
            updateParentChildFields();
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
        }

        function switchRegRole(role) {
            document.getElementById('regRole').value = role;
            const avatarBox = document.getElementById('avatarContainer');
            if (role === 'student') {
                document.getElementById('regTabStudent').style.background = '#facc15';
                document.getElementById('regTabStudent').style.color = '#000';
                document.getElementById('regTabParent').style.background = 'transparent';
                document.getElementById('regTabParent').style.color = '#a1a1aa';
                document.getElementById('studentRegFields').style.display = 'block';
                document.getElementById('parentRegFields').style.display = 'none';
                if (avatarBox) avatarBox.style.display = 'block';
            } else {
                document.getElementById('regTabParent').style.background = '#facc15';
                document.getElementById('regTabParent').style.color = '#000';
                document.getElementById('regTabStudent').style.background = 'transparent';
                document.getElementById('regTabStudent').style.color = '#a1a1aa';
                document.getElementById('studentRegFields').style.display = 'none';
                document.getElementById('parentRegFields').style.display = 'block';
                if (avatarBox) avatarBox.style.display = 'none';
            }
        }

        async function handleWebRegister(e) {
            e.preventDefault();
            const alertBox = document.getElementById('regAlertMsg');
            const btn = document.getElementById('btnSubmitRegister');

            const p1 = document.getElementById('regPassword').value;
            const p2 = document.getElementById('regConfirmPassword').value;

            if (p1 !== p2) {
                alertBox.style.display = 'block';
                alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                alertBox.style.color = '#f87171';
                alertBox.innerText = 'كلمات المرور غير متطابقة!';
                return;
            }

            const role = document.getElementById('regRole').value;
            const fn = document.getElementById('regFirstName').value.trim();
            const ln = document.getElementById('regLastName').value.trim();
            const avatarInput = document.getElementById('regAvatar');

            // 1. التحقق من رفع الصورة (إجبارية للطالب)
            if (role === 'student' && (!avatarInput.files || avatarInput.files.length === 0)) {
                alertBox.style.display = 'block';
                alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                alertBox.style.color = '#f87171';
                alertBox.innerText = 'يرجى تحميل صورة البروفايل (الصورة إجبارية للطالب)';
                return;
            }

            // 2. التحقق من العمر (بين 18 و 23 سنة للطالب)
            if (role === 'student') {
                const birthVal = document.getElementById('regBirthDate').value;
                if (!birthVal) {
                    alertBox.style.display = 'block';
                    alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                    alertBox.style.color = '#f87171';
                    alertBox.innerText = 'يرجى إدخال تاريخ الميلاد';
                    return;
                }
                const birthDate = new Date(birthVal);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) { age--; }

                if (age < 18 || age > 23) {
                    alertBox.style.display = 'block';
                    alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                    alertBox.style.color = '#f87171';
                    alertBox.innerText = `عذراً، يجب أن يكون العمر بين 18 و 23 سنة (العمر الحالي: ${age} سنة).`;
                    return;
                }
            }

            // إعداد الـ FormData لإرسال الصورة والبيانات
            const formData = new FormData();
            formData.append('role', role);
            formData.append('full_name', `${fn} ${ln}`);
            formData.append('first_name', fn);
            formData.append('last_name', ln);
            formData.append('email', document.getElementById('regEmail').value.trim());
            formData.append('phone', document.getElementById('regPhone').value.trim());
            formData.append('telegram_chat_id', document.getElementById('regTelegramId').value.trim());
            formData.append('telegram_username', document.getElementById('regTelegramId').value.trim());
            formData.append('password', p1);

            if (role === 'student') {
                formData.append('gender', document.getElementById('regGender').value);
                formData.append('birth_date', document.getElementById('regBirthDate').value);
                formData.append('department', document.getElementById('regDept').value);
                formData.append('branch', document.getElementById('regBranch').value);
                if (avatarInput.files && avatarInput.files[0]) {
                    formData.append('avatar', avatarInput.files[0]);
                }
            } else {
                const childInputs = document.querySelectorAll('.reg-child-id-input');
                let hasChild = false;
                childInputs.forEach(input => {
                    const val = input.value.trim();
                    if (val) {
                        formData.append('children_ids[]', val);
                        hasChild = true;
                    }
                });
                if (!hasChild) {
                    alertBox.style.display = 'block';
                    alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                    alertBox.style.color = '#f87171';
                    alertBox.innerText = 'يرجى إدخال الرقم الجامعي لكل ابن من الأبناء.';
                    return;
                }
            }

            btn.disabled = true;
            btn.innerText = 'جاري تقديم الطلب...';

            try {
                const res = await fetch('/api/register', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                btn.disabled = false;
                btn.innerHTML = '<span>تقديم طلب إنشاء الحساب</span> <i class="fa-solid fa-arrow-left"></i>';

                if (res.ok || data.success) {
                    alertBox.style.display = 'block';
                    alertBox.style.background = 'rgba(34, 197, 94, 0.15)';
                    alertBox.style.color = '#4ade80';
                    alertBox.innerText = 'تم تقديم طلب إنشاء الحساب بنجاح! يرجى الانتظار لحين اعتماد حسابك من قبل الإدارة.';
                    setTimeout(() => { closeRegisterModal(); }, 3000);
                } else {
                    alertBox.style.display = 'block';
                    alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                    alertBox.style.color = '#f87171';
                    alertBox.innerText = data.message || 'حدث خطأ في تقديم الطلب.';
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<span>تقديم طلب إنشاء الحساب</span> <i class="fa-solid fa-arrow-left"></i>';
                alertBox.style.display = 'block';
                alertBox.style.background = 'rgba(239, 68, 68, 0.15)';
                alertBox.style.color = '#f87171';
                alertBox.innerText = 'عذراً، يتعذر الاتصال بالسيرفر حالياً.';
            }
        }
    </script>
</body>
</html>
