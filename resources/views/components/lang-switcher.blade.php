@php
    $currentLocale = app()->getLocale();
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';
    $targetLabel = $currentLocale === 'ar' ? 'EN' : 'AR';
@endphp

<a href="{{ route('lang.switch', $targetLocale) }}" 
   id="globalLangSwitchBtn"
   title="{{ $currentLocale === 'ar' ? 'Switch to English (EN)' : 'Switch to Arabic (AR)' }}"
   class="inline-flex items-center justify-center px-3.5 py-1.5 rounded-full text-xs font-black transition-all shadow-sm border select-none
          bg-slate-100 hover:bg-slate-200 text-slate-800 border-slate-300
          dark:bg-[#1f1f1f] dark:hover:bg-[#2a2a2a] dark:text-[#f2f20d] dark:border-slate-700/80 dark:hover:border-[#f2f20d]/50">
    <span class="tracking-widest font-black uppercase text-[11px] leading-tight">{{ $targetLabel }}</span>
</a>
