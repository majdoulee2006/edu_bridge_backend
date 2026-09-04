@extends('layouts.student')
@section('title', 'الإنذارات الأكاديمية')
@section('subtitle', 'سجل تنبيهات وإنذارات الغياب')

@push('styles')
<style>
    .warnings-container {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    .warning-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        border-right: 6px solid var(--accent-color);
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .warning-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    .warning-card.first { border-right-color: #f59e0b; }
    .warning-card.second { border-right-color: #f97316; }
    .warning-card.final { border-right-color: #ef4444; }

    .warning-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .warning-title {
        font-size: 1.1rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .warning-badge {
        padding: 0.35rem 0.85rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .badge-first { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .badge-second { background: rgba(249, 115, 22, 0.15); color: #f97316; }
    .badge-final { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

    .warning-msg {
        font-size: 0.95rem;
        line-height: 1.6;
        color: var(--text-primary);
    }
    .warning-meta {
        font-size: 0.85rem;
        color: var(--text-secondary);
        display: flex;
        gap: 1.5rem;
        align-items: center;
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        box-shadow: var(--shadow);
    }
    .empty-state i {
        font-size: 3.5rem;
        color: #10b981;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="warnings-container">
    @if($warnings->isEmpty())
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3 style="font-weight: 800; margin-bottom: 0.5rem;">سجلك ناصع وخالٍ من أي إنذارات!</h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">لم يتم تسجيل أي إنذار غياب بحقك. التزامك بالدوام رائع ومميز، استمر في تفوقك.</p>
        </div>
    @else
        @foreach($warnings as $warning)
            @php
                $levelClass = $warning->warning_level;
                $levelText = match($warning->warning_level) {
                    'first' => 'إنذار أول (7 أيام غياب)',
                    'second' => 'إنذار ثانٍ (10 أيام غياب) - استدعاء ولي أمر',
                    'final' => 'إنذار نهائي (15 يوم غياب) - إحالة للإدارة',
                    default => 'إنذار أكاديمي'
                };
                $badgeClass = match($warning->warning_level) {
                    'first' => 'badge-first',
                    'second' => 'badge-second',
                    'final' => 'badge-final',
                    default => 'badge-first'
                };
            @endphp
            <div class="warning-card {{ $levelClass }}">
                <div class="warning-header">
                    <div class="warning-title">
                        <i class="fas fa-exclamation-triangle" style="color: inherit;"></i>
                        <span>{{ $levelText }}</span>
                    </div>
                    <span class="warning-badge {{ $badgeClass }}">
                        {{ $warning->absence_days }} يوم غياب
                    </span>
                </div>
                <div class="warning-msg">
                    {{ $warning->message }}
                </div>
                <div class="warning-meta">
                    <span><i class="far fa-calendar-alt"></i> {{ $warning->created_at->translatedFormat('d F Y - h:i A') }}</span>
                    @if($warning->warning_level === 'second')
                        <span style="color: #f97316;"><i class="fas fa-user-friends"></i> تم إرسال استدعاء لولي الأمر</span>
                    @elseif($warning->warning_level === 'final')
                        <span style="color: #ef4444;"><i class="fas fa-gavel"></i> بانتظار قرار الإدارة ورئيس القسم</span>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
