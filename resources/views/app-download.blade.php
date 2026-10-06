<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تحميل تطبيق Edu Bridge</title>
    <meta property="og:title" content="تطبيق Edu Bridge">
    <meta property="og:description" content="اضغط لتحميل التطبيق على هاتفك">
    <meta property="og:image" content="{{ asset('images/edubridge-logo.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        // الافتراضي فاتح. نقرأ اختيار المستخدم السابق قبل الرسم لتجنب الوميض.
        try { var t = localStorage.getItem('eb-theme'); if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t); } catch (e) {}
    </script>
    <style>
        :root, [data-theme="light"] {
            --primary:#c5a029; --primary-dark:#9e7d17; --primary-soft:rgba(197,160,41,.12);
            --bg:#faf9f6; --card:#ffffff; --text:#121212; --grey:#55555a; --muted:#88888e;
            --border:rgba(0,0,0,.08); --shadow:0 8px 30px rgba(0,0,0,.07); --btn-text:#121212;
            --glow:rgba(197,160,41,.35);
        }
        [data-theme="dark"] {
            --primary:#ffcc00; --primary-dark:#f57f17; --primary-soft:rgba(255,204,0,.10);
            --bg:#121212; --card:#1e1e1e; --text:#ffffff; --grey:#a0a0a0; --muted:#666666;
            --border:rgba(255,255,255,.07); --shadow:0 8px 30px rgba(0,0,0,.45); --btn-text:#000000;
            --glow:rgba(255,204,0,.30);
        }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; overflow-x:hidden; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:"Cairo","Tajawal",Tahoma,Arial,sans-serif; font-size:17px; line-height:1.9; transition:background .4s, color .4s; overflow-x:hidden; }
        .wrap { max-width:920px; margin:0 auto; padding:0 18px; }

        /* خلفية متحركة هادئة */
        .blob { position:fixed; border-radius:50%; filter:blur(90px); opacity:.5; z-index:-1; background:var(--primary-soft); animation:drift 18s ease-in-out infinite alternate; }
        .blob.a { width:380px; height:380px; top:-120px; left:-100px; }
        .blob.b { width:320px; height:320px; bottom:-100px; right:-80px; animation-delay:-8s; }
        @keyframes drift { to { transform:translate(60px,40px) scale(1.15); } }

        /* الشريط العلوي */
        header { display:flex; align-items:center; justify-content:space-between; padding:16px 0; }
        .brand { font-weight:900; font-size:18px; display:flex; align-items:center; gap:8px; }
        .brand i { width:10px; height:10px; border-radius:50%; background:var(--primary); animation:ping 2s infinite; }
        @keyframes ping { 0%{box-shadow:0 0 0 0 var(--glow);} 100%{box-shadow:0 0 0 12px transparent;} }
        .toggle { border:1px solid var(--border); background:var(--card); color:var(--text); width:44px; height:44px; border-radius:50%; cursor:pointer; box-shadow:var(--shadow); display:grid; place-items:center; transition:transform .3s; }
        .toggle:hover { transform:rotate(20deg) scale(1.08); }
        .toggle svg { width:22px; height:22px; }
        [data-theme="light"] .i-sun { display:none; } [data-theme="dark"] .i-moon { display:none; }

        /* الأقسام */
        .hero { text-align:center; padding:18px 0 8px; }
        .logo { width:128px; height:128px; object-fit:cover; border-radius:32px; border:3px solid var(--primary); animation:float 4s ease-in-out infinite; box-shadow:0 14px 30px var(--glow); }
        @keyframes float { 50% { transform:translateY(-10px); } }
        h1 { margin:6px 0 0; font-size:34px; font-weight:900; letter-spacing:-.5px; }
        h1 span { color:var(--primary); }
        .sub { color:var(--grey); margin:4px auto 0; max-width:520px; font-size:16px; font-weight:600; }

        .btn { position:relative; overflow:hidden; display:block; max-width:420px; margin:26px auto 8px; padding:16px; background:var(--primary); color:var(--btn-text); border-radius:18px; font-size:24px; font-weight:900; text-decoration:none; text-align:center; box-shadow:0 10px 30px var(--glow); transition:transform .25s, box-shadow .25s; animation:pulse 2.6s ease-in-out infinite; }
        .btn:hover { transform:translateY(-3px) scale(1.02); }
        .btn:active { transform:scale(.98); }
        .btn::after { content:""; position:absolute; top:0; bottom:0; width:60px; left:-80px; background:linear-gradient(100deg,transparent,rgba(255,255,255,.55),transparent); transform:skewX(-20deg); animation:shine 3.4s infinite; }
        @keyframes shine { 0%,55% { left:-80px; } 100% { left:120%; } }
        @keyframes pulse { 50% { box-shadow:0 14px 40px var(--glow); } }
        .meta { text-align:center; color:var(--muted); font-size:14px; font-weight:600; }
        .ios { display:none; margin:14px auto 0; max-width:420px; padding:10px 14px; border-radius:14px; background:var(--primary-soft); border:1px solid var(--primary); text-align:center; font-size:14px; font-weight:700; }

        section { margin-top:54px; }
        h2 { text-align:center; font-size:26px; font-weight:900; margin:0 0 6px; }
        h2::after { content:""; display:block; width:48px; height:4px; border-radius:4px; background:var(--primary); margin:8px auto 0; }
        .lead { text-align:center; color:var(--grey); margin:10px auto 26px; max-width:560px; font-size:15px; font-weight:600; }

        .card { background:var(--card); border:1px solid var(--border); border-radius:22px; box-shadow:var(--shadow); padding:18px 22px; transition:transform .3s, border-color .3s; }
        .card:hover { transform:translateY(-5px); border-color:var(--primary); }

        /* خطوات */
        .steps { display:grid; gap:14px; }
        .step { display:flex; gap:14px; align-items:flex-start; }
        .step .n { flex:0 0 40px; height:40px; border-radius:50%; background:var(--primary); color:var(--btn-text); font-weight:900; display:grid; place-items:center; font-size:18px; }
        .step b, .hl { color:var(--primary-dark); }
        [data-theme="dark"] .step b, [data-theme="dark"] .hl { color:var(--primary); }
        .update { margin-top:16px; border-color:var(--primary); background:var(--primary-soft); }
        .update h3 { margin:0 0 4px; font-size:18px; }

        /* ميزات */
        .grid { display:grid; gap:14px; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); }
        .feat .ic { font-size:30px; display:block; margin-bottom:4px; }
        .feat h3 { margin:0; font-size:17px; font-weight:800; }
        .feat p { margin:4px 0 0; color:var(--grey); font-size:14px; font-weight:600; line-height:1.7; }

        /* الفريق */
        .team { display:grid; gap:16px; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); }
        .member { text-align:center; position:relative; overflow:hidden; }
        .member .av { width:68px; height:68px; margin:0 auto 8px; border-radius:20px; display:grid; place-items:center; font-weight:900; font-size:22px; color:var(--btn-text); background:linear-gradient(135deg,var(--primary),var(--primary-dark)); box-shadow:0 8px 18px var(--glow); transition:transform .4s; }
        .member:hover .av { transform:rotate(-8deg) scale(1.12); }
        .member h3 { margin:0; font-size:19px; font-weight:900; }
        .role { color:var(--primary-dark); font-size:13px; font-weight:800; margin:2px 0 8px; }
        [data-theme="dark"] .role { color:var(--primary); }
        .member p { margin:0 0 10px; color:var(--grey); font-size:13.5px; font-weight:600; line-height:1.8; }
        .chips { display:flex; flex-wrap:wrap; gap:6px; justify-content:center; }
        .chips span { font-size:11.5px; font-weight:800; padding:3px 10px; border-radius:999px; background:var(--primary-soft); border:1px solid var(--border); transition:all .25s; }
        .chips span:hover { background:var(--primary); color:var(--btn-text); transform:translateY(-2px); }
        .sup { margin-top:16px; display:grid; gap:16px; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); }
        .sup .card { display:flex; gap:14px; align-items:flex-start; }
        .sup .ic { flex:0 0 48px; height:48px; border-radius:16px; background:var(--primary-soft); border:1px solid var(--primary); display:grid; place-items:center; font-size:24px; }
        .sup h3 { margin:0; font-size:17px; font-weight:900; }
        .sup .role { margin-bottom:0; }

        /* أسئلة شائعة */
        details { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:12px 18px; margin-bottom:10px; box-shadow:var(--shadow); transition:border-color .3s; }
        details[open] { border-color:var(--primary); }
        summary { cursor:pointer; font-weight:800; list-style:none; display:flex; justify-content:space-between; align-items:center; gap:8px; }
        summary::-webkit-details-marker { display:none; }
        summary::after { content:"+"; font-size:24px; color:var(--primary); transition:transform .3s; }
        details[open] summary::after { transform:rotate(45deg); }
        details p { margin:8px 0 2px; color:var(--grey); font-size:15px; font-weight:600; }

        footer { text-align:center; color:var(--muted); font-size:13px; font-weight:600; padding:40px 0 34px; }
        footer a { color:var(--primary-dark); font-weight:800; text-decoration:none; }
        [data-theme="dark"] footer a { color:var(--primary); }


        /* مشاركة و QR */
        .share { display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-top:18px; }
        .sbtn { border:1px solid var(--border); background:var(--card); color:var(--text); font:inherit; font-size:14px; font-weight:800; padding:8px 16px; border-radius:999px; cursor:pointer; text-decoration:none; box-shadow:var(--shadow); transition:all .25s; display:inline-flex; align-items:center; gap:6px; }
        .sbtn:hover { border-color:var(--primary); transform:translateY(-3px); }
        .sbtn.ok { background:var(--primary); color:var(--btn-text); }
        .alt { display:block; text-align:center; margin-top:6px; font-size:13px; font-weight:700; color:var(--grey); }
        .alt a { color:var(--primary-dark); text-decoration:underline; }
        [data-theme="dark"] .alt a { color:var(--primary); }
        .qrbox { display:flex; flex-direction:column; align-items:center; gap:10px; text-align:center; }
        .qrbox .qr { background:#fff; padding:14px; border-radius:18px; box-shadow:var(--shadow); line-height:0; }
        .qrbox .qr svg { width:180px; height:180px; }
        .news { margin-top:16px; }
        .news h3 { margin:0 0 4px; font-size:18px; }
        .news pre { margin:0; white-space:pre-wrap; font:inherit; font-size:15px; color:var(--grey); font-weight:600; }

        /* ظهور تدريجي */
        .rv { opacity:0; transform:translateY(24px); transition:opacity .7s ease, transform .7s ease; }
        .rv.in { opacity:1; transform:none; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation:none !important; transition:none !important; }
            .rv { opacity:1; transform:none; }
        }
        @media (max-width:480px) { h1 { font-size:28px; } .btn { font-size:21px; } }
    </style>
</head>
<body>
<div class="blob a"></div><div class="blob b"></div>

<div class="wrap">
    <header>
        <div class="brand"><i></i> Edu Bridge</div>
        <button class="toggle" id="theme" aria-label="تبديل الوضع الفاتح والداكن" title="تبديل الوضع">
            <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        </button>
    </header>

    <div class="hero rv">
        <img class="logo" src="{{ asset('images/edubridge-logo.png') }}" alt="Edu Bridge">
        <h1>تطبيق <span>Edu Bridge</span></h1>
        <p class="sub">الجسر بين الإدارة والمعلمين والطلاب وأولياء الأمور، بتطبيق واحد على هاتفك.</p>

        @if($release)
            <a class="btn" href="{{ url('/app/download') }}">⬇ تحميل التطبيق</a>
            <div class="meta">
                الإصدار {{ $release['version_name'] }} · {{ number_format($release['size_bytes'] / 1048576, 1) }} ميغابايت · أندرويد
            </div>
            <div class="ios" id="ios">⚠ التطبيق متوفر لهواتف <b>أندرويد</b> فقط حالياً.</div>
            @if($v7a)
                <span class="alt">ظهرت رسالة «التطبيق غير متوافق مع جهازك»؟ <a href="{{ url('/app/download') }}?abi=v7a">حمّل نسخة الهواتف القديمة (32-بت)</a></span>
            @endif
            <div class="share">
                <a class="sbtn" id="wa" href="#" target="_blank" rel="noopener">🟢 واتساب</a>
                <a class="sbtn" id="tg" href="#" target="_blank" rel="noopener">✈️ تيليغرام</a>
                <button class="sbtn" id="copy" type="button">🔗 نسخ الرابط</button>
            </div>
        @else
            <div class="card" style="max-width:420px;margin:24px auto 0">التطبيق غير متوفر حالياً، حاول لاحقاً.</div>
        @endif
    </div>

    @if($release)
    <section class="rv">
        <h2>خطوات التثبيت</h2>
        <p class="lead">أربع خطوات بسيطة، ومرة وحدة فقط.</p>
        <div class="steps">
            <div class="card step"><div class="n">1</div><div>اضغط زر <b>«تحميل التطبيق»</b> أعلاه، وسينزل الملف إلى هاتفك.</div></div>
            <div class="card step"><div class="n">2</div><div>إذا ظهرت رسالة <b>«هذا النوع من الملفات قد يضر بجهازك»</b> اضغط <b>«تنزيل على أي حال»</b>. الرسالة تظهر لأي تطبيق من خارج متجر Google Play.</div></div>
            <div class="card step"><div class="n">3</div><div>افتح الملف من إشعار التحميل (أو من مجلد <b>التنزيلات</b>) واضغط <b>«تثبيت»</b>.</div></div>
            <div class="card step"><div class="n">4</div><div>إذا طلب الهاتف الإذن، اضغط <b>«الإعدادات»</b> ثم فعّل <b>«السماح من هذا المصدر»</b> وارجع واضغط «تثبيت».</div></div>
        </div>
        <div class="card update">
            <h3>🔄 عندك التطبيق مسبقاً؟</h3>
            التطبيق يخبرك عند صدور إصدار جديد. اضغط «تحديث» ويثبت فوق القديم، <b>بدون حذف</b> وبياناتك تبقى محفوظة.
        </div>
        @if(!empty(trim($release['changelog'] ?? '')))
            <div class="card news">
                <h3>✨ ما الجديد في الإصدار {{ $release['version_name'] }}</h3>
                <pre>{{ $release['changelog'] }}</pre>
            </div>
        @endif
    </section>

    <section class="rv">
        <h2>امسح وحمّل</h2>
        <p class="lead">وجّه كاميرا هاتفك إلى الرمز لفتح هذه الصفحة، أو اعرضه لمن بجانبك.</p>
        <div class="card qrbox">
            <div class="qr" id="qr"></div>
            <div class="meta" id="qrurl"></div>
        </div>
    </section>
    @endif

    <section class="rv">
        <h2>ماذا يقدّم التطبيق؟</h2>
        <p class="lead">منصة واحدة تخدم كل أطراف العملية التعليمية.</p>
        <div class="grid">
            <div class="card feat"><span class="ic">🎓</span><h3>للطالب</h3><p>متابعة الحضور والجدول والإعلانات، وتقديم الطلبات الخدمية من الهاتف.</p></div>
            <div class="card feat"><span class="ic">👨‍🏫</span><h3>للمعلم</h3><p>تسجيل الحضور برمز QR ذكي ومتغيّر، ونشر الإعلانات للطلاب.</p></div>
            <div class="card feat"><span class="ic">👪</span><h3>لولي الأمر</h3><p>متابعة أبنائه وإشعارات الغياب فوراً، وتقديم الأعذار وطلب المواعيد.</p></div>
            <div class="card feat"><span class="ic">🏛️</span><h3>للإدارة وشؤون الطلاب</h3><p>إدارة الحسابات والطلبات والتقارير من لوحة تحكم متكاملة.</p></div>
            <div class="card feat"><span class="ic">🔔</span><h3>إشعارات فورية</h3><p>كل جديد يصلك مباشرة على هاتفك بدون ما تفتح التطبيق.</p></div>
            <div class="card feat"><span class="ic">💬</span><h3>محادثة مباشرة</h3><p>تواصل سريع بين الأطراف داخل التطبيق.</p></div>
        </div>
    </section>

    <section class="rv">
        <h2>فريق العمل</h2>
        <p class="lead">فريق جمع بين التخصص والشغف لبناء منصة Edu Bridge.</p>
        <div class="team">
            <div class="card member"><div class="av">م</div><h3>مجدولين محمود</h3><div class="role">قائدة الفريق ومسؤولة الربط البرمجي</div><p>إدارة وتنسيق المهام وتكامل النظام، والربط الكامل بين واجهات التطبيق والخدمات الخلفية.</p><div class="chips"><span>Laravel</span><span>System Integration</span><span>RESTful APIs</span><span>Team Leadership</span></div></div>
            <div class="card member"><div class="av">م</div><h3>محمود غنام</h3><div class="role">مطوّر واجهات تطبيق الموبايل Flutter</div><p>تصميم وتطوير واجهات المستخدم لتطبيق الجوال بالكامل، وضمان سلاسة التجربة وسرعة الاستجابة.</p><div class="chips"><span>Flutter</span><span>Dart</span><span>UI Design</span><span>Mobile UX</span></div></div>
            <div class="card member"><div class="av">إ</div><h3>إسراء منوّر</h3><div class="role">مطوّرة خادم ومصممة APIs (Backend)</div><p>بناء الخدمات الخلفية بالكامل، وتصميم الـ APIs اللازمة لربط النظام وتأمين تدفق البيانات للتطبيق.</p><div class="chips"><span>Laravel</span><span>PHP</span><span>API Design</span><span>Backend Architecture</span></div></div>
            <div class="card member"><div class="av">ش</div><h3>شهد زريقي</h3><div class="role">محللة قواعد بيانات ومطوّرة واجهات</div><p>تصميم قواعد البيانات وعلاقاتها وجداولها، وتحليل متطلبات النظام، وتطوير واجهات المستخدم الرسومية.</p><div class="chips"><span>Database Design</span><span>SQL</span><span>Systems Analysis</span><span>ERD</span></div></div>
            <div class="card member"><div class="av">ه</div><h3>هبة الله عيسى</h3><div class="role">مطوّرة لوحة التحكم والإدارة Web</div><p>تطوير وبناء لوحة تحكم الويب الخاصة بمستخدمي الإدارة وشؤون الطلاب، وتسهيل إدارة العمليات التعليمية.</p><div class="chips"><span>Web Development</span><span>Laravel Blade</span><span>Admin Panels</span><span>Responsive Design</span></div></div>
        </div>

        <h2 style="margin-top:44px">بإشراف</h2>
        <div class="sup">
            <div class="card"><div class="ic">🎖️</div><div><h3>م. وسيم الماضي</h3><div class="role">رئيس قسم الكمبيوتر ونظم المعلومات في معهد دمشق التقاني المتوسط</div></div></div>
            <div class="card"><div class="ic">🧭</div><div><h3>م. خالد اسماعيل</h3><div class="role">مشرف المشروع والمرشد الأكاديمي</div></div></div>
        </div>
    </section>

    <section class="rv">
        <h2>أسئلة شائعة</h2>
        <p class="lead"></p>
        <details><summary>هل التطبيق آمن؟</summary><p>نعم. التطبيق مصدره خادم المعهد مباشرة. الرسالة التحذيرية في هاتفك تظهر لأي تطبيق يُثبَّت من خارج متجر Google Play، وليست خاصة بتطبيقنا.</p></details>
        <details><summary>كيف أحدّث التطبيق؟</summary><p>عند صدور إصدار جديد يظهر لك داخل التطبيق مربع «تحديث الآن». اضغطه وسيُحمَّل ويُثبَّت فوق النسخة الحالية، وبياناتك تبقى كما هي.</p></details>
        <details><summary>ظهرت لي رسالة «التثبيت محظور»، ماذا أفعل؟</summary><p>اضغط «الإعدادات» في الرسالة، وفعّل خيار <b class="hl">«السماح من هذا المصدر»</b> للمتصفح الذي حمّلت منه، ثم ارجع واضغط «تثبيت».</p></details>
        <details><summary>أين أجد الملف بعد التحميل؟</summary><p>في إشعار التحميل بأعلى الشاشة، أو في تطبيق «الملفات» داخل مجلد <b class="hl">التنزيلات (Downloads)</b>.</p></details>
        <details><summary>هل يعمل على الآيفون؟</summary><p>حالياً التطبيق متوفر لهواتف أندرويد فقط.</p></details>
    </section>

    <footer>
        مشروع تخرج · قسم الكمبيوتر ونظم المعلومات · معهد دمشق التقاني المتوسط<br>
        <a href="https://edu-bradge.netlify.app" target="_blank" rel="noopener">تعرّف أكثر على منصة Edu Bridge ←</a>
    </footer>
</div>

<script src="{{ asset('js/qrcode-generator.min.js') }}"></script>
<script>
    // تبديل الوضع (الافتراضي فاتح) وحفظ الاختيار
    document.getElementById('theme').addEventListener('click', function () {
        var root = document.documentElement;
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('eb-theme', next); } catch (e) {}
    });

    // ظهور تدريجي عند التمرير
    var items = document.querySelectorAll('.rv');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (es) {
            es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
    } else { items.forEach(function (el) { el.classList.add('in'); }); }


    // رابط الصفحة للمشاركة و QR
    var pageUrl = location.href.split('#')[0].split('?')[0];
    var msg = 'حمّل تطبيق Edu Bridge من هنا:';
    var wa = document.getElementById('wa'), tg = document.getElementById('tg'), cp = document.getElementById('copy');
    if (wa) wa.href = 'https://wa.me/?text=' + encodeURIComponent(msg + ' ' + pageUrl);
    if (tg) tg.href = 'https://t.me/share/url?url=' + encodeURIComponent(pageUrl) + '&text=' + encodeURIComponent(msg);
    if (cp) cp.addEventListener('click', function () {
        var done = function () { cp.classList.add('ok'); cp.textContent = '✔ تم النسخ'; setTimeout(function () { cp.classList.remove('ok'); cp.textContent = '🔗 نسخ الرابط'; }, 2000); };
        if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(pageUrl).then(done, function () { window.prompt('انسخ الرابط:', pageUrl); }); }
        else { window.prompt('انسخ الرابط:', pageUrl); }
    });
    var qrEl = document.getElementById('qr');
    if (qrEl && window.qrcode) {
        var q = qrcode(0, 'M'); q.addData(pageUrl); q.make();
        qrEl.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
        document.getElementById('qrurl').textContent = pageUrl;
    }

    // تنبيه لمستخدمي الآيفون
    if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        var n = document.getElementById('ios'); if (n) n.style.display = 'block';
    }
</script>
</body>
</html>
