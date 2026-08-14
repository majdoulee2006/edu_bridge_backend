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
        if (isLoggedOut) return;
        isLoggedOut = true;
        
        // البحث عن نموذج تسجيل الخروج التابع للمستخدم
        let logoutForm = document.querySelector('form[action*="logout"]');
        
        alert('🔒 تم تسجيل الخروج تلقائياً لحماية حسابك بسبب عدم وجود أي نشاط لمدة 20 دقيقة.');

        if (logoutForm) {
            // إلحاق حقل مخفي يخبر السيرفر أن الخروج تم بسبب الخمول
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'is_inactivity_logout';
            input.value = '1';
            logoutForm.appendChild(input);
            logoutForm.submit();
        } else {
            // إنشاء نموذج POST بشكل ديناميكي مع CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const dynamicForm = document.createElement('form');
            dynamicForm.method = 'POST';
            dynamicForm.action = '/logout';
            
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                dynamicForm.appendChild(csrfInput);
            }
            
            const flagInput = document.createElement('input');
            flagInput.type = 'hidden';
            flagInput.name = 'is_inactivity_logout';
            flagInput.value = '1';
            dynamicForm.appendChild(flagInput);
            
            document.body.appendChild(dynamicForm);
            dynamicForm.submit();
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
