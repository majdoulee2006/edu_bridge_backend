<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edu Bridge | الإعدادات</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --main-yellow: #f9f21a;
            --bg-color: #fcfcf3;
            --card-bg: #ffffff;
            --text-primary: #2d3436;
            --text-secondary: #8c8c8c;
            --border-color: #f1f1f1;
        }

        /* تعريف ألوان الوضع الداكن */
        body.dark-mode {
            --bg-color: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --border-color: #3d3d3d;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; transition: all 0.3s ease; }
        
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: var(--bg-color); 
            color: var(--text-primary);
            min-height: 100vh;
            padding: 20px;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 10px 5px;
        }
        .back-btn {
            width: 45px; height: 45px; background: var(--card-bg);
            border-radius: 15px; display: flex; align-items: center;
            justify-content: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            text-decoration: none; color: var(--text-primary);
        }
        header h1 { font-size: 1.3rem; font-weight: 900; }

        .container { max-width: 600px; margin: 0 auto; }

        .user-card {
            background: var(--card-bg); border-radius: 30px; padding: 25px;
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }
        .avatar-wrapper { position: relative; }
        .avatar { width: 80px; height: 80px; border-radius: 25px; object-fit: cover; border: 3px solid var(--main-yellow); }
        .badge { position: absolute; bottom: -5px; left: -5px; background: #3498db; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; border: 3px solid var(--card-bg); }

        .user-info h2 { font-size: 1.2rem; font-weight: 900; }
        .user-info p { color: var(--text-secondary); font-size: 0.9rem; font-weight: 700; }

        .section-label { font-weight: 900; font-size: 1rem; color: var(--text-secondary); margin: 0 10px 15px; display: block; }
        
        .settings-box {
            background: var(--card-bg); border-radius: 30px; padding: 15px 25px;
            margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .row { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--border-color); }
        .row:last-child { border-bottom: none; }
        
        .label-with-icon { display: flex; align-items: center; gap: 15px; font-weight: 800; font-size: 1.05rem; }
        .label-with-icon i { color: var(--main-yellow); font-size: 1.2rem; text-shadow: 1px 1px 0 rgba(0,0,0,0.1); }

        .font-slider { width: 100%; height: 8px; background: #eee; border-radius: 10px; appearance: none; outline: none; margin-top: 15px; }
        .font-slider::-webkit-slider-thumb { appearance: none; width: 22px; height: 22px; background: var(--main-yellow); border-radius: 50%; cursor: pointer; border: 4px solid var(--card-bg); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        .toggle { position: relative; display: inline-block; width: 55px; height: 30px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e0e0e0; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 4px; bottom: 4px; background-color: white; border-radius: 50%; transition: .4s; }
        input:checked + .slider { background-color: var(--main-yellow); }
        input:checked + .slider:before { transform: translateX(25px); }

        .lang-group { background: #f5f5f5; padding: 5px; border-radius: 15px; display: flex; gap: 5px; }
        .lang-btn { border: none; padding: 8px 20px; border-radius: 12px; font-weight: 900; cursor: pointer; background: transparent; color: #888; }
        .lang-btn.active { background: var(--card-bg); color: #000; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        .btn-logout {
            width: 100%; padding: 20px; border: none; border-radius: 25px;
            background: #ff7675; color: white; font-weight: 900; font-size: 1.1rem;
            cursor: pointer; margin-top: 10px; box-shadow: 0 10px 20px rgba(255, 118, 117, 0.2);
        }
    </style>
</head>
<body id="bodyTag">

    <div class="container">
        <header>
            <a href="javascript:history.back()" class="back-btn"><i class="fa-solid fa-chevron-right"></i></a>
            <h1>الإعدادات</h1>
            <div style="width: 45px;"></div>
        </header>

        <div class="user-card">
            <div class="avatar-wrapper">
                <img src="https://i.pravatar.cc/150?u=teacher" class="avatar">
                <div class="badge"><i class="fa-solid fa-check"></i></div>
            </div>
            <div class="user-info">
                <h2>أ. هبة عيسى</h2>
                <p>معلمة لغة عربية</p>
            </div>
        </div>

        <span class="section-label">تفضيلات التطبيق</span>
        <div class="settings-box">
            <div class="row" style="flex-direction: column; align-items: flex-start;">
                <div class="label-with-icon"><i class="fa-solid fa-font"></i> حجم الخط</div>
                <input type="range" min="14" max="24" value="16" class="font-slider" id="fontRange">
                <div style="width:100%; display:flex; justify-content:space-between; margin-top:10px; font-size:0.8rem; font-weight:800; color:var(--text-secondary);">
                    <span>صغير</span>
                    <span>كبير</span>
                </div>
            </div>

            <div class="row">
                <div class="label-with-icon"><i class="fa-solid fa-moon"></i> الوضع الداكن</div>
                <label class="toggle">
                    <input type="checkbox" id="darkSwitcher">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <span class="section-label">اللغة والإشعارات</span>
        <div class="settings-box">
            <div class="row">
                <div class="label-with-icon"><i class="fa-solid fa-globe"></i> اللغة</div>
                <div class="lang-group">
                    <button class="lang-btn" id="enBtn">EN</button>
                    <button class="lang-btn active" id="arBtn">العربية</button>
                </div>
            </div>

            <div class="row">
                <div class="label-with-icon"><i class="fa-solid fa-bell"></i> الإشعارات</div>
                <label class="toggle">
                    <input type="checkbox" checked id="notifToggle">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <button class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</button>
    </div>

    <script>
        const darkSwitcher = document.getElementById('darkSwitcher');
        const fontRange = document.getElementById('fontRange');
        const bodyTag = document.body;

        // --- 1. جلب الإعدادات عند فتح الصفحة ---
        window.addEventListener('DOMContentLoaded', () => {
            // التحقق من الوضع الداكن
            if (localStorage.getItem('theme') === 'dark') {
                bodyTag.classList.add('dark-mode');
                darkSwitcher.checked = true;
            }

            // التحقق من حجم الخط
            if (localStorage.getItem('fontSize')) {
                const savedSize = localStorage.getItem('fontSize');
                fontRange.value = savedSize;
                document.documentElement.style.fontSize = savedSize + 'px';
            }
        });

        // --- 2. تفعيل وحفظ الوضع الداكن ---
        darkSwitcher.addEventListener('change', () => {
            if (darkSwitcher.checked) {
                bodyTag.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                bodyTag.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        });

        // --- 3. تفعيل وحفظ حجم الخط ---
        fontRange.addEventListener('input', (e) => {
            const size = e.target.value;
            document.documentElement.style.fontSize = size + 'px';
            localStorage.setItem('fontSize', size);
        });

        // --- 4. برمجة اللغة والاتجاه (اختياري للحفظ) ---
        const arBtn = document.getElementById('arBtn');
        const enBtn = document.getElementById('enBtn');

        enBtn.addEventListener('click', () => {
            document.dir = 'ltr';
            enBtn.classList.add('active');
            arBtn.classList.remove('active');
            localStorage.setItem('dir', 'ltr');
        });
        arBtn.addEventListener('click', () => {
            document.dir = 'rtl';
            arBtn.classList.add('active');
            enBtn.classList.remove('active');
            localStorage.setItem('dir', 'rtl');
        });
    </script>
</body>
</html>