@extends('layouts.parent')
@section('title', 'الجدول الدراسي')

@push('styles')
<style>
    .timetable-wrapper {
        overflow-x: auto;
        border-radius: 1.25rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }

    .timetable {
        width: 100%;
        min-width: 750px;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        overflow: hidden;
    }

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
        width: 100px;
    }

    .timetable tbody td.time-col {
        background: var(--bg-primary);
        color: var(--accent-color);
        font-weight: 800;
        font-size: 0.82rem;
        text-align: center;
        padding: 0.8rem 0.5rem;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
        vertical-align: middle;
        min-width: 100px;
    }

    .timetable tbody td.day-cell {
        padding: 0.6rem;
        border-bottom: 1px solid var(--border-color);
        border-right: 1px solid var(--border-color);
        vertical-align: middle;
        min-width: 150px;
        position: relative;
    }
    .timetable tbody tr:last-child td { border-bottom: none; }

    .course-block {
        border-radius: 0.65rem;
        padding: 0.6rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1.4;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .course-block .cb-name { font-size: 0.85rem; font-weight: 800; }
    .course-block .cb-info { font-size: 0.75rem; opacity: 0.8; }

    /* Color variants */
    .cb-blue  { background: hsl(210,80%,92%); color: hsl(210,60%,25%); }
    .cb-green { background: hsl(150,70%,88%); color: hsl(150,50%,22%); }
    .cb-purple{ background: hsl(270,70%,92%); color: hsl(270,50%,28%); }
    .cb-orange{ background: hsl(30,80%,90%);  color: hsl(30,55%,28%);  }
    .cb-red   { background: hsl(0,70%,90%);   color: hsl(0,50%,30%);   }
    .cb-teal  { background: hsl(180,60%,88%); color: hsl(180,45%,22%); }
    .cb-yellow{ background: var(--accent-color); color: #1a1a1a; }

    .empty-cell { color: var(--text-secondary); opacity: 0.3; text-align: center; font-size: 1rem; }
</style>
@endpush

@section('content')

@php
    $days = [
        'Sunday' => 'الأحد',
        'Monday' => 'الاثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
        'Saturday' => 'السبت',
    ];
@endphp

@if(!$selected_child_id)
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
        <i class="fa-solid fa-child" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5; margin-bottom: 1rem; display: block;"></i>
        <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">يرجى اختيار ابن أولاً</h4>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">يرجى اختيار الابن من القائمة في الأعلى أو إضافة ابن من تبويب "أبنائي".</p>
    </div>
@else
    @section('subtitle')
        الجدول الدراسي الأسبوعي للابن: {{ $selected_child->full_name }}
    @endsection

    @php
        // Prepare list of times
        $allTimes = collect();
        foreach($schedules as $dayName => $daySchedules) {
            foreach($daySchedules as $s) {
                $allTimes->push(\Carbon\Carbon::parse($s->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($s->end_time)->format('H:i'));
            }
        }
        $allTimes = $allTimes->unique()->sort()->values();

        $colors = ['cb-yellow','cb-blue','cb-green','cb-purple','cb-orange','cb-red','cb-teal'];
        $courseColors = [];
        $ci = 0;
        foreach($schedules as $dayName => $daySchedules) {
            foreach($daySchedules as $s){
                if(!isset($courseColors[$s->course_id])){
                    $courseColors[$s->course_id] = $colors[$ci % count($colors)];
                    $ci++;
                }
            }
        }
    @endphp

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
                                    $daySchedules = $schedules->get($dayEn) ?? $schedules->get($dayAr) ?? collect();
                                    $match = $daySchedules->first(function($s) use($slotStart){
                                        return \Carbon\Carbon::parse($s->start_time)->format('H:i') === $slotStart;
                                    });
                                @endphp
                                @if($match)
                                    <div class="course-block {{ $courseColors[$match->course_id] ?? 'cb-yellow' }}">
                                        <span class="cb-name">{{ $match->course_title }}</span>
                                        @if($match->teacher_name)
                                            <span class="cb-info"><i class="fa-solid fa-user" style="font-size:0.65rem; margin-left:0.25rem;"></i> {{ $match->teacher_name }}</span>
                                        @endif
                                        @if($match->room)
                                            <span class="cb-info"><i class="fa-solid fa-door-open" style="font-size:0.65rem; margin-left:0.25rem;"></i> قاعة: {{ $match->room }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="empty-cell">—</div>
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
        <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
            <i class="fa-solid fa-calendar-days" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.4; margin-bottom: 1rem; display: block;"></i>
            <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">لا يوجد جدول دراسي محدد</h4>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">لم يتم تسجيل أي محاضرات أو مواد دراسية للابن {{ $selected_child->full_name }} في هذا الفصل الدراسي بعد.</p>
        </div>
    @endif
@endif

@endsection
