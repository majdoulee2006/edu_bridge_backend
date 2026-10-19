@extends('layouts.hod')
@section('title', 'مولّد الجداول')
@section('subtitle', 'ابنِ جدول الفصل تلقائياً أو يدوياً. لا يتغيّر الجدول الحالي ولا يصل شيء للطلاب والمعلمين إلا عند اعتمادك.')

@push('styles')
<style>
    .st-card { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1.2rem; box-shadow: var(--shadow); }
    .st-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.55rem 1.2rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; text-decoration:none; font-size:.85rem; }
    .st-btn.sec { background:transparent; color: var(--primary-color); border:1px solid var(--primary-color); }
    .st-btn.danger { background:#c62828; }
    .st-btn.big { padding:.9rem 1.8rem; font-size:1.05rem; border-radius:.9rem; }
    .st-btn[disabled] { opacity:.5; cursor:not-allowed; }
    .st-form { display:flex; gap:.6rem; flex-wrap:wrap; align-items:flex-end; }
    .st-form label { display:block; font-size:.75rem; color: var(--text-secondary); margin-bottom:.2rem; }
    .st-form input, .st-form select, .st-form textarea { padding:.5rem .7rem; border-radius:.7rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; }
    .st-table { width:100%; border-collapse:collapse; font-size:.85rem; }
    .st-table th, .st-table td { padding:.55rem .7rem; border-bottom:1px solid var(--border-color); text-align:right; color: var(--text-primary); vertical-align:middle; }
    .st-table th { color: var(--text-secondary); font-size:.78rem; }
    .st-warn { color:#e65100; font-weight:800; }
    .st-ok { color:#2e7d32; font-weight:800; }
    .st-badge { padding:.15rem .65rem; border-radius:2rem; font-size:.75rem; font-weight:800; }
    .st-h2 { font-size:1.05rem; margin:0 0 .7rem; color: var(--text-primary); font-weight:800; }
    .st-muted { color: var(--text-secondary); font-size:.85rem; }

    .st-hero { display:grid; grid-template-columns: 2fr 1fr; gap:1rem; margin-bottom:1.2rem; }
    @media (max-width: 800px) { .st-hero { grid-template-columns: 1fr; } }
    .st-main { background: linear-gradient(135deg, color-mix(in srgb, var(--primary-color) 90%, #000), var(--primary-color)); color:#fff; border-radius:1.2rem; padding:1.5rem 1.6rem; box-shadow: var(--shadow); }
    .st-main h2 { margin:0 0 .4rem; font-size:1.35rem; font-weight:900; }
    .st-main p { margin:0 0 1rem; opacity:.92; line-height:1.8; font-size:.9rem; }
    .st-main .st-btn { background:#fff; color:#1a1a1a; }
    .st-main .st-pill { display:inline-block; margin-inline-start:.8rem; padding:.2rem .8rem; border-radius:2rem; background:rgba(255,255,255,.22); font-size:.8rem; font-weight:800; }
    .st-side h3 { margin:0 0 .4rem; font-size:1.05rem; color: var(--text-primary); }

    .rd-list { display:flex; flex-direction:column; gap:.55rem; }
    .rd-item { display:flex; gap:.8rem; align-items:flex-start; padding:.7rem .9rem; border-radius:.8rem; border:1px solid var(--border-color); border-right:5px solid var(--c); background: var(--bg-primary); }
    .rd-ico { font-size:1.1rem; color: var(--c); line-height:1.6; }
    .rd-body { flex:1; }
    .rd-body b { color: var(--text-primary); font-size:.92rem; }
    .rd-body small { display:block; color: var(--text-secondary); margin-top:.15rem; line-height:1.7; }

    .st-tabs { display:flex; gap:.4rem; margin:1.4rem 0 1rem; padding:.3rem; background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1rem; width:fit-content; max-width:100%; flex-wrap:wrap; }
    .st-tabs a { padding:.5rem 1.2rem; border-radius:.75rem; font-weight:800; font-size:.88rem; text-decoration:none; color: var(--text-secondary); }
    .st-tabs a.active { background: var(--primary-color); color:#fff; }

    dialog.st-dialog { border:none; border-radius:1.2rem; padding:0; width:min(520px, 94vw); background: var(--bg-secondary); color: var(--text-primary); box-shadow: 0 20px 60px rgba(0,0,0,.4); }
    dialog.st-dialog::backdrop { background: rgba(0,0,0,.55); }
    .st-dialog .hd { padding:1.1rem 1.3rem; border-bottom:1px solid var(--border-color); font-weight:900; font-size:1.1rem; }
    .st-dialog .bd { padding:1.2rem 1.3rem; display:flex; flex-direction:column; gap:.9rem; }
    .st-dialog .ft { padding:1rem 1.3rem; border-top:1px solid var(--border-color); display:flex; gap:.6rem; justify-content:flex-end; }
    .st-dialog label { display:block; font-size:.78rem; color: var(--text-secondary); margin-bottom:.25rem; }
    .st-dialog select, .st-dialog input { width:100%; padding:.55rem .7rem; border-radius:.7rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; }
    .st-banner { padding:.7rem 1rem; border-radius:.9rem; margin-bottom:1rem; font-size:.88rem; font-weight:700; background: rgba(21,101,192,.12); color:#1565c0; }
    .st-errors { background: rgba(198,40,40,.1); color:#c62828; border-radius:.8rem; padding:.7rem 1rem; margin-bottom:1rem; font-size:.85rem; line-height:1.9; }
</style>
@endpush

@section('content')
@php
    $statusLabel = ['draft' => ['مسودة', '#e65100'], 'approved' => ['معتمدة', '#2e7d32'], 'discarded' => ['مُهمَلة', '#78909c']];
    $semId = $selected?->semester_id;
    $tabUrl = fn (string $t) => route('hod.studio', array_filter(['semester_id' => $semId, 'tab' => $t === 'drafts' ? null : $t]));
    $isNext = $selected && $selected->semester_id !== $currentId;
    $lvl = ['ok' => ['#2e7d32', '✓'], 'info' => ['#1565c0', 'ℹ'], 'warn' => ['#e65100', '⚠'], 'error' => ['#c62828', '✖']];
    $groupByKey = collect($groups)->keyBy('key');
    $programName = $programs->pluck('name', 'id');
    $yearLabels = [1 => 'سنة أولى', 2 => 'سنة ثانية', 3 => 'سنة ثالثة', 4 => 'سنة رابعة', 5 => 'سنة خامسة'];
@endphp

{{-- الفصل الدراسي --}}
<form method="GET" action="{{ route('hod.studio') }}" class="st-card st-form" style="margin-bottom:1rem">
    <div>
        <label>الفصل الدراسي</label>
        <select name="semester_id" onchange="this.form.submit()">
            @foreach ($semesters as $sem)
                <option value="{{ $sem->semester_id }}" @selected($selected && $selected->semester_id === $sem->semester_id)>{{ $sem->name }}{{ $sem->semester_id === $currentId ? ' (الحالي)' : '' }}</option>
            @endforeach
        </select>
    </div>
    @if ($tab !== 'drafts')<input type="hidden" name="tab" value="{{ $tab }}">@endif
    @if (!$selected)
        <span class="st-warn">لا يوجد فصل دراسي فعّال. اختر فصلاً لعرض مقرراته وتوليد جدوله.</span>
    @endif
</form>

@if ($isNext)
    <div class="st-banner">هذا الفصل ليس الفعّال حالياً: المسودة التي تولّدها تُحفظ للفصل القادم، ولا تُعتمد ولا تظهر للطلاب إلا عندما يصبح فعّالاً.</div>
@endif

{{-- الإجراء الرئيسي --}}
<div class="st-hero">
    <div class="st-main">
        <h2>✨ ابنِ لي الجدول تلقائياً</h2>
        <p>يوزّع النظام حصص كل دورة وسنة على الأسبوع دون تعارض معلمين أو قاعات، ويشرح ما تعذّر وضعه. تراجع المسودة وتعدّلها، ثم تعتمدها بنفسك.</p>
        <button type="button" class="st-btn big" onclick="document.getElementById('gen-dialog').showModal()" @disabled(!$selected)>ابدأ البناء</button>
        @if ($readiness)
            @if ($readiness['errors'])<span class="st-pill">⚠ {{ $readiness['errors'] }} يمنع جزءاً من الحصص</span>
            @elseif ($readiness['warnings'])<span class="st-pill">{{ $readiness['warnings'] }} تنبيه</span>
            @else<span class="st-pill">✓ البيانات جاهزة</span>@endif
        @endif
    </div>
    <div class="st-card st-side">
        <h3>✋ أنشئه بنفسي</h3>
        <p class="st-muted">أضف الحصص واحدة واحدة من شاشة التنظيم، مع فحص تعارض المعلم والشعبة والقاعة.</p>
        <a class="st-btn sec" href="{{ url('/hod/organization') }}">فتح التنظيم اليدوي</a>
    </div>
</div>

{{-- جاهزية البيانات --}}
@if ($readiness)
<div class="st-card" style="margin-bottom:1rem">
    <div class="st-h2">جاهزية البيانات @if ($selected)<small class="st-muted">(مقررات {{ $selected->name }})</small>@endif</div>
    <div class="rd-list">
        @foreach ($readiness['items'] as $it)
            @php [$col, $ico] = $lvl[$it['level']]; @endphp
            <div class="rd-item" style="--c: {{ $col }}">
                <span class="rd-ico">{{ $ico }}</span>
                <div class="rd-body">
                    <b>{{ $it['title'] }}</b>
                    @if ($it['detail'])<small>{{ $it['detail'] }}</small>@endif
                </div>
                @if ($it['action'] === 'auto_rooms')
                    <form method="POST" action="{{ route('hod.studio.rooms.auto') }}">@csrf<button class="st-btn sec">أنشئ قاعة لكل شعبة</button></form>
                @elseif ($it['action'] === 'open_rooms')
                    <a class="st-btn sec" href="{{ $tabUrl('rooms') }}">القاعات</a>
                @elseif ($it['action'] === 'open_availability')
                    <a class="st-btn sec" href="{{ $tabUrl('availability') }}">إضافة قيود</a>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- التبويبات --}}
<div class="st-tabs">
    <a href="{{ $tabUrl('drafts') }}" class="{{ $tab === 'drafts' ? 'active' : '' }}">المسودات</a>
    <a href="{{ $tabUrl('rooms') }}" class="{{ $tab === 'rooms' ? 'active' : '' }}">القاعات</a>
    <a href="{{ $tabUrl('availability') }}" class="{{ $tab === 'availability' ? 'active' : '' }}">توفّر المعلمين</a>
</div>

{{-- ───────── المسودات ───────── --}}
@if ($tab === 'drafts')
<div class="st-card">
    <div class="st-h2">المسودات</div>
    <table class="st-table">
        <tr><th>الاسم</th><th>الفصل</th><th>الحالة</th><th>موضوع</th><th>غير مجدول</th><th>التاريخ</th><th></th></tr>
        @forelse ($drafts as $d)
            @php [$lbl, $col] = $statusLabel[$d->status]; $s = $d->stats ?? []; if ($d->isSavedForNext()) { [$lbl, $col] = ['محفوظة للفصل القادم', '#1565c0']; } @endphp
            <tr>
                <td>{{ $d->name }}</td>
                <td>{{ $d->semester->name ?? '—' }}</td>
                <td><span class="st-badge" style="color:{{ $col }};background:color-mix(in srgb, {{ $col }} 16%, transparent)">{{ $lbl }}{{ $d->reverted_at ? ' (تُرُوجع عنها)' : '' }}</span></td>
                <td>{{ $s['placed'] ?? 0 }} / {{ $s['required'] ?? 0 }}</td>
                <td>{{ count($s['unscheduled'] ?? []) }}</td>
                <td>{{ $d->created_at->format('Y-m-d H:i') }}</td>
                <td><a class="st-btn sec" href="{{ route('hod.studio.show', $d->id) }}">فتح</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="st-muted">لا مسودات بعد. اضغط «ابدأ البناء» أعلاه.</td></tr>
        @endforelse
    </table>
</div>
@endif

{{-- ───────── القاعات ───────── --}}
@if ($tab === 'rooms')
@if (session('import_errors'))
    <div class="st-errors"><b>أسطر لم تُستورد:</b><br>
        @foreach (session('import_errors') as $line => $msg)سطر {{ $line }}: {{ $msg }}<br>@endforeach
    </div>
@endif

<div class="st-card" style="margin-bottom:1rem">
    <div class="st-h2">القاعات</div>
    <p class="st-muted" style="margin-top:0">لكل دورة وسنة قاعة خاصة بها، وتبقى حصصها فيها. القاعة المشتركة (مخبر أو قاعة كبرى) تُترك بلا شعبة ويستعملها أي صف.</p>

    <div class="st-form" style="margin-bottom:1rem">
        <form method="POST" action="{{ route('hod.studio.rooms.auto') }}">@csrf<button class="st-btn">أنشئ قاعة لكل شعبة تلقائياً</button></form>
        <button type="button" class="st-btn sec" onclick="document.getElementById('import-box').toggleAttribute('hidden')">استيراد قائمة قاعات</button>
    </div>

    <div id="import-box" hidden style="margin-bottom:1rem">
        <form method="POST" action="{{ route('hod.studio.rooms.import') }}">@csrf
            <label class="st-muted">سطر لكل قاعة: الاسم، السعة، النوع (اختياري)، الدورة (اختياري)، السنة (اختياري)</label>
            <textarea name="lines" rows="6" style="width:100%;margin:.4rem 0;padding:.6rem;border-radius:.7rem;border:1px solid var(--border-color);background:transparent;color:var(--text-primary);font-family:inherit"
                      placeholder="قاعة معلوماتية - سنة أولى، 40، قاعة شعبة، معلوماتية، أولى&#10;مخبر الحاسوب، 25، مخبر&#10;المدرج، 120، قاعة كبرى"></textarea>
            <button class="st-btn">استيراد</button>
        </form>
    </div>

    <form method="POST" action="{{ route('hod.studio.rooms.store') }}" class="st-form" style="margin-bottom:1rem">
        @csrf
        <div><label>اسم القاعة</label><input name="name" required maxlength="60"></div>
        <div><label>السعة</label><input type="number" name="capacity" required min="1" max="2000" style="width:90px"></div>
        <div><label>النوع</label><select name="type">@foreach ($roomTypes as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <div><label>الدورة (فارغ = مشتركة)</label>
            <select name="program_id"><option value="">مشتركة</option>@foreach ($programs as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
        <div><label>السنة</label><select name="year">@foreach ($yearLabels as $y => $yl)<option value="{{ $y }}">{{ $yl }}</option>@endforeach</select></div>
        <button class="st-btn">إضافة</button>
    </form>

    <table class="st-table">
        <tr><th>القاعة</th><th>النوع</th><th>الشعبة</th><th>السعة</th><th>طلاب الشعبة</th><th></th></tr>
        @forelse ($rooms as $r)
            @php
                $owner = $r->program_id && $r->year ? $groupByKey->get($r->program_id . '-' . $r->year) : null;
                $size = $owner['size'] ?? null;
                $tooSmall = $size !== null && $r->capacity !== null && $size > $r->capacity;
            @endphp
            <tr>
                <td><b>{{ $r->name }}</b> {!! $r->department_id ? '' : '<small>(مشتركة بين الأقسام)</small>' !!}</td>
                <td>{{ $roomTypes[$r->type] ?? $r->type }}</td>
                <td>@if ($r->program_id){{ $programName[$r->program_id] ?? '' }} - {{ $yearLabels[$r->year] ?? $r->year }}@else<span class="st-muted">مشتركة</span>@endif</td>
                <td class="{{ $tooSmall ? 'st-warn' : '' }}">{{ $r->capacity }} مقعد @if ($tooSmall)⚠@endif</td>
                <td>{{ $size ?? '—' }}</td>
                <td>
                    @if ($r->department_id)
                        <details style="display:inline-block">
                            <summary class="st-btn sec" style="display:inline-flex">تعديل</summary>
                            <form method="POST" action="{{ route('hod.studio.rooms.update', $r->id) }}" class="st-form" style="margin-top:.6rem">@csrf
                                <div><label>الاسم</label><input name="name" value="{{ $r->name }}" required maxlength="60"></div>
                                <div><label>السعة</label><input type="number" name="capacity" value="{{ $r->capacity }}" required min="1" max="2000" style="width:90px"></div>
                                <div><label>النوع</label><select name="type">@foreach ($roomTypes as $k => $l)<option value="{{ $k }}" @selected($r->type === $k)>{{ $l }}</option>@endforeach</select></div>
                                <div><label>الدورة</label><select name="program_id"><option value="">مشتركة</option>@foreach ($programs as $p)<option value="{{ $p->id }}" @selected($r->program_id == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
                                <div><label>السنة</label><select name="year">@foreach ($yearLabels as $y => $yl)<option value="{{ $y }}" @selected($r->year == $y)>{{ $yl }}</option>@endforeach</select></div>
                                <button class="st-btn">حفظ</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('hod.studio.rooms.delete', $r->id) }}" style="display:inline" onsubmit="return confirm('حذف هذه القاعة؟')">@csrf<button class="st-btn danger">حذف</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="st-muted">لا قاعات بعد. اضغط «أنشئ قاعة لكل شعبة تلقائياً».</td></tr>
        @endforelse
    </table>
</div>
@endif

{{-- ───────── توفّر المعلمين ───────── --}}
@if ($tab === 'availability')
<div class="st-card">
    <div class="st-h2">توفّر المعلمين</div>
    <p class="st-muted" style="margin-top:0">«غير متاح» قيد صلب لا يضع النظام فيه حصة للمعلم. «يفضّل تجنّبه» قيد ناعم يتجنبه إن أمكن.</p>
    <form method="POST" action="{{ route('hod.studio.availability.store') }}" class="st-form" style="margin-bottom:.8rem">
        @csrf
        <div><label>المعلم</label><select name="teacher_user_id" required>@foreach ($teachers as $t)<option value="{{ $t->user_id }}">{{ $t->full_name }}</option>@endforeach</select></div>
        <div><label>اليوم</label><select name="day">@foreach ($days as $d)<option value="{{ $d }}">{{ $dayLabels[$d] }}</option>@endforeach</select></div>
        <div><label>الفترة</label><select name="period">@for ($i = 1; $i <= $maxPeriods; $i++)<option value="{{ $i }}">{{ $i }} ({{ $periods[$i][0] }})</option>@endfor</select></div>
        <div><label>النوع</label><select name="kind"><option value="unavailable">غير متاح</option><option value="avoid">يفضّل تجنّبه</option></select></div>
        <button class="st-btn">حفظ</button>
    </form>
    <table class="st-table">
        @php $tn = $teachers->pluck('full_name', 'user_id'); @endphp
        @forelse ($avail as $a)
            <tr>
                <td>{{ $tn[$a->teacher_user_id] ?? '—' }}</td><td>{{ $dayLabels[$a->day] ?? $a->day }}</td>
                <td>الفترة {{ $a->period }} ({{ $periods[$a->period][0] ?? '' }})</td>
                <td>{{ $a->kind === 'unavailable' ? 'غير متاح' : 'يفضّل تجنّبه' }}</td>
                <td><form method="POST" action="{{ route('hod.studio.availability.delete', $a->id) }}">@csrf<button class="st-btn danger">حذف</button></form></td>
            </tr>
        @empty
            <tr><td class="st-muted">لا قيود مسجّلة.</td></tr>
        @endforelse
    </table>
</div>
@endif

{{-- نافذة البناء --}}
<dialog id="gen-dialog" class="st-dialog">
    <form method="POST" action="{{ route('hod.studio.generate') }}">
        @csrf
        @if ($selected)<input type="hidden" name="semester_id" value="{{ $selected->semester_id }}">@endif
        <div class="hd">بناء جدول أسبوعي{{ $selected ? ' — ' . $selected->name : '' }}</div>
        <div class="bd">
            <div>
                <label>عدد الفترات في اليوم</label>
                <select name="periods">
                    @for ($i = 3; $i <= $maxPeriods; $i++)
                        <option value="{{ $i }}" @selected($i === $defaultPeriods)>{{ $i }} فترات ({{ $periods[1][0] }} إلى {{ $periods[$i][1] }})</option>
                    @endfor
                </select>
            </div>
            @if ($isNext)<div class="st-banner" style="margin:0">ستُحفظ المسودة للفصل القادم ولن تُعتمد الآن.</div>@endif
            <details>
                <summary class="st-muted" style="cursor:pointer">خيارات متقدمة</summary>
                <div style="margin-top:.7rem"><label>اسم المسودة (اختياري)</label><input name="name" maxlength="120" placeholder="جدول أسبوعي: {{ $selected->name ?? '' }}"></div>
            </details>
            <p class="st-muted" style="margin:0">لن يتغيّر الجدول الحالي. ستراجع المسودة وتعدّلها قبل أي اعتماد.</p>
        </div>
        <div class="ft">
            <button type="button" class="st-btn sec" onclick="this.closest('dialog').close()">إلغاء</button>
            <button class="st-btn">ابنِ الجدول</button>
        </div>
    </form>
</dialog>
@endsection
