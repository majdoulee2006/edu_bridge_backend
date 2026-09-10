@extends('layouts.admin')
@section('title', 'تعديل حساب مدرب / معلم')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.accounts') }}"
           class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">تعديل حساب مدرب / معلم</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">تعديل بيانات المعلم: {{ $usr->full_name }}</span>
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

        <!-- الاسم الأول + الاسم الثاني (النسبة / الكنية) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الأول</label>
                <div class="relative group">
                    <input required name="first_name" value="{{ old('first_name', $usr->first_name) }}" type="text" placeholder="مثال: سامر"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person</span>
                </div>
                @error('first_name')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الثاني / الكنية</label>
                <div class="relative group">
                    <input required name="last_name" value="{{ old('last_name', $usr->last_name) }}" type="text" placeholder="مثال: المحمد"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person_outline</span>
                </div>
                @error('last_name')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- رقم الهاتف + اسم المستخدم -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">رقم الهاتف</label>
                <input name="phone" value="{{ old('phone', $usr->phone) }}" type="tel" dir="ltr" placeholder="09xxxxxxxx"
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                @error('phone')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">اسم المستخدم (Username)</label>
                <div class="relative flex items-center bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all overflow-hidden" dir="ltr">
                    <span class="pl-4 pr-3 text-slate-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">badge</span>
                    </span>
                    <input required name="username" class="flex-1 bg-transparent border-none outline-none focus:outline-none focus:ring-0 py-3.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 font-medium min-w-0" placeholder="teacher_user" type="text" value="{{ old('username', isset($usr) ? preg_replace('/@edu-bridge\.com$/i', '', $usr->username) : '') }}"/>
                    <span class="pr-4 pl-2 text-sm font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap select-none">
                        @edu-bridge.com
                    </span>
                </div>
                @error('username')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- البريد الإلكتروني -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
            <div class="relative flex items-center bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all overflow-hidden" dir="ltr">
                <span class="pl-4 pr-3 text-slate-400 flex items-center justify-center">
                    <span class="material-symbols-outlined">mail</span>
                </span>
                <input required name="email" autocomplete="off" class="flex-1 bg-transparent border-none outline-none focus:outline-none focus:ring-0 py-3.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 font-medium min-w-0" placeholder="teacher" type="text" value="{{ old('email', isset($usr) ? preg_replace('/@gmail\.com$/i', '', $usr->email) : '') }}"/>
                <span class="pr-4 pl-2 text-sm font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap select-none">
                    @gmail.com
                </span>
            </div>
            @error('email')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
        </div>

        <!-- القسم + الدورة -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">القسم</label>
                <div class="relative group">
                    <select required name="department" id="department-select" onchange="filterDeptData()"
                            class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                        <option disabled value="">اختر القسم</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->name }}" data-id="{{ $dept->department_id }}" {{ old('department', $teacher->department ?? '') == $dept->name ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
                @error('department')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الدورة</label>
                <div class="relative group">
                    <select required name="specialization" id="spec-select"
                            class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                        <option disabled value="">اختر القسم أولاً</option>
                    </select>
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
                @error('specialization')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- المواد التي يدرسها -->
        <div class="space-y-2">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">المواد التي يدرسها</label>
            <div class="bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl p-4 shadow-sm">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2" id="courses-container">
                    <div class="text-xs text-slate-500">الرجاء اختيار القسم أولاً</div>
                </div>
            </div>
            @error('courses')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
        </div>

        <!-- كلمة المرور -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">كلمة المرور (اتركها فارغة إذا لم ترد التغيير)</label>
                <div class="relative group">
                    <input name="password" autocomplete="new-password" type="password" placeholder="••••••••"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
                @error('password')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">تأكيد كلمة المرور</label>
                <div class="relative group">
                    <input name="password_confirmation" autocomplete="new-password" type="password" placeholder="••••••••"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
            </div>
        </div>

        <div class="pt-4">
            <button type="submit"
                    class="w-full bg-primary hover:bg-primary-dark text-primary-content font-bold text-lg rounded-2xl py-4 shadow-glow hover:shadow-lg active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                <span>حفظ التعديلات</span>
                <span class="material-symbols-outlined text-xl">save</span>
            </button>
        </div>
    </form>

@endsection

@push('scripts')
<script>
function togglePasswordVisibility(btn) {
    const input = btn.previousElementSibling;
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? 'visibility_off' : 'visibility';
}

const deptCourses = @json($deptCourses ?? []);
const deptBranches = @json($deptBranches ?? []);

function filterDeptData() {
    const select = document.getElementById('department-select');
    const selectedOption = select.options[select.selectedIndex];
    const deptId = selectedOption ? selectedOption.getAttribute('data-id') : null;
    
    const specSelect = document.getElementById('spec-select');
    const coursesContainer = document.getElementById('courses-container');
    
    specSelect.innerHTML = '';
    coursesContainer.innerHTML = '';
    
    if (!deptId) {
        specSelect.innerHTML = '<option disabled selected value="">اختر القسم أولاً</option>';
        coursesContainer.innerHTML = '<div class="text-xs text-slate-500">الرجاء اختيار القسم أولاً</div>';
        return;
    }
    
    // Fill Courses/Branches
    const branches = deptBranches[deptId] || [];
    if (branches.length === 0) {
        specSelect.innerHTML = '<option disabled value="">لا توجد دورات لهذا القسم</option>';
    } else {
        specSelect.innerHTML = '<option disabled value="">اختر الدورة</option>';
        branches.forEach(b => {
            specSelect.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
    }
    
    // Fill Courses
    const courses = deptCourses[deptId] || [];
    if (courses.length === 0) {
        coursesContainer.innerHTML = '<div class="text-xs text-slate-500">لا توجد مواد لهذا القسم</div>';
    } else {
        courses.forEach(c => {
            coursesContainer.innerHTML += `
            <label class="cursor-pointer flex items-center gap-2.5 p-2.5 rounded-xl border border-transparent hover:border-primary/30 hover:bg-primary/5 transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/10">
                <input type="checkbox" name="courses[]" value="${c.id}" class="w-4 h-4 accent-primary cursor-pointer flex-shrink-0">
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 leading-tight">${c.title}</span>
            </label>`;
        });
    }
}

// Trigger initial filter if old value exists
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('department-select').value) {
        filterDeptData();
        // Re-select specialization
        const currentSpec = '{{ old('specialization', $teacher->specialization ?? '') }}';
        if (currentSpec) {
            document.getElementById('spec-select').value = currentSpec;
        }
        // Re-check courses
        const teacherCourses = @json($teacherCourses ?? []);
        teacherCourses.forEach(cid => {
            const cb = document.querySelector(`input[name="courses[]"][value="${cid}"]`);
            if(cb) cb.checked = true;
        });
    }
});
</script>
@endpush
