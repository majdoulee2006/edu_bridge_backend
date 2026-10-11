@extends('layouts.hod')

@section('title', __('messages.appointments_and_summons'))
@section('header-title', __('messages.meetings_and_appointments_mgmt'))
@section('header-subtitle', __('messages.parents_meetings_schedule'))

@push('styles')
<style>
    /* Tabs Styling */
    .custom-tabs {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid var(--border-color, #e2e8f0);
        padding-bottom: 0.75rem;
        overflow-x: auto;
    }
    .tab-btn {
        background: var(--surface-light, #ffffff);
        border: 1px solid var(--border-color, #cbd5e1);
        color: var(--text-secondary, #64748b);
        font-size: 0.95rem;
        font-weight: 700;
        padding: 0.65rem 1.25rem;
        cursor: pointer;
        transition: all 0.25s ease;
        white-space: nowrap;
        border-radius: 0.75rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }
    [data-theme="dark"] .tab-btn { 
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8; 
    }
    .tab-btn:hover {
        color: var(--text-primary, #0f172a);
        background: rgba(0, 0, 0, 0.04);
    }
    [data-theme="dark"] .tab-btn:hover {
        color: #f8fafc;
        background: rgba(255, 255, 255, 0.06);
    }
    /* Active Tab - Uses exact system yellow var(--accent-color) with dark text */
    .tab-btn.active {
        color: #101924 !important;
        background-color: var(--accent-color, #f2f20d) !important;
        border-color: var(--accent-color, #f2f20d) !important;
        box-shadow: var(--glow-shadow, 0 4px 15px rgba(242, 242, 13, 0.4)) !important;
        font-weight: 800 !important;
    }

    /* Tab Content Area */
    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .tab-content.active {
        display: block;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Form and tables */
    .table-container {
        background: var(--surface-light, #ffffff);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06);
    }
    [data-theme="dark"] .table-container {
        background: var(--surface-dark, #1a2633);
        box-shadow: none;
    }
    .grid-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    .card-panel {
        background: var(--surface-light, #ffffff);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color, #e2e8f0);
    }
    [data-theme="dark"] .card-panel {
        background: var(--surface-dark, #1a2633);
        border-color: #334155;
    }
    select option {
        background-color: #ffffff !important;
        color: #0f172a !important;
    }
    [data-theme="dark"] select option {
        background-color: #1a2633 !important;
        color: #f8fafc !important;
    }

    /* Solid opaque background for modals */
    .modal-card {
        background-color: #ffffff !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4) !important;
    }
    [data-theme="dark"] .modal-card {
        background-color: #0f172a !important;
        color: #f8fafc !important;
        border: 1px solid #334155 !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9) !important;
    }
    .modal-card input,
    .modal-card select,
    .modal-card textarea {
        background-color: #ffffff !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
    }
    [data-theme="dark"] .modal-card input,
    [data-theme="dark"] .modal-card select,
    [data-theme="dark"] .modal-card textarea {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border: 1px solid #334155 !important;
    }

    .responsive-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    [dir="rtl"] .responsive-table th,
    [dir="rtl"] .responsive-table td {
        text-align: right;
    }
    [dir="ltr"] .responsive-table th,
    [dir="ltr"] .responsive-table td {
        text-align: left;
    }
    [dir="rtl"] .responsive-table th.col-actions,
    [dir="rtl"] .responsive-table td.col-actions {
        text-align: left;
    }
    [dir="ltr"] .responsive-table th.col-actions,
    [dir="ltr"] .responsive-table td.col-actions {
        text-align: right;
    }
</style>
@endpush

@section('content')
<div style="margin-bottom: 2rem;">

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div class="custom-tabs" style="margin-bottom: 0;">
            <button class="tab-btn active" onclick="switchTab(event, 'meetings-tab')">{{ __('messages.meetings_requests_tab') }}</button>
            <button class="tab-btn" onclick="switchTab(event, 'summons-tab')">{{ __('messages.summons_tab') }}</button>
        </div>

        <button onclick="openCreateSummonModal()" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background-color: #ef4444; color: white; border: none; border-radius: 0.75rem; font-weight: bold; font-size: 0.95rem; cursor: pointer; transition: transform 0.2s, background-color 0.2s; box-shadow: 0 4px 12px rgba(239,68,68,0.25);">
            <i class="fa-solid fa-plus" style="font-size: 1.1rem;"></i>
            {{ __('messages.new_summon_btn') }}
        </button>
    </div>

    <div class="grid-layout">
        {{-- Tables Container --}}
        <div>
            {{-- Meetings Tab --}}
            <div id="meetings-tab" class="tab-content active">
                <div class="table-container">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 5px; height: 20px; background-color: #f2f20d; border-radius: 5px;"></span>
                        {{ __('messages.hod_meetings_requests_title') }}
                    </h3>

                    @if($meetings->isEmpty())
                        <div style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                            {{ __('messages.no_meetings_requests') }}
                        </div>
                    @else
                        <div style="overflow-x: auto;">
                            <table class="responsive-table">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); font-weight: 700;">
                                        <th style="padding: 1rem;">{{ __('messages.parent_col') }}</th>
                                        <th style="padding: 1rem;">{{ __('messages.student') }}</th>
                                        <th style="padding: 1rem;">{{ __('messages.subject_reason_col') }}</th>
                                        <th style="padding: 1rem;">{{ __('messages.visit_date_col') }}</th>
                                        <th style="padding: 1rem;">{{ __('messages.status') }}</th>
                                        <th class="col-actions" style="padding: 1rem;">{{ __('messages.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($meetings as $meeting)
                                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;">
                                            <td style="padding: 1rem; font-weight: bold;">{{ $meeting->parent->full_name ?? __('messages.parent_col') }}</td>
                                            <td style="padding: 1rem;">{{ $meeting->student->user->full_name ?? __('messages.unknown') }}</td>
                                            <td style="padding: 1rem;">
                                                <div style="font-weight: bold;">{{ $meeting->subject }}</div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ $meeting->reason }}</div>
                                            </td>
                                            <td style="padding: 1rem;">
                                                @if($meeting->scheduled_at)
                                                    <span style="color: #10b981; font-weight: bold;">{{ date('Y-m-d h:i A', strtotime($meeting->scheduled_at)) }}</span>
                                                @elseif($meeting->preferred_date)
                                                    <span style="color: var(--text-secondary);">{{ __('messages.preferred_date_label', ['date' => date('Y-m-d', strtotime($meeting->preferred_date))]) }}</span>
                                                @else
                                                    <span style="color: var(--text-secondary);">{{ __('messages.unspecified') }}</span>
                                                @endif
                                            </td>
                                            <td style="padding: 1rem;">
                                                @if($meeting->status === 'pending')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(245,158,11,0.1); color: #f59e0b; font-size: 0.75rem; font-weight: 700;">{{ __('messages.status_meeting_pending') }}</span>
                                                @elseif($meeting->status === 'approved')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(16,185,129,0.1); color: #10b981; font-size: 0.75rem; font-weight: 700;">{{ __('messages.status_meeting_approved') }}</span>
                                                @elseif($meeting->status === 'rejected')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.75rem; font-weight: 700;">{{ __('messages.status_meeting_rejected') }}</span>
                                                @elseif($meeting->status === 'completed')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(59,130,246,0.1); color: #3b82f6; font-size: 0.75rem; font-weight: 700;">{{ __('messages.status_meeting_completed') }}</span>
                                                @endif
                                            </td>
                                            <td class="col-actions" style="padding: 1rem;">
                                                <button onclick="openResponseModal({{ json_encode($meeting) }})"
                                                        style="padding: 0.4rem 0.8rem; border: none; border-radius: 0.5rem; background-color: var(--bg-secondary); color: var(--text-primary); cursor: pointer; font-weight: bold; font-size: 0.8rem; transition: background-color 0.2s;">
                                                    {{ __('messages.reply_btn') }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Summons Tab --}}
            <div id="summons-tab" class="tab-content">
                <div class="table-container">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 5px; height: 20px; background-color: #ef4444; border-radius: 5px;"></span>
                        {{ __('messages.dept_summons_title') }}
                    </h3>

                    @if($summons->isEmpty())
                        <div style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                            {{ __('messages.no_summons_yet') }}
                        </div>
                    @else
                        <div style="overflow-x: auto;">
                            <table class="responsive-table">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); font-weight: 700;">
                                        <th style="padding: 1rem;">{{ __('messages.sender_col') }}</th>
                                        <th style="padding: 1rem;">{{ __('messages.student') }}</th>
                                        <th style="padding: 1rem;">{{ __('messages.reason_details_col') }}</th>
                                        <th style="padding: 1rem;">{{ __('messages.date') }}</th>
                                        <th class="col-actions" style="padding: 1rem;">{{ __('messages.parent_attendance_status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summons as $summon)
                                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;">
                                            <td style="padding: 1rem; font-weight: bold;">{{ $summon->sender->full_name ?? __('messages.admin') }}</td>
                                            <td style="padding: 1rem;">{{ $summon->student->user->full_name ?? __('messages.unknown') }}</td>
                                            <td style="padding: 1rem;">
                                                <div style="font-weight: bold;">{{ $summon->reason_title }}</div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ $summon->details }}</div>
                                            </td>
                                            <td style="padding: 1rem;">{{ $summon->summon_date ? date('Y-m-d', strtotime($summon->summon_date)) : __('messages.unspecified') }}</td>
                                            <td class="col-actions" style="padding: 1rem;">
                                                @if($summon->status === 'sent')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(59,130,246,0.1); color: #3b82f6; font-size: 0.75rem; font-weight: 700;">{{ __('messages.summon_sent') }}</span>
                                                @elseif($summon->status === 'acknowledged')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(16,185,129,0.1); color: #10b981; font-size: 0.75rem; font-weight: 700;">{{ __('messages.summon_acknowledged') }}</span>
                                                @elseif($summon->status === 'cancelled')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.75rem; font-weight: 700;">{{ __('messages.summon_cancelled') }}</span>
                                                @elseif($summon->status === 'attended')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(100,116,139,0.1); color: #64748b; font-size: 0.75rem; font-weight: 700;">{{ __('messages.summon_attended') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Summon Modal --}}
<div id="createSummonModal" style="position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.75); backdrop-filter: blur(8px);">
    <div class="modal-card" style="border-radius: 1.25rem; padding: 1.75rem; width: 100%; max-width: 520px; font-size: 0.85rem; display: flex; flex-direction: column; gap: 1.2rem; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #ef4444; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-user-plus"></i>
                {{ __('messages.new_summon_btn') }}
            </h3>
            <button onclick="closeCreateSummonModal()" style="border: none; background: transparent; color: var(--text-secondary); cursor: pointer; font-size: 1.6rem; line-height: 1;">&times;</button>
        </div>

        <form action="{{ route('hod.summons.store') }}" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">{{ __('messages.select_program_first') }}</label>
                <select id="program_filter_select" onchange="filterStudentsByProgram(this.value)" style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;">
                    <option value="all" selected>{{ __('messages.all_dept_programs') }}</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">{{ __('messages.select_student_summon') }}</label>
                <select name="student_id" id="student_select" required style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;">
                    <option value="" disabled selected>{{ __('messages.select_student_summon') }}</option>
                    @foreach($students as $st)
                        <option value="{{ $st->student_id }}" data-program-id="{{ $st->program_id }}">
                            {{ $st->user->full_name ?? __('messages.unknown') }} - [{{ $st->program->name ?? $st->level }}]
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">{{ __('messages.summon_reason_title') }}</label>
                <input type="text" name="reason_title" required placeholder="{{ __('messages.summon_reason_ph') }}"
                       style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;" />
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">{{ __('messages.summon_details_label') }}</label>
                <textarea name="details" required rows="4" placeholder="{{ __('messages.summon_details_ph') }}"
                          style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;"></textarea>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">{{ __('messages.required_visit_date') }}</label>
                <input type="date" name="summon_date"
                       style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;" />
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 0.5rem;">
                <button type="button" onclick="closeCreateSummonModal()" style="padding: 0.6rem 1.2rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: inherit; font-weight: bold; cursor: pointer;">{{ __('messages.cancel') }}</button>
                <button type="submit" style="padding: 0.6rem 1.4rem; border: none; border-radius: 0.5rem; background-color: #ef4444; color: white; font-weight: bold; cursor: pointer;">{{ __('messages.send_summon_now') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Respond to Meeting Modal --}}
<div id="responseModal" style="position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.75); backdrop-filter: blur(8px);">
    <div class="modal-card" style="border-radius: 1.25rem; padding: 1.5rem; width: 100%; max-width: 450px; font-size: 0.85rem; display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <h3 id="modalTitle" style="font-size: 1rem; font-weight: 700;">{{ __('messages.respond_parent_meeting') }}</h3>
            <button onclick="closeResponseModal()" style="border: none; background: transparent; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">&times;</button>
        </div>

        <form id="modalForm" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">{{ __('messages.final_decision_label') }}</label>
                <select name="status" id="modalStatus" required style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);">
                    <option value="approved">{{ __('messages.approve_confirm_meeting') }}</option>
                    <option value="rejected">{{ __('messages.reject_meeting_opt') }}</option>
                    <option value="completed">{{ __('messages.complete_meeting_opt') }}</option>
                </select>
            </div>

            <div id="dateInputContainer">
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">{{ __('messages.confirmed_datetime_label') }}</label>
                <input type="datetime-local" name="scheduled_at" id="modalScheduledAt"
                       style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);" />
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">{{ __('messages.parent_notes_label') }}</label>
                <textarea name="admin_response" id="modalAdminResponse" rows="3" placeholder="{{ __('messages.parent_notes_ph') }}"
                          style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);"></textarea>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 0.75rem; margin-top: 0.5rem;">
                <button type="button" onclick="closeResponseModal()" style="padding: 0.6rem 1.2rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary); font-weight: bold; cursor: pointer;">{{ __('messages.cancel') }}</button>
                <button type="submit" style="padding: 0.6rem 1.2rem; border: none; border-radius: 0.5rem; background-color: #f2f20d; color: #1a1a1a; font-weight: bold; cursor: pointer;">{{ __('messages.save_response_btn') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    const appointmentStrings = {
        replyTo: "{{ __('messages.reply_to_meeting_for', ['name' => ':name']) }}",
        pastDateAlert: "{{ __('messages.past_date_alert') }}"
    };

    function switchTab(evt, tabName) {
        const contents = document.getElementsByClassName("tab-content");
        for (let i = 0; i < contents.length; i++) {
            contents[i].classList.remove("active");
        }
        const buttons = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove("active");
        }
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    function getCurrentLocalDateTimeString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function getCurrentLocalDateString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function openResponseModal(meeting) {
        document.getElementById('modalForm').action = `/hod/appointments/${meeting.id}/respond`;
        const parentName = meeting.parent ? meeting.parent.full_name : '';
        document.getElementById('modalTitle').innerText = appointmentStrings.replyTo.replace(':name', parentName);
        document.getElementById('modalStatus').value = meeting.status;
        document.getElementById('modalAdminResponse').value = meeting.admin_response || '';
        
        const scheduledInput = document.getElementById('modalScheduledAt');
        const currentNowStr = getCurrentLocalDateTimeString();
        
        scheduledInput.min = currentNowStr;

        if(meeting.scheduled_at) {
            const date = new Date(meeting.scheduled_at);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            scheduledInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        } else {
            scheduledInput.value = currentNowStr;
        }

        document.getElementById('responseModal').style.display = 'flex';
    }

    function closeResponseModal() {
        document.getElementById('responseModal').style.display = 'none';
    }

    document.getElementById('modalStatus').addEventListener('change', function() {
        const container = document.getElementById('dateInputContainer');
        if(this.value === 'rejected') {
            container.style.display = 'none';
        } else {
            container.style.display = 'block';
        }
    });

    document.getElementById('modalForm').addEventListener('submit', function(e) {
        const status = document.getElementById('modalStatus').value;
        const scheduledInput = document.getElementById('modalScheduledAt');
        if (status === 'approved' && scheduledInput.value) {
            const selectedDate = new Date(scheduledInput.value);
            const now = new Date();
            if (selectedDate.getTime() < now.getTime() - 60000) {
                e.preventDefault();
                alert(appointmentStrings.pastDateAlert);
                return false;
            }
        }
    });

    function openCreateSummonModal() {
        const summonDateInput = document.querySelector('input[name="summon_date"]');
        if (summonDateInput) {
            const todayStr = getCurrentLocalDateString();
            summonDateInput.min = todayStr;
            if (!summonDateInput.value) {
                summonDateInput.value = todayStr;
            }
        }
        document.getElementById('createSummonModal').style.display = 'flex';
    }

    function closeCreateSummonModal() {
        document.getElementById('createSummonModal').style.display = 'none';
    }

    function filterStudentsByProgram(selectedProgramId) {
        const studentSelect = document.getElementById('student_select');
        const options = studentSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return; // skip placeholder option

            if (selectedProgramId === 'all') {
                opt.style.display = '';
                opt.disabled = false;
            } else {
                const progId = opt.getAttribute('data-program-id');
                if (progId == selectedProgramId) {
                    opt.style.display = '';
                    opt.disabled = false;
                } else {
                    opt.style.display = 'none';
                    opt.disabled = true;
                }
            }
        });

        studentSelect.value = '';
    }
</script>
@endsection
