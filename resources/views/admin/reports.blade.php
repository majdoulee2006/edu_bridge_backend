@extends('layouts.admin')

@section('title', 'توليد التقارير')
@section('header-title', 'توليد التقارير')
@section('header-subtitle', 'إنشاء تقارير إحصائية وأكاديمية')

@section('header-actions')
    <a href="{{ route('admin.dashboard') }}" class="w-10 h-10 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary transition-all shadow-soft">
        <span class="material-symbols-outlined text-[22px]">arrow_forward</span>
    </a>
@endsection

@section('content')

    <style>
        @media print {
            /* Hide unnecessary UI elements */
            aside, nav, form, button, .no-print, header, .header-actions, a, .pt-1 {
                display: none !important;
            }
            /* Reset body background & shadows for clean printing */
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
            th {
                background-color: #f1f5f9 !important;
            }
            /* Colors adjustment for printing */
            .text-emerald-500 { color: #059669 !important; }
            .text-red-500 { color: #dc2626 !important; }
            .text-primary { color: #2563eb !important; }
        }
    </style>

    <form action="{{ route('admin.reports.generate') }}" method="POST" class="flex flex-col gap-5 no-print">
        @csrf

        {{-- ===== Report Type ===== --}}
        <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-slate-700 dark:text-slate-200 text-right">نوع التقرير</label>
            <div class="grid grid-cols-2 gap-3">
                {{-- Attendance --}}
                <label class="relative cursor-pointer group">
                    <input type="radio" name="report_type" value="attendance" class="peer sr-only"
                           {{ (!isset($reportType) || (isset($reportType) && $reportType === 'attendance')) ? 'checked' : '' }} />
                    <div class="flex flex-col items-center justify-center p-5 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-200 dark:border-slate-700 transition-all duration-300 group-hover:shadow-md peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-2 transition-colors peer-checked:bg-primary">
                            <span class="material-symbols-outlined text-emerald-500 text-2xl">co_present</span>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 text-center">نسب الحضور</span>
                    </div>
                    {{-- Check indicator --}}
                    <div class="absolute top-2 right-2 w-5 h-5 rounded-full border-2 border-slate-200 dark:border-slate-600 flex items-center justify-center peer-checked:bg-primary peer-checked:border-primary transition-all">
                        <span class="material-symbols-outlined text-xs text-transparent peer-checked:text-primary-content">check</span>
                    </div>
                </label>

                {{-- Performance --}}
                <label class="relative cursor-pointer group">
                    <input type="radio" name="report_type" value="performance" class="peer sr-only"
                           {{ (isset($reportType) && $reportType === 'performance') ? 'checked' : '' }} />
                    <div class="flex flex-col items-center justify-center p-5 rounded-2xl bg-surface-light dark:bg-surface-dark border border-slate-200 dark:border-slate-700 transition-all duration-300 group-hover:shadow-md peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary">
                        <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-2 transition-colors peer-checked:bg-primary">
                            <span class="material-symbols-outlined text-blue-500 text-2xl">monitoring</span>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 text-center">أداء الطلاب</span>
                    </div>
                    <div class="absolute top-2 right-2 w-5 h-5 rounded-full border-2 border-slate-200 dark:border-slate-600 flex items-center justify-center peer-checked:bg-primary peer-checked:border-primary transition-all">
                        <span class="material-symbols-outlined text-xs text-transparent peer-checked:text-primary-content">check</span>
                    </div>
                </label>
            </div>
        </div>

        {{-- ===== Filter Criteria ===== --}}
        <div class="bg-surface-light dark:bg-surface-dark rounded-[2rem] p-5 shadow-soft border border-slate-100 dark:border-slate-800/80 flex flex-col gap-4">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white text-center">معايير التصفية</h3>

            {{-- Department --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">القسم / الكلية</label>
                <select name="department_id"
                        class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white appearance-none transition-all outline-none">
                    <option value="">جميع الأقسام</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->department_id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Program --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">الدورة</label>
                <select name="program_id" id="program-select"
                        class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white appearance-none transition-all outline-none">
                    <option value="">جميع الدورات</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}" data-dept-id="{{ $prog->department_id }}">{{ $prog->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Semester --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">الفصل</label>
                <select name="semester_id"
                        class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white appearance-none transition-all outline-none">
                    <option value="">جميع الفصول</option>
                    @foreach($semesters as $sem)
                        <option value="{{ $sem->semester_id }}">{{ $sem->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">من تاريخ</label>
                    <div class="relative">
                        <input name="from_date" type="date"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white transition-all outline-none" />
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-1">إلى تاريخ</label>
                    <div class="relative">
                        <input name="to_date" type="date"
                               class="w-full bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-none focus:ring-2 focus:ring-primary/30 py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white transition-all outline-none" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="pt-1 pb-4">
            <button type="submit" class="group relative w-full overflow-hidden rounded-[1.5rem] bg-primary p-4 transition-all hover:bg-primary-dark active:scale-[0.98] shadow-glow">
                <div class="relative z-10 flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined text-primary-content text-xl">description</span>
                    <span class="font-bold text-primary-content text-lg">توليد التقرير</span>
                </div>
            </button>
        </div>
    </form>

    {{-- ===== Report Results (if any) ===== --}}
    @if(isset($reportData) && count($reportData) > 0)
        {{-- Official Print Header --}}
        <div class="hidden print:block text-center mb-6 border-b-2 border-slate-300 pb-4">
            <h2 class="text-xl font-bold text-slate-900">مؤسسة إيدوبريدج الأكاديمية (EduBridge)</h2>
            <h3 class="text-sm font-semibold text-slate-500 mt-1">تقرير إداري رسمي وشامل</h3>
            <p class="text-[10px] text-slate-400 mt-2">تاريخ إصدار التقرير: {{ date('Y-m-d H:i') }}</p>
        </div>

        <div class="flex items-center justify-between gap-4 -mb-1 no-print">
            <div class="flex items-center gap-2">
                <span class="w-1 h-5 bg-primary rounded-full"></span>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">نتائج التقرير</h3>
            </div>
            
            <div class="flex items-center gap-2">
                {{-- Print Button --}}
                <button onclick="window.print()" class="px-4 py-2 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 active:scale-95 text-slate-700 dark:text-slate-200 font-bold text-xs shadow-soft transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-base">print</span>
                    <span>طباعة (PDF)</span>
                </button>

                {{-- Export Form --}}
                <form action="{{ route('admin.reports.export') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="report_type" value="{{ $reportType }}">
                    <input type="hidden" name="department_id" value="{{ request('department_id') }}">
                    <input type="hidden" name="program_id" value="{{ request('program_id') }}">
                    <input type="hidden" name="semester_id" value="{{ request('semester_id') }}">
                    <input type="hidden" name="from_date" value="{{ request('from_date') }}">
                    <input type="hidden" name="to_date" value="{{ request('to_date') }}">
                    
                    <button type="submit" class="px-4 py-2 rounded-2xl bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-bold text-xs shadow-glow transition-all flex items-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-base">download</span>
                        <span>تحميل التقرير (Excel)</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ===== Summary Analytics Cards ===== --}}
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
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 mt-3">
                <!-- Card 1: Attendance Average -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                    <div class="flex flex-col gap-1">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">متوسط نسبة الحضور</span>
                        <span class="text-2xl font-black text-emerald-500">{{ $avgAttendanceRate }}%</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                        <span class="material-symbols-outlined text-2xl">check_circle</span>
                    </div>
                </div>

                <!-- Card 2: Total Records -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                    <div class="flex flex-col gap-1">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">إجمالي الجلسات المرصودة</span>
                        <span class="text-2xl font-black text-primary">{{ $totalSessionsSum }} محاضرة</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-2xl">event_available</span>
                    </div>
                </div>

                <!-- Card 3: Warning Students -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
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
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 mt-3">
                <!-- Card 1: Average Grade -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                    <div class="flex flex-col gap-1">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">متوسط درجات الطلاب</span>
                        <span class="text-2xl font-black text-primary">{{ $avgGrade }} / 100</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-2xl">analytics</span>
                    </div>
                </div>

                <!-- Card 2: Highest Score -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
                    <div class="flex flex-col gap-1">
                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">أعلى درجة مسجلة</span>
                        <span class="text-2xl font-black text-emerald-500">{{ $maxGrade }}%</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                        <span class="material-symbols-outlined text-2xl">military_tech</span>
                    </div>
                </div>

                <!-- Card 3: Success Rate -->
                <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-3xl border border-slate-100 dark:border-slate-800/80 shadow-soft flex items-center justify-between transition-all hover:translate-y-[-2px]">
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

        <div class="bg-surface-light dark:bg-surface-dark rounded-2xl shadow-soft border border-slate-100 dark:border-slate-700/50 overflow-hidden">
            @if(isset($reportType) && $reportType === 'attendance')
                {{-- Attendance Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-right">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700/50">
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase">الطالب</th>
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase">المادة</th>
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase text-center">حضور</th>
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase text-center">غياب</th>
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase text-center">النسبة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $row)
                                @php
                                    $rate = $row->total_sessions > 0 ? round(($row->present_count / $row->total_sessions) * 100) : 0;
                                    $rateColor = $rate >= 75 ? 'text-emerald-500' : ($rate >= 50 ? 'text-orange-500' : 'text-red-500');
                                @endphp
                                <tr class="border-b border-slate-50 dark:border-slate-800 last:border-0 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="p-3 text-xs font-bold text-slate-800 dark:text-white">{{ $row->full_name }}</td>
                                    <td class="p-3 text-xs text-slate-500">{{ $row->course_title }}</td>
                                    <td class="p-3 text-xs font-bold text-emerald-500 text-center">{{ $row->present_count }}</td>
                                    <td class="p-3 text-xs font-bold text-red-500 text-center">{{ $row->absent_count }}</td>
                                    <td class="p-3 text-xs font-extrabold {{ $rateColor }} text-center">{{ $rate }}%</td>
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
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase">الطالب</th>
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase">المادة</th>
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase text-center">الدرجة</th>
                                <th class="p-3 text-[11px] font-bold text-slate-500 uppercase text-center">الفصل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData as $row)
                                <tr class="border-b border-slate-50 dark:border-slate-800 last:border-0 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="p-3 text-xs font-bold text-slate-800 dark:text-white">{{ $row->full_name }}</td>
                                    <td class="p-3 text-xs text-slate-500">{{ $row->course_title }}</td>
                                    <td class="p-3 text-xs font-extrabold text-primary text-center">{{ $row->grade }}</td>
                                    <td class="p-3 text-xs text-slate-400 text-center">{{ $row->semester }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @elseif(isset($reportData) && count($reportData) == 0)
        <div class="flex flex-col items-center justify-center py-8 gap-3 no-print">
            <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl text-slate-300">search_off</span>
            </div>
            <p class="text-sm font-bold text-slate-400">لا توجد بيانات ضمن المعايير المحددة</p>
        </div>
    @endif

    {{-- Spacer --}}
    <div class="h-4"></div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deptSelect = document.querySelector('select[name="department_id"]');
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
                
                // Reset program selection if the currently selected one is now hidden
                const selectedOpt = programSelect.options[programSelect.selectedIndex];
                if (selectedOpt && selectedOpt.style.display === 'none') {
                    programSelect.value = "";
                }
            });
        }
    });
</script>
@endpush
