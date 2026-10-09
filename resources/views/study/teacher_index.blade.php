@extends('layouts.teacher')
@section('title', 'رفيق المذاكرة')
@section('subtitle', 'حوّل محاضرتك إلى ملخص وبطاقات وأسئلة تدريب. يكتب الذكاء الاصطناعي مسودة فقط، ولا يراها الطلاب إلا بعد مراجعتك واعتمادك.')

@push('styles')
<style>
    .sc-card { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem 1.2rem; margin-bottom:.9rem; box-shadow: var(--shadow); display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
    .sc-title { font-weight:800; color: var(--text-primary); }
    .sc-meta { font-size:.78rem; color: var(--text-secondary); margin-top:.15rem; }
    .sc-badge { padding:.2rem .75rem; border-radius:2rem; font-size:.75rem; font-weight:800; }
    .sc-btn { display:inline-block; padding:.5rem 1.1rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; font-size:.83rem; text-decoration:none; }
    .sc-btn.sec { background:transparent; color: var(--primary-color); border:1px solid var(--primary-color); }
    .sc-note { padding:.8rem 1rem; border-radius:.9rem; margin-bottom:1rem; background: rgba(230,81,0,.12); color:#bf4a00; font-weight:700; }
    .sc-hint { font-size:.78rem; color: var(--text-secondary); }
</style>
@endpush

@section('content')
@php $labels = ['pending' => ['قيد التوليد…', '#1565c0'], 'draft' => ['مسودة بانتظار مراجعتك', '#e65100'], 'approved' => ['منشورة للطلاب', '#2e7d32'], 'failed' => ['فشل التوليد', '#c62828']]; @endphp

@unless ($aiReady)
    <div class="sc-note">خدمة الذكاء الاصطناعي غير مفعّلة على الخادم، لذا لا يمكن توليد مسودات الآن.</div>
@endunless

@forelse ($rows as $r)
    @php $l = $r['lesson']; $m = $r['material']; @endphp
    <div class="sc-card">
        <div>
            <div class="sc-title">{{ $l->title }}</div>
            <div class="sc-meta">{{ $l->course?->title }} @if ($r['whyNot']) · <span class="sc-hint">{{ $r['whyNot'] }}</span>@endif</div>
            @if ($m?->status === 'failed')<div class="sc-meta" style="color:#c62828">{{ $m->failure_reason }}</div>@endif
        </div>
        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
            @if ($m)
                @php [$lbl, $col] = $labels[$m->status]; @endphp
                <span class="sc-badge" style="color:{{ $col }};background:color-mix(in srgb, {{ $col }} 16%, transparent)">{{ $lbl }}</span>
                <a class="sc-btn sec" href="{{ route('teacher.study.show', $m->id) }}">فتح</a>
            @endif
            @if (!$r['whyNot'] && $aiReady && (!$m || in_array($m->status, ['failed', 'draft'])))
                <form method="POST" action="{{ route('teacher.study.generate', $l->lesson_id) }}">@csrf
                    <button class="sc-btn" @disabled($m?->status === 'pending')>{{ $m ? 'إعادة التوليد' : 'توليد مسودة' }}</button></form>
            @endif
        </div>
    </div>
@empty
    <div class="sc-card">لا محاضرات في مقرراتك بعد. ارفع محاضرة (PDF أو Word أو PowerPoint) من صفحة المحاضرات.</div>
@endforelse
<p class="sc-hint">النص المستخرج من الملف يُرسل إلى خدمة Gemini لتوليد المسودة. الملفات الممسوحة ضوئيًا (صورًا) لا تُقرأ.</p>
@endsection
