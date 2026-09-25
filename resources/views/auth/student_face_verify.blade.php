<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>التحقق من بصمة الوجه | EduBridge</title>
    <!-- Local Fonts: Cairo -->
    <link rel="stylesheet" href="{{ asset('css/fonts-local.css') }}">
    <!-- Local FontAwesome -->
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}">

    <style>
        :root {
            --bg-body: #09090b;
            --bg-card: #18181b;
            --border-color: #27272a;
            --accent-yellow: #facc15;
            --accent-hover: #eab308;
            --text-primary: #fafafa;
            --text-secondary: #a1a1aa;
            --color-green: #22c55e;
            --color-red: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(circle at 50% 0%, #1f2937 0%, transparent 60%);
            padding: 1.5rem;
        }

        .auth-container {
            width: 100%;
            max-width: 520px;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(250, 204, 21, 0.12);
            color: var(--accent-yellow);
            border: 1px solid rgba(250, 204, 21, 0.25);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.4rem;
            color: #ffffff;
        }

        .subtitle {
            font-size: 0.88rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .student-info-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(39, 39, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            text-align: right;
        }

        .student-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
        }

        .student-code {
            font-size: 0.8rem;
            color: var(--accent-yellow);
            direction: ltr;
            font-family: monospace;
            font-weight: 700;
        }

        /* Camera Box & Overlay (Matching Attendance) */
        .embedded-cam-box {
            position: relative;
            width: 100%;
            height: 340px;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
            border: 2px solid var(--border-color);
            margin-bottom: 1.25rem;
        }

        #live-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .overlay-mask {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 10;
        }

        .cutout-face {
            width: 200px;
            height: 260px;
            border: 4px solid var(--color-green);
            border-radius: 50%;
            position: relative;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.72);
            transition: border-color 0.3s ease;
        }

        .cutout-face.scanning {
            border-color: var(--accent-yellow);
        }

        .cutout-face.error {
            border-color: var(--color-red);
        }

        @keyframes laserLine {
            0% { top: 6%; }
            50% { top: 90%; }
            100% { top: 6%; }
        }

        .scan-laser {
            position: absolute;
            inset-inline: 0;
            height: 3px;
            background: var(--accent-yellow);
            box-shadow: 0 0 16px var(--accent-yellow);
            animation: laserLine 2.2s infinite ease-in-out;
        }

        .cam-controls {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 20;
            display: flex;
            gap: 8px;
        }

        .cam-btn {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(250, 204, 21, 0.4);
            color: var(--accent-yellow);
            font-size: 0.78rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 9999px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(4px);
            transition: all 0.2s ease;
        }

        .cam-btn:hover {
            background: rgba(15, 23, 42, 1);
            transform: scale(1.03);
        }

        .countdown-badge {
            position: absolute;
            bottom: 12px;
            z-index: 20;
            background: rgba(0, 0, 0, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--color-green);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        .btn-primary {
            width: 100%;
            background-color: var(--accent-yellow);
            color: #000000;
            border: none;
            padding: 0.85rem 1.5rem;
            font-size: 1rem;
            font-weight: 800;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 14px rgba(250, 204, 21, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            background-color: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(250, 204, 21, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            width: 100%;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border-color: #3f3f46;
        }

        /* Error Banner */
        .alert-box {
            display: none;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            text-align: right;
            line-height: 1.5;
        }

        .alert-box.show {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        /* Success Overlay Modal */
        .success-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(9, 9, 11, 0.95);
            backdrop-filter: blur(8px);
            z-index: 50;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .success-overlay.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.15);
            border: 2px solid var(--color-green);
            color: var(--color-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 1.25rem;
            animation: scaleIn 0.3s ease-out;
        }

        @keyframes scaleIn {
            from { transform: scale(0.6); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .success-score {
            display: inline-block;
            background: rgba(250, 204, 21, 0.15);
            color: var(--accent-yellow);
            border: 1px solid rgba(250, 204, 21, 0.3);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 800;
            margin-top: 0.5rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-card">

            <!-- Modal النجاح الفوري والتحويل -->
            <div id="success-overlay" class="success-overlay">
                <div class="success-icon">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h3 style="font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">تم التحقق بنجاح!</h3>
                <p id="success-msg" style="font-size: 0.9rem; color: var(--text-secondary); max-width: 320px; line-height: 1.5;"></p>
                <div id="success-score-box" class="success-score hidden"></div>
                <div style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 0.82rem; margin-top: 0.5rem;">
                    <i class="fa-solid fa-circle-notch fa-spin text-yellow-400"></i>
                    <span>جاري تحويلك إلى لوحة التحكم...</span>
                </div>
            </div>

            <!-- شارة الأمان -->
            <div class="header-badge">
                <i class="fa-solid fa-shield-halved"></i>
                <span>التحقق الأمني الذكي (Face ID)</span>
            </div>

            <h1 class="title">مطابقة بصمة الوجه</h1>
            <p class="subtitle">
                تم رصد محاولة دخول من جهاز ويب جديد. للحفاظ على أمان حسابك، يُرجى تأكيد هويتك عبر الكاميرا المباشرة.
            </p>

            <!-- صندوق التنبيه والأخطاء -->
            <div id="error-alert" class="alert-box">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 1.2rem; flex-shrink: 0; margin-top: 2px;"></i>
                <div id="error-text"></div>
            </div>

            <!-- معلومات الطالب -->
            <div class="student-info-bar">
                <div>
                    <div class="student-name">{{ $user->first_name }} {{ $user->last_name }}</div>
                    <div style="font-size: 0.78rem; color: var(--text-secondary);">
                        {{ $student->program->program_name ?? 'طالب جامعي' }}
                    </div>
                </div>
                <div class="student-code">
                    {{ $student->student_code ?? $user->university_id ?? $user->username }}
                </div>
            </div>

            <!-- نافذة الكاميرا الحية -->
            <div class="embedded-cam-box">
                <!-- أزرار التحكم بالكاميرا -->
                <div class="cam-controls">
                    <button type="button" onclick="toggleFlip()" class="cam-btn" title="عكس الصورة أفقياً">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>عكس الاتجاه</span>
                    </button>
                    <button type="button" onclick="switchCameraFacing()" class="cam-btn" title="تبديل الكاميرا">
                        <i class="fa-solid fa-camera-rotate"></i>
                    </button>
                </div>

                <!-- الفيديو المباشر -->
                <video id="live-video" autoplay playsinline muted style="transform: scaleX(-1);"></video>

                <!-- قناع الوجه البيضاوي ومسار الليزر -->
                <div class="overlay-mask">
                    <div id="cutout-frame" class="cutout-face">
                        <div class="scan-laser"></div>
                    </div>
                </div>

                <!-- إشعار حالة الكاميرا المباشرة -->
                <div class="countdown-badge">
                    <span class="pulse-dot"></span>
                    <span id="cam-status-text">اجعل وجهك داخل الإطار الأخضر</span>
                </div>
            </div>

            <!-- عنصر الكانفاس المخفي للالتقاط بدقة عالية -->
            <canvas id="hidden-canvas" style="display: none;"></canvas>

            <!-- عنصر اختيار/التقاط صورة مباشر كبديل في حال حظر المتصفح لـ WebRTC على الـ IP غير المشفر -->
            <input type="file" id="fallback-file-input" accept="image/*" capture="user" style="display: none;" onchange="handleFallbackImage(event)">

            <!-- زر التقاط وتحقق فوري -->
            <button id="verify-btn" type="button" onclick="captureAndVerify()" class="btn-primary">
                <i class="fa-solid fa-camera"></i>
                <span id="btn-text">التقاط والتحقق الآن</span>
            </button>

            <!-- زر بديل لكاميرا النظام يظهر عند تعذر تشغيل الكاميرا المضمنة -->
            <div id="fallback-cam-box" style="display: none; margin-bottom: 0.75rem;">
                <button type="button" onclick="document.getElementById('fallback-file-input').click()" class="btn-primary" style="background-color: #3b82f6; color: #fff;">
                    <i class="fa-solid fa-camera-retro"></i>
                    <span>فتح كاميرا الجهاز لالتقاط سيلفي مباشر</span>
                </button>
            </div>

            <!-- زر التبديل إلى localhost إذا كان على نفس الجهاز -->
            <div id="localhost-switch-box" style="display: none; margin-bottom: 0.75rem;">
                <a id="localhost-link" href="#" class="btn-secondary" style="color: #facc15; border-color: rgba(250,204,21,0.4); text-decoration: none;">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>فتح الرابط عبر localhost (تفعيل الكاميرا تلقائياً)</span>
                </a>
            </div>

            <!-- زر الإلغاء والعودة -->
            <form action="{{ route('student.face_auth.cancel') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn-secondary">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>إلغاء والعودة لصفحة الدخول</span>
                </button>
            </form>

        </div>
    </div>

    <script>
        let streamInstance = null;
        let isFlipped = true;
        let currentFacingMode = 'user';
        let isProcessing = false;
        let autoScanTimer = null;

        document.addEventListener('DOMContentLoaded', () => {
            startCamera();
        });

        async function startCamera() {
            if (streamInstance) {
                streamInstance.getTracks().forEach(track => track.stop());
            }

            const video = document.getElementById('live-video');
            hideError();

            const isLocalhost = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                handleCameraBlocked(isLocalhost);
                return;
            }

            try {
                streamInstance = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: currentFacingMode },
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                });

                video.srcObject = streamInstance;
                video.style.transform = isFlipped ? 'scaleX(-1)' : 'none';
                await video.play();

                document.getElementById('cam-status-text').textContent = "الكاميرا جاهزة - اجعل وجهك داخل الإطار";

                // تجهيز التحقق التلقائي الذكي بعد ثانيتين من ثبات الكاميرا
                if (autoScanTimer) clearTimeout(autoScanTimer);
                autoScanTimer = setTimeout(() => {
                    if (!isProcessing) {
                        captureAndVerify(true);
                    }
                }, 2200);

            } catch (err) {
                console.error("Camera access error:", err);
                handleCameraBlocked(isLocalhost);
            }
        }

        function handleCameraBlocked(isLocalhost) {
            document.getElementById('cam-status-text').textContent = "الكاميرا غير متاحة";
            document.getElementById('fallback-cam-box').style.display = 'block';

            if (!isLocalhost) {
                const port = window.location.port ? ':' + window.location.port : '';
                const localhostUrl = window.location.protocol + '//localhost' + port + window.location.pathname;
                const link = document.getElementById('localhost-link');
                link.href = localhostUrl;
                document.getElementById('localhost-switch-box').style.display = 'block';

                showError(`
                    <div>
                        <b>تنبيه أمني من المتصفح:</b> تمنع متصفحات Chrome و Edge تشغيل الكاميرا المباشرة عبر عناوين IP المحلية (192.168.x.x) دون تشفير HTTPS.<br>
                        <b>الحلول المتاحة:</b>
                        <ul style="margin-top: 6px; padding-right: 20px; font-size: 0.8rem; line-height: 1.6;">
                            <li>إذا كنت تتصفح من نفس الحاسوب: اضغط على زر <b>"فتح الرابط عبر localhost"</b> بالأسفل.</li>
                            <li>أو اضغط على زر <b>"فتح كاميرا الجهاز لالتقاط سيلفي"</b> لالتقاط الوجه بكاميرا النظام مباشرة.</li>
                        </ul>
                    </div>
                `);
            } else {
                showError("يرجى الضغط على أيقونة القفل أو الكاميرا في شريط العنوان أعلى المتصفح واختيار <b>سماح (Allow)</b> للوصول إلى الكاميرا ثم تحديث الصفحة.");
            }
        }

        function handleFallbackImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            const verifyBtn = document.getElementById('verify-btn');
            const btnText = document.getElementById('btn-text');
            const frame = document.getElementById('cutout-frame');
            const statusText = document.getElementById('cam-status-text');

            verifyBtn.disabled = true;
            btnText.textContent = "جاري معالجة الصورة والتحقق...";
            frame.classList.add('scanning');
            statusText.textContent = "جاري مطابقة بصمة الوجه...";
            hideError();

            reader.onload = function(e) {
                sendFaceVerification(e.target.result);
            };
            reader.readAsDataURL(file);
        }

        function toggleFlip() {
            isFlipped = !isFlipped;
            const video = document.getElementById('live-video');
            if (video) {
                video.style.transform = isFlipped ? 'scaleX(-1)' : 'none';
            }
        }

        async function switchCameraFacing() {
            currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
            isFlipped = (currentFacingMode === 'user');
            await startCamera();
        }

        function captureAndVerify(isAuto = false) {
            if (isProcessing) return;

            const video = document.getElementById('live-video');
            if (!video || video.readyState !== video.HAVE_ENOUGH_DATA) {
                if (!isAuto) showError("الكاميرا غير جاهزة بعد، يرجى الانتظار ثانية واحدة والمحاولة مجدداً.");
                return;
            }

            isProcessing = true;
            hideError();

            const verifyBtn = document.getElementById('verify-btn');
            const btnText = document.getElementById('btn-text');
            const frame = document.getElementById('cutout-frame');
            const statusText = document.getElementById('cam-status-text');

            verifyBtn.disabled = true;
            btnText.textContent = "جاري التحقق من الملامح...";
            frame.classList.add('scanning');
            statusText.textContent = "جاري مطابقة بصمة الوجه...";

            // التقاط الإطار الحالي بدقة متوازنة 640x480
            const canvas = document.getElementById('hidden-canvas');
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            const ctx = canvas.getContext('2d');

            // إذا كانت الكاميرا معكوسة بالـ CSS نقوم بعكسها على الكانفاس لتطابق المنظور الصحيح
            ctx.save();
            if (isFlipped) {
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            ctx.restore();

            const faceBase64 = canvas.toDataURL('image/jpeg', 0.88);
            sendFaceVerification(faceBase64);
        }

        function sendFaceVerification(faceBase64) {
            isProcessing = true;
            hideError();

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch("{{ route('student.face_auth.verify') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    face_image: faceBase64
                })
            })
            .then(async (response) => {
                const data = await response.json();
                if (response.ok && data.success) {
                    handleSuccess(data);
                } else {
                    handleFailure(data.message || "فشلت مطابقة بصمة الوجه، يرجى إعادة المحاولة.", data.face_score);
                }
            })
            .catch((err) => {
                console.error("Verification network error:", err);
                handleFailure("حدث خطأ أثناء الاتصال بالخادم، يرجى التأكد من اتصال الإنترنت والمحاولة مجدداً.");
            });
        }

        function handleSuccess(data) {
            const frame = document.getElementById('cutout-frame');
            frame.classList.remove('scanning');
            frame.style.borderColor = 'var(--color-green)';

            if (streamInstance) {
                streamInstance.getTracks().forEach(track => track.stop());
            }

            const overlay = document.getElementById('success-overlay');
            const msgEl = document.getElementById('success-msg');
            const scoreEl = document.getElementById('success-score-box');

            msgEl.textContent = data.message || "تم التحقق من هويتك بنجاح!";
            if (data.face_score) {
                scoreEl.textContent = `نسبة التطابق: ${data.face_score}%`;
                scoreEl.classList.remove('hidden');
            }

            overlay.classList.add('show');

            setTimeout(() => {
                window.location.href = data.redirect_url || "{{ route('student.dashboard') }}";
            }, 1200);
        }

        function handleFailure(message, score = null) {
            isProcessing = false;
            const verifyBtn = document.getElementById('verify-btn');
            const btnText = document.getElementById('btn-text');
            const frame = document.getElementById('cutout-frame');
            const statusText = document.getElementById('cam-status-text');

            verifyBtn.disabled = false;
            btnText.textContent = "إعادة المحاولة والتحقق";
            frame.classList.remove('scanning');
            frame.classList.add('error');
            setTimeout(() => frame.classList.remove('error'), 1500);

            statusText.textContent = "فشلت المطابقة - اضغط للالتقاط مجدداً";

            showError(message);
        }

        function showError(msg) {
            const alertBox = document.getElementById('error-alert');
            const textEl = document.getElementById('error-text');
            textEl.innerHTML = msg;
            alertBox.classList.add('show');
        }

        function hideError() {
            const alertBox = document.getElementById('error-alert');
            alertBox.classList.remove('show');
        }
    </script>
</body>
</html>
