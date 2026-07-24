@extends('layouts.parent')
@section('title', 'طلبات الإذن والإجازات')

@push('styles')
<style>
    .permissions-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    @media(min-width: 992px) {
        .permissions-container {
            grid-template-columns: 2fr 1fr;
        }
    }
    
    .requests-list {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    
    .request-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }
    
    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .request-type {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    
    .status-badge {
        padding: 0.3rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-block;
    }
    
    .status-pending_parent {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    
    .status-pending_hod {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }
    
    .status-approved {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .status-rejected {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .request-reason {
        background: var(--bg-primary);
        padding: 1rem;
        border-radius: 0.75rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.5;
        margin-bottom: 1.25rem;
        border: 1px solid var(--border-color);
    }
    
    .request-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.82rem;
        color: var(--text-secondary);
        opacity: 0.8;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .btn-approve {
        background: #10b981;
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        transition: background 0.2s ease;
    }
    .btn-approve:hover { background: #059669; }
    
    .btn-reject {
        background: #ef4444;
        color: white;
        border: none;
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        transition: background 0.2s ease;
    }
    .btn-reject:hover { background: #dc2626; }
    
    .submit-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        align-self: start;
    }
    
    .submit-card h4 {
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .submit-card h4 i {
        color: var(--accent-color);
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }
    
    .form-control {
        width: 100%;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-family: inherit;
        font-size: 0.9rem;
        outline: none;
    }
    
    .form-control:focus {
        border-color: var(--accent-color);
    }
    
    .btn-submit {
        background: var(--accent-color);
        color: #1a1a1a;
        font-weight: 700;
        border: none;
        width: 100%;
        padding: 0.75rem;
        border-radius: 0.75rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background 0.2s ease;
    }
    
    .btn-submit:hover {
        background: #e6b800;
    }
    
    .type-options {
        display: flex;
        gap: 0.75rem;
    }
    
    .type-option {
        flex: 1;
        text-align: center;
        border: 1px solid var(--border-color);
        padding: 0.6rem;
        border-radius: 0.75rem;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 700;
        background: var(--bg-primary);
        transition: all 0.2s ease;
    }
    
    .type-option.active {
        background: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
    }
</style>
@endpush

@section('content')

@if(!$selected_child_id)
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
        <i class="fa-solid fa-child" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5; margin-bottom: 1rem; display: block;"></i>
        <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">يرجى اختيار ابن أولاً</h4>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">يرجى اختيار الابن من القائمة في الأعلى أو إضافة ابن من تبويب "أبنائي".</p>
    </div>
@else
    @section('subtitle')
        مراجعة طلبات إذن الخروج والإجازات للابن: {{ $selected_child->full_name }}
    @endsection

    <div class="permissions-container">
        <!-- List of Requests -->
        <div>
            <h3 class="section-title" style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem;">
                <i class="fa-solid fa-list-check"></i> سجل طلبات الإجازات
            </h3>
            
            @if($requests->isNotEmpty())
                <div class="requests-list">
                    @foreach($requests as $req)
                        <div class="request-card">
                            <div class="request-header">
                                <span class="request-type">
                                    <i class="fa-solid fa-envelope-open-text" style="color: var(--accent-color); margin-left: 0.5rem;"></i>
                                    إجازة {{ (($req->type ?? '') === 'hourly' || str_contains($req->reason ?? '', 'إذن ساعي')) ? 'ساعية' : 'يومية (يوم كامل)' }}
                                </span>
                                @php
                                    $reqId = $req->request_id ?? $req->id;
                                    $statusLabels = [
                                        'pending' => 'قيد المراجعة',
                                        'pending_parent' => 'بانتظار موافقة ولي الأمر',
                                        'pending_hod' => 'بانتظار موافقة القسم',
                                        'approved' => 'تمت الموافقة',
                                        'rejected' => 'مرفوض'
                                    ];
                                @endphp
                                <span class="status-badge status-{{ $req->status }}">
                                    {{ $statusLabels[$req->status] ?? $req->status }}
                                </span>
                            </div>
                            
                            <div class="request-reason">
                                {{ $req->reason }}
                            </div>
                            
                            <div class="request-meta">
                                <span>تاريخ الإجازة: {{ \Carbon\Carbon::parse($req->date)->format('Y-m-d') }}</span>
                                <span>تاريخ الطلب: {{ \Carbon\Carbon::parse($req->created_at)->format('Y-m-d H:i') }}</span>
                            </div>
                            
                            @if(in_array($req->status, ['pending', 'pending_parent']))
                                <div class="action-buttons">
                                    <form action="{{ route('parent.permissions.respond', $reqId) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn-approve">
                                            <i class="fa-solid fa-check"></i> موافقة
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('parent.permissions.respond', $reqId) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn-reject">
                                            <i class="fa-solid fa-xmark"></i> رفض
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.25rem; border: 1px dashed var(--border-color); color: var(--text-secondary);">
                    <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: 1rem; display: block;"></i>
                    لا توجد طلبات إجازة مسجلة للابن حالياً.
                </div>
            @endif
        </div>
        
        <!-- Submit Leave Request -->
        <div>
            <div class="submit-card">
                <h4><i class="fa-solid fa-file-pen"></i> تقديم طلب إجازة جديد</h4>
                <form action="{{ route('parent.permissions.submit') }}" method="POST">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selected_child_id }}">
                    
                    <div class="form-group">
                        <label>نوع الإجازة</label>
                        <div class="type-options">
                            <div class="type-option active" data-value="full_day" onclick="selectType('full_day')">يوم كامل</div>
                            <div class="type-option" data-value="hourly" onclick="selectType('hourly')">ساعية</div>
                        </div>
                        <input type="hidden" name="type" id="leave-type-input" value="full_day">
                    </div>
                    
                    <div class="form-group">
                        <label for="leave-date">تاريخ الإجازة</label>
                        <input type="date" name="date" id="leave-date" class="form-control" min="{{ date('Y-m-d') }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="leave-reason">السبب بالتفصيل</label>
                        <textarea name="reason" id="leave-reason" class="form-control" rows="4" placeholder="اكتب سبب طلب الإجازة..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> إرسال الطلب
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
function selectType(val) {
    document.getElementById('leave-type-input').value = val;
    document.querySelectorAll('.type-option').forEach(el => {
        if(el.getAttribute('data-value') === val) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}
</script>
@endpush
