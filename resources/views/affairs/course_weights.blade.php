@extends('layouts.affairs')

@section('title', 'المسار الأكاديمي الطلابي')

@push('styles')
<style>
    /* ─────────────────────────── Theme-Adaptive System ─────────────────────────── */
    .cw-card {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        border-radius: 1.25rem;
        color: var(--text-primary);
        transition: background-color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }
    .cw-card:hover {
        border-color: rgba(245, 158, 11, 0.35);
    }
    html.dark .cw-card:hover, [data-theme="dark"] .cw-card:hover {
        border-color: rgba(242, 242, 13, 0.3);
    }

    .cw-inner-box {
        background-color: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        border-radius: 0.875rem;
        transition: background-color 0.25s ease, border-color 0.25s ease;
    }

    .cw-title {
        color: var(--text-primary) !important;
    }

    .cw-subtitle, .cw-label {
        color: var(--text-secondary) !important;
    }

    /* High-contrast Theme Accent Colors for text and icons */
    .cw-accent-text {
        color: #b45309 !important; /* Deep rich amber in light theme - 100% readable on white/light grey */
        font-weight: 800 !important;
    }
    html.dark .cw-accent-text, [data-theme="dark"] .cw-accent-text {
        color: var(--accent-color) !important; /* Bright neon yellow in dark theme */
    }

    .cw-accent-icon {
        color: #d97706 !important; /* Rich amber icon in light theme */
    }
    html.dark .cw-accent-icon, [data-theme="dark"] .cw-accent-icon {
        color: var(--accent-color) !important; /* Neon yellow in dark theme */
    }

    .cw-accent-badge {
        background-color: rgba(245, 158, 11, 0.14) !important;
        color: #b45309 !important;
        border: 1px solid rgba(245, 158, 11, 0.35) !important;
        font-weight: 800 !important;
    }
    html.dark .cw-accent-badge, [data-theme="dark"] .cw-accent-badge {
        background-color: rgba(242, 242, 13, 0.15) !important;
        color: var(--accent-color) !important;
        border-color: rgba(242, 242, 13, 0.35) !important;
    }

    .cw-input, .cw-select {
        background-color: var(--bg-primary) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        transition: all 0.2s ease;
    }
    .cw-input:focus, .cw-select:focus {
        border-color: #d97706 !important;
        outline: none;
        box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.2);
    }
    html.dark .cw-input:focus, [data-theme="dark"] .cw-input:focus,
    html.dark .cw-select:focus, [data-theme="dark"] .cw-select:focus {
        border-color: var(--accent-color) !important;
        box-shadow: 0 0 0 2px rgba(242, 242, 13, 0.25);
    }
    .cw-input::placeholder {
        color: var(--text-secondary) !important;
        opacity: 0.7;
    }

    /* Table Styles */
    .cw-table th {
        background-color: var(--bg-primary);
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border-color);
    }
    .cw-table td {
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    .cw-table tr:hover td {
        background-color: rgba(245, 158, 11, 0.05);
    }
    html.dark .cw-table tr:hover td, [data-theme="dark"] .cw-table tr:hover td {
        background-color: rgba(242, 242, 13, 0.05);
    }
    .cw-table tfoot {
        background-color: var(--bg-primary);
        border-top: 2px solid var(--border-color);
        color: var(--text-primary);
    }

    /* Student List Items */
    .student-item {
        transition: all 0.2s ease;
        border-inline-end: 3px solid transparent;
        color: var(--text-primary);
    }
    .student-item:hover {
        background-color: var(--bg-primary);
    }
    .student-item.active {
        background-color: rgba(245, 158, 11, 0.14) !important;
        border-inline-end: 3px solid #d97706 !important;
    }
    html.dark .student-item.active, [data-theme="dark"] .student-item.active {
        background-color: rgba(242, 242, 13, 0.15) !important;
        border-inline-end: 3px solid var(--accent-color) !important;
    }

    /* Badges */
    .status-badge-pass {
        background: rgba(34, 197, 94, 0.12);
        color: #15803d;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    html.dark .status-badge-pass, [data-theme="dark"] .status-badge-pass {
        background: rgba(34, 197, 94, 0.18);
        color: #4ade80;
        border-color: rgba(34, 197, 94, 0.35);
    }

    .status-badge-fail {
        background: rgba(239, 68, 68, 0.12);
        color: #b91c1c;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    html.dark .status-badge-fail, [data-theme="dark"] .status-badge-fail {
        background: rgba(239, 68, 68, 0.18);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.35);
    }

    .status-badge-supp {
        background: rgba(245, 158, 11, 0.12);
        color: #b45309;
        border: 1px solid rgba(245, 158, 11, 0.35);
    }
    html.dark .status-badge-supp, [data-theme="dark"] .status-badge-supp {
        background: rgba(245, 158, 11, 0.18);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.35);
    }

    .status-badge-grad {
        background: rgba(59, 130, 246, 0.12);
        color: #1d4ed8;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }
    html.dark .status-badge-grad, [data-theme="dark"] .status-badge-grad {
        background: rgba(59, 130, 246, 0.18);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.35);
    }

    /* Scrollbar */
    .custom-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: var(--bg-primary);
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 4px;
    }
    .custom-scroll::-webkit-scrollbar-thumb:hover {
        background: #d97706;
    }
    html.dark .custom-scroll::-webkit-scrollbar-thumb:hover, [data-theme="dark"] .custom-scroll::-webkit-scrollbar-thumb:hover {
        background: var(--accent-color);
    }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Section -->
    <div class="cw-card p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl cw-accent-badge flex items-center justify-center text-2xl font-bold shadow-sm">
                    <i class="fa-solid fa-graduation-cap cw-accent-icon"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black cw-title tracking-wide">المسار الأكاديمي الطلابي</h1>
                    <p class="text-sm cw-subtitle mt-1">محطة العمل الشاملة لشؤون الطلاب: فرز النتائج، تقييم التثقيلات، إصدار قرارات الترفيع والتخرج، والتقارير المعتمدة</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Cohort PDF Export Button -->
                <button onclick="exportCohortReport('pdf')" class="px-4 py-2.5 rounded-xl cw-inner-box hover:bg-zinc-200 dark:hover:bg-zinc-800 text-sm font-bold flex items-center gap-2 transition shadow-sm">
                    <i class="fa-solid fa-file-pdf text-red-500 text-base"></i>
                    <span class="cw-title">محضر نتائج الدفعة (PDF)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ─────────────────────────── الشريط العلوي للفلاتر المتتالية ─────────────────────────── -->
    <div class="cw-card p-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-[var(--border-color)] mb-4">
            <div class="flex items-center gap-2 cw-title font-bold text-sm">
                <i class="fa-solid fa-sliders cw-accent-icon"></i>
                <span>محددات الفرز الأكاديمي المتتالي</span>
            </div>

            <!-- View Mode Switcher -->
            <div class="inline-flex p-1 cw-inner-box">
                <button type="button" id="btnModeStudent" onclick="switchViewMode('student')" class="px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 bg-primary text-black shadow">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span>منظور الطلاب (Workstation)</span>
                </button>
                <button type="button" id="btnModeCourse" onclick="switchViewMode('course')" class="px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 cw-subtitle hover:cw-title">
                    <i class="fa-solid fa-book-open"></i>
                    <span>منظور المواد (Courses)</span>
                </button>
            </div>
        </div>

        <!-- Dropdowns Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- 1. Department Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-building-columns cw-accent-icon text-xs"></i>
                    <span>1. القسم الأكاديمي</span>
                </label>
                <select id="filterDepartment" onchange="onDepartmentChanged()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="all">جميع الأقسام</option>
                    @foreach($data['departments'] as $dept)
                        <option value="{{ $dept->department_id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Program/Branch Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-code-branch cw-accent-icon text-xs"></i>
                    <span>2. الفرع / التخصص</span>
                </label>
                <select id="filterProgram" onchange="applyFilters()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="all">جميع التخصصات</option>
                    @foreach($data['programs'] as $prog)
                        <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id }}">{{ $prog->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Academic Year Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-days cw-accent-icon text-xs"></i>
                    <span>3. السنة الدراسية</span>
                </label>
                <select id="filterYear" onchange="onYearChanged()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="both">كلاهما (الأولى والثانية)</option>
                    <option value="1">السنة الأولى</option>
                    <option value="2">السنة الثانية</option>
                </select>
            </div>

            <!-- 4. Semester Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group cw-accent-icon text-xs"></i>
                    <span>4. الفصل الدراسي</span>
                </label>
                <select id="filterSemester" onchange="applyFilters()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="both">كلاهما (الفصلين)</option>
                    <option value="1">الفصل الأول</option>
                    <option value="2">الفصل الثاني</option>
                </select>
            </div>

            <!-- 5. Standing Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-clipboard-check cw-accent-icon text-xs"></i>
                    <span>5. الحالة الأكاديمية</span>
                </label>
                <select id="filterStanding" onchange="applyFilters()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="all">جميع الحالات الأكاديمية</option>
                    <option value="passed">الناجحون والمترفعون (✅)</option>
                    <option value="failed">الراسبون (❌)</option>
                    <option value="supplementary">الدورة التكميلية (📝)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ─────────────────────────── المنظور 1: منظور الطلاب (Student-Centric Workstation) ─────────────────────────── -->
    <div id="studentWorkstationContainer" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- القائمة الجانبية للطلاب (Right Sidebar) -->
        <div class="lg:col-span-4 cw-card p-4 flex flex-col h-[780px]">
            <!-- Search & Count -->
            <div class="space-y-3 pb-3 border-b border-[var(--border-color)]">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute right-3.5 top-3 cw-subtitle text-sm"></i>
                    <input type="text" id="studentSearchInput" oninput="onStudentSearch()" placeholder="بحث بالاسم أو الرقم الجامعي..." class="w-full cw-input rounded-xl pr-10 pl-4 py-2 text-sm font-semibold">
                </div>
                <div class="flex items-center justify-between text-xs cw-subtitle px-1 font-semibold">
                    <span>الطلاب المطابقون:</span>
                    <span id="matchedStudentsCount" class="px-2 py-0.5 rounded-md cw-accent-badge font-bold">0</span>
                </div>
            </div>

            <!-- Scrollable Student List -->
            <div id="studentsListScroll" class="custom-scroll flex-1 overflow-y-auto divide-y divide-[var(--border-color)] mt-2 space-y-1 pr-1">
                <!-- Injected by JS -->
            </div>
        </div>

        <!-- اللوحة الرئيسية لبيانات وقرارات الطالب (Main Workstation) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Navigation and Single Student Export Bar -->
            <div class="cw-card p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button type="button" onclick="navigateStudent(-1)" class="px-3.5 py-2 rounded-xl cw-inner-box hover:bg-zinc-200 dark:hover:bg-zinc-800 text-xs font-bold flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-arrow-right"></i>
                        <span>السابق</span>
                    </button>
                    <span id="studentNavCounter" class="text-xs font-bold px-2 py-1 cw-inner-box">
                        0 من 0
                    </span>
                    <button type="button" onclick="navigateStudent(1)" class="px-3.5 py-2 rounded-xl cw-inner-box hover:bg-zinc-200 dark:hover:bg-zinc-800 text-xs font-bold flex items-center gap-1.5 transition">
                        <span>التالي</span>
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="exportStudentTranscript('pdf')" class="px-4 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold flex items-center gap-2 border border-red-500/30 transition shadow-sm">
                        <i class="fa-solid fa-file-pdf text-sm"></i>
                        <span>كشف درجات الطالب (PDF)</span>
                    </button>
                </div>
            </div>

            <!-- بطاقة الطالب المؤسساتية (Institutional Dossier Card) -->
            <div class="cw-card p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-32 h-32 bg-amber-500/5 rounded-full blur-2xl pointer-events-none"></div>

                <div class="flex items-start justify-between gap-4 pb-4 border-b border-[var(--border-color)]">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl cw-inner-box flex items-center justify-center text-2xl font-bold shadow-inner">
                            <i class="fa-solid fa-user-tie cw-accent-icon"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <h2 id="dossierFullName" class="text-xl font-black cw-title">-</h2>
                                <span id="dossierStatusBadge" class="px-2.5 py-1 rounded-full text-xs font-bold status-badge-pass">ناجح</span>
                            </div>
                            <div class="flex items-center gap-3 mt-1.5 text-xs cw-subtitle font-semibold">
                                <span>الرقم الجامعي: <b id="dossierStudentCode" class="cw-accent-badge px-2 py-0.5 rounded font-mono">-</b></span>
                                <span>•</span>
                                <span>تاريخ الالتحاق: <span id="dossierJoinedAt" class="cw-title font-semibold">-</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="text-left">
                        <span class="text-xs cw-subtitle block">نطاق الفلترة النشط:</span>
                        <span id="dossierScopeBadge" class="text-xs font-bold cw-title px-2.5 py-1 cw-inner-box mt-1 inline-block">
                            كافة الفصول والسنوات
                        </span>
                    </div>
                </div>

                <!-- Detailed Dossier Metadata Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-1 text-xs">
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">القسم العلمي</span>
                        <span id="dossierDept" class="cw-title font-bold text-sm">-</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">الفرع / التخصص</span>
                        <span id="dossierProgram" class="cw-title font-bold text-sm">-</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">السنة / المستوى</span>
                        <span id="dossierLevel" class="cw-accent-text font-black text-sm">-</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">المرشد الأكاديمي (المربي)</span>
                        <span id="dossierAdvisor" class="cw-title font-bold text-sm">-</span>
                    </div>
                </div>

                <div class="mt-3 text-xs cw-subtitle flex items-center gap-2">
                    <i class="fa-solid fa-id-badge cw-accent-icon"></i>
                    <span>رئيس القسم المختص: <b id="dossierHOD" class="cw-title">-</b></span>
                </div>
            </div>

            <!-- ─────────────────────────── سجل ومحطة المقررات والتثقيلات الموزونة مفصولة بالفصول ─────────────────────────── -->
            <div class="space-y-6">
                
                <!-- رأس المحطة ومؤشرات نطاق الفلترة -->
                <div class="cw-card p-4 flex flex-wrap items-center justify-between gap-3 bg-[var(--bg-primary)]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl cw-accent-badge flex items-center justify-center text-lg font-bold shadow-sm">
                            <i class="fa-solid fa-layer-group cw-accent-icon"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-sm cw-title">سجل المقررات والتثقيلات الموزونة مفصولة بالفصول الدراسية</h3>
                            <p id="coursesScopeSubtitle" class="text-[11px] cw-subtitle mt-0.5">عرض المقررات مقسمة وفق الفصول المحددة بالفلترة مع احتساب معدل كل فصل وتراكمي الفصول</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="displayedSemestersBadge" class="text-xs px-3 py-1 rounded-lg cw-inner-box cw-subtitle font-bold">
                            0 فصول
                        </span>
                        <span id="coursesCountBadge" class="text-xs px-3 py-1 rounded-lg cw-accent-badge font-bold">
                            0 مقرر
                        </span>
                    </div>
                </div>

                <!-- حاوية الفصول الدراسية المنفصلة (Dynamically injected semester cards) -->
                <div id="studentSemestersContainer" class="space-y-6">
                    <!-- Injected dynamically by renderActiveStudent() -->
                </div>

                <!-- بطاقة ملخص المعدل التراكمي العام (Cumulative Summary Card) -->
                <div id="studentCumSummaryCard" class="cw-card p-5 border-2 border-amber-500/30 dark:border-primary/40 bg-gradient-to-r from-amber-500/5 via-transparent to-amber-500/5 transition">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-[var(--border-color)]">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-amber-500/20 text-amber-600 dark:text-primary flex items-center justify-center text-xl shadow-inner">
                                <i class="fa-solid fa-calculator"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-sm cw-title">ملخص المعدل التراكمي العام (الفصول المنتهية المجتازة)</h3>
                                <p id="cumFormulaExplanation" class="text-xs cw-subtitle mt-0.5 font-medium">
                                    المعادلة المعتمدة: مجموع معدلات الفصول المنتهية المجتازة ÷ عددها فقط
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-end md:self-auto">
                            <div class="text-left md:text-right">
                                <span class="text-xs cw-subtitle block font-semibold">المعدل التراكمي العام:</span>
                                <span id="cumGpaDisplay" class="text-3xl font-black font-mono cw-accent-text tracking-tight">0.00%</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-xs">
                        <div class="cw-inner-box p-3 text-center">
                            <span class="cw-subtitle block mb-1">الفصول المنتهية المجتازة</span>
                            <span id="cumSemestersCount" class="cw-title font-bold text-base font-mono">0</span>
                        </div>
                        <div class="cw-inner-box p-3 text-center">
                            <span class="cw-subtitle block mb-1">إجمالي الساعات (التثقيل)</span>
                            <span id="cumTotalHours" class="cw-title font-bold text-base font-mono">0</span>
                        </div>
                        <div class="cw-inner-box p-3 text-center">
                            <span class="cw-subtitle block mb-1">إجمالي المقررات</span>
                            <span id="cumTotalCoursesCount" class="cw-title font-bold text-base font-mono">0</span>
                        </div>
                        <div class="cw-inner-box p-3 text-center">
                            <span class="cw-subtitle block mb-1">الحالة الأكاديمية للمقررات</span>
                            <span id="cumOverallStatus" class="font-bold text-sm text-emerald-600 dark:text-emerald-400">مستوفٍ لشروط النجاح</span>
                        </div>
                    </div>

                    <!-- إشعار المقررات الراسبة المتبقية إن وجدت -->
                    <div id="failedCoursesAlert" class="hidden mt-4 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 flex items-start gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-red-500 text-base mt-0.5"></i>
                        <div>
                            <div class="text-xs font-bold text-red-600 dark:text-red-400">المقررات غير المجتازة ضمن الفصول المعروضة:</div>
                            <div id="failedCoursesListText" class="text-xs text-red-700 dark:text-red-200 mt-1 font-semibold leading-relaxed">-</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- لوحة القرارات الأكاديمية الذكية (Academic Decision Engine) -->
            <div class="cw-card p-6 border-t-4 border-t-amber-500 dark:border-t-primary">
                <div class="flex items-center justify-between pb-3 border-b border-[var(--border-color)] mb-4">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-gavel cw-accent-icon text-base"></i>
                        <h3 class="text-base font-black cw-title">محطة اتخاذ القرار الأكاديمي لشؤون الطلاب</h3>
                    </div>
                    <span class="text-xs cw-subtitle font-semibold">تحديث فوري وإشعار تلقائي للطالب</span>
                </div>

                <!-- Decision Actions Buttons -->
                <div class="space-y-4">
                    <div id="decisionButtonsContainer" class="flex flex-wrap items-center gap-3">
                        <!-- Rendered dynamically according to student level & standing -->
                    </div>

                    <!-- Administrative Remarks Input -->
                    <div>
                        <label class="block text-xs font-bold cw-subtitle mb-1.5">
                            ملاحظات وتوجيهات الشؤون الإدارية (تُرسل في إشعار الطالب وتُحفظ بالسجل الأكاديمي):
                        </label>
                        <textarea id="decisionNotesInput" rows="2" placeholder="أدخل أي ملاحظات رسمية أو استثناءات إدارية هنا..." class="w-full cw-input rounded-xl p-3 text-xs"></textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ─────────────────────────── المنظور 2: منظور المواد (Course-Centric View) ─────────────────────────── -->
    <div id="courseWorkstationContainer" class="hidden grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- قائمة المواد المفلترة (Right Sidebar) -->
        <div class="lg:col-span-4 cw-card p-4 flex flex-col h-[740px]">
            <div class="pb-3 border-b border-[var(--border-color)] flex items-center justify-between">
                <div class="flex items-center gap-2 cw-title font-bold text-sm">
                    <i class="fa-solid fa-list-check cw-accent-icon"></i>
                    <span>قائمة المواد المفلترة</span>
                </div>
                <span id="matchedCoursesCount" class="text-xs px-2 py-0.5 rounded-md cw-accent-badge font-bold">0 مادة</span>
            </div>

            <div id="coursesListScroll" class="custom-scroll flex-1 overflow-y-auto divide-y divide-[var(--border-color)] mt-2 space-y-1 pr-1">
                <!-- Injected by JS -->
            </div>
        </div>

        <!-- تفاصيل المادة والطلاب المسجلين (Main Course View) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Hero Course Card -->
            <div class="cw-card p-6 relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-[var(--border-color)]">
                    <div>
                        <span id="courseYearBadge" class="px-2.5 py-1 rounded-full text-xs font-bold cw-accent-badge">السنة الأولى</span>
                        <h2 id="courseTitleHeader" class="text-xl font-black cw-title mt-2">-</h2>
                        <div class="flex items-center gap-3 text-xs cw-subtitle mt-1 font-semibold">
                            <span>التثقيل (الساعات المعتمدة): <b id="courseWeightHeader" class="cw-accent-text font-mono">-</b></span>
                            <span>•</span>
                            <span>الفصل الدراسي: <span id="courseSemesterHeader" class="cw-title">-</span></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="exportCourseReport('pdf')" class="px-4 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold flex items-center gap-2 border border-red-500/30 transition shadow-sm">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span>كشف المادة المعتمد (PDF)</span>
                        </button>
                        <button type="button" onclick="exportCourseReport('excel')" class="px-3 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-1.5 border border-emerald-500/30 transition">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>إكسل</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Bar -->
                <div class="grid grid-cols-3 gap-4 mt-5 text-center text-xs">
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">إجمالي المسجلين</span>
                        <span id="courseTotalEnrolled" class="cw-title font-bold text-lg font-mono">0</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">نسبة النجاح</span>
                        <span id="coursePassRate" class="text-emerald-600 dark:text-emerald-400 font-bold text-lg font-mono">0%</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">الطلاب الراسبون</span>
                        <span id="courseFailCount" class="text-red-600 dark:text-red-400 font-bold text-lg font-mono">0</span>
                    </div>
                </div>
            </div>

            <!-- جدول الطلاب المسجلين بالمادة -->
            <div class="cw-card overflow-hidden">
                <div class="p-4 border-b border-[var(--border-color)] flex items-center justify-between bg-[var(--bg-primary)]">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-users cw-accent-icon"></i>
                        <h3 class="font-black text-sm cw-title">الطلاب المسجلون ونتائجهم بالمقرر</h3>
                    </div>

                    <!-- Sub-filter by Pass/Fail -->
                    <div class="inline-flex p-0.5 cw-inner-box text-xs font-semibold">
                        <button type="button" onclick="filterCourseStudents('all')" id="btnCsFilterAll" class="px-3 py-1 rounded-md bg-[var(--bg-secondary)] text-[var(--text-primary)] font-bold shadow-sm border border-[var(--border-color)]">الكل</button>
                        <button type="button" onclick="filterCourseStudents('pass')" id="btnCsFilterPass" class="px-3 py-1 rounded-md text-[var(--text-secondary)] hover:text-[var(--text-primary)]">الناجحون</button>
                        <button type="button" onclick="filterCourseStudents('fail')" id="btnCsFilterFail" class="px-3 py-1 rounded-md text-[var(--text-secondary)] hover:text-[var(--text-primary)]">الراسبون</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs cw-table">
                        <thead>
                            <tr class="font-bold">
                                <th class="py-3 px-4">الرقم الجامعي</th>
                                <th class="py-3 px-4">اسم الطالب</th>
                                <th class="py-3 px-4 text-center">المذاكرة (25)</th>
                                <th class="py-3 px-4 text-center">الشفهي (25)</th>
                                <th class="py-3 px-4 text-center">الامتحان (50)</th>
                                <th class="py-3 px-4 text-center">علامة المقرر (100)</th>
                                <th class="py-3 px-4 text-center">التثقيل</th>
                                <th class="py-3 px-4 text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody id="courseStudentsTbody" class="divide-y divide-[var(--border-color)] font-semibold">
                            <!-- Injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ─────────────────────────── Application Data & State ───────────────────────────
    const appData = @json($data);
    let currentMode = 'student'; // 'student' or 'course'
    let filteredStudents = [];
    let currentStudentIndex = 0;
    let filteredCourses = [];
    let currentCourseId = null;
    let courseSubFilter = 'all';

    document.addEventListener('DOMContentLoaded', () => {
        applyFilters();
    });

    // ─────────────────────────── View Mode Switcher ───────────────────────────
    function switchViewMode(mode) {
        currentMode = mode;
        const btnStudent = document.getElementById('btnModeStudent');
        const btnCourse = document.getElementById('btnModeCourse');
        const containerStudent = document.getElementById('studentWorkstationContainer');
        const containerCourse = document.getElementById('courseWorkstationContainer');

        if (mode === 'student') {
            btnStudent.className = 'px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 bg-primary text-black shadow';
            btnCourse.className = 'px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 cw-subtitle hover:cw-title';
            containerStudent.classList.remove('hidden');
            containerCourse.classList.add('hidden');
            renderStudentWorkstation();
        } else {
            btnCourse.className = 'px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 bg-primary text-black shadow';
            btnStudent.className = 'px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 cw-subtitle hover:cw-title';
            containerCourse.classList.remove('hidden');
            containerStudent.classList.add('hidden');
            renderCourseWorkstation();
        }
    }

    // ─────────────────────────── Cascading Dropdown Handlers ───────────────────────────
    function onDepartmentChanged() {
        const deptId = document.getElementById('filterDepartment').value;
        const progSelect = document.getElementById('filterProgram');

        for (let i = 0; i < progSelect.options.length; i++) {
            const opt = progSelect.options[i];
            if (opt.value === 'all') {
                opt.style.display = 'block';
                continue;
            }
            const progDept = opt.getAttribute('data-dept');
            if (deptId === 'all' || progDept === deptId) {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        }
        progSelect.value = 'all';
        applyFilters();
    }

    function onYearChanged() {
        const yearVal = document.getElementById('filterYear').value;
        const semSelect = document.getElementById('filterSemester');
        if (yearVal === 'both') {
            semSelect.value = 'both';
        }
        applyFilters();
    }

    function applyFilters() {
        const deptId = document.getElementById('filterDepartment').value;
        const progId = document.getElementById('filterProgram').value;
        const year = document.getElementById('filterYear').value;
        const semester = document.getElementById('filterSemester').value;
        const standing = document.getElementById('filterStanding').value;

        // 1. Filter Students
        filteredStudents = (appData.studentsList || []).filter(s => {
            if (deptId !== 'all' && String(s.department_id) !== String(deptId)) return false;
            if (progId !== 'all' && String(s.program_id) !== String(progId)) return false;
            
            const levelStr = String(s.level || '').trim();
            const isYear1 = levelStr.includes('الأولى') || levelStr === '1' || levelStr.includes('first');
            const isYear2 = levelStr.includes('الثانية') || levelStr.includes('خريج') || levelStr.includes('تكميل') || levelStr === '2' || levelStr.includes('second');

            if (year === '1' && !isYear1) return false;
            if (year === '2' && !isYear2) return false;

            if (standing !== 'all') {
                const sStanding = s.summary?.standing || 'passed';
                const hasFailed = (s.summary?.failed_count ?? 0) > 0 || (s.summary?.failed_courses?.length ?? 0) > 0;

                if (standing === 'passed') {
                    if (hasFailed || !['passed', 'graduated'].includes(sStanding)) return false;
                }
                if (standing === 'failed') {
                    // يشمل أي طالب لديه مواد غير مجتازة سواء في السنة الأولى أو الثانية (بما في ذلك الدورة التكميلية)
                    if (!hasFailed && sStanding !== 'failed' && sStanding !== 'supplementary') return false;
                }
                if (standing === 'supplementary') {
                    if (sStanding !== 'supplementary' && !(!isYear1 && hasFailed)) return false;
                }
            }
            return true;
        });

        // 2. Filter Courses
        filteredCourses = (appData.courses || []).filter(c => {
            if (progId !== 'all' && c.program_id && String(c.program_id) !== String(progId)) return false;
            if (year !== 'both' && String(c.year) !== String(year)) return false;
            if (semester !== 'both' && String(c.semester_id) !== String(semester)) return false;
            return true;
        });

        currentStudentIndex = 0;
        if (currentMode === 'student') {
            renderStudentWorkstation();
        } else {
            renderCourseWorkstation();
        }
    }

    // ─────────────────────────── Student Workstation Engine ───────────────────────────
    function renderStudentWorkstation() {
        const searchVal = (document.getElementById('studentSearchInput').value || '').trim().toLowerCase();
        let displayList = filteredStudents;

        if (searchVal) {
            displayList = displayList.filter(s => 
                (s.full_name && s.full_name.toLowerCase().includes(searchVal)) ||
                (s.student_code && s.student_code.toLowerCase().includes(searchVal))
            );
        }

        document.getElementById('matchedStudentsCount').innerText = displayList.length;
        const scrollContainer = document.getElementById('studentsListScroll');
        scrollContainer.innerHTML = '';

        if (displayList.length === 0) {
            scrollContainer.innerHTML = `
                <div class="text-center py-12 cw-subtitle text-xs">
                    <i class="fa-solid fa-user-slash text-2xl mb-2 block opacity-40"></i>
                    لا يوجد طلاب مطابقون لمعايير الفلترة الحالية
                </div>
            `;
            clearStudentDossier();
            return;
        }

        if (currentStudentIndex >= displayList.length) {
            currentStudentIndex = 0;
        }

        displayList.forEach((s, idx) => {
            const isActive = idx === currentStudentIndex;
            const isPass = s.summary?.standing === 'passed' || s.summary?.standing === 'graduated';
            const iconHtml = isPass
                ? `<span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/40 flex items-center justify-center text-xs font-black">✓</span>`
                : `<span class="w-6 h-6 rounded-full bg-red-500/20 text-red-600 dark:text-red-400 border border-red-500/40 flex items-center justify-center text-xs font-black">✗</span>`;

            // احتساب معدل الطالب التراكمي حسب الفلترة النشطة
            const yearVal = document.getElementById('filterYear').value;
            const semVal = document.getElementById('filterSemester').value;
            const sCalc = calculateStudentData(s, yearVal, semVal);
            const displayGpa = sCalc ? sCalc.cumGpa : (s.summary?.weighted_gpa ?? 0);

            const gpaBadge = `<span class="text-[10px] font-bold px-2 py-0.5 rounded ${isPass ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25' : 'bg-red-500/15 text-red-700 dark:text-red-300 border border-red-500/25'}">${displayGpa}%</span>`;

            const item = document.createElement('div');
            item.className = `student-item p-3 rounded-xl cursor-pointer flex items-center justify-between border border-transparent ${isActive ? 'active' : ''}`;
            item.onclick = () => {
                currentStudentIndex = idx;
                renderStudentWorkstation();
            };

            item.innerHTML = `
                <div class="flex items-center gap-3">
                    ${iconHtml}
                    <div>
                        <div class="text-xs font-bold cw-title">${s.full_name}</div>
                        <div class="text-[11px] cw-subtitle font-mono mt-0.5">${s.student_code || 'بدون رقم'} • ${s.level}</div>
                    </div>
                </div>
                ${gpaBadge}
            `;
            scrollContainer.appendChild(item);
        });

        const activeStudent = displayList[currentStudentIndex];
        renderActiveStudent(activeStudent, currentStudentIndex + 1, displayList.length);
    }

    function onStudentSearch() {
        currentStudentIndex = 0;
        renderStudentWorkstation();
    }

    function navigateStudent(delta) {
        const searchVal = (document.getElementById('studentSearchInput').value || '').trim().toLowerCase();
        let displayList = filteredStudents;
        if (searchVal) {
            displayList = displayList.filter(s => 
                (s.full_name && s.full_name.toLowerCase().includes(searchVal)) ||
                (s.student_code && s.student_code.toLowerCase().includes(searchVal))
            );
        }
        if (displayList.length === 0) return;

        currentStudentIndex += delta;
        if (currentStudentIndex < 0) currentStudentIndex = displayList.length - 1;
        if (currentStudentIndex >= displayList.length) currentStudentIndex = 0;

        renderStudentWorkstation();
    }

    // ─────────────────────────── مصفوفة الفصول الأربعة وحساب المعدلات الدقيقة ───────────────────────────
    const CANONICAL_SEMESTERS = [
        { key: '1_1', year: 1, semester_id: 1, num: 1, title: 'الفصل الأول', yearLabel: 'السنة الأولى', fullTitle: 'الفصل الأول • السنة الأولى' },
        { key: '1_2', year: 1, semester_id: 2, num: 2, title: 'الفصل الثاني', yearLabel: 'السنة الأولى', fullTitle: 'الفصل الثاني • السنة الأولى' },
        { key: '2_1', year: 2, semester_id: 1, num: 3, title: 'الفصل الثالث', yearLabel: 'السنة الثانية (فصل 1)', fullTitle: 'الفصل الثالث • السنة الثانية (الفصل الأول)' },
        { key: '2_2', year: 2, semester_id: 2, num: 4, title: 'الفصل الرابع', yearLabel: 'السنة الثانية (فصل 2)', fullTitle: 'الفصل الرابع • السنة الثانية (الفصل الثاني)' },
    ];

    /**
     * احتساب بيانات الطالب الموزونة وفق الفصول النشطة والمعادلة المعتمدة:
     * 1. علامة المادة محصورة 0 - 100
     * 2. معدل الفصل = مجموع (العلامة × الوزن) ÷ مجموع الأوزان
     * 3. المعدل التراكمي النهائي = مجموع معدلات الفصول المنتهية المجتازة ÷ عدد الفصول المنتهية المجتازة فقط
     */
    function calculateStudentData(s, yearFilter, semFilter) {
        if (!s) return null;
        const allCourses = s.courses || [];
        const semGroups = [];
        let totalCoursesCount = 0;
        let cumHours = 0;
        let cumPoints = 0;
        const allSemGpas = [];
        const passedSemGpas = [];
        const failedTitles = [];

        // تحديد ما إذا كان الطالب في السنة الأولى
        const levelStr = String(s.level || '');
        const isYear1 = levelStr.includes('الأولى');

        CANONICAL_SEMESTERS.forEach(semDef => {
            // طالب السنة الأولى لديه فصلان فقط (الأول والثاني) ولا يمكن أن تظهر له فصول السنة الثانية (3 و 4)
            if (isYear1 && semDef.year > 1) return;

            if (yearFilter !== 'both' && String(semDef.year) !== String(yearFilter)) return;
            if (semFilter !== 'both' && String(semDef.semester_id) !== String(semFilter)) return;

            const coursesInSem = allCourses.filter(c => 
                Number(c.year) === semDef.year && Number(c.semester_id) === semDef.semester_id
            );
            // تحديد حالة الفصل الدراسي بالنسبة للطالب (سنة أولى أم سنة ثانية):
            // لطالب السنة الأولى: الفصل الثاني فقط مغلق
            // لطالب السنة الثانية: الفصل الأخير فقط مغلق (الفصل الرابع)، وفصول السنة الأولى مجتازة من العام الماضي
            const isClosed = isYear1 
                ? (semDef.year === 1 && semDef.semester_id === 2)
                : (semDef.year === 2 && semDef.semester_id === 2);

            const isPastCompleted = !isYear1 && (semDef.year === 1);
            const isCurrentActive = isYear1 
                ? (semDef.year === 1 && semDef.semester_id === 1)
                : (semDef.year === 2 && semDef.semester_id === 1);

            if (coursesInSem.length > 0) {
                let sHours = 0;
                let sPoints = 0;

                const mappedCourses = coursesInSem.map(c => {
                    const w = Number(c.weight) > 0 ? Number(c.weight) : 1;
                    const maxScore = 100;
                    totalCoursesCount++;
                    sHours += w;

                    if (isCurrentActive) {
                        const rawScore = Number(c.score) || 0;
                        const pct = Math.min(100, Math.max(0, Number(rawScore.toFixed(1))));
                        const pts = Number((pct * w).toFixed(2));
                        const isPass = pct >= 50;
                        sPoints += pts;

                        if (!isPass) {
                            failedTitles.push(c.title);
                        }

                        return {
                            course_id: c.course_id,
                            title: c.title,
                            year: c.year,
                            semester_id: c.semester_id,
                            isClosed: false,
                            isPastCompleted: false,
                            isCurrentActive: true,
                            quiz_score: Number(c.quiz_score) || 0,
                            oral_score: Number(c.oral_score) || 0,
                            exam_score: Number(c.exam_score) || 0,
                            score: rawScore,
                            max_score: maxScore,
                            percentage: pct,
                            weight: w,
                            points: pts,
                            status: isPass ? 'ناجح' : 'راسب',
                            isPass: isPass
                        };
                    } else if (isPastCompleted) {
                        return {
                            course_id: c.course_id,
                            title: c.title,
                            year: c.year,
                            semester_id: c.semester_id,
                            isClosed: false,
                            isPastCompleted: true,
                            isCurrentActive: false,
                            quiz_score: '-',
                            oral_score: '-',
                            exam_score: '-',
                            score: '-',
                            max_score: maxScore,
                            percentage: '-',
                            weight: w,
                            points: 0,
                            status: 'مجتاز (السنة السابقة)',
                            isPass: true
                        };
                    } else {
                        // isClosed
                        const closedText = isYear1 ? 'مغلق المقرر لحين انتهاء الفصل الأول' : 'مغلق المقرر (الفصل الأخير)';
                        return {
                            course_id: c.course_id,
                            title: c.title,
                            year: c.year,
                            semester_id: c.semester_id,
                            isClosed: true,
                            isPastCompleted: false,
                            isCurrentActive: false,
                            quiz_score: '-',
                            oral_score: '-',
                            exam_score: '-',
                            score: '-',
                            max_score: maxScore,
                            percentage: '-',
                            weight: w,
                            points: 0,
                            status: closedText,
                            isPass: null
                        };
                    }
                });

                sPoints = Number(sPoints.toFixed(2));
                let semGpa = null;
                let isSemPassed = false;

                if (isCurrentActive) {
                    semGpa = sHours > 0 ? Number((sPoints / sHours).toFixed(2)) : 0;
                    allSemGpas.push(semGpa);
                    isSemPassed = semGpa >= 50;
                    if (isSemPassed) {
                        passedSemGpas.push({
                            title: semDef.title,
                            gpa: semGpa,
                            hours: sHours
                        });
                    }
                    cumHours += sHours;
                    cumPoints += sPoints;
                } else if (isPastCompleted) {
                    isSemPassed = true; // فصول السنة الأولى مجتازة لطالب السنة الثانية
                    cumHours += sHours;
                } else {
                    isSemPassed = false;
                }

                semGroups.push({
                    semDef: semDef,
                    isClosed: isClosed,
                    isPastCompleted: isPastCompleted,
                    isCurrentActive: isCurrentActive,
                    courses: mappedCourses,
                    hours: sHours,
                    points: sPoints,
                    gpa: semGpa,
                    isPass: isSemPassed
                });
            }
        });

        // عدد الفصول المنتهية المجتازة فعلياً:
        // لطالب السنة الأولى: عدد الفصول السارية المجتازة (1)
        // لطالب السنة الثانية: فصلان منجزين من السنة الأولى + الفصل الساري المجتاز (3 فصول)
        const visiblePassedCount = semGroups.filter(g => g.isPass === true).length;
        const totalInstitutionalPassed = isYear1 ? visiblePassedCount : (2 + passedSemGpas.length);

        // مجموع معدلات الفصول المنتهية المجتازة
        const sumPassedGpas = passedSemGpas.reduce((acc, cur) => acc + cur.gpa, 0);

        // المعدل التراكمي النهائي = مجموع معدلات الفصول المنتهية المجتازة ÷ عدد الفصول المجتازة
        let cumGpa = (s.summary && typeof s.summary.weighted_gpa === 'number') 
            ? s.summary.weighted_gpa 
            : (totalInstitutionalPassed > 0 ? Number((sumPassedGpas / totalInstitutionalPassed).toFixed(2)) : 0);

        return {
            semGroups,
            totalCoursesCount,
            cumHours,
            cumPoints: Number(cumPoints.toFixed(2)),
            semGpas: allSemGpas,
            allSemGpas,
            passedSemGpas,
            passedSemestersCount: totalInstitutionalPassed,
            cumGpa,
            failedTitles
        };
    }

    function renderActiveStudent(s, pos, total) {
        document.getElementById('studentNavCounter').innerText = `${pos} من ${total}`;

        if (!s) {
            clearStudentDossier();
            return;
        }

        // Dossier Info
        document.getElementById('dossierFullName').innerText = s.full_name;
        document.getElementById('dossierStudentCode').innerText = s.student_code || '-';
        document.getElementById('dossierJoinedAt').innerText = s.joined_at || '2026-09-01';
        document.getElementById('dossierDept').innerText = s.department_name || '-';
        document.getElementById('dossierProgram').innerText = s.program_name || '-';
        document.getElementById('dossierLevel').innerText = s.level || 'السنة الأولى';
        document.getElementById('dossierAdvisor').innerText = s.advisor_name || 'أ. أحمد نصلى (المربي)';
        document.getElementById('dossierHOD').innerText = s.hod_name || 'د. أحمد ديب (رئيس القسم)';

        // تحديد السنة
        const levelStr = String(s.level || '');
        const isYear1 = levelStr.includes('الأولى');

        // نطاق الفلترة النشط
        const yVal = document.getElementById('filterYear').value;
        const sVal = document.getElementById('filterSemester').value;
        let scopeText = '';
        if (yVal === 'both' && sVal === 'both') {
            scopeText = isYear1 ? 'السنة الأولى • الفصل الأول والثاني' : 'كافة السنوات (الأولى والثانية) • الفصول 1 إلى 4';
        } else if (yVal === '1' && sVal === 'both') {
            scopeText = 'السنة الأولى • الفصل الأول والثاني';
        } else if (yVal === '1' && sVal === '1') {
            scopeText = 'السنة الأولى • الفصل الأول';
        } else if (yVal === '1' && sVal === '2') {
            scopeText = 'السنة الأولى • الفصل الثاني';
        } else if (yVal === '2' && sVal === 'both') {
            scopeText = isYear1 ? 'السنة الأولى (لا توجد فصول سنة ثانية)' : 'السنة الثانية • الفصل الثالث والرابع';
        } else if (yVal === '2' && sVal === '1') {
            scopeText = isYear1 ? 'السنة الأولى (لا توجد فصول سنة ثانية)' : 'السنة الثانية • الفصل الثالث (الفصل الأول)';
        } else if (yVal === '2' && sVal === '2') {
            scopeText = isYear1 ? 'السنة الأولى (لا توجد فصول سنة ثانية)' : 'السنة الثانية • الفصل الرابع (الفصل الثاني)';
        } else if (yVal === 'both' && sVal === '1') {
            scopeText = isYear1 ? 'السنة الأولى • الفصل الأول' : 'كافة السنوات • الفصل الأول والثالث';
        } else if (yVal === 'both' && sVal === '2') {
            scopeText = isYear1 ? 'السنة الأولى • الفصل الثاني' : 'كافة السنوات • الفصل الثاني والرابع';
        }
        document.getElementById('dossierScopeBadge').innerText = scopeText;
        document.getElementById('coursesScopeSubtitle').innerText = `نطاق العرض النشط: ${scopeText} (مفصولة بهوامش ومعدلات دقيقة)`;

        // Standing Badge
        const standing = s.summary?.standing || 'passed';
        const badge = document.getElementById('dossierStatusBadge');
        if (standing === 'graduated') {
            badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold status-badge-grad';
            badge.innerText = 'خريج رسمي معتمد 🎓';
        } else if (standing === 'supplementary') {
            badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold status-badge-supp';
            badge.innerText = 'دورة تكميلية 📝';
        } else if (standing === 'failed') {
            badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold status-badge-fail';
            badge.innerText = 'راسب - إعادة سنة ⚠️';
        } else {
            badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold status-badge-pass';
            badge.innerText = 'ناجح ومترفع ✅';
        }

        // احتساب المقررات مقسمة وفق الفصول مع معدل كل فصل والتراكمي
        const data = calculateStudentData(s, yVal, sVal);

        // Header Badges
        document.getElementById('displayedSemestersBadge').innerText = `${data.semGroups.length} فصول معروضة`;
        document.getElementById('coursesCountBadge').innerText = `${data.totalCoursesCount} مقرر`;

        // Render Semesters Container
        const container = document.getElementById('studentSemestersContainer');
        container.innerHTML = '';

        if (data.semGroups.length === 0) {
            container.innerHTML = `
                <div class="cw-card p-10 text-center">
                    <i class="fa-solid fa-folder-open text-3xl cw-subtitle opacity-40 mb-3 block"></i>
                    <div class="cw-title font-bold text-sm">لا توجد مقررات مسجلة لهذا الطالب مطابقة للفلاتر المحددة</div>
                    <div class="cw-subtitle text-xs mt-1">يرجى تعديل محددات السنة الدراسية أو الفصل من شريط التصفية بالأعلى</div>
                </div>
            `;
        } else {
            data.semGroups.forEach(group => {
                const semDef = group.semDef;
                const card = document.createElement('div');
                card.className = 'cw-card overflow-hidden shadow-sm transition hover:shadow-md border border-[var(--border-color)]';

                let semBadgeHtml = '';
                if (group.isClosed) {
                    const lockMsg = isYear1 ? 'مغلق المقرر لحين انتهاء الفصل الأول' : 'مغلق المقرر (الفصل الأخير يبدأ لاحقاً)';
                    semBadgeHtml = `<span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30 flex items-center gap-1.5 shadow-sm"><i class="fa-solid fa-lock text-[10px]"></i> ${lockMsg}</span>`;
                } else if (group.isCurrentActive) {
                    semBadgeHtml = `<span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-[10px]"></i> الفصل الساري حالياً</span>`;
                } else if (group.isPastCompleted) {
                    semBadgeHtml = `<span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-500/15 text-blue-700 dark:text-blue-400 border border-blue-500/30 flex items-center gap-1.5"><i class="fa-solid fa-clock-rotate-left text-[10px]"></i> مجتاز (العام الدراسي الماضي 2024/2025)</span>`;
                }

                let gpaHeaderBadgeHtml = '';
                if (group.isCurrentActive) {
                    gpaHeaderBadgeHtml = `
                        <div class="px-3.5 py-1.5 rounded-xl bg-amber-500/15 dark:bg-[var(--accent-color)]/20 border border-amber-500/30 dark:border-[var(--accent-color)]/40 text-xs font-bold flex items-center gap-2">
                            <span class="text-amber-800 dark:text-amber-200">معدل الفصل:</span>
                            <span class="font-mono font-black text-sm text-amber-700 dark:text-[var(--accent-color)]">${group.gpa.toFixed(2)}%</span>
                        </div>
                    `;
                } else if (group.isPastCompleted) {
                    gpaHeaderBadgeHtml = `
                        <div class="px-3.5 py-1.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs font-bold flex items-center gap-2">
                            <span class="text-blue-700 dark:text-blue-300">معدل الفصل:</span>
                            <span class="font-bold text-xs text-blue-600 dark:text-blue-400">مجتاز بنجاح</span>
                        </div>
                    `;
                } else {
                    gpaHeaderBadgeHtml = `
                        <div class="px-3.5 py-1.5 rounded-xl cw-inner-box text-xs font-bold flex items-center gap-2">
                            <span class="cw-subtitle">معدل الفصل:</span>
                            <span class="font-mono font-bold text-sm cw-subtitle">-</span>
                        </div>
                    `;
                }

                const headerHtml = `
                    <div class="p-4 border-b border-[var(--border-color)] flex flex-wrap items-center justify-between gap-3 bg-[var(--bg-primary)]">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl cw-accent-badge flex items-center justify-center font-mono font-black text-sm shadow-sm border border-amber-500/20">
                                ${semDef.num}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-black text-base cw-title">${semDef.title}</h4>
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full cw-inner-box cw-subtitle">${semDef.yearLabel}</span>
                                    ${semBadgeHtml}
                                </div>
                                <p class="text-[11px] cw-subtitle mt-0.5">مقررات ${semDef.title} (${semDef.yearLabel}) المعتمدة في الخطة</p>
                            </div>
                        </div>

                        <!-- Quick Badges -->
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="px-3 py-1.5 rounded-xl cw-inner-box text-xs font-semibold flex items-center gap-2">
                                <span class="cw-subtitle">الساعات (التثقيل):</span>
                                <span class="font-mono font-bold cw-title">${group.hours}</span>
                            </div>
                            <div class="px-3 py-1.5 rounded-xl cw-inner-box text-xs font-semibold flex items-center gap-2">
                                <span class="cw-subtitle">عدد المقررات:</span>
                                <span class="font-mono font-bold cw-title">${group.courses.length}</span>
                            </div>
                            ${gpaHeaderBadgeHtml}
                        </div>
                    </div>
                `;

                // Courses Table
                let rowsHtml = '';
                group.courses.forEach(c => {
                    let statusBadge = '';
                    if (group.isClosed) {
                        const lockText = isYear1 ? 'مغلق المقرر لحين انتهاء الفصل الأول' : 'مغلق المقرر (الفصل الأخير)';
                        statusBadge = `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">${lockText}</span>`;
                    } else if (group.isPastCompleted) {
                        statusBadge = `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20">مجتاز (السنة السابقة)</span>`;
                    } else if (c.isPass) {
                        statusBadge = `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold status-badge-pass">ناجح</span>`;
                    } else {
                        statusBadge = `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold status-badge-fail">راسب</span>`;
                    }

                    const quizDisplay = group.isCurrentActive ? c.quiz_score : '-';
                    const oralDisplay = group.isCurrentActive ? c.oral_score : '-';
                    const examDisplay = group.isCurrentActive ? c.exam_score : '-';
                    const totalDisplay = group.isCurrentActive ? `${c.percentage} / 100` : '-';

                    rowsHtml += `
                        <tr class="transition hover:bg-black/5 dark:hover:bg-white/5">
                            <td class="py-3 px-4 font-bold cw-title">${c.title}</td>
                            <td class="py-3 px-4 text-center font-mono cw-subtitle">${quizDisplay}</td>
                            <td class="py-3 px-4 text-center font-mono cw-subtitle">${oralDisplay}</td>
                            <td class="py-3 px-4 text-center font-mono cw-subtitle">${examDisplay}</td>
                            <td class="py-3 px-4 text-center font-mono font-bold cw-title">${totalDisplay}</td>
                            <td class="py-3 px-4 text-center font-mono cw-subtitle">${c.weight}</td>
                            <td class="py-3 px-4 text-center">
                                ${statusBadge}
                            </td>
                        </tr>
                    `;
                });

                let footerGpaHtml = '';
                if (group.isCurrentActive) {
                    footerGpaHtml = `
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono ${group.isPass ? 'text-emerald-700 dark:text-emerald-300 bg-emerald-500/10' : 'text-red-700 dark:text-red-300 bg-red-500/10'}">
                            معدل الفصل: ${group.gpa.toFixed(2)}%
                        </span>
                    `;
                } else if (group.isPastCompleted) {
                    footerGpaHtml = `
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono text-blue-700 dark:text-blue-300 bg-blue-500/10">
                            مجتاز من العام الماضي
                        </span>
                    `;
                } else {
                    footerGpaHtml = `
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono cw-subtitle bg-black/5 dark:bg-white/5">
                            يبدأ لاحقاً
                        </span>
                    `;
                }

                const tableHtml = `
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs cw-table">
                            <thead>
                                <tr class="font-bold border-b border-[var(--border-color)]">
                                    <th class="py-3 px-4">المقرر الدراسي</th>
                                    <th class="py-3 px-4 text-center">المذاكرة (25)</th>
                                    <th class="py-3 px-4 text-center">الشفهي (25)</th>
                                    <th class="py-3 px-4 text-center">الامتحان (50)</th>
                                    <th class="py-3 px-4 text-center">علامة المقرر (100)</th>
                                    <th class="py-3 px-4 text-center">التثقيل</th>
                                    <th class="py-3 px-4 text-center">الحالة الأكاديمية</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border-color)] font-semibold">
                                ${rowsHtml}
                            </tbody>
                            <tfoot class="font-bold text-xs bg-[var(--bg-primary)]">
                                <tr>
                                    <td class="py-3.5 px-4 cw-title font-bold" colspan="5">مجموع وساعات ${semDef.title}:</td>
                                    <td class="py-3.5 px-4 text-center cw-accent-text font-mono text-sm font-black">${group.hours}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        ${footerGpaHtml}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;

                card.innerHTML = headerHtml + tableHtml;
                container.appendChild(card);
            });
        }

        // تحديث بطاقة الملخص التراكمي العام
        document.getElementById('cumGpaDisplay').innerText = data.cumGpa.toFixed(2) + '%';
        document.getElementById('cumSemestersCount').innerText = `${data.passedSemestersCount} فصول مجتازة`;
        document.getElementById('cumTotalHours').innerText = data.cumHours;
        const totalCoursesEl = document.getElementById('cumTotalCoursesCount');
        if (totalCoursesEl) totalCoursesEl.innerText = data.totalCoursesCount;

        const cumStatusEl = document.getElementById('cumOverallStatus');
        if (data.failedTitles.length === 0 && data.passedSemestersCount > 0) {
            cumStatusEl.className = 'font-bold text-sm text-emerald-600 dark:text-emerald-400';
            cumStatusEl.innerText = 'مستوفٍ لشروط النجاح ✅';
        } else if (data.failedTitles.length > 0) {
            cumStatusEl.className = 'font-bold text-sm text-red-600 dark:text-red-400';
            cumStatusEl.innerText = `راسب في ${data.failedTitles.length} مواد ⚠️`;
        } else {
            cumStatusEl.className = 'font-bold text-sm text-amber-600 dark:text-amber-400';
            cumStatusEl.innerText = 'بانتظار استكمال النتائج';
        }

        const formulaEl = document.getElementById('cumFormulaExplanation');
        if (!isYear1) {
            const activeGpaStr = data.passedSemGpas.length > 0 ? data.passedSemGpas[0].gpa.toFixed(2) + '%' : '0.00%';
            formulaEl.innerHTML = `المعادلة المعتمدة (طالب سنة ثانية): مجموع الفصول المنتهية المجتازة (فصلان من العام الماضي + الفصل الساري) ÷ عدد الفصول المجتازة (3 فصول) = <span class="cw-title font-mono font-bold">${activeGpaStr}</span> ÷ 3 = <span class="cw-accent-text font-mono font-black text-sm">${data.cumGpa.toFixed(2)}%</span>`;
        } else if (data.passedSemestersCount > 1) {
            const sumStr = data.passedSemGpas.length > 0 
                ? data.passedSemGpas.map(item => `<span class="cw-title font-mono font-bold">${item.gpa.toFixed(2)}%</span>`).join(' + ')
                : '0.00%';
            formulaEl.innerHTML = `المعادلة المعتمدة: مجموع الفصول المنتهية المجتازة ÷ عدد الفصول المجتازة (${data.passedSemestersCount} فصول) = ${sumStr} ÷ ${data.passedSemestersCount} = <span class="cw-accent-text font-mono font-black text-sm">${data.cumGpa.toFixed(2)}%</span>`;
        } else if (data.passedSemestersCount === 1) {
            const valStr = data.passedSemGpas.length > 0 ? data.passedSemGpas[0].gpa.toFixed(2) + '%' : '0.00%';
            formulaEl.innerHTML = `المعادلة المعتمدة: مجموع الفصول المنتهية المجتازة على عددها فقط = <span class="cw-title font-mono font-bold">${valStr}</span> ÷ 1 = <span class="cw-accent-text font-mono font-black text-sm">${data.cumGpa.toFixed(2)}%</span>`;
        } else if (data.allSemGpas.length > 0) {
            formulaEl.innerHTML = `المعادلة المعتمدة: لم يتم اجتياز أي فصل بمعدل ≥ 50% (المعدل الحالي: <span class="text-red-500 font-mono font-bold">${data.cumGpa.toFixed(2)}%</span>)`;
        } else {
            formulaEl.innerText = 'لا توجد فصول دراسية منتهية مجتازة حالياً وفق خيارات الفلترة ومستوى الطالب';
        }

        // صندوق المقررات غير المجتازة
        const alertBox = document.getElementById('failedCoursesAlert');
        const alertText = document.getElementById('failedCoursesListText');
        if (data.failedTitles.length > 0) {
            alertBox.classList.remove('hidden');
            alertText.innerText = data.failedTitles.join(' • ');
        } else {
            alertBox.classList.add('hidden');
        }

        // Decision Action Buttons
        renderDecisionButtons(s);
    }

    function clearStudentDossier() {
        document.getElementById('dossierFullName').innerText = 'لا يوجد طلاب مختارون';
        document.getElementById('dossierStudentCode').innerText = '-';
        document.getElementById('dossierJoinedAt').innerText = '-';
        document.getElementById('dossierDept').innerText = '-';
        document.getElementById('dossierProgram').innerText = '-';
        document.getElementById('dossierLevel').innerText = '-';
        document.getElementById('dossierAdvisor').innerText = '-';
        document.getElementById('dossierHOD').innerText = '-';
        document.getElementById('displayedSemestersBadge').innerText = '0 فصول';
        document.getElementById('coursesCountBadge').innerText = '0 مقرر';
        document.getElementById('studentSemestersContainer').innerHTML = `
            <div class="cw-card p-10 text-center">
                <i class="fa-solid fa-folder-open text-3xl cw-subtitle opacity-40 mb-3 block"></i>
                <div class="cw-title font-bold text-sm">لا توجد بيانات مقررات معروضة</div>
                <div class="cw-subtitle text-xs mt-1">اختر طالباً من القائمة الجانبية لعرض مقرراته ونتائجه</div>
            </div>
        `;
        document.getElementById('cumGpaDisplay').innerText = '0.00%';
        document.getElementById('cumSemestersCount').innerText = '0';
        document.getElementById('cumTotalHours').innerText = '0';
        document.getElementById('cumTotalPoints').innerText = '0';
        document.getElementById('cumOverallStatus').innerText = '-';
        document.getElementById('failedCoursesAlert').classList.add('hidden');
        document.getElementById('decisionButtonsContainer').innerHTML = '<span class="text-xs cw-subtitle">اختر طالباً لاتخاذ القرار الأكاديمي</span>';
    }

    function renderDecisionButtons(s) {
        const container = document.getElementById('decisionButtonsContainer');
        container.innerHTML = '';

        const level = String(s.level || '');
        const failedCount = s.summary?.failed_count ?? 0;
        const isYear1 = level.includes('الأولى');

        // فحص هل الفصل الثاني من السنة الأولى منتهٍ وله درجات؟
        const hasY1Sem2 = (s.courses || []).some(c => (Number(c.year) === 1 || !c.year) && Number(c.semester_id) === 2 && !c.is_closed && c.score !== null);

        // فحص هل الفصل الرابع (الفصل الثاني للسنة الثانية) منتهٍ وله درجات؟
        const hasY2Sem2 = (s.courses || []).some(c => Number(c.year) === 2 && Number(c.semester_id) === 2 && !c.is_closed && c.score !== null);

        if (isYear1) {
            if (hasY1Sem2) {
                // انتهى الفصل الثاني للسنة الأولى -> الترفيع للسنة الثانية
                if (failedCount === 0) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('promote_year_2', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-primary hover:brightness-105 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>الترفيع إلى السنة الثانية (تسجيل المقررات تلقائياً)</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>إعادة السنة الأولى (راسب)</span>
                        </button>
                    `;
                } else if (failedCount <= 4) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('supplementary', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>تحويل للدورة التكميلية (حصر المواد: ${failedCount})</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>إعادة السنة الأولى</span>
                        </button>
                    `;
                } else {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>راسب سنة أولى (إعادة السنة كاملة)</span>
                        </button>
                    `;
                }
            } else {
                // انتهى الفصل الأول فقط للسنة الأولى -> الترفيع للفصل الثاني
                if (failedCount === 0) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('promote_semester_2', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>الترفيع إلى الفصل الثاني (استيفاء الفصل الأول بنجاح)</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>راسب فصل أول (إعادة)</span>
                        </button>
                    `;
                } else if (failedCount <= 4) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('supplementary', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>تحويل للدورة التكميلية (فصل أول: ${failedCount} مواد)</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>إعادة السنة الأولى</span>
                        </button>
                    `;
                } else {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>راسب فصل أول (إعادة السنة)</span>
                        </button>
                    `;
                }
            }
        } else {
            // طلاب السنة الثانية
            if (hasY2Sem2) {
                // انتهى الفصل الرابع (الفصل الثاني للسنة الثانية) -> التخريج الرسمي
                if (failedCount === 0) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('graduate', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-award text-sm"></i>
                            <span>اعتماد التخرج (خريج رسمي معتمد 🎓)</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>إعادة السنة الثانية</span>
                        </button>
                    `;
                } else if (failedCount <= 4) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('supplementary', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>تحويل لدورة الخريجين التكميلية (${failedCount} مواد)</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>إعادة السنة الثانية</span>
                        </button>
                    `;
                } else {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>راسب سنة ثانية (إعادة كاملة)</span>
                        </button>
                    `;
                }
            } else {
                // انتهى الفصل الثالث (الفصل الأول للسنة الثانية) -> الترفيع للفصل الرابع
                if (failedCount === 0) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('promote_semester_4', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>الترفيع إلى الفصل الرابع (استيفاء الفصل الثالث بنجاح)</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>إعادة السنة الثانية</span>
                        </button>
                    `;
                } else if (failedCount <= 4) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('supplementary', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>تحويل للدورة التكميلية (فصل ثالث: ${failedCount} مواد)</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>إعادة السنة الثانية</span>
                        </button>
                    `;
                } else {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>راسب سنة ثانية (إعادة كاملة)</span>
                        </button>
                    `;
                }
            }
        }
    }

    // ─────────────────────────── AJAX Decision Execution ───────────────────────────
    function submitDecision(decision, studentId) {
        const notes = document.getElementById('decisionNotesInput').value;

        if (!confirm('هل أنت متأكد من اعتماد هذا القرار الأكاديمي وإشعار الطالب فورياً؟')) {
            return;
        }

        const btnContainer = document.getElementById('decisionButtonsContainer');
        const originalContent = btnContainer.innerHTML;
        btnContainer.innerHTML = `<span class="text-xs cw-accent-text font-bold animate-pulse"><i class="fa-solid fa-spinner fa-spin mr-1"></i> جارٍ حفظ القرار الأكاديمي وإرسال الإشعار...</span>`;

        fetch("{{ route('affairs.course_weights.student_decision') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                student_id: studentId,
                decision: decision,
                notes: notes
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const student = (appData.studentsList || []).find(x => x.student_id === studentId);
                if (student) {
                    student.level = data.new_level;
                    if (!student.summary) student.summary = {};
                    student.summary.standing = data.new_standing;
                }

                alert('✅ ' + data.message);
                renderStudentWorkstation();
            } else {
                alert('❌ حدث خطأ: ' + (data.message || 'تعذر حفظ القرار'));
                btnContainer.innerHTML = originalContent;
            }
        })
        .catch(err => {
            console.error(err);
            alert('❌ فشل الاتصال بالخادم.');
            btnContainer.innerHTML = originalContent;
        });
    }

    // ─────────────────────────── Course Workstation Engine ───────────────────────────
    function renderCourseWorkstation() {
        document.getElementById('matchedCoursesCount').innerText = `${filteredCourses.length} مادة`;
        const scrollContainer = document.getElementById('coursesListScroll');
        scrollContainer.innerHTML = '';

        if (filteredCourses.length === 0) {
            scrollContainer.innerHTML = `
                <div class="text-center py-12 cw-subtitle text-xs">
                    <i class="fa-solid fa-book-skull text-2xl mb-2 block opacity-40"></i>
                    لا توجد مواد مطابقة لمعايير الفلترة
                </div>
            `;
            return;
        }

        if (!currentCourseId || !filteredCourses.some(c => c.course_id === currentCourseId)) {
            currentCourseId = filteredCourses[0].course_id;
        }

        filteredCourses.forEach(c => {
            const isActive = c.course_id === currentCourseId;
            const enrolledList = (appData.students || []).filter(s => s.course_id === c.course_id);

            const item = document.createElement('div');
            item.className = `p-3 rounded-xl cursor-pointer flex items-center justify-between border border-transparent transition ${isActive ? 'bg-amber-500/15 dark:bg-primary/15 border-inline-end-4 border-amber-500 dark:border-primary font-bold' : 'hover:bg-[var(--bg-primary)]'}`;
            item.onclick = () => {
                currentCourseId = c.course_id;
                renderCourseWorkstation();
            };

            item.innerHTML = `
                <div>
                    <div class="text-xs font-bold cw-title">${c.title}</div>
                    <div class="text-[11px] cw-subtitle mt-0.5">سنة ${c.year} • فصل ${c.semester_id} • وزن: ${c.weight}</div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded cw-accent-badge font-mono">
                    ${enrolledList.length} طالب
                </span>
            `;
            scrollContainer.appendChild(item);
        });

        const activeCourse = filteredCourses.find(c => c.course_id === currentCourseId);
        renderActiveCourse(activeCourse);
    }

    function renderActiveCourse(c) {
        if (!c) return;

        document.getElementById('courseTitleHeader').innerText = c.title;
        document.getElementById('courseWeightHeader').innerText = c.weight || '1';
        document.getElementById('courseYearBadge').innerText = c.year == 1 ? 'السنة الأولى' : 'السنة الثانية';
        const isCourseOpen = (Number(c.semester_id) === 1);
        if (isCourseOpen) {
            document.getElementById('courseSemesterHeader').innerHTML = `${c.semester_id == 1 ? 'الفصل الأول' : 'الفصل الثاني'} <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 font-bold mr-2"><i class="fa-solid fa-circle-check text-[10px]"></i> الفصل الساري حالياً</span>`;
        } else {
            document.getElementById('courseSemesterHeader').innerHTML = `${c.semester_id == 1 ? 'الفصل الأول' : 'الفصل الثاني'} <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/15 text-amber-700 dark:text-amber-400 font-bold mr-2"><i class="fa-solid fa-lock text-[10px]"></i> مغلق المقرر لحين انتهاء الفصل الأول</span>`;
        }

        let students = (appData.students || []).filter(s => s.course_id === c.course_id);
        const total = students.length;
        let passCount = 0;
        let failCount = 0;

        students.forEach(s => {
            const maxScore = 100;
            const pct = (s.exam_score / maxScore) * 100;
            if (pct >= 50) passCount++; else failCount++;
        });

        document.getElementById('courseTotalEnrolled').innerText = total;
        document.getElementById('coursePassRate').innerText = isCourseOpen ? (total > 0 ? Math.round((passCount / total) * 100) + '%' : '0%') : '-';
        document.getElementById('courseFailCount').innerText = isCourseOpen ? failCount : '0';

        if (courseSubFilter === 'pass') {
            students = students.filter(s => ((s.exam_score / 100) * 100) >= 50);
        } else if (courseSubFilter === 'fail') {
            students = students.filter(s => ((s.exam_score / 100) * 100) < 50);
        }

        const tbody = document.getElementById('courseStudentsTbody');
        tbody.innerHTML = '';

        if (students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 cw-subtitle">لا يوجد طلاب مسجلون بهذه المادة</td></tr>';
            return;
        }

        students.forEach(s => {
            const maxScore = 100;
            const pct = Math.round((s.exam_score / maxScore) * 100);
            const isPass = pct >= 50;

            const quizDisplay = isCourseOpen ? (s.quiz_score || 0) : '-';
            const oralDisplay = isCourseOpen ? (s.oral_score || 0) : '-';
            const examDisplay = isCourseOpen ? (s.final_exam_score || 0) : '-';
            const totalDisplay = isCourseOpen ? `${s.exam_score} / 100` : '-';
            const statusHtml = isCourseOpen
                ? `<span class="px-2 py-0.5 rounded text-[11px] font-bold ${isPass ? 'status-badge-pass' : 'status-badge-fail'}">${isPass ? 'ناجح' : 'راسب'}</span>`
                : `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">مغلق المقرر</span>`;

            const tr = document.createElement('tr');
            tr.className = 'transition';
            tr.innerHTML = `
                <td class="py-3 px-4 font-mono cw-subtitle">${s.student_code || '-'}</td>
                <td class="py-3 px-4 font-bold cw-title">${s.student_name}</td>
                <td class="py-3 px-4 text-center font-mono cw-subtitle">${quizDisplay}</td>
                <td class="py-3 px-4 text-center font-mono cw-subtitle">${oralDisplay}</td>
                <td class="py-3 px-4 text-center font-mono cw-subtitle">${examDisplay}</td>
                <td class="py-3 px-4 text-center font-mono font-bold cw-title">${totalDisplay}</td>
                <td class="py-3 px-4 text-center font-mono cw-subtitle">${s.weight}</td>
                <td class="py-3 px-4 text-center">
                    ${statusHtml}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function filterCourseStudents(sub) {
        courseSubFilter = sub;
        const activeClass = 'px-3 py-1 rounded-md bg-[var(--bg-secondary)] text-[var(--text-primary)] font-bold shadow-sm border border-[var(--border-color)]';
        const inactiveClass = 'px-3 py-1 rounded-md text-[var(--text-secondary)] hover:text-[var(--text-primary)]';
        document.getElementById('btnCsFilterAll').className = sub === 'all' ? activeClass : inactiveClass;
        document.getElementById('btnCsFilterPass').className = sub === 'pass' ? (activeClass + ' text-emerald-600 dark:text-emerald-400') : inactiveClass;
        document.getElementById('btnCsFilterFail').className = sub === 'fail' ? (activeClass + ' text-red-600 dark:text-red-400') : inactiveClass;

        const activeCourse = filteredCourses.find(c => c.course_id === currentCourseId);
        renderActiveCourse(activeCourse);
    }

    // ─────────────────────────── Export Dispatchers ───────────────────────────
    function exportStudentTranscript(format) {
        const searchVal = (document.getElementById('studentSearchInput').value || '').trim().toLowerCase();
        let displayList = filteredStudents;
        if (searchVal) {
            displayList = displayList.filter(s => 
                (s.full_name && s.full_name.toLowerCase().includes(searchVal)) ||
                (s.student_code && s.student_code.toLowerCase().includes(searchVal))
            );
        }
        if (displayList.length === 0 || !displayList[currentStudentIndex]) {
            alert('يرجى اختيار طالب أولاً');
            return;
        }

        const studentId = displayList[currentStudentIndex].student_id;
        const url = `{{ route('affairs.course_weights.export_student') }}?student_id=${studentId}&format=${format}`;
        window.open(url, '_blank');
    }

    function exportCourseReport(format) {
        if (!currentCourseId) {
            alert('يرجى اختيار مادة أولاً');
            return;
        }
        const progId = document.getElementById('filterProgram').value !== 'all' ? document.getElementById('filterProgram').value : 1;
        const status = courseSubFilter;
        const url = `{{ route('affairs.course_weights.export_course') }}?course_id=${currentCourseId}&program_id=${progId}&status=${status}&format=${format}`;
        window.open(url, '_blank');
    }

    function exportCohortReport(format) {
        const deptId = document.getElementById('filterDepartment').value;
        const progId = document.getElementById('filterProgram').value;
        const year = document.getElementById('filterYear').value;
        const semester = document.getElementById('filterSemester').value;
        const standing = document.getElementById('filterStanding').value;

        const url = `{{ route('affairs.course_weights.export_cohort') }}?department_id=${deptId}&program_id=${progId}&year=${year}&semester_id=${semester}&standing=${standing}&format=${format}`;
        window.open(url, '_blank');
    }
</script>
@endpush
