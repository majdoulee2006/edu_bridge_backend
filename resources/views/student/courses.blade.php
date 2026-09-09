@extends('layouts.student')
@section('title', 'موادي الدراسية')
@section('subtitle', 'جميع المواد المسجّل فيها')

@push('styles')
<style>
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.25rem;
    }
    .course-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 1.25rem;
        border: 1px solid var(--border-color);
        transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    }
    .course-card:hover {
        transform: translateY(-3px);
        border-color: var(--accent-color);
        box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    }
    .course-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    .course-icon {
        width: 52px; height: 52px;
        border-radius: 1rem;
        background: var(--accent-color);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: #1a1a1a; flex-shrink: 0;
    }
    .course-meta { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem; }
    .meta-chip {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 2rem;
        padding: 0.2rem 0.65rem;
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .course-btn {
        background: var(--accent-color);
        color: #1a1a1a;
        padding: 0.65rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.88rem;
        font-weight: 800;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: opacity 0.2s;
        margin-top: auto;
    }
    .course-btn:hover {
        opacity: 0.9;
    }
</style>
@endpush

@section('content')

@if(count($courses) > 0)
<div class="courses-grid">
    @foreach($courses as $c)
    <div class="course-card">
        <div class="course-header">
            <div class="course-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div style="flex: 1;">
                <div style="font-weight: 800; font-size: 1.05rem; color: var(--text-primary); line-height: 1.4;">{{ $c->title }}</div>
                @if($c->teacher_name ?? false)
                    <div style="color: var(--text-secondary); font-size: 0.82rem; margin-top: 0.25rem;">
                        <i class="fa-solid fa-user-tie" style="color: var(--accent-color);"></i> {{ $c->teacher_name }}
                    </div>
                @endif
            </div>
        </div>

        <div class="course-meta">
            @php
                $yearNames = [
                    1 => 'سنة أولى',
                    2 => 'سنة ثانية',
                    3 => 'سنة ثالثة',
                    4 => 'سنة رابعة',
                    5 => 'سنة خامسة',
                ];
                $yearName = $yearNames[$c->year ?? 1] ?? 'سنة أولى';
            @endphp
            <span class="meta-chip"><i class="fa-solid fa-layer-group"></i> {{ $yearName }}</span>
            @if($c->credits ?? false)
                <span class="meta-chip"><i class="fa-solid fa-clock"></i> {{ $c->credits }} ساعات</span>
            @endif
            @if(($c->lessons_count ?? 0) > 0)
                <span class="meta-chip"><i class="fa-solid fa-file-lines"></i> {{ $c->lessons_count }} محاضرة</span>
            @endif
        </div>

        <a href="{{ route('student.course.materials', $c->course_id) }}" class="course-btn">
            <i class="fa-solid fa-folder-open"></i> استعراض المحاضرات والملفات
        </a>
    </div>
    @endforeach
</div>
@else
<div style="text-align: center; padding: 3.5rem 1.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary); border: 1px solid var(--border-color);">
    <i class="fa-solid fa-book-open" style="font-size: 3rem; opacity: 0.4; display: block; margin-bottom: 1rem; color: var(--accent-color);"></i>
    <h3 style="font-weight: 800; font-size: 1.1rem; color: var(--text-primary); margin-bottom: 0.35rem;">لا توجد مواد مسجّلة حالياً</h3>
    <p style="font-size: 0.88rem;">لم يتم تسجيل أي مقررات دراسية لحسابك في الفصل الحالي.</p>
</div>
@endif

@endsection
