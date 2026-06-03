@extends('layouts.teacher')
@section('title', 'جداولي')
@section('subtitle', 'الجدول الدراسي الأسبوعي')

@push('styles')
<style>
    .type-switcher {
        display: flex;
        background-color: var(--bg-secondary);
        border-radius: 1rem;
        padding: 0.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
    }

    .type-btn {
        flex: 1;
        padding: 0.75rem;
        text-align: center;
        border-radius: 0.75rem;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        border: none;
        background: transparent;
        color: var(--text-secondary);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
        font-family: inherit;
    }

    .type-btn.active {
        background-color: var(--accent-color);
        color: #1a1a1a;
    }

    .schedule-table-wrapper {
        overflow-x: auto;
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        box-shadow: var(--shadow);
        padding: 1rem;
    }

    .schedule-table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
        min-width: 750px;
    }

    .schedule-table thead tr {
        border-bottom: 2px solid var(--border-color);
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .schedule-table th {
        padding: 1rem 0.75rem;
    }

    .schedule-table th:first-child {
        text-align: right;
        width: 10%;
    }

    .schedule-table tbody tr {
        border-bottom: 1px solid var(--border-color);
    }

    .schedule-table td {
        padding: 0.75rem;
        vertical-align: top;
    }

    .schedule-table td:first-child {
        font-weight: 800;
        text-align: right;
        white-space: nowrap;
        color: var(--text-primary);
    }

    .cell-card {
        background-color: #eff6ff;
        padding: 0.75rem;
        border-radius: 0.75rem;
        border-right: 3px solid #3b82f6;
        text-align: right;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .cell-course {
        font-weight: 800;
        color: #1e40af;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .cell-meta {
        font-size: 0.78rem;
        color: #475569;
    }

    .cell-empty {
        color: #cbd5e1;
        font-size: 1.2rem;
    }

    /* Exam table */
    .exam-table-wrapper {
        overflow-x: auto;
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        box-shadow: var(--shadow);
        padding: 1rem;
    }

    .exam-table {
        width: 100%;
        border-collapse: collapse;
        text-align: right;
    }

    .exam-table thead tr {
        border-bottom: 2px solid var(--border-color);
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .exam-table th,
    .exam-table td {
        padding: 1rem;
    }

    .exam-table tbody tr {
        border-bottom: 1px solid var(--border-color);
        font-size: 0.95rem;
        transition: background 0.15s;
    }

    .exam-table tbody tr:hover {
        background-color: var(--bg-primary);
    }

    .room-badge {
        background-color: var(--bg-primary);
        color: var(--text-secondary);
        padding: 0.25rem 0.6rem;
        border-radius: 0.5rem;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        color: var(--text-secondary);
    }
</style>
@endpush

@section('content')
@php
    $dayNames    = ['Sunday'=>'الأحد','Monday'=>'الاثنين','Tuesday'=>'الثلاثاء','Wednesday'=>'الأربعاء','Thursday'=>'الخميس'];
    $periodLabels= ['الأولى','الثانية','الثالثة','الرابعة','الخامسة'];
    $periodTimes = ['08:00','09:30','11:00','12:30','14:00'];
    $periodEnds  = ['09:30','11:00','12:30','14:00','15:30'];
    $dayOrder    = ['Sunday','Monday','Tuesday','Wednesday','Thursday'];

    // Build lookup: [day][periodIndex] => schedule
    $lookup = [];
    foreach($schedules as $s) {
        $startShort = \Carbon\Carbon::parse($s->start_time)->format('H:i');
        foreach($periodTimes as $idx => $pt) {
            if($startShort === $pt) {
                $lookup[$s->day][$idx] = $s;
                break;
            }
        }
    }
@endphp

    {{-- Tab Switcher --}}
    <div class="type-switcher">
        <button class="type-btn active" id="btn-weekly" onclick="switchTab('weekly')">
            <i class="fa-solid fa-calendar-days"></i> الجدول الأسبوعي
        </button>
        <button class="type-btn" id="btn-exams" onclick="switchTab('exams')">
            <i class="fa-solid fa-file-pen"></i> الجدول الامتحاني
        </button>
    </div>

    {{-- ========= Weekly Schedule Tab ========= --}}
    <div id="tab-weekly">
        @if($schedules->isEmpty())
            <div class="empty-state">
                <i class="fa-regular fa-calendar-xmark" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color);"></i>
                <p style="font-size: 1.1rem; font-weight: 600;">لا يوجد جدول محدد لكم حتى الآن</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">يقوم رئيس القسم بإعداد جداول الحصص</p>
            </div>
        @else
            <div class="schedule-table-wrapper">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>اليوم / الحصة</th>
                            @foreach($periodLabels as $i => $label)
                            <th>
                                {{ $label }}<br>
                                <small>{{ $periodTimes[$i] }} - {{ $periodEnds[$i] }}</small>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dayOrder as $day)
                        <tr>
                            <td>{{ $dayNames[$day] }}</td>
                            @foreach($periodTimes as $idx => $pt)
                            <td>
                                @if(isset($lookup[$day][$idx]))
                                    @php $s = $lookup[$day][$idx]; @endphp
                                    <div class="cell-card">
                                        <div class="cell-course">{{ $s->course_title }}</div>
                                        @if($s->class_group)
                                        <div class="cell-meta">
                                            <i class="fa-solid fa-users"></i> {{ $s->class_group }}
                                        </div>
                                        @endif
                                        <div class="cell-meta">
                                            <i class="fa-solid fa-door-open"></i> قاعة {{ $s->room ?? 'غير محدد' }}
                                        </div>
                                    </div>
                                @else
                                    <span class="cell-empty">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ========= Exams Tab ========= --}}
    <div id="tab-exams" style="display: none;">
        @if(isset($exams) && $exams->count() > 0)
        <div class="exam-table-wrapper">
            <table class="exam-table">
                <thead>
                    <tr>
                        <th>المادة</th>
                        <th>الدورة</th>
                        <th>التاريخ والتوقيت</th>
                        <th>القاعة</th>
                        <th style="text-align: center;">العلامة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exams as $e)
                    <tr>
                        <td style="font-weight: bold;">{{ $e->course_title }}</td>
                        <td style="color: var(--text-secondary);">{{ $e->class_group ?? '-' }}</td>
                        <td dir="ltr" style="color: var(--text-secondary);">{{ \Carbon\Carbon::parse($e->exam_date)->format('Y-m-d h:i A') }}</td>
                        <td><span class="room-badge">{{ $e->room ?? '-' }}</span></td>
                        <td style="text-align: center; font-weight: bold; color: var(--accent-color);">{{ $e->max_score }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state">
            <i class="fa-regular fa-calendar-xmark" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color);"></i>
            <p style="font-size: 1.1rem; font-weight: 600;">لا توجد امتحانات مجدولة حتى الآن</p>
        </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
function switchTab(tab) {
    const btnWeekly = document.getElementById('btn-weekly');
    const btnExams  = document.getElementById('btn-exams');
    const tabWeekly = document.getElementById('tab-weekly');
    const tabExams  = document.getElementById('tab-exams');

    if (tab === 'weekly') {
        tabWeekly.style.display = 'block';
        tabExams.style.display = 'none';
        btnWeekly.classList.add('active');
        btnExams.classList.remove('active');
    } else {
        tabWeekly.style.display = 'none';
        tabExams.style.display = 'block';
        btnWeekly.classList.remove('active');
        btnExams.classList.add('active');
    }
}
</script>
@endpush
