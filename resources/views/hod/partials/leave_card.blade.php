@php
    $status = $leave->status ?? 'pending';
    $isPending  = in_array($status, ['pending', 'pending_hod', 'pending_parent']);
    $isApproved = $status === 'approved';
    $statusLabel = match($status) {
        'approved'       => 'موافق عليها',
        'rejected'       => 'مرفوضة',
        'pending_parent' => 'بانتظار ولي الأمر',
        default          => 'بانتظار الموافقة',
    };
    $statusColor = match($status) {
        'approved'       => ['bg' => '#f0fdf4', 'color' => '#16a34a'],
        'rejected'       => ['bg' => '#fef2f2', 'color' => '#dc2626'],
        'pending_parent' => ['bg' => '#eff6ff', 'color' => '#2563eb'],
        default          => ['bg' => '#fefce8', 'color' => '#ca8a04'],
    };
    $studentName = $leave->student_name ?? 'طالب';
    $leaveType = $leave->type ?? 'full_day';
    $leaveCategory = $leave->leave_category ?? ($leaveType === 'hourly' ? 'hourly' : 'daily');
    $attachmentFile = $leave->attachment ?? $leave->document ?? null;
@endphp

<div class="leave-card">
    <div class="leave-header">
        <div class="requester-info">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($studentName) }}&background=random"
                 class="requester-avatar" alt="User">
            <div class="requester-details">
                <h4>{{ $studentName }}</h4>
                <p>
                    {{ $leave->level ?? '' }}
                    @if(!empty($leave->student_code)) | {{ $leave->student_code }} @endif
                </p>
                @if(!empty($leave->created_at))
                <p style="font-size:0.82rem; font-weight:700; color:var(--text-primary);">
                    <i class="fa-regular fa-clock" style="color:var(--accent-color);"></i>
                    تاريخ ووقت التقديم: {{ \Carbon\Carbon::parse($leave->created_at)->format('Y-m-d - h:i A') }}
                </p>
                @endif
            </div>
        </div>

        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem;">
            {{-- نوع الإجازة --}}
            <span class="leave-badge">{{ $leaveType == 'full_day' ? 'يوم كامل' : ($leaveType == 'justification' ? 'تبرير غياب' : 'ساعية') }}</span>
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
                {{ $leaveCategory == 'hourly' ? 'ساعية' : 'يومية' }}
            </span>
            @if(!empty($leave->date))
            <span style="background:var(--bg-primary);padding:0.3rem 0.8rem;border-radius:1rem;font-size:0.85rem;" dir="ltr">
                <i class="fa-regular fa-calendar" style="color:var(--text-secondary);"></i>
                {{ $leave->date }}
            </span>
            @endif
        </div>
        @if(!empty($leave->reason))
        <div style="margin-top:0.75rem;background:var(--bg-primary);padding:0.75rem 1rem;border-radius:0.75rem;font-size:0.9rem;border-right:3px solid var(--border-color);">
            <strong style="color:var(--text-secondary);font-size:0.82rem;display:block;margin-bottom:0.25rem;">السبب:</strong>
            {{ $leave->reason }}
        </div>
        @endif

        {{-- إظهار رأي المراحل السابقة (موافقة ولي الأمر) --}}
        @if(in_array($status, ['pending_hod', 'pending_affairs', 'approved']))
        <div style="margin-top:0.75rem;background:#f0fdf4;color:#16a34a;padding:0.5rem 0.8rem;border-radius:0.6rem;font-size:0.85rem;font-weight:700;display:flex;align-items:center;gap:0.4rem;border:1px solid #bbf7d0;">
            <i class="fa-solid fa-circle-check"></i> رأي المرحلة السابقة: موافقة ولي الأمر (تمت ✓)
        </div>
        @elseif($status === 'pending_parent')
        <div style="margin-top:0.75rem;background:#eff6ff;color:#2563eb;padding:0.5rem 0.8rem;border-radius:0.6rem;font-size:0.85rem;font-weight:700;display:flex;align-items:center;gap:0.4rem;border:1px solid #bfdbfe;">
            <i class="fa-solid fa-clock"></i> في انتظار موافقة ولي الأمر أولاً
        </div>
        @endif

        @if($attachmentFile)
        <div style="margin-top:0.75rem;">
            <a href="{{ asset('storage/' . $attachmentFile) }}" target="_blank" style="display:inline-flex;align-items:center;gap:0.4rem;background:#eff6ff;color:#2563eb;padding:0.4rem 0.8rem;border-radius:1rem;font-size:0.85rem;font-weight:700;text-decoration:none;">
                <i class="fa-solid fa-paperclip"></i> عرض المرفق
            </a>
        </div>
        @endif
    </div>

    {{-- أزرار القرار فقط للطلبات المعلقة --}}
    @if($isPending && !empty($leave->id))
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
