@php
    $currentLocale = app()->getLocale();
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';
    $targetLabel = $currentLocale === 'ar' ? 'EN' : 'عربي';
    $targetFlag = $currentLocale === 'ar' ? '🇺🇸' : '🇸🇦';
@endphp

<a href="{{ route('lang.switch', $targetLocale) }}" 
   id="globalLangSwitchBtn"
   title="{{ $currentLocale === 'ar' ? 'Switch language to English' : 'التحويل إلى اللغة العربية' }}"
   class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black transition-all shadow-sm border
          bg-slate-100 hover:bg-slate-200 text-slate-800 border-slate-300
          dark:bg-[#1f1f1f] dark:hover:bg-[#2a2a2a] dark:text-[#f2f20d] dark:border-slate-700/80 dark:hover:border-[#f2f20d]/50">
    <span class="text-sm leading-none">{{ $targetFlag }}</span>
    <span class="tracking-wider uppercase font-black">{{ $targetLabel }}</span>
</a>
