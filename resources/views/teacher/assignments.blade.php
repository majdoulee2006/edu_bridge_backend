<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | الواجبات والمشاريع</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* أضيفي هذا الكود داخل وسم الـ <style> في كل صفحاتك */
body.dark-mode {
    background-color: #1a1a1a !important; /* لون الخلفية الأساسي */
    color: #ffffff !important; /* لون الخط */
}

/* لتحويل الصناديق والقوائم للون غامق */
body.dark-mode aside, 
body.dark-mode main, 
body.dark-mode .card, 
body.dark-mode div[class*="card"], 
body.dark-mode .search-bar {
    background-color: #2d2d2d !important;
    color: #ffffff !important;
    border-color: #3d3d3d !important;
}

/* لضمان ظهور النصوص باللون الأبيض */
body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, 
body.dark-mode h4, body.dark-mode p, body.dark-mode span {
    color: #ffffff !important;
}

/* الروابط في القائمة الجانبية */
body.dark-mode .nav-link:not(.active) {
    color: #b0b0b0 !important;
}
        :root { --main-yellow: #f9f21a; --bg-cream: #fcfcf3; --white: #ffffff; }
        * { box-sizing: border-box; margin: 0; padding: 0; transition: 0.3s; }
        body { font-family: 'Cairo', sans-serif; background-color: var(--bg-cream); display: flex; height: 100vh; overflow: hidden; }

        /* --- Sidebar --- */
        aside {
            width: 280px; background: var(--white); height: 100vh;
            display: flex; flex-direction: column; padding: 30px 20px;
            border-left: 1px solid #eee; position: fixed; right: 0; top: 0;
            z-index: 100;
        }
        .logo { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 40px; }
        .logo span { color: var(--main-yellow); text-shadow: 1px 1px 0 #000; }
        .nav-link {
            display: flex; align-items: center; gap: 15px; padding: 12px 18px;
            text-decoration: none; color: #636e72; font-weight: 700; border-radius: 15px; margin-bottom: 8px;
        }
        .nav-link.active { background: var(--main-yellow); color: #000; box-shadow: 0 4px 12px rgba(249, 242, 26, 0.3); }

        /* --- Main Content --- */
        main { margin-right: 280px; flex: 1; height: 100vh; padding: 40px; overflow-y: auto; }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .tabs { display: flex; gap: 30px; border-bottom: 2px solid #eee; margin-bottom: 30px; }
        .tab-item { padding: 10px 5px; cursor: pointer; font-weight: 800; color: #aaa; position: relative; }
        .tab-item.active { color: #000; }
        .tab-item.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 3px; background: var(--main-yellow); }

        /* Assignment Cards */
        .assignment-card {
            background: #fff; border-radius: 30px; padding: 25px;
            margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.01);
            border: 2px solid transparent; cursor: pointer;
        }
        .assignment-card.active-hw { border-color: var(--main-yellow); }
        .card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .card-main { display: flex; align-items: center; gap: 15px; }
        .icon-box { width: 55px; height: 55px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .bg-yellow { background: #fffdf0; color: #b8860b; }
        .bg-blue { background: #e3f2fd; color: #2196f3; }
        .bg-orange { background: #fff3e0; color: #e67e22; }
        .bg-green { background: #e8f5e9; color: #2ecc71; }
        .status-badge { padding: 4px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; display: inline-block; margin-bottom: 5px; }
        .status-active { background: #fff9c4; color: #827717; }
        .status-review { background: #ffe0b2; color: #e65100; }
        .status-done { background: #c8e6c9; color: #2e7d32; }
        .title-area h3 { font-weight: 900; font-size: 1.15rem; color: #2d3436; }
        .title-area p { color: #aaa; font-size: 0.85rem; font-weight: 700; }
        .card-bottom { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #fcfcfc; }
        .meta-info { display: flex; gap: 20px; font-size: 0.85rem; color: #888; font-weight: 700; }
        .meta-info span i { margin-left: 5px; color: #ccc; }

        .btn-plus {
            position: fixed; bottom: 40px; left: 40px;
            width: 65px; height: 65px; background: var(--main-yellow);
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; box-shadow: 0 10px 20px rgba(249, 242, 26, 0.4);
            border: none; cursor: pointer; z-index: 1000;
        }

        /* --- MODAL STYLES (نفس الصورة) --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); display: none; align-items: center;
            justify-content: center; z-index: 2000; backdrop-filter: blur(4px);
        }
        .modal-card {
            background: #fff; width: 90%; max-width: 450px;
            border-radius: 40px; padding: 30px; position: relative;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .modal-header h2 { font-weight: 900; font-size: 1.4rem; flex: 1; text-align: center; }
        .modal-header i { cursor: pointer; font-size: 1.2rem; color: #333; }

        .form-group { margin-bottom: 20px; text-align: right; }
        .form-group label { display: block; font-weight: 800; margin-bottom: 8px; font-size: 0.9rem; color: #333; }
        
        .form-input {
            width: 100%; padding: 15px 20px; border-radius: 20px; background: #f9f9f9;
            border: 1px solid #eee; font-family: 'Cairo'; font-weight: 700; outline: none;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .file-upload {
            border: 2px dashed #ddd; padding: 15px; border-radius: 20px;
            text-align: center; cursor: pointer; color: #888; font-weight: 700; margin-top: 10px;
        }
        .file-upload i { margin-left: 10px; }

        .btn-submit {
            background: var(--main-yellow); color: #000; border: none; width: 100%;
            padding: 18px; border-radius: 25px; font-weight: 900; font-size: 1.1rem;
            cursor: pointer; margin-top: 25px; box-shadow: 0 4px 15px rgba(249, 242, 26, 0.3);
        }

        /* --- Tabs Logic --- */
        .content-section { display: none; }
        .content-section.active { display: block; }
    </style>
</head>
<body>

    <aside>
        <div class="logo">EDU<span>BRIDGE</span></div>
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link"><i class="fa-solid fa-house"></i> الرئيسية</a>
            <a href="{{ route('profile') }}" class="nav-link"><i class="fa-solid fa-user"></i> الملف الشخصي</a>
            <a href="{{ route('messages') }}" class="nav-link"><i class="fa-solid fa-comment"></i> المراسلة</a>
            <a href="{{ route('notifications') }}" class="nav-link"><i class="fa-solid fa-bell"></i> الإشعارات</a>
            <a href="{{ route('schedule') }}" class="nav-link"><i class="fa-solid fa-calendar"></i> الجداول</a>
            <a href="{{ route('assignments') }}" class="nav-link active"><i class="fa-solid fa-file-pen"></i> الواجبات</a>
            <a href="{{ route('attendance') }}" class="nav-link"><i class="fa-solid fa-check"></i> الحضور</a>
            <a href="{{ route('lectures') }}" class="nav-link"><i class="fa-solid fa-play"></i> المحاضرات</a>
        </nav>
    </aside>

    <main>
        <header>
            <h1 style="font-weight: 900;">الواجبات والمشاريع</h1>
            <i class="fa-solid fa-gear" style="color: #666; cursor: pointer;"></i>
        </header>

        <div class="tabs">
            <div class="tab-item active" onclick="switchTab('all', this)">الكل</div>
            <div class="tab-item" onclick="switchTab('responses', this)">الردود</div>
        </div>

        <div id="all-section" class="content-section active">
            <div class="assignment-card active-hw">
                <div class="card-top">
                    <div class="card-main">
                        <div class="icon-box bg-yellow"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="title-area">
                            <span class="status-badge status-active">نشط</span>
                            <h3>واجب الفيزياء: الطاقة</h3>
                            <p>الفيزياء | الصف 11 - ب</p>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-left" style="color: #ccc;"></i>
                </div>
            </div>
        </div>

        <div id="responses-section" class="content-section">
            <p style="text-align: center; color: #888; font-weight: 700;">لا توجد ردود حالياً</p>
        </div>
    </main>

    <button class="btn-plus" onclick="openModal()">
        <i class="fa-solid fa-plus"></i>
    </button>

    <div class="modal-overlay" id="addModal">
        <div class="modal-card">
            <div class="modal-header">
                <i class="fa-solid fa-gear"></i>
                <h2>إضافة تمرين منزلي</h2>
                <i class="fa-solid fa-arrow-left" onclick="closeModal()"></i>
            </div>

            <div class="form-group">
                <label>عنوان التمرين</label>
                <input type="text" class="form-input" placeholder="أدخل عنوان التمرين...">
            </div>

            <div class="form-group">
                <label>وصف التمرين</label>
                <textarea class="form-input" style="height: 100px; resize: none;" placeholder="اكتب تفاصيل ومتطلبات التمرين هنا..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>المادة</label>
                    <select class="form-input">
                        <option>اختر المادة</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>الصف</label>
                    <select class="form-input">
                        <option>اختر الصف</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>تاريخ التسليم</label>
                    <input type="date" class="form-input">
                </div>
                <div class="form-group">
                    <label>وقت التسليم</label>
                    <input type="time" class="form-input">
                </div>
            </div>

            <div class="file-upload">
                 إرفاق ملفات (اختياري) <i class="fa-solid fa-paperclip"></i>
            </div>

            <button class="btn-submit" onclick="closeModal()">إضافة التمرين</button>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        function switchTab(tab, el) {
            document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.getElementById(tab + '-section').classList.add('active');
        }
        // إغلاق النافذة عند الضغط خارجها
        window.onclick = function(event) {
            if (event.target == document.getElementById('addModal')) closeModal();
        }
    </script>

<script>
    (function() {
        // فحص هل المعلمة اختارت الوضع الداكن؟
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
        
        // فحص هل غيرت حجم الخط؟
        if (localStorage.getItem('fontSize')) {
            document.documentElement.style.fontSize = localStorage.getItem('fontSize') + 'px';
        }
    })();
</script>
</body>
</html>