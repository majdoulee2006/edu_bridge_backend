<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | Edu-Bridge</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-yellow: #FFD200;
            --primary-dark: #333333;
            --bg-light: #F8F9FA;
            --white: #FFFFFF;
            --text-muted: #888888;
            --shadow: 0 20px 40px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body {
            background: linear-gradient(135deg, #FFF9C4 0%, #FFFFFF 100%);
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

        .login-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 100px; height: 100px;
            background: var(--primary-yellow);
            border-radius: 0 0 0 100%;
            opacity: 0.1;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .logo span { color: var(--primary-yellow); }

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

        .input-group input, .input-group select {
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

        .input-group input:focus, .input-group select:focus {
            border-color: var(--primary-yellow);
            background: var(--white);
            box-shadow: 0 5px 15px rgba(255, 210, 0, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 1.2rem;
            border-radius: 15px;
            border: none;
            background: var(--primary-yellow);
            color: var(--primary-dark);
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
            box-shadow: 0 10px 20px rgba(255, 210, 0, 0.2);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(255, 210, 0, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Decorative Circles */
        .circle {
            position: absolute;
            z-index: -1;
            border-radius: 50%;
            background: var(--primary-yellow);
            opacity: 0.1;
        }
    </style>
</head>
<body>
    <div class="circle" style="width: 300px; height: 300px; top: -100px; left: -100px;"></div>
    <div class="circle" style="width: 200px; height: 200px; bottom: -50px; right: -50px;"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo">Edu<span>Bridge</span></div>
            <h2>مرحباً بك مجدداً</h2>
            <p>سجل دخولك للوصول إلى لوحة التحكم</p>

            @if($errors->any())
                <div style="background: #FFEBEE; color: #D32F2F; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.85rem; font-weight: 700;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ url('/login') }}" method="POST">

                @csrf
                <div class="input-group">
                    <label>الاسم أو البريد الإلكتروني</label>
                    <input type="text" name="username" placeholder="أدخل اسم المستخدم" required>
                </div>

                <div class="input-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>


                <button type="submit" class="btn-login">تسجيل الدخول</button>
            </form>
        </div>
    </div>
</body>
</html>
