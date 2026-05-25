<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - رئيس القسم</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hod-style.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bg-primary);
            margin: 0;
            font-family: 'Cairo', sans-serif;
        }

        .login-card {
            background-color: var(--bg-secondary);
            border-radius: 1.5rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background-color: #fefce8;
            color: #ca8a04;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }

        .login-card h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .login-card p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: right;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem; /* padding-left for icon */
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--accent-color);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 2.7rem;
            color: var(--text-secondary);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background-color: var(--accent-color);
            color: #1a1a1a;
            border: none;
            border-radius: 1rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.3s;
            font-family: inherit;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-icon">
            <i class="fa-solid fa-user-tie"></i>
        </div>
        <h2>بوابة رئيس القسم</h2>
        <p>قم بتسجيل الدخول للوصول إلى لوحة التحكم</p>

        @if($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('hod.login.submit') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                <input type="text" name="login" class="form-control" placeholder="hod1@example.com" required>
                <i class="fa-solid fa-user input-icon"></i>
            </div>

            <div class="form-group">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <i class="fa-solid fa-lock input-icon"></i>
            </div>

            <button type="submit" class="btn-login">تسجيل الدخول</button>
        </form>
    </div>

</body>
</html>
