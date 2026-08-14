<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $role['title'] ?? 'EduBridge - تسجيل الدخول' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-pitch-black: #000000;
            --surface-card: #121212;
            --surface-input: #1a1a1a;
            --border-dark: #262626;
            --accent-yellow: #f2f20d;
            --accent-hover: #dada0b;
            --text-primary: #ffffff;
            --text-muted: #a1a1aa;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: var(--bg-pitch-black);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border-dark);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f2f20d, #eab308, #f2f20d);
        }

        .logo-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon-wrapper {
            width: 68px;
            height: 68px;
            background: rgba(242, 242, 13, 0.1);
            border: 1px solid rgba(242, 242, 13, 0.3);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            color: var(--accent-yellow);
            font-size: 1.9rem;
            box-shadow: 0 0 20px rgba(242, 242, 13, 0.15);
        }

        .brand-title {
            font-size: 1.9rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin-bottom: 0.3rem;
        }

        .brand-title span {
            color: var(--accent-yellow);
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(242, 242, 13, 0.08);
            border: 1px solid rgba(242, 242, 13, 0.2);
            color: var(--accent-yellow);
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-top: 0.4rem;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.92rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 700;
            color: #e4e4e7;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            right: 1.1rem;
            color: var(--text-muted);
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            background-color: var(--surface-input);
            border: 1px solid var(--border-dark);
            border-radius: 14px;
            padding: 0.9rem 2.8rem 0.9rem 1.1rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent-yellow);
            box-shadow: 0 0 0 3px rgba(242, 242, 13, 0.15);
        }

        .password-toggle {
            position: absolute;
            left: 1rem;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0.2rem;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--accent-yellow);
            color: #000000;
            border: none;
            border-radius: 14px;
            padding: 1rem;
            font-size: 1.05rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            margin-top: 1.5rem;
            box-shadow: 0 10px 20px -5px rgba(242, 242, 13, 0.25);
        }

        .btn-submit:hover {
            background-color: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -5px rgba(242, 242, 13, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Alerts */
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 0.9rem 1.1rem;
            border-radius: 14px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
            padding: 0.9rem 1.1rem;
            border-radius: 14px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .footer-note {
            text-align: center;
            margin-top: 1.8rem;
            color: #71717a;
            font-size: 0.82rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-header">
            <div class="logo-icon-wrapper">
                <i class="fa-solid {{ $role['icon'] ?? 'fa-graduation-cap' }}"></i>
            </div>
            <h1 class="brand-title">Edu<span>Bridge</span></h1>
            
            @if(isset($role['badge']))
                <div class="role-badge">
                    <i class="fa-solid {{ $role['icon'] ?? 'fa-user' }}"></i>
                    <span>{{ $role['badge'] }}</span>
                </div>
            @endif
            
            <p class="subtitle">{{ $role['title'] ?? 'بوابة تسجيل الدخول الموحدة' }}</p>
        </div>

        @if (session('success'))
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert-danger" style="background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3); color: #fcd34d;">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ url('/login') }}" method="POST" id="loginForm">
            @csrf

            <!-- Hidden input for tracking role route -->
            <input type="hidden" name="role_key" value="{{ $role['key'] ?? 'unified' }}">

            <!-- Login Input -->
            <div class="form-group">
                <label class="form-label">بيانات الحساب</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" 
                           name="login" 
                           class="form-control" 
                           placeholder="{{ ($role['key'] ?? '') === 'student' ? 'أدخل الرقم الجامعي أو اسم المستخدم' : 'أدخل اسم المستخدم، البريد الإلكتروني، أو الهاتف' }}" 
                           value="{{ old('login') }}" 
                           required 
                           autofocus>
                </div>
            </div>

            <!-- Password Input -->
            <div class="form-group">
                <label class="form-label">كلمة المرور</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" 
                           name="password" 
                           id="password_input" 
                           class="form-control" 
                           placeholder="••••••••" 
                           required>
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility()">
                        <i class="fa-solid fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>تسجيل الدخول</span>
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </form>

        <div class="footer-note">
            جميع الحقوق محفوظة &copy; {{ date('Y') }} EduBridge System
        </div>
    </div>

    <script>
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
    </script>

</body>
</html>
