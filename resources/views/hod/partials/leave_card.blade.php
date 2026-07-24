@php
    $isPending  = in_array($leave->status, ['pending', 'pending_hod', 'pending_parent']);
    $isApproved = $leave->status === 'approved';
    $statusLabel = match($leave->status) {
        'approved'       => 'موافق عليها',
        'rejected'       => 'مرفوضة',
        'pending_parent' => 'بانتظار ولي الأمر',
        default          => 'بانتظار الموافقة',
    };
    $statusColor = match($leave->status) {
        'approved'       => ['bg' => '#f0fdf4', 'color' => '#16a34a'],
        'rejected'       => ['bg' => '#fef2f2', 'color' => '#dc2626'],
        'pending_parent' => ['bg' => '#eff6ff', 'color' => '#2563eb'],
        default          => ['bg' => '#fefce8', 'color' => '#ca8a04'],
    };
@endphp

<div class="leave-card">
    <div class="leave-header">
        <div class="requester-info">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($leave->student_name) }}&background=random"
                 class="requester-avatar" alt="User">
            <div class="requester-details">
                <h4>{{ $leave->student_name }}</h4>
                <p>
                    {{ $leave->level ?? '' }}
                    @if($leave->student_code) | {{ $leave->student_code }} @endif
                </p>
                <p style="font-size:0.8rem;">
                    <i class="fa-regular fa-calendar"></i>
                    {{ \Carbon\Carbon::parse($leave->created_at)->format('d/m/Y') }}
                </p>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem;">
            {{-- نوع الإجازة --}}
            <span class="leave-badge">{{ $leave->type == 'full_day' ? 'يوم كامل' : ($leave->type == 'justification' ? 'تبرير غياب' : 'ساعية') }}</span>
            {{-- حالة الإجازة --}}
            <span style="padding:0.2rem 0.75rem;border-radius:1rem;font-size:0.82rem;font-weight:700;background:{{ $statusColor['bg'] }};color:{{ $statusColor['color'] }};">
                {{ $statusLabel }}
            </span>
        </div>
    </div>

    <div class="leave-details" style="margin-bottom:1.25rem;">
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            <span style="background:var(--bg-primary);padding:0.3rem 0.8rem;border-radius:1rem;font-size:0.85rem;">
                <i class="fa-solid fa-tag" style="color:var(--text-secondary);"></i>
                {{ $leave->leave_category == 'hourly' ? 'ساعية' : 'يومية' }}
            </span>
            @if($leave->date)
            <span style="background:var(--bg-primary);padding:0.3rem 0.8rem;border-radius:1rem;font-size:0.85rem;" dir="ltr">
                <i class="fa-regular fa-calendar" style="color:var(--text-secondary);"></i>
                {{ $leave->date }}
            </span>
            @endif
        </div>
        @if($leave->reason)
        <div style="margin-top:0.75rem;background:var(--bg-primary);padding:0.75rem 1rem;border-radius:0.75rem;font-size:0.9rem;border-right:3px solid var(--border-color);">
            <strong style="color:var(--text-secondary);font-size:0.82rem;display:block;margin-bottom:0.25rem;">السبب:</strong>
            {{ $leave->reason }}
        </div>
        @endif

        @if(isset($leave->attachment) && $leave->attachment)
        <div style="margin-top:0.75rem;">
            <a href="{{ asset('storage/' . $leave->attachment) }}" target="_blank" style="display:inline-flex;align-items:center;gap:0.4rem;background:#eff6ff;color:#2563eb;padding:0.4rem 0.8rem;border-radius:1rem;font-size:0.85rem;font-weight:700;text-decoration:none;">
                <i class="fa-solid fa-paperclip"></i> عرض المرفق
            </a>
        </div>
        @endif
    </div>

    {{-- أزرار القرار فقط للطلبات المعلقة --}}
    @if($isPending)
    <div class="leave-actions" style="display:flex;gap:1rem;">
        <form action="{{ route('hod.leaves.status', $leave->id) }}" method="POST" style="flex:1;">
            @csrf
            <input type="hidden" name="status" value="rejected">
            <button type="submit" class="btn-reject" style="width:100%;">
                <i class="fa-solid fa-xmark"></i> رفض
            </button>
        </form>
        <form action="{{ route('hod.leaves.status', $leave->id) }}" method="POST" style="flex:1;">
            @csrf
            <input type="hidden" name="status" value="approved">
            <button type="submit" class="btn-approve" style="width:100%;">
                <i class="fa-solid fa-check"></i> موافقة
            </button>
        </form>
    </div>
    @endif
</div>
