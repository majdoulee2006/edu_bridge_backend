@extends('layouts.student')
@section('title', 'مواداتي الدراسية')
@section('subtitle', 'جميع المواد المسجّل فيها')

@push('styles')
<style>
    .course-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        border: 2px solid transparent;
        transition: transform 0.2s, border-color 0.2s;
    }
    .course-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent-color);
    }
    .course-icon {
        width: 56px; height: 56px;
        border-radius: 1rem;
        background: var(--accent-color);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: #1a1a1a; flex-shrink: 0;
    }
    .course-meta { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.4rem; }
    .meta-chip {
        background: var(--bg-primary);
        border-radius: 2rem;
        padding: 0.15rem 0.6rem;
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 600;
    }
</style>
@endpush

@section('content')

@forelse($courses as $c)
<div class="course-card">
    <div class="course-icon"><i class="fa-solid fa-chalkboard"></i></div>
    <div style="flex: 1;">
        <div style="font-weight: 800; font-size: 1rem;">{{ $c->title }}</div>
        <div class="course-meta">
            @if($c->level ?? false)
                <span class="meta-chip"><i class="fa-solid fa-layer-group"></i> {{ $c->level }}</span>
            @endif
            @if($c->credits ?? false)
                <span class="meta-chip">{{ $c->credits }} ساعة</span>
            @endif
            @if($c->teacher_name ?? false)
                <span class="meta-chip"><i class="fa-solid fa-user"></i> {{ $c->teacher_name }}</span>
            @endif
            @if(($c->lessons_count ?? 0) > 0)
                <span class="meta-chip"><i class="fa-solid fa-video"></i> {{ $c->lessons_count }} محاضرة</span>
            @endif
        </div>
    </div>
    <a href="{{ route('student.course.materials', $c->course_id) }}"
       style="background: var(--accent-color); color: #1a1a1a; padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.85rem; font-weight: 700; text-decoration: none; white-space: nowrap;">
        <i class="fa-solid fa-folder-open"></i> المحاضرات
    </a>
</div>
@empty
<div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-book-open" style="font-size: 2.5rem; opacity: 0.4; display: block; margin-bottom: 0.75rem;"></i>
    لا توجد مواد مسجّلة حالياً
</div>
@endforelse

@endsection
