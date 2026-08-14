<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول الموحد | Edu-Bridge</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-yellow: #f2f20d;
            --yellow-hover: #d9d905;
            --bg-dark: #000000;
            --card-dark: #121212;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border-color: #242424;
            --shadow: 0 20px 50px rgba(0,0,0,0.8);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif; }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(242, 242, 13, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(242, 242, 13, 0.03) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--text-main);
            position: relative;
            overflow-x: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: var(--card-dark);
            border: 1px solid var(--border-color);
            border-radius: 1.75rem;
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .brand-header {
            margin-bottom: 2rem;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: rgba(242, 242, 13, 0.1);
            border: 1px solid rgba(242, 242, 13, 0.3);
            border-radius: 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-yellow);
            margin-bottom: 1rem;
            box-shadow: 0 0 25px rgba(242, 242, 13, 0.15);
        }

        .logo-title {
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .logo-title span { color: var(--primary-yellow); }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 0.3rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 0.85rem 1rem;
            border-radius: 0.85rem;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-align: right;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            padding: 0.85rem 1rem;
            border-radius: 0.85rem;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-align: right;
        }

        .input-group {
            text-align: right;
            margin-bottom: 1.25rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--text-main);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            color: var(--text-muted);
            font-size: 1.1rem;
            pointer-events: none;
            transition: var(--transition);
        }

        .input-control {
            width: 100%;
            padding: 0.95rem 2.8rem 0.95rem 2.8rem;
            border-radius: 0.85rem;
            border: 1.5px solid var(--border-color);
            background: #181818;
            color: var(--text-main);
            outline: none;
            transition: var(--transition);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .input-control:focus {
            border-color: var(--primary-yellow);
            background: #1f1f1f;
            box-shadow: 0 0 15px rgba(242, 242, 13, 0.15);
        }

        .input-wrapper:focus-within .input-icon {
            color: var(--primary-yellow);
        }

        .toggle-password {
            position: absolute;
            left: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1.1rem;
            padding: 0.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .toggle-password:hover { color: var(--primary-yellow); }

        .btn-login {
            width: 100%;
            padding: 1rem;
            border-radius: 0.85rem;
            border: none;
            background: var(--primary-yellow);
            color: #000000;
            font-size: 1.05rem;
            font-weight: 800;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.75rem;
            box-shadow: 0 4px 20px rgba(242, 242, 13, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .btn-login:hover {
            background: var(--yellow-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(242, 242, 13, 0.3);
        }

        .footer-note {
            margin-top: 1.75rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="brand-header">
                <div class="brand-icon">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div class="logo-title">Edu<span>Bridge</span></div>
                <p class="subtitle">بوابة تسجيل الدخول الموحدة للنظام</p>
            </div>

            @if($errors->any())
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ url('/login') }}" method="POST">
                @csrf
                <div class="input-group">
                    <label>الرقم الجامعي / البريد الإلكتروني / الهاتف / اسم المستخدم</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" name="login" class="input-control" value="{{ old('login') }}" placeholder="أدخل بيانات معرفك..." required autofocus>
                    </div>
                </div>

                <div class="input-group">
                    <label>كلمة المرور</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="password" id="passwordField" class="input-control" placeholder="••••••••" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()" title="إظهار/إخفاء كلمة المرور">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>تسجيل الدخول</span>
                </button>
            </form>

            <div class="footer-note">
                منصة Edu-Bridge الأكاديمية © {{ date('Y') }}
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const field = document.getElementById('passwordField');
            const icon  = document.getElementById('eyeIcon');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
