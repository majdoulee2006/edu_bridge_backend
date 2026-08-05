@extends('layouts.admin')

@section('title', 'الدورات التدريبية')
@section('header-title', 'الدورات والبرامج')
@section('header-subtitle', 'إدارة وتصفية البرامج والدورات التدريبية حسب الأقسام')

@section('content')

@if(session('success'))
<div class="alert-toast mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-between shadow-soft">
    <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-2xl">check_circle</span>
        <span class="text-xs md:text-sm font-bold">{{ session('success') }}</span>
    </div>
    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
        <span class="material-symbols-outlined text-lg">close</span>
    </button>
</div>
@endif

@if(session('new_department_id'))
<div id="web-dept-wizard-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-md rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-100 transition-all duration-300 flex flex-col p-6 text-right">
        
        <!-- Header -->
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-850 dark:text-white">تم إنشاء القسم بنجاح! 🎉</h3>
                <p class="text-xs text-slate-400">القسم: {{ session('new_department_name') }}</p>
            </div>
        </div>

        <!-- Main View -->
        <div id="web-wizard-main-view">
            <p class="text-xs md:text-sm text-slate-600 dark:text-slate-350 leading-relaxed mb-6">
                ما هي الخطوة التي ترغب في البدء بها لتأسيس هذا القسم؟
            </p>

            <div class="flex flex-col gap-3">
                <button onclick="showWebWizardSub('hod')" class="flex items-center justify-between p-4 rounded-2xl border border-slate-150 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all text-right w-full">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-amber-500">supervisor_account</span>
                        <span class="text-xs md:text-sm font-bold text-slate-750 dark:text-white">رئيس القسم</span>
                    </div>
                    <span class="material-symbols-outlined text-slate-400">chevron_left</span>
                </button>

                <button onclick="showWebWizardSub('courses')" class="flex items-center justify-between p-4 rounded-2xl border border-slate-150 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all text-right w-full">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-emerald-500">library_books</span>
                        <span class="text-xs md:text-sm font-bold text-slate-750 dark:text-white">الدورات والبرامج</span>
                    </div>
                    <span class="material-symbols-outlined text-slate-400">chevron_left</span>
                </button>

                <button onclick="document.getElementById('web-dept-wizard-modal').remove()" class="w-full py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs md:text-sm font-bold transition-all text-center">
                    إغلاق وتخطي الإعداد حالياً
                </button>
            </div>
        </div>

        <!-- HOD Sub-View -->
        <div id="web-wizard-hod-view" class="hidden">
            <p class="text-xs md:text-sm text-slate-600 dark:text-slate-350 leading-relaxed mb-4">
                إشراف رئيس القسم: اختر الإجراء المناسب
            </p>
            <div class="flex flex-col gap-3">
                <a href="{{ route('admin.courses.assign-hod') }}?department_id={{ session('new_department_id') }}&action=new" class="flex items-center justify-between p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 text-amber-700 dark:text-amber-300 transition-all">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">person_add</span>
                        <span class="text-xs font-bold">إنشاء رئيس قسم جديد (إضافة)</span>
                    </div>
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                </a>
                <a href="{{ route('admin.courses.assign-hod') }}?department_id={{ session('new_department_id') }}&action=existing" class="flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg text-slate-500">group</span>
                        <span class="text-xs font-bold text-slate-750 dark:text-white">تخصيص شخص موجود بالسيستم</span>
                    </div>
                    <span class="material-symbols-outlined text-sm text-slate-400">arrow_back</span>
                </a>
                <button onclick="showWebWizardSub('main')" class="py-2.5 text-xs text-slate-400 font-bold hover:text-slate-600">
                    رجوع للخيارات الرئيسية
                </button>
            </div>
        </div>

        <!-- Courses Sub-View -->
        <div id="web-wizard-courses-view" class="hidden">
            <p class="text-xs md:text-sm text-slate-600 dark:text-slate-350 leading-relaxed mb-4">
                إعداد الدورات: اختر الإجراء المناسب
            </p>
            <div class="flex flex-col gap-3">
                <a href="{{ route('admin.courses.create') }}?department_id={{ session('new_department_id') }}" class="flex items-center justify-between p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40 text-emerald-700 dark:text-emerald-300 transition-all">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">add_circle</span>
                        <span class="text-xs font-bold">إنشاء دورة جديدة للقسم</span>
                    </div>
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                </a>
                <button onclick="if(document.getElementById('web-dept-wizard-modal')) document.getElementById('web-dept-wizard-modal').remove(); openAssignCoursesModal('{{ session('new_department_id') }}')" class="flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all text-right w-full">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg text-slate-500">category</span>
                        <span class="text-xs font-bold text-slate-750 dark:text-white">تخصيص دورات موجودة في النظام</span>
                    </div>
                    <span class="material-symbols-outlined text-sm text-slate-400">arrow_back</span>
                </button>
                <button onclick="showWebWizardSub('main')" class="py-2.5 text-xs text-slate-400 font-bold hover:text-slate-600">
                    رجوع للخيارات الرئيسية
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function showWebWizardSub(type) {
        document.getElementById('web-wizard-main-view').classList.add('hidden');
        document.getElementById('web-wizard-hod-view').classList.add('hidden');
        document.getElementById('web-wizard-courses-view').classList.add('hidden');

        if (type === 'hod') {
            document.getElementById('web-wizard-hod-view').classList.remove('hidden');
        } else if (type === 'courses') {
            document.getElementById('web-wizard-courses-view').classList.remove('hidden');
        } else {
            document.getElementById('web-wizard-main-view').classList.remove('hidden');
        }
    }
</script>
@endif

@if($errors->any())
<div class="alert-toast mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-between shadow-soft">
    <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-2xl">error</span>
        <span class="text-xs md:text-sm font-bold">{{ $errors->first() }}</span>
    </div>
    <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700">
        <span class="material-symbols-outlined text-lg">close</span>
    </button>
</div>
@endif

{{-- ===== Main View Switcher & Action Toolbar ===== --}}
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-2 bg-slate-100/70 dark:bg-slate-900/70 p-1.5 rounded-2xl border border-slate-200/60 dark:border-slate-800">
        <button type="button" id="tab-btn-depts" onclick="switchMainView('depts')" class="px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700">
            <span class="material-symbols-outlined text-lg">domain</span>
            <span>الأقسام الأكاديمية</span>
            <span class="bg-amber-500/20 text-amber-600 dark:text-amber-400 px-2 py-0.5 rounded-full text-[10px] font-black">{{ count($departments) }}</span>
        </button>

        <button type="button" id="tab-btn-courses" onclick="switchMainView('courses')" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">school</span>
            <span>الدورات والبرامج</span>
            <span class="bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-full text-[10px] font-black">{{ count($programs) }}</span>
        </button>
    </div>

    <div class="flex items-center gap-3">
        <button type="button" id="btn-add-dept" onclick="openAddDepartmentModal()" class="px-4 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs transition-all shadow-glow flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">domain_add</span>
            <span>إضافة قسم جديد</span>
        </button>

        <a href="{{ route('admin.courses.create') }}" id="btn-add-course" class="hidden px-4 py-2.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-black text-xs transition-all shadow-glow flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">add_circle</span>
            <span>إضافة دورة جديدة</span>
        </a>
    </div>
</div>

{{-- ===== SECTION 1: DEPARTMENTS CARDS GRID ===== --}}
<div id="depts-view-container" class="space-y-4 mb-8">
    <div class="flex items-center justify-between px-1">
        <h3 class="text-sm font-bold text-slate-600 dark:text-slate-400">
            الأقسام الأكاديمية المتاحة في المنظومة
        </h3>
        <span class="text-xs font-extrabold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
            {{ count($departments) }} أقسام
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($departments as $dept)
            <div class="flex flex-col justify-between p-6 rounded-3xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 shadow-soft hover:border-amber-500/40 hover:shadow-xl transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 bottom-0 w-1 bg-transparent group-hover:bg-amber-500 transition-colors"></div>

                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-black shrink-0">
                            <span class="material-symbols-outlined text-2xl">domain</span>
                        </div>
                        <div class="flex flex-col">
                            <h4 class="text-base font-black text-slate-850 dark:text-white">{{ $dept->name }}</h4>
                            <span class="text-[10px] font-bold text-slate-400">قسم أكاديمي</span>
                        </div>
                    </div>

                    {{-- Quick Action Menu: Edit / Delete --}}
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="openEditDepartmentModal({{ json_encode($dept) }})" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-amber-500 flex items-center justify-center transition-colors" title="تعديل بيانات القسم">
                            <span class="material-symbols-outlined text-base">edit</span>
                        </button>

                        <form action="{{ route('admin.departments.delete', $dept->department_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف قسم ({{ $dept->name }})؟ سيتم إلغاء تخصيص الدورات المرتبطة به دون حذفها.')">
                            @csrf
                            <button type="submit" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-rose-500 flex items-center justify-center transition-colors" title="حذف القسم">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </form>
                    </div>
                </div>

                @if($dept->description)
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-bold mb-4 line-clamp-2 leading-relaxed">
                        {{ $dept->description }}
                    </p>
                @endif

                <div class="grid grid-cols-2 gap-2 my-2 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                    <div class="bg-slate-50 dark:bg-slate-900/60 p-3 rounded-2xl flex flex-col gap-0.5">
                        <span class="text-[10px] font-bold text-slate-400">رئيس القسم الحالي</span>
                        <span class="text-xs font-black text-slate-800 dark:text-white truncate">{{ $dept->current_hod_name }}</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/60 p-3 rounded-2xl flex flex-col gap-0.5">
                        <span class="text-[10px] font-bold text-slate-400">عدد الدورات المخصصة</span>
                        <span class="text-xs font-black text-slate-800 dark:text-white">{{ $dept->courses_count }} دورات</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-3 pt-2">
                    <a href="{{ route('admin.courses.assign-hod') }}?department_id={{ $dept->department_id }}" class="flex-1 py-2.5 px-3 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-extrabold text-center transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base">manage_accounts</span>
                        <span>تعيين رئيس قسم</span>
                    </a>
                    <button type="button" onclick="selectDepartmentFilter('{{ $dept->department_id }}'); switchMainView('courses');" class="flex-1 py-2.5 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-xs font-extrabold text-center transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base">visibility</span>
                        <span>عرض دوراته</span>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ===== SECTION 2: COURSES VIEW CONTAINER ===== --}}
<div id="courses-view-container" class="hidden space-y-4">
    {{-- ===== Advanced Department Filter Toolbar ===== --}}
    <div class="bg-white dark:bg-surface-dark p-5 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft mb-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800/80 pb-4">
            <div class="flex items-center gap-2">
                <span class="w-2 h-6 bg-primary rounded-full shadow-glow"></span>
                <h3 class="text-base font-bold text-slate-800 dark:text-white">فلترة وتصفية الدورات</h3>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                {{-- Department Select Dropdown (Listbox) --}}
                <div class="relative min-w-[220px]">
                    <select id="department-select-filter" 
                            onchange="onDepartmentSelectChange(this.value)" 
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 rounded-2xl py-3 pr-10 pl-4 text-xs md:text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all appearance-none cursor-pointer">
                        <option value="all" selected>🏢 جميع الأقسام الأكاديمية (الكل)</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}">📂 {{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xl">tune</span>
                </div>

                <div class="relative min-w-[200px]">
                    <input id="course-search-input" 
                           oninput="filterCourses()" 
                           type="text" 
                           placeholder="بحث باسم الدورة..." 
                           class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 rounded-2xl py-3 pr-10 pl-4 text-xs md:text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all placeholder:text-slate-400"/>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                </div>
            </div>
        </div>

        {{-- Interactive Quick Department Filter Pills --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1 pt-1 scrollbar-none" id="dept-pills-container">
            <button type="button" 
                    onclick="selectDepartmentFilter('all')" 
                    data-dept-id="all" 
                    class="dept-pill-btn active px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 bg-primary text-primary-content shadow-glow">
                <span>الكل</span>
                <span class="bg-black/10 dark:bg-white/20 px-2 py-0.5 rounded-full text-[10px] font-black">{{ count($programs) }}</span>
            </button>

            @foreach($departments as $dept)
                @php
                    $deptCoursesCount = $programs->where('department_id', $dept->department_id)->count();
                @endphp
                <button type="button" 
                        onclick="selectDepartmentFilter('{{ $dept->department_id }}')" 
                        data-dept-id="{{ $dept->department_id }}" 
                        class="dept-pill-btn px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                    <span>{{ $dept->name }}</span>
                    <span class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-full text-[10px] font-black">{{ $deptCoursesCount }}</span>
                </button>
            @endforeach
        </div>
    </div>

{{-- ===== Courses List Header Counter ===== --}}
<div class="flex items-center justify-between mb-4 px-1">
    <h3 class="text-sm font-bold text-slate-600 dark:text-slate-400">
        نتائج الدورات والبرامج
    </h3>
    <span id="filtered-count-badge" class="text-xs font-extrabold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
        عرض {{ count($programs) }} من أصل {{ count($programs) }} دورة
    </span>
</div>

{{-- ===== Courses Cards Grid ===== --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="courses-grid">
    @forelse($programs as $program)
        @php
            $icons = ['code', 'design_services', 'language', 'analytics', 'school', 'science', 'psychology', 'brush'];
            $colors = [
                'bg-blue-50 dark:bg-blue-900/20 text-blue-500',
                'bg-purple-50 dark:bg-purple-900/20 text-purple-500',
                'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500',
                'bg-orange-50 dark:bg-orange-900/20 text-orange-500',
                'bg-rose-50 dark:bg-rose-900/20 text-rose-500',
                'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-500',
            ];
            $icon = $icons[$loop->index % count($icons)];
            $color = $colors[$loop->index % count($colors)];
        @endphp
        <div data-dept-id="{{ $program->department_id }}"
             data-dept-name="{{ mb_strtolower($program->department_name) }}"
             data-course-name="{{ mb_strtolower($program->name) }}"
             onclick="showCourseDetails({{ json_encode($program) }}, '{{ $color }}', '{{ $icon }}')" 
             class="course-card-item cursor-pointer flex flex-col justify-between p-6 rounded-3xl bg-white dark:bg-surface-dark border border-slate-100 dark:border-slate-800 shadow-soft hover:border-primary/45 hover:shadow-xl transition-all duration-300 group relative overflow-hidden">
            <div class="absolute right-0 top-0 bottom-0 w-1 bg-transparent group-hover:bg-primary transition-colors"></div>

            <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-2xl {{ $color }} flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform duration-300 shadow-sm">
                    <span class="material-symbols-outlined text-[28px]">{{ $icon }}</span>
                </div>
                
                <form action="{{ route('admin.courses.delete', $program->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الدورة؟')" onclick="event.stopPropagation()" class="z-10">
                    @csrf
                    <button type="submit" class="w-9 h-9 rounded-full flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-all active:scale-90" title="حذف الدورة">
                        <span class="material-symbols-outlined text-xl">delete</span>
                    </button>
                </form>
            </div>
            
            <div class="flex flex-col mt-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">folder</span>
                    {{ $program->department_name }}
                </span>
                <h4 class="text-lg font-bold text-slate-850 dark:text-white leading-snug group-hover:text-primary-dark dark:group-hover:text-primary transition-colors">{{ $program->name }}</h4>
                <div class="flex items-center gap-2 mt-4">
                    <span class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-extrabold border border-slate-100/50 dark:border-slate-700/50">{{ $program->course_count }} مواد</span>
                    <span class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-extrabold border border-slate-100/50 dark:border-slate-700/50">{{ $program->total_hours }} ساعة معتمدة</span>
                </div>
            </div>
        </div>
    @empty
    @endforelse

    {{-- Empty State Placeholder for Filter --}}
    <div id="no-courses-placeholder" class="hidden col-span-full flex flex-col items-center justify-center py-16 gap-4 bg-white dark:bg-surface-dark rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-500 flex items-center justify-center">
            <span class="material-symbols-outlined text-4xl">menu_book</span>
        </div>
        <div class="flex flex-col gap-1 max-w-sm">
            <p class="text-base font-bold text-slate-850 dark:text-white">لا توجد دورات مخصصة لهذا القسم بعد</p>
            <p class="text-xs text-slate-400">لم يتم العثور على أي دورات لهذا القسم. يمكنك إضافة دورة جديدة لهذا القسم فوراً!</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap justify-center mt-2">
            <a id="empty-state-add-course-btn" href="{{ route('admin.courses.create') }}" class="px-5 py-2.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-black shadow-glow hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>إضافة دورة جديدة لهذا القسم</span>
            </a>
            <button type="button" onclick="openAssignCoursesModal()" class="px-5 py-2.5 rounded-2xl bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50 text-xs font-black hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-base">assignment_add</span>
                <span>تخصيص دورات موجودة بالمنظومة</span>
            </button>
            <button onclick="selectDepartmentFilter('all')" class="px-5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 transition-all">إعادة عرض كل الأقسام</button>
        </div>
    </div>
</div>
</div>

{{-- ===== Modal: Assign / Transfer Existing Courses to Department ===== --}}
<div id="assign-courses-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-all duration-300 font-Cairo">
    <div id="assign-courses-card" class="bg-white dark:bg-surface-dark w-full max-w-xl rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 transition-all duration-300 flex flex-col max-h-[85vh]">
        
        {{-- Header --}}
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-500 flex items-center justify-center font-black">
                    <span class="material-symbols-outlined text-xl">assignment_add</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-amber-500 uppercase">تخصيص وإلحاق الدورات</span>
                    <h3 id="assign-modal-title" class="text-sm font-black text-slate-850 dark:text-white">اختر الدورات لإلحاقها بالقسم</h3>
                </div>
            </div>
            <button type="button" onclick="closeAssignCoursesModal()" class="w-8 h-8 rounded-xl bg-slate-200/60 dark:bg-slate-800 text-slate-500 hover:text-rose-500 flex items-center justify-center transition-all">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.courses.assign-programs') }}" method="POST" class="p-6 flex flex-col gap-4 overflow-y-auto hide-scrollbar">
            @csrf
            <input type="hidden" name="department_id" id="assign-dept-id-input" value="" />

            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold leading-relaxed">
                حدّد الدورات المتاحة في المنظومة التي ترغب في إلحاقها وتخصيصها لهذا القسم. الدورات المسجلة لقسم آخر سيتم نقلها رسمياً للقسم الجديد.
            </p>

            {{-- Filter Pills inside Modal --}}
            <div class="flex items-center gap-2 p-1 bg-slate-100 dark:bg-slate-900/60 rounded-2xl border border-slate-200/60 dark:border-slate-800">
                <button type="button" onclick="filterModalAssignCourses('all')" id="assign-pill-all" class="assign-filter-pill flex-1 py-1.5 px-3 rounded-xl text-xs font-black bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700">
                    الكل ({{ count($programs) }})
                </button>
                <button type="button" onclick="filterModalAssignCourses('unassigned')" id="assign-pill-unassigned" class="assign-filter-pill flex-1 py-1.5 px-3 rounded-xl text-xs font-bold text-slate-500 hover:text-emerald-600">
                    🌿 غير مخصصة ({{ $programs->whereNull('department_id')->count() }})
                </button>
                <button type="button" onclick="filterModalAssignCourses('assigned')" id="assign-pill-assigned" class="assign-filter-pill flex-1 py-1.5 px-3 rounded-xl text-xs font-bold text-slate-500 hover:text-amber-600">
                    🏛️ مخصصة لأقسام أخرى ({{ $programs->whereNotNull('department_id')->count() }})
                </button>
            </div>

            <div class="flex flex-col gap-2.5 max-h-[45vh] overflow-y-auto pr-1">
                @php
                    $unassignedPrograms = $programs->whereNull('department_id');
                    $assignedPrograms = $programs->whereNotNull('department_id');
                @endphp

                {{-- Group 1: Unassigned Courses --}}
                @if(count($unassignedPrograms) > 0)
                    <div class="assign-group-header unassigned-group-header text-[11px] font-black text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 pt-1 pb-0.5 sticky top-0 bg-white dark:bg-surface-dark z-10">
                        <span class="material-symbols-outlined text-base">check_circle</span>
                        <span>دورات غير مخصصة (جاهزة للإلحاق بالقسم فوراً) ({{ count($unassignedPrograms) }})</span>
                    </div>

                    @foreach($unassignedPrograms as $p)
                        <label class="assign-course-item flex items-center justify-between p-3.5 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 dark:bg-emerald-500/10 hover:border-emerald-500 cursor-pointer transition-all" data-type="unassigned">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="program_ids[]" value="{{ $p->id }}" onchange="checkCourseTransferWarning(this, '')" class="w-4 h-4 rounded text-emerald-500 focus:ring-emerald-500 dark:bg-slate-800 border-slate-300 dark:border-slate-700" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-extrabold text-slate-850 dark:text-white">{{ $p->name }}</span>
                                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-xs">auto_awesome</span>
                                        <span>دورة جديدة / مستقلة (غير مخصصة لأي قسم)</span>
                                    </span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                @endif

                {{-- Group 2: Courses assigned to other departments --}}
                @if(count($assignedPrograms) > 0)
                    <div class="assign-group-header assigned-group-header text-[11px] font-black text-amber-600 dark:text-amber-400 flex items-center gap-1.5 pt-3 pb-0.5 sticky top-0 bg-white dark:bg-surface-dark z-10">
                        <span class="material-symbols-outlined text-base">alt_route</span>
                        <span>دورات مسجلة لأقسام أخرى (سيتم نقلها للقسم الجديد) ({{ count($assignedPrograms) }})</span>
                    </div>

                    @foreach($assignedPrograms as $p)
                        <label class="assign-course-item flex items-center justify-between p-3.5 rounded-2xl border border-slate-200/70 dark:border-slate-800 hover:border-amber-500/50 bg-slate-50/50 dark:bg-slate-900/50 cursor-pointer transition-all" data-type="assigned">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="program_ids[]" value="{{ $p->id }}" onchange="checkCourseTransferWarning(this, '{{ $p->department_name }}')" class="w-4 h-4 rounded text-amber-500 focus:ring-amber-500 dark:bg-slate-800 border-slate-300 dark:border-slate-700" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-extrabold text-slate-850 dark:text-white">{{ $p->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 mt-0.5">مخصصة حالياً لـ: <strong class="text-slate-600 dark:text-slate-300">{{ $p->department_name }}</strong></span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                @endif
            </div>

            <div id="transfer-warning-box" class="hidden p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-start gap-2.5 text-amber-600 dark:text-amber-400">
                <span class="material-symbols-outlined text-lg shrink-0 mt-0.5">warning</span>
                <p class="text-xs font-bold leading-relaxed" id="transfer-warning-text"></p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeAssignCoursesModal()" class="px-5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 transition-all">إلغاء</button>
                <button type="submit" class="px-6 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs transition-all shadow-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">check_circle</span>
                    <span>حفظ وتخصيص الدورات المختارة</span>
                </button>
            </div>
        </form>

    </div>
</div>

{{-- ===== Modal: Edit Department ===== --}}
<div id="edit-dept-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-all duration-300 font-Cairo">
    <div class="bg-white dark:bg-surface-dark w-full max-w-md rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 transition-all duration-300 flex flex-col" id="edit-dept-modal-card">
        <div class="p-6 pb-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 font-black">
                    <span class="material-symbols-outlined text-xl">edit_note</span>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-850 dark:text-white">تعديل بيانات القسم الأكاديمي</h3>
                    <p class="text-[11px] font-bold text-slate-400">قم بتعديل اسم القسم أو وصفه</p>
                </div>
            </div>
            <button onclick="closeEditDepartmentModal()" type="button" class="w-8 h-8 rounded-xl bg-slate-200/60 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-rose-500 transition-all">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form id="edit-dept-form" action="" method="POST" class="p-6 flex flex-col gap-4">
            @csrf
            <div>
                <label class="block text-xs font-extrabold text-slate-700 dark:text-slate-300 mb-1.5">اسم القسم *</label>
                <input type="text" id="edit-dept-name" name="name" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-xs font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all">
            </div>

            <div>
                <label class="block text-xs font-extrabold text-slate-700 dark:text-slate-300 mb-1.5">وصف القسم</label>
                <textarea id="edit-dept-desc" name="description" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-xs font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all resize-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeEditDepartmentModal()" class="px-5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 transition-all">إلغاء</button>
                <button type="submit" class="px-6 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs transition-all shadow-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>حفظ التعديلات</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Course Details Modal ===== --}}
<div id="course-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-lg rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 transition-all duration-300 flex flex-col" id="modal-card">
        <!-- Header -->
        <div class="p-6 pb-4 flex items-start justify-between border-b border-slate-100 dark:border-slate-800/85">
            <div class="flex items-center gap-3">
                <div id="modal-icon-container" class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm">
                    <span id="modal-icon" class="material-symbols-outlined text-[24px]">school</span>
                </div>
                <div class="flex flex-col">
                    <h3 id="modal-title" class="text-lg font-bold text-slate-850 dark:text-white leading-tight"></h3>
                    <span id="modal-dept" class="text-xs font-bold text-slate-400 mt-1"></span>
                </div>
            </div>
            <button onclick="closeCourseModal()" class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 flex flex-col gap-5 overflow-y-auto max-h-[60vh] hide-scrollbar">
            <!-- Program details stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl flex flex-col items-center justify-center text-center border border-slate-100/50 dark:border-slate-800/30">
                    <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 mb-1">class</span>
                    <span id="modal-courses-count" class="text-sm font-black text-slate-800 dark:text-white"></span>
                    <span class="text-[10px] font-bold text-slate-400 mt-0.5">عدد المواد الدراسية</span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl flex flex-col items-center justify-center text-center border border-slate-100/50 dark:border-slate-800/30">
                    <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 mb-1">schedule</span>
                    <span id="modal-hours" class="text-sm font-black text-slate-800 dark:text-white"></span>
                    <span class="text-[10px] font-bold text-slate-400 mt-0.5">إجمالي الساعات المعتمدة</span>
                </div>
            </div>

            <!-- Program Description -->
            <div class="flex flex-col gap-2">
                <h4 class="text-xs font-bold text-slate-450 dark:text-slate-500">وصف البرنامج التدريبي</h4>
                <p id="modal-desc" class="text-xs text-slate-600 dark:text-slate-350 leading-relaxed bg-slate-50 dark:bg-slate-800/20 p-4 rounded-2xl border border-slate-100/50 dark:border-slate-800/30">
                </p>
            </div>

            <!-- Subjects / Courses list -->
            <div class="flex flex-col gap-3">
                <h4 class="text-xs font-bold text-slate-450 dark:text-slate-500">المواد والمناهج الدراسية المشمولة</h4>
                <div id="modal-subjects-list" class="flex flex-col gap-2.5">
                    <!-- Dynamic subject items -->
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Add Department Modal ===== --}}
<div id="add-dept-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
    <div class="bg-white dark:bg-surface-dark w-full max-w-md rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 transition-all duration-300 flex flex-col" id="add-dept-modal-card">
        <!-- Modal Header -->
        <div class="p-6 pb-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-800/85">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <span class="material-symbols-outlined text-2xl">domain_add</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-850 dark:text-white">إضافة قسم أكاديمي جديد</h3>
                    <p class="text-xs text-slate-400">أدخل تفاصيل القسم لإضافته إلى المنظومة</p>
                </div>
            </div>
            <button onclick="closeAddDepartmentModal()" type="button" class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="{{ route('admin.departments.store') }}" method="POST" class="p-6 flex flex-col gap-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">اسم القسم *</label>
                <input type="text" name="name" required placeholder="مثال: هندسة البرمجيات" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-xs md:text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">وصف القسم</label>
                <textarea name="description" rows="3" placeholder="توصيل مختبرات ومعلومات القسم..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl py-3 px-4 text-xs md:text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 transition-all resize-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 mt-3">
                <button type="button" onclick="closeAddDepartmentModal()" class="px-5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 transition-all">إلغاء</button>
                <button type="submit" class="px-6 py-2.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-extrabold shadow-glow transition-all">حفظ القسم</button>
            </div>
        </form>
    </div>
</div>



<div class="h-8"></div>

@endsection

@push('scripts')
<script>
    let activeDeptId = 'all';

    function onDepartmentSelectChange(deptId) {
        selectDepartmentFilter(deptId, false);
    }

    function selectDepartmentFilter(deptId, syncSelect = true) {
        activeDeptId = deptId;

        // Sync dropdown listbox if triggered from pills
        if (syncSelect) {
            const selectEl = document.getElementById('department-select-filter');
            if (selectEl) selectEl.value = deptId;
        }

        // Update active pill UI styling
        document.querySelectorAll('.dept-pill-btn').forEach(btn => {
            const btnDeptId = btn.getAttribute('data-dept-id');
            if (btnDeptId === String(deptId)) {
                btn.className = 'dept-pill-btn active px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 bg-primary text-primary-content shadow-glow';
            } else {
                btn.className = 'dept-pill-btn px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700';
            }
        });

        filterCourses();
    }

    function filterCourses() {
        const searchQuery = (document.getElementById('course-search-input')?.value || '').toLowerCase().trim();
        const cards = document.querySelectorAll('.course-card-item');
        let visibleCount = 0;
        const totalCount = cards.length;

        cards.forEach(card => {
            const cardDeptId = card.getAttribute('data-dept-id');
            const cardName = card.getAttribute('data-course-name') || '';
            const cardDeptName = card.getAttribute('data-dept-name') || '';

            const matchesDept = (activeDeptId === 'all' || String(cardDeptId) === String(activeDeptId));
            const matchesSearch = (!searchQuery || cardName.includes(searchQuery) || cardDeptName.includes(searchQuery));

            if (matchesDept && matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Update counter badge
        const badge = document.getElementById('filtered-count-badge');
        if (badge) {
            badge.textContent = `عرض ${visibleCount} من أصل ${totalCount} دورة`;
        }

        // Toggle empty placeholder state
        const placeholder = document.getElementById('no-courses-placeholder');
        if (placeholder) {
            if (visibleCount === 0) {
                placeholder.classList.remove('hidden');
            } else {
                placeholder.classList.add('hidden');
            }
        }

        const emptyBtn = document.getElementById('empty-state-add-course-btn');
        if (emptyBtn) {
            if (activeDeptId !== 'all') {
                emptyBtn.href = "{{ route('admin.courses.create') }}?department_id=" + activeDeptId;
            } else {
                emptyBtn.href = "{{ route('admin.courses.create') }}";
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const deptId = urlParams.get('department_id');
        if (deptId) {
            switchMainView('courses');
            selectDepartmentFilter(deptId);
        }
    });

    setTimeout(() => {
        document.querySelectorAll('.alert-toast').forEach(el => {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        });
    }, 4000);

    function showCourseDetails(program, colorClass, iconName) {
        const modal = document.getElementById('course-modal');
        const modalCard = document.getElementById('modal-card');
        
        document.getElementById('modal-title').textContent = program.name;
        document.getElementById('modal-dept').textContent = program.department_name;
        document.getElementById('modal-courses-count').textContent = program.course_count + ' مواد';
        document.getElementById('modal-hours').textContent = program.total_hours + ' ساعة';
        
        if (program.description) {
            document.getElementById('modal-desc').textContent = program.description;
        } else {
            document.getElementById('modal-desc').textContent = 'هذا البرنامج يهدف لتزويد الطلاب بالخبرات الأكاديمية والعملية اللازمة للنجاح والتميز في سوق العمل في تخصص ' + program.name + '.';
        }
        
        const iconContainer = document.getElementById('modal-icon-container');
        iconContainer.className = 'w-12 h-12 rounded-2xl flex items-center justify-center ' + colorClass;
        document.getElementById('modal-icon').textContent = iconName;
        
        const subjectsList = document.getElementById('modal-subjects-list');
        subjectsList.innerHTML = '';
        
        if (program.courses_list && program.courses_list.length > 0) {
            program.courses_list.forEach(subject => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100/50 dark:border-slate-800/30';
                item.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-6 bg-[#f2f20d] rounded-full"></span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">${subject.course_name}</span>
                    </div>
                    <span class="text-[10px] font-mono font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">${subject.course_code || 'SUB-101'}</span>
                `;
                subjectsList.appendChild(item);
            });
        } else {
            subjectsList.innerHTML = `
                <div class="text-center p-6 bg-slate-50 dark:bg-slate-800/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 text-slate-400 text-xs font-bold">
                    لا توجد مواد مضافة لهذا البرنامج حالياً
                </div>
            `;
        }
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modalCard.classList.remove('scale-95');
            modalCard.classList.add('scale-100');
        }, 20);
    }

    function closeCourseModal() {
        const modal = document.getElementById('course-modal');
        const modalCard = document.getElementById('modal-card');
        
        modal.classList.remove('opacity-100');
        modalCard.classList.remove('scale-100');
        modalCard.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    document.getElementById('course-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCourseModal();
        }
    });

    function openAddDepartmentModal() {
        const modal = document.getElementById('add-dept-modal');
        const modalCard = document.getElementById('add-dept-modal-card');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modalCard.classList.remove('scale-95');
            modalCard.classList.add('scale-100');
        }, 20);
    }

    function closeAddDepartmentModal() {
        const modal = document.getElementById('add-dept-modal');
        const modalCard = document.getElementById('add-dept-modal-card');
        modal.classList.remove('opacity-100');
        modalCard.classList.remove('scale-100');
        modalCard.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    document.getElementById('add-dept-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddDepartmentModal();
        }
    });

    function filterModalAssignCourses(type) {
        document.querySelectorAll('.assign-filter-pill').forEach(pill => {
            pill.className = 'assign-filter-pill flex-1 py-1.5 px-3 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 transition-all';
        });

        const activeBtn = document.getElementById('assign-pill-' + type);
        if (activeBtn) {
            activeBtn.className = 'assign-filter-pill flex-1 py-1.5 px-3 rounded-xl text-xs font-black bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700 transition-all';
        }

        document.querySelectorAll('.assign-course-item').forEach(item => {
            const itemType = item.getAttribute('data-type');
            if (type === 'all' || itemType === type) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });

        const unassignedHeader = document.querySelector('.unassigned-group-header');
        const assignedHeader = document.querySelector('.assigned-group-header');

        if (unassignedHeader) unassignedHeader.style.display = (type === 'assigned') ? 'none' : 'flex';
        if (assignedHeader) assignedHeader.style.display = (type === 'unassigned') ? 'none' : 'flex';
    }

    function openAssignCoursesModal(specificDeptId = null) {
        switchMainView('courses');

        const selectEl = document.getElementById('department-select-filter');
        let deptId = specificDeptId;
        if (!deptId) {
            deptId = (typeof activeDeptId !== 'undefined' && activeDeptId !== 'all') ? activeDeptId : (selectEl ? selectEl.value : '');
        }

        if (!deptId || deptId === 'all') {
            alert('يرجى اختيار قسم محدد من الفلتر بالأعلى أولاً للتخصيص له.');
            return;
        }

        if (selectEl) {
            selectEl.value = deptId;
            if (typeof filterCoursesByDepartment === 'function') {
                filterCoursesByDepartment(deptId);
            }
        }

        document.getElementById('assign-dept-id-input').value = deptId;
        
        let deptName = '';
        if (selectEl) {
            const selectedOpt = selectEl.querySelector(`option[value="${deptId}"]`);
            if (selectedOpt) {
                deptName = selectedOpt.textContent.replace('📂', '').trim();
            }
        }

        document.getElementById('assign-modal-title').textContent = 'تخصيص وإلحاق دورات لقسم: ' + (deptName || '');

        const modal = document.getElementById('assign-courses-modal');
        const card = document.getElementById('assign-courses-card');

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 20);
    }

    function closeAssignCoursesModal() {
        const modal = document.getElementById('assign-courses-modal');
        const card = document.getElementById('assign-courses-card');

        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    const assignModal = document.getElementById('assign-courses-modal');
    if (assignModal) {
        assignModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAssignCoursesModal();
            }
        });
    }

    function switchMainView(mode) {
        const deptsContainer = document.getElementById('depts-view-container');
        const coursesContainer = document.getElementById('courses-view-container');
        const tabDepts = document.getElementById('tab-btn-depts');
        const tabCourses = document.getElementById('tab-btn-courses');
        const btnAddDept = document.getElementById('btn-add-dept');
        const btnAddCourse = document.getElementById('btn-add-course');

        if (mode === 'depts') {
            tabDepts.className = 'px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700';
            tabCourses.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all flex items-center gap-2';

            deptsContainer.classList.remove('hidden');
            coursesContainer.classList.add('hidden');

            if (btnAddDept) btnAddDept.classList.remove('hidden');
            if (btnAddCourse) btnAddCourse.classList.add('hidden');
        } else {
            tabCourses.className = 'px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200/60 dark:border-slate-700';
            tabDepts.className = 'px-5 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all flex items-center gap-2';

            coursesContainer.classList.remove('hidden');
            deptsContainer.classList.add('hidden');

            if (btnAddDept) btnAddDept.classList.add('hidden');
            if (btnAddCourse) btnAddCourse.classList.remove('hidden');
        }
    }

    function openEditDepartmentModal(dept) {
        document.getElementById('edit-dept-name').value = dept.name;
        document.getElementById('edit-dept-desc').value = dept.description || '';
        document.getElementById('edit-dept-form').action = "/admin/departments/update/" + dept.department_id;

        const modal = document.getElementById('edit-dept-modal');
        const card = document.getElementById('edit-dept-modal-card');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 20);
    }

    function closeEditDepartmentModal() {
        const modal = document.getElementById('edit-dept-modal');
        const card = document.getElementById('edit-dept-modal-card');
        modal.classList.remove('opacity-100');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    const editDeptModal = document.getElementById('edit-dept-modal');
    if (editDeptModal) {
        editDeptModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditDepartmentModal();
            }
        });
    }

    function checkCourseTransferWarning(checkbox, currentDeptName) {
        const warningBox = document.getElementById('transfer-warning-box');
        const warningText = document.getElementById('transfer-warning-text');
        
        const checkedInputs = document.querySelectorAll('#assign-courses-modal input[name="program_ids[]"]:checked');
        if (checkedInputs.length > 0) {
            warningText.textContent = `تنبيه: سيتم تخصيص أو نقل الدورات المختارة رسمياً لتصبح تابعة لهذا القسم الجديد!`;
            warningBox.classList.remove('hidden');
        } else {
            warningBox.classList.add('hidden');
        }
    }
</script>
@endpush
