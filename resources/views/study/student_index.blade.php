@extends('layouts.student')
@section('title', 'رفيق المذاكرة')
@section('subtitle', 'ملخصات وبطاقات وأسئلة تدريب أعدّها معلموك لمحاضراتك. التدريب لا يُحتسب بالعلامات.')

@push('styles')
<style>
    .sc-card { display:block; text-decoration:none; background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem 1.2rem; margin-bottom:.9rem; box-shadow: var(--shadow); color:inherit; }
    .sc-card:hover { transform: translateY(-2px); }
    .sc-title { font-weight:800; color: var(--text-primary); }
    .sc-meta { font-size:.8rem; color: var(--text-secondary); margin-top:.2rem; }
</style>
@endpush

@section('content')
@forelse ($materials as $m)
    <a class="sc-card" href="{{ route('student.study.show', $m->id) }}">
        <div class="sc-title">{{ $m->lesson?->title }}</div>
        <div class="sc-meta">{{ $m->flashcards_count }} بطاقة · {{ $m->questions_count }} سؤال تدريب · اعتمده المعلم {{ $m->approved_at?->diffForHumans() }}</div>
    </a>
@empty
    <div class="sc-card">لم ينشر معلموك مواد مذاكرة بعد.</div>
@endforelse
@endsection
