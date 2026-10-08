@extends('layouts.hod')
@section('title', 'تحليل الحضور')

@php
    $overall = $data['overall'];
    $hm = $data['heatmap'];
    $cellMap = [];
    foreach ($hm['cells'] as $c) { $cellMap[$c['day']][$c['slot']] = $c; }
    // تدرّج موسّع: 40% وما دون = أحمر، 100% = أخضر (كي يظهر الضعف بوضوح بدل تقارب الألوان)
    $color = fn ($rate) => 'hsl(' . (int) round(max(0, min(1, ($rate - 40) / 60)) * 120) . ', 62%, 38%)';
    $weeklyMax = max(1, collect($data['weekly'])->max('rate') ?? 100);
@endphp

@push('styles')
<style>
    .ai-filter { display:flex; gap:.75rem; flex-wrap:wrap; align-items:flex-end; margin-bottom:1.25rem;
        background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem; }
    .ai-filter label { display:block; font-size:.75rem; color: var(--text-secondary); margin-bottom:.25rem; }
    .ai-filter select, .ai-filter input { padding:.5rem .7rem; border-radius:.7rem; border:1px solid var(--border-color);
        background: transparent; color: var(--text-primary); font-family:inherit; }
    .ai-filter button { padding:.55rem 1.2rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; }
    .ai-stats { display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:1.25rem; }
    .ai-stat { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem; text-align:center; box-shadow: var(--shadow); }
    .ai-stat b { display:block; font-size:1.8rem; font-weight:800; color: var(--text-primary); }
    .ai-stat small { color: var(--text-secondary); font-size:.8rem; }
    .ai-card { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.25rem; padding:1.25rem; margin-bottom:1.25rem; box-shadow: var(--shadow); }
    .ai-card h3 { font-size:1.05rem; font-weight:800; margin-bottom:.9rem; color: var(--text-primary); }
    .ai-hint { font-size:.78rem; color: var(--text-secondary); margin-top:.6rem; }
    .hm-wrap { overflow-x:auto; }
    table.hm { border-collapse: separate; border-spacing: 5px; min-width: 420px; width:100%; }
    table.hm th { font-size:.78rem; color: var(--text-secondary); font-weight:700; padding:.3rem; }
    table.hm td { border-radius:.6rem; text-align:center; color:#fff; font-weight:800; padding:.7rem .3rem; font-size:.95rem; }
    table.hm td small { display:block; font-weight:500; font-size:.65rem; opacity:.85; }
    table.hm td.empty { background: rgba(120,120,120,.12); color: var(--text-secondary); }
    .ai-grid { display:grid; grid-template-columns: 1fr; gap:1.25rem; }
    @media(min-width: 992px) { .ai-grid { grid-template-columns: 1fr 1fr; } }
    .ai-row { display:flex; align-items:center; gap:.7rem; padding:.55rem 0; border-bottom:1px solid var(--border-color); font-size:.9rem; }
    .ai-row:last-child { border-bottom:none; }
    .ai-row .name { flex:1; min-width:0; color: var(--text-primary); }
    .ai-row .name small { display:block; color: var(--text-secondary); font-size:.75rem; }
    .ai-pill { padding:.2rem .65rem; border-radius:2rem; font-weight:800; font-size:.8rem; color:#fff; }
    .bars { display:flex; align-items:flex-end; gap:.5rem; height:120px; }
    .bar { flex:1; border-radius:.5rem .5rem 0 0; position:relative; min-height:4px; }
    .bar span { position:absolute; top:-1.35rem; left:0; right:0; text-align:center; font-size:.72rem; font-weight:800; color: var(--text-primary); }
    .bar-label { text-align:center; font-size:.65rem; color: var(--text-secondary); margin-top:.3rem; }
    .sev-dot { width:10px; height:10px; border-radius:50%; flex:none; }
    .legend { display:flex; gap:.4rem; align-items:center; font-size:.72rem; color: var(--text-secondary); margin-top:.7rem; }
    .legend i { display:inline-block; width:28px; height:10px; border-radius:3px; }
</style>
@endpush

@section('content')
    <form method="GET" action="{{ route('hod.attendance_insights') }}" class="ai-filter">
        <div><label>البرنامج</label>
            <select name="program_id">
                <option value="">كل البرامج</option>
                @foreach($programs as $p)
                    <option value="{{ $p->id }}" {{ (string) $programId === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label>من</label><input type="date" name="from" value="{{ $from }}"></div>
        <div><label>إلى</label><input type="date" name="to" value="{{ $to }}"></div>
        <button type="submit">تطبيق</button>
    </form>

    <div class="ai-stats">
        <div class="ai-stat"><b>{{ $overall['rate'] !== null ? $overall['rate'] . '%' : '—' }}</b><small>معدّل الحضور العام</small></div>
        <div class="ai-stat"><b>{{ number_format($overall['total']) }}</b><small>سجلات محتسبة</small></div>
        <div class="ai-stat"><b>{{ $data['flags']['open'] }}</b><small>اشتباهات مفتوحة</small></div>
        <div class="ai-stat"><b>{{ count($data['behavior_changes']) }}</b><small>طلاب تغيّر حضورهم</small></div>
    </div>

    <div class="ai-card">
        <h3><i class="fa-solid fa-table-cells"></i> خريطة الحضور (اليوم × وقت الحصة)</h3>
        @if(empty($hm['cells']))
            <p class="ai-hint">لا توجد بيانات حضور في هذه الفترة.</p>
        @else
            <div class="hm-wrap">
                <table class="hm">
                    <thead><tr><th></th>@foreach($hm['days'] as $d)<th>{{ $d['label'] }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($hm['slots'] as $slot)
                            <tr>
                                <th>{{ $slot }}</th>
                                @foreach($hm['days'] as $d)
                                    @php $c = $cellMap[$d['key']][$slot] ?? null; @endphp
                                    @if($c && $c['rate'] !== null)
                                        <td style="background: {{ $color($c['rate']) }};" title="{{ $c['attended'] }} من {{ $c['total'] }}">
                                            {{ $c['rate'] }}%<small>{{ $c['total'] }} سجل</small>
                                        </td>
                                    @else
                                        <td class="empty">—</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="legend">أقل حضورًا <i style="background: linear-gradient(90deg, {{ $color(0) }}, {{ $color(50) }}, {{ $color(100) }});"></i> أعلى حضورًا</div>
            <p class="ai-hint">وقت الحصة مأخوذ من الجدول الدراسي للمقرر. الغياب المعذور (إجازة/عذر معتمد) لا يُحتسب ضد الحضور.</p>
        @endif
    </div>

    <div class="ai-grid">
        <div class="ai-card">
            <h3><i class="fa-solid fa-arrow-trend-down"></i> أضعف المقررات حضورًا</h3>
            @forelse($data['courses'] as $c)
                <div class="ai-row">
                    <div class="name">{{ $c['title'] }}<small>{{ $c['attended'] }} من {{ $c['total'] }} سجل</small></div>
                    <span class="ai-pill" style="background: {{ $color($c['rate']) }};">{{ $c['rate'] }}%</span>
                </div>
            @empty
                <p class="ai-hint">لا توجد بيانات كافية للترتيب.</p>
            @endforelse
        </div>

        <div class="ai-card">
            <h3><i class="fa-solid fa-calendar-xmark"></i> أضعف المحاضرات حضورًا</h3>
            @forelse($data['lessons'] as $l)
                <div class="ai-row">
                    <div class="name">{{ $l['course'] }}<small>{{ $l['date'] }} — {{ $l['total'] }} سجل</small></div>
                    <span class="ai-pill" style="background: {{ $color($l['rate']) }};">{{ $l['rate'] }}%</span>
                </div>
            @empty
                <p class="ai-hint">لا توجد بيانات كافية للترتيب.</p>
            @endforelse
        </div>

        <div class="ai-card">
            <h3><i class="fa-solid fa-users"></i> مقارنة البرامج / الدفعات</h3>
            @forelse($data['programs'] as $p)
                <div class="ai-row">
                    <div class="name">{{ $p['name'] }}<small>{{ $p['total'] }} سجل</small></div>
                    <span class="ai-pill" style="background: {{ $color($p['rate']) }};">{{ $p['rate'] }}%</span>
                </div>
            @empty
                <p class="ai-hint">لا توجد بيانات كافية للمقارنة.</p>
            @endforelse
        </div>

        <div class="ai-card">
            <h3><i class="fa-solid fa-chart-column"></i> الاتجاه الأسبوعي</h3>
            @if(empty($data['weekly']))
                <p class="ai-hint">لا توجد بيانات.</p>
            @else
                <div class="bars">
                    @foreach($data['weekly'] as $w)
                        <div style="flex:1;">
                            <div class="bar" style="height: {{ max(4, ($w['rate'] ?? 0) / 100 * 110) }}px; background: {{ $color($w['rate'] ?? 0) }};"><span>{{ $w['rate'] }}%</span></div>
                            <div class="bar-label">{{ \Carbon\Carbon::parse($w['week_start'])->format('m-d') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="ai-card">
        <h3><i class="fa-solid fa-user-clock"></i> طلاب تغيّر حضورهم فجأة</h3>
        @forelse($data['behavior_changes'] as $b)
            <div class="ai-row">
                <div class="name">{{ $b['name'] }}<small>كان {{ $b['baseline_rate'] }}% ثم هبط إلى {{ $b['recent_rate'] }}% في آخر أسبوعين</small></div>
                <span class="ai-pill" style="background: {{ $color($b['recent_rate']) }};">{{ $b['recent_rate'] }}%</span>
            </div>
        @empty
            <p class="ai-hint">لا يوجد طالب انخفض حضوره بشكل مفاجئ.</p>
        @endforelse
        <p class="ai-hint">إشارة مبكرة للمرشد وليست اتهامًا: حضور ممتاز في الأسابيع الأربعة السابقة ثم انخفاض حاد في آخر أسبوعين.</p>
    </div>

    <div class="ai-card">
        <h3><i class="fa-solid fa-magnifying-glass-chart"></i> اشتباهات الحضور المفتوحة في قسمك</h3>
        @forelse($openFlags as $f)
            @php $sevColor = ['high' => '#c62828', 'medium' => '#e65100', 'low' => '#607d8b'][$f->severity] ?? '#607d8b'; @endphp
            <div class="ai-row">
                <span class="sev-dot" style="background: {{ $sevColor }};"></span>
                <div class="name">{{ $f->student->user->full_name ?? '' }} — {{ \App\Services\AttendanceIntelligence\FlagPresenter::label($f->rule) }}
                    <small>{{ $f->course->title ?? '' }}</small></div>
            </div>
        @empty
            <p class="ai-hint">لا توجد اشتباهات مفتوحة.</p>
        @endforelse
        <p class="ai-hint">للاطلاع فقط: المراجعة والقرار عند معلّم المادة. المجموع في الفترة: {{ $data['flags']['total'] }} (مفتوحة {{ $data['flags']['open'] }}، مؤكَّدة {{ $data['flags']['confirmed'] }}، مرفوضة {{ $data['flags']['dismissed'] }}).</p>
    </div>
@endsection
