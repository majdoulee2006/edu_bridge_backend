@extends('layouts.teacher')

@section('title', __('messages.reports'))

@push('styles')
<style>
    .page-subtitle { color: var(--text-secondary); font-size: 1rem; margin-top: -1.5rem; margin-bottom: 2rem; }

    .tab-switcher {
        display: flex; background: var(--bg-secondary); border-radius: 1rem;
        padding: 0.4rem; margin-bottom: 1.5rem; box-shadow: var(--shadow);
    }
    .tab-btn {
        flex: 1; padding: 0.65rem; text-align: center; border-radius: 0.75rem;
        font-weight: 700; cursor: pointer; border: none; background: transparent;
        color: var(--text-secondary); display: flex; align-items: center;
        justify-content: center; gap: 0.4rem; transition: all 0.25s; font-family: inherit;
    }
    .tab-btn.active { background: var(--accent-color); color: #1a1a1a; }

    .request-card {
        background: var(--bg-secondary); border-radius: 1.25rem;
        padding: 1.25rem 1.5rem; margin-bottom: 1rem; box-shadow: var(--shadow);
    }
    .request-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; }
    .student-avatar {
        width: 46px; height: 46px; border-radius: 50%;
        background: #eff6ff; color: #2563eb;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; font-weight: 700; flex-shrink: 0;
    }
    .student-avatar.behavioral { background: #fdf4ff; color: #9333ea; }
    .request-info h5 { font-size: 1rem; font-weight: 700; margin-bottom: 0.15rem; }
    .request-info p  { font-size: 0.83rem; color: var(--text-secondary); }

    .badge { padding: 0.2rem 0.7rem; border-radius: 1rem; font-size: 0.78rem; font-weight: 700; }
    .badge-pending  { background: #fefce8; color: #ca8a04; }
    .badge-done     { background: #f0fdf4; color: #16a34a; }
    .badge-academic { background: #eff6ff; color: #2563eb; }
    .badge-behavioral { background: #fdf4ff; color: #9333ea; }

    .notes-box {
        background: var(--bg-primary); border-radius: 0.75rem; padding: 0.75rem 1rem;
        font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 0.75rem;
        border-right: 3px solid var(--accent-color);
    }

    /* Form */
    .report-form { margin-top: 0.75rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem; }
    .form-label { display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.88rem; color: var(--text-secondary); }
    .form-textarea {
        width: 100%; padding: 0.7rem 0.9rem; border-radius: 0.75rem;
        border: 1px solid var(--border-color); background: var(--bg-primary);
        color: var(--text-primary); font-family: inherit; font-size: 0.92rem;
        box-sizing: border-box; resize: vertical; min-height: 90px;
    }
    .form-textarea:focus { outline: none; border-color: var(--accent-color); }
    .btn-submit {
        background: var(--accent-color); color: #1a1a1a; padding: 0.6rem 1.5rem;
        border-radius: 0.75rem; border: none; font-weight: 700; cursor: pointer;
        font-size: 0.95rem; font-family: inherit; margin-top: 0.5rem;
    }

    .completed-notes {
        background: #f0fdf4; border-radius: 0.75rem; padding: 0.75rem 1rem;
        font-size: 0.88rem; color: #166534; border-right: 3px solid #16a34a;
    }

    .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
</style>
@endpush

@section('content')
    <p class="page-subtitle">{{ __('messages.incoming_report_requests') }} {{ __('messages.tr_from_admin_or_parents') }}</p>

    @php
        $pending   = $requests->where('status', 'pending');
        $completed = $requests->where('status', 'completed');
    @endphp

    {{-- Tab Switcher --}}
    <div class="tab-switcher">
        <button class="tab-btn active" id="btn-pending" onclick="switchTab('pending')">
            <i class="fa-solid fa-clock"></i>
            {{ __('messages.pending_response') }}
            @if($pending->count() > 0)
                <span style="background:#ef4444;color:#fff;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">{{ $pending->count() }}</span>
            @endif
        </button>
        <button class="tab-btn" id="btn-completed" onclick="switchTab('completed')">
            <i class="fa-solid fa-circle-check"></i>
            {{ __('messages.completed_reports') }}
        </button>
    </div>

    {{-- === Tab: Pending === --}}
    <div id="tab-pending">
        @forelse($pending as $req)
        @php 
            $isBehavioral = $req->report_type === 'behavioral'; 
            $requesterType = $req->requester_role_id == 4 ? __('messages.tr_parent') : __('messages.tr_head');
            $requesterName = $req->requester_name ?? __('messages.tr_unknown');
        @endphp
        <div class="request-card">
            <div class="request-header">
                <div class="student-avatar {{ $isBehavioral ? 'behavioral' : '' }}">
                    {{ mb_substr($req->student_name, 0, 1, 'UTF-8') }}
                </div>
                <div class="request-info" style="flex:1;">
                    <h5>{{ $req->student_name }}</h5>
                    <p>{{ $req->student_code }} &bull; {{ \Carbon\Carbon::parse($req->created_at)->format('d/m/Y') }}</p>
                    <p style="margin-top: 0.2rem; font-weight: bold; color: var(--text-primary);">
                        <i class="fa-solid fa-user-tag" style="color: var(--accent-color);"></i> 
                        {{ __('messages.tr_requested_by') }}: {{ $requesterName }} ({{ $requesterType }})
                    </p>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.3rem;">
                    <span class="badge badge-pending">{{ __('messages.pending_response') }}</span>
                    <span class="badge {{ $isBehavioral ? 'badge-behavioral' : 'badge-academic' }}">
                        {{ $isBehavioral ? __('messages.tr_behavioral') : __('messages.tr_academic') }}
                    </span>
                </div>
            </div>

            @if($req->notes)
            <div class="notes-box">
                <strong>{{ __('messages.request_notes') }}</strong> {{ $req->notes }}
            </div>
            @endif

            @if($isBehavioral)
            {{-- المدرب يكتب التقرير السلوكي --}}
            <div class="report-form">
                <form action="{{ route('teacher.reports.submit', $req->id) }}" method="POST">
                    @csrf
                    <label class="form-label"> {{ __('messages.behavioral_report') }} <span style="color:#ef4444">*</span></label>
                    <textarea name="behavioral_notes" required class="form-textarea"
                              placeholder="{{ __('messages.tr_write_behavioral_report') }} {{ $req->student_name }}..."></textarea>
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> {{ __('messages.submit_report_response') }}
                    </button>
                </form>
            </div>
            @else
            {{-- التقرير الأكاديمي يولّده النظام -- المدرب فقط يؤكد --}}
            <div class="report-form">
                <p style="font-size:0.88rem;color:var(--text-secondary);margin-bottom:0.5rem;">
                    <i class="fa-solid fa-info-circle" style="color:#2563eb;"></i>
                    {{ __('messages.tr_academic_auto') }}
                </p>
                <form action="{{ route('teacher.reports.submit', $req->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="behavioral_notes" value="{{ __('messages.academic_report_generated_automatically') }}">
                    <button type="submit" class="btn-submit" style="background:#eff6ff;color:#1d4ed8;">
                        <i class="fa-solid fa-check"></i> {{ __('messages.submit_report_response') }} {{ __('messages.tr_academic_suffix') }}
                    </button>
                </form>
            </div>
            @endif
        </div>
        @empty
        <div class="empty-state">
            <div><i class="fa-solid fa-inbox"></i></div>
            <p>{{ __('messages.tr_no_report_requests') }} {{ __('messages.pending_response') }}.</p>
        </div>
        @endforelse
    </div>

    {{-- === Tab: Completed === --}}
    <div id="tab-completed" style="display:none;">
        @forelse($completed as $req)
        @php $isBehavioral = $req->report_type === 'behavioral'; @endphp
        <div class="request-card">
            <div class="request-header">
                <div class="student-avatar {{ $isBehavioral ? 'behavioral' : '' }}">
                    {{ mb_substr($req->student_name, 0, 1, 'UTF-8') }}
                </div>
                <div class="request-info" style="flex:1;">
                    <h5>{{ $req->student_name }}</h5>
                    <p>{{ $req->student_code }} &bull; {{ \Carbon\Carbon::parse($req->created_at)->format('d/m/Y') }}</p>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.3rem;">
                    <span class="badge badge-done">{{ __('messages.completed') }}</span>
                    <span class="badge {{ $isBehavioral ? 'badge-behavioral' : 'badge-academic' }}">
                        {{ $isBehavioral ? __('messages.tr_behavioral') : __('messages.tr_academic') }}
                    </span>
                </div>
            </div>

            @if($req->submitted_notes)
            <div class="completed-notes">
                <strong>{{ __('messages.sent_report') }}</strong> {{ $req->submitted_notes }}
            </div>
            @endif

            @if(!$isBehavioral && $req->attendance_rate)
            <div class="completed-notes">
                {{ __('messages.tr_attendance_rate') }}: <strong>{{ $req->attendance_rate }}%</strong> &bull;
                {{ __('messages.tr_average') }}: <strong>{{ $req->average_grade }}</strong>
            </div>
            @endif
        </div>
        @empty
        <div class="empty-state">
            <div><i class="fa-solid fa-file-circle-check"></i></div>
            <p>{{ __('messages.no_completed_reports_yet') }}</p>
        </div>
        @endforelse
    </div>

@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        document.getElementById('tab-pending').style.display   = tab === 'pending'   ? 'block' : 'none';
        document.getElementById('tab-completed').style.display = tab === 'completed' ? 'block' : 'none';
        document.getElementById('btn-pending').classList.toggle('active',   tab === 'pending');
        document.getElementById('btn-completed').classList.toggle('active', tab === 'completed');
    }
</script>
@endpush
