<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | الحضور والغياب</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --main-yellow: #f9f21a; 
            --bg-cream: #fcfcf3; 
            --white: #ffffff; 
            --text-gray: #636e72;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; transition: 0.3s; }
        
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: var(--bg-cream); 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
        }

        /* --- Sidebar (الداش بورد الزابطة) --- */
        aside {
            width: 280px; background: var(--white); height: 100vh;
            display: flex; flex-direction: column; padding: 30px 20px;
            border-left: 1px solid #eee; position: fixed; right: 0; top: 0;
            z-index: 100;
        }
        .logo { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 40px; }
        .logo span { color: var(--main-yellow); text-shadow: 1px 1px 0 #000; }
        
        .nav-menu { list-style: none; flex: 1; }
        .nav-link {
            display: flex; align-items: center; gap: 15px; padding: 12px 18px;
            text-decoration: none; color: var(--text-gray); font-weight: 700;
            border-radius: 15px; margin-bottom: 8px; font-size: 0.95rem;
        }
        .nav-link.active { background: var(--main-yellow); color: #000; box-shadow: 0 4px 12px rgba(249, 242, 26, 0.3); }
        .nav-link:hover:not(.active) { background: #f9f9f9; transform: translateX(-5px); }

        /* --- Content Area --- */
        main { 
            margin-right: 280px; 
            flex: 1; 
            height: 100vh;
            padding: 40px; 
            overflow-y: auto; 
            display: flex;
            flex-direction: column;
            align-items: center; 
        }

        header { width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .header-title h1 { font-weight: 900; font-size: 2rem; }
        .header-title p { color: #888; font-weight: 600; font-size: 1rem; }

        .user-pill { display: flex; align-items: center; gap: 12px; background: #fff; padding: 8px 15px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .user-pill img { width: 40px; height: 40px; border-radius: 10px; border: 2px solid var(--main-yellow); }

        /* --- Attendance Card (المحتوى المطلوب) --- */
        .attendance-card {
            background: #fff; 
            width: 100%; 
            max-width: 480px; 
            border-radius: 40px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            text-align: center;
            border-right: 6px solid #ddd; /* نفس ستايل كروت الداش بورد */
        }

        .setup-title { text-align: right; color: #b8860b; font-weight: 900; margin-bottom: 25px; font-size: 1.2rem; }
        .input-group { text-align: right; margin-bottom: 30px; }
        .input-group label { display: block; font-weight: 900; margin-bottom: 12px; font-size: 1rem; color: #333; }
        
        .ui-select {
            width: 100%; padding: 20px; border-radius: 20px; background: #f9f9f9;
            border: 1px solid #eee; font-family: 'Cairo'; font-weight: 700; color: #555; font-size: 1rem;
        }

        .btn-start {
            background: #000; color: #fff; border: none; width: 100%; padding: 20px;
            border-radius: 20px; font-weight: 900; font-size: 1.1rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-start:hover { background: var(--main-yellow); color: #000; }

        /* --- QR View --- */
        #qr-section { display: none; }
        .live-tag {
            background: #e8f5e9; color: #2ecc71; padding: 8px 18px; border-radius: 15px;
            font-weight: 900; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 10px; margin-bottom: 25px;
        }
        .live-tag span { width: 10px; height: 10px; background: #2ecc71; border-radius: 50%; }

        .qr-box {
            width: 260px; height: 260px; background: #000; margin: 20px auto;
            border-radius: 35px; border: 8px dashed var(--main-yellow);
            display: flex; align-items: center; justify-content: center;
        }
        .qr-box img { width: 85%; filter: invert(1); }

        .btn-gray { 
            background: #f5f5f5; border: none; padding: 18px 45px; 
            border-radius: 20px; font-weight: 800; cursor: pointer; 
            margin-top: 20px; font-size: 0.95rem; color: #555;
        }
        
        .cancel-link { color: #ff4757; font-weight: 800; margin-top: 25px; font-size: 0.9rem; cursor: pointer; display: block; }

    </style>
</head>
<body>

    <aside>
        <div class="logo">EDU<span>BRIDGE</span></div>
        <nav class="nav-menu">
            <a href="{{ route('dashboard') }}" class="nav-link"><i class="fa-solid fa-house"></i> الرئيسية</a>
            <a href="{{ route('profile') }}" class="nav-link"><i class="fa-solid fa-user"></i> الملف الشخصي</a>
            <a href="{{ route('messages') }}" class="nav-link"><i class="fa-solid fa-comment"></i> المراسلة</a>
            <a href="{{ route('notifications') }}" class="nav-link"><i class="fa-solid fa-bell"></i> الإشعارات</a>
            <a href="{{ route('schedule') }}" class="nav-link"><i class="fa-solid fa-calendar"></i> الجداول</a>
            <a href="{{ route('assignments') }}" class="nav-link"><i class="fa-solid fa-file"></i> الواجبات</a>
            <a href="{{ route('attendance') }}" class="nav-link active"><i class="fa-solid fa-check"></i> الحضور</a>
            <a href="{{ route('lectures') }}" class="nav-link"><i class="fa-solid fa-play"></i> المحاضرات</a>
        </nav>
    </aside>

    <main>
        <header>
            <div class="header-title">
                <h1>تسجيل الحضور</h1>
                <p>قم بإعداد الجلسة وتوليد رمز الـ QR للطلاب.</p>
            </div>
            <div class="user-pill">
                <div style="text-align: left; font-weight: 900; font-size: 0.85rem;">هبة عيسى</div>
                <img src="https://via.placeholder.com/100">
            </div>
        </header>

        <div class="attendance-card">
            
            <div id="setup-section">
                <p class="setup-title">إعدادات الجلسة</p>
                <div class="input-group">
                    <label>المادة الدراسية</label>
                    <select class="ui-select">
                        <option>اختر المادة الدراسية</option>
                        <option>برمجة تطبيقات Flutter</option>
                        <option>تطوير Backend (Laravel)</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>القاعة / الصف</label>
                    <select class="ui-select">
                        <option>اختر القاعة</option>
                        <option>المختبر البرمجي 1</option>
                        <option>قاعة المحاضرات 4</option>
                    </select>
                </div>
                <button class="btn-start" onclick="startAttendance()">
                    بدء الجلسة <i class="fa-solid fa-play" style="font-size: 0.8rem;"></i>
                </button>
            </div>

            <div id="qr-section">
                <div class="live-tag"><span></span> مباشر • المرحلة 1</div>
                <h2 style="font-weight: 900; font-size: 1.5rem;">رمز الحضور</h2>
                <p style="color: #999; font-size: 0.95rem; font-weight: 700;">اطلب من الطلاب مسح الرمز أدناه</p>
                
                <div class="qr-box">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=EduBridge_Session" alt="QR">
                </div>

                <button class="btn-gray">المرحلة التالية (الغياب)</button>
                <span class="cancel-link" onclick="resetAttendance()">إلغاء الجلسة</span>
            </div>

        </div>
    </main>

    <script>
        function startAttendance() {
            document.getElementById('setup-section').style.display = 'none';
            document.getElementById('qr-section').style.display = 'block';
        }
        function resetAttendance() {
            document.getElementById('qr-section').style.display = 'none';
            document.getElementById('setup-section').style.display = 'block';
        }
    </script>

</body>
</html>