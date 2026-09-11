@extends('layouts.admin')
@section('title', 'تعديل حساب ولي أمر')

@section('content')

    {{-- ===== Page Header ===== --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.accounts') }}"
           class="w-10 h-10 rounded-2xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">تعديل حساب ولي أمر</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">تعديل بيانات ولي الأمر: {{ $usr->full_name }}</span>
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
                    <input required name="first_name" value="{{ old('first_name', $usr->first_name ?? '') }}" type="text" placeholder="مثال: يوسف" autocomplete="off"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person</span>
                </div>
                @error('first_name')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الثاني / الكنية</label>
                <div class="relative group">
                    <input required name="last_name" value="{{ old('last_name', $usr->last_name ?? '') }}" type="text" placeholder="مثال: الخالد" autocomplete="off"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person_outline</span>
                </div>
                @error('last_name')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">الاسم الكامل</label>
                <div class="relative group">
                    <input name="full_name" value="{{ old('full_name', $usr->full_name) }}" type="text" autocomplete="off"
                           class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person</span>
                </div>
                @error('full_name')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">رقم الهاتف</label>
                <input name="phone" value="{{ old('phone', $usr->phone) }}" type="tel" dir="ltr" placeholder="09xxxxxxxx" autocomplete="off"
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
                @error('phone')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">البريد الإلكتروني</label>
            <div class="relative flex items-center bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all overflow-hidden" dir="ltr">
                <span class="pl-4 pr-3 text-slate-400 flex items-center justify-center">
                    <span class="material-symbols-outlined">mail</span>
                </span>
                <input required name="email" autocomplete="new-email" class="flex-1 bg-transparent border-none outline-none focus:outline-none focus:ring-0 py-3.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 font-medium min-w-0" placeholder="parent" type="text" value="{{ old('email', isset($usr) ? preg_replace('/@gmail\.com$/i', '', $usr->email) : '') }}"/>
                <span class="pr-4 pl-2 text-sm font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap select-none">
                    @gmail.com
                </span>
            </div>
            @error('email')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
        </div>

        <!-- اسم المستخدم -->
        <div class="space-y-1.5">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">اسم المستخدم</label>
            <div class="relative group">
                <input name="username" value="{{ old('username', $usr->username) }}" type="text" dir="ltr" autocomplete="off"
                       class="w-full bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3.5 pl-10 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all shadow-sm"/>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">badge</span>
            </div>
            @error('username')<span class="text-xs text-red-500 font-semibold mr-1">{{ $message }}</span>@enderror
        </div>

        <!-- كلمة المرور -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

    <!-- أبناء ولي الأمر (نموذج مستقل عن نموذج تعديل الحساب) -->
    <div class="space-y-1.5 pb-10">
        <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mr-1">
            الأبناء المرتبطون بهذا الحساب ({{ $children->count() }})
        </label>
        <div class="bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($children as $child)
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-slate-800 dark:text-slate-100 font-semibold">{{ $child->full_name }}</span>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-primary" dir="ltr">{{ $child->university_id ?? 'بدون رقم جامعي' }}</span>
                        <form action="{{ route('admin.accounts.unlink_child', [$usr->user_id, $child->student_user_id]) }}" method="POST" onsubmit="return confirm('فك ربط {{ $child->full_name }} عن هذا الحساب؟');">
                            @csrf
                            <button type="submit" class="text-red-500 hover:text-red-600" title="فك الربط">
                                <span class="material-symbols-outlined text-[20px]">link_off</span>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-4 py-3 text-sm text-slate-400">لا يوجد أبناء مرتبطون بهذا الحساب حالياً.</div>
            @endforelse
        </div>

        <form action="{{ route('admin.accounts.link_child', $usr->user_id) }}" method="POST" class="flex items-center gap-2 mt-2">
            @csrf
            <input name="university_id" type="text" dir="ltr" placeholder="أدخلي الرقم الجامعي لربط ابن جديد"
                   class="flex-1 bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-700/50 rounded-2xl px-4 py-3 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-slate-400 shadow-sm"/>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-primary-content font-bold rounded-2xl px-5 py-3 shadow-sm transition-all flex items-center gap-1 whitespace-nowrap">
                <span class="material-symbols-outlined text-[20px]">link</span>
                <span>ربط</span>
            </button>
        </form>
    </div>

@endsection

@push('scripts')
<script>
function togglePasswordVisibility(btn) {
    const input = btn.previousElementSibling;
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? 'visibility_off' : 'visibility';
}
</script>
@endpush
