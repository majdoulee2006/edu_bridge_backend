@extends('layouts.student')
@section('title', 'درجاتي')
@section('subtitle', 'نتائج المواد الدراسية')

@push('styles')
<style>
    .grade-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--shadow);
    }
    .grade-circle {
        width: 60px; height: 60px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; font-weight: 800; flex-shrink: 0;
    }
    .grade-a { background: hsl(120,70%,90%); color: hsl(120,50%,30%); }
    .grade-b { background: hsl(200,70%,90%); color: hsl(200,50%,30%); }
    .grade-c { background: hsl(30,70%,90%);  color: hsl(30,50%,30%);  }
    .grade-f { background: hsl(0,70%,90%);   color: hsl(0,50%,30%);   }
</style>
@endpush

@section('content')

{{-- Average Card --}}
<div style="background: linear-gradient(135deg, #1a2633, #243447); border-radius: 1.5rem; padding: 2rem; margin-bottom: 2rem; color: white; display: flex; align-items: center; gap: 1.5rem;">
    <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--accent-color); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 900; color: #1a1a1a; flex-shrink: 0;">
        {{ $avgGrade }}
    </div>
    <div>
        <div style="font-size: 1.2rem; font-weight: 800; color: var(--accent-color);">متوسط الدرجات الكلي</div>
        <div style="font-size: 0.9rem; opacity: 0.7; margin-top: 0.3rem;">{{ $grades->count() }} مادة دراسية</div>
    </div>
</div>

@forelse($grades as $g)
@php
    $score = $g->score ?? 0;
    $max   = $g->max_score ?? 100;
    $pct   = $max > 0 ? round(($score / $max) * 100) : 0;
    if ($pct >= 85)      $gClass = 'grade-a';
    elseif ($pct >= 70)  $gClass = 'grade-b';
    elseif ($pct >= 50)  $gClass = 'grade-c';
    else                 $gClass = 'grade-f';
@endphp
<div class="grade-card">
    <div class="grade-circle {{ $gClass }}">{{ $score }}</div>
    <div style="flex: 1;">
        <div style="font-weight: 800; font-size: 0.95rem;">{{ $g->course_title }}</div>
        <div style="color: var(--text-secondary); font-size: 0.82rem; margin-top: 0.25rem;">
            {{ $g->exam_title ?? 'امتحان' }} &nbsp;·&nbsp; {{ $score }} / {{ $max }}
        </div>
        <div style="background: var(--bg-primary); border-radius: 2rem; height: 6px; overflow: hidden; margin-top: 0.5rem;">
            <div style="height: 100%; border-radius: 2rem; background: var(--accent-color); width: {{ $pct }}%;"></div>
        </div>
    </div>
    <span style="font-size: 1.1rem; font-weight: 800; color: var(--text-secondary);">{{ $pct }}%</span>
</div>
@empty
<div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-chart-bar" style="font-size: 2.5rem; opacity: 0.4; display: block; margin-bottom: 0.75rem;"></i>
    لا توجد درجات مسجّلة بعد
</div>
@endforelse

@endsection
