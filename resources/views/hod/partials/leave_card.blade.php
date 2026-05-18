<!-- Leave Card -->
<div class="leave-card">
    <div class="leave-header">
        <div class="requester-info">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($leave->teacher_name) }}&background=random" class="requester-avatar" alt="User">
            <div class="requester-details">
                <h4>{{ $leave->teacher_name }}</h4>
                <p>مدرب | {{ $leave->specialization ?? 'قسم عام' }}</p>
                <p style="font-size: 0.8rem;"><i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::parse($leave->created_at)->format('d F Y') }}</p>
            </div>
        </div>
        <span class="leave-badge">{{ $leave->type == 'full_day' ? 'يوم كامل' : 'ساعية' }}</span>
    </div>
    
    <div class="leave-details" style="margin-bottom: 1.5rem;">
        <div class="detail-item" style="margin-bottom: 0.5rem;">
            <span class="detail-label" style="font-weight: bold; color: var(--text-secondary);">نوع الإجازة:</span>
            <span class="detail-value">{{ $leave->leave_category == 'hourly' ? 'ساعية' : 'يومية' }}</span>
        </div>
        <div class="detail-item" style="margin-bottom: 0.5rem;">
            <span class="detail-label" style="font-weight: bold; color: var(--text-secondary);">التاريخ:</span>
            <span class="detail-value" dir="ltr">{{ $leave->date ?? 'غير محدد' }}</span>
        </div>
        <div class="detail-item" style="margin-bottom: 0.5rem;">
            <span class="detail-label" style="font-weight: bold; color: var(--text-secondary);">السبب:</span>
            <span class="detail-value">{{ $leave->reason }}</span>
        </div>
    </div>
    
    <div class="leave-actions" style="display: flex; gap: 1rem;">
        <form action="{{ route('hod.leaves.status', $leave->id) }}" method="POST" style="flex: 1;">
            @csrf
            <input type="hidden" name="status" value="rejected">
            <button type="submit" class="btn-reject" style="width: 100%;"><i class="fa-solid fa-xmark"></i> رفض</button>
        </form>
        
        <form action="{{ route('hod.leaves.status', $leave->id) }}" method="POST" style="flex: 1;">
            @csrf
            <input type="hidden" name="status" value="approved">
            <button type="submit" class="btn-approve" style="width: 100%;"><i class="fa-solid fa-check"></i> موافقة</button>
        </form>
    </div>
</div>
