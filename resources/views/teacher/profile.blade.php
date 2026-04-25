<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | الملف الشخصي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --main-yellow: #f9f21a; --bg-cream: #fcfcf3; --white: #ffffff; --text-gray: #636e72; --text-muted: #a2a2a2; }
        * { box-sizing: border-box; margin: 0; padding: 0; transition: all 0.3s ease; }
        body { font-family: 'Cairo', sans-serif; background-color: var(--bg-cream); display: flex; min-height: 100vh; overflow-x: hidden; }

        aside {
            width: 280px; background: var(--white); height: 100vh;
            display: flex; flex-direction: column; padding: 30px 20px;
            border-left: 1px solid #eee; position: fixed; right: 0; top: 0; 
            z-index: 9999;
        }
        .logo { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 40px; color: #000; text-decoration: none; }
        .logo span { color: var(--main-yellow); text-shadow: 1px 1px 0 #000; }
        
        nav { display: flex; flex-direction: column; gap: 5px; flex-grow: 1; overflow-y: auto; }
        .nav-link {
            display: flex; align-items: center; gap: 15px; padding: 12px 18px;
            text-decoration: none; color: var(--text-gray); font-weight: 700; border-radius: 15px;
            cursor: pointer; position: relative; z-index: 10000;
        }
        .nav-link:hover { background: #fdfdf0; color: #000; }
        .nav-link.active { background: var(--main-yellow); color: #000; box-shadow: 0 4px 12px rgba(249, 242, 26, 0.3); }

        main { margin-right: 280px; flex: 1; padding: 40px; position: relative; z-index: 1; }

        .profile-header {
            background: var(--white); padding: 40px 30px; border-radius: 30px;
            display: flex; flex-direction: column; align-items: center; text-align: center;
            margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.01);
        }
        .profile-img-container { position: relative; margin-bottom: 15px; }
        .profile-img { width: 130px; height: 130px; border-radius: 50%; border: 5px solid var(--main-yellow); object-fit: cover; }
        .verify-icon { position: absolute; bottom: 5px; left: 10px; background: var(--main-yellow); border-radius: 50%; padding: 6px; font-size: 0.8rem; border: 3px solid #fff; color: #000; }

        .info-sections { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .section-title { font-weight: 900; margin-bottom: 20px; color: var(--text-muted); font-size: 1rem; }

        .info-card {
            background: var(--white); padding: 15px 20px; border-radius: 20px;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.01);
        }
        .info-detail { display: flex; align-items: center; gap: 15px; }
        .info-icon-box { width: 45px; height: 45px; border-radius: 15px; background: #fffdf0; color: #000; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .info-text label { display: block; font-size: 0.75rem; color: #aaa; font-weight: 700; }
        .info-text p { font-weight: 800; font-size: 0.95rem; color: #000; }
        
        .edit-pen-btn { color: var(--main-yellow); cursor: pointer; background: none; border: none; font-size: 1rem; outline: none; }
        .tag { background: #f9f9f9; padding: 6px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; border: 1px solid #eee; margin-left: 5px; }

        .btn-bottom {
            grid-column: span 2; background: #f7f7f7; border: 1px solid #eee; padding: 18px; border-radius: 20px; 
            font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 10px;
            cursor: pointer; width: 100%; color: #333; margin-top: 10px; outline: none;
        }

        .custom-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
            display: none; align-items: center; justify-content: center; z-index: 10000;
        }
        .overlay-card { background: var(--white); width: 420px; padding: 40px; border-radius: 35px; position: relative; }
        .close-icon { position: absolute; top: 25px; left: 25px; cursor: pointer; color: #ccc; font-size: 1.3rem; }
        .btn-action-yellow { width: 100%; padding: 16px; border: none; border-radius: 18px; font-weight: 900; background: var(--main-yellow); cursor: pointer; margin-top: 10px; }
        
        input { width: 100%; padding: 15px; border-radius: 15px; border: 1px solid #ddd; outline: none; margin-bottom: 15px; font-family: 'Cairo'; }
    </style>
</head>
<body>

    <aside>
        <div class="logo">EDU<span>BRIDGE</span></div>
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i> الرئيسية
            </a>
            <a href="{{ route('profile') }}" class="nav-link {{ request()->is('profile') ? 'active' : '' }}">
                <i class="fa-solid fa-user"></i> الملف الشخصي
            </a>
            <a href="{{ route('messages') }}" class="nav-link {{ request()->is('messages*') ? 'active' : '' }}">
                <i class="fa-solid fa-comment"></i> المراسلة
            </a>
            <a href="{{ route('notifications') }}" class="nav-link {{ request()->is('notifications*') ? 'active' : '' }}">
                <i class="fa-solid fa-bell"></i> الإشعارات
            </a>
            <a href="{{ route('schedule') }}" class="nav-link {{ request()->is('schedule*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar"></i> الجداول
            </a>
            <a href="{{ route('assignments') }}" class="nav-link {{ request()->is('assignments*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-signature"></i> الواجبات
            </a>
            <a href="{{ route('attendance') }}" class="nav-link {{ request()->is('attendance*') ? 'active' : '' }}">
                <i class="fa-solid fa-clipboard-user"></i> الحضور
            </a>
            <a href="{{ route('lectures') }}" class="nav-link {{ request()->is('lectures*') ? 'active' : '' }}">
                <i class="fa-solid fa-chalkboard-user"></i> المحاضرات
            </a>

            <form action="{{ route('logout') }}" method="POST" style="margin-top: auto;">
                @csrf
                <button type="submit" class="nav-link" style="width: 100%; border: none; background: none; text-align: right; cursor: pointer; outline: none;">
                    <i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج
                </button>
            </form>
        </nav>
    </aside>

    <main>
        <div class="profile-header">
            <div class="profile-img-container">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->full_name) }}&background=f9f21a&color=000" class="profile-img">
                <div class="verify-icon"><i class="fa-solid fa-check"></i></div>
            </div>
            <h2 style="font-weight: 900;">{{ Auth::user()->full_name }}</h2>
            <p style="color: #bfa100; font-weight: 700;">معلم في إيدو بريدج</p>
        </div>

        <div class="info-sections">
            <div>
                <h3 class="section-title">البيانات الشخصية</h3>
                <div class="info-card">
                    <div class="info-detail">
                        <div class="info-icon-box"><i class="fa-solid fa-phone"></i></div>
                        <div class="info-text"><label>رقم الهاتف</label><p id="display-phone">{{ Auth::user()->phone }}</p></div>
                    </div>
                    <button class="edit-pen-btn" onclick="openOverlay('editOverlay')"><i class="fa-solid fa-pen"></i></button>
                </div>
                <div class="info-card">
                    <div class="info-detail">
                        <div class="info-icon-box"><i class="fa-solid fa-envelope"></i></div>
                        <div class="info-text"><label>البريد الإلكتروني</label><p id="display-email">{{ Auth::user()->email }}</p></div>
                    </div>
                    <button class="edit-pen-btn" onclick="openOverlay('editOverlay')"><i class="fa-solid fa-pen"></i></button>
                </div>
            </div>

            <div>
                <h3 class="section-title">البيانات الأكاديمية</h3>
                <div class="info-card">
                    <div class="info-detail">
                        <div class="info-icon-box"><i class="fa-solid fa-building"></i></div>
                        <div class="info-text"><label>القسم</label><p>قسم تكنولوجيا المعلومات</p></div>
                    </div>
                    <i class="fa-solid fa-lock" style="color: #eee;"></i>
                </div>
                <div class="info-card" style="display: block;">
                    <div class="info-detail" style="margin-bottom: 10px;">
                        <div class="info-icon-box"><i class="fa-solid fa-book"></i></div>
                        <div class="info-text"><label>المواد المسؤولة</label><p>قائمة المواد</p></div>
                    </div>
                    <div><span class="tag">Laravel</span><span class="tag">Flutter</span></div>
                </div>
            </div>

            <button class="btn-bottom" onclick="openOverlay('passwordOverlay')">
                <i class="fa-solid fa-shield-halved"></i> تغيير كلمة المرور
            </button>
        </div>
    </main>

    <div id="editOverlay" class="custom-overlay">
        <div class="overlay-card">
            <i class="fa-solid fa-xmark close-icon" onclick="closeOverlays()"></i>
            <h3 style="text-align: center; font-weight: 900; margin-bottom: 20px;">تعديل البيانات</h3>
            <input type="email" id="input-email" value="{{ Auth::user()->email }}" placeholder="البريد الإلكتروني">
            <input type="text" id="input-phone" value="{{ Auth::user()->phone }}" placeholder="رقم الهاتف">
            <button class="btn-action-yellow" onclick="updateProfile()">تأكيد التعديل</button>
        </div>
    </div>

    <div id="passwordOverlay" class="custom-overlay">
        <div class="overlay-card">
            <i class="fa-solid fa-xmark close-icon" onclick="closeOverlays()"></i>
            <h3 style="text-align: center; font-weight: 900; margin-bottom: 20px;">تغيير كلمة المرور</h3>
            <input type="password" id="current_password" placeholder="كلمة المرور الحالية">
            <input type="password" id="new_password" placeholder="كلمة المرور الجديدة">
            <button class="btn-action-yellow" onclick="updatePassword()">تحديث كلمة المرور</button>
        </div>
    </div>

    <script>
        function openOverlay(id) { document.getElementById(id).style.display = 'flex'; }
        function closeOverlays() {
            document.getElementById('editOverlay').style.display = 'none';
            document.getElementById('passwordOverlay').style.display = 'none';
        }

        async function updateProfile() {
            const email = document.getElementById('input-email').value;
            const phone = document.getElementById('input-phone').value;

            const response = await fetch("{{ route('profile.update') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email, phone: phone })
            });

            const result = await response.json();
            alert(result.message);
            if(result.success) {
                document.getElementById('display-email').innerText = email;
                document.getElementById('display-phone').innerText = phone;
                closeOverlays();
            }
        }

        async function updatePassword() {
            const current = document.getElementById('current_password').value;
            const newPass = document.getElementById('new_password').value;

            const response = await fetch("{{ route('profile.password') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ current_password: current, new_password: newPass })
            });

            const result = await response.json();
            alert(result.message);
            if(result.success) closeOverlays();
        }
    </script>
</body>
</html>