@extends('layouts.student')
@section('title', $m->lesson?->title ?? 'مذاكرة')
@section('subtitle', 'مُولَّد بمساعدة الذكاء الاصطناعي وراجعه معلمك واعتمده. للتدريب فقط، ولا يدخل في علاماتك.')

@push('styles')
<style>
    .sc-box { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1.1rem; margin-bottom:1.1rem; box-shadow: var(--shadow); }
    .sc-box h2 { margin:0 0 .7rem; font-size:1.02rem; color: var(--text-primary); }
    .sc-term { padding:.35rem 0; border-bottom:1px solid var(--border-color); color: var(--text-primary); font-size:.9rem; }
    .sc-flip { cursor:pointer; border:1px solid var(--border-color); border-radius:.9rem; padding:.8rem 1rem; margin-bottom:.6rem; color: var(--text-primary); }
    .sc-flip .back { display:none; margin-top:.5rem; padding-top:.5rem; border-top:1px dashed var(--border-color); color: var(--text-secondary); }
    .sc-flip.open .back { display:block; }
    .sc-q { border:1px solid var(--border-color); border-radius:.9rem; padding:.8rem 1rem; margin-bottom:.8rem; }
    .sc-q label { display:block; padding:.25rem 0; cursor:pointer; color: var(--text-primary); }
    .sc-q.ok { border-color:#2e7d32; } .sc-q.bad { border-color:#c62828; }
    .sc-expl { font-size:.82rem; color: var(--text-secondary); margin-top:.4rem; }
    .sc-btn { padding:.55rem 1.3rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; }
    .sc-score { font-size:1.2rem; font-weight:800; color: var(--primary-color); margin-bottom:.8rem; }
    .sc-ai { display:inline-block; padding:.15rem .7rem; border-radius:2rem; background: rgba(21,101,192,.14); color:#1565c0; font-size:.75rem; font-weight:800; }
    .sc-hint { font-size:.78rem; color: var(--text-secondary); }
</style>
@endpush

@section('content')
<p><span class="sc-ai">مُولَّد بالذكاء الاصطناعي · راجعه المعلم</span></p>

<div class="sc-box"><h2>الملخص</h2><div style="white-space:pre-line;color:var(--text-primary);line-height:1.9">{{ $m->summary }}</div></div>

@if ($m->key_terms)
<div class="sc-box"><h2>مصطلحات رئيسية</h2>
    @foreach ($m->key_terms as $t)<div class="sc-term"><b>{{ $t['term'] }}</b>: {{ $t['definition'] }}</div>@endforeach
</div>
@endif

@if ($m->flashcards->count())
<div class="sc-box"><h2>بطاقات المراجعة <span class="sc-hint">(اضغط البطاقة لتظهر الإجابة)</span></h2>
    @foreach ($m->flashcards as $c)
        <div class="sc-flip" onclick="this.classList.toggle('open')"><b>{{ $c->front }}</b><div class="back">{{ $c->back }}</div></div>
    @endforeach
</div>
@endif

@if ($m->questions->count())
<div class="sc-box" id="practice"><h2>تدرّب</h2>
    @if ($last && !$result)<p class="sc-hint">آخر محاولة لك: {{ $last->score }} من {{ $last->total }}</p>@endif
    @if ($result)<div class="sc-score">نتيجتك: {{ $result['score'] }} من {{ $result['total'] }}</div>@endif
    <form method="POST" action="{{ route('student.study.attempt', $m->id) }}">
        @csrf
        @foreach ($m->questions as $i => $q)
            @php $rv = $result ? collect($result['review'])->firstWhere('question_id', $q->id) : null; @endphp
            <div class="sc-q {{ $rv ? ($rv['is_correct'] ? 'ok' : 'bad') : '' }}">
                <b>{{ $i + 1 }}. {{ $q->question }}</b>
                @foreach ($q->options as $oi => $opt)
                    <label>
                        <input type="radio" name="answers[{{ $q->id }}]" value="{{ $oi }}" @checked($rv && $rv['chosen'] === $oi) @disabled($rv)>
                        {{ $opt }} @if ($rv && $rv['correct_index'] === $oi)<b style="color:#2e7d32"> ✓ الصحيحة</b>@endif
                    </label>
                @endforeach
                @if ($rv && $rv['explanation'])<div class="sc-expl">{{ $rv['explanation'] }}</div>@endif
            </div>
        @endforeach
        @if ($result)
            <a class="sc-btn" style="text-decoration:none;display:inline-block" href="{{ route('student.study.show', $m->id) }}#practice">حاول من جديد</a>
        @else
            <button class="sc-btn">تصحيح</button>
        @endif
    </form>
</div>
@endif
@endsection
