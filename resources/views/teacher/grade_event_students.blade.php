@extends('layouts.teacher')
@section('title', 'إدخال العلامات - ' . $event->title)

@push('styles')
<style>
    .header-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
        border-right: 4px solid var(--accent-color);
    }
    
    .event-info span {
        display: inline-block;
        margin-left: 1.5rem;
        color: var(--text-secondary);
        font-size: 0.95rem;
    }

    .students-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    .students-table th, .students-table td {
        padding: 1rem 1.25rem;
        text-align: right;
    }

    .students-table th {
        background: rgba(0, 0, 0, 0.02);
        color: var(--text-secondary);
        font-weight: 700;
        font-size: 0.95rem;
        border-bottom: 1px solid var(--border-color);
    }

    .students-table td {
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    .students-table tr:last-child td {
        border-bottom: none;
    }

    .form-input {
        width: 100px;
        padding: 0.6rem;
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit;
        text-align: center;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--accent-color);
    }

    .btn-primary {
        background: var(--accent-color);
        color: #1a1a1a;
        border: none;
        border-radius: 0.75rem;
        padding: 0.75rem 2rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-secondary {
        background: var(--bg-primary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
</style>
@endpush

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <a href="{{ route('teacher.grade_events') }}" class="btn-secondary">
        <i class="fa-solid fa-arrow-right"></i> رجوع للتقييمات
    </a>
</div>

<div class="header-card">
    <h3 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 1.25rem;">{{ $event->title }}</h3>
    <div class="event-info">
        <span><i class="fa-solid fa-book"></i> المادة: {{ $event->course_title ?? '—' }}</span>
        <span><i class="fa-solid fa-star"></i> العلامة الكلية: {{ $event->max_score }}</span>
        <span><i class="fa-solid fa-calendar"></i> التاريخ: {{ \Carbon\Carbon::parse($event->date)->format('Y-m-d') }}</span>
    </div>
</div>

<form action="{{ route('teacher.grade_events.save_entries', $event->id) }}" method="POST">
    @csrf
    <table class="students-table" style="margin-bottom: 2rem;">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم الطالب</th>
                <th>الرقم الجامعي</th>
                <th style="text-align: center;">العلامة (من {{ $event->max_score }})</th>
                <th>ملاحظات للمدرب</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: 600;">{{ $student->full_name }}</td>
                    <td style="color: var(--text-secondary);">{{ $student->student_code }}</td>
                    <td style="text-align: center;">
                        <input type="number" name="scores[{{ $student->student_id }}]" value="{{ $student->score }}" class="form-input" min="0" max="{{ $event->max_score }}" step="0.5" placeholder="—">
                    </td>
                    <td>
                        <input type="text" name="notes[{{ $student->student_id }}]" value="{{ $student->notes }}" class="form-input" style="width: 100%; text-align: right;" placeholder="ملاحظات اختيارية...">
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                        لا يوجد طلاب مسجلين في هذه المادة بعد.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($students->isNotEmpty())
        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> حفظ العلامات
            </button>
        </div>
    @endif
</form>

@endsection
