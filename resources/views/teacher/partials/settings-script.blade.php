{{-- سكربت الإعدادات العالمي - يُضاف في نهاية كل صفحة --}}
<script>
(function() {
    // تطبيق الوضع الداكن
    const theme = localStorage.getItem('theme');
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
    }

    // تطبيق حجم الخط
    const fontSize = localStorage.getItem('fontSize');
    if (fontSize) {
        document.documentElement.style.fontSize = fontSize + 'px';
    }

    // تطبيق الاتجاه (RTL/LTR)
    const dir = localStorage.getItem('dir');
    if (dir) {
        document.documentElement.dir = dir;
    }
})();
</script>
