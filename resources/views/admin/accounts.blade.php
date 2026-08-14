@extends('layouts.admin')

@section('title', 'إدارة الحسابات والمستخدمين')
@section('header-title', 'الحسابات')
@section('header-subtitle', 'عرض وإدارة كافة حسابات مستخدمي النظام بالمؤسسة')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar (Main Control Header) -->
    <div class="bg-white dark:bg-surface-dark p-6 rounded-3xl shadow-soft border border-slate-100 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-2xl">manage_accounts</span>
                إدارة كافة الحسابات المسجلة
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                إجمالي الحسابات المسجلة في النظام: <span class="font-bold text-primary">{{ $counts['all'] ?? 0 }}</span> حساب
            </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <!-- Create Account Button (+) -->
            <button type="button" onclick="openModal('createAccountModal')" class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-primary text-slate-950 font-black text-sm hover:bg-primary-hover transition-all shadow-lg shadow-primary/20 active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined font-black text-xl">add</span>
                <span>إنشاء حساب جديد</span>
            </button>

            <!-- Delete Accounts Button -->
            <button type="button" onclick="openModal('deleteAccountModal')" class="flex-1 md:flex-none inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-rose-500/10 text-rose-500 dark:bg-rose-500/20 dark:text-rose-400 border border-rose-500/30 font-bold text-sm hover:bg-rose-500 hover:text-white transition-all active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-xl">delete_sweep</span>
                <span>حذف حساب</span>
            </button>
        </div>
    </div>

    <!-- Filter Pills & Search Header -->
    <div class="bg-white dark:bg-surface-dark p-4 rounded-3xl shadow-soft border border-slate-100 dark:border-slate-800 space-y-4">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <a href="{{ route('admin.accounts', ['role' => 'all', 'search' => $search]) }}" 
               class="px-4 py-2.5 rounded-2xl font-bold text-xs shrink-0 transition-all flex items-center gap-2 {{ $roleFilter === 'all' ? 'bg-primary text-slate-950 shadow-md shadow-primary/20 font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                <span>الكل</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'all' ? 'bg-slate-950/20 text-slate-950' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['all'] }}</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'student', 'search' => $search]) }}" 
               class="px-4 py-2.5 rounded-2xl font-bold text-xs shrink-0 transition-all flex items-center gap-2 {{ $roleFilter === 'student' ? 'bg-blue-500 text-white shadow-md shadow-blue-500/20 font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                <span class="material-symbols-outlined text-sm">school</span>
                <span>الطلاب</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'student' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['student'] }}</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'teacher', 'search' => $search]) }}" 
               class="px-4 py-2.5 rounded-2xl font-bold text-xs shrink-0 transition-all flex items-center gap-2 {{ $roleFilter === 'teacher' ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20 font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                <span class="material-symbols-outlined text-sm">sports</span>
                <span>المعلمون والمدربون</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'teacher' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['teacher'] }}</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'hod', 'search' => $search]) }}" 
               class="px-4 py-2.5 rounded-2xl font-bold text-xs shrink-0 transition-all flex items-center gap-2 {{ $roleFilter === 'hod' ? 'bg-purple-500 text-white shadow-md shadow-purple-500/20 font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                <span class="material-symbols-outlined text-sm">supervisor_account</span>
                <span>رؤساء الأقسام</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'hod' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['hod'] }}</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'parent', 'search' => $search]) }}" 
               class="px-4 py-2.5 rounded-2xl font-bold text-xs shrink-0 transition-all flex items-center gap-2 {{ $roleFilter === 'parent' ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20 font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                <span class="material-symbols-outlined text-sm">family_restroom</span>
                <span>أولياء الأمور</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'parent' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['parent'] }}</span>
            </a>

            <a href="{{ route('admin.accounts', ['role' => 'affairs', 'search' => $search]) }}" 
               class="px-4 py-2.5 rounded-2xl font-bold text-xs shrink-0 transition-all flex items-center gap-2 {{ $roleFilter === 'affairs' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20 font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                <span class="material-symbols-outlined text-sm">badge</span>
                <span>موظفو الشؤون</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'affairs' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['affairs'] }}</span>
            </a>
        </div>

        <!-- Search Bar Form -->
        <form action="{{ route('admin.accounts') }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="role" value="{{ $roleFilter }}">
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="ابحث باسم المستخدم، البريد، الرقم الجامعي، أو الهاتف..." 
                       class="w-full pr-11 pl-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary transition-colors">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-slate-900 text-white dark:bg-primary dark:text-slate-950 rounded-2xl text-xs font-bold hover:opacity-90 transition-opacity">
                بحث
            </button>
            @if(!empty($search))
                <a href="{{ route('admin.accounts', ['role' => $roleFilter]) }}" class="px-4 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-2xl text-xs font-bold hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors">
                    إلغاء
                </a>
            @endif
        </form>
    </div>

    <!-- Accounts Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($users as $usr)
            <div class="bg-white dark:bg-surface-dark p-5 rounded-3xl shadow-soft border border-slate-100 dark:border-slate-800 hover:border-primary/30 transition-all group flex flex-col justify-between relative overflow-hidden">
                <!-- Role indicator top bar -->
                <div class="absolute top-0 left-0 right-0 h-1.5 
                    @if($usr->role_id == 3) bg-blue-500
                    @elseif($usr->role_id == 2) bg-emerald-500
                    @elseif($usr->role_id == 5) bg-purple-500
                    @elseif($usr->role_id == 4) bg-orange-500
                    @elseif($usr->role_id == 6) bg-rose-500
                    @else bg-slate-500
                    @endif"></div>

                <div>
                    <!-- Card Header / Avatar + Role -->
                    <div class="flex items-start justify-between gap-3 mt-1">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl shrink-0
                                @if($usr->role_id == 3) bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400
                                @elseif($usr->role_id == 2) bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400
                                @elseif($usr->role_id == 5) bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400
                                @elseif($usr->role_id == 4) bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400
                                @elseif($usr->role_id == 6) bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400
                                @else bg-slate-100 text-slate-600
                                @endif">
                                <span class="material-symbols-outlined text-2xl">
                                    @if($usr->role_id == 3) school
                                    @elseif($usr->role_id == 2) sports
                                    @elseif($usr->role_id == 5) supervisor_account
                                    @elseif($usr->role_id == 4) family_restroom
                                    @elseif($usr->role_id == 6) badge
                                    @else person
                                    @endif
                                </span>
                            </div>

                            <div class="min-w-0">
                                <h3 class="font-black text-sm text-slate-900 dark:text-white truncate group-hover:text-primary transition-colors">
                                    {{ $usr->full_name }}
                                </h3>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold 
                                        @if($usr->role_id == 3) bg-blue-500/10 text-blue-500 border border-blue-500/20
                                        @elseif($usr->role_id == 2) bg-emerald-500/10 text-emerald-500 border border-emerald-500/20
                                        @elseif($usr->role_id == 5) bg-purple-500/10 text-purple-500 border border-purple-500/20
                                        @elseif($usr->role_id == 4) bg-orange-500/10 text-orange-500 border border-orange-500/20
                                        @elseif($usr->role_id == 6) bg-rose-500/10 text-rose-500 border border-rose-500/20
                                        @else bg-slate-500/10 text-slate-500
                                        @endif">
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
                        </div>

                        <!-- Status Badge -->
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold shrink-0 {{ $usr->status === 'active' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20' }}">
                            {{ $usr->status === 'active' ? 'نشط' : 'معلق' }}
                        </span>
                    </div>

                    <!-- Details Box -->
                    <div class="mt-4 space-y-1.5 text-xs text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/60 font-mono">
                        @if(!empty($usr->university_id))
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-sans">الرقم الجامعي:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $usr->university_id }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-sans">اسم المستخدم:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $usr->username }}</span>
                        </div>

                        @if(!empty($usr->email))
                            <div class="flex items-center justify-between truncate" title="{{ $usr->email }}">
                                <span class="text-slate-400 font-sans">البريد:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[170px]">{{ $usr->email }}</span>
                            </div>
                        @endif

                        @if(!empty($usr->phone))
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-sans">الهاتف:</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $usr->phone }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                        {{ \Carbon\Carbon::parse($usr->created_at)->format('Y/m/d') }}
                    </span>

                    <form action="{{ route('admin.accounts.delete_single', $usr->user_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف حساب ({{ $usr->full_name }}) نهائياً من النظام؟')">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-500/10 text-rose-500 dark:bg-rose-500/20 dark:text-rose-400 hover:bg-rose-500 hover:text-white dark:hover:bg-rose-600 text-xs font-bold transition-all flex items-center gap-1 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">delete</span>
                            <span>حذف</span>
                        </button>
                    </form>
                </div>

            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white dark:bg-surface-dark rounded-3xl border border-slate-100 dark:border-slate-800">
                <span class="material-symbols-outlined text-5xl text-slate-400 mb-3">person_search</span>
                <h3 class="text-base font-bold text-slate-800 dark:text-white">لا توجد حسابات تطابق البحث أو الفلتر المحدد</h3>
                <p class="text-xs text-slate-400 mt-1">تأكد من تحديد خيار الفلترة المناسب أو قم بإنشاء حساب جديد.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
        <div class="pt-4 flex justify-center">
            {{ $users->links() }}
        </div>
    @endif

</div>

<!-- ========================================================= -->
<!-- MODAL 1: CREATE ACCOUNT SELECTION modal -->
<!-- ========================================================= -->
<div id="createAccountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-2xl p-6 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 m-4 transform scale-95 transition-transform duration-300">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined font-black">person_add</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">إنشاء حساب جديد</h3>
                    <p class="text-xs text-slate-400">حدد نوع الحساب الذي ترغب في إنشائه وإضافة بياناته</p>
                </div>
            </div>
            <button onclick="closeModal('createAccountModal')" class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
            <!-- Student -->
            <a href="{{ route('admin.accounts.create.student') }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-blue-500 hover:bg-blue-500/5 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">school</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-blue-500 transition-colors">حساب طالب</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">إنشاء حساب طالب وإعطائه رقم جامعي</p>
                </div>
            </a>

            <!-- Parent -->
            <a href="{{ route('admin.accounts.create.parent') }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-orange-500 hover:bg-orange-500/5 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-orange-500/10 text-orange-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">family_restroom</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-orange-500 transition-colors">حساب ولي أمر</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">إنشاء حساب ولي أمر وربطه بالأبناء</p>
                </div>
            </a>

            <!-- Teacher -->
            <a href="{{ route('admin.accounts.create.teacher') }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-emerald-500 hover:bg-emerald-500/5 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">sports</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-emerald-500 transition-colors">حساب مدرب / معلم</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">إضافة كادر تدريسي وتخصيص المواد</p>
                </div>
            </a>

            <!-- HOD -->
            <a href="{{ route('admin.accounts.create.hod') }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-purple-500 hover:bg-purple-500/5 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">supervisor_account</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-purple-500 transition-colors">حساب رئيس قسم</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">إضافة رئيس قسم وتعيين قسمه</p>
                </div>
            </a>

            <!-- Affairs -->
            <a href="{{ route('admin.accounts.create.affairs') }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-rose-500 hover:bg-rose-500/5 transition-all flex items-center gap-4 group sm:col-span-2">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">badge</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-rose-500 transition-colors">حساب موظف شؤون</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">إضافة موظف شؤون طلاب بصلاحيات شؤونية كاملة</p>
                </div>
            </a>
        </div>

    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL 2: DELETE ACCOUNTS CATEGORY SELECTION MODAL -->
<!-- ========================================================= -->
<div id="deleteAccountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-2xl p-6 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 m-4 transform scale-95 transition-transform duration-300">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center">
                    <span class="material-symbols-outlined font-black">delete_sweep</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">إدارة وحذف الحسابات حسب الفئة</h3>
                    <p class="text-xs text-slate-400">حدد فئة الحسابات التي تريد استعراضها وإجراء الحذف التحديد المباشر لها</p>
                </div>
            </div>
            <button onclick="closeModal('deleteAccountModal')" class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
            <!-- Student Delete List -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 3]) }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-red-500 hover:bg-red-500/5 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">school</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-red-500 transition-colors">حسابات الطلاب</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">استعراض وحذف طلاب المعهد</p>
                </div>
            </a>

            <!-- Parent Delete List -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 4]) }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-red-500 hover:bg-red-500/5 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">family_restroom</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-red-500 transition-colors">حسابات أولياء الأمور</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">استعراض وحذف حسابات أولياء الأمور</p>
                </div>
            </a>

            <!-- Teacher Delete List -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 2]) }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-red-500 hover:bg-red-500/5 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">sports</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-red-500 transition-colors">حسابات المعلمين</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">استعراض وحذف الكادر التدريسي</p>
                </div>
            </a>

            <!-- HOD Delete List -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 5]) }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-red-500 hover:bg-red-500/5 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">supervisor_account</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-red-500 transition-colors">حسابات رؤساء الأقسام</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">استعراض وحذف حسابات رؤساء الأقسام</p>
                </div>
            </a>

            <!-- Affairs Delete List -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 6]) }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 hover:border-red-500 hover:bg-red-500/5 transition-all flex items-center gap-4 group sm:col-span-2">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center text-2xl shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined">badge</span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-red-500 transition-colors">حسابات موظفي الشؤون</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">استعراض وحذف حسابات الشؤون</p>
                </div>
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

    // Close on overlay click
    document.querySelectorAll('#createAccountModal, #deleteAccountModal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
</script>
@endpush
@endsection
