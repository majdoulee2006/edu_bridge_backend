@extends('layouts.affairs')
@section('title', __('messages.leave_requests_title'))

@push('styles')
<style>
    .leaves-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .page-header h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    
    /* Filters */
    .filters {
        display: flex;
        gap: 0.8rem;
        margin-bottom: 2rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    .filter-chip {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border: 2px solid transparent;
        padding: 0.6rem 1.2rem;
        border-radius: 2rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .filter-chip.active, .filter-chip:hover {
        background: var(--bg-primary);
        border-color: var(--accent-color);
        color: var(--text-primary);
    }

    /* Grid */
    .leaves-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    /* Leave Card */
    .leave-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        transition: transform 0.3s;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .leave-card:hover {
        transform: translateY(-5px);
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--accent-color);
        color: var(--primary-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .user-info {
        flex: 1;
    }
    .user-name {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    .user-role {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
    
    .status-badge {
        padding: 0.3rem 0.8rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        font-weight: 800;
        white-space: nowrap;
    }
    .status-pending { background: rgba(252, 227, 0, 0.2); color: #d97706; }
    .status-approved { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .status-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

    .leave-details {
        background: var(--bg-primary);
        padding: 1rem;
        border-radius: 0.8rem;
        margin-bottom: 1.5rem;
        flex: 1;
    }
    .detail-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    .detail-row i {
        color: var(--accent-color);
        width: 20px;
        text-align: center;
    }
    .leave-reason {
        margin-top: 0.8rem;
        font-size: 0.85rem;
        line-height: 1.5;
        color: var(--text-primary);
        border-top: 1px dashed var(--border-color);
        padding-top: 0.8rem;
    }

    .card-actions {
        display: flex;
        gap: 0.5rem;
    }
    .btn-action {
        flex: 1;
        padding: 0.75rem;
        border: none;
        border-radius: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        transition: opacity 0.2s, transform 0.1s;
    }
    .btn-action:active { transform: scale(0.98); }
    
    .btn-approve { background: #10b981; color: white; }
    .btn-approve:hover { opacity: 0.9; }
    
    .btn-reject { background: #ef4444; color: white; }
    .btn-reject:hover { opacity: 0.9; }
    
    .btn-view { background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); }
    .btn-view:hover { background: var(--accent-color); color: var(--primary-dark); border-color: transparent; }

    /* Modal Styles */
    .modal-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex; justify-content: center; align-items: center;
        z-index: 1000;
        opacity: 0; pointer-events: none;
        transition: opacity 0.3s;
    }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    .modal-content {
        background: var(--bg-secondary);
        width: 90%; max-width: 450px;
        border-radius: 1.5rem;
        padding: 2rem;
        transform: translateY(-20px);
        transition: transform 0.3s;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .modal-overlay.active .modal-content { transform: translateY(0); }
    .modal-icon {
        font-size: 3.5rem;
        margin-bottom: 1rem;
        color: var(--accent-color);
    }
    .modal-title { font-size: 1.4rem; color: var(--text-primary); margin-bottom: 0.5rem; font-weight: 800; }
    .modal-desc { color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem; }
    
    .modal-actions {
        display: flex;
        gap: 1rem;
    }
    .modal-btn {
        flex: 1;
        padding: 0.8rem;
        border: none;
        border-radius: 0.8rem;
        font-weight: 800;
        cursor: pointer;
    }
    .modal-btn-cancel { background: var(--bg-primary); color: var(--text-primary); }
    .modal-btn-confirm { background: var(--accent-color); color: var(--primary-dark); }
</style>
@endpush

@section('content')
<div class="leaves-container">
    <!-- Header -->
    <div class="page-header">
        <h2>{{ __('messages.leave_requests_title') }}</h2>
    </div>

    <!-- Advanced Filter Bar -->
    <div style="background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.5rem; border: 1px solid var(--border-color); margin-bottom: 1.5rem; box-shadow: var(--shadow);">
        <form method="GET" action="{{ route('affairs.leaves') }}" id="filterForm" style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; align-items: end;">
                
                {{-- اختيار القسم --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem;">
                        <i class="fa-solid fa-building-user" style="color: var(--accent-color);"></i> {{ __('messages.filter_by_dept') }}
                    </label>
                    <select name="department_id" style="width: 100%; padding: 0.65rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                        <option value="">{{ __('messages.all_departments') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}" {{ $deptId == $dept->department_id ? 'selected' : '' }}>
                                {{ $dept->display_name ?? $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- نوع التصفية حسب التاريخ --}}
                <div>
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem;">
                        <i class="fa-solid fa-calendar-days" style="color: var(--accent-color);"></i> {{ __('messages.time_filter_type') }}
                    </label>
                    <select name="date_mode" id="date_mode_select" onchange="toggleDateInputs(this.value)" style="width: 100%; padding: 0.65rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                        <option value="all" {{ $dateMode === 'all' ? 'selected' : '' }}>{{ __('messages.all') }}</option>
                        <option value="single_date" {{ $dateMode === 'single_date' ? 'selected' : '' }}>{{ __('messages.single_day') }}</option>
                        <option value="date_range" {{ $dateMode === 'date_range' ? 'selected' : '' }}>{{ __('messages.date_range') }}</option>
                        <option value="week" {{ $dateMode === 'week' ? 'selected' : '' }}>{{ __('messages.specific_week') }}</option>
                    </select>
                </div>

                {{-- مدخل يوم معين --}}
                <div id="single_date_container" style="display: {{ $dateMode === 'single_date' ? 'block' : 'none' }};">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem;">{{ __('messages.selected_date') }}</label>
                    <input type="date" name="single_date" value="{{ $singleDate }}" style="width: 100%; padding: 0.65rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;" />
                </div>

                {{-- مدخل بين تاريخين --}}
                <div id="date_range_start_container" style="display: {{ $dateMode === 'date_range' ? 'block' : 'none' }};">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem;">{{ __('messages.from_date') }}</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" style="width: 100%; padding: 0.65rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;" />
                </div>
                <div id="date_range_end_container" style="display: {{ $dateMode === 'date_range' ? 'block' : 'none' }};">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem;">{{ __('messages.to_date') }}</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" style="width: 100%; padding: 0.65rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;" />
                </div>

                {{-- مدخل أسبوع معين --}}
                <div id="week_container" style="display: {{ $dateMode === 'week' ? 'block' : 'none' }};">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem;">{{ __('messages.select_week_day') }}</label>
                    <input type="date" name="week_date" value="{{ $weekDate }}" style="width: 100%; padding: 0.65rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;" />
                </div>

                {{-- أزرار التحكم --}}
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" style="flex: 1; padding: 0.65rem 1.25rem; border: none; border-radius: 0.75rem; background: var(--accent-color); color: #101924; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                        <i class="fa-solid fa-filter"></i> {{ __('messages.apply_filter') }}
                    </button>
                    @if($deptId || $dateMode !== 'all')
                        <a href="{{ route('affairs.leaves') }}" style="padding: 0.65rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-secondary); font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center;" title="{{ __('messages.reset_filters') }}">
                            <i class="fa-solid fa-rotate-right"></i>
                        </a>
                    @endif
                </div>
        </form>
    </div>

    <!-- Thin Divider Line -->
    <div style="height: 1px; background: var(--border-color); opacity: 0.6; margin: 2rem 0 2.25rem 0;"></div>

    <!-- Filters -->
    <div class="filters">
        <button class="filter-chip active" data-status="all">{{ __('messages.all') }}</button>
        <button class="filter-chip" data-status="pending">{{ __('messages.filter_pending') }}</button>
        <button class="filter-chip" data-status="approved">{{ __('messages.filter_approved') }}</button>
        <button class="filter-chip" data-status="rejected">{{ __('messages.filter_rejected') }}</button>
    </div>

    {{-- Stats Bar --}}
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.8rem 1.5rem; font-weight: 700; color: #d97706;">{{ __('messages.pending_leaves_badge', ['count' => $pendingCount]) }}</div>
        <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.8rem 1.5rem; font-weight: 700; color: #10b981;">{{ __('messages.approved_leaves_badge', ['count' => $approvedCount]) }}</div>
        <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.8rem 1.5rem; font-weight: 700; color: #ef4444;">{{ __('messages.rejected_leaves_badge', ['count' => $rejectedCount]) }}</div>
    </div>

    <!-- Grid -->
    <div class="leaves-grid" id="leavesGrid">

        @forelse($leaves as $leave)
            @php
                $userName = $leave->student_name ?? __('messages.unknown_user');
                $statusClass = match($leave->status) {
                    'approved' => 'status-approved',
                    'rejected' => 'status-rejected',
                    default    => 'status-pending',
                };
                $statusLabel = match($leave->status) {
                    'approved' => __('messages.status_approved'),
                    'rejected' => __('messages.status_rejected'),
                    default    => __('messages.pending'),
                };
            @endphp
            <div class="leave-card" data-status="{{ in_array($leave->status, ['pending', 'pending_hod', 'pending_affairs']) ? 'pending' : ($leave->status == 'approved' ? 'approved' : 'rejected') }}">
                <div class="card-header">
                    <div class="user-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="user-info">
                        <div class="user-name">{{ $userName }}</div>
                        <div class="user-role">{{ __('messages.role_student') }}</div>
                    </div>
                    <div class="status-badge {{ $statusClass }}">{{ $statusLabel }}</div>
                </div>
                <div class="leave-details">
                    <div class="detail-row"><i class="fa-solid fa-notes-medical"></i> <span>{{ __('messages.absence_request') }}</span></div>
                    <div class="detail-row"><i class="fa-solid fa-calendar-days"></i> <span>{{ __('messages.leave_date') }} {{ $leave->date ? \Carbon\Carbon::parse($leave->date)->format('Y-m-d') : __('messages.not_specified') }}</span></div>
                    @if(!empty($leave->created_at))
                    <div class="detail-row" style="font-weight: 700; color: var(--text-primary);"><i class="fa-regular fa-clock"></i> <span>{{ __('messages.submission_time') }} {{ \Carbon\Carbon::parse($leave->created_at)->format('Y-m-d - h:i A') }}</span></div>
                    @endif
                    <div class="leave-reason">
                        <strong>{{ __('messages.reason_label') }}</strong> {{ $leave->reason ?? __('messages.no_reason_provided') }}
                    </div>

                    {{-- إظهار رأي المراحل السابقة (موافقة ولي الأمر ورئيس القسم) --}}
                    @if(in_array($leave->status, ['pending_affairs', 'approved']))
                    <div style="margin-top:0.75rem;background:#f0fdf4;color:#16a34a;padding:0.5rem 0.8rem;border-radius:0.6rem;font-size:0.82rem;font-weight:700;display:flex;align-items:center;gap:0.4rem;border:1px solid #bbf7d0;">
                        <i class="fa-solid fa-circle-check"></i> {{ __('messages.opinions_parent_hod_approved') }}
                    </div>
                    @elseif($leave->status === 'pending_hod')
                    <div style="margin-top:0.75rem;background:#eff6ff;color:#2563eb;padding:0.5rem 0.8rem;border-radius:0.6rem;font-size:0.82rem;font-weight:700;display:flex;align-items:center;gap:0.4rem;border:1px solid #bfdbfe;">
                        <i class="fa-solid fa-clock"></i> {{ __('messages.opinions_parent_approved_waiting_hod') }}
                    </div>
                    @elseif($leave->status === 'pending_parent')
                    <div style="margin-top:0.75rem;background:#fefce8;color:#ca8a04;padding:0.5rem 0.8rem;border-radius:0.6rem;font-size:0.82rem;font-weight:700;display:flex;align-items:center;gap:0.4rem;border:1px solid #fef08a;">
                        <i class="fa-solid fa-clock"></i> {{ __('messages.opinions_waiting_parent') }}
                    </div>
                    @endif
                </div>
                <div class="card-actions">
                    @if(in_array($leave->status, ['pending', 'pending_hod', 'pending_affairs', 'pending_parent']))
                        <form method="POST" action="{{ route('affairs.leaves.status', $leave->id) }}" style="flex:1;">
                            @csrf
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn-action btn-approve" style="width:100%;"><i class="fa-solid fa-check"></i> {{ __('messages.approve_excuse') }}</button>
                        </form>
                        <form method="POST" action="{{ route('affairs.leaves.status', $leave->id) }}" style="flex:1;">
                            @csrf
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn-action btn-reject" style="width:100%;"><i class="fa-solid fa-xmark"></i> {{ __('messages.reject_request') }}</button>
                        </form>
                    @else
                        <button type="button" onclick="openLeaveDetails('{{ addslashes($userName) }}', '{{ $leave->date ? \Carbon\Carbon::parse($leave->date)->locale(app()->getLocale())->translatedFormat('d F Y') : __('messages.not_specified') }}', '{{ addslashes($leave->reason ?? __('messages.no_reason_provided')) }}', '{{ $statusLabel }}', '{{ $leave->status }}')" class="btn-action btn-view" style="flex:1;"><i class="fa-solid fa-eye"></i> {{ __('messages.processed_view_details') }}</button>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-secondary);">
                <i class="fa-solid fa-inbox" style="font-size: 3rem; opacity: 0.4; margin-bottom: 1rem;"></i>
                <p style="font-size: 1.1rem;">{{ __('messages.no_leaves_found') }}</p>
            </div>
        @endforelse

    </div>
</div>

<!-- Details Modal -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content" style="text-align: {{ app()->getLocale() === 'en' ? 'left' : 'right' }};">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-file-medical" style="color: var(--accent-color);"></i> {{ __('messages.leave_details_modal_title') }}
            </h3>
            <button onclick="closeDetailsModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-secondary);"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="background: var(--bg-primary); padding: 0.85rem 1rem; border-radius: 0.8rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 700;">{{ __('messages.student_name') }}</span>
                <span id="dtStudentName" style="color: var(--text-primary); font-size: 0.95rem; font-weight: 800;"></span>
            </div>

            <div style="background: var(--bg-primary); padding: 0.85rem 1rem; border-radius: 0.8rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 700;">{{ __('messages.absence_date') }}</span>
                <span id="dtLeaveDate" style="color: var(--text-primary); font-size: 0.95rem; font-weight: 800;"></span>
            </div>

            <div style="background: var(--bg-primary); padding: 0.85rem 1rem; border-radius: 0.8rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 700;">{{ __('messages.status') }}</span>
                <span id="dtStatus" class="status-badge"></span>
            </div>

            <div style="background: var(--bg-primary); padding: 1rem; border-radius: 0.8rem;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem;">{{ __('messages.absence_reason') }}</div>
                <div id="dtReason" style="color: var(--text-primary); font-size: 0.9rem; line-height: 1.5; font-weight: 600;"></div>
            </div>
        </div>

        <button onclick="closeDetailsModal()" style="width: 100%; margin-top: 1.5rem; padding: 0.8rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.8rem; font-weight: 800; cursor: pointer;">
            {{ __('messages.close') }}
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleDateInputs(mode) {
        document.getElementById('single_date_container').style.display = (mode === 'single_date') ? 'block' : 'none';
        document.getElementById('date_range_start_container').style.display = (mode === 'date_range') ? 'block' : 'none';
        document.getElementById('date_range_end_container').style.display = (mode === 'date_range') ? 'block' : 'none';
        document.getElementById('week_container').style.display = (mode === 'week') ? 'block' : 'none';
    }

    // Filtering Logic
    const filterChips = document.querySelectorAll('.filter-chip');
    const cards = document.querySelectorAll('.leave-card');

    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');

            const status = chip.getAttribute('data-status');

            cards.forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                if (status === 'all' || cardStatus === status) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    function openLeaveDetails(name, date, reason, statusLabel, statusVal) {
        document.getElementById('dtStudentName').textContent = name;
        document.getElementById('dtLeaveDate').textContent = date;
        document.getElementById('dtReason').textContent = reason;
        
        const dtStatus = document.getElementById('dtStatus');
        dtStatus.textContent = statusLabel;
        dtStatus.className = 'status-badge ' + (statusVal === 'approved' ? 'status-approved' : 'status-rejected');

        const dtModal = document.getElementById('detailsModal');
        if (dtModal) dtModal.classList.add('active');
    }

    function closeDetailsModal() {
        const dtModal = document.getElementById('detailsModal');
        if (dtModal) dtModal.classList.remove('active');
    }

    const detailsModal = document.getElementById('detailsModal');
    if (detailsModal) {
        detailsModal.addEventListener('click', (e) => {
            if (e.target === detailsModal) closeDetailsModal();
        });
    }
</script>
@endpush
