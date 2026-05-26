@extends('layouts.admin')

@section('title', 'إنشاء حساب ولي أمر')
@section('header-title', 'إنشاء حساب ولي أمر')
@section('header-subtitle', 'إضافة ولي أمر جديد وربطه بالطلاب')

@section('back-button')
    <a href="{{ route('admin.accounts') }}" class="p-2 -mr-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
        <span class="material-symbols-outlined text-slate-800 dark:text-white text-2xl">arrow_forward</span>
    </a>
@endsection

@section('content')

    <form class="space-y-5 pb-10" action="{{ route('admin.accounts.store.parent') }}" method="POST">
        @csrf
        
        <!-- Full Name -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الكامل</label>
            <div class="relative group">
                <input required name="full_name" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" placeholder="مثال: يوسف الخالد" type="text" value="{{ old('full_name') }}"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">person</span>
            </div>
            @error('full_name')
                <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Phone & Username -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">رقم الهاتف</label>
                <div class="relative group">
                    <input required name="phone" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="09xxxxxxxx" type="tel" value="{{ old('phone') }}"/>
                </div>
                @error('phone')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">اسم المستخدم</label>
                <div class="relative group">
                    <input required name="username" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="yousef_parent" type="text" value="{{ old('username') }}"/>
                </div>
                @error('username')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Email -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
            <div class="relative group">
                <input required name="email" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="parent@test.com" type="email" value="{{ old('email') }}"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">mail</span>
            </div>
            @error('email')
                <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Linked Children Search Checklist -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">ربط الأبناء (الطلاب)</label>
            <div class="bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl p-4 shadow-sm space-y-3">
                <div class="relative">
                    <input type="text" id="childrenSearch" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-2 px-3 pl-8 text-xs text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-1 focus:ring-primary" placeholder="ابحث عن ابن بالاسم أو الرقم الجامعي..."/>
                    <span class="material-symbols-outlined absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                </div>
                
                <div class="max-h-40 overflow-y-auto space-y-2 pr-1 custom-scrollbar" id="childrenList">
                    @foreach($students as $st)
                        <label class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer select-none border border-transparent hover:border-slate-100 dark:hover:border-slate-700 transition-all child-item" data-name="{{ $st->full_name }}" data-code="{{ $st->student_code }}">
                            <input type="checkbox" name="children[]" value="{{ $st->student_id }}" class="rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary focus:ring-offset-0 size-4"/>
                            <div class="flex flex-col min-w-0">
                                <span class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ $st->full_name }}</span>
                                <span class="text-[10px] text-slate-400">الرقم الجامعي: {{ $st->student_code }} - {{ $st->level }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
            @error('children')
                <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">كلمة المرور</label>
                <div class="relative group">
                    <input required name="password" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" type="password" placeholder="••••••••"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-slate-600 dark:hover:text-slate-200 transition-colors" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
                @error('password')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">تأكيد كلمة المرور</label>
                <div class="relative group">
                    <input required name="password_confirmation" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" type="password" placeholder="••••••••"/>
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

        // Live Search of Children
        document.getElementById('childrenSearch').addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase();
            document.querySelectorAll('.child-item').forEach(item => {
                const name = item.getAttribute('data-name').toLowerCase();
                const code = item.getAttribute('data-code').toLowerCase();
                if (name.includes(val) || code.includes(val)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
@endsection
