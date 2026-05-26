@extends('layouts.admin')

@section('title', 'الملف الشخصي')
@section('header-title', 'الملف الشخصي')
@section('header-subtitle', 'عرض وتعديل معلومات حسابك')

@section('content')

    <!-- Profile Header Info -->
    <div class="flex flex-col items-center gap-4 py-4">
        <div class="relative group">
            <div class="w-32 h-32 rounded-full p-1 bg-white dark:bg-surface-dark shadow-soft border border-slate-100 dark:border-slate-800">
                <div class="w-full h-full rounded-full bg-primary text-primary-content flex items-center justify-center font-bold text-5xl shadow-glow">
                    {{ mb_substr(Auth::user()->full_name ?? 'م', 0, 1) }}
                </div>
            </div>
            <button class="absolute bottom-1 right-1 bg-slate-800 dark:bg-white text-white dark:text-slate-850 p-2 rounded-full shadow-lg hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-sm">photo_camera</span>
            </button>
        </div>
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ Auth::user()->full_name }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">مدير النظام التعليمي</p>
        </div>
    </div>

    <!-- Contact Info Section -->
    <div class="flex flex-col gap-3">
        <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 px-2">معلومات الاتصال</h3>
        
        <form action="{{ route('admin.profile.update') }}" method="POST" class="flex flex-col gap-0 bg-surface-light dark:bg-surface-dark rounded-2xl shadow-soft overflow-hidden border border-slate-100 dark:border-slate-800/50">
            @csrf
            
            <!-- Full Name field -->
            <div class="flex flex-col p-4 border-b border-slate-100 dark:border-slate-800">
                <label class="text-xs text-slate-400 dark:text-slate-500 mb-1.5 font-bold">الاسم الكامل</label>
                <input type="text" name="full_name" value="{{ Auth::user()->full_name }}" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none focus:border-primary" required>
            </div>

            <!-- Phone field -->
            <div class="flex flex-col p-4 border-b border-slate-100 dark:border-slate-800">
                <label class="text-xs text-slate-400 dark:text-slate-500 mb-1.5 font-bold">رقم الهاتف</label>
                <input type="text" name="phone" value="{{ Auth::user()->phone ?? 'غير متوفر' }}" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none focus:border-primary text-left" dir="ltr">
            </div>

            <!-- Email field (disabled/read-only or changeable) -->
            <div class="flex flex-col p-4 border-b border-slate-100 dark:border-slate-800">
                <label class="text-xs text-slate-400 dark:text-slate-500 mb-1.5 font-bold">البريد الإلكتروني</label>
                <input type="email" value="{{ Auth::user()->email }}" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl px-3 py-2 text-sm font-semibold text-slate-400 dark:text-slate-500 cursor-not-allowed" readonly>
            </div>

            <div class="p-4 bg-slate-50/50 dark:bg-slate-900/10 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-primary text-primary-content text-xs font-bold rounded-full shadow-glow hover:scale-105 active:scale-95 transition-all">
                    حفظ التغييرات
                </button>
            </div>
        </form>
    </div>

    <!-- Security & Password change Section -->
    <div class="flex flex-col gap-3">
        <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 px-2">الأمان</h3>
        
        <div class="bg-surface-light dark:bg-surface-dark rounded-2xl shadow-soft p-5 border border-slate-100 dark:border-slate-800/50">
            <form action="{{ route('admin.profile.password') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                
                <h4 class="font-bold text-sm text-slate-800 dark:text-white mb-1 flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-500 text-lg">lock</span>
                    تغيير كلمة المرور
                </h4>

                <div class="flex flex-col">
                    <label class="text-xs text-slate-400 dark:text-slate-500 mb-1">كلمة المرور الحالية</label>
                    <input type="password" name="current_password" placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none focus:border-primary" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <label class="text-xs text-slate-400 dark:text-slate-500 mb-1">كلمة المرور الجديدة</label>
                        <input type="password" name="new_password" placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none focus:border-primary" required>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs text-slate-400 dark:text-slate-500 mb-1">تأكيد كلمة المرور الجديدة</label>
                        <input type="password" name="new_password_confirmation" placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none focus:border-primary" required>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-5 py-2.5 bg-primary text-primary-content text-xs font-bold rounded-full shadow-glow hover:scale-105 active:scale-95 transition-all">
                        تحديث كلمة المرور
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Personal details Section -->
    <div class="flex flex-col gap-3">
        <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 px-2">البيانات الشخصية</h3>
        
        <div class="bg-surface-light dark:bg-surface-dark rounded-2xl shadow-soft p-5 border border-slate-100 dark:border-slate-800/50 flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <span class="text-xs text-slate-400 dark:text-slate-500">تاريخ الميلاد</span>
                <span class="text-sm font-bold text-slate-800 dark:text-white font-[Lexend]">15 / 05 / 1985</span>
            </div>
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <span class="text-xs text-slate-400 dark:text-slate-500">الجنس</span>
                <span class="text-sm font-bold text-slate-800 dark:text-white">ذكر</span>
            </div>
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <span class="text-xs text-slate-400 dark:text-slate-500">تاريخ الانضمام</span>
                <span class="text-sm font-bold text-slate-800 dark:text-white font-[Lexend]">01 / 09 / 2020</span>
            </div>
        </div>
    </div>

    <div class="h-12"></div>

@endsection
