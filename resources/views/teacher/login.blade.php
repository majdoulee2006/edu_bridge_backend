<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu-Bridge | تسجيل دخول المعلم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: var(--bg-primary); }
        .login-wrapper { width: 100%; max-width: 440px; padding: 1rem; }
        .login-card { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2.5rem; box-shadow: 0 20px 60px rgba(0,0,0,0.1); }
        .login-logo { text-align: center; margin-bottom: 2rem; }
        .login-logo .icon { width: 72px; height: 72px; background: var(--accent-color); border-radius: 1.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; }
        .login-logo h1 { font-size: 1.5rem; font-weight: 800; }
        .login-logo p { color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.25rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; }
        .form-input { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; transition: border-color 0.2s; }
        .form-input:focus { outline: none; border-color: var(--accent-color); }
        .btn-login { width: 100%; padding: 0.9rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-size: 1rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: opacity 0.2s; margin-top: 0.5rem; }
        .btn-login:hover { opacity: 0.9; }
        .error-msg { background: hsl(0,70%,95%); color: hsl(0,50%,35%); padding: 0.75rem 1rem; border-radius: 0.75rem; margin-bottom: 1rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <div class="icon"><i class="fa-solid fa-chalkboard-teacher" style="color:#1a1a1a;"></i></div>
                <h1>Edu-Bridge</h1>
                <p>بوابة المعلم</p>
            </div>

            @if ($errors->has('login'))
                <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first('login') }}</div>
            @endif

            <form action="{{ route('teacher.login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني أو رقم الهاتف</label>
                    <input type="text" name="login" class="form-input" placeholder="example@edu.com أو 09..." value="{{ old('login') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول
                </button>
            </form>
        </div>
    </div>
    <script src="{{ asset('js/hod-settings.js') }}"></script>
</body>
</html>
