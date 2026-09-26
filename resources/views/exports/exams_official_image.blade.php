<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>برنامج الامتحانات النهائية - {{ $student->user->full_name ?? 'الطالب' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100..900&family=Space+Grotesk:wght@100..900&family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#ffc665",
                        "surface-tint": "#fabc4d",
                        "primary-container": "#e5a93c",
                        "secondary": "#e9c349",
                        "tertiary": "#fec73a",
                        "background": "#ffffff",
                    },
                    fontFamily: {
                        sans: ['Cairo', 'Plus Jakarta Sans', 'sans-serif'],
                        headline: ['Cairo', 'Space Grotesk', 'sans-serif'],
                    }
                }
            }
        };
    </script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background: #ffffff; font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif; -webkit-print-color-adjust: exact; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-white p-4">

@php
    $user = $student->user ?? null;
    $studentName = $user->full_name ?? $user->name ?? 'طالب أكاديمي';
    $studentId = $student->student_code ?? $user->university_id ?? '2026001';
    $deptName = $user->department ?? 'نظم المعلومات الحاسوبية';
    $progName = $student->program->name ?? $user->branch ?? 'معلوماتية';
    $levelName = $student->level ?? $user->academic_year ?? 'السنة الأولى';

    $examsList = collect($exams ?? []);

    // Exam Period range
    $firstExamDate = $examsList->isNotEmpty() ? $examsList->first()->exam_date ?? null : null;
    $lastExamDate = $examsList->isNotEmpty() ? $examsList->last()->exam_date ?? null : null;
    $periodStr = 'خلال الدورة الامتحانية المعتمدة';
    if ($firstExamDate && $lastExamDate) {
        $cStart = \Carbon\Carbon::parse($firstExamDate);
        $cEnd = \Carbon\Carbon::parse($lastExamDate);
        $periodStr = $cStart->format('d/m/Y') . ' – ' . $cEnd->format('d/m/Y');
    }
@endphp

<!-- THE EXPORTABLE OFFICIAL EXAM SHEET -->
<article class="relative w-[1360px] mx-auto bg-white text-[#191b22] rounded-2xl shadow-none overflow-hidden p-8 select-none border border-slate-200" dir="rtl" id="officialExamSheet">
    
    <!-- Institutional Watermark -->
    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.035] overflow-hidden">
        <svg class="w-[680px] h-[680px] text-[#0c0e14]" fill="currentColor" viewBox="0 0 200 200">
            <path d="M100 15 L20 60 L100 105 L180 60 Z"></path>
            <path d="M40 78 V125 C40 148 65 170 100 178 C135 170 160 148 160 125 V78 L100 112 Z"></path>
            <path d="M30 65 V130 H22 V65 Z"></path>
        </svg>
    </div>

    <!-- Outer Ornamental Security Ribbon (Vibrant Blue Bar) -->
    <div class="w-full h-1.5 bg-blue-600 rounded-full mb-6"></div>

    <!-- 1. OFFICIAL INSTITUTIONAL HEADER -->
    <header class="w-full pb-4 mb-5 border-b border-[#e2e8f0]">
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

            <!-- Center: DTC Crest & Exam Title -->
            <div class="col-span-4 flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-full bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shadow-inner mb-1.5">
                    <span class="material-symbols-outlined text-[32px]">school</span>
                </div>
                <h1 class="text-[20px] font-bold text-[#0f172a] tracking-tight">برنامج الامتحانات النهائية الرسمية المعتمدة</h1>
                <div class="inline-flex items-center gap-2 mt-1 px-3 py-0.5 bg-[#f8fafc] rounded-full border border-slate-200">
                    <span class="text-[11px] text-[#475569] font-semibold">العام الأكاديمي 2025 / 2026</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#d97706]"></span>
                    <span class="text-[11px] text-[#d97706] font-bold">{{ $semesterName ?? 'الفصل الدراسي الأول (الدورة الشتوية)' }}</span>
                </div>
            </div>

            <!-- Left: Edu-Bridge Ecosystem & Digital Verification QR -->
            <div class="col-span-4 flex items-center justify-end gap-3">
                <div class="flex flex-col text-left items-end">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[12px] font-bold text-[#b45309]">منظومة Edu-Bridge الرقمية</span>
                        <span class="material-symbols-outlined text-[16px] text-[#d97706]">verified</span>
                    </div>
                    <span class="font-mono text-[10px] text-[#64748b] tracking-wider mt-0.5">REF: DTC-EXAM-2026-{{ strtoupper($student->program_id ? 'CIS' : 'IT') }}-F1</span>
                    <span class="text-[10px] text-[#94a3b8]">تاريخ الإصدار: {{ now()->format('d/m/Y - h:i') }} {{ now()->format('A') == 'AM' ? 'ص' : 'م' }}</span>
                    <span class="inline-block mt-1 text-[9px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md font-semibold border border-emerald-200">وثيقة رسمية معتمدة ومقفلة آلياً</span>
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

    <!-- 2. STUDENT ACADEMIC IDENTITY CARD (Modified as requested: Removed Hall/Seat and Exam Committee Head) -->
    <section class="w-full bg-slate-50/60 border border-slate-200 rounded-xl p-4 mb-5">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 text-right">
            
            <!-- Student Name (col-span-2) -->
            <div class="col-span-2 bg-white rounded-lg p-2.5 shadow-xs border border-slate-200/80">
                <span class="block text-[10px] text-[#64748b]">اسم الطالب الرباعي:</span>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="material-symbols-outlined text-[18px] text-[#d97706]">person</span>
                    <span class="text-[14px] font-bold text-[#0f172a]">{{ $studentName }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 bg-[#fef3c7] text-[#92400e] font-bold rounded">نظامي</span>
                </div>
            </div>

            <!-- University ID (col-span-1) -->
            <div class="bg-white rounded-lg p-2.5 shadow-xs border border-slate-200/80">
                <span class="block text-[10px] text-[#64748b]">الرقم الأكاديمي / الامتحاني:</span>
                <span class="font-mono text-[13px] font-bold text-[#005a9c] mt-0.5 block">{{ $studentId }}</span>
            </div>

            <!-- Academic Program (col-span-2) -->
            <div class="col-span-2 bg-white rounded-lg p-2.5 shadow-xs border border-slate-200/80">
                <span class="block text-[10px] text-[#64748b]">القسم والتخصص:</span>
                <span class="text-[12px] font-bold text-[#0f172a] mt-0.5 block truncate">
                    {{ $deptName }} ({{ $progName }})
                </span>
            </div>

            <!-- Division & Level (col-span-1) -->
            <div class="bg-white rounded-lg p-2.5 shadow-xs border border-slate-200/80">
                <span class="block text-[10px] text-[#64748b]">المستوى والشعبة:</span>
                <span class="text-[12px] font-bold text-[#1e293b] mt-0.5 block">{{ $levelName }} • شعبة (A)</span>
            </div>

            <!-- Head of Department (col-span-3 - Balanced Row 2) -->
            <div class="col-span-3 bg-white rounded-lg p-2.5 shadow-xs border border-slate-200/80">
                <span class="block text-[10px] text-[#64748b]">رئيس القسم الأكاديمي:</span>
                <span class="text-[12px] font-semibold text-[#334155] mt-0.5 block">د. أحمد ديب</span>
            </div>

            <!-- Exam Period (col-span-3 - Balanced Row 2) -->
            <div class="col-span-3 bg-white rounded-lg p-2.5 shadow-xs border border-slate-200/80">
                <span class="block text-[10px] text-[#64748b]">الفترة الزمنية للامتحانات:</span>
                <div class="flex items-center gap-1 mt-0.5 text-[11px] text-[#047857] font-semibold">
                    <span class="material-symbols-outlined text-[16px]">date_range</span>
                    <span>{{ $periodStr }}</span>
                </div>
            </div>

        </div>
    </section>

    <!-- 3. DETAILED EXAM TIMETABLE TABLE (Strictly the 5 columns requested by user) -->
    <section class="w-full mb-5 overflow-hidden rounded-xl bg-white shadow-sm border border-slate-200">
        <div class="w-full overflow-x-auto">
            <table class="w-full border-collapse text-right min-w-[980px]">
                <thead>
                    <tr class="bg-[#0f172a] text-white">
                        <th class="py-3 px-4 text-[12px] font-bold whitespace-nowrap w-[24%]" scope="col">اليوم والتاريخ</th>
                        <th class="py-3 px-4 text-[12px] font-bold whitespace-nowrap w-[18%]" scope="col">التوقيت</th>
                        <th class="py-3 px-3 text-[12px] font-bold whitespace-nowrap w-[15%]" scope="col">رمز المقرر</th>
                        <th class="py-3 px-4 text-[12px] font-bold whitespace-nowrap w-[23%]" scope="col">اسم المقرر</th>
                        <th class="py-3 px-4 text-[12px] font-bold whitespace-nowrap w-[20%]" scope="col">المشرف المسؤول</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-[#191b22]">
                    @forelse($examsList as $index => $exam)
                        @php
                            $rawDate = $exam->date ?? $exam->exam_date ?? null;
                            $cDate = $rawDate ? \Carbon\Carbon::parse($rawDate) : null;
                            $dayName = $cDate ? $cDate->locale('ar')->isoFormat('dddd') : '—';
                            $dateFormatted = $cDate ? $cDate->format('d/m/Y') : '—';
                            $timeStr = $exam->time ?? ($cDate ? $cDate->format('h:i A') : '09:00 ص');
                            $timeEndStr = $cDate ? $cDate->copy()->addHours(2)->format('h:i A') : '11:00 ص';
                            $cTitle = $exam->course_title ?? $exam->course?->title ?? $exam->title ?? $exam->exam_name ?? 'مقرر دراسي';
                            $cCode = $exam->course_code ?? $exam->course?->code ?? ('CS' . (101 + $index));
                            $teacherName = $exam->teacher_name ?? $exam->teacher ?? 'هيئة التدريس';
                            $isAlt = $index % 2 === 1;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $isAlt ? 'bg-slate-50/40' : 'bg-white' }}">
                            <!-- 1. اليوم والتاريخ -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[16px] text-blue-600">event</span>
                                    <span class="text-[13px] text-[#0f172a] font-bold">{{ $dayName }} {{ $dateFormatted }}</span>
                                </div>
                            </td>

                            <!-- 2. التوقيت -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="text-[12px] text-slate-800 font-semibold font-mono">{{ $timeStr }} - {{ $timeEndStr }}</span>
                                <span class="block text-[10px] text-slate-500">(ساعتان)</span>
                            </td>

                            <!-- 3. رمز المقرر -->
                            <td class="py-3.5 px-3 font-mono font-bold text-[12px] text-[#005a9c] whitespace-nowrap">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-900 border border-blue-200 rounded">
                                    {{ $cCode }}
                                </span>
                            </td>

                            <!-- 4. اسم المقرر -->
                            <td class="py-3.5 px-4">
                                <span class="text-[13px] text-[#0f172a] font-bold">{{ $cTitle }}</span>
                            </td>

                            <!-- 5. المشرف المسؤول (أستاذ المادة) -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 text-[12px] text-slate-700 font-medium">
                                    <span class="material-symbols-outlined text-[15px] text-[#d97706]">school</span>
                                    <span>{{ $teacherName }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 font-medium">
                                لا توجد امتحانات مسجلة للطالب في الدورة الحالية
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- 4. STUDENT INSTRUCTIONS & REGULATORY NOTICE -->
    <section class="w-full mb-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-[#d97706] text-[20px]">gavel</span>
            <h3 class="text-[14px] text-[#0f172a] font-bold">تعليمات وضوابط الامتحان الإلزامية للطلاب</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="bg-amber-50/50 border border-amber-200/80 rounded-xl p-3.5 text-slate-800 flex items-start gap-3 shadow-xs">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                </div>
                <div>
                    <h4 class="text-[12px] font-bold text-amber-950 mb-1">الحضور والانضباط الزمني</h4>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        التواجد داخل القاعة الامتحانية قبل 15 دقيقة على الأقل من بدء الوقت المحدد. يُمنع دخول أي طالب بعد توزيع الأسئلة.
                    </p>
                </div>
            </div>
            <div class="bg-amber-50/50 border border-amber-200/80 rounded-xl p-3.5 text-slate-800 flex items-start gap-3 shadow-xs">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">badge</span>
                </div>
                <div>
                    <h4 class="text-[12px] font-bold text-amber-950 mb-1">إثبات الشخصية والبطاقة</h4>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        إبراز البطاقة الجامعية وبطاقة الامتحان الشخصية مع الهوية كشرط إلزامي للجلوس على المقعد المحدد.
                    </p>
                </div>
            </div>
            <div class="bg-rose-50/50 border border-rose-200/80 rounded-xl p-3.5 text-slate-800 flex items-start gap-3 shadow-xs">
                <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-700 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">phonelink_erase</span>
                </div>
                <div>
                    <h4 class="text-[12px] font-bold text-rose-950 mb-1">المحظورات والأجهزة الذكية</h4>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        يُحظر اصطحاب الهواتف الذكية والساعات الإلكترونية والوسائط التخزينية داخل القاعات تحت طائلة الحرمان الفوري.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. INSTITUTIONAL SEALS & DEPARTMENT SIGN-OFF BLOCK (Head of Department ONLY, NO fake signature, NO stamp, NO student affairs) -->
    <footer class="w-full pt-4 border-t border-[#e2e8f0]">
        <div class="flex flex-col items-center justify-center text-center py-2">
            <span class="text-[11px] text-[#64748b]">رئيس قسم هندسة وتكنولوجيا المعلومات</span>
            <span class="text-[14px] font-bold text-[#0f172a] mt-0.5">د. أحمد ديب</span>
            <span class="font-mono text-[9px] text-[#94a3b8] mt-0.5">اعتماد رقم: DEP-CIS-992</span>
        </div>

        <!-- Bottom Footer Reference Strip -->
        <div class="mt-3 pt-3 border-t border-[#f1f5f9] flex flex-wrap items-center justify-between text-[10px] text-[#64748b]">
            <div class="flex items-center gap-2">
                <span class="font-bold text-[#005a9c]">منظومة جسر التعليم Edu-Bridge • DTC</span>
                <span>تم استخراج الوثيقة إلكترونياً وتعتبر نافذة دون الحاجة لتوقيع يدوي إضافي عند التحقق الرقمي.</span>
            </div>
            <div class="font-mono text-[9px] text-[#94a3b8]">
                SYSTEM ID: EB-EXAM-{{ date('Ymd') }}-{{ rand(100000, 999999) }}-CIS
            </div>
        </div>
    </footer>

</article>

</body>
</html>
