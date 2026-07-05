<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | الإدارة العامة</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-yellow: #f2f20d;
            --primary-dark: #1a2633;
            --white: #FFFFFF;
            --text-muted: #888888;
            --shadow: 0 20px 40px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif; }

        body {
            background: linear-gradient(135deg, #fffde6 0%, #FFFFFF 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            animation: fadeIn 0.8s ease;
        }

        .login-card {
            background: var(--white);
            border-radius: 30px;
            padding: 3rem;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .logo span { color: var(--primary-yellow); }
        .role-badge {
            background: var(--primary-dark);
            color: var(--primary-yellow);
            padding: 0.2rem 1.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        h2 { font-weight: 800; margin-bottom: 0.5rem; color: var(--primary-dark); }
        p { color: var(--text-muted); margin-bottom: 2.5rem; font-size: 0.9rem; }

        .input-group {
            text-align: right;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--primary-dark);
        }

        .input-group input {
            width: 100%;
            padding: 1.2rem;
            border-radius: 15px;
            border: 2px solid #F0F0F0;
            background: #F9F9F9;
            outline: none;
            transition: var(--transition);
            font-size: 1rem;
            font-weight: 600;
        }

        .input-group input:focus {
            border-color: var(--primary-yellow);
            background: var(--white);
            box-shadow: 0 5px 15px rgba(242, 242, 13, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 1.2rem;
            border-radius: 15px;
            border: none;
            background: var(--primary-yellow);
            color: var(--primary-content);
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
            box-shadow: 0 10px 20px rgba(242, 242, 13, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(242, 242, 13, 0.4);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-left: 3rem !important;
        }

        .toggle-password {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #888;
            font-size: 1.1rem;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }

        .toggle-password:hover { color: #1a2633; }    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">Edu<span>Bridge</span></div>
            <div class="role-badge">بوابة الإدارة العامة</div>
            <h2>مرحباً بك مجدداً</h2>
            <p>سجل دخولك للوصول إلى لوحة التحكم الإدارية</p>

            <form action="{{ route('admin.login.submit') }}" method="POST">
                @csrf

                {{-- رسائل الخطأ --}}
                @if($errors->any())
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#b91c1c; padding:1rem; border-radius:0.8rem; margin-bottom:1.2rem; font-weight:700; font-size:0.9rem; text-align:right;">
                    <i class="fa-solid fa-circle-exclamation" style="margin-left:0.5rem;"></i>
                    {{ $errors->first() }}
                </div>
                @endif

                <div class="input-group">
                    <label>اسم المستخدم أو البريد الإلكتروني</label>
                    <input type="text" name="login" value="{{ old('login') }}" placeholder="أدخل اسم المستخدم أو البريد" required>
                </div>

                <div class="input-group">
                    <label>كلمة المرور</label>
                    <div class="password-wrapper"><input type="password" name="password" id="passwordField" placeholder="••••••••" required><button type="button" class="toggle-password" onclick="togglePassword()"><i class="fa-regular fa-eye" id="eyeIcon"></i></button></div>
                </div>

                <button type="submit" class="btn-login">تسجيل الدخول</button>
            </form>
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
    </script></body>
</html>

