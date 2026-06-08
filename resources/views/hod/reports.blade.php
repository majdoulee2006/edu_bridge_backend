@extends('layouts.hod')

@section('title', 'التقارير')

@push('styles')
<style>
    .page-subtitle { color: var(--text-secondary); font-size: 1rem; margin-top: -1.5rem; margin-bottom: 2rem; }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }
    .section-title { font-size: 1.2rem; font-weight: 800; }

    .add-circle-btn {
        width: 38px; height: 38px;
        border-radius: 50%;
        background-color: var(--accent-color);
        color: #1a1a1a;
        border: none; font-size: 1.3rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: transform 0.2s;
        flex-shrink: 0;
    }
    .add-circle-btn:hover { transform: scale(1.1); }

    .report-card {
        background-color: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .report-icon {
        width: 46px; height: 46px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .icon-academic   { background: #eff6ff; color: #2563eb; }
    .icon-behavioral { background: #fdf4ff; color: #9333ea; }

    .report-info h5 { font-size: 1rem; font-weight: 700; margin-bottom: 0.2rem; }
    .report-info p  { font-size: 0.83rem; color: var(--text-secondary); }

    .badge-type { padding: 0.2rem 0.7rem; border-radius: 1rem; font-size: 0.78rem; font-weight: 700; white-space: nowrap; }
    .badge-academic   { background: #eff6ff; color: #2563eb; }
    .badge-behavioral { background: #fdf4ff; color: #9333ea; }

    .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; display: block; }

    /* Modal */
    .modal-overlay {
        position: fixed; top:0; left:0; width:100%; height:100%;
        background:rgba(0,0,0,0.5); display:flex; align-items:center;
        justify-content:center; z-index:1000; opacity:0; pointer-events:none;
        transition:opacity 0.3s;
    }
    .modal-overlay.active { opacity:1; pointer-events:auto; }
    .modal-card {
        background-color:var(--bg-secondary); border-radius:1.5rem; padding:2rem;
        width:92%; max-width:560px; box-shadow:var(--shadow);
        transform:translateY(20px); transition:transform 0.3s;
        max-height:90vh; overflow-y:auto;
    }
    .modal-overlay.active .modal-card { transform:translateY(0); }

    .form-label { display:block; margin-bottom:0.4rem; font-weight:700; font-size:0.88rem; color:var(--text-secondary); }
    .form-input {
        width:100%; padding:0.75rem 1rem; border-radius:0.75rem;
        border:1px solid var(--border-color); background:var(--bg-primary);
        color:var(--text-primary); font-family:inherit; font-size:0.95rem; box-sizing:border-box;
    }
    .form-input:focus { outline:none; border-color:var(--accent-color); }
    .form-group { margin-bottom:0.9rem; }

    .type-cards { display:flex; gap:0.75rem; margin-bottom:0.9rem; }
    .type-card {
        flex:1; border:1px solid var(--border-color); border-radius:0.75rem;
        padding:0.9rem; text-align:center; cursor:pointer; transition:all 0.2s;
    }
    .type-card.active { border-color:var(--accent-color); background:#fefce8; }
    .type-card i { font-size:1.3rem; color:var(--text-secondary); display:block; margin-bottom:0.4rem; }
    .type-card.active i { color:#ca8a04; }
    .type-card span { font-size:0.88rem; font-weight:700; color:var(--text-primary); }

    .btn-save   { background:var(--accent-color); color:#1a1a1a; flex:1; padding:0.75rem; border-radius:0.75rem; border:none; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }
    .btn-cancel { background:transparent; border:1px solid var(--border-color); color:var(--text-primary); flex:1; padding:0.75rem; border-radius:0.75rem; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }
</style>
@endpush

@section('content')
    <p class="page-subtitle">متابعة تقارير أداء الطلاب</p>

    @if(session('success'))
        <div style="background:#f0fdf4;color:#16a34a;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background:#fef2f2;color:#dc2626;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Header + زر + --}}
    <div class="section-header">
        <h4 class="section-title">
            <i class="fa-solid fa-file-lines" style="color:var(--accent-color);margin-left:0.5rem;"></i>
            التقارير الصادرة
        </h4>
        <a href="{{ route('hod.reports.create') }}" class="add-circle-btn" title="طلب تقرير جديد">
            <i class="fa-solid fa-plus"></i>
        </a>
    </div>

    {{-- قائمة التقارير --}}
    @forelse($reports as $report)
    @php $isAcademic = $report->report_type === 'academic'; @endphp
    <div class="report-card">
        <div style="display:flex;align-items:center;gap:1rem;flex:1;min-width:0;">
            <div class="report-icon {{ $isAcademic ? 'icon-academic' : 'icon-behavioral' }}">
                <i class="fa-solid {{ $isAcademic ? 'fa-chart-line' : 'fa-comment-dots' }}"></i>
            </div>
            <div class="report-info" style="min-width:0;">
                <h5>{{ $report->student_name }}</h5>
                <p>
                    {{ \Carbon\Carbon::parse($report->generated_at ?? $report->created_at)->diffForHumans() }}
                    @if($isAcademic && !is_null($report->attendance_rate))
                        &bull; حضور: <strong>{{ number_format($report->attendance_rate, 0) }}%</strong>
                        @if(!is_null($report->average_grade))
                            &bull; معدل: <strong>{{ number_format($report->average_grade, 1) }}</strong>
                        @endif
                    @endif
                    @if(!$isAcademic && $report->recommendations)
                        &bull; {{ Str::limit($report->recommendations, 60) }}
                    @endif
                </p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
            <span class="badge-type {{ $isAcademic ? 'badge-academic' : 'badge-behavioral' }}">
                {{ $isAcademic ? 'أكاديمي' : 'سلوكي' }}
            </span>
            <form action="{{ route('hod.reports.delete', $report->report_id) }}" method="POST"
                  onsubmit="return confirm('حذف هذا التقرير؟')" style="margin:0;">
                @csrf
                <button type="submit" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:0.25rem;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="empty-state">
        <i class="fa-solid fa-file-circle-question"></i>
        <p>لا توجد تقارير صادرة حتى الآن.</p>
        <p style="font-size:0.88rem;">اضغط <strong>+</strong> لطلب تقرير من مدرب.</p>
    </div>
    @endforelse


@endsection
