@extends('layouts.admin')

@section('title', 'إدارة الحسابات')
@section('header-title', 'الحسابات')
@section('header-subtitle', 'إدارة وتفعيل حسابات مستخدمي النظام')

@section('content')

    <input checked class="hidden" id="tab-create" name="tabs" type="radio"/>
    <input class="hidden" id="tab-delete" name="tabs" type="radio"/>
    <input class="hidden" id="tab-requests" name="tabs" type="radio"/>

    <!-- Tabs Header -->
    <div class="tab-list bg-white dark:bg-surface-dark p-1.5 rounded-[1.25rem] shadow-soft flex items-center justify-between relative z-10 border border-slate-100 dark:border-slate-800 transition-colors">
        <label class="flex-1 py-3 px-4 rounded-2xl font-bold text-sm text-center transition-all duration-300 cursor-pointer text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 select-none" for="tab-create">
            إنشاء حساب
        </label>
        <label class="flex-1 py-3 px-4 rounded-2xl font-bold text-sm text-center transition-all duration-300 cursor-pointer text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 select-none" for="tab-delete">
            حذف حساب
        </label>
        <label class="flex-1 py-3 px-4 rounded-2xl font-bold text-sm text-center transition-all duration-300 cursor-pointer text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 select-none relative" for="tab-requests">
            الطلبات
            @if(count($pendingUsers) > 0)
                <span class="absolute top-1 left-2 flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
            @endif
        </label>
    </div>

    <!-- Tab 1: Create Account -->
    <div class="tab-content content-create flex-col gap-4">
        <div class="flex gap-4">
            <a href="{{ route('admin.accounts.create.student') }}" class="flex-1 aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-transparent hover:border-primary/30 group">
                <div class="w-14 h-14 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">school</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200">طالب</span>
            </a>
            <a href="{{ route('admin.accounts.create.parent') }}" class="flex-1 aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-transparent hover:border-primary/30 group">
                <div class="w-14 h-14 rounded-full bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-600 dark:text-orange-400 group-hover:bg-orange-100 dark:group-hover:bg-orange-900/40 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">family_restroom</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200">ولي أمر</span>
            </a>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('admin.accounts.create.teacher') }}" class="flex-1 aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-transparent hover:border-primary/30 group">
                <div class="w-14 h-14 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/40 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">sports</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200">مدرب / معلم</span>
            </a>
            <a href="{{ route('admin.accounts.create.hod') }}" class="flex-1 aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-transparent hover:border-primary/30 group">
                <div class="w-14 h-14 rounded-full bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:bg-purple-100 dark:group-hover:bg-purple-900/40 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">supervisor_account</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200">رئيس قسم</span>
            </a>
        </div>
        <div class="flex justify-center w-full">
            <a href="{{ route('admin.accounts.create.affairs') }}" class="w-[calc(50%-0.5rem)] aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-transparent hover:border-primary/30 group">
                <div class="w-14 h-14 rounded-full bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-600 dark:text-rose-400 group-hover:bg-rose-100 dark:group-hover:bg-rose-900/40 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">badge</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200">موظف شؤون</span>
            </a>
        </div>
    </div>

    <!-- Tab 2: Delete Account -->
    <div class="tab-content content-delete flex-col gap-4">
        <div class="flex gap-4">
            <a href="{{ route('admin.accounts.delete-list.student') }}" class="flex-1 aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-red-50/50 hover:border-red-500/30 group">
                <div class="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">school</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">طلاب</span>
            </a>
            <a href="{{ route('admin.accounts.delete-list.parent') }}" class="flex-1 aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-red-50/50 hover:border-red-500/30 group">
                <div class="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">family_restroom</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">أولياء أمور</span>
            </a>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('admin.accounts.delete-list.teacher') }}" class="flex-1 aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-red-50/50 hover:border-red-500/30 group">
                <div class="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">sports</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">مدربين / معلمين</span>
            </a>
            <a href="{{ route('admin.accounts.delete-list.hod') }}" class="flex-1 aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-red-50/50 hover:border-red-500/30 group">
                <div class="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">supervisor_account</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">رؤساء أقسام</span>
            </a>
        </div>
        <div class="flex justify-center w-full">
            <a href="{{ route('admin.accounts.delete-list.affairs') }}" class="w-[calc(50%-0.5rem)] aspect-square bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft hover:shadow-lg active:scale-95 transition-all duration-300 flex flex-col items-center justify-center gap-3 border border-red-50/50 hover:border-red-500/30 group">
                <div class="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors">
                    <span class="material-symbols-outlined text-[32px]">badge</span>
                </div>
                <span class="text-base font-bold text-slate-700 dark:text-slate-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">موظفي شؤون</span>
            </a>
        </div>
    </div>

    <!-- Tab 3: Requests -->
    <div class="tab-content content-requests flex-col gap-4">
        <div class="flex items-center justify-between mb-2 px-2">
            <h2 class="text-sm font-bold text-slate-500 dark:text-slate-400">الطلبات الجديدة المعلقة</h2>
            <span class="text-xs font-bold px-2 py-1 rounded-full bg-primary/20 text-yellow-800 dark:text-yellow-400">{{ count($pendingUsers) }} طلب</span>
        </div>

        @forelse($pendingUsers as $pUser)
            <div class="bg-white dark:bg-surface-dark p-4 rounded-[1.25rem] shadow-soft flex items-center gap-4 group hover:shadow-lg transition-all duration-300 border border-transparent hover:border-primary/20">
                <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 
                    @if($pUser->role_id == 3) bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400
                    @elseif($pUser->role_id == 4) bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400
                    @elseif($pUser->role_id == 2) bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400
                    @elseif($pUser->role_id == 5) bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400
                    @else bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400
                    @endif">
                    <span class="material-symbols-outlined">
                        @if($pUser->role_id == 3) school
                        @elseif($pUser->role_id == 4) family_restroom
                        @elseif($pUser->role_id == 2) sports
                        @elseif($pUser->role_id == 5) supervisor_account
                        @else badge
                        @endif
                    </span>
                </div>
                
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-slate-800 dark:text-white truncate">{{ $pUser->full_name }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                        طلب حساب: 
                        @if($pUser->role_id == 3) طالب
                        @elseif($pUser->role_id == 4) ولي أمر
                        @elseif($pUser->role_id == 2) مدرب / معلم
                        @elseif($pUser->role_id == 5) رئيس قسم
                        @else موظف شؤون
                        @endif
                        ({{ $pUser->username }})
                    </p>
                    <div class="flex items-center gap-1 mt-1.5">
                        <span class="material-symbols-outlined text-[14px] text-slate-400">schedule</span>
                        <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($pUser->created_at)->diffForHumans() }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-2 shrink-0">
                    <form action="{{ route('admin.accounts.approve', $pUser->user_id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 flex items-center justify-center hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors shadow-sm" title="قبول الطلب وتنشيط الحساب">
                            <span class="material-symbols-outlined text-[20px]">check</span>
                        </button>
                    </form>
                    <form action="{{ route('admin.accounts.reject', $pUser->user_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رفض وحذف هذا الطلب؟')">
                        @csrf
                        <button type="submit" class="w-9 h-9 rounded-full bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400 flex items-center justify-center hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors shadow-sm" title="رفض وحذف الطلب">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center p-8 rounded-2xl bg-surface-light dark:bg-surface-dark shadow-soft text-center border border-slate-100/50 dark:border-slate-800/50">
                <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-700 mb-3">how_to_reg</span>
                <h3 class="text-base font-bold text-slate-800 dark:text-white">لا توجد طلبات معلقة</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs">تظهر هنا الحسابات التي يتم إنشاؤها وتنتظر تفعيل الإدارة.</p>
            </div>
        @endforelse
    </div>

    <!-- Spacing for radial menu -->
    <div class="h-16"></div>

@endsection

@push('styles')
<style>
    #tab-create:checked ~ .tab-list label[for="tab-create"],
    #tab-delete:checked ~ .tab-list label[for="tab-delete"],
    #tab-requests:checked ~ .tab-list label[for="tab-requests"] {
        background-color: #f2f20d;
        color: #1a2633;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(242, 242, 13, 0.2);
    }
    .tab-content {
        display: none;
    }
    #tab-create:checked ~ .content-create,
    #tab-delete:checked ~ .content-delete,
    #tab-requests:checked ~ .content-requests {
        display: flex;
        animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
</style>
@endpush
