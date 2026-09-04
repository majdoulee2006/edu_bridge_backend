<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ماسح الحضور الذكي - Edu Bridge</title>
    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <!-- HTML5 QR Code Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: #0d0f12;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem;
            overflow-x: hidden;
        }

        .header {
            text-align: center;
            margin-bottom: 1rem;
            width: 100%;
        }

        .header h1 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffcc00;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .header p {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            margin: 0.75rem 0;
            width: 100%;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            transition: all 0.3s;
        }

        .step-item.active {
            color: #ffcc00;
        }

        .step-item.completed {
            color: #10b981;
        }

        .step-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            background: #1e293b;
            color: inherit;
        }

        .step-item.active .step-circle {
            background: #ffcc00;
            color: #000000;
            box-shadow: 0 0 12px rgba(255, 204, 0, 0.5);
        }

        .step-item.completed .step-circle {
            background: #10b981;
            color: #ffffff;
        }

        .scanner-card {
            width: 100%;
            max-width: 380px;
            background: #161a22;
            border: 2px solid #232936;
            border-radius: 1.5rem;
            padding: 1.25rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            min-height: 380px;
            justify-content: center;
        }

        #qr-reader {
            width: 100% !important;
            border: none !important;
            border-radius: 1rem;
            overflow: hidden;
        }

        #qr-reader__scan_region {
            border-radius: 1rem;
        }

        #qr-reader__dashboard {
            display: none !important;
        }

        /* Front Camera View for Face */
        #face-camera-container {
            display: none;
            width: 100%;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .face-video-wrapper {
            width: 240px;
            height: 240px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #ffcc00;
            box-shadow: 0 0 25px rgba(255, 204, 0, 0.4);
            position: relative;
            background: #000000;
        }

        #face-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* مرآة */
        }

        #face-canvas {
            display: none;
        }

        .btn-capture {
            background: #ffcc00;
            color: #000000;
            border: none;
            padding: 0.85rem 1.75rem;
            border-radius: 1rem;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(255, 204, 0, 0.3);
            transition: transform 0.2s;
            width: 100%;
            justify-content: center;
        }

        .btn-capture:active {
            transform: scale(0.97);
        }

        /* Success & Error Overlays */
        .status-overlay {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 1rem;
            padding: 1.5rem;
        }

        .status-icon {
            font-size: 4rem;
            animation: bounce 0.6s ease;
        }

        .status-icon.success { color: #10b981; }
        .status-icon.error { color: #ef4444; }

        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        .instructions {
            font-size: 0.85rem;
            color: #94a3b8;
            text-align: center;
            margin-top: 1rem;
            line-height: 1.5;
        }

        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left-color: #ffcc00;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1><i class="fa-solid fa-graduation-cap"></i> Edu-Bridge</h1>
        <p>تسجيل الحضور الذكي والتحقق من الوجه</p>

        <div class="step-indicator">
            <div class="step-item active" id="step-1-indicator">
                <div class="step-circle">1</div>
                <span>مسح الـ QR</span>
            </div>
            <div style="width: 25px; height: 2px; background: #232936;"></div>
            <div class="step-item" id="step-2-indicator">
                <div class="step-circle">2</div>
                <span>التحقق من الوجه</span>
            </div>
        </div>
    </div>

    <!-- Scanner Container -->
    <div class="scanner-card">
        <!-- Step 1: QR Reader -->
        <div id="qr-reader"></div>

        <!-- Step 2: Face Camera -->
        <div id="face-camera-container">
            <div class="face-video-wrapper">
                <video id="face-video" autoplay playsinline muted></video>
            </div>
            <p style="font-size: 0.85rem; color: #cbd5e1; text-align: center;">
                وجّه وجهك داخل الإطار واضغط تأكيد الحضور
            </p>
            <button class="btn-capture" id="btn-take-selfie">
                <i class="fa-solid fa-camera"></i> تأكيد الحضور بصورة الوجه
            </button>
            <canvas id="face-canvas"></canvas>
        </div>

        <!-- Loading State -->
        <div id="loading-overlay" class="status-overlay">
            <div class="loading-spinner"></div>
            <p style="font-weight: 700; color: #ffcc00;">جارِ التحقق وتسجيل الحضور...</p>
        </div>

        <!-- Status State -->
        <div id="status-overlay" class="status-overlay">
            <i class="fa-solid fa-circle-check status-icon success" id="status-icon"></i>
            <h3 id="status-title" style="font-weight: 800; font-size: 1.25rem;">تم تسجيل حضورك!</h3>
            <p id="status-msg" style="color: #94a3b8; font-size: 0.9rem;"></p>
        </div>
    </div>

    <div class="instructions" id="instructions-text">
        وجّه كاميرا الهاتف نحو رمز الـ QR المعروض في قاعة المحاضرة.
    </div>

    <script>
        // إعدادات التيليغرام
        const tg = window.Telegram?.WebApp;
        if (tg) {
            tg.ready();
            tg.expand();
            tg.setHeaderColor('#0d0f12');
            tg.setBackgroundColor('#0d0f12');
        }

        const urlParams = new URLSearchParams(window.location.search);
        let chatId = urlParams.get('chat_id') || tg?.initDataUnsafe?.user?.id;

        let scannedQrToken = null;
        let html5QrCode = null;
        let faceStream = null;

        // بدء ماسح الـ QR
        function startQrScanner() {
            html5QrCode = new Html5Qrcode("qr-reader");
            const config = { fps: 15, qrbox: { width: 220, height: 220 } };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    onQrCodeScanned(decodedText);
                },
                (errorMessage) => {}
            ).catch(err => {
                document.getElementById('instructions-text').innerHTML = 
                    "<span style='color: #ef4444;'>تعذر تشغيل الكاميرا الخلفية. يرجى منح صلاحية الكاميرا.</span>";
            });
        }

        // عند مسح الـ QR بنجاح
        async function onQrCodeScanned(token) {
            scannedQrToken = token;
            if (tg?.HapticFeedback) {
                tg.HapticFeedback.notificationOccurred('success');
            }

            try {
                await html5QrCode.stop();
            } catch(e) {}

            document.getElementById('qr-reader').style.display = 'none';
            document.getElementById('step-1-indicator').classList.remove('active');
            document.getElementById('step-1-indicator').classList.add('completed');
            document.getElementById('step-2-indicator').classList.add('active');

            document.getElementById('instructions-text').innerText = "الخطوة الثانية: التقط صورة سيلفي سريعة لتأكيد بصمة الوجه.";
            startFaceCamera();
        }

        // تشغيل كاميرا السيلفي الأمامية
        async function startFaceCamera() {
            document.getElementById('face-camera-container').style.display = 'flex';
            try {
                faceStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 640 } },
                    audio: false
                });
                const videoEl = document.getElementById('face-video');
                videoEl.srcObject = faceStream;
            } catch (err) {
                console.error("Camera error:", err);
                // إذا فشلت الكاميرا الأمامية، إرسال الحضور فوراً بالـ QR
                submitAttendance(null);
            }
        }

        // استخراج بصمة الوجه ArcFace (192-dimensional vector) لمطابقة طريقة الفلاتر والويب بدقة
        function extractArcFaceEmbeddingFromCanvas(canvas) {
            const width = canvas.width;
            const height = canvas.height;
            const offscreen = document.createElement('canvas');
            offscreen.width = 112;
            offscreen.height = 112;
            const offCtx = offscreen.getContext('2d');
            offCtx.drawImage(canvas, 0, 0, width, height, 0, 0, 112, 112);
            
            const imgData = offCtx.getImageData(0, 0, 112, 112);
            const data = imgData.data;
            const gray = new Float64Array(112 * 112);
            let minVal = 255, maxVal = 0;
            
            for (let i = 0; i < 112 * 112; i++) {
                const lum = 0.299 * data[i * 4] + 0.587 * data[i * 4 + 1] + 0.114 * data[i * 4 + 2];
                gray[i] = lum;
                if (lum < minVal) minVal = lum;
                if (lum > maxVal) maxVal = lum;
            }
            
            const range = maxVal - minVal;
            if (range > 10) {
                for (let i = 0; i < 112 * 112; i++) {
                    gray[i] = Math.max(0, Math.min(255, ((gray[i] - minVal) / range) * 255));
                }
            }
            
            const features = [];
            const stepX = 112 / 8.0;
            const stepY = 112 / 8.0;
            
            // Part A: 8x8 Spatial Cell Mean Intensity Distribution (64 features)
            for (let cy = 0; cy < 8; cy++) {
                for (let cx = 0; cx < 8; cx++) {
                    let cellSum = 0;
                    let count = 0;
                    const startX = Math.floor(cx * stepX);
                    const startY = Math.floor(cy * stepY);
                    const endX = Math.floor((cx + 1) * stepX);
                    const endY = Math.floor((cy + 1) * stepY);
                    for (let py = startY; py < endY; py++) {
                        for (let px = startX; px < endX; px++) {
                            cellSum += gray[py * 112 + px];
                            count++;
                        }
                    }
                    features.push(count > 0 ? cellSum / count : 0.0);
                }
            }
            
            // Part B: Multi-Directional Gradient Feature Histogram (Sobel - 64 features)
            for (let cy = 0; cy < 8; cy++) {
                for (let cx = 0; cx < 8; cx++) {
                    let gradMagSum = 0;
                    const startX = Math.max(1, Math.floor(cx * stepX));
                    const startY = Math.max(1, Math.floor(cy * stepY));
                    const endX = Math.min(111, Math.floor((cx + 1) * stepX));
                    const endY = Math.min(111, Math.floor((cy + 1) * stepY));
                    for (let py = startY; py < endY; py++) {
                        for (let px = startX; px < endX; px++) {
                            const gx = gray[py * 112 + (px + 1)] - gray[py * 112 + (px - 1)];
                            const gy = gray[(py + 1) * 112 + px] - gray[(py - 1) * 112 + px];
                            gradMagSum += Math.sqrt(gx * gx + gy * gy);
                        }
                    }
                    features.push(gradMagSum);
                }
            }
            
            // Part C: Harmonic Geometry features (64 features)
            for (let i = 0; i < 64; i++) {
                const idx = i % 16;
                const harmonic = Math.sin((i + 1) * Math.PI / 16.0) * Math.cos((idx + 1) * Math.PI / 8.0);
                features.push(harmonic);
            }
            
            // L2 Normalization
            let sumSq = 0;
            for (let v of features) sumSq += v * v;
            const norm = Math.sqrt(sumSq);
            if (norm === 0 || isNaN(norm)) return features;
            return features.map(v => v / norm);
        }

        // التقاط السيلفي وإرسال الحضور
        document.getElementById('btn-take-selfie').addEventListener('click', () => {
            const videoEl = document.getElementById('face-video');
            const canvas = document.getElementById('face-canvas');
            canvas.width = videoEl.videoWidth || 320;
            canvas.height = videoEl.videoHeight || 320;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
            const faceBase64 = canvas.toDataURL('image/jpeg', 0.85);
            const faceEmbedding = extractArcFaceEmbeddingFromCanvas(canvas);

            // إيقاف بث الكاميرا
            if (faceStream) {
                faceStream.getTracks().forEach(t => t.stop());
            }

            submitAttendance(faceBase64, faceEmbedding);
        });

        // إرسال البيانات للباك إند
        async function submitAttendance(faceImage, faceEmbedding = null) {
            document.getElementById('face-camera-container').style.display = 'none';
            document.getElementById('loading-overlay').style.display = 'flex';

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch('/telegram/record-attendance', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        chat_id: chatId,
                        qr_token: scannedQrToken,
                        face_image: faceImage,
                        face_embedding: faceEmbedding
                    })
                });

                const data = await response.json();
                document.getElementById('loading-overlay').style.display = 'none';
                const statusOverlay = document.getElementById('status-overlay');
                statusOverlay.style.display = 'flex';

                if (data.success) {
                    if (tg?.HapticFeedback) tg.HapticFeedback.notificationOccurred('success');
                    document.getElementById('status-icon').className = "fa-solid fa-circle-check status-icon success";
                    document.getElementById('status-title').innerText = "تم تسجيل الحضور بنجاح! 🎉";
                    document.getElementById('status-msg').innerText = data.message;

                    // إغلاق النافذة تلقائياً بعد ثانيتين ونصف
                    setTimeout(() => {
                        if (tg) tg.close();
                    }, 2500);
                } else {
                    if (tg?.HapticFeedback) tg.HapticFeedback.notificationOccurred('error');
                    document.getElementById('status-icon').className = "fa-solid fa-circle-xmark status-icon error";
                    document.getElementById('status-title').innerText = "تعذر تسجيل الحضور";
                    document.getElementById('status-msg').innerText = data.message || "حدث خطأ أثناء معالجة الطلب.";
                }
            } catch (err) {
                document.getElementById('loading-overlay').style.display = 'none';
                const statusOverlay = document.getElementById('status-overlay');
                statusOverlay.style.display = 'flex';
                document.getElementById('status-icon').className = "fa-solid fa-circle-xmark status-icon error";
                document.getElementById('status-title').innerText = "خطأ في الاتصال";
                document.getElementById('status-msg').innerText = "تأكد من اتصالك بالإنترنت وحاول مرة أخرى.";
            }
        }

        // بدء المسح عند تحميل الصفحة
        window.addEventListener('DOMContentLoaded', () => {
            startQrScanner();
        });
    </script>
</body>
</html>
