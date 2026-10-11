@extends('layouts.parent')
@section('title', __('messages.performance_and_behavior_reports'))

@push('styles')
<style>
    /* Top Header Banner with Action (+ Request New Report) */
    .page-header-banner {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem 1.75rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        border: 1px solid var(--border-color);
        border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 5px solid var(--accent-color);
    }
    .page-header-info h2 {
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0 0 0.3rem 0;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .page-header-info p {
        font-size: 0.88rem;
        color: var(--text-secondary);
        margin: 0;
        font-weight: 600;
    }
    .btn-create-report {
        background: var(--accent-color);
        color: #1a1a1a;
        border: none;
        padding: 0.85rem 1.6rem;
        border-radius: 0.85rem;
        font-size: 0.95rem;
        font-weight: 800;
        cursor: pointer;
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        box-shadow: 0 4px 14px rgba(242, 242, 13, 0.35);
        transition: all 0.25s ease;
    }
    .btn-create-report:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(242, 242, 13, 0.5);
    }
    
    /* Reports List */
    .reports-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    @media(min-width: 992px) {
        .reports-list {
            grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
        }
    }
    
    .report-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .report-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent-color);
    }
    
    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .report-type {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    
    .status-badge {
        padding: 0.35rem 0.85rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    
    .status-completed {
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    
    .status-pending {
        background: rgba(245, 158, 11, 0.12);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.25);
    }
    
    .report-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .report-stat-item {
        background: var(--bg-primary);
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .report-stat-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-weight: 600;
    }
    
    .report-stat-value {
        font-weight: 800;
        font-size: 1.05rem;
        color: var(--text-primary);
    }
    
    .report-recommendations {
        background: var(--bg-primary);
        padding: 1rem;
        border-radius: 0.75rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.6;
        border: 1px solid var(--border-color);
        position: relative;
    }
    
    .report-recommendations-title {
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
    }
    
    .report-recommendations-title i {
        color: var(--accent-color);
    }
    
    .report-meta {
        margin-top: 1rem;
        font-size: 0.8rem;
        color: var(--text-secondary);
        opacity: 0.8;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 0.75rem;
        border-top: 1px solid var(--border-color);
    }
    
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(6px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-box {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 1.5rem;
        width: 100%;
        max-width: 520px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        overflow: hidden;
        animation: modalPop 0.3s ease-out;
    }
    
    @keyframes modalPop {
        from { transform: scale(0.92); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    
    .modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        font-size: 1.2rem;
        font-weight: 800;
        margin: 0;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    
    .modal-close-btn {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        font-size: 1.3rem;
        cursor: pointer;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .modal-close-btn:hover {
        background: var(--bg-primary);
        color: var(--text-primary);
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 0.6rem;
        color: var(--text-primary);
    }
    
    .type-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .type-option {
        text-align: center;
        border: 2px solid var(--border-color);
        padding: 1rem 0.75rem;
        border-radius: 1rem;
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: 800;
        background: var(--bg-primary);
        transition: all 0.2s ease;
        color: var(--text-primary);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .type-option i {
        font-size: 1.5rem;
        color: var(--accent-color);
    }
    
    .type-option.active {
        background: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
        box-shadow: 0 4px 14px rgba(242, 242, 13, 0.3);
    }
    .type-option.active i {
        color: #1a1a1a;
    }
    
    .btn-submit {
        background: var(--accent-color);
        color: #1a1a1a;
        font-weight: 800;
        border: none;
        width: 100%;
        padding: 0.9rem;
        border-radius: 0.85rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-family: inherit;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    
    .btn-submit:hover {
        filter: brightness(1.05);
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')

@if(!$selected_child_id)
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
        <i class="fa-solid fa-child" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5; margin-bottom: 1rem; display: block;"></i>
        <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">{{ __('messages.please_select_child_first') }}</h4>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">{{ __('messages.select_child_hint') }}</p>
    </div>
@else
    @section('subtitle')
        {{ __('messages.performance_reports_subtitle') }}: {{ $selected_child->full_name }}
    @endsection

    {{-- Top Action Banner with (+ Request Report Button) --}}
    <div class="page-header-banner">
        <div class="page-header-info">
            <h2>
                <i class="fa-solid fa-chart-line" style="color: var(--accent-color);"></i>
                {{ __('messages.issued_reports_log') }}
            </h2>
            <p>{{ __('messages.performance_reports_subtitle') }}: {{ $selected_child->full_name }}</p>
        </div>
        <button type="button" class="btn-create-report" onclick="openRequestReportModal()">
            <i class="fa-solid fa-plus"></i>
            {{ __('messages.request_new_report_title') }}
        </button>
    </div>

    {{-- List of Reports --}}
    @if($reports->isNotEmpty())
        <div class="reports-list">
            @foreach($reports as $rep)
                <div class="report-card">
                    <div class="report-header">
                        <span class="report-type">
                            <i class="fa-solid fa-file-invoice" style="color: var(--accent-color); margin-inline-end: 0.5rem;"></i>
                            {{ $rep->report_type === 'academic' ? __('messages.report_type_academic') : __('messages.report_type_behavioral') }}
                        </span>
                        <span class="status-badge status-{{ $rep->status }}">
                            <i class="fa-solid {{ $rep->status === 'completed' ? 'fa-check-circle' : 'fa-hourglass-half' }}"></i>
                            {{ $rep->status === 'completed' ? __('messages.report_ready') : __('messages.report_under_prep') }}
                        </span>
                    </div>
                    
                    @if($rep->status === 'completed')
                        <div class="report-stats">
                            <div class="report-stat-item">
                                <span class="report-stat-label">{{ __('messages.lecture_attendance_rate') }}</span>
                                <span class="report-stat-value" style="color: #10b981;">{{ $rep->attendance_rate }}%</span>
                            </div>
                            <div class="report-stat-item">
                                <span class="report-stat-label">{{ __('messages.cumulative_gpa_rate') }}</span>
                                <span class="report-stat-value" style="color: var(--accent-color);">{{ $rep->average_grade }}%</span>
                            </div>
                        </div>
                        
                        <div class="report-recommendations">
                            <div class="report-recommendations-title">
                                <i class="fa-solid fa-lightbulb"></i> {{ __('messages.mentor_recommendations') }}:
                            </div>
                            <p style="margin: 0; color: var(--text-secondary);">{{ $rep->recommendations }}</p>
                        </div>
                    @else
                        <div style="color: var(--text-secondary); font-style: italic; font-size: 0.9rem; padding: 1rem; background: var(--bg-primary); border-radius: 0.75rem; border: 1px solid var(--border-color);">
                            <i class="fa-solid fa-info-circle" style="color: var(--accent-color); margin-inline-end: 0.4rem;"></i>
                            {{ __('messages.behavioral_report_pending_note') }}
                        </div>
                    @endif
                    
                    <div class="report-meta">
                        <span><i class="fa-regular fa-calendar-days"></i> {{ __('messages.request_date') }}: {{ \Carbon\Carbon::parse($rep->created_at)->format('Y-m-d H:i') }}</span>
                        <span style="font-weight: 700; color: var(--text-primary);"><i class="fa-solid fa-user-graduate" style="color: var(--accent-color);"></i> {{ $selected_child->full_name }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 4.5rem 2rem; background: var(--bg-secondary); border-radius: 1.25rem; border: 1px dashed var(--border-color); color: var(--text-secondary);">
            <i class="fa-solid fa-file-excel" style="font-size: 2.8rem; opacity: 0.4; margin-bottom: 1rem; display: block;"></i>
            <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem;">{{ __('messages.no_reports_issued_yet') }}</h4>
            <p style="font-size: 0.88rem; color: var(--text-secondary); max-width: 420px; margin: 0 auto 1.5rem;">{{ __('messages.request_report_desc') }}</p>
            <button type="button" class="btn-create-report" onclick="openRequestReportModal()">
                <i class="fa-solid fa-plus"></i>
                {{ __('messages.request_new_report_title') }}
            </button>
        </div>
    @endif

    {{-- Request New Report Modal --}}
    <div class="modal-overlay" id="requestReportModal" onclick="handleModalOverlayClick(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3>
                    <i class="fa-solid fa-file-signature" style="color: var(--accent-color);"></i>
                    {{ __('messages.request_new_report_title') }}
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeRequestReportModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.5rem;">
                    {{ __('messages.request_report_desc') }}
                </p>

                <form action="{{ route('parent.reports.request') }}" method="POST">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selected_child_id }}">
                    
                    <div class="form-group">
                        <label>{{ __('messages.report_type') }}</label>
                        <div class="type-options">
                            <div class="type-option active" data-value="academic" onclick="selectReportType('academic')">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <span>{{ __('messages.academic') }}</span>
                            </div>
                            <div class="type-option" data-value="behavioral" onclick="selectReportType('behavioral')">
                                <i class="fa-solid fa-user-check"></i>
                                <span>{{ __('messages.behavioral') }}</span>
                            </div>
                        </div>
                        <input type="hidden" name="report_type" id="report-type-input" value="academic">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        {{ __('messages.send_report_request_btn') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
function openRequestReportModal() {
    const modal = document.getElementById('requestReportModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeRequestReportModal() {
    const modal = document.getElementById('requestReportModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function handleModalOverlayClick(e) {
    if (e.target.id === 'requestReportModal') {
        closeRequestReportModal();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRequestReportModal();
    }
});

function selectReportType(val) {
    document.getElementById('report-type-input').value = val;
    document.querySelectorAll('.type-option').forEach(el => {
        if(el.getAttribute('data-value') === val) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}
</script>
@endpush
