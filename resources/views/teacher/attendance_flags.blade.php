@extends('layouts.teacher')
@section('title', 'اشتباهات الحضور')
@section('subtitle', 'أنماط رُصدت تلقائيًا بعد إغلاق جلسات الحضور. النظام يقترح فقط، والقرار لك: لا يتغيّر حضور أي طالب إلا إذا اخترتَ ذلك صراحةً.')

@push('styles')
<style>
    .fl-summary { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:1rem; margin-bottom:1.25rem; }
    .fl-stat { background: var(--bg-secondary); border:1px solid var(--border-color); border-radius:1.1rem; padding:1rem; text-align:center; box-shadow: var(--shadow); }
    .fl-stat b { display:block; font-size:1.7rem; font-weight:800; color: var(--text-primary); }
    .fl-stat small { color: var(--text-secondary); font-size:.8rem; }
    .fl-tabs { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.25rem; }
    .fl-tab { padding:.45rem 1rem; border-radius:2rem; font-weight:700; font-size:.85rem; text-decoration:none;
        color: var(--text-secondary); border:1px solid var(--border-color); background: var(--bg-secondary); }
    .fl-tab.active { background: var(--primary-color); color:#fff; border-color: var(--primary-color); }
    .fl-card { background: var(--bg-secondary); border:1px solid var(--border-color); border-right:5px solid var(--sev);
        border-radius:1.1rem; padding:1.1rem 1.25rem; margin-bottom:1rem; box-shadow: var(--shadow); }
    .fl-head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; }
    .fl-student { font-weight:800; color: var(--text-primary); }
    .fl-course { font-size:.82rem; color: var(--text-secondary); margin-top:.15rem; }
    .fl-badges { display:flex; gap:.4rem; flex-wrap:wrap; }
    .fl-badge { padding:.2rem .7rem; border-radius:2rem; font-size:.75rem; font-weight:800; }
    .fl-badge.rule { background: rgba(120,120,120,.15); color: var(--text-primary); }
    .fl-badge.sev { background: color-mix(in srgb, var(--sev) 18%, transparent); color: var(--sev); }
    .fl-text { margin:.8rem 0; line-height:1.9; color: var(--text-primary); font-size:.92rem; }
    .fl-meta { font-size:.78rem; color: var(--text-secondary); }
    .fl-review { margin-top:.9rem; border-top:1px dashed var(--border-color); padding-top:.8rem; }
    .fl-review summary { cursor:pointer; font-weight:800; color: var(--primary-color); }
    .fl-form textarea { width:100%; margin-top:.7rem; padding:.7rem; border-radius:.8rem; border:1px solid var(--border-color);
        background: var(--bg-primary, transparent); color: var(--text-primary); font-family:inherit; }
    .fl-actions { display:flex; gap:.6rem; flex-wrap:wrap; align-items:center; margin-top:.7rem; }
    .fl-btn { padding:.55rem 1.1rem; border-radius:.8rem; font-weight:800; border:none; cursor:pointer; font-family:inherit; }
    .fl-btn.ok { background:#2e7d32; color:#fff; } .fl-btn.no { background: transparent; color: var(--text-primary); border:1px solid var(--border-color); }
    .fl-check { font-size:.82rem; color: var(--text-secondary); display:flex; gap:.4rem; align-items:center; }
    .fl-done { font-size:.85rem; color: var(--text-secondary); margin-top:.7rem; }
    .sev-high { --sev:#c62828; } .sev-medium { --sev:#e65100; } .sev-low { --sev:#607d8b; }
    .fl-alert { padding:.8rem 1rem; border-radius:.9rem; margin-bottom:1rem; font-weight:700; }
    .fl-alert.ok { background: rgba(46,125,50,.12); color:#2e7d32; } .fl-alert.err { background: rgba(198,40,40,.12); color:#c62828; }
</style>
@endpush

@section('content')
    @if(session('success'))<div class="fl-alert ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="fl-alert err">{{ session('error') }}</div>@endif

    <div class="fl-summary">
        <div class="fl-stat"><b>{{ $summary['open'] }}</b><small>مفتوحة للمراجعة</small></div>
        <div class="fl-stat sev-high"><b style="color:var(--sev)">{{ $summary['by_severity']['high'] }}</b><small>خطورة عالية</small></div>
        <div class="fl-stat sev-medium"><b style="color:var(--sev)">{{ $summary['by_severity']['medium'] }}</b><small>خطورة متوسطة</small></div>
        <div class="fl-stat sev-low"><b style="color:var(--sev)">{{ $summary['by_severity']['low'] }}</b><small>خطورة منخفضة</small></div>
    </div>

    <div class="fl-tabs">
        @foreach(['open' => 'مفتوحة', 'confirmed' => 'مؤكَّدة', 'dismissed' => 'مرفوضة', 'all' => 'الكل'] as $key => $label)
            <a href="{{ route('teacher.attendance_flags', array_filter(['status' => $key, 'severity' => $severity])) }}"
               class="fl-tab {{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @forelse($items as $f)
        @php
            $a = $f->attendance;
        @endphp
        <div class="fl-card sev-{{ $f->severity }}">
            <div class="fl-head">
                <div>
                    <div class="fl-student">{{ $f->student->user->full_name ?? '' }}</div>
                    <div class="fl-course">{{ $f->course->title ?? '' }}
                        @if($a) — {{ optional($a->attendance_date)->format('Y-m-d') }} @endif
                    </div>
                </div>
                <div class="fl-badges">
                    <span class="fl-badge rule">{{ \App\Services\AttendanceIntelligence\FlagPresenter::label($f->rule) }}</span>
                    <span class="fl-badge sev">خطورة {{ \App\Services\AttendanceIntelligence\FlagPresenter::severityLabel($f->severity) }}</span>
                </div>
            </div>

            <div class="fl-text">{{ \App\Services\AttendanceIntelligence\FlagPresenter::explain($f) }}</div>

            @if($f->status === 'open')
                <details class="fl-review">
                    <summary>مراجعة هذا الاشتباه</summary>
                    <form method="POST" action="{{ route('teacher.attendance_flags.review', $f->id) }}" class="fl-form">
                        @csrf
                        <textarea name="note" rows="2" maxlength="500" placeholder="ملاحظة اختيارية (مثلًا: زميلان بجوار بعض في القاعة)"></textarea>
                        <div class="fl-actions">
                            <button type="submit" name="decision" value="dismissed" class="fl-btn no">رفض: سلوك طبيعي</button>
                            <button type="submit" name="decision" value="confirmed" class="fl-btn ok">تأكيد الاشتباه</button>
                            <label class="fl-check">
                                <input type="checkbox" name="mark_absent" value="1">
                                عند التأكيد: سجّله غائبًا (يصله إشعار ويُحتسب بالإنذارات)
                            </label>
                        </div>
                    </form>
                </details>
            @else
                <div class="fl-done">
                    {{ $f->status === 'confirmed' ? 'تم التأكيد' : 'تم الرفض' }}
                    @if($f->reviewed_at) — {{ $f->reviewed_at->format('Y-m-d H:i') }} @endif
                    @if(!empty($f->details['marked_absent'])) — سُجّل غائبًا @endif
                    @if($f->review_note) — «{{ $f->review_note }}» @endif
                </div>
            @endif
        </div>
    @empty
        <div style="text-align:center; padding:3.5rem 2rem; background: var(--bg-secondary); border-radius:1.5rem; border:1px dashed var(--border-color);">
            <i class="fa-solid fa-circle-check" style="font-size:3rem; color:#2e7d32; opacity:.6; margin-bottom:1rem; display:block;"></i>
            <h4 style="font-size:1.2rem; font-weight:800; margin-bottom:.4rem;">لا توجد اشتباهات في هذه القائمة</h4>
            <p style="color: var(--text-secondary); font-size:.92rem;">تظهر هنا الحالات بعد إغلاق كل جلسة حضور إذا رُصد نمط مشبوه.</p>
        </div>
    @endforelse

    <div style="margin-top:1.25rem;">{{ $items->links() }}</div>
@endsection
