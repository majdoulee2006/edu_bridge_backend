<!DOCTYPE html>
<html class="light" dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>الملخص الإحصائي المعتمد للحضور والغياب الأكاديمي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #0f172a;
        }
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined' !important;
            font-weight: normal;
            font-style: normal;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-[#0f172a] p-4 text-[11px] leading-normal">

<div class="relative w-full bg-white text-[#191b22] rounded-xl shadow-none overflow-hidden p-6 select-none border border-[#e2e8f0]" dir="rtl" id="officialAttendanceSheet">

    <!-- Institutional Watermark -->
    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.028] overflow-hidden">
        <svg class="w-[640px] h-[640px] text-[#0c0e14]" fill="currentColor" viewBox="0 0 200 200">
            <path d="M100 15 L20 60 L100 105 L180 60 Z"></path>
            <path d="M40 78 V125 C40 148 65 170 100 178 C135 170 160 148 160 125 V78 L100 112 Z"></path>
            <path d="M30 65 V130 H22 V65 Z"></path>
        </svg>
    </div>

    <!-- Outer Ornamental Security Guilloche Bar (Identical to Schedule & Exams) -->
    <div class="w-full h-1.5 bg-gradient-to-r from-[#005a9c] via-[#fabc4d] to-[#005a9c] rounded-full mb-4"></div>

    <!-- 1. OFFICIAL INSTITUTIONAL HEADER (Identical in Style, Logos, and Structure to Weekly & Exam Schedules) -->
    <header class="w-full pb-4 mb-4 border-b border-[#e2e8f0]">
        <div class="grid grid-cols-12 items-center gap-4">
            
            <!-- Right: Syria & UNRWA Directorate Hierarchy -->
            <div class="col-span-4 flex items-center gap-3">
                <div class="w-14 h-14 shrink-0 rounded-xl bg-[#005a9c]/10 flex items-center justify-center p-1.5 border border-[#005a9c]/20">
                    <span class="material-symbols-outlined text-[36px] text-[#005a9c]">public</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[13px] leading-tight text-[#0f172a] font-bold">الجمهورية العربية السورية</span>
                    <span class="text-[11px] text-[#334155] leading-tight">وكالة الأمم المتحدة لإغاثة وتشغيل اللاجئين (UNRWA)</span>
                    <span class="text-[13px] text-[#005a9c] font-bold leading-tight mt-0.5">معهد دمشق المتوسط (DTC)</span>
                    <span class="text-[10px] text-[#64748b]">دائرة التربية والتعليم والتدريب المهني • شؤون الطلاب والامتحانات</span>
                </div>
            </div>

            <!-- Center: DTC Crest & Service Title -->
            <div class="col-span-4 flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#fff7ed] to-[#fef3c7] border border-amber-200 flex items-center justify-center shadow-inner mb-1.5">
                    <span class="material-symbols-outlined text-[34px] text-[#d97706]">how_to_reg</span>
                </div>
                <h1 class="text-[19px] font-bold text-[#0f172a] tracking-tight">الملخص الإحصائي المعتمد للحضور والغياب الأكاديمي</h1>
                <div class="inline-flex items-center gap-2 mt-1 px-3 py-0.5 bg-[#f8fafc] rounded-full border border-slate-200">
                    <span class="text-[11px] text-[#475569] font-semibold">العام الأكاديمي 2025 - 2026</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#d97706]"></span>
                    <span class="text-[11px] text-[#d97706] font-bold">{{ $semesterName ?? 'الفصل الدراسي الأول (دورة الخريف)' }}</span>
                </div>
            </div>

            <!-- Left: Edu-Bridge Ecosystem & Digital Verification QR -->
            <div class="col-span-4 flex items-center justify-end gap-3">
                <div class="flex flex-col text-left items-end">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[12px] font-bold text-[#b45309]">Edu-Bridge Digital System</span>
                        <span class="material-symbols-outlined text-[16px] text-[#d97706]">verified</span>
                    </div>
                    <span class="font-mono text-[10px] text-[#64748b] tracking-wider mt-0.5">REF: {{ $refCode }}</span>
                    <span class="text-[10px] text-[#94a3b8]">تاريخ الاستخراج: {{ $reportDateStr }}</span>
                    <span class="inline-block mt-1 text-[9px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md font-semibold border border-emerald-200">وثيقة رسمية معتمدة ومحققة آلياً</span>
                </div>
                <!-- Verification QR Block -->
                <div class="w-16 h-16 shrink-0 bg-[#f8fafc] p-1.5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-center relative">
                    <svg class="w-full h-full text-[#1e293b]" fill="currentColor" viewBox="0 0 100 100">
                        <rect fill="currentColor" height="26" rx="2" width="26" x="5" y="5"></rect>
                        <rect fill="white" height="18" width="18" x="9" y="9"></rect>
                        <rect fill="currentColor" height="10" width="10" x="13" y="13"></rect>
                        <rect fill="currentColor" height="26" rx="2" width="26" x="69" y="5"></rect>
                        <rect fill="white" height="18" width="18" x="73" y="9"></rect>
                        <rect fill="currentColor" height="10" width="10" x="77" y="13"></rect>
                        <rect fill="currentColor" height="26" rx="2" width="26" x="5" y="69"></rect>
                        <rect fill="white" height="18" width="18" x="9" y="73"></rect>
                        <rect fill="currentColor" height="10" width="10" x="13" y="77"></rect>
                        <rect fill="currentColor" height="8" width="8" x="36" y="10"></rect>
                        <rect fill="currentColor" height="6" width="12" x="48" y="18"></rect>
                        <rect fill="currentColor" height="8" width="20" x="40" y="32"></rect>
                        <rect fill="currentColor" height="16" width="8" x="68" y="40"></rect>
                        <rect fill="currentColor" height="6" width="14" x="12" y="42"></rect>
                        <rect fill="currentColor" height="16" width="16" x="42" y="48"></rect>
                        <rect fill="currentColor" height="12" width="12" x="66" y="66"></rect>
                        <rect fill="currentColor" height="24" width="8" x="84" y="50"></rect>
                        <rect fill="currentColor" height="8" width="18" x="36" y="76"></rect>
                        <rect fill="currentColor" height="6" width="24" x="58" y="86"></rect>
                    </svg>
                </div>
            </div>

        </div>
    </header>

    <!-- 2. TEACHER IDENTITY & FILTER SPECIFICATION CARDS -->
    <section class="grid grid-cols-12 gap-3 mb-3 p-3 bg-[#f8fafc] rounded-xl border border-[#e2e8f0]">
        
        <!-- Teacher Name -->
        <div class="col-span-3 flex items-center gap-2.5 bg-white p-2 rounded-lg border border-slate-200/80 shadow-2xs">
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-100">
                <span class="material-symbols-outlined text-[20px]">badge</span>
            </div>
            <div class="flex flex-col truncate">
                <span class="text-[9.5px] font-semibold text-slate-500">اسم المدرس / المشرف الأكاديمي</span>
                <span class="text-[12px] font-bold text-slate-900 truncate">{{ $teacherName }}</span>
            </div>
        </div>

        <!-- Filter Scope -->
        <div class="col-span-3 flex items-center gap-2.5 bg-white p-2 rounded-lg border border-slate-200/80 shadow-2xs">
            <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0 border border-indigo-100">
                <span class="material-symbols-outlined text-[20px]">tune</span>
            </div>
            <div class="flex flex-col truncate">
                <span class="text-[9.5px] font-semibold text-slate-500">نطاق التقرير (الفلترة)</span>
                <span class="text-[12px] font-bold text-indigo-900 truncate">{{ $filterScopeText }}</span>
            </div>
        </div>

        <!-- Course Filter -->
        <div class="col-span-3 flex items-center gap-2.5 bg-white p-2 rounded-lg border border-slate-200/80 shadow-2xs">
            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-100">
                <span class="material-symbols-outlined text-[20px]">menu_book</span>
            </div>
            <div class="flex flex-col truncate">
                <span class="text-[9.5px] font-semibold text-slate-500">المقرر الدراسي المستهدف</span>
                <span class="text-[12px] font-bold text-amber-950 truncate">{{ $filterCourseText }}</span>
            </div>
        </div>

        <!-- Class / Cohort -->
        <div class="col-span-3 flex items-center gap-2.5 bg-white p-2 rounded-lg border border-slate-200/80 shadow-2xs">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-100">
                <span class="material-symbols-outlined text-[20px]">groups</span>
            </div>
            <div class="flex flex-col truncate">
                <span class="text-[9.5px] font-semibold text-slate-500">{{ $scope === 'advisor_class' ? 'اسم الدورة الإشرافية' : 'الدورة / الشعبة' }}</span>
                <span class="text-[12px] font-bold text-emerald-950 truncate">{{ $filterClassText }}</span>
            </div>
        </div>

    </section>

    <!-- Contextual Banner: Supervisory Cohort Announcement -->
    @if($scope === 'advisor_class')
        <div class="mb-3 px-4 py-2 bg-gradient-to-r from-amber-50 via-[#fffbeb] to-amber-50 border border-amber-200 rounded-xl flex items-center justify-between text-[11px]">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[19px] text-amber-700">supervisor_account</span>
                <span class="font-bold text-amber-950">سجل طلاب الدورة الإشرافية: {{ $advisorCohortName }}</span>
            </div>
            <div class="flex items-center gap-4 text-amber-900 font-semibold text-[10px]">
                <span>المشرف (مربي الدورة): <strong class="font-bold">{{ $teacherName }}</strong></span>
                <span>•</span>
                <span>عدد طلاب الدورة: <strong class="font-bold">{{ count($studentsList) }} طالباً</strong></span>
                <span>•</span>
                <span>الفترة: <strong class="font-bold text-[#b45309]">{{ $periodLabel }}</strong></span>
            </div>
        </div>
    @else
        <div class="mb-3 px-4 py-1.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-[10.5px]">
            <div class="flex items-center gap-2 text-slate-700">
                <span class="material-symbols-outlined text-[17px] text-[#005a9c]">school</span>
                <span class="font-bold text-slate-900">سجل طلاب المقررات المسندة للمعلم</span>
                <span class="text-slate-500 font-medium">(يتضمن اختصاص ودورة كل طالب في حال تعدد الشعب)</span>
            </div>
            <div class="flex items-center gap-3 text-slate-600 font-semibold text-[10px]">
                <span>عدد الطلاب المسجلين بالتقرير: <strong>{{ count($studentsList) }} طالب</strong></span>
                <span>•</span>
                <span>الفترة المحددة: <strong class="text-blue-700">{{ $periodLabel }}</strong></span>
            </div>
        </div>
    @endif

    <!-- 3. ATTENDANCE MATRIX TABLE (Daily Attendance: 1+ session = Full Day Present) -->
    <section class="w-full overflow-hidden mb-3 rounded-xl border border-slate-200 shadow-2xs">
        <table class="w-full text-right border-collapse text-[11px] bg-white">
            <thead>
                <tr class="bg-[#1e293b] text-white font-bold text-center">
                    <th class="p-2 border-r border-slate-700 w-9" rowspan="2">#</th>
                    <th class="p-2 border-r border-slate-700 w-24" rowspan="2">الرقم الأكاديمي</th>
                    <th class="p-2 border-r border-slate-700 min-w-[170px] text-right pr-3" rowspan="2">اسم الطالب الرباعي</th>
                    <th class="p-2 border-r border-slate-700 min-w-[140px] text-center bg-[#253248]" rowspan="2">الدورة / الاختصاص</th>
                    <th class="p-2 border-r border-slate-700 bg-[#334155]" colspan="4">متابعة الدوام اليومي (قاعدة جلسة واحدة فأكثر)</th>
                    <th class="p-2 border-r border-slate-700 bg-[#422006] min-w-[130px]" rowspan="2">الحالة الأكاديمية والإنذار</th>
                    <th class="p-2 bg-[#0f172a] min-w-[140px]" rowspan="2">الإجراء الإداري والتوصية</th>
                </tr>
                <tr class="bg-[#334155] text-white text-[10px] font-semibold text-center border-t border-slate-600">
                    <th class="py-1 px-2 border-r border-slate-600 w-16">أيام الدوام</th>
                    <th class="py-1 px-2 border-r border-slate-600 text-emerald-300 w-16">أيام الحضور</th>
                    <th class="py-1 px-2 border-r border-slate-600 text-rose-300 w-16">غياب كامل</th>
                    <th class="py-1 px-2.5 border-r border-slate-600 bg-[#3b4b61] text-amber-300 font-bold w-20">نسبة الالتزام %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-center font-medium">
                @forelse($studentsList as $index => $row)
                    @php
                        $isAlt = $index % 2 === 1;
                        $rateNum = floatval(rtrim($row['rate'], '%'));
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors {{ $isAlt ? 'bg-slate-50/50' : 'bg-white' }} {{ $rateNum < 80 ? '!bg-rose-50/50' : '' }}">
                        <!-- # -->
                        <td class="p-2 border-r border-slate-200 font-bold text-slate-500">{{ $index + 1 }}</td>
                        
                        <!-- Academic ID -->
                        <td class="p-2 border-r border-slate-200 font-mono text-slate-800 font-bold">{{ $row['academic_id'] }}</td>
                        
                        <!-- Student Name -->
                        <td class="p-2 border-r border-slate-200 text-right font-bold text-slate-900 pr-3">
                            <div class="flex items-center justify-between">
                                <span class="{{ $rateNum < 80 ? 'text-rose-700 font-black' : '' }}">{{ $row['name'] }}</span>
                                @if($rateNum < 80)
                                    <span class="material-symbols-outlined text-[15px] text-rose-600">warning</span>
                                @endif
                            </div>
                        </td>

                        <!-- Specialization / Cohort (الدورة / الاختصاص) -->
                        <td class="p-1.5 border-r border-slate-200 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold {{ $scope === 'advisor_class' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-800 border border-blue-100' }}">
                                {{ $row['batch_name'] ?? 'عام' }}
                            </span>
                        </td>

                        <!-- أيام الدوام -->
                        <td class="p-1.5 border-r border-slate-200 font-semibold text-slate-800">{{ $row['total_days'] }}</td>

                        <!-- أيام الحضور -->
                        <td class="p-1.5 border-r border-slate-200 font-bold text-emerald-700">{{ $row['attended_days'] }}</td>

                        <!-- غياب كامل -->
                        <td class="p-1.5 border-r border-slate-200 font-bold {{ $row['absent_days'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            {{ $row['absent_days'] }}
                        </td>

                        <!-- نسبة الالتزام % -->
                        <td class="p-1.5 border-r border-slate-200 bg-slate-50/70 font-black {{ $rateNum >= 85 ? 'text-emerald-700' : ($rateNum >= 80 ? 'text-amber-600' : 'text-rose-600') }}">
                            {{ $row['rate'] }}
                        </td>

                        <!-- الحالة الأكاديمية والإنذار -->
                        <td class="p-1.5 border-r border-slate-200">
                            @if($rateNum == 100)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[9.5px] border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                    <span>ملتزم تماماً</span>
                                </span>
                            @elseif($rateNum >= 85)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[9.5px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>حضور نظامي</span>
                                </span>
                            @elseif($rateNum >= 80)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 font-bold text-[9.5px] border border-amber-300">
                                    <span class="material-symbols-outlined text-[13px]">info</span>
                                    <span>إنذار أولي (15%)</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-black text-[9.5px] border border-rose-300">
                                    <span class="material-symbols-outlined text-[13px]">notification_important</span>
                                    <span>حرمان أولي (20%)</span>
                                </span>
                            @endif
                        </td>

                        <!-- الإجراء الإداري والتوصية -->
                        <td class="p-1.5 text-[10.5px] font-semibold {{ $rateNum >= 85 ? 'text-emerald-700' : ($rateNum >= 80 ? 'text-amber-800' : 'text-rose-700') }}">
                            @if($rateNum == 100)
                                طبيعي - تميز والتزام كامل
                            @elseif($rateNum >= 85)
                                طبيعي - دون ملاحظات
                            @elseif($rateNum >= 80)
                                تنبيه ومتابعة إدارية
                            @else
                                إنذار خطي + استدعاء ولي أمر
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center text-slate-500 font-bold">
                            لا توجد بيانات حضور مسجلة للطلاب في النطاق المحدد
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-300">
                    <td class="p-2.5 text-right pr-4 font-black" colspan="4">
                        المجموع العام الإجمالي للشعبة ({{ count($studentsList) }} طالباً مسجلاً - عينة التقرير)
                    </td>
                    <td class="p-2 border-r border-slate-300 text-center font-bold">{{ $summaryTotals['days'] ?? 0 }}</td>
                    <td class="p-2 border-r border-slate-300 text-center text-emerald-700 font-bold">{{ $summaryTotals['attended'] ?? 0 }}</td>
                    <td class="p-2 border-r border-slate-300 text-center text-rose-600 font-bold">{{ $summaryTotals['absent'] ?? 0 }}</td>
                    <td class="p-2 border-r border-slate-300 text-center text-amber-700 font-black">{{ $summaryTotals['avg_rate'] ?? '0%' }}</td>
                    <td class="p-2 border-r border-slate-300 text-center text-rose-700 font-black">
                        {{ $summaryTotals['warnings_count'] ?? 0 }} إنذار / حرمان
                    </td>
                    <td class="p-2 text-center text-emerald-800 font-black">مصدق ومطابق رسمياً</td>
                </tr>
            </tfoot>
        </table>
    </section>

    <!-- 4. REGULATORY ACADEMIC GUIDANCE LEGEND CARDS -->
    <section class="grid grid-cols-2 gap-3 mb-3">
        <!-- Rule 1: Daily Presence Calculation Rule -->
        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 flex items-start gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                <span class="material-symbols-outlined text-[16px]">rule</span>
            </div>
            <div>
                <h4 class="text-[11px] font-black text-slate-900">القاعدة الأكاديمية لاحتساب حضور اليوم:</h4>
                <p class="text-[10px] text-slate-600 leading-relaxed mt-0.5">
                    يُعد الطالب <strong class="text-emerald-700">"حاضراً"</strong> في اليوم كاملاً بمجرد حضوره جلسة واحدة على الأقل من الجلسات المنعقدة خلال ذلك اليوم، مع بقاء رصد المادة التي غاب عنها بدقة لاحتساب نصاب الساعات الأكاديمية للمقرر.
                </p>
            </div>
        </div>

        <!-- Rule 2: Academic Deprivation & Warning Regulations -->
        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 flex items-start gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-800 flex items-center justify-center shrink-0 mt-0.5">
                <span class="material-symbols-outlined text-[16px]">gavel</span>
            </div>
            <div>
                <h4 class="text-[11px] font-black text-rose-800">ضوابط الإنذار الأكاديمي والحرمان النهائي:</h4>
                <p class="text-[10px] text-slate-600 leading-relaxed mt-0.5">
                    تجاوز نسبة الغياب غير المبرر <strong class="text-rose-600">15%</strong> يوجب توجيه إنذار خطي أولي يبلغ به ولي الأمر؛ وتجاوز نسبة <strong class="text-rose-800">20%</strong> يترتب عليه حرمان الطالب تلقائياً من التقدم للامتحان النهائي للمقرر وتثبيت نتيجته (محروم).
                </p>
            </div>
        </div>
    </section>

    <!-- 5. OFFICIAL SIGN-OFF BLOCK (Instructor & Head of Department ONLY, NO signatures, NO stamps, NO student affairs) -->
    <footer class="pt-3 mt-1 border-t-2 border-slate-200 flex items-center justify-around text-center">
        <!-- Instructor -->
        <div class="flex flex-col items-center">
            <span class="text-[11px] font-bold text-slate-500">مدرس المقرر / المشرف الأكاديمي</span>
            <span class="text-[13px] font-black text-slate-900 mt-0.5">{{ $teacherName }}</span>
        </div>

        <!-- Head of Department -->
        <div class="flex flex-col items-center">
            <span class="text-[11px] font-bold text-slate-500">رئيس قسم تكنولوجيا المعلومات</span>
            <span class="text-[13px] font-black text-slate-900 mt-0.5">د. أحمد ديب</span>
        </div>
    </footer>

    <!-- 6. BOTTOM SECURITY & AUDIT TRAIL METADATA STRIP -->
    <div class="mt-3 pt-2 border-t border-slate-200 flex items-center justify-between text-[9px] text-slate-500 font-mono">
        <div>DTC-SEC-HASH: {{ substr(md5(($teacherName ?? '') . ($reportDateStr ?? '')), 0, 16) }} | SYSTEM ID: DTC-ATT-{{ date('Ymd') }}-SEC</div>
        <div class="flex items-center gap-1.5">
            <span>دقة الإخراج: 300 DPI Ultra Clear</span>
            <span>•</span>
            <span>منظومة Edu-Bridge للتوثيق الأكاديمي الرقمي المعتمد</span>
        </div>
    </div>

</div>

</body>
</html>
