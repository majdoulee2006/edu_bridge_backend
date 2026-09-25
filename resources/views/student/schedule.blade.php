@extends('layouts.student')
@section('title', 'جدولي الدراسي')
@section('subtitle', 'جدول المحاضرات والامتحانات')

@push('styles')
<style>
    /* ===== Custom Tab Bar (مثل تطبيق الموبايل) ===== */
    .schedule-tab-bar {
        display: flex;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        padding: 5px;
        border-radius: 9999px;
        margin-bottom: 1.75rem;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    }

    .tab-pill-btn {
        flex: 1;
        padding: 10px 18px;
        border-radius: 9999px;
        border: none;
        background: transparent;
        color: var(--text-secondary);
        font-family: inherit;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .tab-pill-btn.active {
        background: var(--accent-color);
        color: #101924;
        font-weight: 800;
        box-shadow: var(--glow-shadow);
    }

    /* ===== Days Circles Row (شريط الأيام الدائري) ===== */
    .day-circles-container {
        display: flex;
        align-items: center;
        gap: 14px;
        overflow-x: auto;
        padding: 6px 4px 16px;
        margin-bottom: 1.5rem;
        scrollbar-width: none;
    }
    .day-circles-container::-webkit-scrollbar { display: none; }

    .day-circle-btn {
        width: 72px;
        height: 72px;
        min-width: 72px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 1.5px solid var(--border-color);
        background: var(--bg-secondary);
        color: var(--text-secondary);
        font-family: inherit;
        font-weight: 700;
        font-size: 0.88rem;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .day-circle-btn:hover {
        transform: translateY(-2px);
        border-color: var(--accent-color);
    }

    .day-circle-btn.active {
        background: var(--accent-color);
        color: #101924;
        font-weight: 800;
        border-color: var(--accent-color);
        box-shadow: var(--glow-shadow);
        transform: translateY(-2px);
    }

    .day-circle-btn .day-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: currentColor;
        margin-top: 3px;
        opacity: 0.8;
    }

    /* ===== Day Subheader ===== */
    .day-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .day-title-wrap {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .day-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0;
    }

    .btn-dark-export {
        background: #1e1e1e;
        color: #ffffff;
        border: none;
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.2s;
    }
    .btn-dark-export:hover {
        background: #2a2a2a;
        transform: translateY(-1px);
    }

    .day-count-badge {
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 700;
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
    }

    /* ===== Timeline & Class Cards (نظام التايم لاين والكروت الفاخرة) ===== */
    .day-timeline-wrapper {
        display: none;
    }
    .day-timeline-wrapper.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .timeline-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        max-width: 820px;
    }

    .timeline-item {
        display: flex;
        align-items: flex-start;
        gap: 1.25rem;
        cursor: pointer;
    }

    .timeline-time-col {
        width: 100px;
        min-width: 100px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .time-badge {
        font-weight: 800;
        font-size: 0.9rem;
        color: var(--text-primary);
        padding: 4px 8px;
        border-radius: 10px;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .timeline-item.is-selected .time-badge {
        background: var(--accent-color);
        color: #101924;
    }

    .time-period {
        font-size: 0.74rem;
        color: var(--text-secondary);
        margin-top: 2px;
    }

    .timeline-connector {
        width: 2px;
        height: 70px;
        background: var(--border-color);
        margin-top: 8px;
    }

    .timeline-card-col {
        flex: 1;
        min-width: 0;
    }

    .class-card {
        background: var(--bg-secondary);
        border-radius: 22px;
        padding: 1.25rem 1.4rem;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        border: 2px solid transparent;
        transition: all 0.25s ease;
    }

    html:not(.dark) .class-card {
        background: #ffffff;
        border-color: #f1f5f9;
    }

    .timeline-item:hover .class-card,
    .timeline-item.is-selected .class-card {
        border-color: var(--accent-color);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        transform: translateY(-1px);
    }

    .card-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .card-duration-tag {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 10px;
        background: var(--bg-primary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .card-icon-bubble {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.12);
        color: #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
    }

    .card-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.85rem;
    }

    .card-bottom-row {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex-wrap: wrap;
        font-size: 0.82rem;
        color: var(--text-secondary);
    }

    .card-info-item {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .empty-day-state {
        text-align: center;
        padding: 3.5rem 1rem;
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
    }
    .empty-day-state i {
        font-size: 2.75rem;
        opacity: 0.35;
        margin-bottom: 0.85rem;
        display: block;
    }
    .empty-day-state p {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    /* ===== Exams View (كروت الامتحانات مثل الموبايل) ===== */
    .exam-cards-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        max-width: 820px;
    }

    .exam-mobile-card {
        background: var(--bg-secondary);
        border-radius: 20px;
        padding: 1.25rem 1.4rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--border-color);
        transition: all 0.2s;
    }

    html:not(.dark) .exam-mobile-card {
        background: #ffffff;
    }

    .exam-mobile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .exam-time-tag {
        font-size: 0.88rem;
        font-weight: 800;
        color: var(--text-secondary);
        min-width: 65px;
    }

    .exam-info-body {
        flex: 1;
        min-width: 0;
    }

    .exam-type-pill {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 800;
        color: #ef4444;
        background: rgba(239, 68, 68, 0.12);
        padding: 2px 8px;
        border-radius: 6px;
        margin-bottom: 4px;
    }

    .exam-subject-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 6px;
    }

    .exam-meta-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.78rem;
        color: var(--text-secondary);
    }

    .exam-meta-row span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .exam-date-box {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 8px 16px;
        text-align: center;
        min-width: 78px;
    }

    .exam-month {
        font-size: 0.72rem;
        color: var(--text-secondary);
    }

    .exam-day-num {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .exam-day-name {
        font-size: 0.72rem;
        color: var(--text-secondary);
    }

    .exam-notice-box {
        margin-top: 1.5rem;
        padding: 1rem 1.25rem;
        border-radius: 16px;
        background: rgba(245, 158, 11, 0.08);
        border: 1.5px solid rgba(245, 158, 11, 0.3);
        display: flex;
        align-items: center;
        gap: 0.85rem;
        color: #b45309;
        font-size: 0.85rem;
        font-weight: 700;
        max-width: 820px;
    }

    html.dark .exam-notice-box {
        color: #fde047;
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.25);
    }
</style>
@endpush

@section('content')

@php
    $weekDaysMap = [
        'Sunday'    => 'الأحد',
        'Monday'    => 'الاثنين',
        'Tuesday'   => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday'  => 'الخميس',
    ];

    $groupedSchedules = [];
    foreach ($weekDaysMap as $enDay => $arDay) {
        $groupedSchedules[$enDay] = $schedules->filter(function($s) use ($enDay, $arDay) {
            $d = trim($s->day);
            return strcasecmp($d, $enDay) === 0 || $d === $arDay;
        })->sortBy('start_time')->values();
    }

    // تحديد اليوم الحالي تلقائياً
    $currentDayName = now()->format('l'); // Sunday, Monday...
    $dayKeys = array_keys($weekDaysMap);
    $defaultDayKey = in_array($currentDayName, $dayKeys) ? $currentDayName : 'Sunday';
@endphp

{{-- ===== التاب العلوي مثل تطبيق الموبايل ===== --}}
<div class="schedule-tab-bar">
    <button type="button" class="tab-pill-btn active" id="tab-classes-btn" onclick="switchScheduleTab('classes')">
        <i class="fa-solid fa-graduation-cap"></i> جدول الحصص
    </button>
    <button type="button" class="tab-pill-btn" id="tab-exams-btn" onclick="switchScheduleTab('exams')">
        <i class="fa-solid fa-file-pen"></i> جدول الامتحانات
    </button>
</div>

{{-- ============================================================== --}}
{{-- 1. واجهة جدول الحصص (نفس تصميم الموبايل تماماً) --}}
{{-- ============================================================== --}}
<div id="classes-schedule-section">
    {{-- شريط الأيام الدائرية --}}
    <div class="day-circles-container">
        @foreach($weekDaysMap as $enDay => $arDay)
            @php
                $count = count($groupedSchedules[$enDay] ?? []);
            @endphp
            <button type="button" 
                    class="day-circle-btn {{ $enDay === $defaultDayKey ? 'active' : '' }}" 
                    id="day-btn-{{ $enDay }}" 
                    onclick="selectDay('{{ $enDay }}', '{{ $arDay }}', {{ $count }})">
                <span class="day-name">{{ $arDay }}</span>
                @if($count > 0)
                    <span class="day-dot"></span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- الترويسة وأزرار التصدير --}}
    <div class="day-header-row">
        <div class="day-title-wrap">
            <h4 class="day-title" id="current-day-title">
                {{ $defaultDayKey === $currentDayName ? 'برنامج اليوم (' . $weekDaysMap[$defaultDayKey] . ')' : 'برنامج ' . $weekDaysMap[$defaultDayKey] }}
            </h4>
            <div class="export-actions">
                <a href="{{ route('student.schedule.export_image') }}" class="btn-dark-export" title="تنزيل صورة البرنامج الأسبوعي الرسمي">
                    <i class="fa-solid fa-image" style="color: #3b82f6;"></i> صورة الجدول (PNG)
                </a>
            </div>
        </div>
        <div class="day-count-badge" id="current-day-count">
            {{ count($groupedSchedules[$defaultDayKey] ?? []) }} حصص
        </div>
    </div>

    {{-- خط المحاضرات الزمني والبطاقات (Timelines) --}}
    <div id="schedule-export-area" style="padding: 4px; border-radius: 20px;">
        @foreach($weekDaysMap as $enDay => $arDay)
            <div class="day-timeline-wrapper {{ $enDay === $defaultDayKey ? 'active' : '' }}" id="timeline-{{ $enDay }}">
                @if($groupedSchedules[$enDay]->isEmpty())
                    <div class="empty-day-state">
                        <i class="fa-regular fa-calendar-xmark"></i>
                        <p>لا توجد حصص مجدولة ليوم {{ $arDay }}</p>
                    </div>
                @else
                    <div class="timeline-list">
                        @foreach($groupedSchedules[$enDay] as $index => $lec)
                            @php
                                $start = !empty($lec->start_time) ? \Carbon\Carbon::parse($lec->start_time)->format('h:i') : '';
                                $startPeriod = !empty($lec->start_time) ? (\Carbon\Carbon::parse($lec->start_time)->format('A') === 'AM' ? 'ص' : 'م') : '';
                                $end = !empty($lec->end_time) ? \Carbon\Carbon::parse($lec->end_time)->format('h:i') : '';
                                $endPeriod = !empty($lec->end_time) ? (\Carbon\Carbon::parse($lec->end_time)->format('A') === 'AM' ? 'ص' : 'م') : '';
                                $duration = (!empty($lec->start_time) && !empty($lec->end_time)) ? round((strtotime($lec->end_time) - strtotime($lec->start_time)) / 60) . ' دقيقة' : 'محاضرة';
                                $isLast = $index === count($groupedSchedules[$enDay]) - 1;
                            @endphp
                            <div class="timeline-item {{ $index === 0 ? 'is-selected' : '' }}" onclick="highlightLecture(this)">
                                <div class="timeline-time-col">
                                    <div class="time-badge">{{ $start }} - {{ $end }}</div>
                                    <div class="time-period">{{ $startPeriod }} - {{ $endPeriod }}</div>
                                    @if(!$isLast)
                                        <div class="timeline-connector"></div>
                                    @endif
                                </div>
                                <div class="timeline-card-col">
                                    <div class="class-card">
                                        <div class="card-top-row">
                                            <span class="card-duration-tag"><i class="fa-regular fa-clock"></i> {{ $duration }}</span>
                                            <div class="card-icon-bubble">
                                                <i class="fa-solid fa-book-open"></i>
                                            </div>
                                        </div>
                                        <div class="card-title">{{ $lec->course_title }}</div>
                                        <div class="card-bottom-row">
                                            @if(!empty($lec->teacher_name))
                                                <span class="card-info-item">
                                                    <i class="fa-regular fa-user"></i> {{ $lec->teacher_name }}
                                                </span>
                                            @endif
                                            @if(!empty($lec->room))
                                                <span class="card-info-item">
                                                    <i class="fa-solid fa-location-dot"></i> {{ $lec->room }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- ============================================================== --}}
{{-- 2. واجهة جدول الامتحانات (نفس تصميم الموبايل تماماً) --}}
{{-- ============================================================== --}}
<div id="exams-schedule-section" style="display: none;">
    <div class="day-header-row">
        <div class="day-title-wrap">
            <h4 class="day-title">الامتحانات النهائية</h4>
            <div class="export-actions">
                <button type="button" onclick="downloadExamsAsPDF()" class="btn-dark-export">
                    <i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i> PDF
                </button>
                <button type="button" onclick="downloadExamsAsImage()" class="btn-dark-export">
                    <i class="fa-solid fa-image" style="color: #3b82f6;"></i> صورة
                </button>
            </div>
        </div>
    </div>

    <div id="exams-export-area" style="padding: 4px; border-radius: 20px;">
        @if($exams->isEmpty())
            <div class="empty-day-state">
                <i class="fa-solid fa-file-circle-xmark"></i>
                <p>لا يوجد برنامج امتحانات متاح حالياً</p>
            </div>
        @else
            <div class="exam-cards-list">
                @foreach($exams as $exam)
                    @php
                        $cDate = \Carbon\Carbon::parse($exam->exam_date);
                        $dayNum = $cDate->format('d');
                        $dayName = $cDate->translatedFormat('l') ?: $cDate->format('D');
                        $monthName = $cDate->translatedFormat('F') ?: $cDate->format('M');
                        $timeStr = !empty($exam->start_time ?? null) ? \Carbon\Carbon::parse($exam->start_time)->format('H:i') : $cDate->format('h:i A');
                        $duration = '120 دقيقة';
                    @endphp
                    <div class="exam-mobile-card">
                        <div class="exam-time-tag">{{ $timeStr }}</div>
                        <div class="exam-info-body">
                            <span class="exam-type-pill">نهائي</span>
                            <div class="exam-subject-title">{{ $exam->course_title }}</div>
                            <div class="exam-meta-row">
                                <span><i class="fa-regular fa-clock"></i> {{ $duration }}</span>
                                <span><i class="fa-solid fa-location-dot"></i> {{ $exam->room ?? 'القاعة الامتحانية' }}</span>
                            </div>
                        </div>
                        <div class="exam-date-box">
                            <div class="exam-month">{{ $monthName }}</div>
                            <div class="exam-day-num">{{ $dayNum }}</div>
                            <div class="exam-day-name">{{ $dayName }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="exam-notice-box">
                <i class="fa-solid fa-circle-info" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                <div>يرجى الحضور قبل موعد الامتحان بـ 15 دقيقة على الأقل وإحضار البطاقة الجامعية.</div>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/html2canvas.min.js') }}"></script>
<script src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
<script>
// تبديل التاب بين الحصص والامتحانات
function switchScheduleTab(tab) {
    const classesBtn = document.getElementById('tab-classes-btn');
    const examsBtn = document.getElementById('tab-exams-btn');
    const classesSec = document.getElementById('classes-schedule-section');
    const examsSec = document.getElementById('exams-schedule-section');

    if (tab === 'classes') {
        classesBtn.classList.add('active');
        examsBtn.classList.remove('active');
        classesSec.style.display = 'block';
        examsSec.style.display = 'none';
    } else {
        examsBtn.classList.add('active');
        classesBtn.classList.remove('active');
        classesSec.style.display = 'none';
        examsSec.style.display = 'block';
    }
}

// اختيار اليوم من الشريط الدائري
function selectDay(enDay, arDay, count) {
    // تحديث الأزرار الدائرية
    document.querySelectorAll('.day-circle-btn').forEach(btn => btn.classList.remove('active'));
    const targetBtn = document.getElementById('day-btn-' + enDay);
    if (targetBtn) targetBtn.classList.add('active');

    // إظهار تايم لاين اليوم المحدد وإخفاء الباقي
    document.querySelectorAll('.day-timeline-wrapper').forEach(wrap => wrap.classList.remove('active'));
    const targetTimeline = document.getElementById('timeline-' + enDay);
    if (targetTimeline) targetTimeline.classList.add('active');

    // تحديث الترويسة
    const nowDayEn = '{{ $currentDayName }}';
    const titleEl = document.getElementById('current-day-title');
    const countEl = document.getElementById('current-day-count');

    if (titleEl) {
        titleEl.textContent = (enDay === nowDayEn) ? `برنامج اليوم (${arDay})` : `برنامج ${arDay}`;
    }
    if (countEl) {
        countEl.textContent = `${count} حصص`;
    }
}

// تمييز الكرت المختار عند الضغط
function highlightLecture(element) {
    document.querySelectorAll('.timeline-item').forEach(el => el.classList.remove('is-selected'));
    element.classList.add('is-selected');
}

// تحميل بصيغة صورة أو PDF
function getExportBgColor() {
    return document.documentElement.classList.contains('dark') ? '#121214' : '#ffffff';
}

function downloadScheduleAsImage() {
    const target = document.querySelector('.day-timeline-wrapper.active') || document.getElementById('schedule-export-area');
    if (!target) return;

    html2canvas(target, { scale: 2, useCORS: true, backgroundColor: getExportBgColor() }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'جدول_الحصص.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
}

function downloadScheduleAsPDF() {
    const target = document.querySelector('.day-timeline-wrapper.active') || document.getElementById('schedule-export-area');
    if (!target) return;

    const opt = {
        margin: 0.4,
        filename: 'جدول_الحصص.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: getExportBgColor() },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(target).save();
}

function downloadExamsAsImage() {
    const target = document.getElementById('exams-export-area');
    if (!target) return;

    html2canvas(target, { scale: 2, useCORS: true, backgroundColor: getExportBgColor() }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'جدول_الامتحانات.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
}

function downloadExamsAsPDF() {
    const target = document.getElementById('exams-export-area');
    if (!target) return;

    const opt = {
        margin: 0.4,
        filename: 'جدول_الامتحانات.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: getExportBgColor() },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(target).save();
}
</script>
@endpush
