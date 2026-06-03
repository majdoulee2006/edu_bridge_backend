@extends('layouts.admin')

@section('title', 'إنشاء حساب طالب')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.accounts') }}"
           class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">إنشاء حساب طالب</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">إضافة ملف طالب جديد في النظام</span>
        </div>
    </div>

    <form class="space-y-5 pb-10" action="{{ route('admin.accounts.store.student') }}" method="POST">
        @csrf
        
        <!-- Full Name -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الكامل</label>
            <div class="relative group">
                <input required name="full_name" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" placeholder="مثال: أحمد محمد علي" type="text" value="{{ old('full_name') }}"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">person</span>
            </div>
            @error('full_name')
                <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Phone & University ID -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">رقم الهاتف</label>
                <div class="relative group">
                    <input name="phone" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="09xxxxxxxx" type="tel" value="{{ old('phone') }}"/>
                </div>
                @error('phone')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الرقم الجامعي (رمز الحساب)</label>
                <div class="relative group">
                    <input required name="university_id" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="2023xxxx" type="text" value="{{ old('university_id') }}"/>
                </div>
                @error('university_id')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Email -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
            <div class="relative group">
                <input required name="email" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="student@university.edu" type="email" value="{{ old('email') }}"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">mail</span>
            </div>
            @error('email')
                <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Department & Level & Program -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">القسم</label>
                <div class="relative group">
                    <select required name="department" id="dept-sel"
                            onchange="filterPrograms(this)"
                            class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                        <option disabled selected value="">اختر القسم</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->name }}" data-id="{{ $dept->department_id }}" {{ old('department') == $dept->name ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
                @error('department')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">السنة الدراسية</label>
                <div class="relative group">
                    <select required name="level" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                        <option disabled selected value="">اختر السنة الدراسية</option>
                        <option value="السنة الأولى" {{ old('level') == 'السنة الأولى' ? 'selected' : '' }}>السنة الأولى</option>
                        <option value="السنة الثانية" {{ old('level') == 'السنة الثانية' ? 'selected' : '' }}>السنة الثانية</option>
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
                @error('level')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- الدورة (البرنامج) -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الدورة / التخصص</label>
            <div class="relative group">
                <select required name="program_id" id="prog-sel"
                        class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                    <option disabled selected value="">اختر القسم أولاً...</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id }}"
                                {{ old('program_id') == $prog->id ? 'selected' : '' }}>
                            {{ $prog->name }}
                        </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
            </div>
            @error('program_id')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
        </div>

        <script>
        function filterPrograms(deptSel) {
            const deptId = deptSel.options[deptSel.selectedIndex]?.dataset.id;
            const progSel = document.getElementById('prog-sel');
            progSel.innerHTML = '<option disabled selected value="">اختر الدورة...</option>';
            @foreach($programs as $prog)
            if ('{{ $prog->department_id }}' === deptId) {
                const o = document.createElement('option');
                o.value = '{{ $prog->id }}';
                o.textContent = '{{ $prog->name }}';
                progSel.appendChild(o);
            }
            @endforeach
        }
        </script>

        <!-- Birth Date & Gender -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">تاريخ الميلاد</label>
                <div class="relative group">
                    <input required name="birth_date" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm text-right min-h-[54px]" type="date" value="{{ old('birth_date') }}"/>
                </div>
                @error('birth_date')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الجنس</label>
                <div class="flex bg-white dark:bg-surface-dark p-1 rounded-2xl border border-slate-200 dark:border-slate-700/50 min-h-[54px] shadow-sm">
                    <label class="flex-1 relative cursor-pointer">
                        <input class="peer hidden" name="gender" type="radio" value="ذكر" {{ old('gender', 'ذكر') == 'ذكر' ? 'checked' : '' }}/>
                        <div class="w-full h-full flex items-center justify-center gap-1.5 rounded-[0.85rem] transition-all text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 peer-checked:bg-primary peer-checked:text-primary-content peer-checked:font-bold peer-checked:shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">male</span>
                            <span class="text-xs">ذكر</span>
                        </div>
                    </label>
                    <label class="flex-1 relative cursor-pointer">
                        <input class="peer hidden" name="gender" type="radio" value="أنثى" {{ old('gender') == 'أنثى' ? 'checked' : '' }}/>
                        <div class="w-full h-full flex items-center justify-center gap-1.5 rounded-[0.85rem] transition-all text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 peer-checked:bg-primary peer-checked:text-primary-content peer-checked:font-bold peer-checked:shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">female</span>
                            <span class="text-xs">أنثى</span>
                        </div>
                    </label>
                </div>
                @error('gender')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
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
    </script>
@endsection
