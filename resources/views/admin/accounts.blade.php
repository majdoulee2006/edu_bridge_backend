@extends('layouts.admin')

@section('title', 'إدارة الحسابات')
@section('header-title', 'الحسابات')
@section('header-subtitle', 'عرض وإدارة مستخدمي النظام بكل سهولة')

@section('content')
<div class="space-y-5">

    <!-- Top Action Bar (Minimal Header) -->
    <div class="bg-white dark:bg-surface-dark p-5 rounded-2xl shadow-soft border border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">manage_accounts</span>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    كافة الحسابات
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-primary/20 text-slate-900 dark:text-primary">
                        {{ $counts['all'] ?? 0 }}
                    </span>
                </h2>
                <p class="text-xs text-slate-400">قائمة بجميع المستخدمين المسجلين في المنصة</p>
            </div>
        </div>

        <!-- Single Create Account Button (+) -->
        <button type="button" onclick="openModal('createAccountModal')" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-slate-950 font-bold text-xs hover:bg-primary-hover transition-all shadow-md shadow-primary/20 active:scale-95 cursor-pointer shrink-0">
            <span class="material-symbols-outlined font-black text-lg">add</span>
            <span>إنشاء حساب جديد</span>
        </button>
    </div>

    <!-- Filter Pills & Search Bar -->
    <div class="bg-white dark:bg-surface-dark p-4 rounded-2xl shadow-soft border border-slate-100 dark:border-slate-800 space-y-3">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
            <a href="{{ route('admin.accounts', ['role' => 'all', 'search' => $search]) }}" 
               class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'all' ? 'bg-primary text-slate-950 font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>الكل</span>
                <span class="opacity-75">({{ $counts['all'] }})</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'student', 'search' => $search]) }}" 
               class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'student' ? 'bg-blue-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>الطلاب</span>
                <span class="opacity-75">({{ $counts['student'] }})</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'teacher', 'search' => $search]) }}" 
               class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'teacher' ? 'bg-emerald-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>المعلمون</span>
                <span class="opacity-75">({{ $counts['teacher'] }})</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'hod', 'search' => $search]) }}" 
               class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'hod' ? 'bg-purple-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>رؤساء الأقسام</span>
                <span class="opacity-75">({{ $counts['hod'] }})</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'parent', 'search' => $search]) }}" 
               class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'parent' ? 'bg-orange-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>أولياء الأمور</span>
                <span class="opacity-75">({{ $counts['parent'] }})</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'affairs', 'search' => $search]) }}" 
               class="px-3.5 py-2 rounded-xl font-bold text-xs shrink-0 transition-all flex items-center gap-1.5 {{ $roleFilter === 'affairs' ? 'bg-rose-500 text-white font-black shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>الشؤون</span>
                <span class="opacity-75">({{ $counts['affairs'] }})</span>
            </a>
        </div>

        <!-- Search Bar Input -->
        <form action="{{ route('admin.accounts') }}" method="GET" class="relative">
            <input type="hidden" name="role" value="{{ $roleFilter }}">
            <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
            <input type="text" 
                   name="search" 
                   value="{{ $search }}" 
                   placeholder="بحث بالاسم، اسم المستخدم، البريد، أو الهاتف..." 
                   class="w-full pr-10 pl-20 py-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary transition-colors">
            
            <div class="absolute left-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                @if(!empty($search))
                    <a href="{{ route('admin.accounts', ['role' => $roleFilter]) }}" class="px-2 py-1 text-[11px] font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        إلغاء
                    </a>
                @endif
                <button type="submit" class="px-3 py-1 bg-slate-800 text-white dark:bg-primary dark:text-slate-950 rounded-lg text-[11px] font-bold">
                    بحث
                </button>
            </div>
        </form>
    </div>

    <!-- Accounts Minimal Clean Table -->
    <div class="bg-white dark:bg-surface-dark rounded-2xl shadow-soft border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">المستخدم والنوع</th>
                        <th class="py-3 px-4">معرف الحساب</th>
                        <th class="py-3 px-4">بيانات التواصل</th>
                        <th class="py-3 px-4 text-center">الحالة</th>
                        <th class="py-3 px-4 text-center">التاريخ</th>
                        <th class="py-3 px-4 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($users as $usr)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- User & Role -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg font-bold shrink-0
                                        @if($usr->role_id == 3) bg-blue-500/10 text-blue-500
                                        @elseif($usr->role_id == 2) bg-emerald-500/10 text-emerald-500
                                        @elseif($usr->role_id == 5) bg-purple-500/10 text-purple-500
                                        @elseif($usr->role_id == 4) bg-orange-500/10 text-orange-500
                                        @elseif($usr->role_id == 6) bg-rose-500/10 text-rose-500
                                        @else bg-slate-500/10 text-slate-500
                                        @endif">
                                        <span class="material-symbols-outlined text-xl">
                                            @if($usr->role_id == 3) school
                                            @elseif($usr->role_id == 2) sports
                                            @elseif($usr->role_id == 5) supervisor_account
                                            @elseif($usr->role_id == 4) family_restroom
                                            @elseif($usr->role_id == 6) badge
                                            @else person
                                            @endif
                                        </span>
                                    </div>

                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $usr->full_name }}
                                        </div>
                                        <span class="text-[10px] font-semibold text-slate-400">
                                            @if($usr->role_id == 3) طالب
                                            @elseif($usr->role_id == 2) معلم / مدرب
                                            @elseif($usr->role_id == 5) رئيس قسم
                                            @elseif($usr->role_id == 4) ولي أمر
                                            @elseif($usr->role_id == 6) موظف شؤون
                                            @else مستخدم
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Username / University Code -->
                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-700 dark:text-slate-300">
                                {{ $usr->username }}
                                @if(!empty($usr->university_id))
                                    <span class="block text-[10px] text-slate-400 font-sans">({{ $usr->university_id }})</span>
                                @endif
                            </td>

                            <!-- Contact info -->
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400">
                                <div>{{ $usr->email ?? '-' }}</div>
                                @if(!empty($usr->phone))
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $usr->phone }}</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $usr->status === 'active' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $usr->status === 'active' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    {{ $usr->status === 'active' ? 'نشط' : 'معلق' }}
                                </span>
                            </td>

                            <!-- Date -->
                            <td class="py-3.5 px-4 text-center text-[11px] text-slate-400 font-mono">
                                {{ \Carbon\Carbon::parse($usr->created_at)->format('Y/m/d') }}
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-center">
                                <form action="{{ route('admin.accounts.delete_single', $usr->user_id) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد من حذف حساب ({{ $usr->full_name }})؟')">
                                    @csrf
                                    <button type="submit" class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white transition-colors inline-flex items-center justify-center" title="حذف الحساب">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl mb-2 text-slate-500">search_off</span>
                                <p class="text-xs font-bold">لا توجد حسابات مسجلة تطابق التصفية الحالية</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-3 border-t border-slate-100 dark:border-slate-800 flex justify-center">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ========================================================= -->
<!-- MODAL: CREATE ACCOUNT SELECTION MODAL -->
<!-- ========================================================= -->
<div id="createAccountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-lg p-6 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 m-4 transform scale-95 transition-transform duration-300">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">person_add</span>
                اختر نوع الحساب المراد إنشاؤه
            </h3>
            <button onclick="closeModal('createAccountModal')" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-2.5 mt-4">
            <!-- Student -->
            <a href="{{ route('admin.accounts.create.student') }}" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-blue-500 hover:bg-blue-500/5 transition-all flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center text-xl shrink-0">
                        <span class="material-symbols-outlined">school</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 dark:text-white group-hover:text-blue-500 transition-colors">حساب طالب</h4>
                        <p class="text-[10px] text-slate-400">تحديد المواد والصفوف والرقم الجامعي</p>
                    </div>
                </div>
                <span class="material-symbols-outlined text-slate-400 group-hover:translate-x-[-4px] transition-transform text-lg">arrow_back</span>
            </a>

            <!-- Parent -->
            <a href="{{ route('admin.accounts.create.parent') }}" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-orange-500 hover:bg-orange-500/5 transition-all flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-orange-500/10 text-orange-500 flex items-center justify-center text-xl shrink-0">
                        <span class="material-symbols-outlined">family_restroom</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 dark:text-white group-hover:text-orange-500 transition-colors">حساب ولي أمر</h4>
                        <p class="text-[10px] text-slate-400">ربطه بالأبناء وتحديد البيانات</p>
                    </div>
                </div>
                <span class="material-symbols-outlined text-slate-400 group-hover:translate-x-[-4px] transition-transform text-lg">arrow_back</span>
            </a>

            <!-- Teacher -->
            <a href="{{ route('admin.accounts.create.teacher') }}" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-emerald-500 hover:bg-emerald-500/5 transition-all flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl shrink-0">
                        <span class="material-symbols-outlined">sports</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 dark:text-white group-hover:text-emerald-500 transition-colors">حساب مدرب / معلم</h4>
                        <p class="text-[10px] text-slate-400">إضافة الكادر التدريسي والمواد</p>
                    </div>
                </div>
                <span class="material-symbols-outlined text-slate-400 group-hover:translate-x-[-4px] transition-transform text-lg">arrow_back</span>
            </a>

            <!-- HOD -->
            <a href="{{ route('admin.accounts.create.hod') }}" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-purple-500 hover:bg-purple-500/5 transition-all flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-purple-500/10 text-purple-500 flex items-center justify-center text-xl shrink-0">
                        <span class="material-symbols-outlined">supervisor_account</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 dark:text-white group-hover:text-purple-500 transition-colors">حساب رئيس قسم</h4>
                        <p class="text-[10px] text-slate-400">تعيين قسم وإسناد المهام</p>
                    </div>
                </div>
                <span class="material-symbols-outlined text-slate-400 group-hover:translate-x-[-4px] transition-transform text-lg">arrow_back</span>
            </a>

            <!-- Affairs -->
            <a href="{{ route('admin.accounts.create.affairs') }}" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-rose-500 hover:bg-rose-500/5 transition-all flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center text-xl shrink-0">
                        <span class="material-symbols-outlined">badge</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 dark:text-white group-hover:text-rose-500 transition-colors">حساب موظف شؤون</h4>
                        <p class="text-[10px] text-slate-400">منحه صلاحيات إدارة الطلاب</p>
                    </div>
                </div>
                <span class="material-symbols-outlined text-slate-400 group-hover:translate-x-[-4px] transition-transform text-lg">arrow_back</span>
            </a>
        </div>

    </div>
</div>

@push('scripts')
<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div').classList.remove('scale-95');
            modal.querySelector('div').classList.add('scale-100');
        }, 10);
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.querySelector('div').classList.remove('scale-100');
        modal.querySelector('div').classList.add('scale-95');
        modal.classList.add('opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    document.querySelectorAll('#createAccountModal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
</script>
@endpush
@endsection
