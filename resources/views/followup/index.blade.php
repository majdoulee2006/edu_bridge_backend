@extends($layout)
@section('title', 'لوحة المتابعة')
@section('subtitle', $d['kind'] === 'advisor'
    ? 'طلابك ومقرراتهم: من يحتاج انتباهك اليوم؟'
    : 'قسمك كله: أي الدورات تحتاج اهتمامًا، ومن يتابع طلابه من المرشدين؟')

@php
    $statusMeta = [
        'attention' => ['label' => 'تحتاج اهتمامًا', 'color' => '#c62828'],
        'watch'     => ['label' => 'راقبها',          'color' => '#e65100'],
        'ok'        => ['label' => 'وضعها جيد',       'color' => '#2e7d32'],
        'nodata'    => ['label' => 'بيانات غير كافية', 'color' => '#78909c'],
    ];
    $lvl = ['high' => '#c62828', 'medium' => '#e65100'];
    $k = $d['kpis'];
    $sort = $filters['sort'] ?? 'attention';
    $riskUrl = fn (array $q = []) => url('/' . $prefix . '/risk') . ($q ? '?' . http_build_query($q) : '');
@endphp

@push('styles')
<style>
    .fu-kpis { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:1rem; margin-bottom:1.25rem; }
    .fu-kpi { display:block; text-decoration:none; background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem; text-align:center; box-shadow: var(--shadow); transition: transform .15s; }
    a.fu-kpi:hover { transform: translateY(-2px); border-color: var(--c, var(--border-color)); }
    a.fu-kpi.active { border-color: var(--c); box-shadow: 0 0 0 2px color-mix(in srgb, var(--c) 35%, transparent); }
    html { scroll-behavior: smooth; }
    .fu-box:target { box-shadow: 0 0 0 3px #e65100; }
    .fu-kpi b { display:block; font-size:1.8rem; font-weight:800; color: var(--c, var(--text-primary)); }
    .fu-kpi small { color: var(--text-secondary); font-size:.8rem; }
    .fu-filter { display:flex; gap:.7rem; flex-wrap:wrap; align-items:flex-end; margin-bottom:1.2rem; background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:.9rem; }
    .fu-filter label { display:block; font-size:.75rem; color: var(--text-secondary); margin-bottom:.2rem; }
    .fu-filter input, .fu-filter select { padding:.5rem .7rem; border-radius:.7rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; }
    .fu-filter button { padding:.55rem 1.2rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; }
    .fu-h { font-size:1.05rem; font-weight:800; color: var(--text-primary); margin:1.4rem 0 .7rem; display:flex; align-items:center; gap:.6rem; }
    .fu-h .n { font-size:.78rem; padding:.1rem .65rem; border-radius:2rem; color:#fff; background: var(--c, #78909c); }
    .fu-cols { display:grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap:1rem; }
    .fu-box { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem 1.1rem; box-shadow: var(--shadow); }
    .fu-box h4 { margin:0 0 .7rem; font-size:.95rem; font-weight:800; color: var(--text-primary); }
    .fu-row { display:flex; justify-content:space-between; gap:.6rem; padding:.45rem 0; border-top:1px dashed var(--border-color); font-size:.85rem; }
    .fu-row:first-of-type { border-top:none; }
    .fu-row a { color: var(--text-primary); font-weight:700; text-decoration:none; }
    .fu-row small { color: var(--text-secondary); display:block; font-size:.74rem; }
    .fu-empty { color: var(--text-secondary); font-size:.85rem; padding:.4rem 0; }
    .fu-course { background: var(--bg-secondary); border:1px solid var(--border-color); border-right:5px solid var(--c); border-radius:1.1rem; padding:1rem 1.2rem; margin-bottom:.8rem; box-shadow: var(--shadow); }
    .fu-ctop { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
    .fu-cname { font-weight:800; color: var(--text-primary); }
    .fu-cmeta { font-size:.76rem; color: var(--text-secondary); margin-top:.15rem; }
    .fu-badge { padding:.2rem .75rem; border-radius:2rem; font-size:.75rem; font-weight:800; color: var(--c); background: color-mix(in srgb, var(--c) 16%, transparent); white-space:nowrap; }
    .fu-metrics { display:flex; gap:1.4rem; flex-wrap:wrap; align-items:center; margin-top:.7rem; font-size:.83rem; color: var(--text-primary); }
    .fu-att { display:flex; align-items:center; gap:.6rem; min-width:210px; }
    .fu-bar { flex:1; min-width:90px; height:8px; border-radius:6px; background: rgba(120,120,120,.2); overflow:hidden; }
    .fu-bar i { display:block; height:100%; border-radius:6px; }
    .fu-chip { padding:.12rem .65rem; border-radius:2rem; font-size:.74rem; font-weight:700; background: rgba(120,120,120,.14); color: var(--text-primary); }
    .fu-reasons { display:flex; gap:.4rem; flex-wrap:wrap; margin-top:.65rem; }
    .fu-prog { background: var(--bg-secondary); border:1px solid var(--border-color); border-right:6px solid var(--c); border-radius:1.2rem; margin-bottom:1rem; box-shadow: var(--shadow); overflow:hidden; }
    .fu-prog > summary { list-style:none; cursor:pointer; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; padding:1rem 1.2rem; }
    .fu-prog > summary::-webkit-details-marker { display:none; }
    .fu-prog[open] > summary { border-bottom:1px solid var(--border-color); }
    .fu-psum { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
    .fu-prog .fu-course { margin:.8rem; }
    .fu-table { width:100%; border-collapse:collapse; font-size:.85rem; }
    .fu-table th, .fu-table td { padding:.6rem .5rem; text-align:right; border-bottom:1px solid var(--border-color); }
    .fu-table th { color: var(--text-secondary); font-size:.76rem; font-weight:700; }
    .fu-nudge { color:#c62828; background: rgba(198,40,40,.12); padding:.1rem .6rem; border-radius:2rem; font-weight:800; font-size:.74rem; }
    .fu-alert { padding:.8rem 1rem; border-radius:.9rem; margin-bottom:1rem; font-weight:700; background: rgba(198,40,40,.12); color:#c62828; }
    .fu-note { font-size:.76rem; color: var(--text-secondary); margin-top:1.2rem; }
</style>
@endpush

@section('content')
    @if(session('error'))<div class="fu-alert">{{ session('error') }}</div>@endif

    {{-- كل مربع رابط إلى مكان بياناته: قائمة الطلاب المطابقة، أو القسم المناسب في هذه الصفحة --}}
    @php $pendingOn = !empty($filters['pending']); @endphp
    <div class="fu-kpis">
        <a class="fu-kpi" style="--c: {{ $lvl['high'] }}" href="{{ $riskUrl(['level' => 'high']) }}"><b>{{ $k['high'] }}</b><small>خطر عالٍ</small></a>
        <a class="fu-kpi" style="--c: {{ $lvl['medium'] }}" href="{{ $riskUrl(['level' => 'medium']) }}"><b>{{ $k['medium'] }}</b><small>خطر متوسط</small></a>
        <a class="fu-kpi" style="--c: #c62828" href="{{ $riskUrl(['sort' => 'change', 'level' => 'at_risk']) }}"><b>{{ $k['rising_week'] }}</b><small>ارتفع خطرهم هذا الأسبوع</small></a>
        <a class="fu-kpi" style="--c: {{ $k['overdue_followups'] ? '#e65100' : '#2e7d32' }}" href="{{ $riskUrl(['followup' => 'overdue']) }}"><b>{{ $k['overdue_followups'] }}</b><small>متابعات فات موعدها</small></a>
        <a class="fu-kpi" style="--c: {{ $k['clock_alerts_escalated'] ? '#c62828' : '#2e7d32' }}" href="#clock-alerts"><b>{{ $k['clock_alerts_escalated'] }}</b><small>تلاعب بساعة الهاتف بلا رد</small></a>
        <a class="fu-kpi {{ $pendingOn ? 'active' : '' }}" style="--c: {{ $k['pending_sessions'] ? '#e65100' : '#2e7d32' }}" href="{{ url('/' . $prefix . '/follow-up') . '?' . http_build_query(array_merge(request()->only('program_id', 'year', 'sort', 'days'), $pendingOn ? [] : ['pending' => 1])) }}#programs"><b>{{ $k['pending_sessions'] }}</b><small>جلسات اليوم بانتظار الإغلاق (12 ليلاً)</small></a>
        <a class="fu-kpi" href="{{ $riskUrl() }}"><b>{{ $k['students'] }}</b><small>إجمالي الطلاب (عرض الكل)</small></a>
    </div>

    <form method="GET" action="{{ url('/' . $prefix . '/follow-up') }}" class="fu-filter">
        <div><label>بحث في الدورات</label><input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="اسم الدورة أو رمزها..."></div>
        @if(count($d['filters']['programs']) > 1)
            <div><label>البرنامج</label>
                <select name="program_id"><option value="">الكل</option>
                    @foreach($d['filters']['programs'] as $p)<option value="{{ $p['id'] }}" {{ (string)($filters['program_id'] ?? '') === (string)$p['id'] ? 'selected' : '' }}>{{ $p['name'] }}</option>@endforeach
                </select></div>
        @endif
        <div><label>السنة</label>
            <select name="year"><option value="">الكل</option>
                @foreach([1 => 'الأولى', 2 => 'الثانية', 3 => 'الثالثة', 4 => 'الرابعة'] as $y => $yl)<option value="{{ $y }}" {{ (string)($filters['year'] ?? '') === (string)$y ? 'selected' : '' }}>{{ $yl }}</option>@endforeach
            </select></div>
        <div><label>الحالة</label>
            <select name="status"><option value="">الكل</option>
                @foreach($statusMeta as $sk => $sm)<option value="{{ $sk }}" {{ ($filters['status'] ?? '') === $sk ? 'selected' : '' }}>{{ $sm['label'] }}</option>@endforeach
            </select></div>
        <div><label>الترتيب</label>
            <select name="sort">
                @foreach(['attention' => 'الأحوج للاهتمام أولاً', 'attendance' => 'الحضور الأقل أولاً', 'risk' => 'الأكثر معرَّضين', 'name' => 'الاسم'] as $sk => $sl)<option value="{{ $sk }}" {{ $sort === $sk ? 'selected' : '' }}>{{ $sl }}</option>@endforeach
            </select></div>
        <div><label>فترة الحضور</label>
            <select name="days">
                @foreach([14 => '14 يومًا', 30 => '30 يومًا', 60 => '60 يومًا', 90 => '90 يومًا'] as $dv => $dl)<option value="{{ $dv }}" {{ (int)$d['period_days'] === $dv ? 'selected' : '' }}>{{ $dl }}</option>@endforeach
            </select></div>
        <button type="submit">تطبيق</button>
    </form>

    {{-- عليك اليوم --}}
    <div class="fu-h">عليك اليوم</div>
    <div class="fu-cols">
        <div class="fu-box">
            <h4>الأعلى خطرًا</h4>
            @forelse($d['today']['top_risk'] as $s)
                <div class="fu-row">
                    <div><a href="{{ url('/' . $prefix . '/risk/students/' . $s['student_id']) }}">{{ $s['name'] }}</a>
                        <small>{{ $s['reasons'][0] ?? ($s['program'] ?? '') }}</small></div>
                    <div style="text-align:left"><b style="color: {{ $lvl[$s['level']] ?? '#78909c' }}">{{ rtrim(rtrim(number_format($s['score'], 1), '0'), '.') }}</b>
                        @if($s['change'] !== null && $s['change'] != 0)<small>{{ $s['change'] > 0 ? '▲' : '▼' }} {{ abs($s['change']) }}</small>@endif</div>
                </div>
            @empty <div class="fu-empty">لا يوجد طلاب بخطر عالٍ أو متوسط.</div> @endforelse
        </div>
        <div class="fu-box">
            <h4>متابعات فات موعدها</h4>
            @forelse($d['today']['overdue'] as $s)
                <div class="fu-row">
                    <div><a href="{{ url('/' . $prefix . '/risk/students/' . $s['student_id']) }}">{{ $s['name'] }}</a>
                        <small>آخر إجراء: {{ $s['last_action'] }}</small></div>
                    <div style="text-align:left"><b style="color:#e65100">{{ $s['days_late'] }}</b><small>يوم تأخير</small></div>
                </div>
            @empty <div class="fu-empty">لا متابعات متأخرة. 👌</div> @endforelse
        </div>
        <div class="fu-box" id="clock-alerts">
            <h4>تلاعب بساعة الهاتف دون رد</h4>
            @forelse($d['today']['clock_alerts'] as $s)
                <div class="fu-row">
                    <div><a href="{{ url('/' . $prefix . '/risk/students/' . $s['student_id']) }}">{{ $s['name'] }}</a>
                        <small>{{ $s['count'] }} تنبيهات ولم يردّ الطالب</small></div>
                </div>
            @empty <div class="fu-empty">لا حالات.</div> @endforelse
        </div>
    </div>

    {{-- البرامج (الدورات) ثم مقرراتها --}}
    <div class="fu-h" id="programs">البرامج ومقرراتها <span class="n" style="--c:#546e7a">{{ count($d['programs']) }}</span>
        @if($pendingOn)<a href="{{ url('/' . $prefix . '/follow-up') }}#programs" class="fu-chip" style="text-decoration:none">⏳ جلسات معلّقة فقط ✕</a>@endif
    </div>
    @if(!count($d['programs']))
        <div class="fu-box"><div class="fu-empty">لا توجد دورات مطابقة. جرّب تغيير الفلاتر.</div></div>
    @endif

    @foreach($d['programs'] as $p)
        @php $pm = $statusMeta[$p['status']]; $pr = $p['attendance_rate']; @endphp
        <details class="fu-prog" style="--c: {{ $pm['color'] }}" {{ $p['status'] !== 'ok' || count($d['programs']) === 1 ? 'open' : '' }}>
            <summary>
                <div>
                    <div class="fu-cname" style="font-size:1.02rem">{{ $p['name'] }}</div>
                    <div class="fu-cmeta">
                        @if($p['department']){{ $p['department'] }} · @endif
                        {{ $p['students'] }} طالب · {{ count($p['courses']) }} مقرر
                        @if($pr !== null) · الحضور {{ rtrim(rtrim(number_format($pr, 1), '0'), '.') }}%@endif
                    </div>
                </div>
                <div class="fu-psum">
                    @if($p['high'])<span class="fu-chip" style="color:{{ $lvl['high'] }}">{{ $p['high'] }} عالٍ</span>@endif
                    @if($p['medium'])<span class="fu-chip" style="color:{{ $lvl['medium'] }}">{{ $p['medium'] }} متوسط</span>@endif
                    <span class="fu-badge">{{ $pm['label'] }}</span>
                </div>
            </summary>

            @forelse($p['courses'] as $c)
                @php
                    $sm = $statusMeta[$c['status']];
                    $rate = $c['attendance_rate'];
                    $barColor = $rate === null ? '#78909c' : ($rate >= 90 ? '#2e7d32' : ($rate >= 70 ? '#e65100' : '#c62828'));
                @endphp
                <div class="fu-course" style="--c: {{ $sm['color'] }}">
                    <div class="fu-ctop">
                        <div>
                            <div class="fu-cname">{{ $c['title'] }} @if($c['code'])<small style="color:var(--text-secondary)">({{ $c['code'] }})</small>@endif</div>
                            <div class="fu-cmeta">
                                @if($c['year'])السنة {{ $c['year'] }}@endif
                                @if($c['teachers']) · د. {{ implode('، ', array_slice($c['teachers'], 0, 2)) }}@endif
                            </div>
                        </div>
                        <span class="fu-badge">{{ $sm['label'] }}</span>
                    </div>
                    <div class="fu-metrics">
                        <div class="fu-att">
                            @if($rate !== null)
                                <span>الحضور</span>
                                <div class="fu-bar"><i style="width: {{ $rate }}%; background: {{ $barColor }}"></i></div>
                                <b style="color: {{ $barColor }}">{{ rtrim(rtrim(number_format($rate, 1), '0'), '.') }}%</b>
                            @else
                                <span style="color:var(--text-secondary)">الحضور: بيانات غير كافية</span>
                            @endif
                        </div>
                        <span>{{ $c['enrolled'] }} طالب</span>
                        @if($c['high'])<span class="fu-chip" style="color:{{ $lvl['high'] }}">{{ $c['high'] }} خطر عالٍ</span>@endif
                        @if($c['medium'])<span class="fu-chip" style="color:{{ $lvl['medium'] }}">{{ $c['medium'] }} متوسط</span>@endif
                        @if($c['pending_sessions'])<span class="fu-chip">⏳ {{ $c['pending_sessions'] }} جلسة بانتظار الإغلاق</span>@endif
                    </div>
                    @if($c['reasons'])
                        <div class="fu-reasons">@foreach($c['reasons'] as $r)<span class="fu-chip">{{ $r }}</span>@endforeach</div>
                    @endif
                </div>
            @empty
                <div class="fu-empty" style="padding:.8rem 1.2rem">لا مقررات لطلاب هذا البرنامج.</div>
            @endforelse
        </details>
    @endforeach

    {{-- المرشدون (رئيس القسم) --}}
    @if(count($d['advisors']))
        <div class="fu-h">المرشدون ومتابعتهم لطلابهم</div>
        <div class="fu-box" style="overflow-x:auto">
            <table class="fu-table">
                <thead><tr><th>المرشد</th><th>الفرع / السنة</th><th>الطلاب</th><th>عالٍ</th><th>متوسط</th><th>متابعات متأخرة</th><th>إجراءات آخر {{ $d['advisors'][0]['idle_days'] }} يومًا</th><th></th></tr></thead>
                <tbody>
                @foreach($d['advisors'] as $a)
                    <tr>
                        <td><b>{{ $a['name'] }}</b></td>
                        <td>{{ $a['branch'] }} · {{ $a['year'] }}</td>
                        <td>{{ $a['students'] }}</td>
                        <td style="color:{{ $a['high'] ? $lvl['high'] : 'inherit' }}">{{ $a['high'] }}</td>
                        <td style="color:{{ $a['medium'] ? $lvl['medium'] : 'inherit' }}">{{ $a['medium'] }}</td>
                        <td>{{ $a['overdue_followups'] }}</td>
                        <td>{{ $a['actions_recent'] }}</td>
                        <td>@if($a['needs_nudge'])<span class="fu-nudge">لم يتابع المعرَّضين</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="fu-note">
        درجات الخطر تُحسب ليلاً@if($d['computed_on']) (آخر تحديث: {{ \Carbon\Carbon::parse($d['computed_on'])->format('Y-m-d') }})@endif.
        نسبة حضور الدورة = (حاضر + متأخر) ÷ كل السجلات خلال آخر {{ $d['period_days'] }} يومًا، بدون الغياب المعذور، ولطلابك فقط.
        درجة الاهتمام تجمع الحضور ونسبة المعرَّضين، وهي لترتيب أولوياتك وليست حكمًا على الدورة أو معلمها.
    </p>
@endsection
