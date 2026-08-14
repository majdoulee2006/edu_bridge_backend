<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduBridge - بوابة تسجيل الدخول الموحدة</title>
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
            max-width: 480px;
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
            width: 64px;
            height: 64px;
            background: rgba(242, 242, 13, 0.1);
            border: 1px solid rgba(242, 242, 13, 0.3);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            color: var(--accent-yellow);
            font-size: 1.8rem;
            box-shadow: 0 0 20px rgba(242, 242, 13, 0.15);
        }

        .brand-title {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin-bottom: 0.2rem;
        }

        .brand-title span {
            color: var(--accent-yellow);
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 600;
        }

        /* Portal Tabs (Staff vs Students/Parents) */
        .portal-tabs {
            display: flex;
            background: #18181b;
            padding: 4px;
            border-radius: 14px;
            border: 1px solid var(--border-dark);
            margin-bottom: 1.8rem;
            gap: 4px;
        }

        .tab-btn {
            flex: 1;
            padding: 0.65rem 0.5rem;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .tab-btn.active {
            background: var(--accent-yellow);
            color: #000000;
            box-shadow: 0 4px 12px rgba(242, 242, 13, 0.2);
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

        .form-control, .form-select {
            width: 100%;
            background-color: var(--surface-input);
            border: 1px solid var(--border-dark);
            border-radius: 14px;
            padding: 0.9rem 2.8rem 0.9rem 1.1rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s;
            appearance: none;
        }

        .form-select {
            padding-left: 2.5rem;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%27a1a1aa%27'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 1rem center;
            background-size: 1.2rem;
        }

        .form-control:focus, .form-select:focus {
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

        option {
            background-color: #18181b;
            color: #ffffff;
            padding: 0.5rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-header">
            <div class="logo-icon-wrapper">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h1 class="brand-title">Edu<span>Bridge</span></h1>
            <p class="subtitle">بوابة تسجيل الدخول الموحدة للأنظمة</p>
        </div>

        @if (session('success'))
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Portal Tabs (Staff vs Students/Parents) -->
        <div class="portal-tabs">
            <button type="button" id="tab-staff" class="tab-btn active" onclick="switchPortal('staff')">
                <i class="fa-solid fa-user-shield"></i>
                <span>الكادر والإدارة</span>
            </button>
            <button type="button" id="tab-students" class="tab-btn" onclick="switchPortal('students')">
                <i class="fa-solid fa-users"></i>
                <span>الطلاب والأهل</span>
            </button>
        </div>

        <form action="{{ url('/login') }}" method="POST" id="loginForm">
            @csrf

            <!-- Hidden input for tracking portal mode -->
            <input type="hidden" name="portal_mode" id="portal_mode" value="staff">

            <!-- Role Selector Dropdown -->
            <div class="form-group">
                <label class="form-label" id="role-label">الصفة الإدارية (Role)</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user-tag input-icon"></i>
                    <select name="role_type" id="role_type" class="form-select">
                        <!-- Staff Options (Default) -->
                        <optgroup label="الكادر الإداري والتعليمي" id="optgroup-staff">
                            <option value="affairs" {{ old('role_type') == 'affairs' ? 'selected' : '' }}>📋 موظف شؤون الطلاب (Affairs)</option>
                            <option value="hod" {{ old('role_type') == 'hod' ? 'selected' : '' }}>🎓 رئيس قسم أكاديمي (HOD)</option>
                            <option value="teacher" {{ old('role_type') == 'teacher' ? 'selected' : '' }}>👨‍🏫 عضو كادر تدريسي (Teacher)</option>
                            <option value="admin" {{ old('role_type') == 'admin' ? 'selected' : '' }}>👑 مدير النظام (Admin)</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- Login Input -->
            <div class="form-group">
                <label class="form-label" id="input-label">بيانات الحساب</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-id-card input-icon" id="input-icon"></i>
                    <input type="text" 
                           name="login" 
                           id="login_input"
                           class="form-control" 
                           placeholder="اسم المستخدم أو البريد الإلكتروني" 
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
        const staffOptions = `
            <option value="affairs">📋 موظف شؤون الطلاب (Affairs)</option>
            <option value="hod">🎓 رئيس قسم أكاديمي (HOD)</option>
            <option value="teacher">👨‍🏫 عضو كادر تدريسي (Teacher)</option>
            <option value="admin">👑 مدير النظام (Admin)</option>
        `;

        const studentOptions = `
            <option value="student">🎓 طالب (Student)</option>
            <option value="parent">👨‍👩‍👧 ولي أمر (Parent)</option>
        `;

        function switchPortal(mode) {
            const tabStaff = document.getElementById('tab-staff');
            const tabStudents = document.getElementById('tab-students');
            const roleSelect = document.getElementById('role_type');
            const roleLabel = document.getElementById('role-label');
            const inputLabel = document.getElementById('input-label');
            const loginInput = document.getElementById('login_input');
            const portalMode = document.getElementById('portal_mode');

            portalMode.value = mode;

            if (mode === 'staff') {
                tabStaff.classList.add('active');
                tabStudents.classList.remove('active');

                roleLabel.textContent = 'الصفة الإدارية (Role)';
                roleSelect.innerHTML = staffOptions;
                inputLabel.textContent = 'بيانات الحساب (اسم المستخدم / البريد / الهاتف)';
                loginInput.placeholder = 'أدخل اسم المستخدم أو البريد الإلكتروني';
            } else {
                tabStudents.classList.add('active');
                tabStaff.classList.remove('active');

                roleLabel.textContent = 'صفة المستفيد (Role)';
                roleSelect.innerHTML = studentOptions;
                inputLabel.textContent = 'الرقم الجامعي / الهاتف / البريد الإلكتروني';
                loginInput.placeholder = 'أدخل الرقم الجامعي أو الهاتف أو البريد الإلكتروني';
            }
        }

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

        // Restore active tab based on query param or old input if validation failed
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const portalParam = urlParams.get('portal') || urlParams.get('type') || urlParams.get('role');
            const oldRole = "{{ old('role_type') }}";

            if (portalParam === 'student' || portalParam === 'students' || portalParam === 'parent' || oldRole === 'student' || oldRole === 'parent') {
                switchPortal('students');
                if (portalParam === 'student' || portalParam === 'parent') {
                    document.getElementById('role_type').value = portalParam;
                } else if (oldRole) {
                    document.getElementById('role_type').value = oldRole;
                }
            } else if (portalParam || oldRole) {
                switchPortal('staff');
                if (['affairs','hod','teacher','admin'].includes(portalParam)) {
                    document.getElementById('role_type').value = portalParam;
                } else if (oldRole) {
                    document.getElementById('role_type').value = oldRole;
                }
            }
        });
    </script>

</body>
</html>
