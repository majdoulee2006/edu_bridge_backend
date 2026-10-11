@extends('layouts.affairs')

@section('title', __('messages.student_academic_pathway'))

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
                    <h1 class="text-2xl font-black cw-title tracking-wide">{{ __('messages.student_academic_pathway') }}</h1>
                    <p class="text-sm cw-subtitle mt-1">{{ __('messages.pathway_subtitle') }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                
                <!-- Premium Settings Modal Button -->
                <button onclick="document.getElementById('premiumSettingsModal').classList.remove('hidden')" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-zinc-800 to-zinc-900 hover:from-zinc-700 hover:to-zinc-800 text-white text-sm font-bold flex items-center gap-2 transition-all shadow-lg border border-zinc-700/50 hover:border-zinc-600">
                    <i class="fa-solid fa-sliders text-amber-400 text-base drop-shadow-md"></i>
                    <span>{{ __('messages.semester_promotion_settings') }}</span>
                </button>

                <!-- Cohort PDF Export Button -->
                <button onclick="exportCohortReport('pdf')" class="px-4 py-2.5 rounded-xl cw-inner-box hover:bg-zinc-200 dark:hover:bg-zinc-800 text-sm font-bold flex items-center gap-2 transition shadow-sm">
                    <i class="fa-solid fa-file-pdf text-red-500 text-base"></i>
                    <span class="cw-title">{{ __('messages.cohort_results_report_pdf') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ─────────────────────────── الشريط العلوي للفلاتر المتتالية ─────────────────────────── -->
    <div class="cw-card p-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-[var(--border-color)] mb-4">
            <div class="flex items-center gap-2 cw-title font-bold text-sm">
                <i class="fa-solid fa-sliders cw-accent-icon"></i>
                <span>{{ __('messages.cascading_academic_filters') }}</span>
            </div>

            <!-- View Mode Switcher -->
            <div class="inline-flex p-1 cw-inner-box">
                <button type="button" id="btnModeStudent" onclick="switchViewMode('student')" class="px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 bg-primary text-black shadow">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span>{{ __('messages.students_view_workstation') }}</span>
                </button>
                <button type="button" id="btnModeCourse" onclick="switchViewMode('course')" class="px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 cw-subtitle hover:cw-title">
                    <i class="fa-solid fa-book-open"></i>
                    <span>{{ __('messages.courses_view_perspective') }}</span>
                </button>
            </div>
        </div>

        <!-- Dropdowns Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- 1. Department Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-building-columns cw-accent-icon text-xs"></i>
                    <span>{{ __('messages.filter_dept_num') }}</span>
                </label>
                <select id="filterDepartment" onchange="onDepartmentChanged()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="all">{{ __('messages.all_departments_opt') }}</option>
                    @foreach($data['departments'] as $dept)
                        <option value="{{ $dept->department_id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Program/Branch Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-code-branch cw-accent-icon text-xs"></i>
                    <span>{{ __('messages.filter_prog_num') }}</span>
                </label>
                <select id="filterProgram" onchange="applyFilters()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="all">{{ __('messages.all_programs_opt') }}</option>
                    @foreach($data['programs'] as $prog)
                        <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id }}">{{ $prog->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Academic Year Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-days cw-accent-icon text-xs"></i>
                    <span>{{ __('messages.filter_year_num') }}</span>
                </label>
                <select id="filterYear" onchange="onYearChanged()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="both">{{ __('messages.both_years_opt') }}</option>
                    <option value="1">{{ __('messages.year_1_opt') }}</option>
                    <option value="2">{{ __('messages.year_2_opt') }}</option>
                </select>
            </div>

            <!-- 4. Semester Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group cw-accent-icon text-xs"></i>
                    <span>{{ __('messages.filter_semester_num') }}</span>
                </label>
                <select id="filterSemester" onchange="applyFilters()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="both">{{ __('messages.both_semesters_opt') }}</option>
                    <option value="1">{{ __('messages.semester_1_opt') }}</option>
                    <option value="2">{{ __('messages.semester_2_opt') }}</option>
                </select>
            </div>

            <!-- 5. Standing Filter -->
            <div>
                <label class="block text-xs font-bold cw-subtitle mb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-clipboard-check cw-accent-icon text-xs"></i>
                    <span>{{ __('messages.filter_standing_num') }}</span>
                </label>
                <select id="filterStanding" onchange="applyFilters()" class="w-full cw-select rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                    <option value="all">{{ __('messages.all_standings_opt') }}</option>
                    <option value="passed">{{ __('messages.passed_promoted_opt') }}</option>
                    <option value="failed">{{ __('messages.failed_opt') }}</option>
                    <option value="supplementary">{{ __('messages.supplementary_opt') }}</option>
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
                    <input type="text" id="studentSearchInput" oninput="onStudentSearch()" placeholder="{{ __('messages.search_student_pathway_placeholder') }}" class="w-full cw-input rounded-xl pr-10 pl-4 py-2 text-sm font-semibold">
                </div>
                <div class="flex items-center justify-between text-xs cw-subtitle px-1 font-semibold">
                    <span>{{ __('messages.matched_students') }}</span>
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
                        <span>{{ __('messages.prev_student') }}</span>
                    </button>
                    <span id="studentNavCounter" class="text-xs font-bold px-2 py-1 cw-inner-box">
                        0 {{ __('messages.of_count') }} 0
                    </span>
                    <button type="button" onclick="navigateStudent(1)" class="px-3.5 py-2 rounded-xl cw-inner-box hover:bg-zinc-200 dark:hover:bg-zinc-800 text-xs font-bold flex items-center gap-1.5 transition">
                        <span>{{ __('messages.next_student') }}</span>
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="exportStudentTranscript('pdf')" class="px-4 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold flex items-center gap-2 border border-red-500/30 transition shadow-sm">
                        <i class="fa-solid fa-file-pdf text-sm"></i>
                        <span>{{ __('messages.student_transcript_pdf') }}</span>
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
                                <span id="dossierStatusBadge" class="px-2.5 py-1 rounded-full text-xs font-bold status-badge-pass">{{ __('messages.passed_status') }}</span>
                            </div>
                            <div class="flex items-center gap-3 mt-1.5 text-xs cw-subtitle font-semibold">
                                <span>{{ __('messages.university_id_label') }} <b id="dossierStudentCode" class="cw-accent-badge px-2 py-0.5 rounded font-mono">-</b></span>
                                <span>•</span>
                                <span>{{ __('messages.joined_date_label') }} <span id="dossierJoinedAt" class="cw-title font-semibold">-</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="text-left">
                        <span class="text-xs cw-subtitle block">{{ __('messages.active_filter_scope') }}</span>
                        <span id="dossierScopeBadge" class="text-xs font-bold cw-title px-2.5 py-1 cw-inner-box mt-1 inline-block">
                            {{ __('messages.all_semesters_years_scope') }}
                        </span>
                    </div>
                </div>

                <!-- Detailed Dossier Metadata Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 pt-1 text-xs">
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">{{ __('messages.academic_department_col') }}</span>
                        <span id="dossierDept" class="cw-title font-bold text-sm">-</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">{{ __('messages.branch_specialization_col') }}</span>
                        <span id="dossierProgram" class="cw-title font-bold text-sm">-</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">{{ __('messages.academic_year_level_col') }}</span>
                        <span id="dossierLevel" class="cw-accent-text font-black text-sm">-</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">{{ __('messages.academic_advisor_col') }}</span>
                        <span id="dossierAdvisor" class="cw-title font-bold text-sm">-</span>
                    </div>
                </div>

                <div class="mt-3 text-xs cw-subtitle flex items-center gap-2">
                    <i class="fa-solid fa-id-badge cw-accent-icon"></i>
                    <span>{{ __('messages.dept_head_label') }} <b id="dossierHOD" class="cw-title">-</b></span>
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
                            <h3 class="font-black text-sm cw-title">{{ __('messages.courses_weights_semester_breakdown_title') }}</h3>
                            <p id="coursesScopeSubtitle" class="text-[11px] cw-subtitle mt-0.5">{{ __('messages.courses_scope_desc') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="displayedSemestersBadge" class="text-xs px-3 py-1 rounded-lg cw-inner-box cw-subtitle font-bold">
                            0 {{ __('messages.semesters') }}
                        </span>
                        <span id="coursesCountBadge" class="text-xs px-3 py-1 rounded-lg cw-accent-badge font-bold">
                            0 {{ __('messages.courses') }}
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
                                <h3 class="font-black text-sm cw-title">{{ __('messages.cum_gpa_summary_title') }}</h3>
                                <p id="cumFormulaExplanation" class="text-xs cw-subtitle mt-0.5 font-medium">
                                    {{ __('messages.cum_formula_desc') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-end md:self-auto">
                            <div class="text-left md:text-right">
                                <span class="text-xs cw-subtitle block font-semibold">{{ __('messages.cum_gpa_label') }}</span>
                                <span id="cumGpaDisplay" class="text-3xl font-black font-mono cw-accent-text tracking-tight">0.00%</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-xs">
                        <div class="cw-inner-box p-3 text-center">
                            <span class="cw-subtitle block mb-1">{{ __('messages.passed_semesters_stat') }}</span>
                            <span id="cumSemestersCount" class="cw-title font-bold text-base font-mono">0</span>
                        </div>
                        <div class="cw-inner-box p-3 text-center">
                            <span class="cw-subtitle block mb-1">{{ __('messages.total_credits_stat') }}</span>
                            <span id="cumTotalHours" class="cw-title font-bold text-base font-mono">0</span>
                        </div>
                        <div class="cw-inner-box p-3 text-center">
                            <span class="cw-subtitle block mb-1">{{ __('messages.total_courses_stat') }}</span>
                            <span id="cumTotalCoursesCount" class="cw-title font-bold text-base font-mono">0</span>
                        </div>
                        <div class="cw-inner-box p-3 text-center">
                            <span class="cw-subtitle block mb-1">{{ __('messages.courses_standing_stat') }}</span>
                            <span id="cumOverallStatus" class="font-bold text-sm text-emerald-600 dark:text-emerald-400">{{ __('messages.eligible_for_pass') }}</span>
                        </div>
                    </div>

                    <!-- إشعار المقررات الراسبة المتبقية إن وجدت -->
                    <div id="failedCoursesAlert" class="hidden mt-4 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 flex items-start gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-red-500 text-base mt-0.5"></i>
                        <div>
                            <div class="text-xs font-bold text-red-600 dark:text-red-400">{{ __('messages.unpassed_courses_title') }}</div>
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
                        <h3 class="text-base font-black cw-title">{{ __('messages.academic_decision_workstation') }}</h3>
                    </div>
                    <span class="text-xs cw-subtitle font-semibold">{{ __('messages.instant_notification_hint') }}</span>
                </div>

                <!-- Decision Actions Buttons -->
                <div class="space-y-4">
                    <div id="decisionButtonsContainer" class="flex flex-wrap items-center gap-3">
                        <!-- Rendered dynamically according to student level & standing -->
                    </div>

                    <!-- Administrative Remarks Input -->
                    <div>
                        <label class="block text-xs font-bold cw-subtitle mb-1.5">
                            {{ __('messages.admin_notes_label') }}
                        </label>
                        <textarea id="decisionNotesInput" rows="2" placeholder="{{ __('messages.admin_notes_placeholder') }}" class="w-full cw-input rounded-xl p-3 text-xs"></textarea>
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
                    <span>{{ __('messages.filtered_courses_list') }}</span>
                </div>
                <span id="matchedCoursesCount" class="text-xs px-2 py-0.5 rounded-md cw-accent-badge font-bold">0 {{ __('messages.courses') }}</span>
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
                        <span id="courseYearBadge" class="px-2.5 py-1 rounded-full text-xs font-bold cw-accent-badge">{{ __('messages.year_1_opt') }}</span>
                        <h2 id="courseTitleHeader" class="text-xl font-black cw-title mt-2">-</h2>
                        <div class="flex items-center gap-3 text-xs cw-subtitle mt-1 font-semibold">
                            <span>{{ __('messages.credit_weight_th') }}: <b id="courseWeightHeader" class="cw-accent-text font-mono">-</b></span>
                            <span>•</span>
                            <span>{{ __('messages.filter_semester_num') }}: <span id="courseSemesterHeader" class="cw-title">-</span></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="exportCourseReport('pdf')" class="px-4 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold flex items-center gap-2 border border-red-500/30 transition shadow-sm">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span>{{ __('messages.certified_course_report_pdf') }}</span>
                        </button>
                        <button type="button" onclick="exportCourseReport('excel')" class="px-3 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-1.5 border border-emerald-500/30 transition">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>{{ __('messages.excel_btn') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Bar -->
                <div class="grid grid-cols-3 gap-4 mt-5 text-center text-xs">
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">{{ __('messages.total_enrolled_students') }}</span>
                        <span id="courseTotalEnrolled" class="cw-title font-bold text-lg font-mono">0</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">{{ __('messages.pass_rate') }}</span>
                        <span id="coursePassRate" class="text-emerald-600 dark:text-emerald-400 font-bold text-lg font-mono">0%</span>
                    </div>
                    <div class="cw-inner-box p-3">
                        <span class="cw-subtitle block mb-1">{{ __('messages.failed_students') }}</span>
                        <span id="courseFailCount" class="text-red-600 dark:text-red-400 font-bold text-lg font-mono">0</span>
                    </div>
                </div>
            </div>

            <!-- جدول الطلاب المسجلين بالمادة -->
            <div class="cw-card overflow-hidden">
                <div class="p-4 border-b border-[var(--border-color)] flex items-center justify-between bg-[var(--bg-primary)]">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-users cw-accent-icon"></i>
                        <h3 class="font-black text-sm cw-title">{{ __('messages.enrolled_students_course_results') }}</h3>
                    </div>

                    <!-- Sub-filter by Pass/Fail -->
                    <div class="inline-flex p-0.5 cw-inner-box text-xs font-semibold">
                        <button type="button" onclick="filterCourseStudents('all')" id="btnCsFilterAll" class="px-3 py-1 rounded-md bg-[var(--bg-secondary)] text-[var(--text-primary)] font-bold shadow-sm border border-[var(--border-color)]">{{ __('messages.filter_all') }}</button>
                        <button type="button" onclick="filterCourseStudents('pass')" id="btnCsFilterPass" class="px-3 py-1 rounded-md text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('messages.passed_status') }}</button>
                        <button type="button" onclick="filterCourseStudents('fail')" id="btnCsFilterFail" class="px-3 py-1 rounded-md text-[var(--text-secondary)] hover:text-[var(--text-primary)]">{{ __('messages.failed_status') }}</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs cw-table">
                        <thead>
                            <tr class="font-bold">
                                <th class="py-3 px-4">{{ __('messages.university_id') }}</th>
                                <th class="py-3 px-4">{{ __('messages.student_name') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('messages.quiz_th') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('messages.oral_th') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('messages.exam_th') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('messages.course_mark_th') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('messages.credit_weight_th') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('messages.status') }}</th>
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
<!-- Premium Academic Settings Modal -->
<div id="premiumSettingsModal" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <!-- Glassmorphism Background overlay -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity" aria-hidden="true" onclick="document.getElementById('premiumSettingsModal').classList.add('hidden')"></div>

        <!-- Modal panel -->
        <div class="relative inline-block w-full max-w-3xl text-right align-bottom transition-all transform bg-[#18181b] rounded-[24px] shadow-2xl sm:my-8 sm:align-middle border border-zinc-700/50 overflow-hidden ring-1 ring-white/10" style="text-align: {{ app()->getLocale() === 'en' ? 'left' : 'right' }};">
            
            <!-- Header with Gradient Line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 via-orange-500 to-red-500"></div>
            <div class="px-8 py-6 flex justify-between items-center bg-[#18181b]/90 border-b border-zinc-800/80">
                <h3 class="text-xl font-black text-white flex items-center gap-3 tracking-wide" id="modal-title">
                    <div class="w-10 h-10 rounded-full bg-amber-500/10 flex items-center justify-center border border-amber-500/20 shadow-inner">
                        <i class="fa-solid fa-sliders text-amber-400 text-lg"></i>
                    </div>
                    {{ __('messages.academic_settings_center') }}
                </h3>
                <button type="button" class="text-zinc-400 hover:text-white hover:bg-zinc-800 p-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-zinc-600" onclick="document.getElementById('premiumSettingsModal').classList.add('hidden')">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="px-8 py-8 space-y-8 bg-[#121214] max-h-[75vh] overflow-y-auto custom-scrollbar">
                
                <!-- Section 1: Semesters -->
                <div class="bg-[#1c1c1e] rounded-2xl p-6 border border-zinc-800/80 shadow-inner relative overflow-hidden group hover:border-zinc-700/80 transition-colors">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                    
                    <div class="flex items-center gap-3 mb-6">
                        <i class="fa-solid fa-calendar-check text-emerald-400 text-lg"></i>
                        <h4 class="text-lg font-bold text-white">{{ __('messages.semesters_management_activation') }}</h4>
                    </div>
                    
                    <form action="{{ route('affairs.semester.activate') }}" method="POST" class="space-y-5 relative z-10">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-400 mb-2">{{ __('messages.target_semester_activation') }}</label>
                                <select name="semester_name" required class="w-full bg-zinc-900/80 border border-zinc-700/60 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all outline-none">
                                    <option value="الفصل الأول" {{ (($activeSemester->name ?? $activeSemester->semester_name ?? '') == 'الفصل الأول') ? 'selected' : '' }}>{{ __('messages.semester_1_opt') }}</option>
                                    <option value="الفصل الثاني" {{ (($activeSemester->name ?? $activeSemester->semester_name ?? '') == 'الفصل الثاني') ? 'selected' : '' }}>{{ __('messages.semester_2_opt') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-zinc-400 mb-2">{{ __('messages.semester_start_date') }}</label>
                                <input type="date" name="start_date" value="{{ $activeSemester->start_date ?? '' }}" required class="w-full bg-zinc-900/80 border border-zinc-700/60 rounded-xl px-4 py-3 text-sm text-zinc-300 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all outline-none" style="color-scheme: dark;">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-zinc-400 mb-2">{{ __('messages.semester_end_date') }}</label>
                                <input type="date" name="end_date" value="{{ $activeSemester->end_date ?? '' }}" required class="w-full bg-zinc-900/80 border border-zinc-700/60 rounded-xl px-4 py-3 text-sm text-zinc-300 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all outline-none" style="color-scheme: dark;">
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 font-bold rounded-xl text-sm transition-all flex items-center gap-2">
                                <i class="fa-solid fa-check-double"></i>
                                {{ __('messages.activate_semester_submit') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section 2: Promotion -->
                <div class="bg-[#1c1c1e] rounded-2xl p-6 border border-zinc-800/80 shadow-inner relative overflow-hidden group hover:border-zinc-700/80 transition-colors">
                    <div class="absolute top-0 left-0 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl -ml-16 -mt-16 pointer-events-none"></div>
                    
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fa-solid fa-users-gear text-blue-400 text-lg"></i>
                        <h4 class="text-lg font-bold text-white">{{ __('messages.cohort_bulk_promotion') }}</h4>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-zinc-800 text-zinc-400 border border-zinc-700 ml-auto">{{ __('messages.annual_action') }}</span>
                    </div>
                    <p class="text-xs text-zinc-400 mb-6 leading-relaxed">
                        {{ __('messages.cohort_promotion_desc', ['count' => $firstYearCount ?? 0]) }}
                    </p>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-xl bg-zinc-900/50 border border-zinc-800/50 relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center border border-blue-500/20">
                                <i class="fa-solid fa-users text-blue-400 text-xl"></i>
                            </div>
                            <div>
                                <div class="text-xs text-zinc-500 font-semibold">{{ __('messages.currently_eligible_promotion') }}</div>
                                <div class="text-lg font-black text-white">{{ $firstYearCount ?? 0 }} <span class="text-xs font-normal text-zinc-400">{{ __('messages.students_count_unit') }}</span></div>
                            </div>
                        </div>
                        
                        <form action="{{ route('affairs.promote.year2') }}" method="POST" onsubmit="return confirm('{{ __('messages.confirm_decision_prompt') }}');">
                            @csrf
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl text-sm transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40">
                                <i class="fa-solid fa-graduation-cap"></i>
                                {{ __('messages.execute_promotion_year_1') }}
                            </button>
                        </form>
                    </div>
                </div>

            </div>
            
            <!-- Footer -->
            <div class="px-6 py-4 bg-[#18181b] border-t border-zinc-800/80 flex justify-end">
                <button type="button" class="px-6 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white rounded-xl font-bold transition-all border border-zinc-700" onclick="document.getElementById('premiumSettingsModal').classList.add('hidden')">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ─────────────────────────── Localization Dictionary ───────────────────────────
    const currentLocale = '{{ app()->getLocale() }}';
    const isRTL = currentLocale === 'ar';

    const i18n = {
        of: '{{ __("messages.of_count") }}',
        year_1: '{{ __("messages.year_1_opt") }}',
        year_2: '{{ __("messages.year_2_opt") }}',
        semester_1: '{{ __("messages.semester_1_opt") }}',
        semester_2: '{{ __("messages.semester_2_opt") }}',
        sem_3_label: currentLocale === 'en' ? 'Semester 3' : 'الفصل الثالث',
        sem_4_label: currentLocale === 'en' ? 'Semester 4' : 'الفصل الرابع',
        year_2_sem_1: currentLocale === 'en' ? 'Year 2 (Sem 1)' : 'السنة الثانية (فصل 1)',
        year_2_sem_2: currentLocale === 'en' ? 'Year 2 (Sem 2)' : 'السنة الثانية (فصل 2)',
        sem_1_year_1_full: currentLocale === 'en' ? 'Semester 1 • First Year' : 'الفصل الأول • السنة الأولى',
        sem_2_year_1_full: currentLocale === 'en' ? 'Semester 2 • First Year' : 'الفصل الثاني • السنة الأولى',
        sem_3_year_2_full: currentLocale === 'en' ? 'Semester 3 • Second Year (Semester 1)' : 'الفصل الثالث • السنة الثانية (الفصل الأول)',
        sem_4_year_2_full: currentLocale === 'en' ? 'Semester 4 • Second Year (Semester 2)' : 'الفصل الرابع • السنة الثانية (الفصل الثاني)',
        passed: '{{ __("messages.passed_status") }}',
        failed: '{{ __("messages.failed_status") }}',
        passed_prev_year: '{{ __("messages.passed_prev_year") }}',
        locked_sem1: '{{ __("messages.locked_course_sem1_done") }}',
        locked_final: '{{ __("messages.locked_course_final_sem") }}',
        currently_active_sem: '{{ __("messages.currently_active_sem") }}',
        starts_later: '{{ __("messages.starts_later") }}',
        passed_prev_year_full: currentLocale === 'en' ? 'Passed (Previous Academic Year 2024/2025)' : 'مجتاز (العام الدراسي الماضي 2024/2025)',
        passed_successfully: currentLocale === 'en' ? 'Passed Successfully' : 'مجتاز بنجاح',
        no_code: currentLocale === 'en' ? 'No ID' : 'بدون رقم',
        sem_gpa_label: '{{ __("messages.sem_gpa_label") }}',
        sem_total_hours_prefix: currentLocale === 'en' ? 'Total Hours for' : 'مجموع وساعات',
        hours_weight: '{{ __("messages.total_credits_stat") }}',
        courses_count_label: '{{ __("messages.total_courses_stat") }}',
        plan_courses_subtitle: currentLocale === 'en' ? 'Approved plan courses for' : 'مقررات',
        semesters_displayed: currentLocale === 'en' ? 'Semesters Displayed' : 'فصول معروضة',
        courses_unit: currentLocale === 'en' ? 'Courses' : 'مقرر',
        courses_unit_single: currentLocale === 'en' ? 'course' : 'مادة',
        students_unit: currentLocale === 'en' ? 'Students' : 'طلاب',
        student_unit_single: currentLocale === 'en' ? 'student' : 'طالب',
        passed_semesters_unit: currentLocale === 'en' ? 'Passed Semesters' : 'فصول مجتازة',
        eligible_for_pass: '{{ __("messages.eligible_for_pass") }}',
        failed_in_courses: currentLocale === 'en' ? 'Failed in :count Courses ⚠️' : 'راسب في :count مواد ⚠️',
        pending_results: '{{ __("messages.pending_results_completion") }}',
        no_matching_students: '{{ __("messages.no_matching_students") }}',
        no_matching_courses: currentLocale === 'en' ? 'No courses match current filter' : 'لا توجد مواد مطابقة لمعايير الفلترة',
        no_student_selected: '{{ __("messages.no_selected_students") }}',
        select_student_prompt: '{{ __("messages.please_select_student_sidebar") }}',
        select_student_decision: currentLocale === 'en' ? 'Select a student to take an academic decision' : 'اختر طالباً لاتخاذ القرار الأكاديمي',
        no_courses_to_display: currentLocale === 'en' ? 'No course data to display' : 'لا توجد بيانات مقررات معروضة',
        no_courses_matching_filter: currentLocale === 'en' ? 'No registered courses match the selected filters' : 'لا توجد مقررات مسجلة لهذا الطالب مطابقة للفلاتر المحددة',
        adjust_filter_prompt: currentLocale === 'en' ? 'Please adjust academic year or semester filters above' : 'يرجى تعديل محددات السنة الدراسية أو الفصل من شريط التصفية بالأعلى',
        no_students_in_course: '{{ __("messages.no_students_enrolled_course") }}',
        confirm_decision: '{{ __("messages.confirm_decision_prompt") }}',
        saving_decision: '{{ __("messages.saving_decision_sending") }}',
        error_occurred: '{{ __("messages.error") }}',
        connection_failed: currentLocale === 'en' ? 'Failed to connect to server.' : 'فشل الاتصال بالخادم.',
        select_student_first: currentLocale === 'en' ? 'Please select a student first' : 'يرجى اختيار طالب أولاً',
        select_course_first: currentLocale === 'en' ? 'Please select a course first' : 'يرجى اختيار مادة أولاً',
        scope_prefix: currentLocale === 'en' ? 'Active Scope:' : 'نطاق العرض النشط:',
        scope_suffix: currentLocale === 'en' ? '(Separated with exact credits & GPA)' : '(مفصولة بهوامش ومعدلات دقيقة)',
        all_years_scope: currentLocale === 'en' ? 'All Years (Year 1 & 2) • Semesters 1 to 4' : 'كافة السنوات (الأولى والثانية) • الفصول 1 إلى 4',
        all_years_sem1_scope: currentLocale === 'en' ? 'All Years • Semester 1 & 3' : 'كافة السنوات • الفصل الأول والثالث',
        all_years_sem2_scope: currentLocale === 'en' ? 'All Years • Semester 2 & 4' : 'كافة السنوات • الفصل الثاني والرابع',
        year1_all_sem: currentLocale === 'en' ? 'First Year • Semester 1 & 2' : 'السنة الأولى • الفصل الأول والثاني',
        year1_sem1: currentLocale === 'en' ? 'First Year • Semester 1' : 'السنة الأولى • الفصل الأول',
        year1_sem2: currentLocale === 'en' ? 'First Year • Semester 2' : 'السنة الأولى • الفصل الثاني',
        year2_all_sem: currentLocale === 'en' ? 'Second Year • Semester 3 & 4' : 'السنة الثانية • الفصل الثالث والرابع',
        year2_sem1: currentLocale === 'en' ? 'Second Year • Semester 3 (Semester 1)' : 'السنة الثانية • الفصل الثالث (الفصل الأول)',
        year2_sem2: currentLocale === 'en' ? 'Second Year • Semester 4 (Semester 2)' : 'السنة الثانية • الفصل الرابع (الفصل الثاني)',
        year1_no_year2_sem: currentLocale === 'en' ? 'First Year (No Year 2 Semesters)' : 'السنة الأولى (لا توجد فصول سنة ثانية)',
        formula_year2: currentLocale === 'en'
            ? 'Approved Formula (Year 2 Student): Sum of completed passed semesters (2 from last year + current active semester) ÷ Passed semesters (3) = '
            : 'المعادلة المعتمدة (طالب سنة ثانية): مجموع الفصول المنتهية المجتازة (فصلان من العام الماضي + الفصل الساري) ÷ عدد الفصول المجتازة (3 فصول) = ',
        formula_multi_sem: currentLocale === 'en'
            ? 'Approved Formula: Sum of completed passed semesters ÷ Number of passed semesters (:count semesters) = '
            : 'المعادلة المعتمدة: مجموع الفصول المنتهية المجتازة ÷ عدد الفصول المجتازة (:count فصول) = ',
        formula_single_sem: currentLocale === 'en'
            ? 'Approved Formula: Sum of completed passed semesters ÷ 1 = '
            : 'المعادلة المعتمدة: مجموع الفصول المنتهية المجتازة على عددها فقط = ',
        formula_none_passed: currentLocale === 'en'
            ? 'Approved Formula: No semester passed with GPA ≥ 50% (Current GPA: :gpa)'
            : 'المعادلة المعتمدة: لم يتم اجتياز أي فصل بمعدل ≥ 50% (المعدل الحالي: :gpa)',
        formula_no_semesters: currentLocale === 'en'
            ? 'No completed passed semesters currently under selected filters and student level'
            : 'لا توجد فصول دراسية منتهية مجتازة حالياً وفق خيارات الفلترة ومستوى الطالب',
        // Decision buttons
        btn_promote_y2: '{{ __("messages.promote_year_2_btn") }}',
        btn_repeat_y1_failed: '{{ __("messages.repeat_year_1_btn") }}',
        btn_supp: currentLocale === 'en' ? 'Transfer to Supplementary Session (:count courses)' : 'تحويل للدورة التكميلية (حصر المواد: :count)',
        btn_supp_sem1: currentLocale === 'en' ? 'Transfer to Supplementary Session (Sem 1: :count courses)' : 'تحويل للدورة التكميلية (فصل أول: :count مواد)',
        btn_supp_sem3: currentLocale === 'en' ? 'Transfer to Supplementary Session (Sem 3: :count courses)' : 'تحويل للدورة التكميلية (فصل ثالث: :count مواد)',
        btn_supp_grad: currentLocale === 'en' ? 'Transfer to Graduate Supplementary Session (:count courses)' : 'تحويل لدورة الخريجين التكميلية (:count مواد)',
        btn_repeat_y1: currentLocale === 'en' ? 'Repeat First Year' : 'إعادة السنة الأولى',
        btn_repeat_y1_all: currentLocale === 'en' ? 'Year 1 Failed (Repeat Full Year)' : 'راسب سنة أولى (إعادة السنة كاملة)',
        btn_promote_sem2: '{{ __("messages.promote_semester_2_btn") }}',
        btn_repeat_sem1: currentLocale === 'en' ? 'Failed Semester 1 (Repeat)' : 'راسب فصل أول (إعادة)',
        btn_repeat_sem1_year: currentLocale === 'en' ? 'Failed Semester 1 (Repeat Year)' : 'راسب فصل أول (إعادة السنة)',
        btn_graduate: '{{ __("messages.graduate_btn") }}',
        btn_repeat_y2: '{{ __("messages.repeat_year_2_btn") }}',
        btn_repeat_y2_all: currentLocale === 'en' ? 'Year 2 Failed (Remain in Year 2)' : 'راسب سنة ثانية (بقاء في السنة الثانية)',
        btn_promote_sem4: '{{ __("messages.promote_semester_4_btn") }}',
        btn_repeat_sem3: currentLocale === 'en' ? 'Failed Semester 3 (Remain in Year 2)' : 'راسب فصل ثالث (بقاء في السنة الثانية)',
        grad_status_badge: '{{ __("messages.graduated_status") }}',
        supp_status_badge: '{{ __("messages.supplementary_status") }}',
        fail_status_badge: '{{ __("messages.repeat_year_status") }}',
        pass_status_badge: '{{ __("messages.promoted_status") }}',
    };

    // ─────────────────────────── Application Data & State ───────────────────────────
    const appData = @json($data);
    let currentMode = 'student'; // 'student' or 'course'
    let filteredStudents = [];
    let currentStudentIndex = 0;
    let filteredCourses = [];
    let currentCourseId = null;
    let courseSubFilter = 'all';

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const paramStudentId = urlParams.get('student_id');
        const paramSearch = urlParams.get('search') || urlParams.get('student_code');

        if (paramStudentId) {
            const targetStudent = (appData.studentsList || []).find(s => String(s.student_id) === String(paramStudentId));
            if (targetStudent) {
                if (targetStudent.department_id) {
                    const deptSelect = document.getElementById('filterDepartment');
                    if (deptSelect) {
                        deptSelect.value = targetStudent.department_id;
                        onDepartmentChanged();
                    }
                }
                if (targetStudent.program_id) {
                    const progSelect = document.getElementById('filterProgram');
                    if (progSelect) progSelect.value = targetStudent.program_id;
                }
            }
        }

        if (paramSearch) {
            const searchInput = document.getElementById('studentSearchInput');
            if (searchInput) searchInput.value = paramSearch;
        }

        applyFilters();

        if (paramStudentId) {
            const targetIdx = filteredStudents.findIndex(s => String(s.student_id) === String(paramStudentId));
            if (targetIdx !== -1) {
                currentStudentIndex = targetIdx;
                renderStudentWorkstation();
            }
        }
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
            
            const levelStr = String(s.level || '').trim().toLowerCase();
            const isYear1 = levelStr.includes('الأولى') || levelStr === '1' || levelStr.includes('first');
            const isYear2 = levelStr.includes('الثانية') || levelStr.includes('خريج') || levelStr.includes('تكميل') || levelStr === '2' || levelStr.includes('second') || levelStr.includes('graduate');

            if (year === '1' && !isYear1) return false;
            if (year === '2' && !isYear2) return false;

            if (standing !== 'all') {
                const sStanding = s.summary?.standing || 'passed';
                const hasFailed = (s.summary?.failed_count ?? 0) > 0 || (s.summary?.failed_courses?.length ?? 0) > 0;

                if (standing === 'passed') {
                    if (hasFailed || !['passed', 'graduated'].includes(sStanding)) return false;
                }
                if (standing === 'failed') {
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
                    ${i18n.no_matching_students}
                </div>
            `;
            clearStudentDossier();
            return;
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
                        <div class="text-[11px] cw-subtitle font-mono mt-0.5">${s.student_code || i18n.no_code} • ${s.level}</div>
                    </div>
                </div>
                ${gpaBadge}
            `;
            scrollContainer.appendChild(item);
        });

        if (currentStudentIndex >= 0 && currentStudentIndex < displayList.length) {
            const activeStudent = displayList[currentStudentIndex];
            renderActiveStudent(activeStudent, currentStudentIndex + 1, displayList.length);
        } else {
            clearStudentDossier();
            document.getElementById('decisionButtonsContainer').innerHTML = `<span class="text-xs cw-subtitle">${i18n.select_student_prompt}</span>`;
        }
    }

    function onStudentSearch() {
        currentStudentIndex = -1;
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

        if (currentStudentIndex === -1) {
            currentStudentIndex = 0;
        } else {
            currentStudentIndex += delta;
            if (currentStudentIndex < 0) currentStudentIndex = displayList.length - 1;
            if (currentStudentIndex >= displayList.length) currentStudentIndex = 0;
        }

        renderStudentWorkstation();
    }

    // ─────────────────────────── Canonical Semesters Array ───────────────────────────
    const CANONICAL_SEMESTERS = [
        { key: '1_1', year: 1, semester_id: 1, num: 1, title: i18n.semester_1, yearLabel: i18n.year_1, fullTitle: i18n.sem_1_year_1_full },
        { key: '1_2', year: 1, semester_id: 2, num: 2, title: i18n.semester_2, yearLabel: i18n.year_1, fullTitle: i18n.sem_2_year_1_full },
        { key: '2_1', year: 2, semester_id: 1, num: 3, title: i18n.sem_3_label, yearLabel: i18n.year_2_sem_1, fullTitle: i18n.sem_3_year_2_full },
        { key: '2_2', year: 2, semester_id: 2, num: 4, title: i18n.sem_4_label, yearLabel: i18n.year_2_sem_2, fullTitle: i18n.sem_4_year_2_full },
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
        const levelStr = String(s.level || '').toLowerCase();
        const isYear1 = levelStr.includes('الأولى') || levelStr.includes('first') || levelStr === '1';

        CANONICAL_SEMESTERS.forEach(semDef => {
            if (isYear1 && semDef.year > 1) return;

            if (yearFilter !== 'both' && String(semDef.year) !== String(yearFilter)) return;
            if (semFilter !== 'both' && String(semDef.semester_id) !== String(semFilter)) return;

            const coursesInSem = allCourses.filter(c => 
                Number(c.year) === semDef.year && Number(c.semester_id) === semDef.semester_id
            );

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
                            status: isPass ? i18n.passed : i18n.failed,
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
                            status: i18n.passed_prev_year,
                            isPass: true
                        };
                    } else {
                        const closedText = isYear1 ? i18n.locked_sem1 : i18n.locked_final;
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
                    isSemPassed = true;
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

        const visiblePassedCount = semGroups.filter(g => g.isPass === true).length;
        const totalInstitutionalPassed = isYear1 ? visiblePassedCount : (2 + passedSemGpas.length);
        const sumPassedGpas = passedSemGpas.reduce((acc, cur) => acc + cur.gpa, 0);

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
        document.getElementById('studentNavCounter').innerText = `${pos} ${i18n.of} ${total}`;

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
        document.getElementById('dossierLevel').innerText = s.level || i18n.year_1;
        document.getElementById('dossierAdvisor').innerText = s.advisor_name || (currentLocale === 'en' ? 'Mr. Ahmed Nasli (Mentor)' : 'أ. أحمد نصلى (المربي)');
        document.getElementById('dossierHOD').innerText = s.hod_name || (currentLocale === 'en' ? 'Dr. Ahmed Deeb (Head of Dept.)' : 'د. أحمد ديب (رئيس القسم)');

        // تحديد السنة
        const levelStr = String(s.level || '').toLowerCase();
        const isYear1 = levelStr.includes('الأولى') || levelStr.includes('first') || levelStr === '1';

        // نطاق الفلترة النشط
        const yVal = document.getElementById('filterYear').value;
        const sVal = document.getElementById('filterSemester').value;
        let scopeText = '';
        if (yVal === 'both' && sVal === 'both') {
            scopeText = isYear1 ? i18n.year1_all_sem : i18n.all_years_scope;
        } else if (yVal === '1' && sVal === 'both') {
            scopeText = i18n.year1_all_sem;
        } else if (yVal === '1' && sVal === '1') {
            scopeText = i18n.year1_sem1;
        } else if (yVal === '1' && sVal === '2') {
            scopeText = i18n.year1_sem2;
        } else if (yVal === '2' && sVal === 'both') {
            scopeText = isYear1 ? i18n.year1_no_year2_sem : i18n.year2_all_sem;
        } else if (yVal === '2' && sVal === '1') {
            scopeText = isYear1 ? i18n.year1_no_year2_sem : i18n.year2_sem1;
        } else if (yVal === '2' && sVal === '2') {
            scopeText = isYear1 ? i18n.year1_no_year2_sem : i18n.year2_sem2;
        } else if (yVal === 'both' && sVal === '1') {
            scopeText = isYear1 ? i18n.year1_sem1 : i18n.all_years_sem1_scope;
        } else if (yVal === 'both' && sVal === '2') {
            scopeText = isYear1 ? i18n.year1_sem2 : i18n.all_years_sem2_scope;
        }
        document.getElementById('dossierScopeBadge').innerText = scopeText;
        document.getElementById('coursesScopeSubtitle').innerText = `${i18n.scope_prefix} ${scopeText} ${i18n.scope_suffix}`;

        // Standing Badge
        const standing = s.summary?.standing || 'passed';
        const badge = document.getElementById('dossierStatusBadge');
        if (standing === 'graduated') {
            badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold status-badge-grad';
            badge.innerText = i18n.grad_status_badge;
        } else if (standing === 'supplementary') {
            badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold status-badge-supp';
            badge.innerText = i18n.supp_status_badge;
        } else if (standing === 'failed') {
            badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold status-badge-fail';
            badge.innerText = i18n.fail_status_badge;
        } else {
            badge.className = 'px-2.5 py-1 rounded-full text-xs font-bold status-badge-pass';
            badge.innerText = i18n.pass_status_badge;
        }

        // احتساب المقررات مقسمة وفق الفصول مع معدل كل فصل والتراكمي
        const data = calculateStudentData(s, yVal, sVal);

        // Header Badges
        document.getElementById('displayedSemestersBadge').innerText = `${data.semGroups.length} ${i18n.semesters_displayed}`;
        document.getElementById('coursesCountBadge').innerText = `${data.totalCoursesCount} ${i18n.courses_unit}`;

        // Render Semesters Container
        const container = document.getElementById('studentSemestersContainer');
        container.innerHTML = '';

        if (data.semGroups.length === 0) {
            container.innerHTML = `
                <div class="cw-card p-10 text-center">
                    <i class="fa-solid fa-folder-open text-3xl cw-subtitle opacity-40 mb-3 block"></i>
                    <div class="cw-title font-bold text-sm">${i18n.no_courses_matching_filter}</div>
                    <div class="cw-subtitle text-xs mt-1">${i18n.adjust_filter_prompt}</div>
                </div>
            `;
        } else {
            data.semGroups.forEach(group => {
                const semDef = group.semDef;
                const card = document.createElement('div');
                card.className = 'cw-card overflow-hidden shadow-sm transition hover:shadow-md border border-[var(--border-color)]';

                let semBadgeHtml = '';
                if (group.isClosed) {
                    const lockMsg = isYear1 ? i18n.locked_sem1 : i18n.locked_final;
                    semBadgeHtml = `<span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30 flex items-center gap-1.5 shadow-sm"><i class="fa-solid fa-lock text-[10px]"></i> ${lockMsg}</span>`;
                } else if (group.isCurrentActive) {
                    semBadgeHtml = `<span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-[10px]"></i> ${i18n.currently_active_sem}</span>`;
                } else if (group.isPastCompleted) {
                    semBadgeHtml = `<span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-500/15 text-blue-700 dark:text-blue-400 border border-blue-500/30 flex items-center gap-1.5"><i class="fa-solid fa-clock-rotate-left text-[10px]"></i> ${i18n.passed_prev_year_full}</span>`;
                }

                let gpaHeaderBadgeHtml = '';
                if (group.isCurrentActive) {
                    gpaHeaderBadgeHtml = `
                        <div class="px-3.5 py-1.5 rounded-xl bg-amber-500/15 dark:bg-[var(--accent-color)]/20 border border-amber-500/30 dark:border-[var(--accent-color)]/40 text-xs font-bold flex items-center gap-2">
                            <span class="text-amber-800 dark:text-amber-200">${i18n.sem_gpa_label}</span>
                            <span class="font-mono font-black text-sm text-amber-700 dark:text-[var(--accent-color)]">${group.gpa.toFixed(2)}%</span>
                        </div>
                    `;
                } else if (group.isPastCompleted) {
                    gpaHeaderBadgeHtml = `
                        <div class="px-3.5 py-1.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs font-bold flex items-center gap-2">
                            <span class="text-blue-700 dark:text-blue-300">${i18n.sem_gpa_label}</span>
                            <span class="font-bold text-xs text-blue-600 dark:text-blue-400">${i18n.passed_successfully}</span>
                        </div>
                    `;
                } else {
                    gpaHeaderBadgeHtml = `
                        <div class="px-3.5 py-1.5 rounded-xl cw-inner-box text-xs font-bold flex items-center gap-2">
                            <span class="cw-subtitle">${i18n.sem_gpa_label}</span>
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
                                <p class="text-[11px] cw-subtitle mt-0.5">${i18n.plan_courses_subtitle} ${semDef.title} (${semDef.yearLabel})</p>
                            </div>
                        </div>

                        <!-- Quick Badges -->
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="px-3 py-1.5 rounded-xl cw-inner-box text-xs font-semibold flex items-center gap-2">
                                <span class="cw-subtitle">${i18n.hours_weight}:</span>
                                <span class="font-mono font-bold cw-title">${group.hours}</span>
                            </div>
                            <div class="px-3 py-1.5 rounded-xl cw-inner-box text-xs font-semibold flex items-center gap-2">
                                <span class="cw-subtitle">${i18n.courses_count_label}:</span>
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
                        const lockText = isYear1 ? i18n.locked_sem1 : i18n.locked_final;
                        statusBadge = `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">${lockText}</span>`;
                    } else if (group.isPastCompleted) {
                        statusBadge = `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20">${i18n.passed_prev_year}</span>`;
                    } else if (c.isPass) {
                        statusBadge = `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold status-badge-pass">${i18n.passed}</span>`;
                    } else {
                        statusBadge = `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold status-badge-fail">${i18n.failed}</span>`;
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
                            ${i18n.sem_gpa_label} ${group.gpa.toFixed(2)}%
                        </span>
                    `;
                } else if (group.isPastCompleted) {
                    footerGpaHtml = `
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono text-blue-700 dark:text-blue-300 bg-blue-500/10">
                            ${i18n.passed_prev_year}
                        </span>
                    `;
                } else {
                    footerGpaHtml = `
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono cw-subtitle bg-black/5 dark:bg-white/5">
                            ${i18n.starts_later}
                        </span>
                    `;
                }

                const tableHtml = `
                    <div class="overflow-x-auto">
                        <table class="w-full text-start text-xs cw-table">
                            <thead>
                                <tr class="font-bold border-b border-[var(--border-color)]">
                                    <th class="py-3 px-4">{{ __('messages.course_name_th') }}</th>
                                    <th class="py-3 px-4 text-center">{{ __('messages.quiz_th') }}</th>
                                    <th class="py-3 px-4 text-center">{{ __('messages.oral_th') }}</th>
                                    <th class="py-3 px-4 text-center">{{ __('messages.exam_th') }}</th>
                                    <th class="py-3 px-4 text-center">{{ __('messages.course_mark_th') }}</th>
                                    <th class="py-3 px-4 text-center">{{ __('messages.credit_weight_th') }}</th>
                                    <th class="py-3 px-4 text-center">{{ __('messages.academic_status_th') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border-color)] font-semibold">
                                ${rowsHtml}
                            </tbody>
                            <tfoot class="font-bold text-xs bg-[var(--bg-primary)]">
                                <tr>
                                    <td class="py-3.5 px-4 cw-title font-bold" colspan="5">${i18n.sem_total_hours_prefix} ${semDef.title}:</td>
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
        document.getElementById('cumSemestersCount').innerText = `${data.passedSemestersCount} ${i18n.passed_semesters_unit}`;
        document.getElementById('cumTotalHours').innerText = data.cumHours;
        const totalCoursesEl = document.getElementById('cumTotalCoursesCount');
        if (totalCoursesEl) totalCoursesEl.innerText = data.totalCoursesCount;

        const cumStatusEl = document.getElementById('cumOverallStatus');
        if (data.failedTitles.length === 0 && data.passedSemestersCount > 0) {
            cumStatusEl.className = 'font-bold text-sm text-emerald-600 dark:text-emerald-400';
            cumStatusEl.innerText = i18n.eligible_for_pass;
        } else if (data.failedTitles.length > 0) {
            cumStatusEl.className = 'font-bold text-sm text-red-600 dark:text-red-400';
            cumStatusEl.innerText = i18n.failed_in_courses.replace(':count', data.failedTitles.length);
        } else {
            cumStatusEl.className = 'font-bold text-sm text-amber-600 dark:text-amber-400';
            cumStatusEl.innerText = i18n.pending_results;
        }

        const formulaEl = document.getElementById('cumFormulaExplanation');
        if (!isYear1) {
            const activeGpaStr = data.passedSemGpas.length > 0 ? data.passedSemGpas[0].gpa.toFixed(2) + '%' : '0.00%';
            formulaEl.innerHTML = `${i18n.formula_year2}<span class="cw-title font-mono font-bold">${activeGpaStr}</span> ÷ 3 = <span class="cw-accent-text font-mono font-black text-sm">${data.cumGpa.toFixed(2)}%</span>`;
        } else if (data.passedSemestersCount > 1) {
            const sumStr = data.passedSemGpas.length > 0 
                ? data.passedSemGpas.map(item => `<span class="cw-title font-mono font-bold">${item.gpa.toFixed(2)}%</span>`).join(' + ')
                : '0.00%';
            const baseTpl = i18n.formula_multi_sem.replace(':count', data.passedSemestersCount);
            formulaEl.innerHTML = `${baseTpl}${sumStr} ÷ ${data.passedSemestersCount} = <span class="cw-accent-text font-mono font-black text-sm">${data.cumGpa.toFixed(2)}%</span>`;
        } else if (data.passedSemestersCount === 1) {
            const valStr = data.passedSemGpas.length > 0 ? data.passedSemGpas[0].gpa.toFixed(2) + '%' : '0.00%';
            formulaEl.innerHTML = `${i18n.formula_single_sem}<span class="cw-title font-mono font-bold">${valStr}</span> ÷ 1 = <span class="cw-accent-text font-mono font-black text-sm">${data.cumGpa.toFixed(2)}%</span>`;
        } else if (data.allSemGpas.length > 0) {
            const gpaSpan = `<span class="text-red-500 font-mono font-bold">${data.cumGpa.toFixed(2)}%</span>`;
            formulaEl.innerHTML = i18n.formula_none_passed.replace(':gpa', gpaSpan);
        } else {
            formulaEl.innerText = i18n.formula_no_semesters;
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
        document.getElementById('dossierFullName').innerText = i18n.no_student_selected;
        document.getElementById('dossierStudentCode').innerText = '-';
        document.getElementById('dossierJoinedAt').innerText = '-';
        document.getElementById('dossierDept').innerText = '-';
        document.getElementById('dossierProgram').innerText = '-';
        document.getElementById('dossierLevel').innerText = '-';
        document.getElementById('dossierAdvisor').innerText = '-';
        document.getElementById('dossierHOD').innerText = '-';
        document.getElementById('displayedSemestersBadge').innerText = `0 ${i18n.semesters_displayed}`;
        document.getElementById('coursesCountBadge').innerText = `0 ${i18n.courses_unit}`;
        document.getElementById('studentSemestersContainer').innerHTML = `
            <div class="cw-card p-10 text-center">
                <i class="fa-solid fa-folder-open text-3xl cw-subtitle opacity-40 mb-3 block"></i>
                <div class="cw-title font-bold text-sm">${i18n.no_courses_to_display}</div>
                <div class="cw-subtitle text-xs mt-1">${i18n.select_student_prompt}</div>
            </div>
        `;
        document.getElementById('cumGpaDisplay').innerText = '0.00%';
        document.getElementById('cumSemestersCount').innerText = '0';
        document.getElementById('cumTotalHours').innerText = '0';
        document.getElementById('cumTotalPoints').innerText = '0';
        document.getElementById('cumOverallStatus').innerText = '-';
        document.getElementById('failedCoursesAlert').classList.add('hidden');
        document.getElementById('decisionButtonsContainer').innerHTML = `<span class="text-xs cw-subtitle">${i18n.select_student_decision}</span>`;
    }

    function renderDecisionButtons(s) {
        const container = document.getElementById('decisionButtonsContainer');
        container.innerHTML = '';

        const level = String(s.level || '').toLowerCase();
        const failedCount = s.summary?.failed_count ?? 0;
        const isYear1 = level.includes('الأولى') || level.includes('first') || level === '1';

        // فحص هل الفصل الثاني من السنة الأولى منتهٍ وله درجات؟
        const hasY1Sem2 = (s.courses || []).some(c => (Number(c.year) === 1 || !c.year) && Number(c.semester_id) === 2 && !c.is_closed && c.score !== null);

        // فحص هل الفصل الرابع (الفصل الثاني للسنة الثانية) منتهٍ وله درجات؟
        const hasY2Sem2 = (s.courses || []).some(c => Number(c.year) === 2 && Number(c.semester_id) === 2 && !c.is_closed && c.score !== null);

        const suppBtnText = i18n.btn_supp.replace(':count', failedCount);
        const suppSem1Text = i18n.btn_supp_sem1.replace(':count', failedCount);
        const suppSem3Text = i18n.btn_supp_sem3.replace(':count', failedCount);
        const suppGradText = i18n.btn_supp_grad.replace(':count', failedCount);

        if (isYear1) {
            if (hasY1Sem2) {
                // انتهى الفصل الثاني للسنة الأولى -> الترفيع للسنة الثانية
                if (failedCount === 0) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('promote_year_2', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-primary hover:brightness-105 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>${i18n.btn_promote_y2}</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y1_failed}</span>
                        </button>
                    `;
                } else if (failedCount <= 4) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('supplementary', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>${suppBtnText}</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y1}</span>
                        </button>
                    `;
                } else {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y1_all}</span>
                        </button>
                    `;
                }
            } else {
                // انتهى الفصل الأول فقط للسنة الأولى -> الترفيع للفصل الثاني
                if (failedCount === 0) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('promote_semester_2', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>${i18n.btn_promote_sem2}</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_sem1}</span>
                        </button>
                    `;
                } else if (failedCount <= 4) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('supplementary', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>${suppSem1Text}</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y1}</span>
                        </button>
                    `;
                } else {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_sem1_year}</span>
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
                            <span>${i18n.btn_graduate}</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y2}</span>
                        </button>
                    `;
                } else if (failedCount <= 4) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('supplementary', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>${suppGradText}</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y2}</span>
                        </button>
                    `;
                } else {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y2_all}</span>
                        </button>
                    `;
                }
            } else {
                // انتهى الفصل الثالث (الفصل الأول للسنة الثانية) -> الترفيع للفصل الرابع
                if (failedCount === 0) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('promote_semester_4', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>${i18n.btn_promote_sem4}</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y2}</span>
                        </button>
                    `;
                } else if (failedCount <= 4) {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('supplementary', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-pen-ruler"></i>
                            <span>${suppSem3Text}</span>
                        </button>
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs flex items-center gap-2 transition border border-red-500/30">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_y2}</span>
                        </button>
                    `;
                } else {
                    container.innerHTML = `
                        <button type="button" onclick="submitDecision('repeat_year', ${s.student_id})" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs flex items-center gap-2 transition shadow-md">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>${i18n.btn_repeat_sem3}</span>
                        </button>
                    `;
                }
            }
        }
    }

    // ─────────────────────────── AJAX Decision Execution ───────────────────────────
    function submitDecision(decision, studentId) {
        const notes = document.getElementById('decisionNotesInput').value;

        if (!confirm(i18n.confirm_decision)) {
            return;
        }

        const btnContainer = document.getElementById('decisionButtonsContainer');
        const originalContent = btnContainer.innerHTML;
        btnContainer.innerHTML = `<span class="text-xs cw-accent-text font-bold animate-pulse"><i class="fa-solid fa-spinner fa-spin mr-1"></i> ${i18n.saving_decision}</span>`;

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
                alert('❌ ' + (data.message || i18n.error_occurred));
                btnContainer.innerHTML = originalContent;
            }
        })
        .catch(err => {
            console.error(err);
            alert('❌ ' + i18n.connection_failed);
            btnContainer.innerHTML = originalContent;
        });
    }

    // ─────────────────────────── Course Workstation Engine ───────────────────────────
    function renderCourseWorkstation() {
        document.getElementById('matchedCoursesCount').innerText = `${filteredCourses.length} ${i18n.courses_unit_single}`;
        const scrollContainer = document.getElementById('coursesListScroll');
        scrollContainer.innerHTML = '';

        if (filteredCourses.length === 0) {
            scrollContainer.innerHTML = `
                <div class="text-center py-12 cw-subtitle text-xs">
                    <i class="fa-solid fa-book-skull text-2xl mb-2 block opacity-40"></i>
                    ${i18n.no_matching_courses}
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

            const yearLabel = c.year == 1 ? i18n.year_1 : i18n.year_2;
            const semLabel = c.semester_id == 1 ? i18n.semester_1 : i18n.semester_2;

            item.innerHTML = `
                <div>
                    <div class="text-xs font-bold cw-title">${c.title}</div>
                    <div class="text-[11px] cw-subtitle mt-0.5">${yearLabel} • ${semLabel} • ${i18n.hours_weight}: ${c.weight}</div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded cw-accent-badge font-mono">
                    ${enrolledList.length} ${i18n.student_unit_single}
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
        document.getElementById('courseYearBadge').innerText = c.year == 1 ? i18n.year_1 : i18n.year_2;
        const isCourseOpen = (Number(c.semester_id) === 1);
        const semName = c.semester_id == 1 ? i18n.semester_1 : i18n.semester_2;

        if (isCourseOpen) {
            document.getElementById('courseSemesterHeader').innerHTML = `${semName} <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 font-bold mx-2"><i class="fa-solid fa-circle-check text-[10px]"></i> ${i18n.currently_active_sem}</span>`;
        } else {
            document.getElementById('courseSemesterHeader').innerHTML = `${semName} <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/15 text-amber-700 dark:text-amber-400 font-bold mx-2"><i class="fa-solid fa-lock text-[10px]"></i> ${i18n.locked_sem1}</span>`;
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
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 cw-subtitle">${i18n.no_students_in_course}</td></tr>`;
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
                ? `<span class="px-2 py-0.5 rounded text-[11px] font-bold ${isPass ? 'status-badge-pass' : 'status-badge-fail'}">${isPass ? i18n.passed : i18n.failed}</span>`
                : `<span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">${i18n.locked_sem1}</span>`;

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
            alert(i18n.select_student_first);
            return;
        }

        const studentId = displayList[currentStudentIndex].student_id;
        const url = `{{ route('affairs.course_weights.export_student') }}?student_id=${studentId}&format=${format}`;
        window.open(url, '_blank');
    }

    function exportCourseReport(format) {
        if (!currentCourseId) {
            alert(i18n.select_course_first);
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
