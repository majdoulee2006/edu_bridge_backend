@extends('layouts.hod')
@section('title', 'مولّد الجدول')
@section('subtitle', 'اختر: إنشاء الجدول يدويًا، أو اطلب من النظام مسودة مقترحة تراجعها وتعدّلها وتعتمدها. لا يتغيّر الجدول الحالي إلا عند اعتمادك.')

@push('styles')
<style>
    .st-choice { display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1rem; margin-bottom:1.5rem; }
    .st-card { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1.2rem; box-shadow: var(--shadow); }
    .st-card h3 { margin:0 0 .4rem; font-size:1.05rem; color: var(--text-primary); }
    .st-card p { margin:0 0 .8rem; color: var(--text-secondary); font-size:.85rem; line-height:1.8; }
    .st-btn { display:inline-block; padding:.55rem 1.2rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; text-decoration:none; font-size:.85rem; }
    .st-btn.sec { background:transparent; color: var(--primary-color); border:1px solid var(--primary-color); }
    .st-btn.danger { background:#c62828; }
    .st-form { display:flex; gap:.6rem; flex-wrap:wrap; align-items:flex-end; }
    .st-form label { display:block; font-size:.75rem; color: var(--text-secondary); margin-bottom:.2rem; }
    .st-form input, .st-form select { padding:.5rem .7rem; border-radius:.7rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; }
    .st-table { width:100%; border-collapse:collapse; font-size:.85rem; }
    .st-table th, .st-table td { padding:.55rem .7rem; border-bottom:1px solid var(--border-color); text-align:right; color: var(--text-primary); }
    .st-table th { color: var(--text-secondary); font-size:.78rem; }
    .st-warn { color:#e65100; font-weight:800; }
    .st-ok { color:#2e7d32; font-weight:800; }
    .st-sec { margin-bottom:1.5rem; }
    .st-sec > h2 { font-size:1.05rem; margin:0 0 .7rem; color: var(--text-primary); }
    .st-badge { padding:.15rem .65rem; border-radius:2rem; font-size:.75rem; font-weight:800; }
</style>
@endpush

@section('content')
@php $statusLabel = ['draft' => ['مسودة', '#e65100'], 'approved' => ['معتمدة', '#2e7d32'], 'discarded' => ['مُهمَلة', '#78909c']]; @endphp

<div class="st-card" style="margin-bottom:1rem">
    <form method="GET" action="{{ route('hod.studio') }}" class="st-form">
        <div>
            <label>الفصل الدراسي</label>
            <select name="semester_id" onchange="this.form.submit()">
                @foreach ($semesters as $sem)
                    <option value="{{ $sem->semester_id }}" @selected($selected && $selected->semester_id === $sem->semester_id)>{{ $sem->name }}{{ $sem->semester_id === $currentId ? ' (الحالي)' : '' }}</option>
                @endforeach
            </select>
        </div>
        @if (!$selected)
            <span class="st-warn">لا يوجد فصل دراسي فعّال. اختر فصلاً لعرض مقرراته وتوليد جدوله.</span>
        @elseif ($selected->semester_id !== $currentId)
            <span class="st-warn">هذا الفصل ليس الفعّال حالياً: المسودة التي تولّدها تُحفظ للفصل القادم، ولا تُعتمد إلا عندما يصبح فعّالاً.</span>
        @endif
    </form>
</div>

<div class="st-choice">
    <div class="st-card">
        <h3><i class="fa-solid fa-hand-pointer"></i> إنشاء يدوي</h3>
        <p>أضف الحصص بنفسك واحدة واحدة من شاشة التنظيم، مع فحص تعارض المعلم والشعبة والقاعة.</p>
        <a class="st-btn sec" href="{{ url('/hod/organization') }}">فتح التنظيم اليدوي</a>
    </div>
    <div class="st-card">
        <h3><i class="fa-solid fa-wand-magic-sparkles"></i> اقتراح من النظام</h3>
        <p>يبني النظام مسودة تحترم القيود (لا تعارض ولا قاعة ممتلئة ولا معلم غير متاح) ويشرح ما تعذّر وضعه. أنت من يراجع ويعدّل ويعتمد.</p>
        <form class="st-form" method="POST" action="{{ route('hod.studio.generate') }}">
            @csrf
            @if ($selected)<input type="hidden" name="semester_id" value="{{ $selected->semester_id }}">@endif
            <div><label>اسم المسودة</label><input name="name" maxlength="120" placeholder="افتراضي: جدول أسبوعي: {{ $selected->name ?? '' }}"></div>
            <div><label>فترات اليوم</label>
                <select name="periods">
                    @for ($i = 3; $i <= $maxPeriods; $i++)
                        <option value="{{ $i }}" @selected($i === $defaultPeriods)>{{ $i }} ({{ $periods[1][0] }}–{{ $periods[$i][1] }})</option>
                    @endfor
                </select>
            </div>
            <button class="st-btn" @disabled(!$selected)>توليد مسودة</button>
        </form>
    </div>
</div>

<div class="st-sec st-card">
    <h2>هل يتسع الأسبوع لحصص كل شعبة؟ @if ($selected)<small style="color:var(--text-secondary)">(مقررات {{ $selected->name }})</small>@endif</h2>
    <table class="st-table">
        <tr><th>الشعبة</th><th>حصص مطلوبة</th><th>الفترات المتاحة</th><th>الحالة</th></tr>
        @forelse ($capacity as $c)
            <tr>
                <td>{{ $c['label'] }}</td>
                <td>{{ $c['needed'] }}</td>
                <td>{{ $c['available'] }}</td>
                <td>
                    @if ($c['overflow'] > 0)<span class="st-warn">يزيد {{ $c['overflow'] }} حصة عن المتاح: زد الفترات أو قلّل المواد</span>
                    @else <span class="st-ok">يتسع</span>@endif
                    @if ($c['no_teacher'] > 0)<br><span class="st-warn">{{ $c['no_teacher'] }} حصة لمواد بلا معلم مرتبط</span>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4">لا شعب ولا مواد في قسمك بعد.</td></tr>
        @endforelse
    </table>
</div>

<div class="st-sec st-card">
    <h2>المسودات</h2>
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
            <tr><td colspan="7">لا مسودات بعد.</td></tr>
        @endforelse
    </table>
</div>

<div class="st-sec st-card">
    <h2>القاعات</h2>
    <p style="color:var(--text-secondary);font-size:.85rem">إن لم تُضف قاعات، يكتفي النظام باسم القاعة المسجّل سابقًا بلا فحص سعة. أضف قاعاتك ليتحقق من السعة وتعارض الحجز.</p>
    <form class="st-form" method="POST" action="{{ route('hod.studio.rooms.store') }}" style="margin-bottom:.8rem">
        @csrf
        <div><label>اسم القاعة</label><input name="name" required maxlength="60"></div>
        <div><label>السعة</label><input type="number" name="capacity" required min="1" max="2000" style="width:90px"></div>
        <button class="st-btn">إضافة</button>
    </form>
    <table class="st-table">
        @foreach ($rooms as $r)
            <tr>
                <td>{{ $r->name }} {!! $r->department_id ? '' : '<small>(مشتركة)</small>' !!}</td><td>{{ $r->capacity }} مقعد</td>
                <td>@if ($r->department_id)<form method="POST" action="{{ route('hod.studio.rooms.delete', $r->id) }}">@csrf<button class="st-btn danger">حذف</button></form>@endif</td>
            </tr>
        @endforeach
    </table>
</div>

<div class="st-sec st-card">
    <h2>توفّر المعلمين</h2>
    <p style="color:var(--text-secondary);font-size:.85rem">«غير متاح» قيد صلب لا يضع النظام فيه حصة للمعلم. «يفضّل تجنّبه» قيد ناعم يتجنبه إن أمكن.</p>
    <form class="st-form" method="POST" action="{{ route('hod.studio.availability.store') }}" style="margin-bottom:.8rem">
        @csrf
        <div><label>المعلم</label><select name="teacher_user_id" required>@foreach ($teachers as $t)<option value="{{ $t->user_id }}">{{ $t->full_name }}</option>@endforeach</select></div>
        <div><label>اليوم</label><select name="day">@foreach ($days as $d)<option value="{{ $d }}">{{ $dayLabels[$d] }}</option>@endforeach</select></div>
        <div><label>الفترة</label><select name="period">@for ($i = 1; $i <= $maxPeriods; $i++)<option value="{{ $i }}">{{ $i }} ({{ $periods[$i][0] }})</option>@endfor</select></div>
        <div><label>النوع</label><select name="kind"><option value="unavailable">غير متاح</option><option value="avoid">يفضّل تجنّبه</option></select></div>
        <button class="st-btn">حفظ</button>
    </form>
    <table class="st-table">
        @php $tn = $teachers->pluck('full_name', 'user_id'); @endphp
        @foreach ($avail as $a)
            <tr>
                <td>{{ $tn[$a->teacher_user_id] ?? '—' }}</td><td>{{ $dayLabels[$a->day] ?? $a->day }}</td>
                <td>الفترة {{ $a->period }} ({{ $periods[$a->period][0] ?? '' }})</td>
                <td>{{ $a->kind === 'unavailable' ? 'غير متاح' : 'يفضّل تجنّبه' }}</td>
                <td><form method="POST" action="{{ route('hod.studio.availability.delete', $a->id) }}">@csrf<button class="st-btn danger">حذف</button></form></td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
