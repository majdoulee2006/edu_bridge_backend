@extends('layouts.hod')
@section('title', 'مولّد الامتحانات')
@section('subtitle', 'مذاكرة وامتحان لكل مقرر. تحدّد التواريخ والفترات والمدد، ويوزّع النظام الطلاب على القاعات، وتختار مراقب كل قاعة. لا يراه أحد حتى تعتمد.')

@push('styles')
    @include('exam_studio._styles')
@endpush

@section('content')
@php
    $statusLabel = ['draft' => ['مسودة', '#e65100'], 'approved' => ['معتمد', '#2e7d32'], 'discarded' => ['مُهمَل', '#78909c']];
    $isNext = $selected && $selected->semester_id !== $currentId;
    $lvl = ['ok' => ['#2e7d32', '✓'], 'info' => ['#1565c0', 'ℹ'], 'warn' => ['#e65100', '⚠'], 'error' => ['#c62828', '✖']];
    $kindDefs = [
        'midterm' => ['label' => 'المذاكرات', 'hint' => 'امتحان نصفي مصغّر، بفترة مستقلة عن الامتحانات النهائية'],
        'final' => ['label' => 'الامتحانات النهائية', 'hint' => 'تبدأ بعد انتهاء فترة المذاكرات'],
    ];
    $old = old('kinds', []);
@endphp

<form method="GET" action="{{ route('hod.exams') }}" class="st-card st-form">
    <div>
        <label>الفصل الدراسي</label>
        <select name="semester_id" onchange="this.form.submit()">
            @foreach ($semesters as $sem)
                <option value="{{ $sem->semester_id }}" @selected($selected && $selected->semester_id === $sem->semester_id)>{{ $sem->name }}{{ $sem->semester_id === $currentId ? ' (الحالي)' : '' }}</option>
            @endforeach
        </select>
    </div>
</form>

@if ($isNext)
    <div class="st-banner">هذا الفصل ليس الفعّال حالياً: الجدول الذي تولّده يُحفظ للفصل القادم، ولا يُعتمد ولا يظهر للطلاب إلا عندما يصبح فعّالاً.</div>
@endif

<div class="st-hero">
    <div class="st-main">
        <h2>📝 ابنِ لي جدول المذاكرات والامتحانات</h2>
        <p>تدخل تواريخ كل فترة وفتراتها اليومية ومدة كل نوع. يضمن النظام تقييماً واحداً فقط لكل شعبة في اليوم، ويوزّع طلاب الشعبة على عدة قاعات عند الحاجة، ثم تختار مراقباً لكل قاعة من أساتذة القسم.</p>
        <button type="button" class="st-btn big" onclick="document.getElementById('exam-dialog').showModal()" @disabled(!$selected || ($readiness && !$readiness['ready']))>ابدأ البناء</button>
        @if ($readiness)
            @if ($readiness['errors'])<span class="st-pill">⚠ {{ $readiness['errors'] }} يمنع البناء</span>
            @elseif ($readiness['warnings'])<span class="st-pill">{{ $readiness['warnings'] }} تنبيه</span>
            @else<span class="st-pill">✓ البيانات جاهزة</span>@endif
        @endif
    </div>
    <div class="st-card">
        <div class="st-h2">🏫 القاعات</div>
        <p class="st-muted" style="margin-top:0">تُدار من مولّد الجدول الأسبوعي، وتُستعمل هنا كما هي.</p>
        <a class="st-btn sec" href="{{ route('hod.studio', ['tab' => 'rooms']) }}">إدارة القاعات</a>
    </div>
</div>

@if ($readiness)
<div class="st-card">
    <div class="st-h2">جاهزية البيانات</div>
    <div class="rd-list">
        @foreach ($readiness['items'] as $it)
            @php [$col, $ico] = $lvl[$it['level']]; @endphp
            <div class="rd-item" style="--c: {{ $col }}">
                <span class="rd-ico">{{ $ico }}</span>
                <div class="rd-body"><b>{{ $it['title'] }}</b>@if ($it['detail'])<small>{{ $it['detail'] }}</small>@endif</div>
                @if ($it['action'] === 'open_rooms')<a class="st-btn sec" href="{{ route('hod.studio', ['tab' => 'rooms']) }}">القاعات</a>@endif
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="st-card">
    <div class="st-h2">الجداول الامتحانية</div>
    <table class="st-table">
        <tr><th>الاسم</th><th>الفصل</th><th>الحالة</th><th>موضوع</th><th>غير مجدول</th><th>التاريخ</th><th></th></tr>
        @forelse ($drafts as $dr)
            @php [$lbl, $col] = $statusLabel[$dr->status]; $s = $dr->stats ?? []; if ($dr->isSavedForNext()) { [$lbl, $col] = ['محفوظ للفصل القادم', '#1565c0']; } @endphp
            <tr>
                <td>{{ $dr->name }}</td>
                <td>{{ $dr->semester->name ?? '—' }}</td>
                <td><span class="st-badge" style="color:{{ $col }};background:color-mix(in srgb, {{ $col }} 16%, transparent)">{{ $lbl }}{{ $dr->reverted_at ? ' (تُرُوجع عنه)' : '' }}</span></td>
                <td>{{ $s['placed'] ?? 0 }} / {{ $s['required'] ?? 0 }}</td>
                <td>{{ count($s['unscheduled'] ?? []) }}</td>
                <td>{{ $dr->created_at->format('Y-m-d H:i') }}</td>
                <td><a class="st-btn sec" href="{{ route('hod.exams.show', $dr->id) }}">فتح</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="st-muted">لا جداول بعد. اضغط «ابدأ البناء».</td></tr>
        @endforelse
    </table>
</div>

{{-- نافذة البناء --}}
<dialog id="exam-dialog" class="st-dialog">
    <form method="POST" action="{{ route('hod.exams.generate') }}">
        @csrf
        @if ($selected)<input type="hidden" name="semester_id" value="{{ $selected->semester_id }}">@endif
        <div class="hd">بناء جدول امتحاني{{ $selected ? ' — ' . $selected->name : '' }}</div>
        <div class="bd">
            @foreach ($kindDefs as $kind => $def)
                @php
                    $o = $old[$kind] ?? [];
                    $on = $old ? !empty($o['enabled']) : true;
                    $slots = $o['slots'] ?? [['start' => '09:00', 'end' => $kind === 'final' ? '11:30' : '10:30'], ['start' => '12:00', 'end' => $kind === 'final' ? '14:30' : '13:30']];
                @endphp
                <div class="kind-box {{ $on ? '' : 'off' }}" data-kind="{{ $kind }}">
                    <label class="kind-title">
                        <input type="hidden" name="kinds[{{ $kind }}][enabled]" value="0"><input type="checkbox" name="kinds[{{ $kind }}][enabled]" value="1" @checked($on) onchange="this.closest('.kind-box').classList.toggle('off', !this.checked)">
                        {{ $def['label'] }} <span class="st-muted" style="font-weight:400">— {{ $def['hint'] }}</span>
                    </label>
                    <div class="kind-fields">
                        <div><label>من تاريخ</label><input type="date" name="kinds[{{ $kind }}][from]" value="{{ $o['from'] ?? '' }}"></div>
                        <div><label>إلى تاريخ</label><input type="date" name="kinds[{{ $kind }}][to]" value="{{ $o['to'] ?? '' }}"></div>
                        <div><label>مدة التقييم (دقيقة)</label><input type="number" min="15" max="360" step="5" name="kinds[{{ $kind }}][duration]" value="{{ $o['duration'] ?? $defaults[$kind] }}"></div>
                        <div class="wide">
                            <label>الفترات اليومية (متى يبدأ التقييم وينتهي أقصى وقت لكل فترة)</label>
                            <div class="slots" data-name="kinds[{{ $kind }}][slots]">
                                @foreach ($slots as $i => $sl)
                                    <div class="slot-row"><input type="time" name="kinds[{{ $kind }}][slots][{{ $i }}][start]" value="{{ $sl['start'] }}"><span>إلى</span><input type="time" name="kinds[{{ $kind }}][slots][{{ $i }}][end]" value="{{ $sl['end'] }}"><button type="button" class="st-btn danger mini" onclick="this.parentNode.remove()">×</button></div>
                                @endforeach
                            </div>
                            <button type="button" class="st-btn sec mini" onclick="addSlot(this.previousElementSibling, {{ $maxSlots }})">+ فترة</button>
                        </div>
                        <div class="wide">
                            <label>أيام مستثناة (عطل أو أيام لا امتحان فيها) — تواريخ مفصولة بفاصلة أو سطر، بصيغة 2026-12-25</label>
                            <textarea name="kinds[{{ $kind }}][excluded]" rows="2">{{ $o['excluded'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
            <p class="st-muted" style="margin:0">الأيام المتاحة هي أيام الدوام الأسبوعية فقط. اليوم الذي تُحجز فيه مذاكرة أو امتحان لشعبة لا يُحجز فيه شيء آخر لها. لن يظهر شيء لأحد قبل اعتمادك.</p>
        </div>
        <div class="ft">
            <button type="button" class="st-btn sec" onclick="this.closest('dialog').close()">إلغاء</button>
            <button class="st-btn">ابنِ الجدول</button>
        </div>
    </form>
</dialog>
<script>
function addSlot(box, max) {
    var rows = box.querySelectorAll('.slot-row');
    if (rows.length >= max) { return; }
    var name = box.dataset.name, i = Date.now() % 100000;
    var d = document.createElement('div'); d.className = 'slot-row';
    d.innerHTML = '<input type="time" name="' + name + '[' + i + '][start]"><span>إلى</span><input type="time" name="' + name + '[' + i + '][end]"><button type="button" class="st-btn danger mini" onclick="this.parentNode.remove()">×</button>';
    box.appendChild(d);
}
@if ($errors->any() || session('error'))
document.getElementById('exam-dialog').showModal();
@endif
</script>
@endsection
