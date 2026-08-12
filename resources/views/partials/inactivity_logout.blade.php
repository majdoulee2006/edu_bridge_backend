{{-- Auto Logout on 20 Minutes Inactivity --}}
<script>
(function() {
    // 20 دقيقة (20 * 60 * 1000 ملي ثانية)
    const IDLE_TIMEOUT_MS = 20 * 60 * 1000;
    let idleTimer = null;
    let isLoggedOut = false;

    function resetIdleTimer() {
        if (isLoggedOut) return;
        if (idleTimer) clearTimeout(idleTimer);
        idleTimer = setTimeout(onUserIdle, IDLE_TIMEOUT_MS);
    }

    function onUserIdle() {
        isLoggedOut = true;
        
        // البحث عن نموذج تسجيل الخروج التابع للمستخدم
        const logoutForm = document.querySelector('form[action*="logout"]');
        
        alert('🔒 تم تسجيل الخروج تلقائياً لحماية حسابك بسبب عدم وجود أي نشاط لمدة 20 دقيقة.');

        if (logoutForm) {
            logoutForm.submit();
        } else {
            window.location.href = '/';
        }
    }

    // الاستماع لحركات المستخدم
    const userActivityEvents = ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll', 'click'];
    userActivityEvents.forEach(event => {
        window.addEventListener(event, resetIdleTimer, { passive: true });
    });

    // تشغيل المؤقت عند فتح الصفحة
    resetIdleTimer();
})();
</script>
