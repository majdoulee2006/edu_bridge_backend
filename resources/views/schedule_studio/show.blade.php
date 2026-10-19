@extends('layouts.hod')
@section('title', $draft->name)
@section('subtitle', 'مسودة مقترحة من النظام. عدّلها كما تشاء، ولن تصير الجدول الفعلي إلا عند الاعتماد.')

@php
    $editable = $draft->status === 'draft';
    $quality = $stats['quality'] ?? [];
    $violations = $stats['violations'] ?? [];
    $unscheduled = $stats['unscheduled'] ?? [];
    $statusLabel = ['draft' => 'مسودة', 'approved' => 'معتمدة', 'discarded' => 'مُهمَلة'][$draft->status];
@endphp

@push('styles')
<style>
    .st-card { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1.1rem; box-shadow: var(--shadow); margin-bottom:1.1rem; }
    .st-card h2 { margin:0 0 .7rem; font-size:1.02rem; color: var(--text-primary); }
    .st-stats { display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap:.8rem; margin-bottom:1.1rem; }
    .st-stat { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1rem; padding:.8rem; text-align:center; }
    .st-stat b { display:block; font-size:1.5rem; font-weight:800; color: var(--c, var(--text-primary)); }
    .st-stat small { color: var(--text-secondary); font-size:.75rem; }
    .st-btn { display:inline-block; padding:.5rem 1.1rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; font-size:.83rem; text-decoration:none; }
    .st-btn.sec { background:transparent; color: var(--primary-color); border:1px solid var(--primary-color); }
    .st-btn.danger { background:#c62828; }
    .st-btn.mini { padding:.15rem .5rem; font-size:.7rem; }
    .st-bar { display:flex; gap:.6rem; flex-wrap:wrap; align-items:center; }
    .st-grid { width:100%; border-collapse:collapse; table-layout:fixed; font-size:.78rem; }
    .st-grid th, .st-grid td { border:1px solid var(--border-color); padding:.3rem; vertical-align:top; color: var(--text-primary); }
    .st-grid th { background: rgba(120,120,120,.1); font-size:.75rem; }
    .st-grid th.p { width:92px; }
    .st-cell { border-radius:.5rem; padding:.3rem .4rem; background: color-mix(in srgb, var(--primary-color) 14%, transparent); border-right:3px solid var(--primary-color); }
    .st-cell.locked { border-right-color:#e65100; background: rgba(230,81,0,.12); }
    .st-cell.manual { border-right-color:#2e7d32; }
    .st-cell b { display:block; font-size:.78rem; }
    .st-cell span { color: var(--text-secondary); font-size:.7rem; display:block; }
    .st-err { background: rgba(198,40,40,.1); color:#c62828; padding:.5rem .8rem; border-radius:.7rem; margin-bottom:.4rem; font-weight:700; font-size:.83rem; }
    .st-warnbox { background: rgba(230,81,0,.1); color:#bf4a00; padding:.5rem .8rem; border-radius:.7rem; margin-bottom:.4rem; font-size:.83rem; }
    .st-form { display:flex; gap:.5rem; flex-wrap:wrap; align-items:flex-end; }
    .st-form label { display:block; font-size:.72rem; color: var(--text-secondary); margin-bottom:.15rem; }
    .st-form select { padding:.4rem .5rem; border-radius:.6rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; max-width:230px; }
    .st-scroll { overflow-x:auto; }
</style>
@endpush

@section('content')
<div class="st-bar" style="margin-bottom:1rem">
    <a class="st-btn sec" href="{{ route('hod.studio') }}">← كل المسودات</a>
    <span class="st-btn sec" style="cursor:default">{{ $statusLabel }}{{ $draft->reverted_at ? ' (تُرُوجع عنها)' : '' }}</span>
    <span class="st-btn sec" style="cursor:default">{{ $draft->semester->name ?? '' }}</span>
</div>
@if ($draft->isSavedForNext())
    <div class="st-card" style="margin-bottom:1rem;border-color:#1565c0">
        <b style="color:#1565c0">محفوظة للفصل القادم</b>
        <p style="margin:.4rem 0 0;color:var(--text-secondary);font-size:.88rem">هذه المسودة لفصل «{{ $draft->semester->name }}» وهو ليس الفصل الفعّال الآن. يمكنك مراجعتها وتعديلها بحرية، لكنها لا تُعتمد ولا تظهر للطلاب والمعلمين إلا عندما يصبح هذا الفصل فعّالاً.</p>
    </div>
@endif

<div class="st-stats">
    <div class="st-stat" style="--c:#2e7d32"><b>{{ $stats['placed'] ?? 0 }} / {{ $stats['required'] ?? 0 }}</b><small>حصص موضوعة</small></div>
    <div class="st-stat" style="--c:{{ $unscheduled ? '#e65100' : '#2e7d32' }}"><b>{{ count($unscheduled) }}</b><small>مواد ناقصة</small></div>
    <div class="st-stat" style="--c:{{ $violations ? '#c62828' : '#2e7d32' }}"><b>{{ count($violations) }}</b><small>تعارضات</small></div>
    <div class="st-stat"><b>{{ $quality['gaps'] ?? 0 }}</b><small>فترات فراغ بين المحاضرات</small></div>
    <div class="st-stat"><b>{{ $quality['avoid'] ?? 0 }}</b><small>حصص بأوقات يتجنبها معلموها</small></div>
</div>

@foreach ($violations as $v)<div class="st-err"><i class="fa-solid fa-ban"></i> {{ $v['message'] }}</div>@endforeach

@if ($unscheduled)
<div class="st-card">
    <h2>ما لم يُجدول ولماذا</h2>
    @foreach ($unscheduled as $u)
        <div class="st-warnbox"><b>{{ $u['group_label'] }} — {{ $u['title'] }}</b>: ينقص {{ $u['missing'] }} من {{ $u['of'] }} حصص. {{ $u['message'] }}</div>
    @endforeach
</div>
@endif

@foreach ($grids as $key => $g)
<div class="st-card">
    <h2>{{ $g['label'] }}</h2>
    <div class="st-scroll">
    <table class="st-grid">
        <tr><th class="p"></th>@foreach ($days as $d)<th>{{ $dayLabels[$d] }}</th>@endforeach</tr>
        @for ($p = 1; $p <= $periodCount; $p++)
            <tr>
                <th class="p">{{ $periods[$p][0] }}–{{ $periods[$p][1] }}</th>
                @foreach ($days as $d)
                    @php $e = $g['cells'][$d][$p] ?? null; @endphp
                    <td>
                        @if ($e)
                            <div class="st-cell {{ $e->locked ? 'locked' : '' }} {{ $e->source === 'manual' ? 'manual' : '' }}">
                                <b>{{ $courseTitles[$e->course_id] ?? '#'.$e->course_id }}</b>
                                <span>{{ $teacherNames[$e->teacher_user_id] ?? '' }}</span>
                                <span>{{ $e->room }}</span>
                                @if ($editable)
                                    <form method="POST" action="{{ route('hod.studio.lock', $draft->id) }}" style="display:inline">@csrf
                                        <input type="hidden" name="entry_id" value="{{ $e->id }}"><input type="hidden" name="locked" value="{{ $e->locked ? 0 : 1 }}">
                                        <button class="st-btn sec mini" title="المثبّتة لا تتغير عند إعادة التوليد">{{ $e->locked ? 'إلغاء التثبيت' : 'تثبيت' }}</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endfor
    </table>
    </div>
</div>
@endforeach

@if ($editable)
<div class="st-card">
    <h2>تعديل يدوي</h2>
    <p style="color:var(--text-secondary);font-size:.82rem;margin-top:0">أي نقل أو تبديل يُفحص فورًا؛ إن كسر قاعدة (تعارض معلم/شعبة/قاعة أو وقت غير متاح) يُرفض ويُشرح السبب.</p>
    <form class="st-form" method="POST" action="{{ route('hod.studio.move', $draft->id) }}" style="margin-bottom:.9rem">
        @csrf
        <div><label>انقل الحصة</label>
            <select name="entry_id" required>@foreach ($entries as $e)<option value="{{ $e->id }}">{{ $e->class_group }} — {{ $courseTitles[$e->course_id] ?? '' }} ({{ $dayLabels[$e->day] }} ف{{ $e->period }})</option>@endforeach</select></div>
        <div><label>إلى يوم</label><select name="day">@foreach ($days as $d)<option value="{{ $d }}">{{ $dayLabels[$d] }}</option>@endforeach</select></div>
        <div><label>فترة</label><select name="period">@for ($p = 1; $p <= $periodCount; $p++)<option value="{{ $p }}">{{ $p }} ({{ $periods[$p][0] }})</option>@endfor</select></div>
        <div><label>قاعة (اختياري)</label><select name="room"><option value="">بدون تغيير</option>@foreach ($rooms as $r)<option>{{ $r }}</option>@endforeach</select></div>
        <button class="st-btn">نقل</button>
    </form>
    <form class="st-form" method="POST" action="{{ route('hod.studio.swap', $draft->id) }}">
        @csrf
        <div><label>بدّل موعد</label>
            <select name="a" required>@foreach ($entries as $e)<option value="{{ $e->id }}">{{ $e->class_group }} — {{ $courseTitles[$e->course_id] ?? '' }} ({{ $dayLabels[$e->day] }} ف{{ $e->period }})</option>@endforeach</select></div>
        <div><label>مع موعد</label>
            <select name="b" required>@foreach ($entries as $e)<option value="{{ $e->id }}">{{ $e->class_group }} — {{ $courseTitles[$e->course_id] ?? '' }} ({{ $dayLabels[$e->day] }} ف{{ $e->period }})</option>@endforeach</select></div>
        <button class="st-btn sec">تبديل</button>
    </form>
</div>

<div class="st-card">
    <h2>إجراءات المسودة</h2>
    <div class="st-bar">
        <form method="POST" action="{{ route('hod.studio.regenerate', $draft->id) }}">@csrf
            <button class="st-btn sec" title="يعيد توزيع غير المثبّت فقط">إعادة توزيع غير المثبّت</button></form>
        <form method="POST" action="{{ route('hod.studio.discard', $draft->id) }}" onsubmit="return confirm('إهمال هذه المسودة؟')">@csrf
            <button class="st-btn danger">إهمال المسودة</button></form>
    </div>
    <hr style="border:none;border-top:1px solid var(--border-color);margin:1rem 0">
    <form method="POST" action="{{ route('hod.studio.approve', $draft->id) }}" onsubmit="return confirm('اعتماد هذا الجدول سيستبدل الجدول الحالي لقسمك، ويُشعر المعلمين والطلاب. متابعة؟')">
        @csrf
        @if ($unscheduled)
            <label style="display:block;margin-bottom:.6rem;font-size:.85rem"><input type="checkbox" name="acknowledge" value="1"> أقرّ بأن بعض المواد لم تُجدول بالكامل وأريد اعتماد المسودة كما هي</label>
        @endif
        <button class="st-btn" @disabled($violations || $draft->isSavedForNext())>اعتماد الجدول</button>
        @if ($draft->isSavedForNext())<small style="color:#1565c0"> الاعتماد متاح عندما يصبح فصل هذه المسودة فعّالاً</small>@endif
        @if ($violations)<small style="color:#c62828"> لا يمكن الاعتماد قبل حل التعارضات</small>@endif
    </form>
</div>
@endif

@if ($draft->status === 'approved' && !$draft->reverted_at)
<div class="st-card">
    <h2>اعتُمدت في {{ $draft->approved_at->format('Y-m-d H:i') }}</h2>
    @if ($latestApprovedId === $draft->id)
        <form method="POST" action="{{ route('hod.studio.revert', $draft->id) }}" onsubmit="return confirm('سيعود جدول قسمك كما كان قبل هذا الاعتماد. متابعة؟')">@csrf
            <button class="st-btn danger">تراجع عن الاعتماد</button></form>
    @else
        <small style="color:var(--text-secondary)">اعتُمدت مسودة أحدث بعدها؛ التراجع يكون عن الأحدث أولًا.</small>
    @endif
</div>
@endif
@endsection
