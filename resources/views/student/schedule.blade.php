@extends('layouts.student')
@section('title', 'جدولي الدراسي')
@section('subtitle', 'جدول المحاضرات الأسبوعي')

@push('styles')
<style>
    /* ===== Timetable Grid ===== */
    .timetable-wrapper {
        overflow-x: auto;
        border-radius: 1.25rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }

    .timetable {
        width: 100%;
        min-width: 650px;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        overflow: hidden;
    }

    /* Header row — days */
    .timetable thead th {
        background: #1a2633;
        color: var(--accent-color);
        font-weight: 800;
        font-size: 0.9rem;
        padding: 1rem 0.75rem;
        text-align: center;
        border-bottom: 2px solid var(--accent-color);
    }
    .timetable thead th:first-child {
        background: #111b26;
        color: var(--text-secondary);
        font-size: 0.82rem;
        font-weight: 700;
        width: 90px;
    }

    /* Time column */
    .timetable tbody td.time-col {
        background: var(--bg-primary);
        color: var(--accent-color);
        font-weight: 800;
        font-size: 0.82rem;
        text-align: center;
        padding: 0.6rem 0.5rem;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
        vertical-align: middle;
        min-width: 80px;
    }

    /* Regular cells */
    .timetable tbody td.day-cell {
        padding: 0.4rem;
        border-bottom: 1px solid var(--border-color);
        border-right: 1px solid var(--border-color);
        vertical-align: middle;
        min-width: 120px;
        position: relative;
    }
    .timetable tbody tr:last-child td { border-bottom: none; }

    /* Course block inside cell */
    .course-block {
        background: var(--accent-color);
        color: #1a1a1a;
        border-radius: 0.65rem;
        padding: 0.5rem 0.65rem;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.4;
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    .course-block .cb-name { font-size: 0.82rem; font-weight: 800; }
    .course-block .cb-info { font-size: 0.72rem; opacity: 0.75; }

    /* Color variants */
    .cb-blue  { background: hsl(210,80%,92%); color: hsl(210,60%,25%); }
    .cb-green { background: hsl(150,70%,88%); color: hsl(150,50%,22%); }
    .cb-purple{ background: hsl(270,70%,92%); color: hsl(270,50%,28%); }
    .cb-orange{ background: hsl(30,80%,90%);  color: hsl(30,55%,28%);  }
    .cb-red   { background: hsl(0,70%,90%);   color: hsl(0,50%,30%);   }
    .cb-teal  { background: hsl(180,60%,88%); color: hsl(180,45%,22%); }
    .cb-yellow{ background: var(--accent-color); color: #1a1a1a; }

    /* Empty cell */
    .empty-cell { color: var(--border-color); text-align: center; font-size: 1rem; }

    /* ===== Exams ===== */
    .exam-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 0.6rem;
        display: flex; align-items: center; gap: 1rem;
        box-shadow: var(--shadow);
        border-right: 4px solid #ef4444;
    }
    .exam-icon {
        background: #fee2e2; color: #ef4444;
        width: 44px; height: 44px; border-radius: 0.75rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
</style>
@endpush

@section('content')

@php
    // Define time slots (generate from schedule data)
    $allTimes = $schedules->map(function($s) {
        return \Carbon\Carbon::parse($s->start_time)->format('H:i')
               . ' - '
               . \Carbon\Carbon::parse($s->end_time)->format('H:i');
    })->unique()->sort()->values();

    $grouped = $schedules->groupBy('day');

    $colors = ['cb-yellow','cb-blue','cb-green','cb-purple','cb-orange','cb-red','cb-teal'];
    // Assign consistent color per course
    $courseColors = [];
    $ci = 0;
    foreach($schedules as $s){
        if(!isset($courseColors[$s->course_id])){
            $courseColors[$s->course_id] = $colors[$ci % count($colors)];
            $ci++;
        }
    }
@endphp

{{-- ===== Timetable Grid ===== --}}
@if($schedules->isNotEmpty())
<div class="timetable-wrapper">
    <table class="timetable">
        <thead>
            <tr>
                <th>الوقت</th>
                @foreach($days as $dayEn => $dayAr)
                    @if($dayEn !== 'Friday' && $dayEn !== 'Saturday')
                        <th>{{ $dayAr }}</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($allTimes as $slot)
            @php
                list($slotStart, $slotEnd) = array_map('trim', explode('-', $slot));
            @endphp
            <tr>
                <td class="time-col">
                    <div>{{ $slotStart }}</div>
                    <div style="font-weight:400; font-size:0.72rem; opacity:0.7;">{{ $slotEnd }}</div>
                </td>
                @foreach($days as $dayEn => $dayAr)
                    @if($dayEn !== 'Friday' && $dayEn !== 'Saturday')
                    <td class="day-cell">
                        @php
                            $daySchedules = $grouped->get($dayEn) ?? $grouped->get($dayAr) ?? collect();
                            $match = $daySchedules->first(function($s) use($slotStart){
                                return \Carbon\Carbon::parse($s->start_time)->format('H:i') === $slotStart;
                            });
                        @endphp
                        @if($match)
                            <div class="course-block {{ $courseColors[$match->course_id] ?? 'cb-yellow' }}">
                                <span class="cb-name">{{ $match->course_title }}</span>
                                @if($match->teacher_name ?? false)
                                    <span class="cb-info"><i class="fa-solid fa-user" style="font-size:0.65rem;"></i> {{ $match->teacher_name }}</span>
                                @endif
                                @if($match->room ?? false)
                                    <span class="cb-info"><i class="fa-solid fa-door-open" style="font-size:0.65rem;"></i> {{ $match->room }}</span>
                                @endif
                            </div>
                        @else
                            <span class="empty-cell">—</span>
                        @endif
                    </td>
                    @endif
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary); margin-bottom: 2rem;">
    <i class="fa-solid fa-calendar-xmark" style="font-size: 2.5rem; opacity: 0.4; display: block; margin-bottom: 0.75rem;"></i>
    لا يوجد جدول دراسي محدد حالياً
</div>
@endif

{{-- ===== Exams Grid ===== --}}
<p style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem;">
    <i class="fa-solid fa-pencil" style="color: #ef4444;"></i>
    مواعيد الامتحانات
</p>

@if($exams->isNotEmpty())
<div class="timetable-wrapper">
    <table class="timetable">
        <thead>
            <tr>
                <th style="text-align:right; padding-right:1.25rem;">المادة</th>
                <th>التاريخ</th>
                <th>الوقت</th>
                <th>القاعة</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exams as $exam)
            @php
                $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($exam->exam_date), false);
                if ($daysLeft < 0) {
                    $statusText = 'انتهى';
                    $statusClass = 'background:#f3f4f6; color:var(--text-secondary)';
                } elseif ($daysLeft == 0) {
                    $statusText = 'اليوم';
                    $statusClass = 'background:#fee2e2; color:#ef4444';
                } elseif ($daysLeft <= 3) {
                    $statusText = 'بعد ' . $daysLeft . ' يوم';
                    $statusClass = 'background:#fee2e2; color:#ef4444';
                } else {
                    $statusText = 'بعد ' . $daysLeft . ' يوم';
                    $statusClass = 'background:var(--accent-color); color:#1a1a1a';
                }
            @endphp
            <tr>
                <td class="day-cell" style="padding: 0.875rem 1.25rem; text-align:right;">
                    <div style="display:flex; align-items:center; gap:0.6rem;">
                        <div style="width:10px; height:10px; border-radius:50%; background:#ef4444; flex-shrink:0;"></div>
                        <span style="font-weight:700; font-size:0.9rem;">{{ $exam->course_title }}</span>
                    </div>
                </td>
                <td class="day-cell" style="text-align:center;">
                    <div style="font-weight:700; font-size:0.85rem;">{{ \Carbon\Carbon::parse($exam->exam_date)->format('d/m/Y') }}</div>
                    <div style="color:var(--text-secondary); font-size:0.75rem;">{{ \Carbon\Carbon::parse($exam->exam_date)->translatedFormat('l') ?: \Carbon\Carbon::parse($exam->exam_date)->format('D') }}</div>
                </td>
                <td class="day-cell" style="text-align:center;">
                    @if($exam->start_time ?? false)
                        <div style="font-weight:700; font-size:0.85rem;">{{ \Carbon\Carbon::parse($exam->start_time)->format('H:i') }}</div>
                    @else
                        <span style="color:var(--text-secondary);">—</span>
                    @endif
                </td>
                <td class="day-cell" style="text-align:center;">
                    @if($exam->room ?? false)
                        <div style="font-weight:700; font-size:0.85rem;">
                            <i class="fa-solid fa-door-open" style="color:var(--accent-color);"></i>
                            {{ $exam->room }}
                        </div>
                    @else
                        <span style="color:var(--text-secondary);">—</span>
                    @endif
                </td>
                <td class="day-cell" style="text-align:center;">
                    <span style="font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:2rem; white-space:nowrap; {{ $statusClass }}">
                        {{ $statusText }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-pencil" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
    لا توجد امتحانات مجدولة حالياً
</div>
@endif

@endsection
