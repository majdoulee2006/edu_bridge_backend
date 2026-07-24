@extends('layouts.admin')

@section('title', 'سجل التقارير الإدارية')
@section('header-title', 'سجل التقارير والإحصائيات')
@section('header-subtitle', 'سجل وثائق وتقارير أداء وحضور المعهد التقني')

@section('header-actions')
    <div class="flex items-center gap-3">
        {{-- Back Button --}}
        <a href="{{ route('admin.dashboard') }}" class="w-10 h-10 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft">
            <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
        </a>
    </div>
@endsection

@section('content')

    <style>
        @media print {
            aside, nav, form, button, .no-print, header, .header-actions, a, .pt-1, #report-modal {
                display: none !important;
            }
            body, main, .print-container, .bg-surface-light, .bg-surface-dark {
                background: white !important;
                color: black !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .grid {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 1rem !important;
            }
            table {
                border-collapse: collapse !important;
                width: 100% !important;
            }
            th, td {
                border: 1px solid #cbd5e1 !important;
                padding: 8px !important;
                color: black !important;
            }
            th { background-color: #f1f5f9 !important; }
            .text-emerald-500 { color: #059669 !important; }
            .text-red-500 { color: #dc2626 !important; }
            .text-primary { color: #2563eb !important; }
        }
    </style>

    {{-- ===== SUCCESS FLASH MESSAGE ALERT ===== --}}
    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 p-4 rounded-2xl flex items-center gap-3 text-xs font-black shadow-sm mb-4 no-print">
            <span class="material-symbols-outlined text-xl">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ===== TOP ACTION BAR: NEW REPORT BUTTON ON THE RIGHT ABOVE THE DARK CONTAINER ===== --}}
    <div class="flex items-center justify-start mb-4 no-print">
        <button onclick="openReportModal()" class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-primary text-primary-content font-black shadow-glow hover:bg-primary-dark transition-all active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined font-black text-2xl">add</span>
            <span class="text-sm font-extrabold">تقرير جديد</span>
        </button>
    </div>

    {{-- ===== MODAL DIALOG (+) FOR GENERATING NEW REPORT ===== --}}
    <div id="report-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto no-print">
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 p-6 md:p-8 w-full max-w-2xl relative my-8" dir="rtl">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-primary/20 flex items-center justify-center text-slate-900 dark:text-white">
                        <span class="material-symbols-outlined text-2xl">post_add</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">توليد تقرير إداري جديد</h3>
                        <p class="text-xs text-slate-400">حدد نوع ومعايير التصفية للتقرير المطلوب</p>
                    </div>
                </div>
                <button onclick="closeReportModal()" class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:hover:text-white transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            {{-- Form inside Modal --}}
            <form action="{{ route('admin.reports.generate') }}" method="POST" onsubmit="return validateReportDates(event)" class="flex flex-col gap-5">
                @csrf

                {{-- Client-side Error Alert Container --}}
                <div id="date-error-alert" class="hidden bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 p-4 rounded-2xl flex items-center gap-3 text-xs font-bold shadow-sm">
                    <span class="material-symbols-outlined text-xl flex-shrink-0">error</span>
                    <span id="date-error-text"></span>
                </div>

                {{-- 1. Report Type Selection --}}
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-200">نوع التقرير</label>
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Attendance --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="report_type" value="attendance" class="peer sr-only"
                                   {{ (!isset($reportType) || (isset($reportType) && $reportType === 'attendance')) ? 'checked' : '' }} />
                            <div class="flex flex-col items-center justify-center p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 transition-all group-hover:shadow-md peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:ring-2 peer-checked:ring-primary">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-1.5 transition-colors peer-checked:bg-primary">
                                    <span class="material-symbols-outlined text-emerald-500 text-xl">co_present</span>
                                </div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">نسب الحضور</span>
                            </div>
                        </label>

                        {{-- Performance --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="report_type" value="performance" class="peer sr-only"
                                   {{ (isset($reportType) && $reportType === 'performance') ? 'checked' : '' }} />
                            <div class="flex flex-col items-center justify-center p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 transition-all group-hover:shadow-md peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:ring-2 peer-checked:ring-primary">
                                <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-1.5 transition-colors peer-checked:bg-primary">
                                    <span class="material-symbols-outlined text-blue-500 text-xl">monitoring</span>
                                </div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">أداء الطلاب</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Filters Container --}}
                <div class="bg-slate-50 dark:bg-slate-800/40 rounded-2xl p-4 flex flex-col gap-3 border border-slate-100 dark:border-slate-800">
                    <h4 class="text-xs font-bold text-slate-600 dark:text-slate-300">معايير التصفية والفرز</h4>

                    {{-- 2. Department --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-semibold text-slate-400">القسم</label>
                        <select name="department_id" id="department-select"
                                class="w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 py-2.5 px-3 text-xs font-semibold text-slate-800 dark:text-white outline-none">
                            <option value="">جميع الأقسام</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->department_id }}" {{ request('department_id') == $dept->department_id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Program / Course --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-semibold text-slate-400">الدورة</label>
                        <select name="program_id" id="program-select"
                                class="w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 py-2.5 px-3 text-xs font-semibold text-slate-800 dark:text-white outline-none">
                            <option value="">جميع الدورات</option>
                            @foreach($programs as $prog)
                                <option value="{{ $prog->id }}" data-dept-id="{{ $prog->department_id }}" {{ request('program_id') == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 4. Semester --}}
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-semibold text-slate-400">الفصل الدراسي</label>
                        <select name="semester_id"
                                class="w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 py-2.5 px-3 text-xs font-semibold text-slate-800 dark:text-white outline-none">
                            <option value="">جميع الفصول</option>
                            @foreach($semesters as $sem)
                                @php
                                    $semCleanName = trim(preg_replace('/\d+/', '', $sem->name));
                                @endphp
                                <option value="{{ $sem->semester_id }}" {{ request('semester_id') == $sem->semester_id ? 'selected' : '' }}>{{ $semCleanName }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 5. Dates --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-semibold text-slate-400">من تاريخ</label>
                            <input id="from_date_input" name="from_date" type="date" value="{{ request('from_date') }}" max="{{ date('Y-m-d') }}"
                                   class="w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 py-2.5 px-3 text-xs font-semibold text-slate-800 dark:text-white outline-none" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[11px] font-semibold text-slate-400">إلى تاريخ</label>
                            <input id="to_date_input" name="to_date" type="date" value="{{ request('to_date') }}" max="{{ date('Y-m-d') }}"
                                   class="w-full bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 py-2.5 px-3 text-xs font-semibold text-slate-800 dark:text-white outline-none" />
                        </div>
                    </div>
                </div>

                {{-- 6. Submit Button --}}
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 py-3 rounded-2xl bg-primary text-primary-content font-extrabold text-sm hover:bg-primary-dark transition-all shadow-glow active:scale-95 cursor-pointer">
                        توليد التقرير الان
                    </button>
                    <button type="button" onclick="closeReportModal()" class="px-5 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm hover:bg-slate-200 transition-all cursor-pointer">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== MAIN SCREEN CONTENT: REPORTS LOG OR PREVIEW RESULTS ===== --}}
    @if(!isset($previewReport))
        {{-- ===== 1. REPORTS HISTORY LOG SCREEN ===== --}}
        <div class="flex flex-col gap-6 no-print">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-primary/20 flex items-center justify-center text-slate-900 dark:text-white">
                        <span class="material-symbols-outlined text-2xl">assessment</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-800 dark:text-white">سجل التقارير الإدارية</h3>
                        <p class="text-xs text-slate-400">عرض كافة التقارير المُنشأة مسبقاً وإمكانية استعراضها أو تحميلها</p>
                    </div>
                </div>
                <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                    إجمالي التقارير: {{ count($savedReports) }}
                </span>
            </div>

            @if(count($savedReports) == 0)
                {{-- EMPTY STATE --}}
                <div class="flex flex-col items-center justify-center min-h-[350px] bg-surface-light dark:bg-surface-dark rounded-[2.5rem] p-8 text-center border border-slate-100 dark:border-slate-800/80 shadow-soft">
                    <div class="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-4 shadow-glow">
                        <span class="material-symbols-outlined text-4xl">folder_off</span>
                    </div>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white mb-2">لا توجد تقارير محفوظة بالسجل حالياً</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 max-w-md mb-6 leading-relaxed">
                        قم بالنقر على زر "+ تقرير جديد" في الأعلى لتوليد تقرير إداري جديد وحفظه تلقائياً في السجل.
                    </p>
                    <button onclick="openReportModal()" class="flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-primary-content font-black text-xs shadow-glow hover:bg-primary-dark transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-lg">add</span>
                        <span>توليد تقرير جديد الان</span>
                    </button>
                </div>
            @else
                {{-- CARDS LIST OF SAVED REPORTS --}}
                <div class="grid grid-cols-1 gap-4">
                    @foreach($savedReports as $report)
                        <div class="bg-surface-light dark:bg-surface-dark rounded-3xl p-5 border border-slate-100 dark:border-slate-800 shadow-soft hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4">
                            
                            {{-- Info Left --}}
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 {{ $report->report_type === 'attendance' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-blue-500/10 text-blue-500' }}">
                                    <span class="material-symbols-outlined text-2xl">{{ $report->report_type === 'attendance' ? 'co_present' : 'monitoring' }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-base font-extrabold text-slate-800 dark:text-white">{{ $report->title }}</h4>
                                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $report->report_type === 'attendance' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                            {{ $report->report_type === 'attendance' ? 'نسب الحضور' : 'أداء الطلاب' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs font-semibold text-slate-400 flex-wrap">
                                        <span><strong class="text-slate-600 dark:text-slate-300">القسم:</strong> {{ $report->department_name ?? 'جميع الأقسام' }}</span>
                                        <span>•</span>
                                        <span><strong class="text-slate-600 dark:text-slate-300">الدورة:</strong> {{ $report->program_name ?? 'جميع الدورات' }}</span>
                                        <span>•</span>
                                        <span><strong class="text-slate-600 dark:text-slate-300">الفصل:</strong> {{ $report->semester_name ?? 'جميع الفصول' }}</span>
                                        @if($report->from_date || $report->to_date)
                                            <span>•</span>
                                            <span><strong class="text-slate-600 dark:text-slate-300">الفترة:</strong> {{ $report->from_date ?? 'البداية' }} إلى {{ $report->to_date ?? 'الآن' }}</span>
                                        @endif
                                    </div>
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                        تم الإصدار: {{ \Carbon\Carbon::parse($report->created_at)->diffForHumans() }} ({{ \Carbon\Carbon::parse($report->created_at)->format('Y-m-d H:i') }})
                                    </span>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex items-center gap-2 flex-wrap md:flex-nowrap flex-shrink-0 border-t md:border-t-0 pt-3 md:pt-0 border-slate-100 dark:border-slate-800">
                                {{-- 👁️ View Button --}}
                                <a href="{{ route('admin.reports', ['view_id' => $report->id]) }}" 
                                   class="px-3.5 py-2 rounded-xl bg-primary text-primary-content font-extrabold text-xs shadow-glow hover:bg-primary-dark transition-all flex items-center gap-1.5 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>رؤية التقرير</span>
                                </a>

                                {{-- 🟢 Download Excel Button --}}
                                <form action="{{ route('admin.reports.export') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="export_format" value="excel">
                                    <input type="hidden" name="report_type" value="{{ $report->report_type }}">
                                    <input type="hidden" name="department_id" value="{{ $report->department_id }}">
                                    <input type="hidden" name="program_id" value="{{ $report->program_id }}">
                                    <input type="hidden" name="semester_id" value="{{ $report->semester_id }}">
                                    <input type="hidden" name="from_date" value="{{ $report->from_date }}">
                                    <input type="hidden" name="to_date" value="{{ $report->to_date }}">

                                    <button type="submit" 
                                            class="px-3.5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-xs shadow-glow transition-all flex items-center gap-1.5 cursor-pointer">
                                        <span class="material-symbols-outlined text-base">table_chart</span>
                                        <span>تنزيل Excel</span>
                                    </button>
                                </form>

                                {{-- 🔴 Download PDF Button --}}
                                <form action="{{ route('admin.reports.export') }}" method="POST" target="_blank" class="inline">
                                    @csrf
                                    <input type="hidden" name="export_format" value="pdf">
                                    <input type="hidden" name="report_type" value="{{ $report->report_type }}">
                                    <input type="hidden" name="department_id" value="{{ $report->department_id }}">
                                    <input type="hidden" name="program_id" value="{{ $report->program_id }}">
                                    <input type="hidden" name="semester_id" value="{{ $report->semester_id }}">
                                    <input type="hidden" name="from_date" value="{{ $report->from_date }}">
                                    <input type="hidden" name="to_date" value="{{ $report->to_date }}">

                                    <button type="submit" 
                                            class="px-3.5 py-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-extrabold text-xs shadow-glow transition-all flex items-center gap-1.5 cursor-pointer">
                                        <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                                        <span>تنزيل PDF</span>
                                    </button>
                                </form>

                                {{-- 🗑️ Delete Button --}}
                                <form action="{{ route('admin.reports.delete', $report->id) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت تأكد من رغبتك في حذف هذا التقرير من السجل؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 rounded-xl bg-slate-100 hover:bg-rose-500 hover:text-white dark:bg-slate-800 text-slate-400 font-bold text-xs transition-all flex items-center justify-center cursor-pointer" title="حذف من السجل">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </form>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        {{-- ===== 2. REPORT GENERATED / PREVIEW RESULT VIEW ===== --}}
        <div class="flex flex-col gap-6">
            {{-- Official Print Header --}}
            <div class="hidden print:block text-center mb-6 border-b-2 border-slate-300 pb-4">
                <h2 class="text-xl font-bold text-slate-900">مؤسسة إيدوبريدج الأكاديمية (EduBridge)</h2>
                <h3 class="text-sm font-semibold text-slate-500 mt-1">تقرير إداري رسمي وشامل</h3>
                <p class="text-[10px] text-slate-400 mt-2">تاريخ إصدار التقرير: {{ date('Y-m-d H:i') }}</p>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print bg-surface-light dark:bg-surface-dark p-4 rounded-3xl border border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.reports') }}" class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition-all cursor-pointer" title="العودة لسجل التقارير">
                        <span class="material-symbols-outlined text-xl">arrow_forward</span>
                    </a>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-white">
                            معاينة: {{ $previewReport->title }}
                        </h3>
                        <p class="text-xs text-slate-400">نوع التقرير: {{ $reportType === 'attendance' ? 'نسب الحضور والغياب' : 'أداء ودرجات الطلاب' }}</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    {{-- Print Button --}}
                    <button onclick="window.print()" class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 active:scale-95 text-slate-700 dark:text-slate-200 font-bold text-xs shadow-soft transition-all flex items-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-base">print</span>
                        <span>طباعة مباشرة</span>
                    </button>

                    {{-- 🟢 Export Excel --}}
                    <form action="{{ route('admin.reports.export') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="export_format" value="excel">
                        <input type="hidden" name="report_type" value="{{ $previewReport->report_type }}">
                        <input type="hidden" name="department_id" value="{{ $previewReport->department_id }}">
                        <input type="hidden" name="program_id" value="{{ $previewReport->program_id }}">
                        <input type="hidden" name="semester_id" value="{{ $previewReport->semester_id }}">
                        <input type="hidden" name="from_date" value="{{ $previewReport->from_date }}">
                        <input type="hidden" name="to_date" value="{{ $previewReport->to_date }}">
                        
                        <button type="submit" class="px-4 py-2.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-bold text-xs shadow-glow transition-all flex items-center gap-1.5 cursor-pointer">
                            <span class="material-symbols-outlined text-base">table_chart</span>
                            <span>تنزيل Excel</span>
                        </button>
                    </form>

                    {{-- 🔴 Export PDF --}}
                    <form action="{{ route('admin.reports.export') }}" method="POST" target="_blank" class="inline">
                        @csrf
                        <input type="hidden" name="export_format" value="pdf">
                        <input type="hidden" name="report_type" value="{{ $previewReport->report_type }}">
                        <input type="hidden" name="department_id" value="{{ $previewReport->department_id }}">
                        <input type="hidden" name="program_id" value="{{ $previewReport->program_id }}">
                        <input type="hidden" name="semester_id" value="{{ $previewReport->semester_id }}">
                        <input type="hidden" name="from_date" value="{{ $previewReport->from_date }}">
                        <input type="hidden" name="to_date" value="{{ $previewReport->to_date }}">
                        
                        <button type="submit" class="px-4 py-2.5 rounded-2xl bg-rose-500 hover:bg-rose-600 active:scale-95 text-white font-bold text-xs shadow-glow transition-all flex items-center gap-1.5 cursor-pointer">
                            <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                            <span>تنزيل PDF</span>
                        </button>
                    </form>

                    {{-- Close preview button --}}
                    <a href="{{ route('admin.reports') }}" class="px-4 py-2.5 rounded-2xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-300 transition-all">
                        إغلاق المعاينة
                    </a>
                </div>
            </div>

            {{-- Summary Analytics Cards --}}
            @if(isset($reportType) && $reportType === 'attendance')
                @php
                    $totalSessionsSum = 0;
                    $presentSum = 0;
                    $absentSum = 0;
                    $lowAttendanceWarningCount = 0;
                    
                    foreach($reportData as $row) {
                        $totalSessionsSum += $row->total_sessions;
                        $presentSum += $row->present_count;
                        $absentSum += $row->absent_count;
                        
                        $rate = $row->total_sessions > 0 ? ($row->present_count / $row->total_sessions) * 100 : 0;
                        if ($rate < 75) {
                            $lowAttendanceWarningCount++;
                        }
                    }
                    
                    $avgAttendanceRate = $totalSessionsSum > 0 ? round(($presentSum / $totalSessionsSum) * 100) : 0;
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Card 1: Attendance Average -->
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">متوسط نسبة الحضور</span>
                            <span class="text-2xl font-black text-emerald-500">{{ $avgAttendanceRate }}%</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <span class="material-symbols-outlined text-2xl">check_circle</span>
                        </div>
                    </div>

                    <!-- Card 2: Total Records -->
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">إجمالي الجلسات المرصودة</span>
                            <span class="text-2xl font-black text-primary">{{ $totalSessionsSum }} محاضرة</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-2xl">event_available</span>
                        </div>
                    </div>

                    <!-- Card 3: Warning Students -->
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">طلاب منذرين (حضور < 75%)</span>
                            <span class="text-2xl font-black text-red-500">{{ $lowAttendanceWarningCount }} طالب</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-red-500">
                            <span class="material-symbols-outlined text-2xl">error</span>
                        </div>
                    </div>
                </div>
            @else
                @php
                    $gradeSum = 0;
                    $maxGrade = 0;
                    $successCount = 0;
                    $totalEvaluated = count($reportData);
                    
                    foreach($reportData as $row) {
                        $gradeSum += $row->grade;
                        if ($row->grade > $maxGrade) {
                            $maxGrade = $row->grade;
                        }
                        if ($row->grade >= 60) {
                            $successCount++;
                        }
                    }
                    
                    $avgGrade = $totalEvaluated > 0 ? round($gradeSum / $totalEvaluated, 1) : 0;
                    $successRate = $totalEvaluated > 0 ? round(($successCount / $totalEvaluated) * 100) : 0;
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Card 1: Average Grade -->
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">متوسط درجات الطلاب</span>
                            <span class="text-2xl font-black text-primary">{{ $avgGrade }} / 100</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-2xl">analytics</span>
                        </div>
                    </div>

                    <!-- Card 2: Highest Score -->
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">أعلى درجة مسجلة</span>
                            <span class="text-2xl font-black text-emerald-500">{{ $maxGrade }}%</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <span class="material-symbols-outlined text-2xl">military_tech</span>
                        </div>
                    </div>

                    <!-- Card 3: Success Rate -->
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">نسبة النجاح العامة (درجة >= 60)</span>
                            <span class="text-2xl font-black text-blue-500">{{ $successRate }}%</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-500">
                            <span class="material-symbols-outlined text-2xl">school</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ===== COLORFUL INTERACTIVE CHARTS SECTION ===== --}}
            @if(count($reportData) > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 no-print">
                    {{-- Chart 1: Distribution Chart (Doughnut) --}}
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                <h4 class="text-sm font-extrabold text-slate-800 dark:text-white">
                                    {{ $reportType === 'attendance' ? 'التوزيع النسبي العام لنسب الحضور والغياب' : 'توزيع درجات وأداء الطلاب' }}
                                </h4>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400">رسم بياني دائري</span>
                        </div>
                        <div class="relative w-full h-64 flex items-center justify-center p-2">
                            <canvas id="reportDoughnutChart"></canvas>
                        </div>
                    </div>

                    {{-- Chart 2: Comparative Bar Chart --}}
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-soft flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                <h4 class="text-sm font-extrabold text-slate-800 dark:text-white">
                                    {{ $reportType === 'attendance' ? 'مقارنة نسبة الحضور حسب المواد' : 'معدل درجات المواد الدراسية' }}
                                </h4>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400">رسم بياني أعمدة</span>
                        </div>
                        <div class="relative w-full h-64 flex items-center justify-center p-2">
                            <canvas id="reportBarChart"></canvas>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Table Results --}}
            <div class="bg-surface-light dark:bg-surface-dark rounded-3xl shadow-soft border border-slate-100 dark:border-slate-700/50 overflow-hidden">
                @if(count($reportData) > 0)
                    @if(isset($reportType) && $reportType === 'attendance')
                        {{-- Attendance Table --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-right">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700/50">
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">#</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase">اسم الطالب</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase">القسم</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase">الدورة / البرنامج</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase">المادة</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">الفصل</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">حاضر</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">غائب</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">الإجمالي</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">نسبة الحضور</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $index => $row)
                                        @php
                                            $rate = $row->total_sessions > 0 ? round(($row->present_count / $row->total_sessions) * 100) : 0;
                                        @endphp
                                        <tr class="border-b border-slate-50 dark:border-slate-800 last:border-0 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                            <td class="p-4 text-xs font-bold text-slate-400 text-center">{{ $index + 1 }}</td>
                                            <td class="p-4 text-xs font-bold text-slate-800 dark:text-white">{{ $row->full_name }}</td>
                                            <td class="p-4 text-xs font-bold text-primary">{{ $row->department_name ?? 'عام' }}</td>
                                            <td class="p-4 text-xs text-slate-500">{{ $row->program_name ?? 'عام' }}</td>
                                            <td class="p-4 text-xs text-slate-600 dark:text-slate-300 font-semibold">{{ $row->course_title }}</td>
                                            <td class="p-4 text-xs text-slate-400 text-center">{{ $row->semester_name ?? 'عام' }}</td>
                                            <td class="p-4 text-xs font-bold text-emerald-500 text-center">{{ $row->present_count }}</td>
                                            <td class="p-4 text-xs font-bold text-rose-500 text-center">{{ $row->absent_count }}</td>
                                            <td class="p-4 text-xs font-bold text-slate-500 text-center">{{ $row->total_sessions }}</td>
                                            <td class="p-4 text-xs font-extrabold text-center">{{ $rate }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        {{-- Performance Table --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-right">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700/50">
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">#</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase">اسم الطالب</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase">القسم</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase">الدورة / البرنامج</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase">المادة</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">الفصل</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">الدرجة</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">التقدير</th>
                                        <th class="p-4 text-[11px] font-bold text-slate-500 uppercase text-center">النتيجة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $index => $row)
                                        @php
                                            $g = $row->grade;
                                            if ($g >= 90) { $rating = 'ممتاز'; $pass = 'ناجح'; $badge = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'; }
                                            elseif ($g >= 80) { $rating = 'جيد جداً'; $pass = 'ناجح'; $badge = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'; }
                                            elseif ($g >= 70) { $rating = 'جيد'; $pass = 'ناجح'; $badge = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'; }
                                            elseif ($g >= 60) { $rating = 'مقبول'; $pass = 'ناجح'; $badge = 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'; }
                                            else { $rating = 'راسب'; $pass = 'راسب'; $badge = 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 font-bold'; }
                                        @endphp
                                        <tr class="border-b border-slate-50 dark:border-slate-800 last:border-0 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                            <td class="p-4 text-xs font-bold text-slate-400 text-center">{{ $index + 1 }}</td>
                                            <td class="p-4 text-xs font-bold text-slate-800 dark:text-white">{{ $row->full_name }}</td>
                                            <td class="p-4 text-xs font-bold text-primary">{{ $row->department_name ?? 'عام' }}</td>
                                            <td class="p-4 text-xs text-slate-500">{{ $row->program_name ?? 'عام' }}</td>
                                            <td class="p-4 text-xs text-slate-600 dark:text-slate-300 font-semibold">{{ $row->course_title }}</td>
                                            <td class="p-4 text-xs text-slate-400 text-center">{{ $row->semester ?? 'عام' }}</td>
                                            <td class="p-4 text-xs font-black text-slate-800 dark:text-white text-center">{{ $row->grade }}</td>
                                            <td class="p-4 text-center">
                                                <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-lg {{ $badge }}">
                                                    {{ $rating }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-center">
                                                <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-lg {{ $badge }}">
                                                    {{ $pass }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-12 gap-3">
                        <span class="material-symbols-outlined text-4xl text-slate-300">search_off</span>
                        <p class="text-sm font-bold text-slate-400">لا توجد بيانات مطابقة ضمن المعايير المحددة</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function openReportModal() {
        const modal = document.getElementById('report-modal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeReportModal() {
        const modal = document.getElementById('report-modal');
        if (modal) modal.classList.add('hidden');
    }

    function validateReportDates(e) {
        const fromInput = document.getElementById('from_date_input');
        const toInput = document.getElementById('to_date_input');
        const alertBox = document.getElementById('date-error-alert');
        const errorText = document.getElementById('date-error-text');

        if (alertBox) alertBox.classList.add('hidden');

        const fromVal = fromInput ? fromInput.value : '';
        const toVal = toInput ? toInput.value : '';
        const todayStr = new Date().toISOString().split('T')[0];

        if (fromVal && fromVal > todayStr) {
            e.preventDefault();
            errorText.textContent = 'تنبيه: لا يمكن تحديد "من تاريخ" في المستقبل (التاريخ يجب أن يكون اليوم أو ما قبله).';
            alertBox.classList.remove('hidden');
            return false;
        }

        if (toVal && toVal > todayStr) {
            e.preventDefault();
            errorText.textContent = 'تنبيه: لا يمكن تحديد "إلى تاريخ" في المستقبل (التاريخ يجب أن يكون اليوم أو ما قبله).';
            alertBox.classList.remove('hidden');
            return false;
        }

        if (fromVal && toVal) {
            const dFrom = new Date(fromVal);
            const dTo = new Date(toVal);

            if (dFrom > dTo) {
                e.preventDefault();
                errorText.textContent = 'تنبيه: تاريخ البداية (من تاريخ) يجب أن يكون قبل أو يطابق تاريخ النهاية (إلى تاريخ).';
                alertBox.classList.remove('hidden');
                return false;
            }

            const diffTime = dTo - dFrom;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            if (diffDays > 365) {
                e.preventDefault();
                errorText.textContent = 'تنبيه: نطاق البحث بين التواريخ يجب ألا يتجاوز سنة واحدة (365 يوماً).';
                alertBox.classList.remove('hidden');
                return false;
            }
        }

        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const deptSelect = document.getElementById('department-select');
        const programSelect = document.getElementById('program-select');
        
        if (deptSelect && programSelect) {
            deptSelect.addEventListener('change', function() {
                const selectedDeptId = this.value;
                const options = programSelect.querySelectorAll('option');
                
                options.forEach(opt => {
                    if (opt.value === "") {
                        opt.style.display = 'block';
                        return;
                    }
                    const optDeptId = opt.getAttribute('data-dept-id');
                    if (!selectedDeptId || optDeptId === selectedDeptId) {
                        opt.style.display = 'block';
                    } else {
                        opt.style.display = 'none';
                    }
                });
                
                const selectedOpt = programSelect.options[programSelect.selectedIndex];
                if (selectedOpt && selectedOpt.style.display === 'none') {
                    programSelect.value = "";
                }
            });
        }

        {{-- ===== INITIALIZE CHARTS IF IN PREVIEW MODE ===== --}}
        @if(isset($previewReport) && isset($reportData) && count($reportData) > 0)
            const doughnutCtx = document.getElementById('reportDoughnutChart');
            const barCtx = document.getElementById('reportBarChart');

            @if($reportType === 'attendance')
                @php
                    $presTot = collect($reportData)->sum('present_count');
                    $absTot  = collect($reportData)->sum('absent_count');
                    $cGroup  = collect($reportData)->groupBy('course_title');
                    $cLabels = [];
                    $cValues = [];
                    foreach($cGroup as $ctitle => $rows) {
                        $tot = $rows->sum('total_sessions');
                        $p   = $rows->sum('present_count');
                        $cLabels[] = $ctitle;
                        $cValues[] = $tot > 0 ? round(($p / $tot) * 100) : 0;
                    }
                @endphp

                if (doughnutCtx) {
                    new Chart(doughnutCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['حضور', 'غياب'],
                            datasets: [{
                                data: [{{ $presTot }}, {{ $absTot }}],
                                backgroundColor: ['#10b981', '#f43f5e'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { font: { family: 'Segoe UI, sans-serif', size: 12, weight: 'bold' } } }
                            }
                        }
                    });
                }

                if (barCtx) {
                    new Chart(barCtx, {
                        type: 'bar',
                        data: {
                            labels: {!! json_encode($cLabels) !!},
                            datasets: [{
                                label: 'نسبة الحضور %',
                                data: {!! json_encode($cValues) !!},
                                backgroundColor: '#3b82f6',
                                borderRadius: 10
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true, max: 100 } },
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            @else
                @php
                    $g90 = 0; $g80 = 0; $g70 = 0; $g60 = 0; $gFail = 0;
                    foreach($reportData as $row) {
                        $g = $row->grade;
                        if ($g >= 90) $g90++;
                        elseif ($g >= 80) $g80++;
                        elseif ($g >= 70) $g70++;
                        elseif ($g >= 60) $g60++;
                        else $gFail++;
                    }
                    $cGroup = collect($reportData)->groupBy('course_title');
                    $cLabels = [];
                    $cAvgGrades = [];
                    foreach($cGroup as $ctitle => $rows) {
                        $cLabels[] = $ctitle;
                        $cAvgGrades[] = round($rows->avg('grade'), 1);
                    }
                @endphp

                if (doughnutCtx) {
                    new Chart(doughnutCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['ممتاز (90-100)', 'جيد جداً (80-89)', 'جيد (70-79)', 'مقبول (60-69)', 'راسب (<60)'],
                            datasets: [{
                                data: [{{ $g90 }}, {{ $g80 }}, {{ $g70 }}, {{ $g60 }}, {{ $gFail }}],
                                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#f43f5e'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { font: { family: 'Segoe UI, sans-serif', size: 11, weight: 'bold' } } }
                            }
                        }
                    });
                }

                if (barCtx) {
                    new Chart(barCtx, {
                        type: 'bar',
                        data: {
                            labels: {!! json_encode($cLabels) !!},
                            datasets: [{
                                label: 'معدل الدرجات',
                                data: {!! json_encode($cAvgGrades) !!},
                                backgroundColor: '#8b5cf6',
                                borderRadius: 10
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true, max: 100 } },
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            @endif
        @endif
    });
</script>
@endpush
