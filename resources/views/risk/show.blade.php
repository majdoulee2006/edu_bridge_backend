@extends($layout)
@section('title', $d['student']['name'])
@section('subtitle', $d['student']['program'] . ' — ' . $d['student']['year'] . ' — ' . $d['student']['code'])

@php
    $lvlColor = ['high' => '#c62828', 'medium' => '#e65100', 'low' => '#2e7d32', 'unknown' => '#78909c'];
    $risk = $d['risk'];
    $c = $lvlColor[$risk['level'] ?? 'unknown'];
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');

    // رسم الاتجاه: نقاط (x,y) في مساحة 300×80 (الدرجة 0 في الأسفل و100 في الأعلى)
    $pts = collect($d['history']);
    $poly = '';
    if ($pts->count() >= 2) {
        $n = $pts->count();
        $poly = $pts->values()->map(fn ($p, $i) => round($i / ($n - 1) * 290 + 5, 1) . ',' . round(75 - ($p['score'] / 100) * 70, 1))->implode(' ');
    }
@endphp

@push('styles')
<style>
    .rs-grid { display:grid; grid-template-columns: 1fr; gap:1.25rem; }
    @media(min-width: 992px) { .rs-grid { grid-template-columns: 1.3fr 1fr; } }
    .rs-card { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.25rem; padding:1.25rem; box-shadow: var(--shadow); margin-bottom:1.25rem; }
    .rs-card h3 { font-size:1.02rem; font-weight:800; margin-bottom:.9rem; color: var(--text-primary); }
    .rs-hero { display:flex; align-items:center; gap:1.2rem; flex-wrap:wrap; }
    .rs-dial { width:96px; height:96px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex:none;
        background: conic-gradient(var(--c) calc(var(--p) * 1%), rgba(120,120,120,.2) 0); position:relative; }
    .rs-dial::after { content:''; position:absolute; inset:9px; background: var(--bg-secondary); border-radius:50%; }
    .rs-dial b { position:relative; z-index:1; font-size:1.5rem; font-weight:800; color: var(--c); }
    .rs-badge { padding:.25rem .9rem; border-radius:2rem; font-weight:800; color: var(--c); background: color-mix(in srgb, var(--c) 16%, transparent); }
    .rs-reasons { margin:.6rem 0 0; padding:0 1.1rem 0 0; line-height:1.9; color: var(--text-primary); }
    .rs-note { font-size:.78rem; color: var(--text-secondary); margin-top:.7rem; line-height:1.7; }
    .fc-row { padding:.7rem 0; border-bottom:1px solid var(--border-color); }
    .fc-row:last-child { border-bottom:none; }
    .fc-head { display:flex; justify-content:space-between; gap:1rem; font-size:.9rem; font-weight:700; color: var(--text-primary); }
    .fc-head small { color: var(--text-secondary); font-weight:500; }
    .fc-bar { height:7px; border-radius:5px; background: rgba(120,120,120,.2); margin:.4rem 0; overflow:hidden; }
    .fc-bar i { display:block; height:100%; border-radius:5px; }
    .fc-reason { font-size:.82rem; color: var(--text-secondary); }
    .fc-na { opacity:.55; }
    .iv { padding:.75rem 0; border-bottom:1px solid var(--border-color); font-size:.88rem; }
    .iv:last-child { border-bottom:none; }
    .iv b { color: var(--text-primary); } .iv small { color: var(--text-secondary); display:block; margin-top:.15rem; }
    .rs-form label { display:block; font-size:.78rem; color: var(--text-secondary); margin:.6rem 0 .2rem; }
    .rs-form select, .rs-form textarea, .rs-form input { width:100%; padding:.55rem .7rem; border-radius:.7rem; border:1px solid var(--border-color); background:transparent; color: var(--text-primary); font-family:inherit; }
    .rs-form button { margin-top:.8rem; padding:.6rem 1.3rem; border-radius:.8rem; border:none; background: var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-family:inherit; }
    .rs-delta { display:inline-block; padding:.2rem .7rem; border-radius:2rem; font-weight:800; font-size:.8rem; }
    .rs-alert { padding:.8rem 1rem; border-radius:.9rem; margin-bottom:1rem; font-weight:700; background: rgba(46,125,50,.12); color:#2e7d32; }
    .rs-back { display:inline-block; margin-bottom:1rem; color: var(--primary-color); font-weight:700; text-decoration:none; }
</style>
@endpush

@section('content')
    <a href="{{ url('/' . $prefix . '/risk') }}" class="rs-back"><i class="fa-solid fa-arrow-right"></i> رجوع للقائمة</a>
    @if(session('success'))<div class="rs-alert">{{ session('success') }}</div>@endif

    <div class="rs-grid">
        <div>
            <div class="rs-card" style="--c: {{ $c }}">
                @if($risk)
                    <div class="rs-hero">
                        @if($risk['level'] !== 'unknown')
                            <div class="rs-dial" style="--p: {{ $risk['score'] }}"><b>{{ $fmt($risk['score']) }}</b></div>
                        @endif
                        <div>
                            <span class="rs-badge">{{ $risk['level_label'] }}</span>
                            @if($risk['previous_score'] !== null)
                                @php $delta = round($risk['score'] - $risk['previous_score'], 1); @endphp
                                @if(abs($delta) >= 1)
                                    <span class="rs-delta" style="color: {{ $delta > 0 ? '#c62828' : '#2e7d32' }}">{{ $delta > 0 ? '▲' : '▼' }} {{ abs($delta) }} عن الحساب السابق</span>
                                @endif
                            @endif
                            <div class="rs-note">آخر حساب: {{ $risk['computed_on'] }} — اكتمال البيانات {{ (int) round($risk['confidence'] * 100) }}%</div>
                        </div>
                    </div>
                    @if(!empty($risk['reasons']))
                        <ul class="rs-reasons">@foreach($risk['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                    @elseif($risk['level'] === 'unknown')
                        <p class="rs-note">لا تتوفر بيانات كافية لتقدير الخطر بعد (حضور أو واجبات أو علامات). هذا لا يعني أن الطالب بخير.</p>
                    @else
                        <p class="rs-note">لا مؤشرات مقلقة حاليًا.</p>
                    @endif
                    @if($risk['confidence'] < 0.7 && $risk['level'] !== 'unknown')
                        <p class="rs-note">⚠️ بيانات جزئية: بعض العوامل غير متوفرة، فالدرجة تقدير أولي.</p>
                    @endif
                @else
                    <p class="rs-note">لم تُحسب درجة لهذا الطالب بعد (تُحسب ليلًا).</p>
                @endif
            </div>

            @if($risk)
            <div class="rs-card">
                <h3><i class="fa-solid fa-scale-balanced"></i> كيف حُسبت الدرجة</h3>
                @foreach($risk['factors'] as $f)
                    <div class="fc-row {{ $f['available'] ? '' : 'fc-na' }}">
                        <div class="fc-head"><span>{{ $f['label'] }} <small>(وزنه {{ $f['weight'] }})</small>
                            @if(!empty($f['critical']))<span class="rs-delta" style="color:#c62828; background: rgba(198,40,40,.14);">حالة حرجة</span>@endif</span>
                            <span dir="ltr">{{ $f['available'] ? $fmt($f['score']) . ' / 100' : '—' }}</span></div>
                        @if($f['available'])
                            <div class="fc-bar"><i style="width: {{ $f['score'] }}%; background: {{ $f['score'] >= 60 ? '#c62828' : ($f['score'] >= 35 ? '#e65100' : '#2e7d32') }}"></i></div>
                        @endif
                        <div class="fc-reason">{{ $f['reason'] }}@if($f['available']) — مساهمته في الدرجة {{ $fmt($f['contribution']) }} @endif</div>
                    </div>
                @endforeach
                <p class="rs-note">إذا انهار عامل رئيسي كليًا (95 نقطة فأكثر) تُرفع الدرجة إلى 60 على الأقل ويُوسَم «حالة حرجة»، كي لا يضيع في متوسط يخففه تفوق الطالب في الباقي.</p>
                <p class="rs-note">الدرجة = مجموع (نقاط كل عامل × وزنه) مقسومًا على أوزان العوامل المتوفرة فقط، فلا يُعاقَب الطالب على بيانات غير موجودة. هذه أداة لترتيب الأولويات وليست حكمًا.</p>
            </div>

            <div class="rs-card">
                <h3><i class="fa-solid fa-chart-line"></i> الاتجاه (آخر 12 أسبوعًا)</h3>
                @if($poly)
                    <svg viewBox="0 0 300 80" style="width:100%; height:110px;" preserveAspectRatio="none">
                        <line x1="5" x2="295" y1="{{ 75 - 0.6 * 70 }}" y2="{{ 75 - 0.6 * 70 }}" stroke="#c62828" stroke-dasharray="3 3" stroke-opacity=".5"/>
                        <line x1="5" x2="295" y1="{{ 75 - 0.35 * 70 }}" y2="{{ 75 - 0.35 * 70 }}" stroke="#e65100" stroke-dasharray="3 3" stroke-opacity=".5"/>
                        <polyline points="{{ $poly }}" fill="none" stroke="{{ $c }}" stroke-width="2.5" vector-effect="non-scaling-stroke"/>
                    </svg>
                    <p class="rs-note">الخط الأحمر المنقط حدّ الخطر العالي (60)، والبرتقالي حدّ المتوسط (35). من {{ $pts->first()['date'] }} إلى {{ $pts->last()['date'] }}.</p>
                @else
                    <p class="rs-note">لا يوجد تاريخ كافٍ بعد (يُبنى مع كل حساب ليلي).</p>
                @endif
            </div>
            @endif
        </div>

        <div>
            <div class="rs-card">
                <h3><i class="fa-solid fa-clipboard-check"></i> سجل المتابعة</h3>
                @if($d['since_last_intervention'])
                    @php $s = $d['since_last_intervention']; @endphp
                    <p class="rs-note" style="margin-top:0">منذ آخر إجراء ({{ $s['date'] }}): من {{ $fmt($s['from']) }} إلى {{ $fmt($s['to']) }}
                        <span class="rs-delta" style="color: {{ $s['delta'] > 0 ? '#c62828' : '#2e7d32' }}">{{ $s['delta'] > 0 ? '▲' : '▼' }} {{ abs($s['delta']) }}</span></p>
                @endif

                <form method="POST" action="{{ url('/' . $prefix . '/risk/students/' . $d['student']['id'] . '/interventions') }}" class="rs-form">
                    @csrf
                    <label>نوع الإجراء</label>
                    <select name="type" required>@foreach($d['intervention_types'] as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                    <label>ملاحظة</label>
                    <textarea name="note" rows="3" maxlength="1000" placeholder="ماذا حدث؟ وما الاتفاق؟"></textarea>
                    <label>موعد المتابعة التالية (اختياري)</label>
                    <input type="date" name="follow_up_on" min="{{ now()->toDateString() }}">
                    <button type="submit">تسجيل الإجراء</button>
                </form>

                <div style="margin-top:1.2rem;">
                    @forelse($d['interventions'] as $iv)
                        <div class="iv">
                            <b>{{ $iv['type_label'] }}</b> — {{ \Carbon\Carbon::parse($iv['created_at'])->format('Y-m-d') }}
                            @if($iv['note'])<div>{{ $iv['note'] }}</div>@endif
                            <small>{{ $iv['author'] }}@if($iv['score_at_time'] !== null) — الدرجة وقتها {{ $fmt($iv['score_at_time']) }}@endif @if($iv['follow_up_on']) — المتابعة في {{ $iv['follow_up_on'] }}@endif</small>
                        </div>
                    @empty
                        <p class="rs-note">لم تُسجَّل أي متابعة لهذا الطالب بعد.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
