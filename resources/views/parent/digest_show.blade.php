@extends('layouts.parent')
@section('title', 'الملخص الأسبوعي')
@section('subtitle', ($digest->student->user->full_name ?? '') . ' — ' . $digest->week_start->format('Y-m-d') . ' إلى ' . $digest->week_end->format('Y-m-d'))

@php
    $f = $digest->facts ?? [];
    $a = $f['attendance'] ?? [];
    $w = $f['assignments'] ?? [];
    $g = $f['grades'] ?? [];
    $tones = [
        'good'      => ['label' => 'أسبوع جيد',     'color' => '#2e7d32'],
        'attention' => ['label' => 'بعض الملاحظات', 'color' => '#f9a825'],
        'concern'   => ['label' => 'يحتاج متابعة',   'color' => '#c62828'],
    ];
    $tone = $tones[$digest->tone] ?? $tones['good'];
@endphp

@push('styles')
<style>
    .dg-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem; }
    .dg-badge { padding:.35rem .9rem; border-radius:2rem; font-weight:800; font-size:.85rem; color: var(--tone);
        background: color-mix(in srgb, var(--tone) 14%, transparent); border:1px solid color-mix(in srgb, var(--tone) 35%, transparent); }
    .dg-actions { display:flex; gap:.6rem; }
    .dg-btn { padding:.55rem 1.1rem; border-radius:.85rem; font-weight:700; font-size:.9rem; text-decoration:none;
        border:1px solid var(--border-color); color: var(--text-primary); }
    .dg-btn.primary { background: var(--tone); color:#fff; border-color: var(--tone); }
    .dg-stats { display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap:1rem; margin-bottom:1.25rem; }
    .dg-stat { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem; text-align:center; box-shadow: var(--shadow); }
    .dg-stat small { display:block; color: var(--text-secondary); font-size:.8rem; }
    .dg-stat b { display:block; font-size:1.6rem; font-weight:800; color: var(--text-primary); margin:.25rem 0; }
    .dg-body { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.25rem; padding:1.4rem;
        line-height:2; white-space:pre-wrap; color: var(--text-primary); box-shadow: var(--shadow); }
    .dg-note { margin-top:1rem; font-size:.78rem; color: var(--text-secondary); }
</style>
@endpush

@section('content')
<div style="--tone: {{ $tone['color'] }};">
    <div class="dg-head">
        <div style="display:flex; align-items:center; gap:.75rem;">
            <span class="dg-badge">{{ $tone['label'] }}</span>
            <strong style="font-size:1.1rem;">{{ $digest->title }}</strong>
        </div>
        <div class="dg-actions">
            <a href="{{ route('parent.digests') }}" class="dg-btn"><i class="fa-solid fa-arrow-right"></i> رجوع</a>
            <a href="{{ route('parent.digests.pdf', $digest->id) }}" class="dg-btn primary"><i class="fa-solid fa-file-pdf"></i> تنزيل PDF</a>
        </div>
    </div>

    <div class="dg-stats">
        <div class="dg-stat">
            <small>الحضور</small>
            <b>{{ ($a['total'] ?? 0) > 0 ? (($a['present'] ?? 0) + ($a['late'] ?? 0)) . ' / ' . $a['total'] : '—' }}</b>
            <small>{{ isset($a['rate']) ? 'نسبة ' . $a['rate'] . '%' : 'لا محاضرات' }}</small>
        </div>
        <div class="dg-stat">
            <small>الواجبات المسلَّمة</small>
            <b>{{ ($w['due_this_week'] ?? 0) > 0 ? ($w['submitted'] ?? 0) . ' / ' . $w['due_this_week'] : '—' }}</b>
            <small>{{ ($w['missing'] ?? 0) > 0 ? 'فائتة: ' . $w['missing'] : 'مستحقة هذا الأسبوع' }}</small>
        </div>
        <div class="dg-stat">
            <small>متوسط العلامات</small>
            <b>{{ isset($g['avg_percent']) ? $g['avg_percent'] . '%' : '—' }}</b>
            <small>{{ ($g['count'] ?? 0) > 0 ? $g['count'] . ' علامة جديدة' : 'لا علامات جديدة' }}</small>
        </div>
    </div>

    <div class="dg-body">{{ $digest->body }}</div>

    @if($digest->source === 'ai')
        <p class="dg-note"><i class="fa-solid fa-wand-magic-sparkles"></i> صيغ النص بمساعدة الذكاء الاصطناعي من أرقام حقيقية محسوبة في النظام.</p>
    @endif
</div>
@endsection
