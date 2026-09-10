@extends('layouts.admin')

@section('title', 'تعديل حساب طالب')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.accounts') }}"
           class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">تعديل حساب طالب</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">تعديل بيانات الطالب: {{ $usr->full_name }}</span>
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
                    <input required name="last_name" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm" placeholder="مثال: علي" type="text" value="{{ old('last_name', $usr->last_name) }}"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">person_outline</span>
                </div>
                @error('last_name')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Phone & University ID -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">رقم الهاتف</label>
                <div class="relative group">
                    <input name="phone" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="09xxxxxxxx" type="tel" value="{{ old('phone', $usr->phone) }}"/>
                </div>
                @error('phone')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الرقم الجامعي (رمز الحساب)</label>
                <div class="relative group">
                    <input required name="university_id" autocomplete="off" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="2023xxxx" type="text" value="{{ old('university_id', $usr->university_id) }}"/>
                </div>
                @error('university_id')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Email -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
            <div class="relative flex items-center bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all overflow-hidden" dir="ltr">
                <span class="pl-4 pr-3 text-slate-400 flex items-center justify-center">
                    <span class="material-symbols-outlined">mail</span>
                </span>
                <input required name="email" autocomplete="off" class="flex-1 bg-transparent border-none outline-none focus:outline-none focus:ring-0 py-3.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 font-medium min-w-0" placeholder="student" type="text" value="{{ old('email', isset($usr) ? preg_replace('/@gmail\.com$/i', '', $usr->email) : '') }}"/>
                <span class="pr-4 pl-2 text-sm font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap select-none">
                    @gmail.com
                </span>
            </div>
            @error('email')
                <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Telegram Chat ID -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">Telegram Chat ID (اختياري)</label>
            <div class="relative group">
                <input name="telegram_chat_id" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm" dir="ltr" placeholder="مثال: 123456789" type="text" value="{{ old('telegram_chat_id', $student->telegram_chat_id ?? '') }}" title="احصل عليه من بوت الجامعة لإرسال الإشعارات وبيانات الدخول فوراً"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">send</span>
            </div>
            @error('telegram_chat_id')
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
                        <option disabled value="">اختر القسم</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->name }}" data-id="{{ $dept->department_id }}" {{ old('department', $student->department ?? '') == $dept->name ? 'selected' : '' }}>{{ $dept->name }}</option>
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
                        <option disabled value="">اختر السنة الدراسية</option>
                        <option value="السنة الأولى" {{ old('level', $student->level ?? '') == 'السنة الأولى' ? 'selected' : '' }}>السنة الأولى</option>
                        <option value="السنة الثانية" {{ old('level', $student->level ?? '') == 'السنة الثانية' ? 'selected' : '' }}>السنة الثانية</option>
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
                @error('level')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- الدورة -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الدورة</label>
            <div class="relative group">
                <select required name="program_id" id="prog-sel"
                        class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                    <option disabled value="">اختر القسم أولاً...</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id }}"
                                {{ old('program_id', $student->program_id ?? '') == $prog->id ? 'selected' : '' }}>
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
        
        // Run on load
        document.addEventListener('DOMContentLoaded', function() {
            // Keep the selected value
            const currentProg = '{{ old('program_id', $student->program_id ?? '') }}';
            const deptSel = document.getElementById('dept-sel');
            const deptId = deptSel.options[deptSel.selectedIndex]?.dataset.id;
            const progSel = document.getElementById('prog-sel');
            progSel.innerHTML = '<option disabled value="">اختر الدورة...</option>';
            @foreach($programs as $prog)
            if ('{{ $prog->department_id }}' === deptId) {
                const o = document.createElement('option');
                o.value = '{{ $prog->id }}';
                o.textContent = '{{ $prog->name }}';
                if(o.value == currentProg) o.selected = true;
                progSel.appendChild(o);
            }
            @endforeach
        });
        </script>

        <!-- Birth Date & Gender -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">تاريخ الميلاد</label>
                <div class="relative group">
                    <input required name="birth_date" class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm text-right min-h-[54px]" type="date" value="{{ old('birth_date', $student->birth_date ?? '') }}"/>
                </div>
                @error('birth_date')
                    <span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الجنس</label>
                <div class="flex bg-white dark:bg-surface-dark p-1 rounded-2xl border border-slate-200 dark:border-slate-700/50 min-h-[54px] shadow-sm">
                    <label class="flex-1 relative cursor-pointer">
                        <input class="peer hidden" name="gender" type="radio" value="ذكر" {{ old('gender', $student->gender ?? '') == 'ذكر' ? 'checked' : '' }}/>
                        <div class="w-full h-full flex items-center justify-center gap-1.5 rounded-[0.85rem] transition-all text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 peer-checked:bg-primary peer-checked:text-primary-content peer-checked:font-bold peer-checked:shadow-sm">
                            <span class="material-symbols-outlined text-[18px]">male</span>
                            <span class="text-xs">ذكر</span>
                        </div>
                    </label>
                    <label class="flex-1 relative cursor-pointer">
                        <input class="peer hidden" name="gender" type="radio" value="أنثى" {{ old('gender', $student->gender ?? '') == 'أنثى' ? 'checked' : '' }}/>
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
