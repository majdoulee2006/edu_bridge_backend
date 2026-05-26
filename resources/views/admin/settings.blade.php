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
                <input class="w-full h-1 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer" max="100" min="0" type="range" value="50"/>
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

    <!-- Language Settings -->
    <div class="bg-white dark:bg-card-dark rounded-3xl p-5 shadow-soft border border-slate-100 dark:border-slate-800/50 transition-colors space-y-4">
        <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-2">اللغة والترجمة</h4>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-[20px]">language</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">لغة لوحة التحكم</span>
            </div>
            <div class="flex bg-slate-100 dark:bg-slate-800 rounded-full p-1 border border-slate-200/50 dark:border-slate-700">
                <button class="px-3 py-1 text-xs font-bold rounded-full bg-white dark:bg-card-dark shadow-sm text-slate-900 dark:text-white">عربي</button>
                <button class="px-3 py-1 text-xs font-medium rounded-full text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">EN</button>
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
                <input checked class="absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 left-6 border-primary" id="notif-toggle" name="toggle" type="checkbox"/>
                <label class="block overflow-hidden h-6 rounded-full bg-primary cursor-pointer transition-colors duration-300" for="notif-toggle"></label>
            </div>
        </div>

        <!-- Sounds Switch -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-[20px]">volume_up</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">أصوات التنبيهات</span>
            </div>
            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                <input checked class="absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 left-6 border-primary" id="sound-toggle" name="toggle" type="checkbox"/>
                <label class="block overflow-hidden h-6 rounded-full bg-primary cursor-pointer transition-colors duration-300" for="sound-toggle"></label>
            </div>
        </div>

        <!-- Vibrations Switch -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-[20px]">vibration</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">الاهتزاز</span>
            </div>
            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                <input class="absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300 left-0 border-slate-200" id="vibrate-toggle" name="toggle" type="checkbox"/>
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

    <!-- Logout Action -->
    <form action="{{ route('admin.logout') }}" method="POST">
        @csrf
        <button type="submit" class="w-full bg-red-50 dark:bg-red-500/10 border border-red-100 dark:border-red-500/20 rounded-3xl p-4 flex items-center justify-center gap-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 active:scale-95 transition-all mb-8">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span class="text-sm font-bold">تسجيل الخروج من لوحة التحكم</span>
        </button>
    </form>

    <div class="h-10"></div>

@endsection

@push('scripts')
<script>
    // Sync checkbox with the page's current theme on load
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('dark-mode-toggle-checkbox');
        if (document.documentElement.classList.contains('dark')) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });
</script>
@endpush
