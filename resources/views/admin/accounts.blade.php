@extends('layouts.admin')

@section('title', 'إدارة الحسابات')
@section('header-title', 'الحسابات')
@section('header-subtitle', 'إدارة وتفعيل حسابات مستخدمي النظام')

@section('content')

    <input checked class="hidden" id="tab-create" name="tabs" type="radio"/>
    <input class="hidden" id="tab-delete" name="tabs" type="radio"/>

    <!-- Tabs Header -->
    <div class="tab-list bg-white dark:bg-surface-dark p-1.5 rounded-[1.25rem] shadow-soft flex items-center justify-between relative z-10 border border-slate-100 dark:border-slate-850 transition-colors">
        <label class="flex-1 py-3.5 px-6 rounded-2xl font-bold text-sm text-center transition-all duration-300 cursor-pointer text-slate-500 dark:text-slate-450 hover:bg-slate-50 dark:hover:bg-slate-800/50 select-none" for="tab-create">
            إنشاء حساب
        </label>
        <label class="flex-1 py-3.5 px-6 rounded-2xl font-bold text-sm text-center transition-all duration-300 cursor-pointer text-slate-500 dark:text-slate-450 hover:bg-slate-50 dark:hover:bg-slate-800/50 select-none" for="tab-delete">
            حذف حساب
        </label>
    </div>

    <!-- Tab 1: Create Account -->
    <div class="tab-content content-create flex-col gap-6 mt-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
            <!-- Student -->
            <a href="{{ route('admin.accounts.create.student') }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-blue-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <!-- Decorative top color bar -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-blue-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">school</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حساب طالب</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    إنشاء حساب جديد للطلاب وتحديد الصفوف والمواد الدراسية الخاصة بهم.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 dark:text-blue-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    البدء بالإنشاء
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>

            <!-- Parent -->
            <a href="{{ route('admin.accounts.create.parent') }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-orange-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-orange-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">family_restroom</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حساب ولي أمر</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    إنشاء حساب لأولياء الأمور لتمكينهم من متابعة أبنائهم وتقاريرهم.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-orange-600 dark:text-orange-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    البدء بالإنشاء
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>

            <!-- Teacher -->
            <a href="{{ route('admin.accounts.create.teacher') }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-emerald-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-emerald-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">sports</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حساب مدرب / معلم</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    إضافة المعلمين والمدربين وتفويضهم لإعطاء المحاضرات وإدارة التقييمات.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    البدء بالإنشاء
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>

            <!-- HOD -->
            <a href="{{ route('admin.accounts.create.hod') }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-purple-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-purple-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">supervisor_account</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حساب رئيس قسم</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    إضافة رؤساء للأقسام لتفويضهم إدارة المعلمين والبرامج الدراسية.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-600 dark:text-purple-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    البدء بالإنشاء
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>

            <!-- Affairs -->
            <a href="{{ route('admin.accounts.create.affairs') }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-rose-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-rose-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">badge</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حساب موظف شؤون</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    إنشاء حساب لموظفي شؤون الطلاب لمتابعة الإعلانات والطلبات اليومية.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    البدء بالإنشاء
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>
        </div>
    </div>

    <!-- Tab 2: Delete Account -->
    <div class="tab-content content-delete flex-col gap-6 mt-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
            <!-- Student -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 3]) }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-red-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-red-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">school</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حسابات الطلاب</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    عرض وحذف حسابات الطلاب المقيدين أو الخريجين من المعهد.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    إدارة وحذف
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>

            <!-- Parent -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 4]) }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-red-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-red-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">family_restroom</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حسابات أولياء الأمور</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    عرض وحذف حسابات أولياء الأمور المسجلة وتحديث روابطها.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    إدارة وحذف
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>

            <!-- Teacher -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 2]) }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-red-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-red-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">sports</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حسابات المدربين والمعلمين</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    عرض وحذف وتعديل صلاحيات معلمي ومدربي المواد المختلفة.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    إدارة وحذف
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>

            <!-- HOD -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 5]) }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-red-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-red-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">supervisor_account</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حسابات رؤساء الأقسام</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    إدارة وحذف حسابات رؤساء الأقسام الأكاديمية وإعادة تنسيق القسم.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    إدارة وحذف
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>

            <!-- Affairs -->
            <a href="{{ route('admin.accounts.delete-list', ['role_id' => 6]) }}" class="relative overflow-hidden bg-white dark:bg-surface-dark rounded-3xl p-6 shadow-soft hover:shadow-xl border border-slate-100 dark:border-slate-800 hover:border-red-500/30 transition-all duration-300 flex flex-col items-center text-center group active:scale-98">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-red-500"></div>
                
                <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-red-50 dark:group-hover:bg-red-950/30 group-hover:text-red-500 transition-colors duration-300 mt-2">
                    <span class="material-symbols-outlined text-[36px]">badge</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mt-4">حسابات موظفي الشؤون</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 px-2 leading-relaxed">
                    عرض وحذف حسابات موظفي شؤون الطلاب وإلغاء صلاحياتهم.
                </p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400 mt-6 group-hover:translate-x-[-4px] transition-transform">
                    إدارة وحذف
                    <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                </span>
            </a>
        </div>
    </div>

    <!-- Tab 3: Requests -->
    {{-- تبويب الطلبات محذوف - الادارة تنشئ الحسابات مباشرة --}}
    <div class="tab-content content-requests flex-col gap-6 mt-4" style="display:none!important">
        <div class="flex items-center justify-between mb-2 px-2">
            <h2 class="text-base font-bold text-slate-700 dark:text-slate-300">الطلبات الجديدة المعلقة</h2>
            <span class="text-xs font-extrabold px-3 py-1.5 rounded-full bg-primary/20 text-yellow-800 dark:text-yellow-400 shadow-sm">{{ count($pendingUsers) }} طلب معلق</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($pendingUsers as $pUser)
                <div class="bg-white dark:bg-surface-dark p-6 rounded-3xl shadow-soft flex flex-col justify-between group hover:shadow-xl transition-all duration-300 border border-slate-100 dark:border-slate-800/80 hover:border-primary/20 relative overflow-hidden">
                    <!-- Top colored highlight -->
                    <div class="absolute top-0 right-0 left-0 h-1.5
                        @if($pUser->role_id == 3) bg-blue-500
                        @elseif($pUser->role_id == 4) bg-orange-500
                        @elseif($pUser->role_id == 2) bg-emerald-500
                        @elseif($pUser->role_id == 5) bg-purple-500
                        @else bg-rose-500
                        @endif"></div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm
                            @if($pUser->role_id == 3) bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400
                            @elseif($pUser->role_id == 4) bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400
                            @elseif($pUser->role_id == 2) bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400
                            @elseif($pUser->role_id == 5) bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400
                            @else bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400
                            @endif">
                            <span class="material-symbols-outlined text-[24px]">
                                @if($pUser->role_id == 3) school
                                @elseif($pUser->role_id == 4) family_restroom
                                @elseif($pUser->role_id == 2) sports
                                @elseif($pUser->role_id == 5) supervisor_account
                                @else badge
                                @endif
                            </span>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-800 dark:text-white truncate group-hover:text-primary-dark dark:group-hover:text-primary transition-colors">{{ $pUser->full_name }}</h3>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                    @if($pUser->role_id == 3) طالب
                                    @elseif($pUser->role_id == 4) ولي أمر
                                    @elseif($pUser->role_id == 2) مدرب / معلم
                                    @elseif($pUser->role_id == 5) رئيس قسم
                                    @else موظف شؤون
                                    @endif
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-mono truncate">({{ $pUser->username }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom meta and actions -->
                    <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800/80 pt-4 mt-6">
                        <div class="flex items-center gap-1 text-slate-400 dark:text-slate-500">
                            <span class="material-symbols-outlined text-[16px]">schedule</span>
                            <span class="text-[11px] font-bold">{{ \Carbon\Carbon::parse($pUser->created_at)->diffForHumans() }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <form action="{{ route('admin.accounts.reject', $pUser->user_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رفض وحذف هذا الطلب؟')">
                                @csrf
                                <button type="submit" class="w-9 h-9 rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/20 dark:text-rose-400 flex items-center justify-center hover:bg-rose-100 dark:hover:bg-rose-900/40 active:scale-90 transition-all shadow-sm" title="رفض الطلب">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </form>
                            <form action="{{ route('admin.accounts.approve', $pUser->user_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/20 dark:text-emerald-400 flex items-center justify-center hover:bg-emerald-100 dark:hover:bg-emerald-900/40 active:scale-90 transition-all shadow-sm" title="قبول وتنشيط الحساب">
                                    <span class="material-symbols-outlined text-[18px]">check</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center p-12 rounded-3xl bg-white dark:bg-surface-dark shadow-soft text-center border border-slate-100/50 dark:border-slate-800/50">
                    <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-slate-400 dark:text-slate-500 mb-4">
                        <span class="material-symbols-outlined text-4xl">how_to_reg</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">لا توجد طلبات معلقة</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 max-w-sm leading-relaxed">تظهر هنا الحسابات التي يتم إنشاؤها وتنتظر تفعيل الإدارة.</p>
                </div>
            @endforelse
        </div>
    </div>

@endsection

@push('styles')
<style>
    #tab-create:checked ~ .tab-list label[for="tab-create"],
    #tab-delete:checked ~ .tab-list label[for="tab-delete"] {
        background-color: #f2f20d;
        color: #1a2633;
        font-weight: 800;
        box-shadow: 0 4px 15px rgba(242, 242, 13, 0.25);
    }
    .tab-content {
        display: none;
    }
    #tab-create:checked ~ .content-create,
    #tab-delete:checked ~ .content-delete {
        display: flex;
        animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
</style>
@endpush
