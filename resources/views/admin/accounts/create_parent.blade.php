@extends('layouts.admin')
@section('title', 'إنشاء حساب ولي أمر')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.accounts') }}"
           class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">إنشاء حساب ولي أمر</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">إضافة ولي أمر جديد وربطه بالطلاب</span>
        </div>
    </div>

    <form class="space-y-5 pb-10" action="{{ route('admin.accounts.store.parent') }}" method="POST">
        @csrf

        <!-- الاسم الكامل -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الكامل</label>
            <div class="relative group">
                <input required name="full_name" value="{{ old('full_name') }}" type="text" placeholder="مثال: يوسف الخالد"
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person</span>
            </div>
            @error('full_name')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
        </div>

        <!-- رقم الهاتف + اسم المستخدم -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">رقم الهاتف</label>
                <input required name="phone" value="{{ old('phone') }}" type="tel" dir="ltr" placeholder="09xxxxxxxx"
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                @error('phone')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">اسم المستخدم</label>
                <input required name="username" value="{{ old('username') }}" type="text" dir="ltr" placeholder="yousef_parent"
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                @error('username')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <!-- البريد الإلكتروني -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
            <div class="relative group">
                <input required name="email" value="{{ old('email') }}" type="email" dir="ltr" placeholder="parent@test.com"
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">mail</span>
            </div>
            @error('email')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
        </div>

        <!-- عدد الأبناء -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">عدد الأبناء</label>
            <input type="number" name="children_count" id="children_count_input"
                   min="1" max="10" value="{{ old('children_count', 1) }}"
                   oninput="updateChildrenFields(parseInt(this.value) || 1)"
                   class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all shadow-sm"
                   placeholder="أدخل عدد الأبناء..."/>
        </div>

        <!-- حقول الأرقام الجامعية للأبناء — تتولّد ديناميكياً --}}
        <div id="children-fields" class="space-y-3"></div>

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

const ordinals = ['الأول','الثاني','الثالث','الرابع','الخامس','السادس','السابع','الثامن','التاسع','العاشر'];

function updateChildrenFields(count) {
    count = Math.max(1, Math.min(parseInt(count) || 1, 10));
    const container = document.getElementById('children-fields');
    container.innerHTML = '';
    for (let i = 1; i <= count; i++) {
        container.innerHTML += `
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">
                الرقم الجامعي للابن ${ordinals[i-1] || i}
            </label>
            <div class="relative group">
                <input name="children_university_ids[]"
                       type="text" dir="ltr"
                       placeholder="مثال: 2023xxxx"
                       id="uid-input-${i}"
                       oninput="lookupStudent(${i}, this.value)"
                       ${i === 1 ? 'required' : ''}
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" id="uid-icon-${i}">badge</span>
            </div>
            <div id="uid-result-${i}" class="hidden text-xs px-3 py-2 rounded-xl font-semibold"></div>
        </div>`;
    }
}

let lookupTimers = {};
function lookupStudent(index, uid) {
    const resultDiv = document.getElementById('uid-result-' + index);
    const icon = document.getElementById('uid-icon-' + index);

    clearTimeout(lookupTimers[index]);
    if (!uid || uid.length < 4) { resultDiv.classList.add('hidden'); return; }

    lookupTimers[index] = setTimeout(() => {
        fetch('/api/student/info/' + encodeURIComponent(uid))
            .then(r => r.json())
            .then(data => {
                if (data && data.full_name) {
                    resultDiv.className = 'text-xs px-3 py-2 rounded-xl font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800';
                    resultDiv.innerHTML = '✓ ' + data.full_name + ' — ' + (data.department || '') + ' — ' + (data.level || '');
                    resultDiv.classList.remove('hidden');
                    icon.textContent = 'check_circle';
                    icon.style.color = '#16a34a';
                } else {
                    resultDiv.className = 'text-xs px-3 py-2 rounded-xl font-semibold bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800';
                    resultDiv.textContent = '✗ الرقم الجامعي غير موجود';
                    resultDiv.classList.remove('hidden');
                    icon.textContent = 'error';
                    icon.style.color = '#dc2626';
                }
            })
            .catch(() => resetResult(index));
    }, 500);
}

function resetResult(index) {
    const r = document.getElementById('uid-result-' + index);
    r.classList.add('hidden');
    const icon = document.getElementById('uid-icon-' + index);
    icon.textContent = 'badge';
    icon.style.color = '';
}

// تهيئة العدد عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => updateChildrenFields({{ old('children_count', 1) }}));
</script>
@endpush
