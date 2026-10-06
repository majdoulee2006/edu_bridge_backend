// الصفحات العربية (المسار فيه /ar/) تُعرض من اليمين لليسار
(function () {
  if (/\/ar\//.test(window.location.pathname)) {
    document.documentElement.setAttribute('dir', 'rtl');
    document.documentElement.setAttribute('lang', 'ar');
  }
})();
