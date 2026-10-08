@extends('layouts.parent')
@section('title', 'الملخص الأسبوعي')
@section('subtitle', 'ملخص أسبوعي عن حضور أبنائك وواجباتهم وعلاماتهم، يصلك مساء كل خميس')

@push('styles')
<style>
    .digest-switch {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        background: var(--bg-secondary); border: 1px solid var(--border-color);
        border-radius: 1.25rem; padding: 1rem 1.25rem; margin-bottom: 1.5rem; box-shadow: var(--shadow);
    }
    .digest-switch strong { display: block; font-weight: 800; color: var(--text-primary); }
    .digest-switch span { font-size: .85rem; color: var(--text-secondary); }
    .digest-list { display: flex; flex-direction: column; gap: 1rem; }
    .digest-card {
        display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit;
        background: var(--bg-secondary); border: 1px solid var(--border-color);
        border-radius: 1.25rem; padding: 1.1rem 1.25rem; box-shadow: var(--shadow);
        transition: transform .15s ease, border-color .15s ease;
    }
    .digest-card:hover { transform: translateY(-2px); }
    .digest-card.unread { border-color: var(--tone); }
    .digest-ico {
        width: 46px; height: 46px; border-radius: 50%; flex: none;
        display: flex; align-items: center; justify-content: center;
        background: color-mix(in srgb, var(--tone) 15%, transparent); color: var(--tone); font-size: 1.15rem;
    }
    .digest-main { flex: 1; min-width: 0; }
    .digest-title { font-weight: 800; color: var(--text-primary); }
    .digest-meta { font-size: .8rem; color: var(--text-secondary); margin-top: .2rem; }
    .digest-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--tone); flex: none; }
    .digest-pdf {
        flex: none; padding: .45rem .85rem; border-radius: .75rem; font-size: .8rem; font-weight: 700;
        border: 1px solid var(--border-color); color: var(--text-primary); text-decoration: none; background: transparent;
    }
    .digest-pdf:hover { background: var(--bg-primary, rgba(0,0,0,.04)); }
    .tone-good { --tone: #2e7d32; } .tone-attention { --tone: #f9a825; } .tone-concern { --tone: #c62828; }
</style>
@endpush

@section('content')
    <form method="POST" action="{{ route('parent.digests.settings') }}" class="digest-switch">
        @csrf
        <div>
            <strong>استلام الملخص كل أسبوع</strong>
            <span>يصلك إشعار وعلى Telegram إن كان حسابك مرتبطًا به</span>
        </div>
        <input type="hidden" name="digest_enabled" value="{{ $digestEnabled ? 0 : 1 }}">
        <button type="submit" class="digest-pdf" style="cursor:pointer; {{ $digestEnabled ? '' : 'color:#2e7d32; border-color:#2e7d32;' }}">
            {{ $digestEnabled ? 'إيقاف الملخص' : 'تفعيل الملخص' }}
        </button>
    </form>

    @if($digests->isEmpty())
        <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
            <i class="fa-solid fa-chart-pie" style="font-size: 3rem; color: var(--text-secondary); opacity: .5; margin-bottom: 1rem; display: block;"></i>
            <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: .5rem;">لا توجد ملخصات بعد</h4>
            <p style="color: var(--text-secondary); font-size: .95rem;">سيظهر أول ملخص بنهاية أسبوع دراسي فيه نشاط.</p>
        </div>
    @else
        <div class="digest-list">
            @foreach($digests as $d)
                @php
                    $icon = ['good' => 'fa-circle-check', 'attention' => 'fa-circle-info', 'concern' => 'fa-triangle-exclamation'][$d->tone] ?? 'fa-circle-check';
                @endphp
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <a href="{{ route('parent.digests.show', $d->id) }}" class="digest-card tone-{{ $d->tone }} {{ $d->read_at ? '' : 'unread' }}" style="flex:1;">
                        <div class="digest-ico"><i class="fa-solid {{ $icon }}"></i></div>
                        <div class="digest-main">
                            <div class="digest-title">{{ $d->title }}</div>
                            <div class="digest-meta">{{ $d->week_start->format('Y-m-d') }} ← {{ $d->week_end->format('Y-m-d') }}</div>
                        </div>
                        @unless($d->read_at)<span class="digest-dot"></span>@endunless
                    </a>
                    <a href="{{ route('parent.digests.pdf', $d->id) }}" class="digest-pdf" title="تنزيل PDF">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </a>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 1.5rem;">{{ $digests->links() }}</div>
    @endif
@endsection
