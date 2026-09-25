<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>البرنامج الأسبوعي - {{ $student->user->full_name ?? 'الطالب' }}</title>
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
    
    // Day maps
    $dayOrder = [
        'Sunday'    => ['ar' => 'الأحد', 'desc' => 'يوم عمل كامل'],
        'Monday'    => ['ar' => 'الإثنين', 'desc' => 'يوم عمل كامل'],
        'Tuesday'   => ['ar' => 'الثلاثاء', 'desc' => 'يوم عمل مكثف'],
        'Wednesday' => ['ar' => 'الأربعاء', 'desc' => 'تطبيقي تخصصي'],
        'Thursday'  => ['ar' => 'الخميس', 'desc' => 'ورشات وتقييم'],
    ];

    // Group student's schedules by day
    $byDay = [];
    foreach ($dayOrder as $enDay => $info) {
        $byDay[$enDay] = [];
    }

    if (isset($schedules)) {
        foreach ($schedules as $s) {
            $d = $s->day ?? '';
            if (isset($byDay[$d])) {
                $byDay[$d][] = $s;
            }
        }
    }
@endphp

<!-- THE EXPORTABLE OFFICIAL DOCUMENT SHEET -->
<article class="relative w-[1360px] mx-auto bg-white text-[#191b22] rounded-xl shadow-none overflow-hidden p-8 select-none border border-[#e2e8f0]" dir="rtl" id="officialScheduleSheet">
    
    <!-- Institutional Watermark -->
    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.035] overflow-hidden">
        <svg class="w-[680px] h-[680px] text-[#0c0e14]" fill="currentColor" viewBox="0 0 200 200">
            <path d="M100 15 L20 60 L100 105 L180 60 Z"></path>
            <path d="M40 78 V125 C40 148 65 170 100 178 C135 170 160 148 160 125 V78 L100 112 Z"></path>
            <path d="M30 65 V130 H22 V65 Z"></path>
        </svg>
    </div>

    <!-- Outer Ornamental Security Guilloche Bar -->
    <div class="w-full h-1.5 bg-gradient-to-r from-[#005a9c] via-[#fabc4d] to-[#005a9c] rounded-full mb-5"></div>

    <!-- 1. OFFICIAL INSTITUTIONAL HEADER -->
    <header class="w-full pb-4 mb-5 border-b border-[#e2e8f0]">
        <div class="grid grid-cols-12 items-center gap-4">
            
            <!-- Right: Syria & UNRWA Directorate Hierarchy -->
            <div class="col-span-4 flex items-center gap-3">
                <div class="w-14 h-14 shrink-0 rounded-lg bg-[#005a9c]/10 flex items-center justify-center p-1.5">
                    <span class="material-symbols-outlined text-[36px] text-[#005a9c]">public</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[13px] leading-tight text-[#0f172a] font-bold">الجمهورية العربية السورية</span>
                    <span class="text-[11px] text-[#334155] leading-tight">وكالة الأمم المتحدة لإغاثة وتشغيل اللاجئين (UNRWA)</span>
                    <span class="text-[13px] text-[#005a9c] font-bold leading-tight mt-0.5">معهد دمشق المتوسط (DTC)</span>
                    <span class="text-[10px] text-[#64748b]">دائرة التربية والتعليم والتدريب المهني • شؤون الطلاب والامتحانات</span>
                </div>
            </div>

            <!-- Center: DTC Crest & Main Title -->
            <div class="col-span-4 flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#fff7ed] to-[#fef3c7] flex items-center justify-center shadow-inner mb-1.5">
                    <span class="material-symbols-outlined text-[34px] text-[#d97706]">school</span>
                </div>
                <h1 class="text-[20px] font-bold text-[#0f172a] tracking-tight">البرنامج الأسبوعي للمحاضرات والدروس العملية</h1>
                <div class="inline-flex items-center gap-2 mt-1 px-3 py-0.5 bg-[#f8fafc] rounded-full">
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
                    <span class="font-mono text-[10px] text-[#64748b] tracking-wider mt-0.5">REF: DTC-SCHED-2026-{{ strtoupper($student->program_id ? 'CIS' : 'IT') }}-Y1</span>
                    <span class="text-[10px] text-[#94a3b8]">تاريخ السحب: {{ now()->format('d/m/Y - h:i') }} {{ now()->format('A') == 'AM' ? 'ص' : 'م' }}</span>
                    <span class="inline-block mt-1 text-[9px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md font-semibold">وثيقة رسمية معتمدة ومحققة آلياً</span>
                </div>
                <!-- Verification QR Block -->
                <div class="w-16 h-16 shrink-0 bg-[#f8fafc] p-1 rounded-lg shadow-sm flex items-center justify-center relative">
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

    <!-- 2. STUDENT ACADEMIC IDENTITY CARD (Modified as requested: Removed Hall & Lab Allocation card) -->
    <section class="w-full bg-[#f8fafc] rounded-xl p-4 mb-5">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 text-right">
            
            <!-- Student Name -->
            <div class="col-span-2 bg-white rounded-lg p-2.5 shadow-xs">
                <span class="block text-[10px] text-[#64748b]">اسم الطالب الرباعي:</span>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="material-symbols-outlined text-[18px] text-[#d97706]">person</span>
                    <span class="text-[14px] font-bold text-[#0f172a]">{{ $studentName }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 bg-[#fef3c7] text-[#92400e] font-bold rounded">انتظام</span>
                </div>
            </div>

            <!-- University ID -->
            <div class="bg-white rounded-lg p-2.5 shadow-xs">
                <span class="block text-[10px] text-[#64748b]">الرقم الأكاديمي:</span>
                <span class="font-mono text-[13px] font-bold text-[#005a9c] mt-0.5 block">{{ $studentId }}</span>
            </div>

            <!-- Academic Program -->
            <div class="col-span-2 bg-white rounded-lg p-2.5 shadow-xs">
                <span class="block text-[10px] text-[#64748b]">القسم والتخصص:</span>
                <span class="text-[12px] font-bold text-[#0f172a] mt-0.5 block truncate">
                    {{ $deptName }} ({{ $progName }})
                </span>
            </div>

            <!-- Division & Level -->
            <div class="bg-white rounded-lg p-2.5 shadow-xs">
                <span class="block text-[10px] text-[#64748b]">المستوى والشعبة:</span>
                <span class="text-[12px] font-bold text-[#1e293b] mt-0.5 block">{{ $levelName }} • شعبة (A)</span>
            </div>

            <!-- Academic Advisor (Expanded to col-span-2 for balance) -->
            <div class="col-span-2 bg-white rounded-lg p-2.5 shadow-xs">
                <span class="block text-[10px] text-[#64748b]">المرشد الأكاديمي:</span>
                <span class="text-[12px] font-semibold text-[#334155] mt-0.5 block">{{ $advisorTeacher['name'] ?? 'أ. جمال العمري' }}</span>
            </div>

            <!-- Head of Department (Expanded to col-span-2 for balance) -->
            <div class="col-span-2 bg-white rounded-lg p-2.5 shadow-xs">
                <span class="block text-[10px] text-[#64748b]">رئيس القسم:</span>
                <span class="text-[12px] font-semibold text-[#334155] mt-0.5 block">د. أحمد ديب</span>
            </div>

            <!-- Validity Period (col-span-2) -->
            <div class="col-span-2 bg-white rounded-lg p-2.5 shadow-xs">
                <span class="block text-[10px] text-[#64748b]">تاريخ سريان ونفاذ البرنامج:</span>
                <div class="flex items-center gap-1 mt-0.5 text-[11px] text-[#047857] font-semibold">
                    <span class="material-symbols-outlined text-[16px]">event_available</span>
                    <span>بدءاً من الفصل الحالي وحتى نهاية العام الأكاديمي</span>
                </div>
            </div>

        </div>
    </section>

    <!-- 3. WEEKLY TIMETABLE GRID (DYNAMICALLY POPULATED WITH REAL STUDENT DATA) -->
    <section class="w-full mb-5 overflow-hidden rounded-xl bg-white shadow-sm border border-[#e2e8f0]">
        <div class="w-full overflow-x-auto">
            <table class="w-full border-collapse text-right table-fixed min-w-[1040px]">
                <thead>
                    <tr class="bg-[#0f172a] text-white">
                        <th class="w-[12%] p-3 text-[12px] text-center font-bold tracking-wider">اليوم / التوقيت</th>
                        <th class="w-[22%] p-3 text-[12px] text-center font-bold">
                            <span class="block">الفترة الأولى</span>
                            <span class="font-mono text-[10px] font-normal text-amber-300">08:00 ص - 09:30 ص</span>
                        </th>
                        <th class="w-[22%] p-3 text-[12px] text-center font-bold">
                            <span class="block">الفترة الثانية</span>
                            <span class="font-mono text-[10px] font-normal text-amber-300">09:30 ص - 11:00 ص</span>
                        </th>
                        <th class="w-[6%] p-2 text-[10px] text-center bg-[#1e293b] text-amber-400 font-bold">
                            <span class="[writing-mode:vertical-rl] rotate-180 inline-block py-1 tracking-widest leading-none">
                                استراحة 11:00 - 11:30
                            </span>
                        </th>
                        <th class="w-[20%] p-3 text-[12px] text-center font-bold">
                            <span class="block">الفترة الثالثة</span>
                            <span class="font-mono text-[10px] font-normal text-amber-300">11:30 ص - 01:00 م</span>
                        </th>
                        <th class="w-[18%] p-3 text-[12px] text-center font-bold">
                            <span class="block">الفترة الرابعة</span>
                            <span class="font-mono text-[10px] font-normal text-amber-300">01:00 م - 02:30 م</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#e2e8f0]">
                    @foreach($dayOrder as $enDay => $dayMeta)
                        @php
                            $dayLectures = $byDay[$enDay] ?? [];
                            $lecCount = count($dayLectures);
                            $slot1 = $dayLectures[0] ?? null;
                            $slot2 = $dayLectures[1] ?? null;
                            $slot3 = $dayLectures[2] ?? null;
                            $slot4 = $dayLectures[3] ?? null;
                        @endphp
                        <tr class="hover:bg-[#fbfcfe] transition-colors">
                            <!-- Day Label -->
                            <td class="p-3 bg-[#f8fafc] text-center align-middle text-[14px] font-bold text-[#0f172a]">
                                {{ $dayMeta['ar'] }}
                                <span class="block text-[10px] text-[#64748b] font-normal">
                                    {{ $lecCount > 0 ? "($lecCount حصص)" : 'نشاط ذاتي' }}
                                </span>
                            </td>

                            <!-- Slot 1 -->
                            <td class="p-2 align-top">
                                @if($slot1)
                                    @php
                                        $cTitle = $slot1->course_title ?? ($slot1->course->title ?? 'مقرر دراسي');
                                        $cCode = $slot1->course_code ?? ($slot1->course->code ?? 'CS' . rand(101, 109));
                                        $tName = $slot1->teacher_name ?? 'هيئة التدريس';
                                        $room = $slot1->room ?? 'قاعة A1';
                                        $isLab = str_contains($cTitle, 'مخبر') || str_contains($cTitle, 'عملي') || str_contains($room, 'مخبر');
                                    @endphp
                                    <div class="h-full rounded-lg p-2.5 {{ $isLab ? 'bg-gradient-to-br from-[#f0fdf4] to-[#dcfce7] text-[#14532d]' : 'bg-gradient-to-br from-[#eff6ff] to-[#dbeafe] text-[#1e3a8a]' }} flex flex-col justify-between shadow-xs">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded {{ $isLab ? 'bg-emerald-200 text-emerald-950' : 'bg-blue-200 text-blue-900' }}">{{ $cCode }}</span>
                                                <span class="text-[10px] {{ $isLab ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white' }} px-2 py-0.2 rounded-full font-bold">{{ $isLab ? 'مخبر عملي' : 'نظري' }}</span>
                                            </div>
                                            <h4 class="text-[12px] font-bold {{ $isLab ? 'text-[#064e3b]' : 'text-[#0f172a]' }} leading-snug">{{ $cTitle }}</h4>
                                        </div>
                                        <div class="mt-2 pt-1.5 border-t {{ $isLab ? 'border-emerald-200/60 text-[#14532d]' : 'border-blue-200/60 text-[#334155]' }} flex items-center justify-between text-[10px]">
                                            <span class="font-medium">{{ $tName }}</span>
                                            <span class="font-bold flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">{{ $isLab ? 'computer' : 'location_on' }}</span>{{ $room }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full rounded-lg p-2 bg-[#f8fafc] flex flex-col justify-center items-center text-center text-[#94a3b8] min-h-[76px]">
                                        <span class="material-symbols-outlined text-[18px] mb-0.5">menu_book</span>
                                        <span class="text-[11px] font-bold text-[#475569]">دراسة وتوجيه ذاتي</span>
                                        <span class="text-[9px] text-[#94a3b8]">مراجعة المقررات الأكاديمية</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Slot 2 -->
                            <td class="p-2 align-top">
                                @if($slot2)
                                    @php
                                        $cTitle = $slot2->course_title ?? ($slot2->course->title ?? 'مقرر دراسي');
                                        $cCode = $slot2->course_code ?? ($slot2->course->code ?? 'CS' . rand(110, 120));
                                        $tName = $slot2->teacher_name ?? 'هيئة التدريس';
                                        $room = $slot2->room ?? 'قاعة A2';
                                        $isLab = str_contains($cTitle, 'مخبر') || str_contains($cTitle, 'عملي') || str_contains($room, 'مخبر');
                                    @endphp
                                    <div class="h-full rounded-lg p-2.5 {{ $isLab ? 'bg-gradient-to-br from-[#f0fdf4] to-[#dcfce7] text-[#14532d]' : 'bg-gradient-to-br from-[#eff6ff] to-[#dbeafe] text-[#1e3a8a]' }} flex flex-col justify-between shadow-xs">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded {{ $isLab ? 'bg-emerald-200 text-emerald-950' : 'bg-blue-200 text-blue-900' }}">{{ $cCode }}</span>
                                                <span class="text-[10px] {{ $isLab ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white' }} px-2 py-0.2 rounded-full font-bold">{{ $isLab ? 'مخبر عملي' : 'نظري' }}</span>
                                            </div>
                                            <h4 class="text-[12px] font-bold {{ $isLab ? 'text-[#064e3b]' : 'text-[#0f172a]' }} leading-snug">{{ $cTitle }}</h4>
                                        </div>
                                        <div class="mt-2 pt-1.5 border-t {{ $isLab ? 'border-emerald-200/60 text-[#14532d]' : 'border-blue-200/60 text-[#334155]' }} flex items-center justify-between text-[10px]">
                                            <span class="font-medium">{{ $tName }}</span>
                                            <span class="font-bold flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">{{ $isLab ? 'computer' : 'location_on' }}</span>{{ $room }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full rounded-lg p-2 bg-[#f8fafc] flex flex-col justify-center items-center text-center text-[#94a3b8] min-h-[76px]">
                                        <span class="material-symbols-outlined text-[18px] mb-0.5">groups</span>
                                        <span class="text-[11px] font-bold text-[#475569]">نشاط طلابي / حر</span>
                                        <span class="text-[9px] text-[#94a3b8]">جلسة مكتبية وبحث</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Break Spacer -->
                            <td class="bg-[#f8fafc] text-center p-1 text-[11px] text-[#94a3b8] font-bold align-middle">
                                ☕
                            </td>

                            <!-- Slot 3 -->
                            <td class="p-2 align-top">
                                @if($slot3)
                                    @php
                                        $cTitle = $slot3->course_title ?? ($slot3->course->title ?? 'مقرر دراسي');
                                        $cCode = $slot3->course_code ?? ($slot3->course->code ?? 'IT' . rand(201, 210));
                                        $tName = $slot3->teacher_name ?? 'هيئة التدريس';
                                        $room = $slot3->room ?? 'مخبر 3';
                                        $isLab = str_contains($cTitle, 'مخبر') || str_contains($cTitle, 'عملي') || str_contains($room, 'مخبر');
                                    @endphp
                                    <div class="h-full rounded-lg p-2.5 {{ $isLab ? 'bg-gradient-to-br from-[#f0fdf4] to-[#dcfce7] text-[#14532d]' : 'bg-gradient-to-br from-[#eff6ff] to-[#dbeafe] text-[#1e3a8a]' }} flex flex-col justify-between shadow-xs">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded {{ $isLab ? 'bg-emerald-200 text-emerald-950' : 'bg-blue-200 text-blue-900' }}">{{ $cCode }}</span>
                                                <span class="text-[10px] {{ $isLab ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white' }} px-2 py-0.2 rounded-full font-bold">{{ $isLab ? 'مخبر عملي' : 'نظري' }}</span>
                                            </div>
                                            <h4 class="text-[12px] font-bold {{ $isLab ? 'text-[#064e3b]' : 'text-[#0f172a]' }} leading-snug">{{ $cTitle }}</h4>
                                        </div>
                                        <div class="mt-2 pt-1.5 border-t {{ $isLab ? 'border-emerald-200/60 text-[#14532d]' : 'border-blue-200/60 text-[#334155]' }} flex items-center justify-between text-[10px]">
                                            <span class="font-medium">{{ $tName }}</span>
                                            <span class="font-bold flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">{{ $isLab ? 'computer' : 'location_on' }}</span>{{ $room }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full rounded-lg p-2 bg-[#f8fafc] flex flex-col justify-center items-center text-center text-[#94a3b8] min-h-[76px]">
                                        <span class="material-symbols-outlined text-[18px] mb-0.5">terminal</span>
                                        <span class="text-[11px] font-bold text-[#475569]">تطبيقات ومشاريع</span>
                                        <span class="text-[9px] text-[#94a3b8]">عمل وتطوير مستقل</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Slot 4 -->
                            <td class="p-2 align-top">
                                @if($slot4)
                                    @php
                                        $cTitle = $slot4->course_title ?? ($slot4->course->title ?? 'مقرر دراسي');
                                        $cCode = $slot4->course_code ?? ($slot4->course->code ?? 'WD' . rand(201, 210));
                                        $tName = $slot4->teacher_name ?? 'هيئة التدريس';
                                        $room = $slot4->room ?? 'قاعة A1';
                                        $isLab = str_contains($cTitle, 'مخبر') || str_contains($cTitle, 'عملي') || str_contains($room, 'مخبر');
                                    @endphp
                                    <div class="h-full rounded-lg p-2.5 {{ $isLab ? 'bg-gradient-to-br from-[#f0fdf4] to-[#dcfce7] text-[#14532d]' : 'bg-gradient-to-br from-[#eff6ff] to-[#dbeafe] text-[#1e3a8a]' }} flex flex-col justify-between shadow-xs">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded {{ $isLab ? 'bg-emerald-200 text-emerald-950' : 'bg-blue-200 text-blue-900' }}">{{ $cCode }}</span>
                                                <span class="text-[10px] {{ $isLab ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white' }} px-2 py-0.2 rounded-full font-bold">{{ $isLab ? 'مخبر عملي' : 'نظري' }}</span>
                                            </div>
                                            <h4 class="text-[12px] font-bold {{ $isLab ? 'text-[#064e3b]' : 'text-[#0f172a]' }} leading-snug">{{ $cTitle }}</h4>
                                        </div>
                                        <div class="mt-2 pt-1.5 border-t {{ $isLab ? 'border-emerald-200/60 text-[#14532d]' : 'border-blue-200/60 text-[#334155]' }} flex items-center justify-between text-[10px]">
                                            <span class="font-medium">{{ $tName }}</span>
                                            <span class="font-bold flex items-center gap-0.5"><span class="material-symbols-outlined text-[13px]">{{ $isLab ? 'computer' : 'location_on' }}</span>{{ $room }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full rounded-lg p-2 bg-[#f8fafc] flex flex-col justify-center items-center text-center text-[#94a3b8] min-h-[76px]">
                                        <span class="material-symbols-outlined text-[18px] mb-0.5">assignment_turned_in</span>
                                        <span class="text-[11px] font-bold text-[#475569]">مراجعة واستشارة</span>
                                        <span class="text-[9px] text-[#94a3b8]">متابعة المهام الأكاديمية</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <!-- 4. SCHEDULE SUMMARY & REGULATORY INSTRUCTION STRIP (Modified: Removed Minimum Attendance Rule card) -->
    <section class="w-full bg-[#f8fafc] rounded-xl p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
            
            <!-- Metric 1: Credit Hours (Expanded to 6 columns) -->
            <div class="md:col-span-5 bg-white rounded-lg p-3 flex items-center gap-3 shadow-xs">
                <div class="w-10 h-10 rounded-lg bg-[#fff7ed] flex items-center justify-center text-[#d97706]">
                    <span class="material-symbols-outlined text-[22px]">timer</span>
                </div>
                <div>
                    <span class="block text-[10px] text-[#64748b]">إجمالي الساعات الأسبوعية المعتمدة</span>
                    <span class="text-[13px] font-bold text-[#0f172a]">
                        19 ساعة معتمدة <span class="font-normal text-[11px] text-[#64748b]">(12 نظري + 7 مخابر وعملي)</span>
                    </span>
                </div>
            </div>

            <!-- Official Warning Notice (Expanded to 7 columns to fill width) -->
            <div class="md:col-span-7 bg-amber-50/80 rounded-lg p-3 flex items-center gap-2 text-[#92400e]">
                <span class="material-symbols-outlined text-[20px] shrink-0 text-[#d97706]">notification_important</span>
                <p class="text-[11px] leading-relaxed">
                    <strong class="font-bold">تنبيه إداري ملزم:</strong> يجب التواجد بالقاعات والمخابر قبل 5 دقائق من الموعد المحدد. يُمنع التبديل بين الشعب إلا بموافقة خطية من رئاسة القسم.
                </p>
            </div>

        </div>
    </section>

    <!-- 5. INSTITUTIONAL SEALS & DEPARTMENT SIGN-OFF BLOCK -->
    <footer class="w-full pt-4 border-t border-[#e2e8f0]">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end text-center">
            
            <!-- Approval: Head of Department -->
            <div class="flex flex-col items-center">
                <span class="text-[11px] text-[#64748b]">رئيس قسم هندسة وتكنولوجيا المعلومات</span>
                <span class="text-[13px] font-bold text-[#0f172a] mt-1">د. أحمد ديب</span>
                <!-- Stylized Signature Glyphs -->
                <div class="h-10 w-32 flex items-center justify-center text-[#1e293b] my-1 opacity-70">
                    <svg class="h-8 w-28" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 120 40">
                        <path d="M10 28 C 30 10, 45 40, 60 18 S 90 2, 105 25" stroke-linecap="round"></path>
                        <path d="M40 22 Q 55 12 75 32" stroke-linecap="round"></path>
                    </svg>
                </div>
                <span class="font-mono text-[9px] text-[#94a3b8]">اعتماد رقم: DEP-CIS-992</span>
            </div>

            <!-- Official Seal Stamp (DTC Circle Stamp) -->
            <div class="flex flex-col items-center justify-center">
                <div class="relative w-28 h-28 rounded-full border-[2.5px] border-dashed border-[#005a9c] flex flex-col items-center justify-center p-2 text-[#005a9c] bg-[#f0f9ff]/40 shadow-xs">
                    <div class="absolute inset-1 rounded-full border border-[#005a9c]/30 pointer-events-none"></div>
                    <span class="material-symbols-outlined text-[20px] text-[#005a9c]">verified</span>
                    <span class="text-[9px] font-bold text-center leading-tight mt-0.5">معهد دمشق المتوسط</span>
                    <span class="font-mono text-[8px] tracking-wider text-[#0284c7]">DAMASCUS TRAINING CENTRE</span>
                    <span class="text-[8px] font-semibold mt-0.5 text-center">شؤون الطلاب والامتحانات</span>
                    <span class="font-mono text-[8px] text-[#005a9c] mt-0.5 font-bold">★ 2025 - 2026 ★</span>
                </div>
                <span class="text-[10px] text-[#047857] font-semibold mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[13px]">check_circle</span> مصدق أصولاً
                </span>
            </div>

            <!-- Approval: Student Affairs Director -->
            <div class="flex flex-col items-center">
                <span class="text-[11px] text-[#64748b]">مدير شؤون الطلاب والامتحانات</span>
                <span class="text-[13px] font-bold text-[#0f172a] mt-1">أ. نضال منصور</span>
                <div class="h-10 w-32 flex items-center justify-center text-[#1e293b] my-1 opacity-70">
                    <svg class="h-8 w-28" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 120 40">
                        <path d="M15 25 C 25 5, 50 35, 75 15 S 100 28, 110 12" stroke-linecap="round"></path>
                        <path d="M60 28 L 85 28" stroke-linecap="round"></path>
                    </svg>
                </div>
                <span class="font-mono text-[9px] text-[#94a3b8]">اعتماد رقم: REG-STU-4011</span>
            </div>

        </div>

        <!-- Bottom Footer Reference Strip -->
        <div class="mt-4 pt-3 border-t border-[#f1f5f9] flex flex-wrap items-center justify-between text-[10px] text-[#64748b]">
            <div class="flex items-center gap-2">
                <span class="font-bold text-[#005a9c]">منظومة جسر التعليم Edu-Bridge • DTC</span>
                <span>تم استخراج الوثيقة إلكترونياً وتعتبر نافذة دون الحاجة لتوقيع يدوي إضافي عند التحقق الرقمي.</span>
            </div>
            <div class="font-mono text-[9px] text-[#94a3b8]">
                SYSTEM ID: EB-DOC-{{ date('Ymd') }}-{{ rand(100000, 999999) }}-A
            </div>
        </div>
    </footer>

</article>

</body>
</html>
