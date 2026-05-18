@extends('layouts.hod')

@section('title', 'طلبات الإجازة')

@push('styles')
<style>
    .tabs-container {
        display: flex;
        background-color: var(--bg-secondary);
        border-radius: 2rem;
        padding: 0.25rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
    }
    
    .tab-btn {
        flex: 1;
        padding: 0.75rem;
        border: none;
        background: transparent;
        border-radius: 1.5rem;
        font-weight: 700;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .tab-btn.active {
        background-color: var(--accent-color);
        color: #1a1a1a;
    }
    
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

    <div class="tabs-container">
        <button class="tab-btn active" onclick="showTab('daily', this)">إجازات يومية</button>
        <button class="tab-btn" onclick="showTab('hourly', this)">إجازات ساعية</button>
    </div>

    <!-- الإجازات اليومية -->
    <div id="daily-leaves">
        @php $hasDaily = false; @endphp
        @foreach($pendingLeaves as $leave)
            @if($leave->leave_category == 'daily')
                @php $hasDaily = true; @endphp
                @include('hod.partials.leave_card', ['leave' => $leave])
            @endif
        @endforeach
        
        @if(!$hasDaily)
        <div class="card" style="text-align: center; color: var(--text-secondary);">
            لا توجد طلبات إجازة يومية معلقة حالياً.
        </div>
        @endif
    </div>

    <!-- الإجازات الساعية -->
    <div id="hourly-leaves" style="display: none;">
        @php $hasHourly = false; @endphp
        @foreach($pendingLeaves as $leave)
            @if($leave->leave_category == 'hourly')
                @php $hasHourly = true; @endphp
                @include('hod.partials.leave_card', ['leave' => $leave])
            @endif
        @endforeach
        
        @if(!$hasHourly)
        <div class="card" style="text-align: center; color: var(--text-secondary);">
            لا توجد طلبات إجازة ساعية معلقة حالياً.
        </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    function showTab(tabId, btn) {
        // إخفاء جميع الأقسام
        document.getElementById('daily-leaves').style.display = 'none';
        document.getElementById('hourly-leaves').style.display = 'none';
        
        // إزالة الكلاس active من كل الأزرار
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(b => b.classList.remove('active'));
        
        // إظهار القسم المطلوب وإضافة الكلاس active للزر
        document.getElementById(tabId + '-leaves').style.display = 'block';
        btn.classList.add('active');
    }
</script>
@endpush
