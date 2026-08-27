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

                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                    <input type="checkbox" name="remember" id="remember" style="accent-color: var(--accent-yellow); width: 16px; height: 16px; cursor: pointer;">
                    <label for="remember" style="color: #e4e4e7; font-size: 0.9rem; cursor: pointer; user-select: none;">تذكرني (Remember Me)</label>
                </div>

                <button type="submit" class="btn-submit">
                    تسجيل الدخول
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
            </form>
            
        </div>
        
        <div class="footer">
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
