@extends('layouts.hod')
@section('title', $draft->name)
@section('subtitle', 'مسودة لا يراها أحد غيرك. عدّل المواعيد والقاعات واختر المراقبين، ثم اعتمد.')

@push('styles')
    @include('exam_studio._styles')
@endpush

@php
    $editable = $draft->status === 'draft';
    $stats = $d['stats'] ?? [];
    $violations = $stats['violations'] ?? [];
    $unscheduled = $stats['unscheduled'] ?? [];
    $noProctor = (int) ($stats['rooms_without_proctor'] ?? 0);
    $statusLabel = ['draft' => 'مسودة', 'approved' => 'معتمد', 'discarded' => 'مُهمَل'][$draft->status];
    $kindColor = ['midterm' => '#1565c0', 'final' => '#6a1b9a'];
@endphp

@section('content')
<div class="st-bar" style="margin-bottom:1rem">
    <a class="st-btn sec" href="{{ route('hod.exams') }}">← كل الجداول</a>
    <span class="st-btn sec" style="cursor:default">{{ $statusLabel }}{{ $d['reverted'] ? ' (تُرُوجع عنه)' : '' }}</span>
    <span class="st-btn sec" style="cursor:default">{{ $d['semester'] }}</span>
</div>

@if ($d['saved_for_next'])
    <div class="st-banner">محفوظ للفصل القادم: يمكنك مراجعته وتعديله، لكنه لا يُعتمد ولا يظهر لأحد إلا عندما يصبح فصله «{{ $d['semester'] }}» فعّالاً.</div>
@endif

<div class="st-stats">
    <div class="st-stat" style="--c:#2e7d32"><b>{{ $stats['placed'] ?? 0 }} / {{ $stats['required'] ?? 0 }}</b><small>تقييمات موضوعة</small></div>
    <div class="st-stat" style="--c:{{ $unscheduled ? '#e65100' : '#2e7d32' }}"><b>{{ count($unscheduled) }}</b><small>لم تُجدول</small></div>
    <div class="st-stat" style="--c:{{ $violations ? '#c62828' : '#2e7d32' }}"><b>{{ count($violations) }}</b><small>تعارضات</small></div>
    <div class="st-stat" style="--c:{{ $noProctor ? '#e65100' : '#2e7d32' }}"><b>{{ $noProctor }}</b><small>قاعات بلا مراقب</small></div>
</div>

@foreach ($violations as $v)<div class="st-err">⛔ {{ $v['message'] }}</div>@endforeach

@foreach (($stats['capacity'] ?? []) as $c)
    @if ($c['overflow'] > 0)<div class="st-warnbox">⚠ {{ $c['label'] }} ({{ $c['kind_label'] }}): تحتاج {{ $c['needed_days'] }} يوماً والمتاح {{ $c['available_days'] }}. مدّد الفترة.</div>@endif
@endforeach

@if ($unscheduled)
<div class="st-card">
    <div class="st-h2">ما لم يُجدول ولماذا</div>
    @foreach ($unscheduled as $u)
        <div class="st-warnbox"><b>{{ $u['group_label'] }} — {{ $u['title'] }} ({{ $u['kind_label'] }})</b>: {{ $u['message'] }}</div>
    @endforeach
</div>
@endif

@foreach ($byDate as $date => $items)
<div class="ex-day">
    <h3>{{ $items[0]['day'] }} — {{ $date }}</h3>
    @foreach ($items as $e)
    <div class="ex-item {{ $e['locked'] ? 'locked' : '' }}" style="--k: {{ $kindColor[$e['kind']] ?? '#555' }}">
        <div class="ex-head">
            <span class="st-badge" style="color:{{ $kindColor[$e['kind']] ?? '#555' }};background:color-mix(in srgb, {{ $kindColor[$e['kind']] ?? '#555' }} 16%, transparent)">{{ $e['kind_label'] }}</span>
            <b>{{ $e['title'] }}</b>
            <span class="st-muted">{{ $e['group_label'] }}</span>
            <span class="st-muted">{{ $e['start'] }}–{{ $e['end'] }} ({{ $e['duration'] }} دقيقة)</span>
            @if ($e['source'] === 'manual')<span class="st-badge" style="color:#2e7d32;background:rgba(46,125,50,.14)">معدّل يدوياً</span>@endif
            @if ($editable)
                <span style="margin-inline-start:auto" class="st-bar">
                    <form method="POST" action="{{ route('hod.exams.lock', $draft->id) }}">@csrf
                        <input type="hidden" name="entry_id" value="{{ $e['id'] }}"><input type="hidden" name="locked" value="{{ $e['locked'] ? 0 : 1 }}">
                        <button class="st-btn sec mini" title="المثبّت لا يتغير عند إعادة التوليد">{{ $e['locked'] ? 'إلغاء التثبيت' : 'تثبيت' }}</button>
                    </form>
                    <details>
                        <summary class="st-btn sec mini" style="display:inline-flex">نقل</summary>
                        <form method="POST" action="{{ route('hod.exams.move', $draft->id) }}" class="st-form" style="margin-top:.5rem">@csrf
                            <input type="hidden" name="entry_id" value="{{ $e['id'] }}">
                            <div><label>اليوم</label>
                                <select name="date">@foreach (($d['dates'][$e['kind']] ?? []) as $dt)<option value="{{ $dt }}" @selected($dt === $e['date'])>{{ $dt }}</option>@endforeach</select></div>
                            <div><label>الفترة</label>
                                <select name="slot">@foreach (($d['slots'][$e['kind']] ?? []) as $sl)<option value="{{ $sl['slot'] }}" @selected($sl['start'] === $e['start'])>{{ $sl['start'] }}–{{ $sl['end'] }}</option>@endforeach</select></div>
                            <button class="st-btn mini">نقل</button>
                        </form>
                    </details>
                </span>
            @endif
        </div>

        @if ($editable)
        <form method="POST" action="{{ route('hod.exams.proctors', $draft->id) }}">@csrf
            <input type="hidden" name="entry_id" value="{{ $e['id'] }}">
        @endif
        <div class="ex-rooms">
            @foreach ($e['rooms'] as $r)
                <div class="ex-room">
                    <b>{{ $r['name'] }}</b> <span class="st-muted">{{ $r['count'] }} / {{ $r['capacity'] }} طالباً</span>
                    @if ($editable)
                        <select name="proctors[{{ $r['name'] }}]">
                            <option value="">— المراقب —</option>
                            @foreach ($d['teachers'] as $t)<option value="{{ $t['id'] }}" @selected($t['id'] === $r['proctor_id'])>{{ $t['name'] }}</option>@endforeach
                        </select>
                    @else
                        <div class="st-muted">المراقب: {{ $r['proctor'] ?? 'غير محدد' }}</div>
                    @endif
                    <details style="margin-top:.35rem">
                        <summary>الطلاب</summary>
                        @foreach ($r['students'] as $s)
                            <div class="ex-stu"><span>{{ $s['name'] }}</span>
                                @if ($editable && count($e['rooms']) > 1)
                                    <button type="submit" form="mv-{{ $e['id'] }}-{{ $s['id'] }}" class="st-btn sec mini">نقل</button>
                                @endif
                            </div>
                        @endforeach
                    </details>
                </div>
            @endforeach
        </div>
        @if ($editable)
            <div style="margin-top:.5rem"><button class="st-btn mini">حفظ المراقبين</button></div>
        </form>
        @endif

        @if ($editable && count($e['rooms']) > 1)
            @foreach ($e['rooms'] as $r)
                @foreach ($r['students'] as $s)
                    <form id="mv-{{ $e['id'] }}-{{ $s['id'] }}" method="POST" action="{{ route('hod.exams.student', $draft->id) }}" onsubmit="return pickRoom(this, '{{ addslashes($r['name']) }}', {{ json_encode(array_column($e['rooms'], 'name')) }})">
                        @csrf<input type="hidden" name="entry_id" value="{{ $e['id'] }}"><input type="hidden" name="student_id" value="{{ $s['id'] }}"><input type="hidden" name="room" value="">
                    </form>
                @endforeach
            @endforeach
        @endif
    </div>
    @endforeach
</div>
@endforeach

@if ($editable)
<div class="st-card">
    <div class="st-h2">إجراءات المسودة</div>
    <div class="st-bar">
        <form method="POST" action="{{ route('hod.exams.regenerate', $draft->id) }}">@csrf<button class="st-btn sec" title="يعيد توزيع غير المثبّت فقط">إعادة توزيع غير المثبّت</button></form>
        <form method="POST" action="{{ route('hod.exams.discard', $draft->id) }}" onsubmit="return confirm('إهمال هذه المسودة؟')">@csrf<button class="st-btn danger">إهمال المسودة</button></form>
    </div>
    <hr style="border:none;border-top:1px solid var(--border-color);margin:1rem 0">
    @if ($impact)
        <p class="st-muted" style="margin-top:0">عند الاعتماد: يُحدَّث {{ $impact['updated'] }} تقييماً قائماً ويُضاف {{ $impact['created'] }}، ويصل الجدول إلى {{ $impact['students'] }} طالباً و{{ $impact['teachers'] }} معلماً. العلامات المرصودة لا تُمسّ.</p>
    @endif
    <form method="POST" action="{{ route('hod.exams.approve', $draft->id) }}" onsubmit="return confirm('اعتماد الجدول سيجعله ظاهراً للطلاب والمعلمين والمراقبين ويُشعرهم. متابعة؟')">@csrf
        @if ($unscheduled || $noProctor)
            <label style="display:block;margin-bottom:.6rem;font-size:.85rem"><input type="checkbox" name="acknowledge" value="1"> أقرّ بأن {{ $unscheduled ? 'بعض التقييمات لم تُجدول' : '' }}{{ $unscheduled && $noProctor ? ' وأن ' : '' }}{{ $noProctor ? 'بعض القاعات بلا مراقب' : '' }} وأريد الاعتماد كما هو</label>
        @endif
        <button class="st-btn" @disabled($violations || $d['saved_for_next'])>اعتماد الجدول</button>
        @if ($violations)<small style="color:#c62828"> لا يمكن الاعتماد قبل حل التعارضات</small>@endif
    </form>
</div>
@endif

@if ($draft->status === 'approved' && !$d['reverted'])
<div class="st-card">
    <div class="st-h2">اعتُمد في {{ $d['approved_at'] }}</div>
    @if ($latestApprovedId === $draft->id)
        <form method="POST" action="{{ route('hod.exams.revert', $draft->id) }}" onsubmit="return confirm('سيعود الجدول كما كان قبل هذا الاعتماد. متابعة؟')">@csrf<button class="st-btn danger">تراجع عن الاعتماد</button></form>
    @else
        <small class="st-muted">اعتُمد جدول أحدث بعده؛ التراجع يكون عن الأحدث أولاً.</small>
    @endif
</div>
@endif

<script>
function pickRoom(form, current, rooms) {
    var others = rooms.filter(function (r) { return r !== current; });
    var to = others.length === 1 ? others[0] : prompt('انقل إلى أي قاعة؟\n' + others.join('\n'), others[0]);
    if (!to || others.indexOf(to) < 0) { return false; }
    form.room.value = to;
    return true;
}
</script>
@endsection
