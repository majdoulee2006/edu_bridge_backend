@extends($layout)
@section('title', 'لوحة المتابعة')
@section('subtitle', 'درجة خطر أكاديمي لكل طالب من الحضور والواجبات والعلامات والنشاط، مع أسبابها. أداة لترتيب أولوياتك، وليست حكمًا على الطالب.')

@php
    $lvlColor = ['high' => '#c62828', 'medium' => '#e65100', 'low' => '#2e7d32', 'unknown' => '#78909c'];
    $q = fn (array $extra = []) => array_filter(array_merge(request()->only('search', 'program_id', 'sort'), $extra), fn ($v) => $v !== null && $v !== '');
    $tabs = ['' => 'الكل', 'at_risk' => 'معرَّض (عالٍ + متوسط)', 'high' => 'عالٍ', 'medium' => 'متوسط', 'low' => 'منخفض', 'unknown' => 'بيانات غير كافية'];
@endphp

@push('styles')
<style>
    .rk-stats { display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:1rem; margin-bottom:1.25rem; }
    .rk-stat { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem; text-align:center; box-shadow: var(--shadow); }
    .rk-stat b { display:block; font-size:1.8rem; font-weight:800; color: var(--c); }
    .rk-stat small { color: var(--text-secondary); font-size:.8rem; }
    .rk-banner { display:flex; justify-content:space-between; align-items:center; gap:.8rem; flex-wrap:wrap; padding:.7rem 1rem; margin-bottom:1rem; border-radius:.9rem; background: rgba(230,81,0,.12); color:#e65100; font-weight:700; font-size:.88rem; }
    .rk-banner a { color:#e65100; text-decoration:none; font-weight:800; white-space:nowrap; }
    a.rk-stat { display:block; text-decoration:none; cursor:pointer; transition: transform .15s, border-color .15s, box-shadow .15s; }
    a.rk-stat:hover { transform: translateY(-2px); border-color: var(--c); }
    a.rk-stat.active { border-color: var(--c); box-shadow: 0 0 0 2px color-mix(in srgb, var(--c) 35%, transparent); }
    .rk-filter { display:flex; gap:.7rem; flex-wrap:wrap; align-items:flex-end; margin-bottom:1rem; background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:.9rem; }
    .rk-filter label { display:block; font-size:.75rem; color: var(--text-secondary); margin-bottom:.2rem; }
    .rk-filter input, .rk-filter select { padding:.5rem .7rem; border-radius:.7rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; }
    .rk-filter button { padding:.55rem 1.2rem; border-radius:.7rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; }
    .rk-tabs { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.1rem; }
    .rk-tab { padding:.4rem .95rem; border-radius:2rem; font-weight:700; font-size:.82rem; text-decoration:none; color: var(--text-secondary); border:1px solid var(--border-color); background: var(--bg-secondary); }
    .rk-tab.active { background: var(--primary-color); color:#fff; border-color: var(--primary-color); }
    .rk-card { display:block; text-decoration:none; color:inherit; background: var(--bg-secondary); border:1px solid var(--border-color); border-right:5px solid var(--c);
        border-radius:1.1rem; padding:1rem 1.2rem; margin-bottom:.9rem; box-shadow: var(--shadow); transition: transform .15s; }
    .rk-card:hover { transform: translateY(-2px); }
    .rk-top { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
    .rk-name { font-weight:800; color: var(--text-primary); }
    .rk-meta { font-size:.78rem; color: var(--text-secondary); margin-top:.1rem; }
    .rk-score { display:flex; align-items:center; gap:.7rem; min-width: 190px; }
    .rk-bar { flex:1; min-width:90px; height:8px; border-radius:6px; background: rgba(120,120,120,.2); overflow:hidden; }
    .rk-bar i { display:block; height:100%; background: var(--c); border-radius:6px; }
    .rk-num { font-weight:800; font-size:1.1rem; color: var(--c); min-width: 2.6rem; text-align:left; }
    .rk-badge { padding:.2rem .7rem; border-radius:2rem; font-size:.75rem; font-weight:800; color: var(--c); background: color-mix(in srgb, var(--c) 16%, transparent); }
    .rk-chg { font-size:.78rem; font-weight:800; }
    .rk-reasons { margin:.6rem 0 0; padding:0 1.1rem 0 0; font-size:.85rem; color: var(--text-primary); line-height:1.8; }
    .rk-foot { display:flex; gap:.6rem; flex-wrap:wrap; margin-top:.5rem; font-size:.75rem; color: var(--text-secondary); }
    .rk-due { background: rgba(230,81,0,.15); color:#e65100; padding:.1rem .6rem; border-radius:2rem; font-weight:800; }
    .rk-alert { padding:.8rem 1rem; border-radius:.9rem; margin-bottom:1rem; font-weight:700; background: rgba(198,40,40,.12); color:#c62828; }
</style>
@endpush

@section('content')
    @include('followup._tabs', ['active' => 'students'])
    @if(session('error'))<div class="rk-alert">{{ session('error') }}</div>@endif

    @php
        $progFilter = !empty($filters['program_id']);
        $progName = $progFilter ? (\DB::table('programs')->where('id', (int) $filters['program_id'])->value('name') ?? ('#' . $filters['program_id'])) : null;
    @endphp
    {{-- الأرقام تتبع البحث والبرنامج المختارين: نقول ذلك صراحة كي لا يُظن أنها كل طلابك --}}
    @if($progFilter || !empty($filters['search']))
        <div class="rk-banner">
            <span><i class="fa-solid fa-filter"></i> عرض مفلتر، الأرقام أدناه لجزء من طلابك:
                @if($progFilter) البرنامج: {{ $progName }} @endif
                @if(!empty($filters['search'])) بحث: «{{ $filters['search'] }}» @endif
            </span>
            <a href="{{ url('/' . $prefix . '/risk') }}">إزالة الفلاتر ✕</a>
        </div>
    @endif

    @php $curLevel = $filters['level'] ?? ''; $curFollow = $filters['followup'] ?? ''; $base = url('/' . $prefix . '/risk'); @endphp
    {{-- كل مربع رابط: يعرض الطلاب المطابقين له تحت (مع الإبقاء على البحث والبرنامج والترتيب) --}}
    <div class="rk-stats">
        @foreach(['high' => 'خطر عالٍ', 'medium' => 'خطر متوسط', 'low' => 'منخفض', 'unknown' => 'بيانات غير كافية'] as $k => $label)
            <a class="rk-stat {{ $curLevel === $k && !$curFollow ? 'active' : '' }}" style="--c: {{ $lvlColor[$k] }}" href="{{ $base . '?' . http_build_query($q(['level' => $k])) }}#list"><b>{{ $summary[$k] }}</b><small>{{ $label }}</small></a>
        @endforeach
        <a class="rk-stat {{ $curFollow === 'overdue' ? 'active' : '' }}" style="--c: #e65100" href="{{ $base . '?' . http_build_query($q(['followup' => 'overdue'])) }}#list"><b>{{ $summary['overdue'] ?? 0 }}</b><small>متابعات فات موعدها</small></a>
        <a class="rk-stat {{ !$curLevel && !$curFollow ? 'active' : '' }}" style="--c: var(--text-primary)" href="{{ $base . '?' . http_build_query($q()) }}#list"><b>{{ $summary['total'] }}</b><small>إجمالي طلابك (عرض الكل)</small></a>
    </div>

    <form method="GET" action="{{ url('/' . $prefix . '/risk') }}" class="rk-filter">
        <div><label>بحث بالاسم أو الرقم</label><input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="اسم الطالب..."></div>
        @if($programs->count() > 1)
            <div><label>البرنامج</label>
                <select name="program_id"><option value="">الكل</option>
                    @foreach($programs as $p)<option value="{{ $p->id }}" {{ (string)($filters['program_id'] ?? '') === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
                </select></div>
        @endif
        <div><label>الترتيب</label>
            <select name="sort"><option value="score" {{ ($filters['sort'] ?? 'score') === 'score' ? 'selected' : '' }}>الأعلى خطرًا</option><option value="change" {{ ($filters['sort'] ?? '') === 'change' ? 'selected' : '' }}>الأكثر ارتفاعًا</option></select></div>
        @if(!empty($filters['level']))<input type="hidden" name="level" value="{{ $filters['level'] }}">@endif
        <button type="submit">تطبيق</button>
    </form>

    <div class="rk-tabs" id="list">
        @foreach($tabs as $key => $label)
            <a href="{{ url('/' . $prefix . '/risk') . '?' . http_build_query($q(['level' => $key])) }}" class="rk-tab {{ ($filters['level'] ?? '') === $key && !($filters['followup'] ?? '') ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ url('/' . $prefix . '/risk') . '?' . http_build_query($q(['followup' => 'overdue'])) }}" class="rk-tab {{ ($filters['followup'] ?? '') === 'overdue' ? 'active' : '' }}">متابعة فات موعدها ({{ $summary['overdue'] ?? 0 }})</a>
    </div>

    @forelse($rows as $r)
        @php $c = $lvlColor[$r['level']]; @endphp
        <a href="{{ url('/' . $prefix . '/risk/students/' . $r['student']['id']) }}" class="rk-card" style="--c: {{ $c }}">
            <div class="rk-top">
                <div>
                    <div class="rk-name">{{ $r['student']['name'] }}</div>
                    <div class="rk-meta">{{ $r['student']['program'] }} — {{ $r['student']['year'] }} — {{ $r['student']['code'] }}</div>
                </div>
                <div class="rk-score">
                    @if($r['score'] !== null && $r['level'] !== 'unknown')
                        <div class="rk-bar"><i style="width: {{ $r['score'] }}%"></i></div>
                        <span class="rk-num">{{ rtrim(rtrim(number_format($r['score'], 1), '0'), '.') }}</span>
                    @endif
                    <span class="rk-badge">{{ $r['level_label'] }}</span>
                    @if($r['change'] !== null && abs($r['change']) >= 1)
                        <span class="rk-chg" style="color: {{ $r['change'] > 0 ? '#c62828' : '#2e7d32' }}">{{ $r['change'] > 0 ? '▲' : '▼' }} {{ abs($r['change']) }}</span>
                    @endif
                </div>
            </div>
            @if(!empty($r['reasons']))
                <ul class="rk-reasons">@foreach($r['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
            @endif
            <div class="rk-foot">
                @if($r['last_intervention'])<span>آخر إجراء: {{ $r['last_intervention']['type_label'] }} ({{ $r['last_intervention']['date'] }})</span>
                @elseif(in_array($r['level'], ['high', 'medium']))<span>لم تُسجَّل متابعة بعد</span>@endif
                @if($r['follow_up_due'])<span class="rk-due">موعد المتابعة حلّ</span>@endif
            </div>
        </a>
    @empty
        <div style="text-align:center; padding:3.5rem 2rem; background: var(--bg-secondary); border-radius:1.5rem; border:1px dashed var(--border-color);">
            <i class="fa-solid fa-circle-check" style="font-size:3rem; color:#2e7d32; opacity:.6; margin-bottom:1rem; display:block;"></i>
            <h4 style="font-size:1.2rem; font-weight:800;">لا طلاب في هذه القائمة</h4>
            <p style="color: var(--text-secondary); font-size:.9rem;">تُحسب الدرجات ليلًا. الطالب الذي لا تتوفر عنه بيانات كافية يظهر تحت "بيانات غير كافية".</p>
        </div>
    @endforelse

    <div style="margin-top:1.25rem;">{{ $paginator->links() }}</div>
@endsection
