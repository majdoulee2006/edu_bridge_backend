@extends('layouts.admin')

@section('title', 'الإعدادات')
@section('header-title', 'الإعدادات')
@section('header-subtitle', 'تخصيص وإعدادات لوحة التحكم')

@section('content')

    <!-- Profile Summary Widget -->
    <div class="bg-white dark:bg-card-dark rounded-3xl p-4 flex items-center justify-between shadow-soft border border-slate-100 dark:border-slate-800/50 transition-colors">
        <div class="flex flex-col gap-1 mr-2">
            <h3 class="font-bold text-lg text-slate-900 dark:text-white">{{ Auth::user()->full_name }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">مدير النظام التعليمي</p>
        </div>
        <div class="relative">
            <div class="w-14 h-14 rounded-full bg-primary text-primary-content flex items-center justify-center font-bold text-xl shadow-glow">
                {{ mb_substr(Auth::user()->full_name ?? 'م', 0, 1) }}
            </div>
        </div>
    </div>

    <!-- Appearance Settings -->
    <div class="bg-white dark:bg-card-dark rounded-3xl p-5 shadow-soft border border-slate-100 dark:border-slate-800/50 transition-colors space-y-6">
        <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-2">المظهر والسمات</h4>
        
        <!-- Font Size Slider -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">حجم الخط</span>
                <span class="material-symbols-outlined text-slate-400 text-[20px]">text_fields</span>
            </div>
            <div class="flex items-center gap-3 px-1">
                <span class="text-[10px] text-slate-400">صغير</span>
                <input id="font-size-slider" class="w-full h-1 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-primary" max="22" min="12" step="1" type="range" value="16"/>
                <span class="text-[10px] text-slate-400">كبير</span>
            </div>
            <div class="flex justify-between px-1 text-[10px] text-slate-400">
                <span></span>
                <span class="text-primary-dark dark:text-primary font-bold">متوسط</span>
                <span></span>
            </div>
        </div>

        <!-- Dark Mode Switch -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-[20px]">dark_mode</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">الوضع الداكن</span>
            </div>
            
            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                <input type="checkbox" id="dark-mode-toggle-checkbox" class="absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 left-0 border-slate-200 dark:border-primary dark:left-6" onclick="toggleDarkMode()">
                <label for="dark-mode-toggle-checkbox" class="block overflow-hidden h-6 rounded-full bg-slate-200 dark:bg-primary cursor-pointer transition-colors duration-300"></label>
            </div>
        </div>
    </div>

    <!-- Notifications Settings -->
    <div class="bg-white dark:bg-card-dark rounded-3xl p-5 shadow-soft border border-slate-100 dark:border-slate-800/50 transition-colors space-y-6">
        <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-2">الإشعارات والتنبيهات</h4>
        
        <!-- Enable Notifications -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-[20px]">notifications</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">تفعيل التنبيهات الفورية</span>
            </div>
            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                <input class="absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 left-0 border-slate-200" id="notif-toggle" name="toggle" type="checkbox" onclick="toggleSwitch('notif-toggle')"/>
                <label class="block overflow-hidden h-6 rounded-full bg-slate-200 cursor-pointer transition-colors duration-300" for="notif-toggle"></label>
            </div>
        </div>

        <!-- Sounds Switch -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-[20px]">volume_up</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">أصوات التنبيهات</span>
            </div>
            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                <input class="absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 left-0 border-slate-200" id="sound-toggle" name="toggle" type="checkbox" onclick="toggleSwitch('sound-toggle')"/>
                <label class="block overflow-hidden h-6 rounded-full bg-slate-200 cursor-pointer transition-colors duration-300" for="sound-toggle"></label>
            </div>
        </div>

        <!-- Vibrations Switch -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-[20px]">vibration</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">الاهتزاز</span>
            </div>
            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                <input class="absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 left-0 border-slate-200" id="vibrate-toggle" name="toggle" type="checkbox" onclick="toggleSwitch('vibrate-toggle')"/>
                <label class="block overflow-hidden h-6 rounded-full bg-slate-200 cursor-pointer transition-colors duration-300" for="vibrate-toggle"></label>
            </div>
        </div>
    </div>

    <!-- Application Version Widget -->
    <div class="bg-white dark:bg-card-dark rounded-3xl p-6 shadow-soft border border-slate-100 dark:border-slate-800/50 flex flex-col items-center text-center space-y-4 transition-colors">
        <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center shadow-glow">
            <span class="material-symbols-outlined text-primary-content text-[32px]">school</span>
        </div>
        <div class="space-y-1">
            <h3 class="font-bold text-lg text-slate-900 dark:text-white">Edu-Bridge</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-[200px] mx-auto leading-relaxed">
                التطبيق الرسمي لإدارة الشؤون التعليمية والإدارية.
            </p>
            <span class="inline-block mt-2 px-3 py-1 bg-slate-100 dark:bg-white/5 rounded-full text-[10px] font-mono text-slate-500">النسخة 2.0.1</span>
        </div>
    </div>

    <div class="h-4"></div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Restore Dark Mode Switch Visuals
        const darkCheckbox = document.getElementById('dark-mode-toggle-checkbox');
        if (darkCheckbox) {
            darkCheckbox.checked = document.documentElement.classList.contains('dark');
        }

        // 2. Restore Font Size Slider Visuals & Event Listener
        const fontSlider = document.getElementById('font-size-slider');
        const savedFontSize = localStorage.getItem('app-font-size') || '16';
        if (fontSlider) {
            fontSlider.value = savedFontSize;
            fontSlider.addEventListener('input', function() {
                const size = this.value;
                document.documentElement.style.fontSize = size + 'px';
                localStorage.setItem('app-font-size', size);
            });
        }

        // 3. Restore Notification Switches States
        initSwitch('notif-toggle', true);
        initSwitch('sound-toggle', true);
        initSwitch('vibrate-toggle', false);

        // 4. Restore Language Active state
        const savedLang = localStorage.getItem('app-lang') || 'ar';
        updateLangVisuals(savedLang);
    });

    // Helper to init toggles
    function initSwitch(id, defaultVal) {
        const checkbox = document.getElementById(id);
        if (!checkbox) return;
        
        const stored = localStorage.getItem(id);
        const isChecked = stored !== null ? (stored === 'true') : defaultVal;
        
        checkbox.checked = isChecked;
        
        if (isChecked) {
            checkbox.classList.remove('left-0', 'border-slate-200');
            checkbox.classList.add('left-6', 'border-primary');
            checkbox.nextElementSibling.classList.remove('bg-slate-200');
            checkbox.nextElementSibling.classList.add('bg-primary');
        } else {
            checkbox.classList.remove('left-6', 'border-primary');
            checkbox.classList.add('left-0', 'border-slate-200');
            checkbox.nextElementSibling.classList.remove('bg-primary');
            checkbox.nextElementSibling.classList.add('bg-slate-200');
        }
    }

    // Toggle helper when clicked
    function toggleSwitch(id) {
        const checkbox = document.getElementById(id);
        if (!checkbox) return;
        const isChecked = checkbox.checked;
        
        localStorage.setItem(id, isChecked ? 'true' : 'false');
        
        if (isChecked) {
            checkbox.classList.remove('left-0', 'border-slate-200');
            checkbox.classList.add('left-6', 'border-primary');
            checkbox.nextElementSibling.classList.remove('bg-slate-200');
            checkbox.nextElementSibling.classList.add('bg-primary');
        } else {
            checkbox.classList.remove('left-6', 'border-primary');
            checkbox.classList.add('left-0', 'border-slate-200');
            checkbox.nextElementSibling.classList.remove('bg-primary');
            checkbox.nextElementSibling.classList.add('bg-slate-200');
        }
    }

    function toggleDarkMode() {
        const checkbox = document.getElementById('dark-mode-toggle-checkbox');
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            localStorage.setItem('color-theme', 'light');
            if (checkbox) checkbox.checked = false;
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
            localStorage.setItem('color-theme', 'dark');
            if (checkbox) checkbox.checked = true;
        }
    }

    function setLanguage(lang) {
        localStorage.setItem('app-lang', lang);
        updateLangVisuals(lang);
        if (lang === 'en') {
            alert('English localization will be loaded in the next updates.');
        } else {
            alert('تم تعيين اللغة العربية كلغة افتراضية للوحة التحكم.');
        }
    }

    function updateLangVisuals(lang) {
        const btnAr = document.getElementById('lang-ar');
        const btnEn = document.getElementById('lang-en');
        if (!btnAr || !btnEn) return;

        if (lang === 'ar') {
            btnAr.className = "px-3 py-1 text-xs font-bold rounded-full transition-all bg-white dark:bg-card-dark shadow-sm text-slate-900 dark:text-white";
            btnEn.className = "px-3 py-1 text-xs font-medium rounded-full transition-all text-slate-500 hover:text-slate-900 dark:hover:text-white";
        } else {
            btnEn.className = "px-3 py-1 text-xs font-bold rounded-full transition-all bg-white dark:bg-card-dark shadow-sm text-slate-900 dark:text-white";
            btnAr.className = "px-3 py-1 text-xs font-medium rounded-full transition-all text-slate-500 hover:text-slate-900 dark:hover:text-white";
        }
    }
</script>
@endpush
