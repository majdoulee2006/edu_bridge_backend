@extends('layouts.admin')

@section('title', 'تعديل حساب رئيس قسم')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.accounts') }}"
           class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">تعديل حساب رئيس قسم</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">تعديل بيانات رئيس القسم: {{ $usr->full_name }}</span>
        </div>
    </div>

    <form class="space-y-5 pb-10" action="{{ route('admin.accounts.update', $usr->user_id) }}" method="POST" autocomplete="off">
        @csrf
        
        <!-- حالة الحساب -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">حالة الحساب</label>
            <div class="relative group">
                <select required name="status" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                    <option value="active" {{ old('status', $usr->status) == 'active' ? 'selected' : '' }}>نشط (Active)</option>
                    <option value="inactive" {{ old('status', $usr->status) == 'inactive' ? 'selected' : '' }}>موقوف (Inactive)</option>
                </select>
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
            </div>
            @error('status')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
        </div>

        <!-- الاسم الأول والاسم الثاني (النسبة / الكنية) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الأول</label>
                <div class="relative group">
                    <input required name="first_name" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" placeholder="مثال: أحمد" type="text" value="{{ old('first_name', $usr->first_name) }}"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">person</span>
                </div>
                @error('first_name')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الثاني / الكنية</label>
                <div class="relative group">
                    <input required name="last_name" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" placeholder="مثال: ديب" type="text" value="{{ old('last_name', $usr->last_name) }}"/>
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
                <input name="phone" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="09xxxxxxxx" type="tel" value="{{ old('phone', $usr->phone) }}"/>
                @error('phone')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">اسم المستخدم (Username)</label>
                <div class="relative flex items-center bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all overflow-hidden" dir="ltr">
                    <span class="pl-4 pr-3 text-slate-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">badge</span>
                    </span>
                    <input required name="username" class="flex-1 bg-transparent border-none outline-none focus:outline-none focus:ring-0 py-3.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 font-medium min-w-0" placeholder="issakurdi-head" type="text" value="{{ old('username', isset($usr) ? preg_replace('/@edu-bridge\.com$/i', '', $usr->username) : '') }}"/>
                    <span class="pr-4 pl-2 text-sm font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap select-none">
                        @edu-bridge.com
                    </span>
                </div>
                @error('username')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Email & Department Allocation -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
                <div class="relative flex items-center bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all overflow-hidden" dir="ltr">
                    <span class="pl-4 pr-3 text-slate-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">mail</span>
                    </span>
                    <input required name="email" autocomplete="off" class="flex-1 bg-transparent border-none outline-none focus:outline-none focus:ring-0 py-3.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 font-medium min-w-0" placeholder="issakurdi" type="text" value="{{ old('email', isset($usr) ? preg_replace('/@gmail\.com$/i', '', $usr->email) : '') }}"/>
                    <span class="pr-4 pl-2 text-sm font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap select-none">
                        @gmail.com
                    </span>
                </div>
                @error('email')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">القسم المخصص للرئاسة</label>
                <div class="relative group">
                    <select required name="department_id" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                        <option disabled value="">اختر القسم</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}" {{ old('department_id', $hod->department_id ?? '') == $dept->department_id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-primary transition-colors">expand_more</span>
                </div>
                @error('department_id')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Password -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">كلمة المرور (اتركها فارغة إذا لم ترد التغيير)</label>
                <div class="relative group">
                    <input name="password" autocomplete="new-password" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" type="password" placeholder="••••••••"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-slate-600 dark:hover:text-slate-200 transition-colors" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
                @error('password')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">تأكيد كلمة المرور</label>
                <div class="relative group">
                    <input name="password_confirmation" autocomplete="new-password" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" type="password" placeholder="••••••••"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-slate-600 dark:hover:text-slate-200 transition-colors" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
            </div>
        </div>

        <div class="pt-4">
            <button class="w-full bg-primary hover:bg-primary-dark text-primary-content font-bold text-lg rounded-2xl py-4 shadow-glow hover:shadow-lg active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2" type="submit">
                <span>حفظ التعديلات</span>
                <span class="material-symbols-outlined text-xl">save</span>
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
