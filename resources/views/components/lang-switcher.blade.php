@php
    $currentLocale = app()->getLocale();
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';
    $targetLabel = $currentLocale === 'ar' ? 'EN' : 'عربي';
    $targetFlag = $currentLocale === 'ar' ? '🇺🇸' : '🇸🇦';
@endphp

<a href="{{ route('lang.switch', $targetLocale) }}" 
   id="globalLangSwitchBtn"
   title="{{ $currentLocale === 'ar' ? 'Switch language to English' : 'التحويل إلى اللغة العربية' }}"
   class="lang-switch-btn"
   style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.35rem 0.85rem; border-radius: 9999px; height: 38px; background: #1f1f1f; color: #f2f20d; border: 1.5px solid rgba(242, 242, 13, 0.5); text-decoration: none; font-weight: 900; font-size: 0.85rem; box-shadow: 0 0 10px rgba(242, 242, 13, 0.2); transition: all 0.2s ease;">
    <span style="font-size: 1.1rem; line-height: 1;">{{ $targetFlag }}</span>
    <span style="letter-spacing: 0.5px;">{{ $targetLabel }}</span>
</a>
