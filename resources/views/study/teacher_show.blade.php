@extends('layouts.teacher')
@section('title', $m->lesson?->title ?? 'مادة مذاكرة')
@section('subtitle', 'راجع ما ولّده الذكاء الاصطناعي وعدّله. لن يظهر للطلاب قبل أن تعتمده.')

@php $editable = in_array($m->status, ['draft', 'failed']); @endphp

@push('styles')
<style>
    .sc-box { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1.1rem; margin-bottom:1.1rem; box-shadow: var(--shadow); }
    .sc-box h2 { margin:0 0 .7rem; font-size:1.02rem; color: var(--text-primary); }
    .sc-box textarea, .sc-box input[type=text] { width:100%; padding:.5rem .7rem; border-radius:.7rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; margin-bottom:.4rem; }
    .sc-row { border:1px dashed var(--border-color); border-radius:.9rem; padding:.7rem; margin-bottom:.7rem; }
    .sc-opt { display:flex; align-items:center; gap:.5rem; }
    .sc-opt input[type=text] { flex:1; }
    .sc-btn { display:inline-block; padding:.5rem 1.1rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; font-size:.83rem; text-decoration:none; }
    .sc-btn.sec { background:transparent; color: var(--primary-color); border:1px solid var(--primary-color); }
    .sc-btn.danger { background:#c62828; }
    .sc-bar { display:flex; gap:.6rem; flex-wrap:wrap; align-items:center; }
    .sc-ai { display:inline-block; padding:.15rem .7rem; border-radius:2rem; background: rgba(21,101,192,.14); color:#1565c0; font-size:.75rem; font-weight:800; }
    .sc-term { padding:.3rem 0; border-bottom:1px solid var(--border-color); color: var(--text-primary); font-size:.88rem; }
    .sc-hint { font-size:.78rem; color: var(--text-secondary); }
</style>
@endpush

@if ($m->status === 'pending')
    @push('scripts')<script>setTimeout(() => location.reload(), 5000);</script>@endpush
@endif

@section('content')
<div class="sc-bar" style="margin-bottom:1rem">
    <a class="sc-btn sec" href="{{ route('teacher.study') }}">← كل المحاضرات</a>
    <span class="sc-ai">مُولَّد بالذكاء الاصطناعي</span>
    <span class="sc-hint">{{ ['pending' => 'قيد التوليد…', 'draft' => 'مسودة', 'approved' => 'منشورة للطلاب', 'failed' => 'فشل التوليد'][$m->status] }}</span>
</div>

@if ($m->status === 'pending')
    <div class="sc-box">يجري التوليد الآن… تتحدّث هذه الصفحة تلقائيًا. (يتطلب عمل queue worker على الخادم.)</div>
@elseif ($m->status === 'failed' && !$m->summary)
    <div class="sc-box" style="color:#c62828">{{ $m->failure_reason }}</div>
@else
<form method="POST" action="{{ route('teacher.study.save', $m->id) }}">
    @csrf
    <div class="sc-box">
        <h2>الملخص</h2>
        <textarea name="summary" rows="7" required @disabled(!$editable)>{{ old('summary', $m->summary) }}</textarea>
    </div>

    @if ($m->key_terms)
    <div class="sc-box">
        <h2>المصطلحات الرئيسية <span class="sc-hint">(أُسقط ما لم يرد في المحاضرة)</span></h2>
        @foreach ($m->key_terms as $t)<div class="sc-term"><b>{{ $t['term'] }}</b>: {{ $t['definition'] }}</div>@endforeach
    </div>
    @endif

    <div class="sc-box">
        <h2>بطاقات المراجعة <span class="sc-hint">(أفرغ الحقلين لحذف بطاقة)</span></h2>
        @php $cards = $m->flashcards->concat($editable ? collect([new \App\Models\StudyFlashcard, new \App\Models\StudyFlashcard]) : []); @endphp
        @foreach ($cards as $i => $c)
            <div class="sc-row">
                <input type="text" name="flashcards[{{ $i }}][front]" value="{{ $c->front }}" placeholder="الوجه الأمامي (سؤال/مفهوم)" @disabled(!$editable)>
                <textarea name="flashcards[{{ $i }}][back]" rows="2" placeholder="الجواب" @disabled(!$editable)>{{ $c->back }}</textarea>
            </div>
        @endforeach
    </div>

    <div class="sc-box">
        <h2>أسئلة التدريب <span class="sc-hint">(اختر الجواب الصحيح بالدائرة؛ أفرغ نص السؤال لحذفه)</span></h2>
        @php $qs = $m->questions->concat($editable ? collect([new \App\Models\StudyQuestion(['options' => ['', '', '', ''], 'correct_index' => 0])]) : []); @endphp
        @foreach ($qs as $i => $q)
            <div class="sc-row">
                <input type="text" name="questions[{{ $i }}][question]" value="{{ $q->question }}" placeholder="نص السؤال" @disabled(!$editable)>
                @foreach (($q->options ?: ['', '', '', '']) as $oi => $opt)
                    <div class="sc-opt">
                        <input type="radio" name="questions[{{ $i }}][correct_index]" value="{{ $oi }}" @checked(($q->correct_index ?? 0) === $oi) @disabled(!$editable)>
                        <input type="text" name="questions[{{ $i }}][options][{{ $oi }}]" value="{{ $opt }}" placeholder="الخيار {{ $oi + 1 }}" @disabled(!$editable)>
                    </div>
                @endforeach
                <input type="text" name="questions[{{ $i }}][explanation]" value="{{ $q->explanation }}" placeholder="شرح الجواب (اختياري)" @disabled(!$editable)>
            </div>
        @endforeach
    </div>

    @if ($editable)<button class="sc-btn">حفظ التعديلات</button>@endif
</form>

<div class="sc-box" style="margin-top:1.1rem">
    <h2>النشر</h2>
    <div class="sc-bar">
        @if ($m->status === 'draft')
            <form method="POST" action="{{ route('teacher.study.approve', $m->id) }}" onsubmit="return confirm('سيراها كل طلاب المقرر ويصلهم إشعار. متابعة؟')">@csrf
                <button class="sc-btn">اعتماد ونشر للطلاب</button></form>
        @endif
        @if ($m->status === 'approved')
            <form method="POST" action="{{ route('teacher.study.unpublish', $m->id) }}">@csrf<button class="sc-btn danger">إلغاء النشر</button></form>
        @endif
        @if ($m->questions->count())
            <form method="POST" action="{{ route('teacher.study.export', $m->id) }}">@csrf<button class="sc-btn sec">تصدير الأسئلة إلى كويز رسمي (مسودة)</button></form>
        @endif
    </div>
    <p class="sc-hint">احفظ تعديلاتك قبل الاعتماد. التدريب لا يُحتسب بعلامات الطلاب. يرى الطلاب وسم «مُولَّد بالذكاء الاصطناعي وراجعه المعلم».</p>
</div>
@endif
@endsection
