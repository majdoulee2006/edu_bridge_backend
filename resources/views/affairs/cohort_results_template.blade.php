<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>محضر وقرار نتائج الدفعة الأكاديمي والإداري الداخلي - {{ $progName }}</title>
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
    <!-- Local Fonts: Cairo (100% Offline) -->
    <link rel="stylesheet" href="{{ asset('css/fonts-local.css') }}">
    <!-- Local Tailwind CSS Engine (100% Offline) -->
    <script src="{{ asset('js/tailwind-play.js') }}"></script>
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
            size: A4 landscape;
            margin: 5mm;
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
                min-height: 198mm !important;
                height: 198mm !important;
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
        /* معاينة الشاشة: تثبيت أبعاد ورقة A4 Landscape وضمان بدء التمرير من أول اليمين بدون اقتطاع */
        @media screen {
            .report-viewport-wrapper {
                width: 100%;
                max-width: 100vw;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                display: flex;
                flex-direction: column;
                align-items: flex-start; /* يبدأ المحاذاة من بداية اتجاه RTL (اليمين) ليظهر أول الجدول كاملاً */
                padding: 0 0 2rem 0;
            }
            @media (min-width: 1250px) {
                .report-viewport-wrapper {
                    align-items: center; /* توسيط أنيق على شاشات الحاسب العريضة */
                }
            }
            .page-sheet {
                width: 297mm !important;
                min-width: 297mm !important;
                max-width: 297mm !important;
                min-height: 210mm !important;
                box-sizing: border-box !important;
                margin: 0;
            }
            @media (min-width: 1250px) {
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
            height: 31px !important;
            max-height: 31px !important;
            min-height: 31px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }
        .fixed-report-table tr {
            height: 31px !important;
            max-height: 31px !important;
        }
        .watermark-bg {
            background-image: radial-gradient(circle at center, rgba(234, 179, 8, 0.03) 0%, transparent 70%);
        }
    </style>
</head>
<body class="bg-slate-100 dark:bg-[#070a13] min-h-screen text-slate-800 dark:text-slate-100 antialiased py-4 sm:py-6 px-1.5 sm:px-4 flex flex-col items-center justify-start transition-colors duration-200">

    <!-- Top Action Toolbar (مرن يتبع ثيم الواجهة ومتجاوب بالكامل على الموبايل والحاسب) -->
    <nav class="no-print w-full max-w-[297mm] mb-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-[#0f172a] p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors" data-purpose="top-action-bar">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 px-3 py-1.5 rounded-xl text-xs font-bold">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>محضر وقرار نتائج الدفعة الأكاديمية والإدارية (داخلي سري ومصادق)</span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500 font-mono hidden md:inline">{{ $reportNumber }}</span>
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

            <!-- Share Cohort Button -->
            <button class="flex-1 sm:flex-initial flex items-center justify-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white border border-indigo-500 transition shadow-md whitespace-nowrap" onclick="openCohortShareModal()" type="button">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                <span>مشاركة المحضر إدارياً</span>
            </button>

            <!-- Print / PDF Button -->
            <button class="flex-1 sm:flex-initial flex items-center justify-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 border border-amber-500 transition shadow-md whitespace-nowrap" onclick="window.print()" type="button">
                <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                <span>طباعة المحضر (PDF)</span>
            </button>
        </div>
    </nav>

    <!-- تنبيه إرشادي ذكي لمستخدمي الهواتف الذكية -->
    <div class="no-print w-full max-w-[297mm] mb-4 sm:hidden px-3.5 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-900 dark:text-amber-200 text-xs flex items-center gap-2.5 shadow-xs">
        <span class="text-base select-none">📱</span>
        <span class="leading-relaxed"><strong>ملاحظة للموبايل:</strong> هذا المحضر مجهّز كوثيقة رسمية بحجم <strong>A4 عرضي (Landscape)</strong>. يمكنك التمرير يميناً ويساراً، أو <strong>تدوير الهاتف بالعرض</strong> لتصفحه بوضوح كامل كالحاسب.</span>
    </div>

    @php
        $totalStudents = count($filteredStudentsList);
        $studentsPages = [];
        
        if ($totalStudents === 0) {
            $studentsPages = [[]];
        } elseif ($totalStudents <= 7) {
            // صفحة واحدة تتسع للـ KPIs والطلاب حتى 7 وملخص الدفعة والتواقيع والختم
            $studentsPages = [$filteredStudentsList];
        } else {
            // الصفحة الأولى تأخذ أول 7 طلاب مع الـ KPIs والتواقيع
            $studentsPages[] = array_slice($filteredStudentsList, 0, 7);
            $offset = 7;
            $remaining = $totalStudents - $offset;
            
            while ($remaining > 0) {
                // إذا كان المتبقي يتسع مع التواقيع وملخص الدفعة في الصفحة الأخيرة (حتى 7 طلاب)
                if ($remaining <= 7) {
                    $studentsPages[] = array_slice($filteredStudentsList, $offset, $remaining);
                    break;
                }
                // إذا كان المتبقي 8 أو 9 طلاب، نقسمهم بالتساوي لضمان عدم تجاوز حدود الصفحة
                if ($remaining <= 9) {
                    $take = (int) ceil($remaining / 2);
                    $studentsPages[] = array_slice($filteredStudentsList, $offset, $take);
                    $offset += $take;
                    $remaining -= $take;
                    $studentsPages[] = array_slice($filteredStudentsList, $offset, $remaining);
                    break;
                }
                // الصفحات الوسطى تستوعب حتى 8 طلاب بارتياح مع التواقيع
                $take = 8;
                $studentsPages[] = array_slice($filteredStudentsList, $offset, $take);
                $offset += $take;
                $remaining -= $take;
            }
        }
        
        $totalPages = count($studentsPages);
        
        // حساب إزاحات الترقيم التسلسلي المستمر للطلاب عبر الصفحات
        $pageOffsets = [];
        $runningOffset = 0;
        foreach ($studentsPages as $pIdx => $pList) {
            $pageOffsets[$pIdx] = $runningOffset;
            $runningOffset += count($pList);
        }
    @endphp

    <!-- A4 Landscape Printable Master Document Canvas (مع حاوية تمرير أفقية تضمن الحجم الطبيعي للورقة على كافة الشاشات) -->
    <div class="report-viewport-wrapper w-full">
        <main class="w-[297mm] min-w-[297mm] max-w-[297mm] flex flex-col gap-8 print:w-full print:min-w-0 print:max-w-none print:gap-0 print:p-0">
        @foreach($studentsPages as $pageIndex => $pageStudents)
        <div class="page-sheet relative bg-white text-slate-900 rounded-xl shadow-2xl p-6 print:p-3 overflow-hidden border border-slate-300 dark:border-slate-800 watermark-bg flex flex-col justify-between" id="printable-master-sheet-{{ $pageIndex }}">
            <!-- Institutional Watermark Background -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.03] select-none z-0">
                <img alt="DTC Institutional Seal Watermark" class="w-[520px] h-[520px] object-contain grayscale" src="{{ $watermarkBase64 }}">
            </div>

            <!-- Content Layer -->
            <div class="relative z-10 flex flex-col justify-between h-full">
                
                <!-- UPPER SECTION: Recurring Official Header & Scope Classification (ثابت بكل ورقة طبقاً للصورة 1) -->
                <div class="flex flex-col gap-3">
                    <!-- 1. Official Institutional Header (Wide Landscape Layout) -->
                    <header class="flex flex-row items-start justify-between gap-4 pb-2 bg-slate-50/80 border border-slate-200/90 p-3.5 rounded-xl">
                        <!-- Right: Official Ministry Hierarchy -->
                        <div class="flex flex-col text-right w-1/4">
                            <span class="font-bold text-slate-800 text-[12px] leading-tight">الجمهورية العربية السورية</span>
                            <span class="font-medium text-slate-700 text-[11px] leading-tight">وكالة الأمم المتحدة لإغاثة وتشغيل اللاجئين (UNRWA)</span>
                            <span class="font-bold text-slate-900 text-[12.5px] leading-tight mt-0.5">معهد دمشق المتوسط (DTC)</span>
                            <span class="font-semibold text-amber-700 text-[10.5px] leading-tight">مديرية شؤون الطلاب والامتحانات والكنترول الأكاديمي</span>
                            <span class="font-mono text-slate-500 text-[9px] mt-0.5 tracking-tighter">Damascus Training Centre // Central Control</span>
                        </div>

                        <!-- Center: Official DTC Crest and Core Legal Title -->
                        <div class="flex flex-col items-center justify-center text-center flex-1 px-3">
                            <div class="flex items-center gap-2">
                                <div class="w-12 h-12 p-1 rounded-full border border-sky-300 bg-sky-50 flex items-center justify-center shadow-xs">
                                    <img alt="شعار معهد دمشق المتوسط الرسمي" class="w-full h-full object-contain rounded-full" src="{{ $dtcLogoBase64 }}">
                                </div>
                            </div>
                            <h1 class="text-[18px] font-black text-slate-950 mt-0.5 tracking-tight leading-snug">
                                محضر وقرار نتائج الدفعة الأكاديمي والإداري الداخلي
                            </h1>
                            <p class="font-medium text-[10.5px] text-slate-600">
                                (وثيقة إدارية رسمية سرية خاصة بالعمادة واللجنة المركزية للامتحانات والمصادقة)
                            </p>
                            <div class="inline-flex items-center gap-2 mt-0.5 px-3 py-1 rounded-lg bg-amber-100/90 text-amber-900 font-bold text-[10.5px] border border-amber-300/80 whitespace-nowrap">
                                <span>العام الدراسي: {{ $academicYearLabel }}</span>
                                <span>•</span>
                                <span>{{ $semesterScopeLabel }} (الامتحانات المعتمدة)</span>
                            </div>
                        </div>

                        <!-- Left: Document Serial, Timestamps & QR Verification -->
                        <div class="flex flex-row items-center justify-end gap-3 w-1/4 text-left">
                            <div class="flex flex-col text-right text-[9.5px] text-slate-600 leading-snug">
                                <span class="font-bold text-slate-800">رقم المحضر:</span>
                                <span class="font-mono font-bold text-amber-800 text-[10.5px]">{{ $reportNumber }}</span>
                                <span class="font-bold text-slate-800 mt-0.5">تاريخ الانعقاد والاعتماد:</span>
                                <span class="font-medium text-slate-700">{{ $issueDateFormatted }}</span>
                                <span class="inline-flex items-center gap-1 font-bold text-emerald-700 mt-0.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                    <span>مصادق ونهائي (معتمد رسمياً)</span>
                                </span>
                            </div>
                            <!-- Miniature QR Verification Box -->
                            <div class="flex flex-col items-center p-1 bg-white border border-slate-300 rounded shadow-xs">
                                <svg class="w-11 h-11" fill="none" viewBox="0 0 100 100">
                                    <rect fill="#0F172A" height="30" rx="3" width="30" x="5" y="5"></rect>
                                    <rect fill="#FFFFFF" height="20" rx="2" width="20" x="10" y="10"></rect>
                                    <rect fill="#0F172A" height="12" rx="1" width="12" x="14" y="14"></rect>
                                    <rect fill="#0F172A" height="30" rx="3" width="30" x="65" y="5"></rect>
                                    <rect fill="#FFFFFF" height="20" rx="2" width="20" x="70" y="10"></rect>
                                    <rect fill="#0F172A" height="12" rx="1" width="12" x="74" y="14"></rect>
                                    <rect fill="#0F172A" height="30" rx="3" width="30" x="5" y="65"></rect>
                                    <rect fill="#FFFFFF" height="20" rx="2" width="20" x="10" y="70"></rect>
                                    <rect fill="#0F172A" height="12" rx="1" width="12" x="14" y="74"></rect>
                                    <rect fill="#0F172A" height="8" rx="1" width="8" x="42" y="10"></rect>
                                    <rect fill="#B45309" height="6" rx="1" width="6" x="52" y="18"></rect>
                                    <rect fill="#0F172A" height="6" rx="1" width="10" x="40" y="24"></rect>
                                    <rect fill="#047857" height="12" rx="2" width="12" x="44" y="42"></rect>
                                    <rect fill="#0F172A" height="8" rx="1" width="8" x="62" y="44"></rect>
                                    <rect fill="#0F172A" height="6" rx="1" width="14" x="75" y="52"></rect>
                                    <rect fill="#0F172A" height="12" rx="1" width="8" x="42" y="68"></rect>
                                    <rect fill="#0F172A" height="8" rx="1" width="10" x="56" y="65"></rect>
                                    <rect fill="#0F172A" height="16" rx="2" width="16" x="72" y="72"></rect>
                                    <rect fill="#0F172A" height="8" rx="1" width="8" x="25" y="42"></rect>
                                </svg>
                                <span class="text-[7px] font-mono text-slate-500 font-bold tracking-tighter mt-0.5">AUTH-DTC-VERIFY</span>
                            </div>
                        </div>
                    </header>

                    <!-- 2. Administrative Scope Definition Bar (ثابت في بداية كل ورقة طبقاً للصورة 1) -->
                    <div class="bg-white border border-slate-200 p-2 rounded-lg shadow-xs">
                        <div class="flex items-center justify-between pb-1 mb-1 border-b border-slate-100">
                            <span class="text-[10.5px] font-bold text-amber-800 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-amber-600 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                نطاق القرار ومحددات الفرز الأكاديمي المعتمد
                            </span>
                            <span class="text-[9.5px] font-mono text-slate-500 font-semibold">BATCH CLASSIFICATION</span>
                        </div>
                        <div class="grid grid-cols-4 gap-2 text-[10.5px]">
                            <div class="flex flex-col bg-slate-50 border border-slate-200/80 px-2 py-1 rounded">
                                <span class="text-slate-500 text-[8.5px] font-medium">السنة الأكاديمية:</span>
                                <span class="font-bold text-slate-900 truncate">{{ $yearScopeLabel }}</span>
                            </div>
                            <div class="flex flex-col bg-slate-50 border border-slate-200/80 px-2 py-1 rounded">
                                <span class="text-slate-500 text-[8.5px] font-medium">القسم / التخصص:</span>
                                <span class="font-bold text-slate-900 truncate" title="{{ $progName }}">{{ $progName }}</span>
                            </div>
                            <div class="flex flex-col bg-slate-50 border border-slate-200/80 px-2 py-1 rounded">
                                <span class="text-slate-500 text-[8.5px] font-medium">الحالة الأكاديمية المفروزة:</span>
                                <span class="font-bold text-amber-900 truncate">{{ $standingScopeLabel }}</span>
                            </div>
                            <div class="flex flex-col bg-slate-50 border border-slate-200/80 px-2 py-1 rounded">
                                <span class="text-slate-500 text-[8.5px] font-medium">الدورة الامتحانية:</span>
                                <span class="font-bold text-slate-900 truncate">{{ $semesterScopeLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MIDDLE SECTION: Page Body Content -->
                <div class="flex flex-col gap-3 my-2 flex-1 justify-start">
                    <!-- If Page 1: High-Impact Metric Summary Tiles -->
                    @if($loop->first)
                    <div class="grid grid-cols-4 gap-2">
                        <!-- Metric 1: Total Students -->
                        <div class="bg-slate-50 border border-slate-200/80 p-2 rounded-lg flex flex-col justify-between shadow-xs">
                            <div class="flex items-center justify-between text-slate-500 text-[9.5px] font-bold">
                                <span>إجمالي الطلاب المفرزين</span>
                                <span class="font-mono text-slate-400 text-xs">COUNT</span>
                            </div>
                            <div class="mt-0.5">
                                <span class="text-slate-900 font-black text-lg tracking-tight">{{ $totalStudentsCount }}</span>
                                <span class="text-[9.5px] text-slate-600 font-semibold mr-1">طالباً</span>
                            </div>
                            <div class="text-[8.5px] text-emerald-700 font-medium mt-0.5">مدقق 100% بالكنترول</div>
                        </div>

                        <!-- Metric 2: Pass Rate -->
                        <div class="bg-emerald-50/80 border border-emerald-200/80 p-2 rounded-lg flex flex-col justify-between shadow-xs">
                            <div class="flex items-center justify-between text-emerald-900 text-[9.5px] font-bold">
                                <span>نسبة النجاح العامة</span>
                                <span class="font-mono text-emerald-600 text-xs">RATE</span>
                            </div>
                            <div class="mt-0.5">
                                <span class="text-emerald-800 font-black text-lg tracking-tight">{{ number_format($passRate, 1) }}%</span>
                            </div>
                            <div class="text-[8.5px] text-emerald-700 font-medium mt-0.5">{{ $passedStudentsCount }} طالباً مستوفين نظامياً</div>
                        </div>

                        <!-- Metric 3: Highest GPA -->
                        <div class="bg-amber-50/80 border border-amber-200/80 p-2 rounded-lg flex flex-col justify-between shadow-xs">
                            <div class="flex items-center justify-between text-amber-900 text-[9.5px] font-bold">
                                <span>أعلى معدل مسجل</span>
                                <span class="font-mono text-amber-600 text-xs">MAX</span>
                            </div>
                            <div class="mt-0.5">
                                <span class="text-amber-900 font-black text-lg tracking-tight">{{ number_format($maxGpa, 2) }}%</span>
                            </div>
                            <div class="text-[8.5px] text-amber-800 font-medium truncate mt-0.5">مرتبة الشرف للدفعة</div>
                        </div>

                        <!-- Metric 4: Lowest GPA / Supplementary -->
                        <div class="bg-rose-50/70 border border-rose-200/80 p-2 rounded-lg flex flex-col justify-between shadow-xs">
                            <div class="flex items-center justify-between text-rose-900 text-[9.5px] font-bold">
                                <span>أدنى معدل مسجل</span>
                                <span class="font-mono text-rose-600 text-xs">MIN</span>
                            </div>
                            <div class="mt-0.5">
                                <span class="text-rose-800 font-black text-lg tracking-tight">{{ number_format($minGpa, 2) }}%</span>
                            </div>
                            <div class="text-[8.5px] text-rose-700 font-medium mt-0.5">{{ $failedTotalCount }} غير مستوفين / استدراك</div>
                        </div>
                    </div>
                    @endif

                    <!-- Bulk Student Progression Master Table -->
                    <div class="border border-slate-300 rounded-lg overflow-hidden shadow-xs">
                        <table class="fixed-report-table w-full text-right text-[10.5px] leading-snug border-collapse bg-white">
                            <colgroup>
                                <col style="width: 3.5%;"> <!-- # -->
                                <col style="width: 9.5%;"> <!-- الرقم الجامعي -->
                                <col style="width: 18%;">  <!-- اسم الطالب الرباعي -->
                                <col style="width: 15%;">  <!-- القسم والتخصص الأكاديمي -->
                                <col style="width: 6%;">   <!-- مجموع الساعات -->
                                <col style="width: 8%;">   <!-- المعدل التراكمي -->
                                <col style="width: 16%;">  <!-- المقررات غير المستوفاة -->
                                <col style="width: 24%;">  <!-- القرار الإداري المعتمد -->
                            </colgroup>
                            <!-- Table Header -->
                            <thead>
                                <tr class="bg-slate-900 text-white font-bold text-[10px]">
                                    <th class="py-1 px-1 text-center border-l border-slate-700">#</th>
                                    <th class="py-1 px-1 text-center border-l border-slate-700">الرقم الجامعي</th>
                                    <th class="py-1 px-2.5 text-right border-l border-slate-700">اسم الطالب الرباعي</th>
                                    <th class="py-1 px-2 text-right border-l border-slate-700">القسم والتخصص الأكاديمي</th>
                                    <th class="py-1 px-1 text-center border-l border-slate-700">الساعات</th>
                                    <th class="py-1 px-1 text-center border-l border-slate-700 bg-slate-800">المعدل التراكمي</th>
                                    <th class="py-1 px-2 text-right border-l border-slate-700">المقررات غير المستوفاة</th>
                                    <th class="py-1 px-2 text-center">القرار الإداري المعتمد للدفعة</th>
                                </tr>
                            </thead>
                            <!-- Table Rows for Current Page -->
                            <tbody class="divide-y divide-slate-200 font-medium text-slate-800">
                                @forelse($pageStudents as $idx => $s)
                                @php
                                    $globalIdx = $pageOffsets[$pageIndex] + $idx;
                                @endphp
                                <tr class="{{ $loop->even ? 'bg-slate-50/50' : 'bg-white' }} hover:bg-amber-50/30 transition-colors">
                                    <td class="py-1 px-1 text-center font-mono text-slate-500 text-[9.5px] border-l border-slate-200">{{ str_pad($globalIdx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="py-1 px-1 text-center font-mono font-bold text-slate-900 border-l border-slate-200 truncate" title="{{ $s['student_code'] }}">{{ $s['student_code'] }}</td>
                                    <td class="py-1 px-2.5 font-bold text-slate-900 border-l border-slate-200 truncate" title="{{ $s['full_name'] }}">
                                        <div class="flex items-center justify-between w-full overflow-hidden">
                                            <span class="truncate">{{ $s['full_name'] }}</span>
                                            @if($s['cum_gpa'] >= 90 && $s['failed_count'] === 0)
                                                <span class="shrink-0 text-[8px] font-bold text-amber-800 bg-amber-100 border border-amber-300 px-1 py-0.2 rounded-full mr-1">شرف</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-1 px-2 text-slate-700 border-l border-slate-200 font-semibold truncate" title="{{ $s['program_name'] }} ({{ $s['level'] }})">{{ $s['program_name'] }} ({{ $s['level'] }})</td>
                                    <td class="py-1 px-1 text-center font-mono font-bold text-slate-800 border-l border-slate-200">{{ $s['total_hours'] }}</td>
                                    <td class="py-1 px-1 text-center font-mono font-black border-l border-slate-200 text-[11px] {{ $s['cum_gpa'] >= 80 ? 'text-emerald-700' : ($s['cum_gpa'] >= 50 ? 'text-slate-900' : 'text-red-700') }}">
                                        {{ number_format($s['cum_gpa'], 2) }}%
                                    </td>
                                    <td class="py-1 px-2 border-l border-slate-200 truncate" title="{{ $s['failed_count'] === 0 ? 'لا يوجد (مستوفٍ المقررات كافة)' : $s['failed_courses_titles'] }}">
                                        @if($s['failed_count'] === 0)
                                            <span class="text-emerald-700 font-bold text-[9.5px] inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 text-emerald-600 inline shrink-0" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"></path></svg>
                                                <span class="truncate">لا يوجد (مستوفٍ المقررات كافة)</span>
                                            </span>
                                        @else
                                            <span class="font-bold text-rose-700 text-[9px] bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded inline-block truncate max-w-full">
                                                {{ $s['failed_courses_titles'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-1 px-2 text-center" title="{{ $s['decision_label'] }}">
                                        @if($s['decision_type'] === 'honor')
                                            <span class="inline-flex items-center justify-center gap-1.5 w-full py-0.5 px-2 rounded-full bg-gradient-to-r from-amber-600 to-amber-700 text-white font-black text-[9.5px] shadow-xs whitespace-nowrap">
                                                {{ $s['decision_label'] }}
                                            </span>
                                        @elseif($s['decision_type'] === 'pass')
                                            <span class="inline-flex items-center justify-center gap-1.5 w-full py-0.5 px-2 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[9.5px] border border-emerald-300 whitespace-nowrap">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                                                <span class="whitespace-nowrap">{{ $s['decision_label'] }}</span>
                                            </span>
                                        @elseif($s['decision_type'] === 'supplementary')
                                            <span class="inline-flex items-center justify-center gap-1.5 w-full py-0.5 px-2 rounded-full bg-amber-100 text-amber-900 font-bold text-[9.5px] border border-amber-300 whitespace-nowrap">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600 shrink-0"></span>
                                                <span class="whitespace-nowrap">{{ $s['decision_label'] }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center gap-1.5 w-full py-0.5 px-2 rounded-full bg-rose-600 text-white font-bold text-[9.5px] shadow-xs whitespace-nowrap">
                                                {{ $s['decision_label'] }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="py-6 text-center text-slate-500 font-bold">
                                        لا يوجد طلاب مطابقون لمعايير الفلترة الحالية المحددة
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <!-- If Last Page: Cohort Summary Footer Row -->
                            @if($loop->last && $totalStudents > 0)
                            <tfoot>
                                <tr class="bg-slate-100 text-slate-900 font-bold text-[10.5px] border-t-2 border-slate-300">
                                    <td class="py-1.5 px-3" colspan="4">
                                        <div class="flex items-center gap-3 flex-wrap">
                                            <span class="font-black text-slate-950">إجمالي نتائج مخرجات الدفعة المفروزة:</span>
                                            <span class="text-emerald-800 font-bold">الناجحون والمستوفون: <strong>{{ $passedStudentsCount }} طالباً</strong></span>
                                            <span class="text-amber-800 font-bold">المحالون للتكميلية: <strong>{{ $supplementaryCount }} طالباً</strong></span>
                                            <span class="text-rose-800 font-bold">الراسبون (إعادة سنة): <strong>{{ $repeatCount }} طلاب</strong></span>
                                        </div>
                                    </td>
                                    <td class="py-1.5 px-2 text-center font-mono font-bold">-</td>
                                    <td class="py-1.5 px-2 text-center font-mono font-black text-slate-950 text-[11px] bg-slate-200/70 border-x border-slate-300">
                                        {{ number_format($avgGpa, 2) }}%
                                    </td>
                                    <td class="py-1.5 px-3 text-left font-semibold text-[10px] text-slate-700" colspan="2">
                                        متوسط المعدل العام التراكمي للدفعة المفروزة: <strong class="font-mono text-slate-950">{{ number_format($avgGpa, 2) }}%</strong>
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    <!-- Recurring Administrative Executive Signatures & Official Stamp (ثابت بكل ورقة بناءً على طلب الإدارة) -->
                    <div class="mt-1 pt-1.5 bg-slate-50/60 p-2 rounded-xl border border-slate-200/80">
                        <div class="grid grid-cols-2 gap-6 items-end">
                            <!-- Pillar 1: Students Affairs & Central Control Director -->
                            <div class="flex flex-col items-center justify-between h-[68px] p-1 rounded-lg bg-white border border-slate-200 shadow-xs">
                                <span class="font-bold text-slate-900 text-[10.5px]">
                                    مدير شؤون الطلاب والامتحانات والكنترول المركزي
                                </span>
                                <div class="w-44 border-b border-slate-400 border-dotted my-1"></div>
                                <div class="flex items-center gap-2 text-[8.5px] text-slate-500 font-medium">
                                    <span>التوقيع والاعتماد الرسمي: .................................</span>
                                </div>
                            </div>

                            <!-- Pillar 2: Dean / General Director of DTC + Official Seal Stamp -->
                            <div class="flex flex-col items-center justify-between h-[68px] p-1 rounded-lg bg-white border border-slate-200 shadow-xs">
                                <span class="font-bold text-slate-900 text-[10.5px]">
                                    عميد ومـدير معهد دمشق المتوسط (DTC)
                                </span>
                                <div class="w-44 border-b border-slate-400 border-dotted my-1"></div>
                                <div class="flex items-center gap-2 text-[8.5px] text-slate-500 font-medium">
                                    <span>الاعتماد والختم الرسمي: .................................</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LOWER SECTION: Recurring Official Confidential Footer (ثابت بكل ورقة طبقاً للصورة 2) -->
                <footer class="mt-auto pt-2 border-t border-slate-200 flex flex-row items-center justify-between text-[9px] text-slate-600 gap-2">
                    <div class="flex items-center gap-1.5 text-right font-medium">
                        <svg class="w-3.5 h-3.5 text-amber-600 shrink-0 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <span>وثيقة بيانات طلابية سرية مخصصة للاستخدام الإداري والعمادة حصراً - تفقد حجيتها وقيمتها القانونية إذا كانت خالية من التوقيع الحي وختم إدارة المعهد الرسمي.</span>
                    </div>
                    <div class="flex items-center gap-2 font-mono text-[9px] shrink-0">
                        <span class="text-slate-400">HASH: {{ $verificationHash }}</span>
                        <span>•</span>
                        <span>EduBridge System v4.8 Internal Enterprise</span>
                        <span>•</span>
                        <span class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">صفحة {{ $pageIndex + 1 }} من {{ $totalPages }}</span>
                    </div>
                </footer>

            </div>
        </div>
        @endforeach
        </main>
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

        function openCohortShareModal() {
            const m = document.getElementById('shareCohortModal');
            m.classList.remove('hidden');
            setTimeout(() => {
                m.classList.remove('opacity-0');
            }, 10);
            updateSelectedCount();
        }

        function closeCohortShareModal() {
            const m = document.getElementById('shareCohortModal');
            m.classList.add('opacity-0');
            setTimeout(() => {
                m.classList.add('hidden');
            }, 300);
        }

        function updateSelectedCount() {
            const checked = document.querySelectorAll('.staff-checkbox:checked').length;
            const counter = document.getElementById('selectedStaffCount');
            if (counter) counter.innerText = checked;
        }

        function selectAllStaff(selectAll) {
            const items = document.querySelectorAll('.staff-item');
            items.forEach(item => {
                if (!item.classList.contains('hidden')) {
                    const cb = item.querySelector('.staff-checkbox');
                    if (cb) cb.checked = selectAll;
                }
            });
            updateSelectedCount();
        }

        let currentActiveCategory = 'all';

        function filterStaffCategory(cat, btn) {
            currentActiveCategory = cat;
            document.querySelectorAll('.filter-cat-btn').forEach(b => {
                b.classList.remove('bg-indigo-600', 'text-white');
                b.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
            });
            if (btn) {
                btn.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
                btn.classList.add('bg-indigo-600', 'text-white');
            }
            applyStaffFilter();
        }

        function applyStaffFilter() {
            const q = (document.getElementById('searchStaffInput')?.value || '').toLowerCase().trim();
            const items = document.querySelectorAll('.staff-item');
            let visibleCount = 0;

            items.forEach(item => {
                const itemCat = item.getAttribute('data-category');
                const itemSearch = item.getAttribute('data-search') || '';
                const matchCategory = (currentActiveCategory === 'all' || itemCat === currentActiveCategory);
                const matchSearch = (!q || itemSearch.includes(q));

                if (matchCategory && matchSearch) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            const noResults = document.getElementById('noStaffFound');
            if (noResults) {
                if (visibleCount === 0) noResults.classList.remove('hidden');
                else noResults.classList.add('hidden');
            }
        }

        function submitCohortShare() {
            const checkboxes = document.querySelectorAll('.staff-checkbox:checked');
            const userIds = Array.from(checkboxes).map(cb => cb.value);

            if (userIds.length === 0) {
                alert('يرجى تحديد شخص واحد على الأقل من القائمة لمشاركة المحضر معه.');
                return;
            }

            const notes = document.getElementById('cohortShareNotes')?.value || '';
            const btn = document.getElementById('btnConfirmCohortShare');
            const originalHtml = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin mr-2">⏳</span> جاري إرسال الإشعارات والمشاركة...`;

            fetch("{{ route('affairs.course_weights.share_cohort') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    user_ids: userIds,
                    dept_name: @json($deptName ?? 'جميع الأقسام'),
                    prog_name: @json($progName ?? 'جميع التخصصات'),
                    year_label: @json($yearLabel ?? $yearScopeLabel ?? 'كافة السنوات'),
                    notes: notes
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (data.success) {
                    alert('✅ ' + data.message);
                    closeCohortShareModal();
                } else {
                    alert('❌ ' + (data.message || 'تعذر إتمام عملية مشاركة المحضر'));
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('حدث خطأ أثناء إرسال البيانات: ' + err);
            });
        }
    </script>

    @php
        $staffList = isset($academicStaffList) ? $academicStaffList : collect();
    @endphp

    <!-- نافذة منبثقة لمشاركة محضر وقرار الدفعة إدارياً بالأسماء والمناصب -->
    <div id="shareCohortModal" class="no-print fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4 hidden transition-opacity duration-300 opacity-0">
        <div class="bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 max-w-2xl w-full shadow-2xl space-y-4 text-right max-h-[92vh] flex flex-col" dir="rtl">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg font-black">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">مشاركة محضر وقرار نتائج الدفعة مع الكادر</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $deptName ?? 'جميع الأقسام' }} - {{ $progName ?? 'جميع التخصصات' }} ({{ $yearLabel ?? $yearScopeLabel ?? 'كافة السنوات' }})</p>
                    </div>
                </div>
                <button type="button" onclick="closeCohortShareModal()" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center justify-center text-sm font-bold transition">✕</button>
            </div>

            <!-- Search and Filter Bar -->
            <div class="space-y-2.5 shrink-0">
                <div class="relative">
                    <input type="text" id="searchStaffInput" oninput="applyStaffFilter()" placeholder="🔍 ابحث باسم الشخص، المنصب، أو القسم الأكاديمي..." class="w-full text-xs p-2.5 pr-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/70 text-slate-800 dark:text-slate-200 outline-hidden focus:ring-2 focus:ring-indigo-500 transition">
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <!-- Category Tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                        <button type="button" onclick="filterStaffCategory('all', this)" class="filter-cat-btn px-2.5 py-1 rounded-lg font-bold bg-indigo-600 text-white transition text-[11px]">الكل</button>
                        <button type="button" onclick="filterStaffCategory('admin', this)" class="filter-cat-btn px-2.5 py-1 rounded-lg font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-[11px]">العمادة والإدارة</button>
                        <button type="button" onclick="filterStaffCategory('hod', this)" class="filter-cat-btn px-2.5 py-1 rounded-lg font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-[11px]">رؤساء الأقسام</button>
                        <button type="button" onclick="filterStaffCategory('teachers', this)" class="filter-cat-btn px-2.5 py-1 rounded-lg font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-[11px]">المعلمون</button>
                    </div>

                    <!-- Quick Selection & Counter -->
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded-md border border-indigo-200 dark:border-indigo-800/60">
                            المحدد: <strong id="selectedStaffCount">0</strong>
                        </span>
                        <button type="button" onclick="selectAllStaff(true)" class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold">تحديد الكل</button>
                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        <button type="button" onclick="selectAllStaff(false)" class="text-[11px] text-slate-500 hover:underline">إلغاء</button>
                    </div>
                </div>
            </div>

            <!-- Staff List (Multi-Select with Names and Positions) -->
            <div class="overflow-y-auto space-y-2 p-1 border border-slate-100 dark:border-slate-800 rounded-2xl flex-1 max-h-[36vh] sm:max-h-[40vh] divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($staffList as $staff)
                    <label class="staff-item flex items-center justify-between p-2.5 rounded-xl hover:bg-indigo-50/50 dark:hover:bg-slate-800/60 cursor-pointer transition select-none"
                           data-category="{{ $staff->category }}"
                           data-search="{{ mb_strtolower($staff->name . ' ' . $staff->position . ' ' . $staff->category_label) }}">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="cohortUserIds[]" value="{{ $staff->user_id }}" class="staff-checkbox rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" onchange="updateSelectedCount()">
                            
                            <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs font-black text-slate-700 dark:text-slate-200 shrink-0">
                                {{ mb_substr($staff->name, 0, 1) }}
                            </div>

                            <div class="text-right">
                                <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $staff->name }}</span>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $staff->badge_class }}">
                                        {{ $staff->category_label }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                                    {{ $staff->position }}
                                </div>
                            </div>
                        </div>
                    </label>
                @empty
                    <div class="p-6 text-center text-xs text-slate-500">
                        لم يتم العثور على أفراد كادر مسجلين بالنظام حالياً.
                    </div>
                @endforelse

                <div id="noStaffFound" class="hidden p-6 text-center text-xs text-slate-500">
                    لا يوجد نتائج مطابقة للبحث أو التصفية الحالية.
                </div>
            </div>

            <!-- Additional Notes -->
            <div class="shrink-0">
                <label for="cohortShareNotes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ملاحظات أو توجيهات إدارية ملحقة (اختياري):</label>
                <textarea id="cohortShareNotes" rows="2" placeholder="اكتب أية توجيهات خاصة باللجنة الامتحانية أو التنسيق الأكاديمي..." class="w-full text-xs p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-hidden"></textarea>
            </div>

            <!-- Notice -->
            <div class="shrink-0 p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/25 text-indigo-950 dark:text-indigo-300 text-xs flex items-center gap-2.5">
                <span class="text-base select-none">🔒</span>
                <span><strong>وثيقة رسمية وسرية:</strong> سيصل إشعار فوري للأشخاص المحددين أعلاه بالاسم والمنصب لمراجعة المحضر.</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3 pt-1 shrink-0">
                <button type="button" id="btnConfirmCohortShare" onclick="submitCohortShare()" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    <span>تأكيد المشاركة للأعضاء المحددين</span>
                </button>
                <button type="button" onclick="closeCohortShareModal()" class="py-2.5 px-4 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                    إلغاء
                </button>
            </div>
        </div>
    </div>
</body>
</html>
