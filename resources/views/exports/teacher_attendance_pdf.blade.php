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
            background-color: #fcfcfa;
            color: #1a1c1e;
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
<body class="bg-[#fcfcfa] text-[#1a1c1e] p-4 text-[11px] leading-normal">

<div class="w-full bg-[#fcfcfa] text-[#1a1c1e] rounded-xl p-4 flex flex-col relative overflow-hidden border border-[#d8d2c4]/60">

    <!-- 1. INSTITUTIONAL HEADER & VERIFICATION STRIP (3-Columns Layout) -->
    <header class="relative z-10 pb-4 border-b-2 border-[#d8d2c4] flex items-center justify-between gap-4">
        
        <!-- Right: Official Ministry / UNRWA Identity -->
        <div class="flex items-center gap-3 text-right">
            <img alt="شعار الأونروا المعتمد" class="w-16 h-16 object-contain rounded-lg drop-shadow-xs" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAxVPDLAOqoD1dBeRbcLdqCmQsNm9nLGxx8p9XbZs6Jg-oi7y4koXP0iCOXdaq7rNGQCF_pu5sMsSeg8t0lPLyW-gABp2o2DYvDfWouHLRLtgMGH9GzJ6fykG93nCVE2ou0UwwQ_8w_XokYP6yAQIY12YwNQefZOTw8Il93T6zXe5yaAaQJHg52PwBEzuIrlgvE8ym_Al9B9NEtkatlwswWUT7cvchrGsrMcBdQFMWvrlg_EvkDN09l9vHZTpWB5Aabi2Y">
            <div class="flex flex-col">
                <span class="text-[12px] font-bold text-[#343a40] leading-tight">الجمهورية العربية السورية</span>
                <span class="text-[12px] font-bold text-[#005a9c] leading-tight">وكالة الأمم المتحدة لإغاثة وتشغيل اللاجئين الفلسطينيين</span>
                <span class="text-[13px] font-black text-[#1b2234] leading-tight mt-0.5">معهد دمشق المتوسط (DTC) - دمشق</span>
                <span class="text-[10px] font-semibold text-[#6c757d]">برنامج التعليم والتدريب الفني والمهني (VTC/TVET)</span>
            </div>
        </div>

        <!-- Center: Official Title & Scope Period -->
        <div class="flex flex-col items-center text-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 bg-[#f4ecd8] text-[#855800] rounded-full text-[11px] font-black tracking-wide mb-1 shadow-2xs">
                <span class="material-symbols-outlined text-[15px]">verified_user</span>
                <span>وثيقة وسجل أكاديمي رسمي معتمد</span>
            </div>
            <h1 class="text-[20px] font-black text-[#181d27] tracking-tight">الملخص الإحصائي المعتمد للحضور والغياب الأكاديمي</h1>
            <p class="text-[12px] font-bold text-[#495057] mt-0.5">الفصل الدراسي الأول - العام الأكاديمي 2024 / 2025</p>
            <div class="flex items-center gap-1.5 mt-1 text-[11px] font-bold text-[#6a5b3a] bg-[#fff9ea] px-3 py-0.5 rounded-lg border border-[#e8dcb9]">
                <span class="material-symbols-outlined text-[13px]">event_repeat</span>
                <span>{{ $periodLabel ?? 'الفترة الأكاديمية المحددة' }}</span>
            </div>
        </div>

        <!-- Left: Edu-Bridge Digital Verification Box -->
        <div class="flex items-center gap-2 text-left">
            <div class="bg-[#fcfaf4] p-2.5 rounded-xl border border-[#e6dece] shadow-2xs flex flex-col items-end min-w-[190px]">
                <div class="flex items-center justify-between w-full border-b border-[#e6dece] pb-1.5 mb-1.5">
                    <div class="flex items-center gap-1.5">
                        <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-amber-500/20 to-amber-500/5 border border-amber-500/30 flex items-center justify-center text-amber-600">
                            <span class="material-symbols-outlined text-[14px]">school</span>
                        </div>
                        <div class="flex flex-col text-right">
                            <span class="font-bold text-[10px] text-[#1b2234] leading-tight">منظومة Edu-Bridge</span>
                            <span class="text-[8px] text-[#78716c] font-mono leading-tight">Digital System</span>
                        </div>
                    </div>
                    <div class="w-6 h-6 bg-white border border-[#d6cfbe] p-0.5 rounded flex items-center justify-center text-[#1b2234]">
                        <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-0.5 w-full text-right">
                    <div class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full bg-[#e6f7ec] text-[#0f6b39] font-bold text-[8.5px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#137a43]"></span>
                        <span>وثيقة رسمية معتمدة ومحققة آلياً</span>
                    </div>
                    <div class="text-[9.5px] font-mono font-bold text-[#343a40] mt-0.5">REF: DTC-ATT-{{ date('Y') }}-{{ $refCode ?? 'CIS-A1' }}</div>
                    <div class="text-[8.5px] text-[#78716c] font-semibold">تاريخ الإصدار: {{ $reportDateStr ?? date('d/m/Y') }}</div>
                </div>
            </div>
        </div>

    </header>

    <!-- 2. ACADEMIC METADATA SPECIFICATION BAR -->
    <section class="relative z-10 py-2.5 my-2.5 bg-[#f5f3ec] rounded-xl px-4 grid grid-cols-4 gap-2 text-[11px] border border-[#e5dfd0]">
        <div class="flex items-center gap-1.5 truncate">
            <span class="text-[#7c6f57] font-semibold">مدرس المقرر والمشرف:</span>
            <span class="text-[#1a1c1e] font-black truncate">{{ $teacherName ?? 'م. وسيم يوسف' }}</span>
        </div>
        <div class="flex items-center gap-1.5 truncate">
            <span class="text-[#7c6f57] font-semibold">القسم الأكاديمي:</span>
            <span class="text-[#1a1c1e] font-bold truncate">{{ $departmentName ?? 'هندسة وتكنولوجيا المعلومات' }}</span>
        </div>
        <div class="flex items-center gap-1.5 truncate">
            <span class="text-[#7c6f57] font-semibold">المستوى والشعبة:</span>
            <span class="text-[#1a1c1e] font-bold truncate">{{ $levelName ?? 'السنة الأولى - شعبة (A)' }}</span>
        </div>
        <div class="flex items-center gap-1.5 truncate">
            <span class="text-[#7c6f57] font-semibold">تاريخ الاستخراج:</span>
            <span class="text-[#1a1c1e] font-bold truncate">{{ $reportDateStr ?? date('d/m/Y') }}</span>
        </div>
    </section>

    <!-- 3. ATTENDANCE MATRIX TABLE (Daily Aggregate Rule: 1+ session = Full Day Present) -->
    <section class="relative z-10 w-full overflow-hidden my-1 rounded-xl border border-[#d6cfbe]">
        <table class="w-full text-right border-collapse text-[11px] bg-white">
            <thead>
                <tr class="bg-[#242b35] text-white font-bold text-center">
                    <th class="p-2 border-r border-[#3a4454] w-10" rowspan="2">#</th>
                    <th class="p-2 border-r border-[#3a4454] w-24" rowspan="2">الرقم الأكاديمي</th>
                    <th class="p-2 border-r border-[#3a4454] min-w-[180px] text-right pr-3" rowspan="2">اسم الطالب الرباعي</th>
                    <th class="p-2 border-r border-[#3a4454] bg-[#353f4e]" colspan="4">متابعة الدوام اليومي (قاعدة جلسة واحدة فأكثر)</th>
                    <th class="p-2 border-r border-[#3a4454] bg-[#433116] min-w-[140px]" rowspan="2">الحالة الأكاديمية والإنذار</th>
                    <th class="p-2 bg-[#1b2234] min-w-[140px]" rowspan="2">الإجراء الإداري والتوصية</th>
                </tr>
                <tr class="bg-[#353f4e] text-white text-[10px] font-semibold text-center border-t border-[#465366]">
                    <th class="py-1 px-2 border-r border-[#465366] w-20">أيام الدوام</th>
                    <th class="py-1 px-2 border-r border-[#465366] text-[#e8f7ee] w-20">أيام الحضور</th>
                    <th class="py-1 px-2 border-r border-[#465366] text-rose-300 w-20">غياب كامل</th>
                    <th class="py-1 px-2.5 border-r border-[#465366] bg-[#424e60] text-amber-300 font-bold w-24">نسبة الالتزام %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e3ded2] text-center font-medium">
                @forelse($studentsList as $index => $row)
                    @php
                        $isAlt = $index % 2 === 1;
                        $rateNum = floatval(rtrim($row['rate'], '%'));
                    @endphp
                    <tr class="hover:bg-[#faf7f0] transition-colors {{ $isAlt ? 'bg-[#fdfdfd]' : 'bg-white' }} {{ $rateNum < 80 ? '!bg-[#fff8f8]' : '' }}">
                        <!-- # -->
                        <td class="p-2 border-r border-[#e3ded2] font-bold text-[#495057]">{{ $index + 1 }}</td>
                        
                        <!-- Academic ID -->
                        <td class="p-2 border-r border-[#e3ded2] font-mono text-[#1a1c1e] font-bold">{{ $row['academic_id'] }}</td>
                        
                        <!-- Student Name -->
                        <td class="p-2 border-r border-[#e3ded2] text-right font-bold text-[#1b2234] pr-3">
                            <div class="flex items-center justify-between">
                                <span class="{{ $rateNum < 80 ? 'text-[#991b1b]' : '' }}">{{ $row['name'] }}</span>
                                @if($rateNum < 80)
                                    <span class="material-symbols-outlined text-[15px] text-[#dc2626]">warning</span>
                                @endif
                            </div>
                        </td>

                        <!-- أيام الدوام -->
                        <td class="p-1.5 border-r border-[#e3ded2] font-semibold text-[#1a1c1e]">{{ $row['total_days'] }}</td>

                        <!-- أيام الحضور -->
                        <td class="p-1.5 border-r border-[#e3ded2] font-bold text-[#137a43]">{{ $row['attended_days'] }}</td>

                        <!-- غياب كامل -->
                        <td class="p-1.5 border-r border-[#e3ded2] font-bold {{ $row['absent_days'] > 0 ? 'text-[#dc2626]' : 'text-[#71717a]' }}">
                            {{ $row['absent_days'] }}
                        </td>

                        <!-- نسبة الالتزام % -->
                        <td class="p-1.5 border-r border-[#e3ded2] bg-[#fbf9f4] font-black {{ $rateNum >= 85 ? 'text-[#137a43]' : ($rateNum >= 80 ? 'text-[#d97706]' : 'text-[#dc2626]') }}">
                            {{ $row['rate'] }}
                        </td>

                        <!-- الحالة الأكاديمية والإنذار -->
                        <td class="p-1.5 border-r border-[#e3ded2]">
                            @if($rateNum == 100)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#e6f7ec] text-[#0f6b39] font-bold text-[10px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#137a43]"></span>
                                    <span>ملتزم تماماً</span>
                                </span>
                            @elseif($rateNum >= 85)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#f3f4f6] text-[#374151] font-bold text-[10px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
                                    <span>حضور نظامي</span>
                                </span>
                            @elseif($rateNum >= 80)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#fef9c3] text-[#854d0e] font-bold text-[10px] border border-[#fde047]">
                                    <span class="material-symbols-outlined text-[13px]">info</span>
                                    <span>إنذار أولي (15%)</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#fef2f2] text-[#b91c1c] font-black text-[10px] border border-[#fca5a5]">
                                    <span class="material-symbols-outlined text-[13px]">notification_important</span>
                                    <span>حرمان أولي (20%)</span>
                                </span>
                            @endif
                        </td>

                        <!-- الإجراء الإداري والتوصية -->
                        <td class="p-1.5 text-[11px] font-semibold {{ $rateNum >= 85 ? 'text-[#137a43]' : ($rateNum >= 80 ? 'text-[#854d0e]' : 'text-[#b91c1c]') }}">
                            @if($rateNum == 100)
                                طبيعي - تميز أكاديمي
                            @elseif($rateNum >= 85)
                                طبيعي - دون ملاحظات
                            @elseif($rateNum >= 80)
                                تنبيه ومتابعة إدارية
                            @else
                                تنبيه خطي + استدعاء ولي أمر
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-6 text-center text-[#78716c] font-bold">
                            لا توجد بيانات حضور مسجلة للطلاب في النطاق المحدد
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-[#f0ebe0] font-bold text-[#1b2234] border-t-2 border-[#c8beaa]">
                    <td class="p-2.5 text-right pr-4 font-black" colspan="3">
                        المجموع العام الإجمالي للشعبة ({{ count($studentsList) }} طالباً مسجلاً - عينة التقرير)
                    </td>
                    <td class="p-2 border-r border-[#d4cbba] text-center font-bold">{{ $summaryTotals['days'] ?? 0 }}</td>
                    <td class="p-2 border-r border-[#d4cbba] text-center text-[#137a43] font-bold">{{ $summaryTotals['attended'] ?? 0 }}</td>
                    <td class="p-2 border-r border-[#d4cbba] text-center text-[#b91c1c] font-bold">{{ $summaryTotals['absent'] ?? 0 }}</td>
                    <td class="p-2 border-r border-[#d4cbba] text-center text-[#855800] font-black">{{ $summaryTotals['avg_rate'] ?? '0%' }}</td>
                    <td class="p-2 border-r border-[#d4cbba] text-center text-[#991b1b] font-black">
                        {{ $summaryTotals['warnings_count'] ?? 0 }} إنذار / حرمان
                    </td>
                    <td class="p-2 text-center text-[#0f6b39] font-black">مصدق ومعتمد رسمياً</td>
                </tr>
            </tfoot>
        </table>
    </section>

    <!-- 4. REGULATORY ACADEMIC GUIDANCE LEGEND CARDS -->
    <section class="relative z-10 grid grid-cols-2 gap-3 my-2.5">
        <!-- Rule 1: Daily Presence Calculation Rule -->
        <div class="bg-[#fcfaf4] p-3 rounded-xl border border-[#e6dece] flex items-start gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-[#efe7d3] text-[#855800] flex items-center justify-center shrink-0 mt-0.5">
                <span class="material-symbols-outlined text-[16px]">rule</span>
            </div>
            <div>
                <h4 class="text-[11.5px] font-black text-[#2e2617]">القاعدة الأكاديمية لاحتساب حضور اليوم:</h4>
                <p class="text-[10.5px] text-[#5c5344] leading-relaxed mt-0.5">
                    يُعد الطالب <strong class="text-[#137a43]">"حاضراً"</strong> في اليوم كاملاً بمجرد حضوره جلسة واحدة على الأقل من الجلسات المنعقدة خلال ذلك اليوم، مع بقاء رصد المادة أو الجلسة التي غاب عنها كـ <strong class="text-[#b91c1c]">"غائب"</strong> بدقة متناهية لاحتساب نصاب الساعات الأكاديمية للمقرر.
                </p>
            </div>
        </div>

        <!-- Rule 2: Academic Deprivation & Warning Regulations -->
        <div class="bg-[#fcfaf4] p-3 rounded-xl border border-[#e6dece] flex items-start gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-[#fee2e2] text-[#b91c1c] flex items-center justify-center shrink-0 mt-0.5">
                <span class="material-symbols-outlined text-[16px]">gavel</span>
            </div>
            <div>
                <h4 class="text-[11.5px] font-black text-[#991b1b]">ضوابط الإنذار الأكاديمي والحرمان النهائي:</h4>
                <p class="text-[10.5px] text-[#5c5344] leading-relaxed mt-0.5">
                    تجاوز نسبة الغياب غير المبرر <strong class="text-[#dc2626]">15%</strong> يوجب توجيه إنذار خطي أولي يبلغ به ولي الأمر؛ وتجاوز نسبة <strong class="text-[#991b1b]">20%</strong> يترتب عليه حرمان الطالب تلقائياً من التقدم للامتحان النهائي للمقرر وتثبيت نتيجته (محروم).
                </p>
            </div>
        </div>
    </section>

    <!-- 5. OFFICIAL SIGN-OFF BLOCK (Instructor & Head of Department ONLY, NO signatures, NO stamps, NO student affairs) -->
    <footer class="relative z-10 pt-3 mt-2 border-t-2 border-[#d8d2c4] flex items-center justify-around text-center">
        <!-- Instructor -->
        <div class="flex flex-col items-center">
            <span class="text-[11px] font-bold text-[#495057]">مدرس المقرر / مشرف الدورة</span>
            <span class="text-[13px] font-black text-[#1b2234] mt-0.5">{{ $teacherName ?? 'م. وسيم يوسف' }}</span>
        </div>

        <!-- Head of Department -->
        <div class="flex flex-col items-center">
            <span class="text-[11px] font-bold text-[#495057]">رئيس قسم تكنولوجيا المعلومات</span>
            <span class="text-[13px] font-black text-[#1b2234] mt-0.5">د. أحمد ديب</span>
        </div>
    </footer>

    <!-- 6. BOTTOM SECURITY & AUDIT TRAIL METADATA STRIP -->
    <div class="relative z-10 mt-3 pt-2 border-t border-[#e2dcd0] flex items-center justify-between text-[9.5px] text-[#78716c] font-mono">
        <div>DTC-SEC-HASH: {{ substr(md5(($teacherName ?? '') . ($reportDateStr ?? '')), 0, 16) }} | SYSTEM ID: DTC-ATT-{{ date('Ymd') }}-{{ rand(100000, 999999) }}-SYS</div>
        <div class="flex items-center gap-1.5">
            <span>دقة الإخراج: 300 DPI Ultra Clear</span>
            <span>•</span>
            <span>منظومة Edu-Bridge للتوثيق الأكاديمي الرقمي</span>
        </div>
    </div>

</div>

</body>
</html>
