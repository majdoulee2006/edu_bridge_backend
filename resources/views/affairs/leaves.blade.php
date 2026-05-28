@extends('layouts.affairs')
@section('title', 'طلبات الإجازة')

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
        text-align: center;
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
        <h2>مراجعة طلبات الإجازة</h2>
    </div>

    <!-- Filters -->
    <div class="filters">
        <button class="filter-chip active" data-status="all">الكل</button>
        <button class="filter-chip" data-status="pending">قيد الانتظار</button>
        <button class="filter-chip" data-status="approved">المقبولة</button>
        <button class="filter-chip" data-status="rejected">المرفوضة</button>
    </div>

    {{-- Stats Bar --}}
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.8rem 1.5rem; font-weight: 700; color: #d97706;">⏳ قيد الانتظار: {{ $pendingCount }}</div>
        <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.8rem 1.5rem; font-weight: 700; color: #10b981;">✅ مقبولة: {{ $approvedCount }}</div>
        <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.8rem 1.5rem; font-weight: 700; color: #ef4444;">❌ مرفوضة: {{ $rejectedCount }}</div>
    </div>

    {{-- Success/Error messages --}}
    @if(session('success'))
        <div style="background: rgba(16,185,129,0.1); border: 1px solid #10b981; border-radius: 0.75rem; padding: 1rem 1.5rem; margin-bottom: 1.5rem; color: #10b981; font-weight: 700;">
            <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Grid -->
    <div class="leaves-grid" id="leavesGrid">

        @forelse($leaves as $leave)
            @php
                $userName = $leave->student->user->full_name ?? 'مستخدم غير معروف';
                $statusClass = match($leave->status) {
                    'approved' => 'status-approved',
                    'rejected' => 'status-rejected',
                    default    => 'status-pending',
                };
                $statusLabel = match($leave->status) {
                    'approved' => 'مقبولة',
                    'rejected' => 'مرفوضة',
                    default    => 'قيد الانتظار',
                };
            @endphp
            <div class="leave-card" data-status="{{ $leave->status == 'pending' ? 'pending' : ($leave->status == 'approved' ? 'approved' : 'rejected') }}">
                <div class="card-header">
                    <div class="user-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="user-info">
                        <div class="user-name">{{ $userName }}</div>
                        <div class="user-role">طالب</div>
                    </div>
                    <div class="status-badge {{ $statusClass }}">{{ $statusLabel }}</div>
                </div>
                <div class="leave-details">
                    <div class="detail-row"><i class="fa-solid fa-notes-medical"></i> <span>طلب غياب</span></div>
                    <div class="detail-row"><i class="fa-solid fa-calendar-days"></i> <span>{{ $leave->date ? $leave->date->format('d M Y') : 'غير محدد' }}</span></div>
                    <div class="leave-reason">
                        <strong>السبب:</strong> {{ $leave->reason ?? 'لم يذكر سبب.' }}
                    </div>
                </div>
                <div class="card-actions">
                    @if($leave->status === 'pending')
                        <form method="POST" action="{{ route('affairs.leaves.status', $leave->request_id) }}" style="flex:1;">
                            @csrf
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn-action btn-approve" style="width:100%;"><i class="fa-solid fa-check"></i> موافقة</button>
                        </form>
                        <form method="POST" action="{{ route('affairs.leaves.status', $leave->request_id) }}" style="flex:1;">
                            @csrf
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn-action btn-reject" style="width:100%;"><i class="fa-solid fa-xmark"></i> رفض</button>
                        </form>
                    @else
                        <button class="btn-action btn-view" style="flex:1;"><i class="fa-solid fa-eye"></i> تمت المعالجة</button>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-secondary);">
                <i class="fa-solid fa-inbox" style="font-size: 3rem; opacity: 0.4; margin-bottom: 1rem;"></i>
                <p style="font-size: 1.1rem;">لا توجد طلبات إجازة حتى الآن.</p>
            </div>
        @endforelse

    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-content">
        <div class="modal-icon" id="modalIcon"><i class="fa-solid fa-circle-question"></i></div>
        <h3 class="modal-title" id="modalTitle">تأكيد الإجراء</h3>
        <p class="modal-desc" id="modalDesc">هل أنت متأكد من رغبتك في اتخاذ هذا الإجراء على طلب الإجازة؟</p>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="closeConfirmModal()">إلغاء</button>
            <button class="modal-btn modal-btn-confirm" onclick="closeConfirmModal()">تأكيد</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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

    // Modal Logic
    const modal = document.getElementById('confirmModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalIcon = document.getElementById('modalIcon');
    const confirmBtn = document.querySelector('.modal-btn-confirm');

    function openConfirmModal(actionType) {
        modal.classList.add('active');
        
        if (actionType === 'موافقة') {
            modalTitle.innerText = 'الموافقة على الطلب';
            modalDesc.innerText = 'هل أنت متأكد أنك تريد الموافقة على طلب الإجازة؟ سيتم إشعار الموظف بذلك.';
            modalIcon.innerHTML = '<i class="fa-solid fa-check-circle" style="color: #10b981;"></i>';
            confirmBtn.style.background = '#10b981';
            confirmBtn.style.color = 'white';
        } else {
            modalTitle.innerText = 'رفض الطلب';
            modalDesc.innerText = 'هل أنت متأكد أنك تريد رفض طلب الإجازة؟ يرجى التواصل مع الموظف لتوضيح السبب.';
            modalIcon.innerHTML = '<i class="fa-solid fa-circle-xmark" style="color: #ef4444;"></i>';
            confirmBtn.style.background = '#ef4444';
            confirmBtn.style.color = 'white';
        }
    }

    function closeConfirmModal() {
        modal.classList.remove('active');
    }

    modal.addEventListener('click', (e) => {
        if(e.target === modal) closeConfirmModal();
    });
</script>
@endpush
