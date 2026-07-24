@extends('layouts.admin')
@section('title', 'إنشاء حساب مدرب / معلم')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.accounts') }}"
           class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">إنشاء حساب مدرب / معلم</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">إضافة ملف مدرب أو معلم جديد في النظام</span>
        </div>
    </div>

    <form class="space-y-5 pb-10" action="{{ route('admin.accounts.store.teacher') }}" method="POST">
        @csrf

        <!-- الاسم الأول + الاسم الثاني (النسبة / الكنية) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الأول</label>
                <div class="relative group">
                    <input required name="first_name" value="{{ old('first_name') }}" type="text" placeholder="مثال: سامر"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person</span>
                </div>
                @error('first_name')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الثاني / الكنية</label>
                <div class="relative group">
                    <input required name="last_name" value="{{ old('last_name') }}" type="text" placeholder="مثال: المحمد"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person_outline</span>
                </div>
                @error('last_name')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- رقم الهاتف + البريد -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">رقم الهاتف</label>
                <input name="phone" value="{{ old('phone') }}" type="tel" dir="ltr" placeholder="09xxxxxxxx"
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                @error('phone')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
                <div class="relative group">
                    <input required name="email" value="{{ old('email') }}" type="email" dir="ltr" placeholder="teacher@test.com"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">mail</span>
                </div>
                @error('email')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- القسم + الدورة -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">القسم</label>
                <div class="relative group">
                    <select required name="department" id="department-select" onchange="filterDeptData()"
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
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الدورة</label>
                <div class="relative group">
                    <select required name="specialization" id="spec-select"
                            class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all appearance-none shadow-sm cursor-pointer">
                        <option disabled selected value="">اختر القسم أولاً</option>
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
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">كلمة المرور</label>
                <div class="relative group">
                    <input required name="password" type="password" placeholder="••••••••"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
                @error('password')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">تأكيد كلمة المرور</label>
                <div class="relative group">
                    <input required name="password_confirmation" type="password" placeholder="••••••••"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer" onclick="togglePasswordVisibility(this)">visibility_off</span>
                </div>
            </div>
        </div>

        <div class="pt-4">
            <button type="submit"
                    class="w-full bg-primary hover:bg-primary-dark text-primary-content font-bold text-lg rounded-2xl py-4 shadow-glow hover:shadow-lg active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                <span>إنشاء الحساب</span>
                <span class="material-symbols-outlined text-xl">arrow_back</span>
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
        specSelect.innerHTML = '<option disabled selected value="">لا توجد دورات لهذا القسم</option>';
    } else {
        specSelect.innerHTML = '<option disabled selected value="">اختر الدورة</option>';
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
        // optionally, we would need to re-select old('specialization') and old('courses') here...
    }
});
</script>
@endpush
