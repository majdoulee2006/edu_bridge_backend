@extends('layouts.admin')

@section('title', 'تخصيص رؤساء الأقسام')
@section('header-title', 'تخصيص وإدارة رؤساء الأقسام')
@section('header-subtitle', 'استعراض كافة الأقسام ورؤساء الأقسام وتحديد أو إضافة رؤساء أقسام جدد')

@section('header-actions')
    <a href="{{ route('admin.courses') }}" class="w-10 h-10 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft" title="رجوع للدورات">
        <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
    </a>
@endsection

@section('content')
<div class="font-Cairo max-w-6xl mx-auto flex flex-col gap-8 pb-12">

    {{-- Success Message Alert --}}
    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 p-5 rounded-3xl flex items-center gap-3 text-sm font-black shadow-sm">
            <span class="material-symbols-outlined text-xl">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Validation Errors Alert --}}
    @if($errors->any())
        <div class="bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 p-5 rounded-3xl flex flex-col gap-1.5 text-xs font-bold shadow-sm">
            <div class="flex items-center gap-2 font-black text-sm">
                <span class="material-symbols-outlined text-xl">warning</span>
                <span>يرجى تصحيح الأخطاء التالية:</span>
            </div>
            <ul class="list-disc list-inside text-xs pr-6">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header title card --}}
    <div class="flex items-center justify-between px-1 flex-wrap gap-4">
        <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                <span class="material-symbols-outlined text-amber-500 text-2xl">workspace_premium</span>
                قائمة الأقسام ورؤساء الأقسام الحاليين
            </h2>
            <p class="text-xs font-bold text-slate-400 mt-1">انقر على زر "تغيير / تعيين" لتعديل رئيس القسم أو زر "إضافة قسم جديد" لخلق قسم جديد</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openAddDepartmentModal()" class="px-4 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs transition-all flex items-center gap-2 shadow-md hover:scale-105 active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-lg">add_circle</span>
                <span>إضافة قسم جديد</span>
            </button>
            <span class="px-4 py-2.5 rounded-2xl bg-primary/10 border border-primary/20 text-xs font-black text-primary-dark dark:text-primary shadow-sm">
                {{ count($departments) }} أقسام أكاديمية
            </span>
        </div>
    </div>

    {{-- Departments List Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($departments as $dept)
            @php
                $hasHOD = !empty($dept->current_hod_user_id);
            @endphp
            <div class="bg-white dark:bg-surface-dark p-7 rounded-[2rem] border border-slate-100 dark:border-slate-800/80 shadow-md hover:shadow-xl transition-all duration-300 flex items-center justify-between gap-5 group">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl {{ $hasHOD ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center font-bold text-2xl flex-shrink-0 shadow-sm group-hover:scale-105 transition-all">
                        <span class="material-symbols-outlined text-2xl">{{ $hasHOD ? 'verified_user' : 'no_accounts' }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <h3 class="text-base font-black text-slate-850 dark:text-white group-hover:text-primary transition-colors">قسم {{ $dept->name }}</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-400">رئيس القسم:</span>
                            <span class="text-sm font-extrabold {{ $hasHOD ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                                {{ $dept->current_hod_name }}
                            </span>
                        </div>
                    </div>
                </div>

                <button type="button"
                        id="hod-btn-{{ $dept->department_id }}"
                        data-dept-id="{{ $dept->department_id }}"
                        data-dept-name="{{ $dept->name }}"
                        data-hod-name="{{ $dept->current_hod_name }}"
                        onclick="openHodOptionModal({{ $dept->department_id }}, '{{ addslashes($dept->name) }}', '{{ addslashes($dept->current_hod_name) }}')"
                        class="px-5 py-3 rounded-2xl bg-primary text-slate-900 border border-primary/40 text-xs font-black transition-all flex items-center gap-2 flex-shrink-0 shadow-md hover:bg-primary-dark hover:scale-[1.03] active:scale-[0.98]">
                    <span class="material-symbols-outlined text-lg">edit</span>
                    <span>تغيير / تعيين</span>
                </button>
            </div>
        @endforeach
    </div>

</div>

{{-- ===== MODAL: Select Option or Register New HOD ===== --}}
<div id="hod-action-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 font-Cairo transition-all duration-200 opacity-0">
    <div id="modal-card" class="bg-white dark:bg-surface-dark w-full max-w-2xl rounded-3xl border border-slate-100 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col transform scale-95 transition-all duration-200">
        
        {{-- Modal Header --}}
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/20 text-slate-900 dark:text-primary flex items-center justify-center font-black">
                    <span class="material-symbols-outlined text-xl">manage_accounts</span>
                </div>
                <div class="flex flex-col">
                    <span id="modal-subtitle" class="text-[10px] font-bold text-primary-dark dark:text-primary uppercase">تخصيص رئيس قسم</span>
                    <h3 id="modal-dept-title" class="text-sm font-black text-slate-850 dark:text-white"></h3>
                </div>
            </div>
            <button type="button" onclick="closeHodModal()" class="w-8 h-8 rounded-xl bg-slate-200/60 dark:bg-slate-800 text-slate-500 hover:text-rose-500 flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        {{-- Option Selector Tabs --}}
        <div id="hod-tabs-header" class="p-4 bg-slate-100/50 dark:bg-slate-900/60 border-b border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-2">
            <button type="button" id="tab-existing" onclick="switchHodTab('existing')" class="py-3 px-4 rounded-2xl font-black text-xs flex items-center justify-center gap-2 transition-all bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700">
                <span class="material-symbols-outlined text-lg">badge</span>
                <span>شخص موجود بالمنظومة</span>
            </button>

            <button type="button" id="tab-new" onclick="switchHodTab('new')" class="py-3 px-4 rounded-2xl font-extrabold text-xs text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center gap-2 transition-all">
                <span class="material-symbols-outlined text-lg">person_add</span>
                <span>إضافة رئيس قسم جديد</span>
            </button>
        </div>

        {{-- TAB 1: Existing Staff --}}
        <div id="form-existing-container" class="p-6 flex flex-col gap-4">
            <form action="{{ route('admin.courses.assign-hod.store') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="department_id" id="existing-dept-id" value="" />

                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-extrabold text-slate-800 dark:text-white">اختر رئيس القسم من المدرسين والمدربين المتاحين:</h4>
                    <span class="text-xs font-bold text-primary"><span id="hod-visible-count">{{ count($availableUsers) }}</span> متاح</span>
                </div>

                {{-- Category Filter Pills (الكل / المدربين / رؤساء الأقسام) --}}
                <div class="flex items-center gap-2 p-1 bg-slate-100 dark:bg-slate-900/60 rounded-2xl border border-slate-200/60 dark:border-slate-800">
                    <button type="button" onclick="filterRoleCategory('all')" id="role-pill-all" class="role-category-pill active flex-1 py-2 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700">
                        <span>الكل</span>
                        <span class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-full text-[10px] font-black">{{ count($availableUsers) }}</span>
                    </button>
                    <button type="button" onclick="filterRoleCategory('teacher')" id="role-pill-teacher" class="role-category-pill flex-1 py-2 px-3 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-emerald-500">school</span>
                        <span>المدرسين والمدربين</span>
                        <span class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full text-[10px] font-black">{{ $availableUsers->where('role_id', 2)->count() }}</span>
                    </button>
                    <button type="button" onclick="filterRoleCategory('hod')" id="role-pill-hod" class="role-category-pill flex-1 py-2 px-3 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-amber-500">workspace_premium</span>
                        <span>رؤساء الأقسام</span>
                        <span class="bg-amber-500/10 text-amber-600 dark:text-amber-400 px-2 py-0.5 rounded-full text-[10px] font-black">{{ $availableUsers->where('role_id', 5)->count() }}</span>
                    </button>
                </div>

                {{-- Department Filter & Search --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="relative">
                        <select id="hod-dept-filter" onchange="filterHODs()" class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3 text-xs font-bold text-slate-800 dark:text-white outline-none cursor-pointer">
                            <option value="">جميع الأقسام الأكاديمية</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->name }}">قسم {{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-base">search</span>
                        <input id="hod-search" type="text"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 pr-9 pl-3 text-xs font-bold text-slate-800 dark:text-white placeholder:text-slate-400 transition-all outline-none"
                               placeholder="بحث باسم المدرب..." onkeyup="filterHODs()" />
                    </div>
                </div>

                {{-- Grouped Users List --}}
                @php
                    $hodUsers = $availableUsers->where('role_id', 5);
                    $teacherUsers = $availableUsers->where('role_id', 2);
                @endphp

                <div class="flex flex-col gap-2 max-h-[260px] overflow-y-auto hide-scrollbar pr-1">
                    
                    {{-- Group 1: Existing HODs --}}
                    @if(count($hodUsers) > 0)
                        <div class="role-group-header hod-group-header text-[11px] font-black text-amber-600 dark:text-amber-400 flex items-center gap-1.5 pt-1 pb-0.5 sticky top-0 bg-white dark:bg-surface-dark z-10">
                            <span class="material-symbols-outlined text-base">workspace_premium</span>
                            <span>رؤساء الأقسام الحاليين بالمنظومة ({{ count($hodUsers) }})</span>
                        </div>

                        @foreach($hodUsers as $user)
                            @php
                                $initials = mb_substr($user->full_name, 0, 2);
                            @endphp
                            <label class="relative cursor-pointer group hod-user-item" data-name="{{ strtolower($user->full_name) }}" data-dept="{{ $user->department ?? '' }}" data-role="hod">
                                <input type="radio" name="user_id" value="{{ $user->user_id }}" class="peer sr-only" required />
                                <div class="flex items-center justify-between p-3 rounded-2xl bg-amber-500/5 dark:bg-amber-500/10 border border-amber-200/80 dark:border-amber-900/40 transition-all group-hover:border-amber-500 peer-checked:border-amber-500 peer-checked:bg-amber-500/20 peer-checked:ring-2 peer-checked:ring-amber-500/40">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center font-black text-xs shrink-0">
                                            {{ $initials }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-black text-slate-900 dark:text-white">{{ $user->full_name }}</span>
                                            <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold mt-0.5 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-xs">workspace_premium</span>
                                                <span>{{ $user->role_title ?? 'رئيس قسم ('.$user->department.')' }}</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="w-4 h-4 rounded-full border-2 border-amber-400 dark:border-amber-600 flex items-center justify-center peer-checked:border-amber-500 peer-checked:bg-amber-500 transition-all">
                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-900"></div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    @endif

                    {{-- Group 2: Teachers and Trainers grouped by Department --}}
                    @if(count($teacherUsers) > 0)
                        <div class="role-group-header teacher-group-header text-[11px] font-black text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 pt-3 pb-0.5 sticky top-0 bg-white dark:bg-surface-dark z-10">
                            <span class="material-symbols-outlined text-base">school</span>
                            <span>الكادر التدريسي والمدربين الأكاديميين ({{ count($teacherUsers) }})</span>
                        </div>

                        @php
                            $groupedTeachers = $teacherUsers->groupBy(function($u) {
                                return !empty($u->department) ? $u->department : 'كادر تدريسي عام';
                            });
                        @endphp

                        @foreach($groupedTeachers as $deptName => $teachersInDept)
                            <div class="dept-sub-header text-[11px] font-bold text-slate-400 dark:text-slate-400/80 flex items-center gap-1.5 pt-2 pb-1 pr-1 border-b border-slate-100 dark:border-slate-800/50 mb-1" data-group-dept="{{ $deptName }}">
                                <span class="material-symbols-outlined text-xs text-slate-400">domain</span>
                                <span>قسم {{ $deptName }} ({{ count($teachersInDept) }})</span>
                            </div>

                            @foreach($teachersInDept as $user)
                                @php
                                    $initials = mb_substr($user->full_name, 0, 2);
                                @endphp
                                <label class="relative cursor-pointer group hod-user-item mb-1.5 block" data-name="{{ strtolower($user->full_name) }}" data-dept="{{ $user->department ?? '' }}" data-role="teacher">
                                    <input type="radio" name="user_id" value="{{ $user->user_id }}" class="peer sr-only" required />
                                    <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-800 transition-all group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:ring-2 peer-checked:ring-primary/40">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-primary/20 text-slate-900 dark:text-primary flex items-center justify-center font-black text-xs shrink-0">
                                                {{ $initials }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-xs font-black text-slate-900 dark:text-white">{{ $user->full_name }}</span>
                                                <span class="text-[10px] text-slate-400 font-bold mt-0.5 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">school</span>
                                                    <span>مدرس / مدرب أكاديمي</span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="w-4 h-4 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center peer-checked:border-primary peer-checked:bg-primary transition-all">
                                            <div class="w-1.5 h-1.5 rounded-full bg-slate-900"></div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        @endforeach
                    @endif

                </div>

                <div class="pt-3">
                    <button type="submit" class="w-full py-3.5 rounded-2xl bg-primary text-slate-900 font-extrabold text-xs flex items-center justify-center gap-2 shadow-md hover:bg-primary-dark transition-all">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        <span>تأكيد تعيين / نقل رئيس القسم</span>
                    </button>
                </div>
            </form>

            <form action="{{ route('admin.courses.assign-hod.unassign') }}" method="POST" id="unassign-hod-form" class="pt-1 border-t border-slate-100 dark:border-slate-800">
                @csrf
                <input type="hidden" name="department_id" id="unassign-dept-id" value="" />
                <button type="submit" onclick="return confirm('هل أنت تأكد من إلغاء تعيين رئيس القسم الحالي لهذا القسم وجعله غير مخصص؟')" class="w-full py-2.5 rounded-2xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-600 dark:text-rose-400 font-bold text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-base">person_remove</span>
                    <span>إلغاء وإبطال تكليف رئيس القسم الحالي</span>
                </button>
            </form>
        </div>

        {{-- TAB 2: Register New External HOD Form --}}
        <div id="form-new-container" class="p-6 hidden flex-col gap-4">
            <form action="{{ route('admin.courses.assign-hod.store-new') }}" method="POST" autocomplete="off" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="department_id" id="new-dept-id" value="" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- الاسم الأول --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">الاسم الأول *</label>
                        <input type="text" name="first_name" required placeholder="الاسم الأول" autocomplete="off"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-850 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>

                    {{-- الاسم الثاني / العائلة --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">الاسم الثاني / العائلة *</label>
                        <input type="text" name="last_name" required placeholder="اسم العائلة" autocomplete="off"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-850 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- البريد الإلكتروني --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">البريد الإلكتروني *</label>
                        <input type="email" name="email" required placeholder="name@example.com" autocomplete="off"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-850 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>

                    {{-- اسم المستخدم --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">اسم المستخدم (Username) *</label>
                        <input type="text" name="username" required placeholder="اسم المستخدم" autocomplete="off"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-850 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>
                </div>

                {{-- رقم الهاتف --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">رقم الهاتف *</label>
                    <input type="text" name="phone" required placeholder="09xxxxxxxx" autocomplete="off"
                           class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-850 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- كلمة المرور --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">كلمة المرور *</label>
                        <input type="password" name="password" required placeholder="••••••••" autocomplete="new-password"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-850 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>

                    {{-- تأكيد كلمة المرور --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">تأكيد كلمة المرور *</label>
                        <input type="password" name="password_confirmation" required placeholder="••••••••" autocomplete="new-password"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-700 py-2.5 px-3.5 text-xs font-bold text-slate-850 dark:text-white outline-none focus:ring-2 focus:ring-primary/30 transition-all" />
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" class="w-full py-3.5 rounded-2xl bg-primary hover:bg-primary-dark text-slate-900 font-extrabold text-xs flex items-center justify-center gap-2 shadow-md transition-all">
                        <span class="material-symbols-outlined text-lg">person_add</span>
                        <span>إنشاء الحساب وتعيينه رئيساً للقسم</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

{{-- ===== MODAL: Add New Department ===== --}}
<div id="add-dept-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 font-Cairo transition-all duration-200 opacity-0">
    <div id="add-dept-card" class="bg-white dark:bg-surface-dark w-full max-w-lg rounded-3xl border border-slate-100 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col transform scale-95 transition-all duration-200">
        
        {{-- Modal Header --}}
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center font-black">
                    <span class="material-symbols-outlined text-xl">domain_add</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase">الأقسام الأكاديمية</span>
                    <h3 class="text-sm font-black text-slate-850 dark:text-white">إضافة قسم أكاديمي جديد</h3>
                </div>
            </div>
            <button type="button" onclick="closeAddDepartmentModal()" class="w-8 h-8 rounded-xl bg-slate-200/60 dark:bg-slate-800 text-slate-500 hover:text-rose-500 flex items-center justify-center transition-all">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        {{-- Modal Body Form --}}
        <form action="{{ route('admin.departments.store') }}" method="POST" class="p-6 flex flex-col gap-4">
            @csrf
            <div>
                <label class="block text-xs font-black text-slate-700 dark:text-slate-300 mb-1.5">اسم القسم الأكاديمي <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="مثال: هندسة البرمجيات" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-850 dark:text-white focus:outline-none focus:border-amber-500 transition-all">
            </div>

            <div>
                <label class="block text-xs font-black text-slate-700 dark:text-slate-300 mb-1.5">رمز / كود القسم (اختياري)</label>
                <input type="text" name="code" placeholder="مثال: SWE" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-850 dark:text-white focus:outline-none focus:border-amber-500 transition-all">
            </div>

            <div>
                <label class="block text-xs font-black text-slate-700 dark:text-slate-300 mb-1.5">وصف القسم (اختياري)</label>
                <textarea name="description" rows="3" placeholder="اكتب وصفاً مختصراً للقسم..." class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-850 dark:text-white focus:outline-none focus:border-amber-500 transition-all resize-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeAddDepartmentModal()" class="px-5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 transition-all">
                    إلغاء
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs transition-all shadow-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">check_circle</span>
                    <span>حفظ القسم الجديد</span>
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function openAddDepartmentModal() {
        const modal = document.getElementById('add-dept-modal');
        const card = document.getElementById('add-dept-card');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 20);
    }

    function closeAddDepartmentModal() {
        const modal = document.getElementById('add-dept-modal');
        const card = document.getElementById('add-dept-card');
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    function openHodOptionModal(deptId, deptName, currentHodName, forceTab = null) {
        document.getElementById('modal-dept-title').textContent = deptName + ' (الحالي: ' + currentHodName + ')';
        document.getElementById('existing-dept-id').value = deptId;
        document.getElementById('new-dept-id').value = deptId;
        const unassignEl = document.getElementById('unassign-dept-id');
        if (unassignEl) unassignEl.value = deptId;

        // Set department filter to default (show all available teachers)
        const filterSelect = document.getElementById('hod-dept-filter');
        if (filterSelect) {
            filterSelect.value = '';
        }

        const tabsHeader = document.getElementById('hod-tabs-header');
        const subtitleEl = document.getElementById('modal-subtitle');

        if (forceTab === 'new') {
            if (tabsHeader) tabsHeader.classList.add('hidden');
            if (subtitleEl) subtitleEl.textContent = 'إضافة وتأسيس حساب رئيس قسم جديد';
            switchHodTab('new');
        } else if (forceTab === 'existing') {
            if (tabsHeader) tabsHeader.classList.add('hidden');
            if (subtitleEl) subtitleEl.textContent = 'تخصيص رئيس قسم من الكادر الحالي';
            switchHodTab('existing');
        } else {
            if (tabsHeader) tabsHeader.classList.remove('hidden');
            if (subtitleEl) subtitleEl.textContent = 'تخصيص وإدارة رئيس القسم';
            switchHodTab('existing');
        }

        filterHODs();

        const modal = document.getElementById('hod-action-modal');
        const card = document.getElementById('modal-card');

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 20);
    }

    function closeHodModal() {
        const modal = document.getElementById('hod-action-modal');
        const card = document.getElementById('modal-card');

        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    function switchHodTab(tab) {
        const tabExisting = document.getElementById('tab-existing');
        const tabNew = document.getElementById('tab-new');
        const formExisting = document.getElementById('form-existing-container');
        const formNew = document.getElementById('form-new-container');

        if (tab === 'existing') {
            tabExisting.className = 'py-3 px-4 rounded-2xl font-black text-xs flex items-center justify-center gap-2 transition-all bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700';
            tabNew.className = 'py-3 px-4 rounded-2xl font-extrabold text-xs text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center gap-2 transition-all';
            
            formExisting.classList.remove('hidden');
            formExisting.classList.add('flex');
            formNew.classList.remove('flex');
            formNew.classList.add('hidden');
        } else {
            tabNew.className = 'py-3 px-4 rounded-2xl font-black text-xs flex items-center justify-center gap-2 transition-all bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700';
            tabExisting.className = 'py-3 px-4 rounded-2xl font-extrabold text-xs text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center gap-2 transition-all';
            
            formNew.classList.remove('hidden');
            formNew.classList.add('flex');
            formExisting.classList.remove('flex');
            formExisting.classList.add('hidden');
        }
    }

    let currentRoleCategory = 'all';

    function filterRoleCategory(role) {
        currentRoleCategory = role;
        
        document.querySelectorAll('.role-category-pill').forEach(pill => {
            pill.className = 'role-category-pill flex-1 py-2 px-3 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all flex items-center justify-center gap-1.5';
        });

        const activePill = document.getElementById('role-pill-' + role);
        if (activePill) {
            activePill.className = 'role-category-pill active flex-1 py-2 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700';
        }

        filterHODs();
    }

    function filterHODs() {
        const query = (document.getElementById('hod-search').value || '').toLowerCase();
        const deptFilter = document.getElementById('hod-dept-filter').value;
        let visibleCount = 0;

        document.querySelectorAll('.hod-user-item').forEach(item => {
            const name = item.getAttribute('data-name') || '';
            const dept = item.getAttribute('data-dept') || '';
            const role = item.getAttribute('data-role') || '';

            const matchName = name.includes(query);
            const matchDept = !deptFilter || dept === deptFilter;
            const matchRole = (currentRoleCategory === 'all' || role === currentRoleCategory);

            if (matchName && matchDept && matchRole) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        const hodHeader = document.querySelector('.hod-group-header');
        const teacherHeader = document.querySelector('.teacher-group-header');

        if (hodHeader) {
            hodHeader.style.display = (currentRoleCategory === 'teacher') ? 'none' : 'flex';
        }
        if (teacherHeader) {
            teacherHeader.style.display = (currentRoleCategory === 'hod') ? 'none' : 'flex';
        }

        // Hide/Show Department Sub-Headers dynamically
        document.querySelectorAll('.dept-sub-header').forEach(header => {
            const groupDept = header.getAttribute('data-group-dept');
            if (currentRoleCategory === 'hod') {
                header.style.display = 'none';
                return;
            }
            
            // Check if any teacher item in this group is visible
            let hasVisibleTeacher = false;
            document.querySelectorAll(`.hod-user-item[data-role="teacher"]`).forEach(item => {
                const itemDept = item.getAttribute('data-dept') || 'كادر تدريسي عام';
                if ((itemDept === groupDept || (!item.getAttribute('data-dept') && groupDept === 'كادر تدريسي عام')) && item.style.display === 'block') {
                    hasVisibleTeacher = true;
                }
            });

            header.style.display = hasVisibleTeacher ? 'flex' : 'none';
        });

        const countEl = document.getElementById('hod-visible-count');
        if (countEl) {
            countEl.textContent = visibleCount;
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const deptId = urlParams.get('department_id');
        const action = urlParams.get('action');
        if (deptId) {
            const btn = document.getElementById('hod-btn-' + deptId);
            if (btn) {
                const name = btn.getAttribute('data-dept-name') || '';
                const hod = btn.getAttribute('data-hod-name') || '';
                openHodOptionModal(deptId, name, hod, action);
            }
        }
    });
</script>
@endpush
