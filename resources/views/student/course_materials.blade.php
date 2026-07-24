@extends('layouts.student')
@section('title', $course->title)
@section('subtitle', 'المواد التعليمية والمحاضرات')

@push('styles')
<style>
    .material-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--shadow);
        border-right: 3px solid var(--accent-color);
    }
    .material-icon {
        width: 44px; height: 44px; border-radius: 0.75rem;
        background: var(--accent-color); color: #1a1a1a;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
</style>
@endpush

@section('content')

<div style="margin-bottom: 1.5rem;">
    <a href="{{ route('student.courses') }}" style="color: var(--text-secondary); font-size: 0.9rem; text-decoration: none; font-weight: 600;">
        <i class="fa-solid fa-arrow-right"></i> العودة للمواد
    </a>
</div>

@forelse($materials as $m)
<div class="material-card">
    <div class="material-icon">
        @if(($m->file_type ?? '') === 'video')
            <i class="fa-solid fa-video"></i>
        @elseif(($m->file_type ?? '') === 'image')
            <i class="fa-solid fa-image"></i>
        @else
            <i class="fa-solid fa-file-lines"></i>
        @endif
    </div>
    <div style="flex: 1;">
        <div style="font-weight: 700; font-size: 0.95rem;">{{ $m->title }}</div>
        @if($m->description ?? false)
            <div style="color: var(--text-secondary); font-size: 0.82rem; margin-top: 0.2rem;">{{ Str::limit($m->description, 100) }}</div>
        @endif
        <div style="color: var(--text-secondary); font-size: 0.78rem; margin-top: 0.3rem;">
            <i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($m->created_at)->diffForHumans() }}
        </div>
    </div>
    @if($m->file_path ?? false)
        <a href="/storage/{{ $m->file_path }}" target="_blank" download="{{ $m->file_name ?? 'lecture' }}"
           style="background: var(--accent-color); color: #1a1a1a; padding: 0.4rem 0.9rem; border-radius: 0.65rem; font-size: 0.82rem; font-weight: 700; text-decoration: none; white-space: nowrap;">
            <i class="fa-solid fa-download"></i> تحميل
        </a>
    @elseif($m->content_url ?? false)
        <a href="{{ $m->content_url }}" target="_blank"
           style="background: var(--accent-color); color: #1a1a1a; padding: 0.4rem 0.9rem; border-radius: 0.65rem; font-size: 0.82rem; font-weight: 700; text-decoration: none; white-space: nowrap;">
            <i class="fa-solid fa-up-right-from-square"></i> عرض الرابط
        </a>
    @endif
</div>
@empty
<div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; opacity: 0.4; display: block; margin-bottom: 0.75rem;"></i>
    لا توجد محاضرات لهذه المادة بعد
</div>
@endforelse

@endsection
