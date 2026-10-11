@extends('layouts.parent')
@section('title', __('messages.permissions_and_leaves'))

@push('styles')
<style>
    /* 🌟 رأس الصفحة العلوي مع زر الإضافة (+ تقديم طلب إجازة جديد) */
    .page-header-banner {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem 1.75rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        border: 1px solid var(--border-color);
        border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 5px solid var(--accent-color);
    }
    .page-header-info h2 {
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0 0 0.3rem 0;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .page-header-info p {
        font-size: 0.88rem;
        color: var(--text-secondary);
        margin: 0;
        font-weight: 600;
    }
    .btn-create-leave {
        background: var(--accent-color);
        color: #1a1a1a;
        border: none;
        padding: 0.85rem 1.6rem;
        border-radius: 0.85rem;
        font-size: 0.95rem;
        font-weight: 800;
        cursor: pointer;
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        box-shadow: 0 4px 14px rgba(242, 242, 13, 0.35);
        transition: all 0.25s ease;
    }
    .btn-create-leave:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(242, 242, 13, 0.5);
    }

    /* 🌟 البطاقات وسلسلة العرض */
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
        transition: transform 0.2s ease, border-color 0.2s ease;
        animation: fadeInCard 0.3s ease-out;
    }
    .request-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent-color);
    }
    
    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .request-type {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    
    .status-badge {
        padding: 0.35rem 0.85rem;
        border-radius: 2rem;
        font-size: 0.82rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    
    .status-pending_parent { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
    .status-pending_hod    { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
    .status-approved       { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-rejected       { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    
    .request-reason {
        background: var(--bg-primary);
        padding: 1rem;
        border-radius: 0.75rem;
        font-size: 0.92rem;
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 1.25rem;
        border: 1px solid var(--border-color);
    }
    
    .request-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
        color: var(--text-secondary);
        flex-wrap: wrap;
        gap: 0.6rem;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .btn-approve {
        background: #10b981; color: white;
        border: none; padding: 0.55rem 1.35rem;
        border-radius: 0.65rem; font-weight: 800; cursor: pointer;
        display: flex; align-items: center; gap: 0.4rem; font-size: 0.88rem;
        font-family: inherit; transition: background 0.2s ease;
    }
    .btn-approve:hover { background: #059669; }
    
    .btn-reject {
        background: #ef4444; color: white;
        border: none; padding: 0.55rem 1.35rem;
        border-radius: 0.65rem; font-weight: 800; cursor: pointer;
        display: flex; align-items: center; gap: 0.4rem; font-size: 0.88rem;
        font-family: inherit; transition: background 0.2s ease;
    }
    .btn-reject:hover { background: #dc2626; }
    
    /* 🌟 أسلوب الفورم وعناصر الإدخال */
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-primary); }
    
    .form-control, input[type="date"], input[type="time"], select.form-control, textarea.form-control {
        width: 100%;
        background: var(--bg-primary);
        border: 2px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.85rem 1rem;
        border-radius: 0.75rem;
        font-family: inherit;
        font-size: 0.95rem;
        font-weight: 700;
        outline: none;
        transition: all 0.2s ease;
    }

    [data-theme="dark"] input[type="date"],
    [data-theme="dark"] input[type="time"],
    html.dark input[type="date"],
    html.dark input[type="time"] {
        color-scheme: dark !important;
        color: #ffffff !important;
        background-color: var(--bg-primary, #000000) !important;
    }

    .form-control:focus, input[type="date"]:focus, input[type="time"]:focus {
        border-color: var(--accent-color, #f2f20d) !important;
        box-shadow: 0 0 0 3px rgba(242, 242, 13, 0.25) !important;
    }
    
    .btn-submit {
        background: var(--accent-color); color: #1a1a1a;
        font-weight: 800; border: none; width: 100%;
        padding: 0.875rem; border-radius: 0.75rem; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        gap: 0.5rem; font-family: inherit; font-size: 1rem;
        transition: background 0.2s ease;
    }
    .btn-submit:hover:not(:disabled) { filter: brightness(1.05); }
    .btn-submit:disabled { opacity: 0.65; cursor: not-allowed !important; }
    
    .type-options { display: flex; gap: 0.75rem; }
    .type-option {
        flex: 1; text-align: center; border: 2px solid var(--border-color);
        padding: 0.75rem; border-radius: 0.75rem; cursor: pointer;
        font-size: 0.9rem; font-weight: 800; background: var(--bg-primary);
        transition: all 0.2s ease; color: var(--text-primary);
    }
    .type-option.active {
        background: var(--accent-color); color: #1a1a1a;
        border-color: var(--accent-color);
    }

    .modal-scrollable-body::-webkit-scrollbar { width: 6px; }
    .modal-scrollable-body::-webkit-scrollbar-track { background: var(--bg-primary); border-radius: 4px; }
    .modal-scrollable-body::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
    .modal-scrollable-body::-webkit-scrollbar-thumb:hover { background: var(--accent-color); }

    @keyframes fadeInCard {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes modalPop {
        from { transform: scale(0.93); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>
@endpush

@section('content')

{{-- تنبيهات الرسائل --}}
<div id="dynamicAlertContainer">
    @if(session('success'))
        <div style="background: #dcfce7; color: #15803d; padding: 0.85rem 1.25rem; border-radius: 0.75rem; font-weight: 700; margin-bottom: 1.25rem; border: 1px solid #86efac; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background: #fee2e2; color: #b91c1c; padding: 0.85rem 1.25rem; border-radius: 0.75rem; font-weight: 700; margin-bottom: 1.25rem; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background: #fee2e2; color: #b91c1c; padding: 0.85rem 1.25rem; border-radius: 0.75rem; font-weight: 700; margin-bottom: 1.25rem; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
        </div>
    @endif
</div>

@if(!$selected_child_id)
    <div style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color);">
        <i class="fa-solid fa-child" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5; margin-bottom: 1rem; display: block;"></i>
        <h4 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem;">{{ __('messages.please_select_child_first') }}</h4>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">{{ __('messages.select_child_hint') }}</p>
    </div>
@else
    @section('subtitle')
        {{ __('messages.permissions_for_child') }}: {{ $selected_child->full_name }}
    @endsection

    {{-- 🌟 رأس الصفحة العلوي مع زر إشارة زائد (+ تقديم طلب إجازة جديد) 🌟 --}}
    <div class="page-header-banner">
        <div class="page-header-info">
            <h2>
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-color);"></i>
                {{ __('messages.permissions_and_leaves') }}: {{ $selected_child->full_name }}
            </h2>
            <p>{{ __('messages.permissions_banner_desc') }}</p>
        </div>
        <button type="button" class="btn-create-leave" onclick="openParentLeaveModal()">
            <i class="fa-solid fa-plus" style="font-size: 1.1rem;"></i>
            {{ __('messages.submit_new_leave_btn') }}
        </button>
    </div>

    {{-- 🌟 قائمة الطلبات كاملة العرض 🌟 --}}
    <div style="margin-bottom: 2rem;">
        <h3 class="section-title" style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-list-check" style="color: var(--accent-color);"></i>
            <span>{{ __('messages.leave_requests_log') }} (<span id="requestsCount">{{ $requests->count() }}</span>)</span>
        </h3>
        
        <div id="parentRequestsContainer" class="requests-list">
            @forelse($requests as $req)
                <div class="request-card" id="req-card-{{ $req->request_id ?? $req->id }}">
                    <div class="request-header">
                        <span class="request-type">
                            <i class="fa-solid fa-envelope-open-text" style="color: var(--accent-color); margin-inline-end: 0.5rem;"></i>
                            {{ __('messages.leave_type_label') }} {{ (($req->type ?? '') === 'hourly' || str_contains($req->reason ?? '', 'إذن ساعي') || str_contains($req->reason ?? '', 'hourly')) ? __('messages.leave_hourly') : __('messages.leave_daily') }}
                        </span>
                        @php
                            $reqId = $req->request_id ?? $req->id;
                            $statusLabels = [
                                'pending' => __('messages.pending_review'),
                                'pending_parent' => __('messages.pending_parent_approval'),
                                'pending_hod' => __('messages.pending_hod_approval'),
                                'approved' => __('messages.approved_status'),
                                'rejected' => __('messages.rejected_status')
                            ];
                        @endphp
                        <span class="status-badge status-{{ $req->status }}">
                            {{ $statusLabels[$req->status] ?? $req->status }}
                        </span>
                    </div>
                    
                    <div class="request-reason">
                        <strong style="color: var(--text-primary);">{{ __('messages.reason') }}:</strong> {{ $req->reason }}
                    </div>
                    
                    <div class="request-meta">
                        <span><i class="fa-regular fa-calendar-days" style="color: var(--accent-color);"></i> {{ __('messages.leave_date') }}: {{ \Carbon\Carbon::parse($req->date)->format('Y-m-d') }}</span>
                        <span style="font-weight: 700; color: var(--text-primary);"><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> {{ __('messages.submitted_date_time') }}: {{ \Carbon\Carbon::parse($req->created_at)->format('Y-m-d - h:i A') }}</span>
                    </div>
                    
                    @if(in_array($req->status, ['pending', 'pending_parent']))
                        <div class="action-buttons">
                            <form action="{{ route('parent.permissions.respond', $reqId) }}" method="POST" style="margin: 0;">
                                @csrf
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn-approve">
                                    <i class="fa-solid fa-check"></i> {{ __('messages.approve') }}
                                </button>
                            </form>
                            
                            <form action="{{ route('parent.permissions.respond', $reqId) }}" method="POST" style="margin: 0;">
                                @csrf
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn-reject">
                                    <i class="fa-solid fa-xmark"></i> {{ __('messages.reject') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div id="emptyRequestsState" style="text-align: center; padding: 4rem 2rem; background: var(--bg-secondary); border-radius: 1.25rem; border: 1px dashed var(--border-color); color: var(--text-secondary);">
                    <i class="fa-solid fa-folder-open" style="font-size: 2.8rem; color: var(--accent-color); opacity: 0.6; margin-bottom: 1rem; display: block;"></i>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.3rem;">{{ __('messages.no_leave_requests_yet') }}</h3>
                    <p style="font-size: 0.88rem; margin-bottom: 1.2rem;">{{ __('messages.no_leave_requests_hint') }}</p>
                    <button type="button" class="btn-create-leave" onclick="openParentLeaveModal()">
                        <i class="fa-solid fa-plus"></i> {{ __('messages.submit_request_now') }}
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- 🌟 1. النافذة المنبثقة لتقديم طلب إجازة جديد (Parent Leave Request Modal) 🌟 --}}
    <div id="parentLeaveModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center; padding: 1rem;">
        <div style="background: var(--bg-secondary); border-radius: 1.35rem; width: 100%; max-width: 550px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.4); border: 1px solid var(--border-color); border-top: 5px solid var(--accent-color); overflow: hidden; position: relative; animation: modalPop 0.25s ease-out;">
            
            {{-- Header مع زر رجوع وإغلاق --}}
            <div style="background: var(--bg-primary); padding: 1rem 1.35rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <button type="button" onclick="closeParentLeaveModal()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.4rem 0.8rem; border-radius: 0.65rem; cursor: pointer; font-size: 0.85rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem; font-family: inherit; transition: all 0.2s;">
                        <i class="fa-solid {{ app()->getLocale() === 'ar' ? 'fa-arrow-right' : 'fa-arrow-left' }}" style="color: var(--accent-color);"></i> {{ __('messages.back') }}
                    </button>
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0; color: var(--text-primary);">{{ __('messages.submit_leave_for_child_title') }}</h3>
                    </div>
                </div>
                <button type="button" onclick="closeParentLeaveModal()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
            </div>

            {{-- Body قابلة للسكرول --}}
            <div class="modal-scrollable-body" style="padding: 1.5rem; overflow-y: auto; flex: 1;">
                <form action="{{ route('parent.permissions.submit') }}" method="POST" id="parentLeaveForm" novalidate onsubmit="handleParentLeaveFormAjaxSubmit(event)">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selected_child_id }}">
                    
                    <div class="form-group">
                        <label><i class="fa-solid fa-list" style="color: var(--accent-color);"></i> {{ __('messages.leave_type') }}</label>
                        <div class="type-options">
                            <div class="type-option active" data-value="full_day" onclick="selectType('full_day')">{{ __('messages.full_day') }}</div>
                            <div class="type-option" data-value="hourly" onclick="selectType('hourly')">{{ __('messages.hourly') }}</div>
                        </div>
                        <input type="hidden" name="type" id="leave-type-input" value="full_day">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label for="leave-date" style="display: flex; align-items: center; justify-content: space-between;">
                                <span><i class="fa-regular fa-calendar-days" style="color: var(--accent-color);"></i> {{ __('messages.leave_date') }}</span>
                                <span style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 700;">(YYYY-MM-DD)</span>
                            </label>
                            <input type="date" name="date" id="leave-date" class="form-control" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                        </div>

                        <div id="parent-time-group">
                            <label for="leave-time" style="display: flex; align-items: center; justify-content: space-between;">
                                <span><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> {{ __('messages.leave_time_label') }}</span>
                                <span style="font-size: 0.75rem; background: var(--accent-color); color: #1a1a1a; padding: 0.1rem 0.4rem; border-radius: 0.3rem; font-weight: 800;">{{ __('messages.hour_minute_badge') }}</span>
                            </label>
                            <input type="time" name="time" id="leave-time" class="form-control" max="15:00" value="{{ date('H:i') > '15:00' ? '14:00' : date('H:i') }}">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="leave-reason"><i class="fa-solid fa-align-right" style="color: var(--accent-color);"></i> {{ __('messages.detailed_reason') }}</label>
                        <textarea name="reason" id="leave-reason" class="form-control" rows="4" placeholder="{{ __('messages.leave_reason_placeholder') }}"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                        <button type="submit" id="submitParentLeaveBtn" class="btn-submit">
                            <i class="fa-solid fa-paper-plane"></i> {{ __('messages.send_request_btn') }}
                        </button>
                        <button type="button" onclick="closeParentLeaveModal()" style="background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.875rem 1.5rem; border-radius: 0.75rem; font-size: 0.95rem; font-weight: 700; cursor: pointer; font-family: inherit;">
                            {{ __('messages.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
function openParentLeaveModal() {
    const modal = document.getElementById('parentLeaveModal');
    if (modal) {
        modal.style.display = 'flex';
        const submitBtn = document.getElementById('submitParentLeaveBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> {{ __('messages.send_request_btn') }}';
        }
    }
}

function closeParentLeaveModal() {
    const modal = document.getElementById('parentLeaveModal');
    if (modal) modal.style.display = 'none';
}

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

// 🚀 تقديم الطلب عبر AJAX لولي الأمر مع إغلاق النافذة فوراً وإدراج الطلب تحت كـ "معلق"
let isSubmittingParentLeave = false;

function handleParentLeaveFormAjaxSubmit(event) {
    event.preventDefault();

    if (isSubmittingParentLeave) return false;

    const form = document.getElementById('parentLeaveForm');
    const submitBtn = document.getElementById('submitParentLeaveBtn');
    const reasonInput = document.getElementById('leave-reason');

    if (!reasonInput || !reasonInput.value.trim()) {
        alert('{{ __('messages.leave_reason_required_alert') }}');
        if (reasonInput) reasonInput.focus();
        return false;
    }

    const parentTimeInput = document.getElementById('leave-time');
    if (parentTimeInput && parentTimeInput.value && parentTimeInput.value > '15:00') {
        alert('{{ __('messages.leave_time_limit_alert') }}');
        if (parentTimeInput) parentTimeInput.focus();
        return false;
    }

    isSubmittingParentLeave = true;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> {{ __('messages.submitting_request') }}';
    }

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw err; });
        }
        return response.json();
    })
    .then(data => {
        isSubmittingParentLeave = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> {{ __('messages.send_request_btn') }}';
        }

        if (data.success) {
            // 1. إغلاق النافذة فوراً
            closeParentLeaveModal();

            // 2. إخفاء حالة "لا توجد طلبات" إن وجدت
            const emptyState = document.getElementById('emptyRequestsState');
            if (emptyState) emptyState.style.display = 'none';

            // 3. تحديث العداد
            const countEl = document.getElementById('requestsCount');
            if (countEl) {
                const currentCount = parseInt(countEl.innerText) || 0;
                countEl.innerText = currentCount + 1;
            }

            // 4. إدراج كرت الطلب الجديد تحت كـ "معلق" (بانتظار موافقة القسم)
            const req = data.request;
            const typeText = req.type === 'hourly' ? '{{ __('messages.leave_hourly') }}' : '{{ __('messages.leave_daily') }}';
            
            const newCardHtml = `
                <div class="request-card" id="req-card-${req.id}">
                    <div class="request-header">
                        <span class="request-type">
                            <i class="fa-solid fa-envelope-open-text" style="color: var(--accent-color); margin-inline-end: 0.5rem;"></i>
                            {{ __('messages.leave_type_label') }} ${typeText}
                        </span>
                        <span class="status-badge status-pending_hod">
                            {{ __('messages.pending_hod_approval') }}
                        </span>
                    </div>
                    
                    <div class="request-reason">
                        <strong style="color: var(--text-primary);">{{ __('messages.reason') }}:</strong> ${req.reason}
                    </div>
                    
                    <div class="request-meta">
                        <span><i class="fa-regular fa-calendar-days" style="color: var(--accent-color);"></i> {{ __('messages.leave_date') }}: ${req.date}</span>
                        <span style="font-weight: 700; color: var(--text-primary);"><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> {{ __('messages.submitted_date_time') }}: {{ __('messages.now') ?? 'Now' }}</span>
                    </div>
                </div>
            `;

            const container = document.getElementById('parentRequestsContainer');
            if (container) {
                container.insertAdjacentHTML('afterbegin', newCardHtml);
            }

            // 5. إظهار التنبيه الأخضر في الأعلى
            const alertBox = document.getElementById('dynamicAlertContainer');
            if (alertBox) {
                alertBox.innerHTML = `
                    <div style="background: #dcfce7; color: #15803d; padding: 0.85rem 1.25rem; border-radius: 0.75rem; font-weight: 700; margin-bottom: 1.25rem; border: 1px solid #86efac; display: flex; align-items: center; gap: 0.5rem; animation: modalPop 0.3s ease-out;">
                        <i class="fa-solid fa-circle-check"></i> ${data.message}
                    </div>
                `;
            }

            form.reset();
            selectType('full_day');
        } else {
            alert('⚠️ ' + (data.message || '{{ __('messages.submission_error') }}'));
        }
    })
    .catch(err => {
        isSubmittingParentLeave = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> {{ __('messages.send_request_btn') }}';
        }
        console.error('Submission error:', err);
        const errorMsg = err.message || (err.errors ? Object.values(err.errors).flat().join('\n') : '{{ __('messages.server_error') ?? 'Server error' }}');
        alert('⚠️ ' + errorMsg);
    });
}
</script>
@endpush
