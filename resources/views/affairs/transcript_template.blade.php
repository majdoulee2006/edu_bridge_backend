<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>كشف درجات وسجل أكاديمي موحد معتمد - {{ $student->full_name }}</title>
    <!-- Early theme detection to avoid flash of light mode if user prefers dark mode -->
    <script>
        (function() {
            try {
                const colorTheme = localStorage.getItem('color-theme');
                const hodSettings = JSON.parse(localStorage.getItem('hodSettings') || '{}');
                const savedTheme = colorTheme || hodSettings.theme || localStorage.getItem('theme');
                const isSystemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = savedTheme ? savedTheme : (isSystemDark ? 'dark' : 'light');
                
                if (theme === 'light') {
                    document.documentElement.setAttribute('data-theme', 'light');
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    document.documentElement.classList.add('dark');
                }
            } catch(e) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <!-- Google Fonts: Cairo for crisp official Arabic typography -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet">
    <!-- Tailwind CSS v3 -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                    },
                    colors: {
                        dtc: {
                            gold: '#EAB308',
                            goldDark: '#CA8A04',
                            goldLight: '#FEF08A',
                            cyan: '#0284c7',
                            navy: '#0f172a',
                            emerald: '#10B981',
                            emeraldDark: '#059669',
                            slateBg: '#F8FAFC',
                            border: '#E2E8F0',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Stylesheet for Print and Page Layout -->
    <style data-purpose="print-and-page-setup">
        @page {
            size: A4 portrait;
            margin: 6mm;
        }
        @media print {
            html, body {
                background-color: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .page-sheet {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 3mm 4mm !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                min-height: 283mm !important;
                height: 283mm !important;
                box-sizing: border-box !important;
            }
            .page-sheet:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
        }
        body {
            font-family: 'Cairo', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        /* الورقة الرسمية حصراً بنمط نهاري معتمد في كلا الوضعين (النهاري والليلي) */
        .page-sheet {
            background-color: #ffffff !important;
            color: #0f172a !important;
            color-scheme: light !important;
        }
        .page-sheet * {
            color-scheme: light !important;
        }
        /* معاينة الشاشة: تثبيت أبعاد ورقة A4 Portrait وضمان بدء التمرير من اليمين بدون اقتطاع */
        @media screen {
            .report-viewport-wrapper {
                width: 100%;
                max-width: 100vw;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                display: flex;
                flex-direction: column;
                align-items: flex-start; /* يبدأ العرض من اليمين دائماً */
                padding: 0 0 2rem 0;
            }
            @media (min-width: 850px) {
                .report-viewport-wrapper {
                    align-items: center;
                }
            }
            .page-sheet {
                width: 210mm !important;
                min-width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                box-sizing: border-box !important;
                margin: 0;
            }
            @media (min-width: 850px) {
                .page-sheet {
                    margin: 0 auto;
                }
            }
        }
        /* تثبيت أبعاد خلايا الجدول طولياً وعرضياً لمنع التشويه البصري */
        .fixed-report-table {
            table-layout: fixed !important;
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .fixed-report-table th,
        .fixed-report-table td {
            height: 29px !important;
            max-height: 29px !important;
            min-height: 29px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }
        .fixed-report-table tr {
            height: 29px !important;
            max-height: 29px !important;
        }
        .watermark-bg {
            background-image: radial-gradient(circle at center, rgba(234, 179, 8, 0.03) 0%, transparent 70%);
        }
    </style>
</head>
<body class="bg-slate-100 dark:bg-[#070a13] min-h-screen text-slate-800 dark:text-slate-100 antialiased py-4 sm:py-6 px-1.5 sm:px-3 flex flex-col items-center justify-start transition-colors duration-200">

    <!-- Top Action Toolbar (مرن يتبع ثيم الواجهة ومتجاوب على الموبايل) -->
    <nav class="no-print w-full max-w-[210mm] mb-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-[#0f172a] p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors" data-purpose="top-action-bar">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 px-3 py-1.5 rounded-xl text-xs font-bold">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>وثيقة معتمدة ومطابقة رقمياً بالسجل الأكاديمي المركزي</span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500 font-medium hidden md:inline">DTC-{{ $student->student_code ?? '2026' }}-V9</span>
            <!-- زر تبديل الثيم للموبايل -->
            <button onclick="toggleTheme()" class="sm:hidden flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-amber-400 border border-slate-200 dark:border-slate-700" title="تبديل المظهر" type="button">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
            </button>
        </div>
        <div class="flex items-center gap-2">
            <!-- Theme Toggle Button (Desktop) -->
            <button id="themeToggleBtn" onclick="toggleTheme()" class="hidden sm:flex items-center justify-center w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-amber-400 border border-slate-200 dark:border-slate-700 transition shadow-xs" title="تبديل المظهر (ليلي / نهاري)" type="button">
                <svg id="themeIconSun" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <svg id="themeIconMoon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
            </button>
            <!-- Back to Workstation Button -->
            <button class="flex-1 sm:flex-initial flex items-center justify-center gap-1.5 text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition" onclick="window.close(); if(window.opener){window.opener.focus();}" type="button">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                <span>العودة للمسار الأكاديمي</span>
            </button>

            <!-- Print / PDF Button -->
            <button class="flex-1 sm:flex-initial flex items-center justify-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 border border-amber-500 transition shadow-md whitespace-nowrap" onclick="window.print()" type="button">
                <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                <span>طباعة السجل (PDF)</span>
            </button>
        </div>
    </nav>

    <!-- تنبيه إرشادي ذكي لمستخدمي الهواتف الذكية -->
    <div class="no-print w-full max-w-[210mm] mb-4 sm:hidden px-3.5 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-900 dark:text-amber-200 text-xs flex items-center gap-2.5 shadow-xs">
        <span class="text-base select-none">📱</span>
        <span class="leading-relaxed"><strong>ملاحظة للموبايل:</strong> يمكنك التمرير أفقياً لرؤية كشف العلامات بكامل تفاصيله ودرجاته بحجم <strong>A4 طولي</strong> المعتمد.</span>
    </div>

    @php
        $totalCourses = count($coursesList);
        $coursesPages = [];
        
        if ($totalCourses === 0) {
            $coursesPages = [[]];
        } elseif ($totalCourses <= 8) {
            // صفحة واحدة تتسع للمقررات حتى 8 مع بطاقة القرار والتواقيع الثلاثة والختم
            $coursesPages = [$coursesList];
        } else {
            // الصفحة الأولى تأخذ حتى 10 مقررات بارتياح مع التواقيع والختم
            $coursesPages[] = array_slice($coursesList, 0, 10);
            $offset = 10;
            $remaining = $totalCourses - $offset;
            
            while ($remaining > 0) {
                // إذا كان المتبقي يتسع مع بطاقة القرار والتواقيع في الصفحة الأخيرة (حتى 7 مقررات)
                if ($remaining <= 7) {
                    $coursesPages[] = array_slice($coursesList, $offset, $remaining);
                    break;
                }
                if ($remaining <= 9) {
                    $take = (int) ceil($remaining / 2);
                    $coursesPages[] = array_slice($coursesList, $offset, $take);
                    $offset += $take;
                    $remaining -= $take;
                    $coursesPages[] = array_slice($coursesList, $offset, $remaining);
                    break;
                }
                $take = 10;
                $coursesPages[] = array_slice($coursesList, $offset, $take);
                $offset += $take;
                $remaining -= $take;
            }
        }
        
        $totalPages = count($coursesPages);
        
        // حساب إزاحة ترقيم المقررات عبر الصفحات
        $pageOffsets = [];
        $runningOffset = 0;
        foreach ($coursesPages as $pIdx => $pList) {
            $pageOffsets[$pIdx] = $runningOffset;
            $runningOffset += count($pList);
        }
    @endphp

    <!-- A4 Printable Document Container (مع حاوية تمرير أفقية تضمن الحجم الطبيعي للورقة على كافة الشاشات) -->
    <div class="report-viewport-wrapper w-full">
        <div class="w-[210mm] min-w-[210mm] max-w-[210mm] flex flex-col gap-8 print:w-full print:min-w-0 print:max-w-none print:gap-0 print:p-0">
        @foreach($coursesPages as $pageIndex => $pageCourses)
        <main class="page-sheet relative w-full bg-white border border-slate-300 rounded-lg shadow-xl p-6 print:p-3 overflow-hidden watermark-bg flex flex-col justify-between" id="academic-sheet-{{ $pageIndex }}" data-purpose="academic-sheet-root">
            <!-- Decorative Watermark Seal -->
            <div class="absolute inset-0 flex items-center justify-center opacity-[0.035] pointer-events-none select-none z-0">
                <img alt="EduBridge Watermark" class="w-[450px] h-auto object-contain filter grayscale" src="{{ $watermarkBase64 }}">
            </div>

            <!-- Content Layer -->
            <div class="relative z-10 flex flex-col justify-between h-full">
                
                <!-- UPPER SECTION: Recurring Official Header & Student Identity (ثابت بكل ورقة طبقاً للصورة 3) -->
                <div class="flex flex-col gap-3">
                    <!-- Official Institutional Header -->
                    <header class="border-b-2 border-slate-800 pb-3" data-purpose="official-header">
                        <div class="grid grid-cols-12 items-center gap-3">
                            <!-- Right Column: Institutional Hierarchy -->
                            <div class="col-span-4 text-right text-[10.5px] leading-relaxed font-bold text-slate-700">
                                <p class="text-slate-900 font-extrabold text-xs">الجمهورية العربية السورية</p>
                                <p>وكالة الأمم المتحدة لإغاثة وتشغيل اللاجئين (UNRWA)</p>
                                <p>دائرة التربية والتعليم المهني والتقني - دمشق</p>
                                <p class="text-amber-700 font-black text-xs">معهد دمشق المتوسط (DTC)</p>
                                <p class="text-slate-500 font-semibold text-[10px]">شؤون الطلاب والامتحانات والكنترول الأكاديمي</p>
                            </div>
                            <!-- Middle Column: Emblem & Main Title -->
                            <div class="col-span-4 flex flex-col items-center justify-center text-center">
                                <div class="w-14 h-14 mb-1 p-1 rounded-full border border-sky-300 bg-sky-50 flex items-center justify-center shadow-xs">
                                    <img alt="DTC Logo" class="w-full h-full object-contain rounded-full" src="{{ $dtcLogoBase64 }}">
                                </div>
                                <h1 class="text-[14px] font-black text-slate-900 tracking-tight leading-snug">
                                    كشف درجات وسجل أكاديمي موحد معتمد
                                </h1>
                                <p class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full mt-0.5">
                                    العام الدراسي {{ $academicYearLabel }} | الدورة الرئيسية
                                </p>
                            </div>
                            <!-- Left Column: Verification & Barcode -->
                            <div class="col-span-4 flex flex-col items-end text-left">
                                <div class="border border-slate-300 rounded-md p-1.5 bg-slate-50 text-[9.5px] space-y-0.5 w-48 text-right">
                                    <div class="flex justify-between items-center text-slate-500 border-b border-slate-200 pb-0.5 font-semibold">
                                        <span>رقم السجل الموحد:</span>
                                        <span class="font-mono text-slate-900 font-bold">DTC-{{ $student->student_code ?? '2026' }}-REG</span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-500">
                                        <span>تاريخ التوليد:</span>
                                        <span class="text-slate-800 font-medium">{{ $issueDateFormatted }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-500">
                                        <span>حالة الوثيقة:</span>
                                        <span class="text-emerald-700 font-bold bg-emerald-100 px-1 rounded text-[9px]">معتمدة رسمياً</span>
                                    </div>
                                    <!-- Miniature Verification Barcode Representation -->
                                    <div class="pt-0.5 flex items-center justify-center">
                                        <div class="w-full bg-white border border-slate-300 py-0.5 px-2 rounded flex flex-col items-center">
                                            <div class="flex items-center gap-[2px] h-5 w-36 justify-center">
                                                <span class="w-[2px] h-full bg-black"></span>
                                                <span class="w-[1px] h-full bg-black"></span>
                                                <span class="w-[3px] h-full bg-black"></span>
                                                <span class="w-[1px] h-full bg-black"></span>
                                                <span class="w-[2px] h-full bg-black"></span>
                                                <span class="w-[4px] h-full bg-black"></span>
                                                <span class="w-[1px] h-full bg-black"></span>
                                                <span class="w-[2px] h-full bg-black"></span>
                                                <span class="w-[1px] h-full bg-black"></span>
                                                <span class="w-[3px] h-full bg-black"></span>
                                                <span class="w-[2px] h-full bg-black"></span>
                                                <span class="w-[1px] h-full bg-black"></span>
                                                <span class="w-[4px] h-full bg-black"></span>
                                                <span class="w-[2px] h-full bg-black"></span>
                                            </div>
                                            <span class="font-mono text-[7.5px] tracking-widest text-slate-600 mt-0.5">*DTC-VERIFIED-{{ $student->student_code ?? '2026' }}*</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Student Academic Identity Card (ثابت في بداية كل ورقة طبقاً للصورة 3) -->
                    <section class="bg-slate-50 border border-slate-300 rounded-lg p-2.5" data-purpose="student-identity-block">
                        <div class="flex flex-wrap items-center justify-between pb-1.5 mb-1.5 border-b border-slate-200">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-full bg-amber-400 text-slate-900 font-black flex items-center justify-center text-xs shadow-xs border border-amber-500">
                                    {{ $studentInitials }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-xs font-extrabold text-slate-900">{{ $student->full_name }}</h2>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-medium">الرقم الجامعي الأكاديمي: <strong class="text-slate-800 font-mono text-[11px]">{{ $student->student_code ?? '-' }}</strong> | تاريخ الالتحاق: <span class="font-mono">{{ $joinedAtFormatted }}</span></p>
                                </div>
                            </div>
                            <div class="text-left">
                                <span class="inline-block text-[9.5px] font-bold text-slate-600 bg-white border border-slate-300 px-2 py-0.5 rounded">نطاق التقرير: كافة الفصول ({{ $student->level }})</span>
                            </div>
                        </div>

                        <!-- Structured Academic Metrics Grid -->
                        <div class="grid grid-cols-4 gap-2 text-[10.5px]">
                            <div class="bg-white p-1.5 rounded border border-slate-200">
                                <span class="block text-slate-400 text-[9px] font-bold mb-0.5">القسم الأكاديمي العام</span>
                                <span class="font-extrabold text-slate-800 truncate block">{{ $student->department_name ?? 'عام' }}</span>
                            </div>
                            <div class="bg-white p-1.5 rounded border border-slate-200">
                                <span class="block text-slate-400 text-[9px] font-bold mb-0.5">الفرع / التخصص الدقيق</span>
                                <span class="font-extrabold text-amber-700 truncate block">{{ $student->program_name ?? 'عام' }}</span>
                            </div>
                            <div class="bg-white p-1.5 rounded border border-slate-200">
                                <span class="block text-slate-400 text-[9px] font-bold mb-0.5">السنة الدراسية / المستوى</span>
                                <span class="font-extrabold text-slate-800 truncate block">{{ $student->level }}</span>
                            </div>
                            <div class="bg-white p-1.5 rounded border border-slate-200">
                                <span class="block text-slate-400 text-[9px] font-bold mb-0.5">الخطة الدراسية المعتمدة</span>
                                <span class="font-bold text-slate-700 font-mono truncate block">{{ $planCode }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- MIDDLE SECTION: Page Course Breakdown Content -->
                <div class="flex flex-col gap-2 my-2 flex-1 justify-start">
                    <div class="flex items-center justify-between">
                        <h3 class="text-[11px] font-black text-slate-800 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-amber-500 inline-block"></span>
                            @if($loop->first)
                                <span>جدول المقررات الدراسية وتثقيل الساعات وعلامات التقييم المعتمدة</span>
                            @else
                                <span>تابع جدول المقررات الدراسية وتثقيل الساعات (تكملة السجل الأكاديمي)</span>
                            @endif
                        </h3>
                        <span class="text-[9.5px] font-semibold text-slate-500">نظام القياس المعتمد: المئوي والنقاط الموزونة (Weighted Scale)</span>
                    </div>

                    <div class="border border-slate-300 rounded-lg overflow-hidden">
                        <table class="fixed-report-table w-full text-right border-collapse text-[10px]">
                            <colgroup>
                                <col style="width: 4.5%;"> <!-- # -->
                                <col style="width: 31.5%;">  <!-- اسم المقرر الدراسي -->
                                <col style="width: 9.5%;"> <!-- الفصل -->
                                <col style="width: 5.5%;"> <!-- الساعات -->
                                <col style="width: 6%;">   <!-- المذاكرة -->
                                <col style="width: 6%;">   <!-- الامتحان -->
                                <col style="width: 6%;">   <!-- الشفهي -->
                                <col style="width: 7%;">   <!-- المجموع -->
                                <col style="width: 7%;">   <!-- النقاط -->
                                <col style="width: 16.5%;">  <!-- التقدير والحالة -->
                            </colgroup>
                            <thead>
                                <tr class="bg-slate-800 text-white font-bold text-[10px]">
                                    <th class="py-1 px-1 border-l border-slate-700 text-center">#</th>
                                    <th class="py-1 px-2.5 border-l border-slate-700 text-right">اسم المقرر الدراسي</th>
                                    <th class="py-1 px-1 border-l border-slate-700 text-center">الفصل</th>
                                    <th class="py-1 px-1 border-l border-slate-700 text-center">الساعات</th>
                                    <th class="py-1 px-1 border-l border-slate-700 text-center">المذاكرة (25)</th>
                                    <th class="py-1 px-1 border-l border-slate-700 text-center">الامتحان (50)</th>
                                    <th class="py-1 px-1 border-l border-slate-700 text-center">الشفهي (25)</th>
                                    <th class="py-1 px-1 border-l border-slate-700 text-center bg-slate-900">المجموع</th>
                                    <th class="py-1 px-1 border-l border-slate-700 text-center bg-amber-950 text-amber-300">النقاط</th>
                                    <th class="py-1 px-1 text-center">الحالة والتقدير</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($pageCourses as $idx => $c)
                                @php
                                    $globalIdx = $pageOffsets[$pageIndex] + $idx;
                                @endphp
                                <tr class="{{ $loop->even ? 'bg-slate-50/50' : 'bg-white' }} hover:bg-amber-50/40 transition">
                                    <td class="py-1 px-1 text-center font-mono text-slate-400 border-l border-slate-200 text-[10px]">{{ $globalIdx + 1 }}</td>
                                    <td class="py-1 px-2.5 border-l border-slate-200 font-extrabold text-slate-800 truncate" title="{{ $c['title'] }}">
                                        {{ $c['title'] }}
                                    </td>
                                    <td class="py-1 px-1 text-center border-l border-slate-200 text-slate-600 font-medium truncate">{{ $c['sem_label'] }}</td>
                                    <td class="py-1 px-1 text-center border-l border-slate-200 font-bold text-slate-800">{{ $c['weight'] }}</td>
                                    <td class="py-1 px-1 text-center border-l border-slate-200 font-mono">{{ $c['quiz_score'] }}</td>
                                    <td class="py-1 px-1 text-center border-l border-slate-200 font-mono">{{ $c['exam_score'] }}</td>
                                    <td class="py-1 px-1 text-center border-l border-slate-200 font-mono">{{ $c['oral_score'] }}</td>
                                    <td class="py-1 px-1 text-center border-l border-slate-200 font-black text-slate-900 bg-slate-50 font-mono">{{ $c['score'] }}</td>
                                    <td class="py-1 px-1 text-center border-l border-slate-200 font-black text-amber-800 bg-amber-50/60 font-mono">{{ $c['points'] }}</td>
                                    <td class="py-1 px-1.5 text-center" title="{{ $c['status_label'] }}">
                                        @if($c['is_closed'])
                                            <span class="inline-block w-full py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 whitespace-nowrap">مغلق</span>
                                        @elseif($c['is_past'])
                                            <span class="inline-block w-full py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-800 border border-blue-300 whitespace-nowrap">مجتاز سابقاً</span>
                                        @elseif($c['is_pass'])
                                            <span class="inline-block w-full py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 whitespace-nowrap">{{ $c['status_label'] }}</span>
                                        @else
                                            <span class="inline-block w-full py-0.5 rounded text-[9px] font-black bg-red-100 text-red-800 border border-red-300 whitespace-nowrap">راسب</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <!-- If Last Page: Academic Summary Totals Footer -->
                            @if($loop->last)
                            <tfoot class="bg-slate-100 font-extrabold text-slate-800 border-t-2 border-slate-400 text-[11px]">
                                <tr>
                                    <td class="py-1.5 px-3 text-left border-l border-slate-300" colspan="3">
                                        إجمالي الساعات المعتمدة والنقاط التراكمية:
                                    </td>
                                    <td class="py-1.5 px-2 text-center font-bold text-slate-900 border-l border-slate-300 font-mono">{{ $totalHours }}</td>
                                    <td class="py-1.5 px-2 text-center border-l border-slate-300 text-slate-500 font-normal text-[9.5px]" colspan="4">
                                        مجموع الأوزان المعتمدة طبقاً للائحة الامتحانية
                                    </td>
                                    <td class="py-1.5 px-2 text-center font-mono font-black text-amber-900 bg-amber-100 border-l border-slate-300">{{ number_format($totalPoints, 1) }}</td>
                                    <td class="py-1.5 px-2 text-center text-emerald-700 font-black text-[10px]"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    <!-- If Last Page: Academic Average & Progression Decision Box -->
                    @if($loop->last)
                    <section class="mt-1 bg-gradient-to-r from-amber-50/80 via-white to-amber-50/80 border-2 border-amber-300 rounded-xl p-2.5 shadow-xs" data-purpose="progression-decision-card">
                        <div class="grid grid-cols-12 gap-2.5 items-center">
                            <!-- GPA & Semester Performance Figures -->
                            <div class="col-span-5 border-l border-amber-200 pl-2.5 space-y-1">
                                @if($isYear1)
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="text-slate-600 font-semibold">معدل الفصل الأول:</span>
                                        <span class="font-mono font-bold text-slate-900">{{ number_format($currentSemGpa, 2) }}%</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="text-slate-600 font-semibold">معدل الفصل الثاني:</span>
                                        <span class="font-semibold text-amber-700 text-[9.5px]">مغلق لحين انتهاء الفصل الأول</span>
                                    </div>
                                @else
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="text-slate-600 font-semibold">الفصل الأول والثاني:</span>
                                        <span class="font-bold text-blue-700 text-[9.5px]">مجتاز بنجاح من العام الماضي</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[11px]">
                                        <span class="text-slate-600 font-semibold">معدل الفصل الثالث:</span>
                                        <span class="font-mono font-bold text-slate-900">{{ number_format($currentSemGpa, 2) }}%</span>
                                    </div>
                                @endif
                                <div class="pt-0.5 border-t border-amber-200 flex justify-between items-center text-[11.5px]">
                                    <span class="text-amber-950 font-black">المعدل التراكمي العام (GPA):</span>
                                    <div class="text-left">
                                        <span class="font-mono text-xs font-black text-slate-900 bg-amber-200/80 px-2 py-0.5 rounded border border-amber-400">{{ $cumulativeStats['cum_gpa'] }}%</span>
                                    </div>
                                </div>
                                <div class="text-left flex justify-between items-center text-[9.5px]">
                                    <span class="text-slate-500 font-semibold">المعادلة: مجموع الفصول المجتازة ÷ عددها ({{ $passedSemCount }} فصول)</span>
                                    <span class="font-black text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded">التقدير: {{ $cumulativeStats['appreciation'] }}</span>
                                </div>
                            </div>

                            <!-- Official Progression Decree / Decision Badge -->
                            <div class="col-span-7 pr-1">
                                <div class="bg-white border border-emerald-300 rounded-lg p-2 shadow-xs mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"></path></svg>
                                        </div>
                                        <div>
                                            <div class="text-[9.5px] text-emerald-700 font-bold uppercase tracking-wider">القرار الأكاديمي والإداري الرسمي النهائي:</div>
                                            <div class="text-[11.5px] font-black text-slate-900">{{ $decisionTitle }}</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Observations / Directives Note -->
                                <p class="text-[9.5px] leading-snug text-slate-600 font-medium bg-slate-50 p-1.5 rounded border border-slate-200">
                                    <strong class="text-slate-800">توجيهات لجنة الامتحانات والكنترول الأكاديمي:</strong>
                                    {{ $directivesText }}
                                </p>
                            </div>
                        </div>
                    </section>
                    @endif

                    <!-- Recurring Official Approvals, Signatures & Stamp (ثابت بكل ورقة بناءً على طلب الإدارة) -->
                    <section class="mt-1 pt-1.5 border-t border-slate-300" data-purpose="signatures-and-legal-warranty">
                        <div class="grid grid-cols-3 gap-4 text-center text-xs">
                            <!-- Pillar 1: Student Affairs Auditor -->
                            <div class="flex flex-col items-center justify-between h-[68px] border border-dashed border-slate-200 p-1 rounded bg-slate-50/40">
                                <span class="font-extrabold text-slate-800 text-[10px]">تدقيق ومطابقة شؤون الطلاب</span>
                                <div class="w-24 border-b border-slate-400 border-dotted mx-auto my-0.5"></div>
                                <span class="text-[8px] text-slate-400">التوقيع والتاريخ: ............................</span>
                            </div>
                            <!-- Pillar 2: Head of Department -->
                            <div class="flex flex-col items-center justify-between h-[68px] border border-dashed border-slate-200 p-1 rounded bg-slate-50/40">
                                <span class="font-extrabold text-slate-800 text-[10px]">رئيس قسم {{ $student->department_name ?? 'القسم الأكاديمي' }}</span>
                                <div class="w-24 border-b border-slate-400 border-dotted mx-auto my-0.5"></div>
                                <span class="text-[8px] text-slate-400">الاعتماد الأكاديمي والتوقيع: ............................</span>
                            </div>
                            <!-- Pillar 3: Dean / Institute Director & Stamp -->
                            <div class="flex flex-col items-center justify-between h-[68px] border border-slate-300 bg-amber-50/20 p-1 rounded relative overflow-hidden">
                                <span class="font-extrabold text-slate-900 text-[10px]">مدير معهد دمشق المتوسط (DTC)</span>
                                <!-- Realistic Seal Badge Simulation -->
                                <div class="relative flex items-center justify-center my-0.5">
                                    <div class="w-8 h-8 rounded-full border border-sky-600/70 border-double flex flex-col items-center justify-center text-[5.5px] text-sky-800 font-black rotate-[-12deg] bg-sky-50/40 shadow-xs">
                                        <span>خاتم رسمي</span>
                                        <span class="text-[5px]">DTC-UNRWA</span>
                                        <span>مُعتمد</span>
                                    </div>
                                </div>
                                <span class="text-[7.5px] text-slate-600 font-bold">التوقيع والخاتم الرسمي المعتمد</span>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- LOWER SECTION: Recurring Official Legal Warning & Archival Footer (ثابت بكل ورقة طبقاً للصورة 4) -->
                <footer class="mt-auto pt-2 border-t border-slate-300 flex flex-col gap-1.5">
                    <div class="bg-slate-50 border border-slate-200 rounded p-1.5 text-center text-[9px] leading-normal text-slate-600">
                        <p class="font-medium">
                            <span class="text-rose-700 font-extrabold">تنبيه قانوني رسمي:</span>
                            تعتبر هذه الوثيقة لاغية وفاقدة لأي أثر أو حجية نظامية في حال وجود أي كشط أو شطب أو تعديل، أو خلوها من التوقيع الحي والخاتم الحي المعتمد لإدارة معهد دمشق المتوسط (DTC).
                        </p>
                    </div>
                    <div class="flex items-center justify-between text-[8.5px] text-slate-500 font-mono px-1">
                        <span>المنظومة الإلكترونية المعتمدة: EduBridge Academic Management System (v4.8)</span>
                        <span>رمز التحقق المشفر (Hash): {{ $verificationHash }}</span>
                        <span class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">صفحة {{ $pageIndex + 1 }} من {{ $totalPages }}</span>
                    </div>
                </footer>

            </div>
        </main>
        @endforeach
        </div>
    </div>

    <!-- Theme synchronization and manual toggle script -->
    <script>
        function updateThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            const sun = document.getElementById('themeIconSun');
            const moon = document.getElementById('themeIconMoon');
            if (sun && moon) {
                if (isDark) {
                    sun.classList.remove('hidden');
                    moon.classList.add('hidden');
                } else {
                    sun.classList.add('hidden');
                    moon.classList.remove('hidden');
                }
            }
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            const newTheme = isDark ? 'light' : 'dark';
            if (newTheme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.setAttribute('data-theme', 'light');
            }
            try {
                localStorage.setItem('color-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                const hodSettings = JSON.parse(localStorage.getItem('hodSettings') || '{}');
                hodSettings.theme = newTheme;
                localStorage.setItem('hodSettings', JSON.stringify(hodSettings));
            } catch(e) {}
            updateThemeIcons();
        }

        document.addEventListener('DOMContentLoaded', updateThemeIcons);
        updateThemeIcons();
    </script>
</body>
</html>
