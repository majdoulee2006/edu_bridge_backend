<!DOCTYPE html>
<html class="dark" dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>سجل الحضور والغياب الأكاديمي المعتمد - مصنف إكسل</title>
    
    <!-- Google Fonts & Material Symbols & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        @layer base {
            html, body {
                margin: 0;
                padding: 0;
                font-family: 'Cairo', 'Plus Jakarta Sans', system-ui, sans-serif;
                background-color: #111319;
                color: #e2e2eb;
            }
        }
        
        /* Custom scrollbar for spreadsheet view */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #191b22;
        }
        ::-webkit-scrollbar-thumb {
            background: #33343b;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #504535;
        }

        /* Freeze panes styling for sticky columns A, B, C in RTL */
        .freeze-col-num {
            position: sticky;
            right: 0;
            z-index: 20;
            background-color: #1e1f26;
        }
        .freeze-col-acad {
            position: sticky;
            right: 48px;
            z-index: 20;
            background-color: #191b22;
        }
        .freeze-col-name {
            position: sticky;
            right: 170px;
            z-index: 20;
            background-color: #191b22;
            box-shadow: -4px 0 8px rgba(0, 0, 0, 0.45);
        }
        th.freeze-col-num, th.freeze-col-acad, th.freeze-col-name {
            background-color: #282a30 !important;
            z-index: 30;
        }
        tfoot .freeze-col-num, tfoot .freeze-col-acad, tfoot .freeze-col-name {
            background-color: #1e1f26 !important;
            z-index: 25;
        }

        /* Active cell outline */
        td.cell-active {
            outline: 2px solid #ffc665 !important;
            outline-offset: -2px;
            background-color: rgba(255, 198, 101, 0.12) !important;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; }
            .freeze-col-num, .freeze-col-acad, .freeze-col-name { position: static !important; box-shadow: none !important; }
        }
    </style>

    <!-- Tailwind CSS with Exact Custom Palette matching template -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface": "#111319",
                        "surface-dim": "#111319",
                        "surface-bright": "#373940",
                        "surface-variant": "#33343b",
                        "surface-container-lowest": "#0c0e14",
                        "surface-container-low": "#191b22",
                        "surface-container": "#1e1f26",
                        "surface-container-high": "#282a30",
                        "surface-container-highest": "#33343b",
                        "on-surface": "#e2e2eb",
                        "on-surface-variant": "#d4c4b0",
                        "primary": "#ffc665",
                        "primary-container": "#e5a93c",
                        "secondary": "#e9c349",
                        "secondary-container": "#af8d11",
                        "error": "#ffb4ab",
                        "outline": "#9d8f7c",
                        "outline-variant": "#504535",
                    },
                    fontFamily: {
                        "body-sm": ["Cairo", "Plus Jakarta Sans"],
                        "body-md": ["Cairo", "Plus Jakarta Sans"],
                        "label-sm": ["Cairo", "Plus Jakarta Sans"],
                        "label-md": ["Cairo", "Plus Jakarta Sans"],
                        "headline-sm": ["Space Grotesk", "Cairo"],
                        "headline-md": ["Space Grotesk", "Cairo"],
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-surface text-on-surface select-none antialiased min-h-screen flex flex-col">

    <!-- 1. Metadata Badges & Filter Ribbon (Header without clutter, exactly matching user screenshot) -->
    <section class="px-6 pt-3 pb-2.5 bg-surface-container-low border-b border-surface-container-highest/60 flex items-center justify-between gap-3 shadow-sm no-print overflow-x-auto">
        <!-- Metadata Badges -->
        <div class="flex items-center gap-2 whitespace-nowrap">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-surface-container font-label-sm text-label-sm text-on-surface border border-surface-container-highest/50">
                <span class="material-symbols-outlined text-primary text-base">groups</span>
                <span class="text-on-surface-variant font-medium">الشعبة:</span>
                <span class="font-bold text-primary">{{ $filterClass }}</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-surface-container font-label-sm text-label-sm text-on-surface border border-surface-container-highest/50">
                <span class="material-symbols-outlined text-secondary text-base">person</span>
                <span class="text-on-surface-variant font-medium">المشرف:</span>
                <span class="font-bold text-on-surface">{{ $supervisorName }}</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-surface-container font-label-sm text-label-sm text-on-surface border border-surface-container-highest/50">
                <span class="material-symbols-outlined text-primary text-base">calendar_month</span>
                <span class="text-on-surface-variant font-medium">الفترة:</span>
                <span class="font-semibold">{{ $periodLabel }}</span>
            </div>
        </div>

        <!-- Quick Search & Dropdown Filters & Actions -->
        <div class="flex items-center gap-2.5 whitespace-nowrap">
            <div class="relative">
                <span class="material-symbols-outlined absolute right-3 top-2 text-on-surface-variant text-base">search</span>
                <input id="quickSearchInput" oninput="applyFilters()" class="w-56 pl-3 pr-8 py-1.5 rounded-lg bg-surface-container font-body-sm text-xs text-on-surface placeholder:text-on-surface-variant/70 border border-surface-container-highest focus:outline-none focus:ring-1 focus:ring-primary" placeholder="بحث بالاسم أو الرقم الأكاديمي..." type="text">
            </div>

            <select id="statusFilterSelect" onchange="applyFilters()" class="px-3 py-1.5 rounded-lg bg-surface-container font-label-sm text-xs text-on-surface border border-surface-container-highest focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer">
                <option value="all">فلترة الحالة: الكل ({{ $totalStudents }} طالب)</option>
                <option value="100">الحضور الكامل (100%)</option>
                <option value="warning">تنبيه غياب (15% فما فوق)</option>
                <option value="deprived">إنذار وحرمان أكاديمي (20%)</option>
            </select>

            <button onclick="resetFilters()" class="w-7 h-7 rounded-lg bg-surface-container border border-surface-container-highest flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors cursor-pointer" title="إعادة ضبط الفلاتر" type="button">
                <span class="material-symbols-outlined text-sm">filter_alt_off</span>
            </button>

            <!-- Export to .xls button directly from web viewer -->
            <button onclick="downloadWorkbookAsExcel()" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-label-sm text-xs font-bold shadow transition-all cursor-pointer" title="تنزيل المصنف المعتمد بصيغة Excel">
                <span class="material-symbols-outlined text-base">download</span>
                <span>تنزيل Excel (.XLS)</span>
            </button>
        </div>
    </section>

    <!-- 2. Interactive Excel Workbook Tabs System -->
    <section class="px-6 bg-surface-container-high border-b border-surface-container-highest flex items-center justify-between overflow-x-auto no-print">
        <div class="flex items-end gap-1 pt-2 whitespace-nowrap" id="workbookTabsList">
            @foreach($sheets as $index => $sheet)
                <button type="button" 
                        onclick="switchSheet('{{ $sheet['id'] }}')" 
                        id="tab-btn-{{ $sheet['id'] }}" 
                        data-sheet-id="{{ $sheet['id'] }}"
                        data-total-students="{{ $sheet['total_students'] ?? 0 }}"
                        data-total-sessions="{{ $sheet['total_sessions_count'] ?? 0 }}"
                        data-overall-rate="{{ $sheet['overall_session_rate'] ?? 0 }}"
                        class="tab-btn group flex items-center gap-2 px-3.5 py-2 rounded-t-lg font-label-md text-label-md transition-all relative cursor-pointer whitespace-nowrap {{ $sheet['is_active'] ? 'active bg-surface text-primary font-bold shadow-[0_-2px_8px_rgba(0,0,0,0.25)]' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface/50' }}">
                    
                    @if(!empty($sheet['is_alerts']))
                        <span class="material-symbols-outlined text-rose-400 text-sm">warning</span>
                        <span>{{ $sheet['title'] }}</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-rose-500/20 text-rose-300 font-label-sm text-[10px] font-bold">{{ $sheet['badge'] }}</span>
                    @elseif(!empty($sheet['is_master']))
                        <span class="material-symbols-outlined text-secondary text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                        <span>{{ $sheet['title'] }}</span>
                    @else
                        <span class="tab-dot w-2.5 h-2.5 rounded-full {{ $sheet['is_active'] ? 'bg-emerald-500' : 'bg-surface-variant' }}"></span>
                        <span>{{ $sheet['title'] }}</span>
                        <span class="px-1.5 py-0.2 rounded bg-primary/10 text-primary font-label-sm text-[10px]">{{ $sheet['badge'] }}</span>
                    @endif

                    <!-- Active tab indicator line -->
                    <span class="tab-indicator absolute bottom-0 left-0 right-0 h-0.5 bg-primary {{ $sheet['is_active'] ? '' : 'hidden' }}"></span>
                </button>
            @endforeach
        </div>

        <!-- View Mode controls -->
        <div class="flex items-center gap-2 py-1 text-on-surface-variant font-label-sm text-label-sm">
            <span class="flex items-center gap-1 text-primary font-semibold">
                <span class="material-symbols-outlined text-base">grid_view</span>
                <span>معاينة نمط الخلايا (Live Grid)</span>
            </span>
            <span class="text-surface-container-highest">|</span>
            <span class="text-on-surface-variant">نمط القراءة الأكاديمي</span>
        </div>
    </section>

    <!-- 3. Spreadsheet Formula Bar & Formula Helper -->
    <section class="px-6 py-2 bg-surface-container flex items-center gap-3 border-b border-surface-container-highest font-body-sm text-body-sm no-print">
        <!-- Active Cell Coordinate -->
        <div id="activeCellCoordinate" class="flex items-center justify-center w-14 py-1 rounded bg-surface-container-lowest font-headline-sm text-headline-sm text-primary font-mono font-bold shadow-inner">
            D4
        </div>
        
        <!-- Formula fx split -->
        <div class="flex items-center gap-1.5 text-on-surface-variant">
            <span class="font-headline-sm text-label-lg font-bold italic text-secondary">fx</span>
            <div class="h-4 w-px bg-surface-container-highest"></div>
        </div>
        
        <!-- Formula Input -->
        <div class="flex-1 px-3 py-1 bg-surface-container-lowest rounded text-on-surface font-mono text-body-sm flex items-center gap-2 shadow-inner overflow-hidden text-ellipsis whitespace-nowrap">
            <span class="text-secondary">=IF</span>
            <span class="text-on-surface">(</span>
            <span class="text-primary">COUNTIF</span>
            <span class="text-on-surface" id="formulaCellRange">(E4:T4, "حاضر") &gt;= 1,</span>
            <span class="text-emerald-400">"حاضر"</span>
            <span class="text-on-surface">,</span>
            <span class="text-rose-400">"غائب"</span>
            <span class="text-on-surface">)</span>
            <span class="text-on-surface-variant text-label-sm mr-auto font-body-sm">[قاعدة احتساب دوام اليوم الأكاديمي: جلسة واحدة فما فوق]</span>
        </div>
        
        <!-- Quick Formula Aggregates -->
        <div class="flex items-center gap-3 font-label-sm text-label-sm text-on-surface-variant pl-2">
            <div class="flex items-center gap-1">
                <span>الطلاب:</span>
                <span id="formulaStatStudents" class="text-primary font-bold">{{ $sheets[0]['total_students'] ?? $totalStudents }}</span>
            </div>
            <div class="flex items-center gap-1">
                <span>الجلسات:</span>
                <span id="formulaStatSessions" class="text-on-surface font-semibold">{{ $sheets[0]['total_sessions_count'] ?? $totalSessions }}</span>
            </div>
            <div class="flex items-center gap-1">
                <span>نسبة الحضور:</span>
                <span id="formulaStatRate" class="text-emerald-400 font-bold">{{ $sheets[0]['overall_session_rate'] ?? 91.4 }}%</span>
            </div>
        </div>
    </section>

    <!-- 4. Spreadsheet Data Canvas (Excel Viewport with multiple sheets) -->
    <main class="w-full flex-1 overflow-x-auto bg-surface-container-lowest select-none relative" id="spreadsheetViewport">

        @foreach($sheets as $sheetIndex => $sheet)

            @if(!empty($sheet['is_alerts']))
                <!-- Alerts Sheet Container -->
                <div id="{{ $sheet['id'] }}" class="sheet-container w-full p-6 {{ $sheet['is_active'] ? '' : 'hidden' }}">
                    <div class="max-w-6xl mx-auto bg-surface-container rounded-xl border border-surface-container-highest shadow-xl overflow-hidden">
                        <div class="p-5 border-b border-surface-container-highest flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-rose-950/60 border border-rose-800/50 flex items-center justify-center text-rose-400">
                                    <span class="material-symbols-outlined text-2xl">warning</span>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-on-surface">سجل الإنذارات والحرمان الأكاديمي (الغياب المتجاوز للنسب النظامية)</h2>
                                    <p class="text-xs text-on-surface-variant">الطلاب الذين بلغت نسبة غيابهم 15% فما فوق استناداً للقواعد التنظيمية المعتمدة</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 font-bold text-xs border border-rose-500/30">
                                إجمالي الحالات: {{ count($sheet['alert_students'] ?? []) }} طالب
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-right text-xs whitespace-nowrap">
                                <thead>
                                    <tr class="bg-surface-container-high text-on-surface-variant font-bold border-b border-surface-container-highest">
                                        <th class="p-3 text-center w-12">#</th>
                                        <th class="p-3 text-right">الرقم الأكاديمي</th>
                                        <th class="p-3 text-right">اسم الطالب</th>
                                        <th class="p-3 text-right">الدورة / الشعبة</th>
                                        <th class="p-3 text-right">المقررات المعنية</th>
                                        <th class="p-3 text-center">الجلسات الكلية</th>
                                        <th class="p-3 text-center text-emerald-400">حضور</th>
                                        <th class="p-3 text-center text-rose-400">غياب</th>
                                        <th class="p-3 text-center text-primary font-bold">نسبة الغياب</th>
                                        <th class="p-3 text-center">درجة الإنذار</th>
                                        <th class="p-3 text-right">الإجراء الإداري المطلوب</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-surface-container-highest/60">
                                    @forelse($sheet['alert_students'] ?? [] as $stAlert)
                                        <tr class="hover:bg-surface-container-high/60 transition-colors">
                                            <td class="p-3 text-center text-on-surface-variant font-mono">{{ $stAlert['num'] }}</td>
                                            <td class="p-3 text-primary font-bold font-mono">{{ $stAlert['academic_id'] }}</td>
                                            <td class="p-3 font-bold text-on-surface">{{ $stAlert['name'] }}</td>
                                            <td class="p-3 text-on-surface-variant">{{ $stAlert['branch_year'] }}</td>
                                            <td class="p-3 text-on-surface font-semibold">{{ $stAlert['courses'] }}</td>
                                            <td class="p-3 text-center font-mono">{{ $stAlert['total_sess'] }}</td>
                                            <td class="p-3 text-center font-mono font-bold text-emerald-400">{{ $stAlert['attended'] }}</td>
                                            <td class="p-3 text-center font-mono font-bold text-rose-400">{{ $stAlert['absent'] }}</td>
                                            <td class="p-3 text-center font-mono font-bold text-rose-400 bg-rose-950/20">{{ $stAlert['absence_rate'] }}</td>
                                            <td class="p-3 text-center">
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $stAlert['level_badge'] }}">
                                                    {{ $stAlert['level'] }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-rose-300 font-semibold">{{ $stAlert['action'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="p-8 text-center text-emerald-400 font-bold">
                                                <span class="material-symbols-outlined text-3xl mb-1 block">verified</span>
                                                لا توجد حالات إنذار أو حرمان؛ كافة الطلاب ملتزمون بنسب الحضور المعتمدة!
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            @else
                <!-- Course / Master Spreadsheet Table -->
                <div id="{{ $sheet['id'] }}" class="sheet-container w-full {{ $sheet['is_active'] ? '' : 'hidden' }}">
                    <table class="w-full border-collapse text-right font-body-sm text-body-sm whitespace-nowrap spreadsheet-table" id="table-{{ $sheet['id'] }}">
                        
                        <!-- Row 1: Excel Coordinate Letters A through Z -->
                        <thead>
                            <tr class="bg-surface-container text-on-surface-variant font-mono text-[11px] select-none border-b border-surface-container-highest">
                                <th class="w-10 px-2 py-1 text-center bg-surface-container-high border-l border-surface-container-highest text-on-surface-variant/80 freeze-col-num">#</th>
                                <th class="w-12 px-2 py-1 text-center border-l border-surface-container-highest freeze-col-num">A</th>
                                <th class="w-28 px-2 py-1 text-center border-l border-surface-container-highest freeze-col-acad">B</th>
                                <th class="w-56 px-2 py-1 text-center border-l border-surface-container-highest freeze-col-name">C</th>

                                @php
                                    $colLetterCode = 68; // ASCII 'D'
                                @endphp

                                @foreach($sheet['days_grouped'] as $dayKey => $dayData)
                                    <!-- Day attendance calculated column letter -->
                                    <th class="w-20 px-2 py-1 text-center border-l border-surface-container-highest bg-primary/5 text-primary">
                                        {{ chr($colLetterCode++) }}
                                    </th>
                                    <!-- Sessions letters -->
                                    @foreach($dayData['sessions'] as $sItem)
                                        <th class="w-20 px-2 py-1 text-center border-l border-surface-container-highest">
                                            {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                        </th>
                                    @endforeach
                                @endforeach

                                <!-- Metrics formula columns letters -->
                                <th class="w-20 px-2 py-1 text-center border-l border-surface-container-highest text-secondary">
                                    {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                </th>
                                <th class="w-20 px-2 py-1 text-center border-l border-surface-container-highest text-emerald-400">
                                    {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                </th>
                                <th class="w-20 px-2 py-1 text-center border-l border-surface-container-highest text-rose-400">
                                    {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                </th>
                                <th class="w-24 px-2 py-1 text-center border-l border-surface-container-highest text-primary font-bold">
                                    {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                </th>
                                <th class="w-20 px-2 py-1 text-center border-l border-surface-container-highest">
                                    {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                </th>
                                <th class="w-20 px-2 py-1 text-center border-l border-surface-container-highest text-secondary font-bold">
                                    {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                </th>
                                <th class="w-24 px-2 py-1 text-center border-l border-surface-container-highest text-emerald-400 font-bold">
                                    {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                </th>
                                <th class="w-32 px-3 py-1 text-center border-b border-surface-container-highest text-rose-300">
                                    {{ chr($colLetterCode <= 90 ? $colLetterCode++ : 65) }}
                                </th>
                            </tr>

                            <!-- Row 2: Institutional Hierarchical Table Header (Day bands) -->
                            <tr class="bg-surface-container-low text-on-surface font-label-md text-label-md border-b border-surface-container-highest">
                                <th class="p-2 text-center bg-surface-container-high border-l border-surface-container-highest font-mono text-[11px] text-on-surface-variant freeze-col-num">1</th>
                                <th class="p-2.5 text-center border-l border-surface-container-highest font-bold text-primary freeze-col-acad" colspan="3">
                                    بيانات الطالب الأكاديمية
                                </th>

                                @foreach($sheet['days_grouped'] as $dayKey => $dayData)
                                    @php
                                        $dayColspan = 1 + count($dayData['sessions']);
                                        $isEven = $loop->even;
                                    @endphp
                                    <th class="p-2 text-center border-l border-surface-container-highest font-bold {{ $isEven ? 'bg-surface-container/30' : 'bg-surface-container/60' }}" colspan="{{ $dayColspan }}">
                                        {{ $dayData['label'] }}
                                    </th>
                                @endforeach

                                <th class="p-2 text-center border-l border-surface-container-highest text-secondary bg-surface-container/80 font-bold" colspan="4">
                                    إحصاء الجلسات (Sessions)
                                </th>
                                <th class="p-2 text-center border-l border-surface-container-highest text-primary bg-surface-container/80 font-bold" colspan="3">
                                    إحصاء الأيام (Daily Basis)
                                </th>
                                <th class="p-2 text-center text-rose-300 bg-rose-950/20 font-bold">
                                    الإنذار الأكاديمي
                                </th>
                            </tr>

                            <!-- Row 3: Sub-Headers (Individual Sessions & Metrics) -->
                            <tr class="bg-surface text-on-surface-variant font-label-sm text-label-sm border-b border-surface-container-highest">
                                <th class="p-2 text-center bg-surface-container-high border-l border-surface-container-highest font-mono text-[11px] freeze-col-num">2</th>
                                <th class="px-2 py-2 text-center border-l border-surface-container-highest freeze-col-num">م</th>
                                <th class="px-3 py-2 text-right border-l border-surface-container-highest freeze-col-acad">الرقم الأكاديمي</th>
                                <th class="px-4 py-2 text-right border-l border-surface-container-highest freeze-col-name">اسم الطالب الرباعي</th>

                                @foreach($sheet['days_grouped'] as $dayKey => $dayData)
                                    <!-- دوام اليوم -->
                                    <th class="px-2 py-2 text-center border-l border-surface-container-highest text-primary bg-primary/5 font-bold" title="قاعدة احتساب دوام اليوم الأكاديمي: جلسة واحدة فما فوق">
                                        دوام اليوم
                                    </th>
                                    <!-- الجلسات -->
                                    @foreach($dayData['sessions'] as $sIndex => $sessItem)
                                        @php
                                            $shortCourseTitle = mb_substr($sessItem->course_title, 0, 14);
                                        @endphp
                                        <th class="px-2 py-2 text-center border-l border-surface-container-highest" title="{{ $sessItem->course_title }}">
                                            ج{{ $sIndex + 1 }}: {{ $shortCourseTitle }}
                                        </th>
                                    @endforeach
                                @endforeach

                                <!-- Totals Formula Headers -->
                                <th class="px-2 py-2 text-center border-l border-surface-container-highest font-semibold">
                                    المنعقدة ({{ $sheet['total_sessions_count'] }})
                                </th>
                                <th class="px-2 py-2 text-center border-l border-surface-container-highest text-emerald-400 font-bold">حضور</th>
                                <th class="px-2 py-2 text-center border-l border-surface-container-highest text-rose-400 font-bold">غياب</th>
                                <th class="px-2 py-2 text-center border-l border-surface-container-highest text-primary font-bold">نسبة الجلسات</th>
                                
                                <th class="px-2 py-2 text-center border-l border-surface-container-highest font-semibold">
                                    الكلية ({{ count($sheet['days_grouped']) }})
                                </th>
                                <th class="px-2 py-2 text-center border-l border-surface-container-highest text-secondary font-bold">المحضورة</th>
                                <th class="px-2 py-2 text-center border-l border-surface-container-highest text-emerald-400 font-bold">التزام الأيام</th>
                                <th class="px-3 py-2 text-center font-bold">الحالة الأكاديمية</th>
                            </tr>
                        </thead>

                        <!-- Excel Data Rows -->
                        <tbody class="divide-y divide-surface-container-highest font-mono text-xs">
                            @foreach($sheet['students'] as $rowIndex => $stRow)
                                @php
                                    $excelRowNum = $rowIndex + 3;
                                @endphp
                                <tr class="student-row hover:bg-surface-container-high/60 transition-colors group" 
                                    data-name="{{ $stRow['name'] }}" 
                                    data-academic-id="{{ $stRow['academic_id'] }}"
                                    data-status="{{ $stRow['academic_status'] }}"
                                    data-rate="{{ $stRow['session_rate'] }}">
                                    
                                    <td class="p-2 text-center bg-surface-container text-on-surface-variant font-mono text-[11px] border-l border-surface-container-highest freeze-col-num">
                                        {{ $excelRowNum }}
                                    </td>
                                    <td class="p-2 text-center border-l border-surface-container-highest text-on-surface-variant freeze-col-num">
                                        {{ $stRow['num'] }}
                                    </td>
                                    <td class="p-2 text-right border-l border-surface-container-highest text-primary font-bold freeze-col-acad">
                                        {{ $stRow['academic_id'] }}
                                    </td>
                                    <td class="p-2 text-right border-l border-surface-container-highest font-body-sm font-semibold text-on-surface freeze-col-name">
                                        {{ $stRow['name'] }}
                                    </td>

                                    @foreach($sheet['days_grouped'] as $dayKey => $dayData)
                                        @php
                                            $dayCell = $stRow['day_cells'][$dayKey] ?? ['status' => 'absent', 'label' => 'غائب', 'bg' => 'bg-rose-900/60 text-rose-300 font-bold'];
                                        @endphp
                                        <!-- دوام اليوم -->
                                        <td class="p-1.5 text-center border-l border-surface-container-highest {{ $dayCell['status'] === 'present' ? 'bg-emerald-950/20' : 'bg-rose-950/20' }}" 
                                            onclick="selectCell(this, 'دوام اليوم: {{ $dayCell['label'] }}')">
                                            <span class="inline-block px-2 py-0.5 rounded {{ $dayCell['bg'] }} font-bold font-body-sm">
                                                {{ $dayCell['label'] }}
                                            </span>
                                        </td>

                                        <!-- الجلسات -->
                                        @foreach($dayData['sessions'] as $sessItem)
                                            @php
                                                $sessCell = $stRow['session_cells'][$sessItem->lesson_id] ?? ['status' => 'absent', 'label' => 'غائب', 'bg' => 'bg-rose-900/40 text-rose-300 font-bold'];
                                            @endphp
                                            <td class="p-1.5 text-center border-l border-surface-container-highest" 
                                                onclick="selectCell(this, '{{ $sessItem->course_title }}: {{ $sessCell['label'] }}')">
                                                <span class="inline-block px-1.5 py-0.5 rounded {{ $sessCell['bg'] }} font-body-sm">
                                                    {{ $sessCell['label'] }}
                                                </span>
                                            </td>
                                        @endforeach
                                    @endforeach

                                    <!-- Formula Totals for this Student -->
                                    <td class="p-2 text-center border-l border-surface-container-highest text-on-surface font-semibold" onclick="selectCell(this, 'المنعقدة: {{ $stRow['total_sessions'] }}')">
                                        {{ $stRow['total_sessions'] }}
                                    </td>
                                    <td class="p-2 text-center border-l border-surface-container-highest font-bold text-emerald-400" onclick="selectCell(this, 'حضور: {{ $stRow['attended_sessions'] }}')">
                                        {{ $stRow['attended_sessions'] }}
                                    </td>
                                    <td class="p-2 text-center border-l border-surface-container-highest font-bold {{ $stRow['absent_sessions'] > 0 ? 'text-rose-400' : 'text-on-surface-variant' }}" onclick="selectCell(this, 'غياب: {{ $stRow['absent_sessions'] }}')">
                                        {{ $stRow['absent_sessions'] }}
                                    </td>
                                    <td class="p-2 text-center border-l border-surface-container-highest text-primary font-bold" onclick="selectCell(this, 'نسبة الجلسات: {{ $stRow['session_rate'] }}%')">
                                        {{ $stRow['session_rate'] }}%
                                    </td>

                                    <td class="p-2 text-center border-l border-surface-container-highest text-on-surface font-semibold" onclick="selectCell(this, 'أيام الدوام: {{ $stRow['total_days'] }}')">
                                        {{ $stRow['total_days'] }}
                                    </td>
                                    <td class="p-2 text-center border-l border-surface-container-highest font-bold text-secondary" onclick="selectCell(this, 'الأيام المحضورة: {{ $stRow['attended_days'] }}')">
                                        {{ $stRow['attended_days'] }}
                                    </td>
                                    <td class="p-2 text-center border-l border-surface-container-highest text-emerald-400 font-bold" onclick="selectCell(this, 'التزام الأيام: {{ $stRow['day_rate'] }}%')">
                                        {{ $stRow['day_rate'] }}%
                                    </td>

                                    <td class="p-2 text-center font-body-sm" onclick="selectCell(this, 'الحالة: {{ $stRow['status_label'] }}')">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-label-sm border {{ $stRow['badge_class'] }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $stRow['dot_class'] }}"></span>
                                            {{ $stRow['status_label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <!-- Excel Footer Row: System Auto-Calculation formulas -->
                        <tfoot class="bg-surface-container font-mono text-xs text-on-surface border-t-2 border-surface-container-highest">
                            <tr>
                                <td class="p-2 text-center bg-surface-container-high text-on-surface-variant font-bold border-l border-surface-container-highest freeze-col-num">Σ</td>
                                <td class="p-2.5 text-right font-headline-sm text-label-md text-primary font-bold border-l border-surface-container-highest freeze-col-acad" colspan="3">
                                    متوسط ونسب حضور الدفعة الإجمالية (SUM &amp; AVERAGE)
                                </td>

                                @foreach($sheet['days_grouped'] as $dayKey => $dayData)
                                    @php
                                        $dayPresent = $sheet['col_day_present_counts'][$dayKey] ?? 0;
                                        $dayTotal = count($sheet['students']);
                                        $dayPct = $dayTotal > 0 ? round(($dayPresent / $dayTotal) * 100, 1) : 0;
                                    @endphp
                                    <!-- Day percentage -->
                                    <td class="p-1.5 text-center border-l border-surface-container-highest text-emerald-400 font-bold bg-primary/5">
                                        {{ $dayPct }}%
                                    </td>
                                    <!-- Each session ratio -->
                                    @foreach($dayData['sessions'] as $sessItem)
                                        @php
                                            $sessPresent = $sheet['col_present_counts'][$sessItem->lesson_id] ?? 0;
                                        @endphp
                                        <td class="p-1.5 text-center border-l border-surface-container-highest text-on-surface-variant font-semibold">
                                            {{ $sessPresent }}/{{ $dayTotal }}
                                        </td>
                                    @endforeach
                                @endforeach

                                <!-- Formula Aggregates Totals -->
                                <td class="p-2 text-center border-l border-surface-container-highest font-bold text-on-surface">
                                    {{ array_sum(array_column($sheet['students'], 'total_sessions')) }}
                                </td>
                                <td class="p-2 text-center border-l border-surface-container-highest font-bold text-emerald-400">
                                    {{ $sheet['total_attended_all'] }}
                                </td>
                                <td class="p-2 text-center border-l border-surface-container-highest font-bold text-rose-400">
                                    {{ $sheet['total_absent_all'] }}
                                </td>
                                <td class="p-2 text-center border-l border-surface-container-highest font-bold text-primary">
                                    {{ $sheet['overall_session_rate'] }}%
                                </td>

                                <td class="p-2 text-center border-l border-surface-container-highest text-on-surface font-semibold">
                                    {{ $sheet['total_days_all'] }}
                                </td>
                                <td class="p-2 text-center border-l border-surface-container-highest text-secondary font-bold">
                                    {{ $sheet['total_days_attended'] }}
                                </td>
                                <td class="p-2 text-center border-l border-surface-container-highest text-emerald-400 font-bold">
                                    {{ $sheet['overall_day_rate'] }}%
                                </td>
                                <td class="p-2 text-center font-body-sm text-secondary font-bold">
                                    جاهز للاعتماد
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

        @endforeach

    </main>

    <!-- 5. Excel Bottom Sheet Status & Freeze Indicator Bar -->
    <footer class="px-6 py-2 bg-surface-container-high text-on-surface-variant border-t border-surface-container-highest flex flex-wrap items-center justify-between gap-4 font-label-sm text-label-sm select-none no-print">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5 text-emerald-400 font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>جاهز للتحرير والفرز (Excel Online Engine v16.0)</span>
            </div>
            <div class="h-4 w-px bg-surface-container-highest"></div>
            <div class="flex items-center gap-1 text-on-surface">
                <span class="material-symbols-outlined text-sm text-primary">view_carousel</span>
                <span id="sheetIndexLabel">ورقة عمل 1 من {{ count($sheets) }}</span>
            </div>
            <div class="h-4 w-px bg-surface-container-highest"></div>
            <div class="flex items-center gap-1 text-secondary">
                <span class="material-symbols-outlined text-sm">lock</span>
                <span>تم تجميد الأعمدة الرئيسية (Freeze Panes: A-C)</span>
            </div>
        </div>

        <!-- Live Excel Selection Quick Stats & Zoom Slider -->
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3 font-mono text-[11px] text-on-surface">
                <span>المتوسط: <strong id="bottomStatAvg" class="text-primary">{{ $sheets[0]['overall_session_rate'] ?? 0 }}%</strong></span>
                <span>العدد: <strong id="bottomStatCount" class="text-primary">{{ $sheets[0]['total_students'] ?? 0 }}</strong></span>
                <span>المجموع: <strong id="bottomStatTotal" class="text-emerald-400">{{ $sheets[0]['total_sessions_count'] ?? 0 }} جلسة</strong></span>
            </div>
            <div class="h-4 w-px bg-surface-container-highest"></div>

            <!-- Zoom and Views -->
            <div class="flex items-center gap-2">
                <button onclick="changeZoom(-10)" class="text-on-surface-variant hover:text-on-surface cursor-pointer p-1" title="تصغير" type="button">
                    <span class="material-symbols-outlined text-sm">remove</span>
                </button>
                <span id="zoomLabel" class="font-mono text-xs w-10 text-center text-on-surface">100%</span>
                <button onclick="changeZoom(10)" class="text-on-surface-variant hover:text-on-surface cursor-pointer p-1" title="تكبير" type="button">
                    <span class="material-symbols-outlined text-sm">add</span>
                </button>
            </div>

            <div class="flex items-center gap-1">
                <button onclick="window.print()" class="p-1 rounded hover:bg-surface text-primary cursor-pointer" title="طباعة الورقة الحالية" type="button">
                    <span class="material-symbols-outlined text-base">print</span>
                </button>
            </div>
        </div>
    </footer>

    <!-- Interactive Client Scripts -->
    <script>
        let currentZoom = 100;
        let currentActiveSheetId = '{{ $sheets[0]["id"] ?? "sheet-master" }}';

        // 1. Switch Sheets / Tabs
        function switchSheet(sheetId) {
            currentActiveSheetId = sheetId;

            // Hide all sheet containers
            document.querySelectorAll('.sheet-container').forEach(el => {
                el.classList.add('hidden');
            });

            // Show selected container
            const targetContainer = document.getElementById(sheetId);
            if (targetContainer) {
                targetContainer.classList.remove('hidden');
            }

            // Update Tab buttons styling
            const tabButtons = document.querySelectorAll('.tab-btn');
            let sheetIndex = 1;

            tabButtons.forEach((btn, idx) => {
                const btnSheetId = btn.getAttribute('data-sheet-id');
                const indicator = btn.querySelector('.tab-indicator');
                const dot = btn.querySelector('.tab-dot');

                if (btnSheetId === sheetId) {
                    sheetIndex = idx + 1;
                    btn.classList.add('active', 'bg-surface', 'text-primary', 'font-bold', 'shadow-[0_-2px_8px_rgba(0,0,0,0.25)]');
                    btn.classList.remove('text-on-surface-variant');
                    if (indicator) indicator.classList.remove('hidden');
                    if (dot) {
                        dot.classList.add('bg-emerald-500');
                        dot.classList.remove('bg-surface-variant');
                    }

                    // Update Top Formula & Bottom Stats
                    const stCount = btn.getAttribute('data-total-students') || '0';
                    const sessCount = btn.getAttribute('data-total-sessions') || '0';
                    const rate = btn.getAttribute('data-overall-rate') || '0';

                    document.getElementById('formulaStatStudents').innerText = stCount;
                    document.getElementById('formulaStatSessions').innerText = sessCount;
                    document.getElementById('formulaStatRate').innerText = rate + '%';

                    document.getElementById('bottomStatAvg').innerText = rate + '%';
                    document.getElementById('bottomStatCount').innerText = stCount;
                    document.getElementById('bottomStatTotal').innerText = sessCount + ' جلسة';

                } else {
                    btn.classList.remove('active', 'bg-surface', 'text-primary', 'font-bold', 'shadow-[0_-2px_8px_rgba(0,0,0,0.25)]');
                    btn.classList.add('text-on-surface-variant');
                    if (indicator) indicator.classList.add('hidden');
                    if (dot) {
                        dot.classList.remove('bg-emerald-500');
                        dot.classList.add('bg-surface-variant');
                    }
                }
            });

            document.getElementById('sheetIndexLabel').innerText = `ورقة عمل ${sheetIndex} من ${tabButtons.length}`;
            resetFilters();
        }

        // 2. Select Cell (Excel Active Cell feedback)
        function selectCell(tdElement, formulaDesc) {
            document.querySelectorAll('td.cell-active').forEach(td => td.classList.remove('cell-active'));
            tdElement.classList.add('cell-active');

            // Find cell row and column
            const row = tdElement.closest('tr');
            if (!row) return;

            const rowIndex = row.rowIndex; // 1-indexed in DOM table
            const cellIndex = tdElement.cellIndex;
            const colLetter = String.fromCharCode(64 + cellIndex);

            document.getElementById('activeCellCoordinate').innerText = `${colLetter}${rowIndex}`;
            if (formulaDesc) {
                document.getElementById('formulaCellRange').innerText = formulaDesc;
            }
        }

        // 3. Quick Search & Status Filter
        function applyFilters() {
            const query = (document.getElementById('quickSearchInput').value || '').trim().toLowerCase();
            const statusFilter = document.getElementById('statusFilterSelect').value;

            const activeSheet = document.getElementById(currentActiveSheetId);
            if (!activeSheet) return;

            const rows = activeSheet.querySelectorAll('.student-row');
            rows.forEach(row => {
                const name = (row.getAttribute('data-name') || '').toLowerCase();
                const acadId = (row.getAttribute('data-academic-id') || '').toLowerCase();
                const status = row.getAttribute('data-status') || '';
                const rate = parseFloat(row.getAttribute('data-rate') || '0');

                // Check text search
                const matchesQuery = !query || name.includes(query) || acadId.includes(query);

                // Check status filter
                let matchesStatus = true;
                if (statusFilter === '100') {
                    matchesStatus = (rate >= 99.9);
                } else if (statusFilter === 'warning') {
                    matchesStatus = (status === 'warning' || status === 'deprived');
                } else if (statusFilter === 'deprived') {
                    matchesStatus = (status === 'deprived');
                }

                if (matchesQuery && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function resetFilters() {
            document.getElementById('quickSearchInput').value = '';
            document.getElementById('statusFilterSelect').value = 'all';
            applyFilters();
        }

        // 4. Zoom Viewport Controls
        function changeZoom(delta) {
            currentZoom = Math.max(70, Math.min(140, currentZoom + delta));
            document.getElementById('zoomLabel').innerText = currentZoom + '%';
            
            const tables = document.querySelectorAll('.spreadsheet-table');
            tables.forEach(table => {
                table.style.fontSize = (currentZoom / 100 * 0.75) + 'rem';
            });
        }

        // 5. Download active sheet as .XLS file
        function downloadWorkbookAsExcel() {
            const activeSheet = document.getElementById(currentActiveSheetId);
            if (!activeSheet) return;

            const table = activeSheet.querySelector('table');
            if (!table) {
                window.print();
                return;
            }

            const activeTab = document.getElementById('tab-btn-' + currentActiveSheetId);
            const sheetName = activeTab ? activeTab.innerText.trim().replace(/\s+/g, '_') : 'attendance_sheet';

            const html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <style>
                        table { direction: rtl; font-family: 'Segoe UI', Tahoma, sans-serif; border-collapse: collapse; }
                        th { background-color: #282a30; color: #fff; border: 1px solid #4a5568; padding: 6px; }
                        td { border: 1px solid #ddd; padding: 6px; text-align: center; }
                    </style>
                </head>
                <body>
                    ${table.outerHTML}
                </body>
                </html>
            `;

            const blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `سجل_الحضور_${sheetName}_${new Date().toISOString().slice(0,10)}.xls`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
