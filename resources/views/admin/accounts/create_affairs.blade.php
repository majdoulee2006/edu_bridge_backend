@extends('layouts.admin')

@section('title', 'إنشاء حساب موظف شؤون')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.accounts') }}"
           class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">إنشاء حساب موظف شؤون</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">إضافة موظف شؤون طلاب/إداري جديد في النظام</span>
        </div>
    </div>

    <form class="space-y-5 pb-10" action="{{ route('admin.accounts.store.affairs') }}" method="POST" autocomplete="off">
        @csrf
        
        <!-- الاسم الأول والاسم الثاني (النسبة / الكنية) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الأول</label>
                <div class="relative group">
                    <input required name="first_name" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" placeholder="مثال: محمد" type="text" value="{{ old('first_name') }}"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">person</span>
                </div>
                @error('first_name')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الثاني / الكنية</label>
                <div class="relative group">
                    <input required name="last_name" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" placeholder="مثال: الأحمد" type="text" value="{{ old('last_name') }}"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">person_outline</span>
                </div>
                @error('last_name')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- رقم الهاتف + اسم المستخدم -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">رقم الهاتف</label>
                <input name="phone" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="09xxxxxxxx" type="tel" value="{{ old('phone') }}"/>
                @error('phone')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">اسم المستخدم (Username)</label>
                <div class="relative flex items-center bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all overflow-hidden" dir="ltr">
                    <span class="pl-4 pr-3 text-slate-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">badge</span>
                    </span>
                    <input required name="username" class="flex-1 bg-transparent border-none outline-none focus:outline-none focus:ring-0 py-3.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 font-medium min-w-0" placeholder="affairs_user" type="text" value="{{ old('username') }}"/>
                    <span class="pr-4 pl-2 text-sm font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap select-none">
                        @edu-bridge.com
                    </span>
                </div>
                @error('username')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="space-y-1.5 mb-4">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
            <div class="relative flex items-center bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all overflow-hidden" dir="ltr">
                <span class="pl-4 pr-3 text-slate-400 flex items-center justify-center">
                    <span class="material-symbols-outlined">mail</span>
                </span>
                <input required name="email" autocomplete="off" class="flex-1 bg-transparent border-none outline-none focus:outline-none focus:ring-0 py-3.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 font-medium min-w-0" placeholder="affairs" type="text" value="{{ old('email') }}"/>
                <span class="pr-4 pl-2 text-sm font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap select-none">
                    @gmail.com
                </span>
            </div>
            @error('email')
                <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">كلمة المرور</label>
                <div class="relative group">
                    <input required name="password" autocomplete="new-password" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" type="password" placeholder="••••••••"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-slate-600 dark:hover:text-slate-200 transition-colors" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
                @error('password')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">تأكيد كلمة المرور</label>
                <div class="relative group">
                    <input required name="password_confirmation" autocomplete="new-password" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" type="password" placeholder="••••••••"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-slate-600 dark:hover:text-slate-200 transition-colors" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
            </div>
        </div>

        <div class="pt-4">
            <button class="w-full bg-primary hover:bg-primary-dark text-primary-content font-bold text-lg rounded-2xl py-4 shadow-glow hover:shadow-lg active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2" type="submit">
                <span>إنشاء الحساب</span>
                <span class="material-symbols-outlined text-xl">arrow_back</span>
            </button>
        </div>
    </form>

    <script>
        function togglePasswordVisibility(btn) {
            const input = btn.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'visibility';
            } else {
                input.type = 'password';
                btn.textContent = 'visibility_off';
            }
        }
    </script>
@endsection
