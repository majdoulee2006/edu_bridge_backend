@extends('layouts.student')

@section('title', 'تسجيل الحضور بالكاميرا المباشرة')
@section('subtitle', 'الكاميرا تعمل تلقائياً من داخل الصفحة لمسح الـ QR والتحقق من الوجه ⚡')

@push('styles')
<!-- jsQR for real-time video frame decoding -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<style>
    .embedded-cam-box {
        position: relative;
        width: 100%;
        max-width: 440px;
        aspect-ratio: 3 / 4;
        background: #000;
        border-radius: 2rem;
        overflow: hidden;
        border: 2px solid #f2f20d;
        box-shadow: 0 0 35px rgba(242, 242, 13, 0.25);
        margin: 0 auto;
    }

    .cutout-qr {
        width: 240px;
        height: 240px;
        border: 3px solid #f2f20d;
        border-radius: 1.5rem;
        position: relative;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7);
    }

    .cutout-face {
        width: 220px;
        height: 290px;
        border: 4px solid #4ade80;
        border-radius: 50%;
        position: relative;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7);
    }

    @keyframes laserLine {
        0% { top: 5%; }
        50% { top: 90%; }
        100% { top: 5%; }
    }

    .scan-laser {
        position: absolute;
        inset-x: 0;
        height: 3px;
        background: #f2f20d;
        box-shadow: 0 0 15px #f2f20d;
        animation: laserLine 2s infinite ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="py-2 flex flex-col items-center justify-center space-y-4">

    <!-- إشعار حالة الكاميرا المباشرة -->
    <div id="status-bar" class="w-full max-w-md bg-slate-900 border border-yellow-400/40 rounded-2xl p-3.5 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span id="status-dot" class="w-3.5 h-3.5 rounded-full bg-yellow-400 animate-pulse"></span>
            <div>
                <div id="status-title" class="font-extrabold text-white text-sm">مسح رمز الـ QR (الكاميرا الخلفية)</div>
                <div id="status-subtitle" class="text-[11px] text-slate-400">وجه الكاميرا نحو رمز الـ QR المباشر</div>
            </div>
        </div>
        <span id="step-badge" class="bg-yellow-400 text-black text-xs font-black px-3 py-1 rounded-full">1 / 2</span>
    </div>

    <!-- الكاميرا المضمنة مباشرة داخل عناصر الصفحة (Embedded Video) -->
    <div class="embedded-cam-box">
        
        <!-- عنصر الفيديو المباشر في الـ HTML (يعمل تلقائياً) -->
        <video id="live-video" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"></video>

        <!-- 1. إطار مسح الـ QR المدمج -->
        <div id="frame-qr" class="absolute inset-0 z-10 flex flex-col items-center justify-center">
            <div class="cutout-qr">
                <div class="scan-laser"></div>
            </div>
            <p class="mt-4 text-xs font-bold text-yellow-400 bg-black/80 px-4 py-1.5 rounded-full border border-yellow-400/40">
                جاري قراءة الـ QR تلقائياً...
            </p>
        </div>

        <!-- 2. إطار مطابقة الوجه المدمج -->
        <div id="frame-face" class="hidden absolute inset-0 z-10 flex flex-col items-center justify-center">
            <div class="cutout-face"></div>
            <p class="mt-4 text-xs font-bold text-green-400 bg-black/80 px-4 py-1.5 rounded-full border border-green-400/40">
                اجعل وجهك في المنتصف للتحقق...
            </p>
        </div>

        <!-- في حال حظر المتصفح للكاميرا بسبب HTTP غير المشفر -->
        <div id="perm-error-box" class="hidden absolute inset-0 bg-slate-950/95 z-30 flex flex-col items-center justify-center p-6 text-center">
            <i class="fa-solid fa-lock text-4xl text-amber-400 mb-3"></i>
            <h4 class="text-white font-bold text-sm mb-2">يتطلب المتصفح إذن الكاميرا</h4>
            <p class="text-slate-400 text-xs leading-relaxed mb-4">
                يرجى الضغط على <b>"سماح (Allow)"</b> في المتصفح، أو فتح الرابط من <b>localhost:8001</b> لتشغيل الكاميرا المباشرة من أصل الصفحة.
            </p>
            <button onclick="startEmbeddedCamera('environment')" class="bg-yellow-400 text-black font-extrabold px-6 py-2.5 rounded-xl text-xs">
                إعادة محاولة فتح الكاميرا
            </button>
        </div>

    </div>

</div>

<canvas id="hidden-canvas" class="hidden"></canvas>

<!-- Modal النتيجة الفورية -->
<div id="result-modal" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
    <div class="bg-card-dark border border-slate-700 rounded-3xl p-8 max-w-sm w-full text-center space-y-4 shadow-2xl">
        <div id="modal-icon" class="text-6xl text-green-400">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h3 id="modal-title" class="text-2xl font-bold text-white">تم تسجيل الحضور</h3>
        <p id="modal-msg" class="text-sm text-slate-300 leading-relaxed"></p>
        <div id="modal-score" class="text-sm font-bold text-yellow-400 bg-slate-900 py-2.5 rounded-xl border border-slate-800"></div>

        <button onclick="closeModal()" class="w-full bg-yellow-400 hover:bg-yellow-500 text-black font-bold py-3.5 rounded-xl transition text-base">
            تم
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let streamInstance = null;
    let qrToken = "";
    let faceBase64 = "";
    let isDecoding = false;

    // البدء الفوري المباشر بمجرد فتح الصفحة/التبويب
    document.addEventListener("DOMContentLoaded", () => {
        initAutoAttendance();
    });

    async function initAutoAttendance() {
        qrToken = "";
        faceBase64 = "";
        
        document.getElementById('frame-qr').classList.remove('hidden');
        document.getElementById('frame-face').classList.add('hidden');
        document.getElementById('perm-error-box').classList.add('hidden');

        // فتح الكاميرا الخلفية من أصل الصفحة فورا
        const started = await startEmbeddedCamera("environment");
        if (started) {
            startLiveScanLoop();
        }
    }

    async function startEmbeddedCamera(facingMode) {
        if (streamInstance) {
            streamInstance.getTracks().forEach(t => t.stop());
        }

        const video = document.getElementById('live-video');

        try {
            streamInstance = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: facingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });

            video.srcObject = streamInstance;
            await video.play();
            return true;
        } catch (e) {
            console.warn("Direct embedded video failed:", e);
            document.getElementById('perm-error-box').classList.remove('hidden');
            return false;
        }
    }

    function startLiveScanLoop() {
        isDecoding = true;
        const video = document.getElementById('live-video');
        const canvas = document.getElementById('hidden-canvas');
        const ctx = canvas.getContext('2d');

        function processFrame() {
            if (!isDecoding) return;

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imgData.data, imgData.width, imgData.height);

                if (code && code.data && code.data.trim() !== "") {
                    isDecoding = false;
                    handleQrFound(code.data.trim());
                    return;
                }
            }
            requestAnimationFrame(processFrame);
        }
        requestAnimationFrame(processFrame);
    }

    async function handleQrFound(token) {
        qrToken = token;
        if (navigator.vibrate) navigator.vibrate(100);

        // التحديث الفوري لإطار الوجه
        document.getElementById('frame-qr').classList.add('hidden');
        document.getElementById('frame-face').classList.remove('hidden');
        
        document.getElementById('status-title').textContent = "التحقق من بصمة الوجه (الكاميرا الأمامية)";
        document.getElementById('status-subtitle').textContent = "اجعل وجهك في المنتصف للتحقق المباشر";
        document.getElementById('step-badge').textContent = "2 / 2";
        document.getElementById('status-dot').className = "w-3.5 h-3.5 rounded-full bg-green-400 animate-pulse";

        // الانتقال الفوري للكاميرا الأمامية من أصل الصفحة
        const ok = await startEmbeddedCamera("user");

        if (ok) {
            setTimeout(() => {
                captureFaceAndSubmit();
            }, 1000);
        }
    }

    function captureFaceAndSubmit() {
        const video = document.getElementById('live-video');
        const canvas = document.getElementById('hidden-canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        faceBase64 = dataUrl.replace(/^data:image\/[a-z]+;base64,/, "");

        if (streamInstance) {
            streamInstance.getTracks().forEach(t => t.stop());
        }

        sendDataToServer();
    }

    async function sendDataToServer() {
        try {
            const response = await fetch("{{ route('student.attendance.scan') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    qr_token: qrToken,
                    face_image: faceBase64,
                    scanned_at: new Date().toISOString()
                })
            });

            const res = await response.json();
            showResultModal(res);
        } catch (e) {
            showResultModal({ success: false, message: "حدث خطأ أثناء الاتصال بالخادم: " + e.message });
        }
    }

    function showResultModal(res) {
        const modal = document.getElementById('result-modal');
        const icon = document.getElementById('modal-icon');
        const title = document.getElementById('modal-title');
        const msg = document.getElementById('modal-msg');
        const score = document.getElementById('modal-score');

        if (res.success) {
            icon.className = "text-6xl text-green-400";
            icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
            title.textContent = "تم تسجيل حضورك بنجاح ✅";
            msg.textContent = res.message || "تم التحقق من الوجه وتثبيت الحضور.";
            if (res.face_score !== undefined && res.face_score !== null) {
                score.style.display = "block";
                score.textContent = "نسبة المطابقة الأمنية: " + res.face_score + "%";
            } else {
                score.style.display = "none";
            }
        } else {
            icon.className = "text-6xl text-red-500";
            icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
            title.textContent = "فشل التحقق من الحضور ❌";
            msg.textContent = res.message || "فشلت مطابقة بصمة الوجه أو رمز QR غير صالح.";
            score.style.display = "none";
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('result-modal').classList.add('hidden');
        window.location.reload();
    }
</script>
@endpush
