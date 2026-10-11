@extends('layouts.hod')

@section('title', __('messages.reports'))

@push('styles')
<style>
    .page-subtitle { color: var(--text-secondary); font-size: 1rem; margin-top: -1.5rem; margin-bottom: 2rem; }

    /* Tabs Navigation */
    .tabs-nav {
        display: flex;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 1.5rem;
        gap: 2rem;
    }
    .tab-btn {
        background: transparent;
        border: none;
        padding: 0.75rem 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-secondary);
        cursor: pointer;
        position: relative;
        transition: color 0.2s;
    }
    .tab-btn:hover {
        color: var(--accent-color);
    }
    .tab-btn.active {
        color: var(--accent-color);
    }
    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: var(--accent-color);
    }
    .tab-panel {
        display: none;
    }
    .tab-panel.active {
        display: block;
    }

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
        text-decoration: none;
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

    .btn-save   { background:var(--accent-color); color:#1a1a1a; flex:1; padding:0.75rem; border-radius:0.75rem; border:none; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }
    .btn-cancel { background:transparent; border:1px solid var(--border-color); color:var(--text-primary); flex:1; padding:0.75rem; border-radius:0.75rem; font-weight:700; cursor:pointer; font-size:1rem; font-family:inherit; }

    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.25); }
</style>
@endpush

@section('content')
    <p class="page-subtitle">{{ __('messages.follow_student_performance_reports') }}</p>

    {{-- Header + Add Button --}}
    <div class="section-header">
        <h4 class="section-title">
            <i class="fa-solid fa-file-lines" style="color:var(--accent-color);margin-left:0.5rem;"></i>
            {{ __('messages.outgoing_reports') }}
        </h4>
        <a href="{{ route('hod.reports.create') }}" class="add-circle-btn" title="{{ __('messages.request_new_report') }}">
            <i class="fa-solid fa-plus"></i>
        </a>
    </div>

    {{-- Tabs Navigation --}}
    <div class="tabs-nav">
        <button class="tab-btn active" onclick="switchTab('my-requests')">
            <i class="fa-solid fa-reply-all" style="margin-left:0.25rem;"></i>
            {{ __('messages.my_requests_responses_tab') }}
        </button>
        <button class="tab-btn" onclick="switchTab('advisor-requests')">
            <i class="fa-solid fa-user-tie" style="margin-left:0.25rem;"></i>
            {{ __('messages.advisor_requests_tab') }}
        </button>
    </div>

    {{-- Tab 1: My Requests Responses --}}
    <div id="my-requests" class="tab-panel active">
        @forelse($myRequestsReports as $report)
            @php 
                $isAcademic = $report->report_type === 'academic'; 
                $rId = $report->report_id ?? $report->request_id;
                $sName = addslashes($report->student_name ?? '');
                $sCode = addslashes($report->student_code ?? '');
                $tName = addslashes($report->teacher_name ?? __('messages.supervising_teacher'));
                $tLabel = $isAcademic ? __('messages.academic_report_type') : __('messages.behavioral_report_type');
                $dText = \Carbon\Carbon::parse($report->generated_at ?? $report->created_at)->format('Y/m/d H:i');
                $att = $isAcademic && !is_null($report->attendance_rate) ? number_format($report->attendance_rate, 0) : '';
                $avg = $isAcademic && !is_null($report->average_grade) ? number_format($report->average_grade, 1) : '';
                $recom = addslashes(str_replace(["\r\n", "\r", "\n"], '\n', $report->recommendations ?? ''));
                $hNotes = addslashes(str_replace(["\r\n", "\r", "\n"], '\n', $report->hod_notes ?? ''));
            @endphp
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
                                &bull; {{ __('messages.attendance_word') }}: <strong>{{ number_format($report->attendance_rate, 0) }}%</strong>
                                @if(!is_null($report->average_grade))
                                    &bull; {{ __('messages.gpa_word') }}: <strong>{{ number_format($report->average_grade, 1) }}</strong>
                                @endif
                            @endif
                            @if(!$isAcademic && $report->recommendations)
                                &bull; {{ Str::limit($report->recommendations, 60) }}
                            @endif
                            @if($report->hod_notes)
                                <br><span style="color:#d97706;font-weight:600;"><i class="fa-solid fa-pen-fancy"></i> {{ __('messages.hod_opinion_short') }} {{ Str::limit($report->hod_notes, 50) }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
                    <span class="badge-type {{ $isAcademic ? 'badge-academic' : 'badge-behavioral' }}">
                        {{ $isAcademic ? __('messages.academic_report_type') : __('messages.behavioral_report_type') }}
                    </span>

                    {{-- View & Add HOD Notes --}}
                    <button type="button" 
                            onclick="openReadModal('{{ $rId }}', '{{ $sName }}', '{{ $sCode }}', '{{ $tName }}', '{{ $tLabel }}', '{{ $dText }}', '{{ $att }}', '{{ $avg }}', '{{ $recom }}', '{{ $hNotes }}')"
                            style="background:#eff6ff; border:1px solid #3b82f6; color:#1d4ed8; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.45rem 0.9rem; border-radius:0.6rem; display:flex; align-items:center; gap:0.35rem;" 
                            title="{{ __('messages.view_report_btn') }}">
                        <i class="fa-solid fa-file-lines"></i> {{ __('messages.view_report_btn') }}
                    </button>

                    {{-- Send to Parent --}}
                    @if(!$report->sent_to_parent)
                    <form action="{{ route('hod.reports.send_to_parent', $rId) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" 
                                style="background:#fffbeb; border:1px solid #f59e0b; color:#d97706; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.45rem 0.9rem; border-radius:0.6rem; display:flex; align-items:center; gap:0.35rem;" 
                                title="{{ __('messages.send_to_parent_btn') }}">
                            <i class="fa-solid fa-share-nodes"></i> {{ __('messages.send_to_parent_btn') }}
                        </button>
                    </form>
                    @else
                    <span style="background:#f0fdf4; border:1px solid #16a34a; color:#16a34a; font-size:0.83rem; font-weight:700; padding:0.45rem 0.9rem; border-radius:0.6rem; display:flex; align-items:center; gap:0.35rem;">
                        <i class="fa-solid fa-circle-check"></i> {{ __('messages.sent_success_badge') }}
                    </span>
                    @endif

                    {{-- Delete Report --}}
                    <form action="{{ route('hod.reports.delete', $rId) }}" method="POST"
                          onsubmit="return confirm('{{ __('messages.delete_report_confirm') }}')" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:0.25rem;" title="{{ __('messages.delete') }}">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fa-solid fa-file-circle-question"></i>
                <p>{{ __('messages.no_outgoing_reports_yet') }}</p>
                <p style="font-size:0.88rem;">{{ __('messages.click_plus_request_report') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Tab 2: Advisor Requests --}}
    <div id="advisor-requests" class="tab-panel">
        @forelse($advisorReports as $report)
            @php 
                $isAcademic = $report->report_type === 'academic'; 
                $rId = $report->report_id ?? $report->request_id;
                $sName = addslashes($report->student_name ?? '');
                $sCode = addslashes($report->student_code ?? '');
                $tName = addslashes($report->teacher_name ?? __('messages.supervising_teacher'));
                $tLabel = $isAcademic ? __('messages.academic_report_type') : __('messages.behavioral_report_type');
                $dText = \Carbon\Carbon::parse($report->generated_at ?? $report->created_at)->format('Y/m/d H:i');
                $att = $isAcademic && !is_null($report->attendance_rate) ? number_format($report->attendance_rate, 0) : '';
                $avg = $isAcademic && !is_null($report->average_grade) ? number_format($report->average_grade, 1) : '';
                $recom = addslashes(str_replace(["\r\n", "\r", "\n"], '\n', $report->recommendations ?? ''));
                $hNotes = addslashes(str_replace(["\r\n", "\r", "\n"], '\n', $report->hod_notes ?? ''));
            @endphp
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
                                &bull; {{ __('messages.attendance_word') }}: <strong>{{ number_format($report->attendance_rate, 0) }}%</strong>
                                @if(!is_null($report->average_grade))
                                    &bull; {{ __('messages.gpa_word') }}: <strong>{{ number_format($report->average_grade, 1) }}</strong>
                                @endif
                            @endif
                            @if(!$isAcademic && $report->recommendations)
                                &bull; {{ Str::limit($report->recommendations, 60) }}
                            @endif
                            @if($report->hod_notes)
                                <br><span style="color:#d97706;font-weight:600;"><i class="fa-solid fa-pen-fancy"></i> {{ __('messages.hod_opinion_short') }} {{ Str::limit($report->hod_notes, 50) }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
                    <span class="badge-type {{ $isAcademic ? 'badge-academic' : 'badge-behavioral' }}">
                        {{ $isAcademic ? __('messages.academic_report_type') : __('messages.behavioral_report_type') }}
                    </span>

                    {{-- View & Add HOD Notes --}}
                    <button type="button" 
                            onclick="openReadModal('{{ $rId }}', '{{ $sName }}', '{{ $sCode }}', '{{ $tName }}', '{{ $tLabel }}', '{{ $dText }}', '{{ $att }}', '{{ $avg }}', '{{ $recom }}', '{{ $hNotes }}')"
                            style="background:#eff6ff; border:1px solid #3b82f6; color:#1d4ed8; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.45rem 0.9rem; border-radius:0.6rem; display:flex; align-items:center; gap:0.35rem;" 
                            title="{{ __('messages.view_report_btn') }}">
                        <i class="fa-solid fa-file-lines"></i> {{ __('messages.view_report_btn') }}
                    </button>

                    {{-- Send to Parent --}}
                    @if(!$report->sent_to_parent)
                    <form action="{{ route('hod.reports.send_to_parent', $rId) }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" 
                                style="background:#fffbeb; border:1px solid #f59e0b; color:#d97706; cursor:pointer; font-size:0.83rem; font-weight:700; padding:0.45rem 0.9rem; border-radius:0.6rem; display:flex; align-items:center; gap:0.35rem;" 
                                title="{{ __('messages.send_to_parent_btn') }}">
                            <i class="fa-solid fa-share-nodes"></i> {{ __('messages.send_to_parent_btn') }}
                        </button>
                    </form>
                    @else
                    <span style="background:#f0fdf4; border:1px solid #16a34a; color:#16a34a; font-size:0.83rem; font-weight:700; padding:0.45rem 0.9rem; border-radius:0.6rem; display:flex; align-items:center; gap:0.35rem;">
                        <i class="fa-solid fa-circle-check"></i> {{ __('messages.sent_success_badge') }}
                    </span>
                    @endif

                    {{-- Delete Report --}}
                    <form action="{{ route('hod.reports.delete', $rId) }}" method="POST"
                          onsubmit="return confirm('{{ __('messages.delete_report_confirm') }}')" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:transparent;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:0.25rem;" title="{{ __('messages.delete') }}">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fa-solid fa-file-circle-question"></i>
                <p>{{ __('messages.no_advisor_reports_yet') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Modal: Read & Edit Report --}}
    <div class="modal-overlay" id="read-report-modal">
        <div class="modal-card" style="max-width:540px; width:92%; max-height:86vh; padding:1.75rem; border-radius:1.5rem; display:flex; flex-direction:column; overflow:hidden;">
            <!-- Modal Header -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem; flex-shrink:0;">
                <h4 style="font-size:1.15rem; font-weight:800; margin:0; display:flex; align-items:center; gap:0.5rem; color:var(--text-primary);">
                    <i class="fa-solid fa-file-invoice" style="color:var(--accent-color);"></i>
                    {{ __('messages.report_details_and_followup') }}
                </h4>
                <button type="button" onclick="closeReadModal()" style="background:transparent; border:none; font-size:1.4rem; cursor:pointer; color:var(--text-secondary); line-height:1;">&times;</button>
            </div>
            
            <!-- Scrollable Body (Vertical Flow) -->
            <div class="custom-scroll" style="flex:1; overflow-y:auto; padding-left:0.35rem; padding-right:0.15rem; display:flex; flex-direction:column; gap:1rem;">
                <div id="report-detail-content">
                    <!-- Dynamic Content -->
                </div>

                <!-- Section: Locked HOD Opinion -->
                <div id="hod-notes-locked-container" style="display:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                        <span style="font-weight:800; font-size:0.9rem; color:#b45309; display:flex; align-items:center; gap:0.4rem;">
                            <i class="fa-solid fa-user-tie"></i> {{ __('messages.hod_guidance_and_notes') }}
                        </span>
                        <span style="font-size:0.75rem; color:#92400e; background:#fef3c7; border:1px solid #fde68a; padding:0.2rem 0.6rem; border-radius:0.5rem; font-weight:700;">
                            <i class="fa-solid fa-lock"></i> {{ __('messages.hod_opinion_approved_locked') }}
                        </span>
                    </div>
                    <div id="hod-notes-locked-text" style="background:#fffbeb; color:#78350f; padding:0.85rem 1rem; border-radius:0.85rem; border-right:4px solid #d97706; border-top:1px solid #fef3c7; border-bottom:1px solid #fef3c7; border-left:1px solid #fef3c7; line-height:1.6; font-size:0.92rem; white-space:pre-wrap; font-weight:600;"></div>
                </div>

                <!-- Section: Editable HOD Opinion input for single time submission -->
                <div id="hod-notes-input-container" style="display:none; padding:1.1rem; background:var(--bg-primary); border-radius:1rem; border:1px solid var(--border-color);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                        <label for="modal-hod-notes" style="display:flex; align-items:center; gap:0.4rem; font-weight:800; font-size:0.9rem; color:#b45309; margin:0;">
                            <i class="fa-solid fa-user-tie"></i>
                            {{ __('messages.add_hod_opinion_title') }}
                        </label>
                        <span style="font-size:0.72rem; color:#dc2626; background:#fee2e2; padding:0.15rem 0.5rem; border-radius:0.4rem; font-weight:700;">
                            <i class="fa-solid fa-circle-info"></i> {{ __('messages.saved_once_notice') }}
                        </span>
                    </div>
                    <p style="font-size:0.78rem; color:var(--text-secondary); margin-bottom:0.5rem; line-height:1.4;">
                        {{ __('messages.hod_notes_desc_notice') }}
                    </p>
                    <textarea id="modal-hod-notes" rows="3" class="form-input" 
                              placeholder="{{ __('messages.write_hod_opinion_ph') }}" 
                              style="resize:none; font-size:0.9rem;"></textarea>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.75rem;">
                        <span id="hod-notes-status" style="font-size:0.82rem; font-weight:600; display:none;"></span>
                        <button type="button" onclick="saveHodNotes()" id="btn-save-hod-notes" class="btn-save" style="padding:0.55rem 1.25rem; flex:none; display:flex; align-items:center; gap:0.4rem; font-size:0.9rem;">
                            <i class="fa-solid fa-floppy-disk"></i>
                            {{ __('messages.save_and_approve_hod_opinion') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div style="margin-top:1.25rem; padding-top:0.75rem; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; flex-shrink:0;">
                <button class="btn-cancel" onclick="closeReadModal()" style="padding:0.6rem 2.2rem; flex:none; font-weight:700;">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    let currentReportId = null;

    const reportStrings = {
        academicBadge: "{{ __('messages.academic_report_type') }}",
        behavioralBadge: "{{ __('messages.behavioral_report_type') }}",
        academicIdLabel: "{{ __('messages.student_university_id') }}",
        supervisingTeacherLabel: "{{ __('messages.supervising_teacher') }}",
        attendanceRateLabel: "{{ __('messages.attendance_rate_label') }}",
        gpaLabel: "{{ __('messages.cumulative_gpa_label') }}",
        teacherEvaluationLabel: "{{ __('messages.teacher_evaluation_notes') }}",
        evaluatedLockedBadge: "{{ __('messages.evaluation_locked_badge') }}",
        noTeacherNotes: "{{ __('messages.no_teacher_notes_found') }}",
        writeNotePrompt: "{{ __('messages.write_note_before_saving') }}",
        confirmApprove: "{{ __('messages.confirm_approve_note') }}",
        approvingText: "{{ __('messages.approving_status') }}",
        saveBtnText: "{{ __('messages.save_and_approve_hod_opinion') }}",
        approvedSuccess: "{{ __('messages.approved_success') }}",
        saveError: "{{ __('messages.save_error_occured') }}",
        serverError: "{{ __('messages.server_connection_error') }}"
    };

    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));

        const clickedBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.getAttribute('onclick').includes(tabId));
        if (clickedBtn) clickedBtn.classList.add('active');

        const targetPanel = document.getElementById(tabId);
        if (targetPanel) targetPanel.classList.add('active');
    }

    function openReadModal(reportId, studentName, studentCode, teacherName, typeLabel, dateText, attendance, average, recommendations, hodNotes) {
        currentReportId = reportId;

        let isAcad = (typeLabel === reportStrings.academicBadge || typeLabel === 'أكاديمي');
        let contentHtml = `
            <div style="background:var(--bg-primary); border-radius:1rem; padding:1.1rem 1.25rem; border:1px solid var(--border-color); display:flex; flex-direction:column; gap:0.65rem;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:1.05rem; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:0.4rem;">
                        <i class="fa-solid fa-user-graduate" style="color:var(--accent-color);"></i>
                        ${studentName}
                    </span>
                    <span class="badge-type ${isAcad ? 'badge-academic' : 'badge-behavioral'}">${typeLabel}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; color:var(--text-secondary); border-top:1px dashed var(--border-color); padding-top:0.55rem;">
                    <span>${reportStrings.academicIdLabel}: <strong style="color:var(--text-primary); font-family:monospace;">${studentCode || '—'}</strong></span>
                    <span><i class="fa-solid fa-calendar-day" style="margin-left:0.25rem;"></i>${dateText}</span>
                </div>
                <div style="font-size:0.85rem; color:var(--text-secondary);">
                    <span>${reportStrings.supervisingTeacherLabel}: <strong style="color:var(--text-primary);">${teacherName || '—'}</strong></span>
                </div>
            </div>
        `;

        if (attendance !== '' || average !== '') {
            contentHtml += `<div style="display:flex; gap:0.75rem; margin-top:0.75rem;">`;
            if (attendance !== '') {
                contentHtml += `
                    <div style="flex:1; background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.75rem; padding:0.6rem 0.8rem; text-align:center;">
                        <div style="font-size:0.75rem; color:#1e40af; font-weight:700;">${reportStrings.attendanceRateLabel}</div>
                        <div style="font-size:1.15rem; color:#1d4ed8; font-weight:800;">${attendance}%</div>
                    </div>`;
            }
            if (average !== '') {
                contentHtml += `
                    <div style="flex:1; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.75rem; padding:0.6rem 0.8rem; text-align:center;">
                        <div style="font-size:0.75rem; color:#166534; font-weight:700;">${reportStrings.gpaLabel}</div>
                        <div style="font-size:1.15rem; color:#15803d; font-weight:800;">${average}</div>
                    </div>`;
            }
            contentHtml += `</div>`;
        }

        contentHtml += `
            <div style="margin-top:0.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                    <span style="font-weight:800; font-size:0.9rem; color:var(--text-primary); display:flex; align-items:center; gap:0.4rem;">
                        <i class="fa-solid fa-chalkboard-user" style="color:#2563eb;"></i> ${reportStrings.teacherEvaluationLabel}
                    </span>
                    <span style="font-size:0.72rem; color:#16a34a; background:#dcfce7; padding:0.15rem 0.5rem; border-radius:0.4rem; font-weight:700;">
                        <i class="fa-solid fa-lock"></i> ${reportStrings.evaluatedLockedBadge}
                    </span>
                </div>
                <div style="background:var(--bg-primary); padding:0.85rem 1rem; border-radius:0.85rem; border-right:4px solid #2563eb; border-top:1px solid var(--border-color); border-bottom:1px solid var(--border-color); border-left:1px solid var(--border-color); line-height:1.6; font-size:0.92rem; white-space:pre-wrap;">
                    ${recommendations ? recommendations.replace(/\\n/g, '\n') : `<span style="color:var(--text-secondary); font-style:italic;">${reportStrings.noTeacherNotes}</span>`}
                </div>
            </div>
        `;

        document.getElementById('report-detail-content').innerHTML = contentHtml;
        
        const lockedContainer = document.getElementById('hod-notes-locked-container');
        const lockedText = document.getElementById('hod-notes-locked-text');
        const inputContainer = document.getElementById('hod-notes-input-container');
        const hodTextarea = document.getElementById('modal-hod-notes');
        const statusEl = document.getElementById('hod-notes-status');
        
        statusEl.style.display = 'none';

        const cleanNotes = (hodNotes || '').trim();
        if (cleanNotes.length > 0) {
            lockedContainer.style.display = 'block';
            lockedText.innerText = cleanNotes.replace(/\\n/g, '\n');
            inputContainer.style.display = 'none';
        } else {
            lockedContainer.style.display = 'none';
            inputContainer.style.display = 'block';
            hodTextarea.value = '';
        }

        document.getElementById('read-report-modal').classList.add('active');
    }

    function saveHodNotes() {
        if (!currentReportId) return;
        const saveBtn = document.getElementById('btn-save-hod-notes');
        const statusEl = document.getElementById('hod-notes-status');
        const notesValue = document.getElementById('modal-hod-notes').value.trim();
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!notesValue) {
            statusEl.style.display = 'inline-block';
            statusEl.style.color = '#dc2626';
            statusEl.innerText = reportStrings.writeNotePrompt;
            return;
        }

        if (!confirm(reportStrings.confirmApprove)) {
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${reportStrings.approvingText}`;

        fetch(`{{ url('/hod/reports') }}/${currentReportId}/hod-notes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ hod_notes: notesValue })
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> ${reportStrings.saveBtnText}`;
            statusEl.style.display = 'inline-block';
            if (data.success) {
                statusEl.style.color = '#16a34a';
                statusEl.innerText = '✓ ' + (data.message || reportStrings.approvedSuccess);
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            } else {
                statusEl.style.color = '#dc2626';
                statusEl.innerText = '✕ ' + (data.message || reportStrings.saveError);
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> ${reportStrings.saveBtnText}`;
            statusEl.style.display = 'inline-block';
            statusEl.style.color = '#dc2626';
            statusEl.innerText = '✕ ' + reportStrings.serverError;
        });
    }

    function closeReadModal() {
        document.getElementById('read-report-modal').classList.remove('active');
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeReadModal();
        }
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('read-report-modal');
        if (modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === this) {
                    closeReadModal();
                }
            });
        }
    });
</script>
@endpush
