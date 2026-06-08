@extends('layouts.hod')

@section('title', 'طلبات الإجازة')

@push('styles')
<style>
    .leave-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .leave-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    
    .requester-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .requester-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .requester-details h4 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .requester-details p {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    
    .leave-badge {
        background-color: #fee2e2;
        color: #b91c1c;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .leave-reason {
        background-color: var(--bg-primary);
        padding: 1rem;
        border-radius: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .leave-reason strong {
        display: block;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .status-btn {
        padding: 0.45rem 1.2rem;
        border-radius: 2rem;
        border: 1px solid var(--border-color);
        background: transparent;
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }
    .status-btn.active {
        background-color: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
    }

    .leave-actions {
        display: flex;
        gap: 1rem;
    }
    
    .btn-approve, .btn-reject {
        flex: 1;
        padding: 0.75rem;
        border-radius: 2rem;
        font-weight: 700;
        font-size: 1.1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        border: none;
    }
    
    .btn-approve {
        background-color: var(--accent-color);
        color: #1a1a1a;
    }
    
    .btn-reject {
        background-color: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
</style>
@endpush

@section('content')

    @if(session('success'))
        <div style="background:#f0fdf4;color:#16a34a;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    {{-- فلتر الحالة --}}
    <div style="display:flex;gap:0.6rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <button class="status-btn active" onclick="setStatus('all',      this)">الكل</button>
        <button class="status-btn"        onclick="setStatus('pending',  this)">معلقة</button>
        <button class="status-btn"        onclick="setStatus('approved', this)">موافق عليها</button>
        <button class="status-btn"        onclick="setStatus('rejected', this)">مرفوضة</button>
    </div>

    {{-- الكروت --}}
    <div id="leaves-container">
        @forelse($allLeaves as $leave)
            @php
                $cat    = $leave->leave_category ?? 'daily';
                $status = $leave->status ?? 'pending';
                $statusGroup = in_array($status, ['pending','pending_hod','pending_parent']) ? 'pending' : $status;
            @endphp
            <div class="leave-item" data-category="{{ $cat }}" data-status="{{ $statusGroup }}">
                @include('hod.partials.leave_card', ['leave' => $leave])
            </div>
        @empty
        @endforelse
    </div>

    <div id="empty-state" style="display:none;text-align:center;padding:3rem;color:var(--text-secondary);">
        <i class="fa-regular fa-folder-open" style="font-size:2.5rem;display:block;margin-bottom:0.75rem;opacity:0.4;"></i>
        لا توجد إجازات تطابق الفلتر المحدد.
    </div>

@endsection

@push('scripts')
<script>
    let activeStatus = 'all';

    function setStatus(status, btn) {
        activeStatus = status;
        document.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        applyFilters();
    }

    function applyFilters() {
        let visible = 0;
        document.querySelectorAll('.leave-item').forEach(item => {
            const show = activeStatus === 'all' || item.dataset.status === activeStatus;
            item.style.display = show ? 'block' : 'none';
            if (show) visible++;
        });
        document.getElementById('empty-state').style.display = visible === 0 ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', applyFilters);
</script>
@endpush
