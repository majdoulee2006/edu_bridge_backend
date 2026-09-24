@extends('layouts.affairs')
@section('title', 'الخدمات الطلابية')

@push('styles')
<style>
    .services-container {
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
    }
    .page-header h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
    }

    /* Tabs Styling */
    .custom-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 0.5rem;
        overflow-x: auto;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }
    .custom-tabs::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }
    .tab-btn {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        font-size: 1.05rem;
        font-weight: 700;
        padding: 0.8rem 1.5rem;
        cursor: pointer;
        position: relative;
        transition: color 0.3s;
        white-space: nowrap;
        border-radius: 8px 8px 0 0;
    }
    .tab-btn:hover {
        color: var(--text-primary);
        background: var(--bg-secondary);
    }
    .tab-btn.active {
        color: var(--accent-color);
    }
    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -0.65rem;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--accent-color);
        border-radius: 3px;
    }

    /* Tab Content Area */
    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .tab-content.active {
        display: block;
    }

    /* Tables - Fixed Height & Strictly Single-Line */
    .table-container {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.2rem;
        box-shadow: var(--shadow);
        overflow-x: auto;
    }
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        white-space: nowrap !important;
    }
    .custom-table th {
        text-align: right;
        padding: 0.9rem 1rem !important;
        color: var(--text-secondary);
        font-weight: 800;
        border-bottom: 2px solid var(--border-color);
        white-space: nowrap !important;
        font-size: 0.88rem;
    }
    .custom-table td {
        padding: 0.65rem 1rem !important;
        color: var(--text-primary);
        font-weight: 600;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle !important;
        white-space: nowrap !important;
        height: 54px;
        line-height: 1.2;
    }
    .custom-table tr:hover td {
        background: rgba(0, 0, 0, 0.02);
    }
    .table-cell-nowrap {
        white-space: nowrap !important;
    }
    .table-student-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        white-space: nowrap !important;
        vertical-align: middle;
    }
    .table-avatar {
        width: 32px;
        height: 32px;
        min-width: 32px;
        background: var(--accent-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1a1a1a;
        font-weight: bold;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    .table-student-name {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap !important;
        display: inline-block;
        vertical-align: middle;
        font-weight: 700;
    }
    .table-text-truncate {
        display: inline-block;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap !important;
        vertical-align: middle;
        font-size: 0.86rem;
    }

    /* Status Badges */
    .badge {
        padding: 0.35rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.82rem;
        font-weight: 700;
        display: inline-block;
        white-space: nowrap !important;
    }
    .badge-pending { background: #fef08a; color: #854d0e; }
    .badge-approved { background: #bbf7d0; color: #166534; }
    .badge-rejected { background: #fecaca; color: #991b1b; }

    /* Square Action Buttons Side-by-Side */
    .action-btns {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.45rem !important;
        white-space: nowrap !important;
    }
    .btn-action-square {
        width: 35px !important;
        height: 35px !important;
        min-width: 35px !important;
        min-height: 35px !important;
        max-width: 35px !important;
        max-height: 35px !important;
        border-radius: 9px !important;
        border: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        margin: 0 !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-size: 0.95rem !important;
        text-decoration: none !important;
        color: #ffffff !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08) !important;
        flex-shrink: 0 !important;
    }
    .btn-action-square:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18) !important;
    }
    .btn-action-square.btn-reset {
        background: #f59e0b !important;
    }
    .btn-action-square.btn-view {
        background: #3b82f6 !important;
    }
    .btn-action-square.btn-pathway {
        background: #10b981 !important;
    }
    .btn-action-square.btn-disabled {
        background: #9ca3af !important;
        cursor: default !important;
    }

    /* Modal Styling - Compact Portrait Card */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(4px);
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.25s ease;
        padding: 1rem;
    }
    .modal-overlay.active {
        display: flex;
        opacity: 1;
    }
    #requestModal .modal-content {
        background: var(--bg-primary, #ffffff) !important;
        border-radius: 1.5rem !important;
        width: 100% !important;
        max-width: 440px !important;
        max-height: 90vh !important;
        overflow-y: auto !important;
        padding: 1.35rem 1.4rem !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        border: 1px solid var(--border-color, #e5e7eb) !important;
        transform: scale(0.92);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 0.85rem !important;
        margin: auto;
    }
    .modal-overlay.active #requestModal .modal-content,
    #requestModal.active .modal-content {
        transform: scale(1) !important;
    }

    /* Modal Card Header */
    .modal-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color, #e5e7eb);
    }
    .modal-card-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .modal-card-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .modal-card-title h3 {
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0;
        color: var(--text-primary);
        line-height: 1.2;
    }
    .modal-card-title span {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--accent-color, #f59e0b);
    }
    .btn-close-modal {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--bg-secondary, #f3f4f6);
        border: none;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    .btn-close-modal:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    /* Student Info Mini-Card */
    .student-mini-card {
        background: var(--bg-secondary, #f9fafb);
        border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    .student-mini-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
    }
    .student-mini-label {
        color: var(--text-secondary);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .student-mini-value {
        color: var(--text-primary);
        font-weight: 700;
        text-align: left;
    }

    /* Details Box */
    .modal-detail-box {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .modal-detail-box label {
        font-size: 0.83rem;
        font-weight: 700;
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .modal-detail-content {
        background: var(--bg-secondary, #f9fafb);
        border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 12px;
        padding: 0.7rem 0.85rem;
        font-size: 0.86rem;
        color: var(--text-primary);
        font-weight: 600;
        line-height: 1.45;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 90px;
        overflow-y: auto;
    }

    /* Notes Area Compact */
    .notes-area-compact {
        width: 100%;
        min-height: 65px;
        max-height: 95px;
        border: 1.5px solid var(--border-color, #e5e7eb);
        border-radius: 12px;
        padding: 0.6rem 0.75rem;
        background: var(--bg-primary, #ffffff);
        color: var(--text-primary);
        font-family: 'Cairo', sans-serif;
        font-size: 0.86rem;
        resize: vertical;
        transition: border-color 0.2s;
    }
    .notes-area-compact:focus {
        outline: none;
        border-color: var(--accent-color, #f59e0b);
    }

    /* Modal Footer Buttons */
    .modal-card-footer {
        display: flex;
        gap: 0.6rem;
        margin-top: 0.2rem;
    }
    .modal-card-footer button {
        flex: 1;
        padding: 0.7rem 0.8rem;
        border-radius: 12px;
        border: none;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        font-family: 'Cairo', sans-serif;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: all 0.2s ease;
    }
    .btn-modal-approve {
        background: #10b981 !important;
        color: white !important;
    }
    .btn-modal-approve:hover {
        background: #059669 !important;
    }
    .btn-modal-reject {
        background: #ef4444 !important;
        color: white !important;
    }
    .btn-modal-reject:hover {
        background: #dc2626 !important;
    }
</style>
@endpush

@section('content')
<div class="services-container">
    <div class="page-header">
        <h2>الخدمات والطلبات الطلابية</h2>
    </div>

    <!-- Tabs Navigation -->
    <div class="custom-tabs">
        <button class="tab-btn active" onclick="switchTab(this, 'mercy')">
            <i class="fa-solid fa-gavel"></i> طلبات الاسترحام
        </button>
        <button class="tab-btn" onclick="switchTab(this, 'documents')">
            <i class="fa-solid fa-file-invoice"></i> طلبات الوثائق
        </button>
        <button class="tab-btn" onclick="switchTab(this, 'makeup')">
            <i class="fa-solid fa-pen-to-square"></i> امتحانات الإكمال
        </button>
        <button class="tab-btn" onclick="switchTab(this, 'device-reset')">
            <i class="fa-solid fa-mobile-screen-button"></i> فك قفل الجهاز
        </button>
        <button class="tab-btn" onclick="switchTab(this, 'face-photo')">
            <i class="fa-solid fa-user-gear"></i> طلبات صورة الوجه / البصمة
        </button>
    </div>

    <!-- 1. Mercy Petitions Tab -->
    <div id="tab-mercy" class="tab-content active">
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الرقم الجامعي</th>
                        <th>العام الدراسي</th>
                        <th>موضوع الاسترحام</th>
                        <th>تاريخ الطلب</th>
                        <th>الحالة</th>
                        <th style="text-align: center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests->where('type', 'mercy') as $req)
                    <tr>
                        <td class="table-cell-nowrap">
                            <div class="table-student-cell">
                                <div class="table-avatar">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span class="table-student-name" title="{{ $req->student?->user?->full_name ?? 'غير معروف' }}">{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td>
                            <span class="table-text-truncate" title="{{ $req->formatted_details }}">{{ $req->formatted_details }}</span>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td class="table-cell-nowrap">
                            @if($req->status == 'pending_affairs')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @else
                                <span class="badge badge-approved">تم تحويله لرئيس القسم</span>
                            @endif
                        </td>
                        <td class="table-cell-nowrap" style="text-align: center;">
                            @php $canRespond = ($req->status == 'pending_affairs'); @endphp
                            <div class="action-btns">
                                <button type="button" class="btn-action-square {{ $canRespond ? 'btn-view' : 'btn-disabled' }}"
                                    title="{{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}"
                                    data-type="استرحام"
                                    data-type-key="{{ $req->type }}"
                                    data-name="{{ $req->student?->user?->full_name ?? 'غير معروف' }}"
                                    data-id="{{ $req->student?->student_code ?? 'N/A' }}"
                                    data-year="{{ $req->student?->user?->academic_year ?? 'N/A' }}"
                                    data-department="{{ $req->student?->program?->department?->name ?? 'غير محدد' }}"
                                    data-specialization="{{ $req->student?->program?->name ?? 'غير محدد' }}"
                                    data-details="{{ $req->formatted_details }}"
                                    data-affairs-notes="{{ $req->affairs_notes ?? ($canRespond ? '' : 'تم إرسال الرأي مسبقاً') }}"
                                    data-req-id="{{ $req->id }}"
                                    data-can-respond="{{ $canRespond ? 'true' : 'false' }}"
                                    onclick="openRequestModalFromBtn(this)">
                                    <i class="fa-solid {{ $canRespond ? 'fa-pen-to-square' : 'fa-eye' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($requests->where('type', 'mercy')->isEmpty())
                    <tr><td colspan="7" style="text-align: center;">لا توجد طلبات</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Documents Requests Tab -->
    <div id="tab-documents" class="tab-content">
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الرقم الجامعي</th>
                        <th>العام الدراسي</th>
                        <th>نوع الوثيقة</th>
                        <th>تاريخ الطلب</th>
                        <th>الحالة</th>
                        <th style="text-align: center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests->where('type', 'document') as $req)
                    <tr>
                        <td class="table-cell-nowrap">
                            <div class="table-student-cell">
                                <div class="table-avatar">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span class="table-student-name" title="{{ $req->student?->user?->full_name ?? 'غير معروف' }}">{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td>
                            <span class="table-text-truncate" title="{{ $req->formatted_details }}">{{ $req->formatted_details }}</span>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td class="table-cell-nowrap">
                            @if($req->status == 'pending_affairs')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @elseif($req->status == 'pending_hod')
                                <span class="badge badge-pending" style="background:#3b82f6; color:#fff;">عند رئيس القسم</span>
                            @elseif($req->status == 'pending_admin')
                                <span class="badge badge-pending" style="background:#8b5cf6; color:#fff;">عند إدارة المعهد</span>
                            @elseif($req->status == 'completed' && $req->admin_decision == 'approved')
                                <span class="badge badge-approved">معتمد ومكتمل ✅</span>
                            @elseif($req->status == 'completed' && $req->admin_decision == 'rejected')
                                <span class="badge badge-rejected">مرفوض ❌</span>
                            @else
                                <span class="badge badge-approved">تم تحويله للمتابعة</span>
                            @endif
                        </td>
                        <td class="table-cell-nowrap" style="text-align: center;">
                            @php 
                                $canRespond = ($req->status == 'pending_affairs');
                                $isFullyApproved = ($req->status == 'completed' && $req->admin_decision == 'approved');
                            @endphp
                            <div class="action-btns">
                                <button type="button" class="btn-action-square {{ $canRespond ? 'btn-view' : 'btn-disabled' }}"
                                    title="{{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}"
                                    data-type="وثيقة"
                                    data-type-key="{{ $req->type }}"
                                    data-name="{{ $req->student?->user?->full_name ?? 'غير معروف' }}"
                                    data-id="{{ $req->student?->student_code ?? 'N/A' }}"
                                    data-year="{{ $req->student?->user?->academic_year ?? 'N/A' }}"
                                    data-department="{{ $req->student?->program?->department?->name ?? 'غير محدد' }}"
                                    data-specialization="{{ $req->student?->program?->name ?? 'غير محدد' }}"
                                    data-details="{{ $req->formatted_details }}"
                                    data-affairs-notes="{{ $req->affairs_notes ?? ($canRespond ? '' : 'تم إرسال الرأي مسبقاً') }}"
                                    data-req-id="{{ $req->id }}"
                                    data-can-respond="{{ $canRespond ? 'true' : 'false' }}"
                                    onclick="openRequestModalFromBtn(this)">
                                    <i class="fa-solid {{ $canRespond ? 'fa-pen-to-square' : 'fa-eye' }}"></i>
                                </button>

                                @if($isFullyApproved)
                                    <a href="{{ route('affairs.course_weights', ['student_id' => $req->student_id, 'search' => $req->student?->student_code ?? $req->student?->user?->full_name]) }}"
                                       target="_blank"
                                       class="btn-action-square btn-pathway"
                                       title="المسار الأكاديمي للطالب">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($requests->where('type', 'document')->isEmpty())
                    <tr><td colspan="7" style="text-align: center;">لا توجد طلبات</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Makeup Exams Tab -->
    <div id="tab-makeup" class="tab-content">
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الرقم الجامعي</th>
                        <th>العام الدراسي</th>
                        <th>المواد المطلوبة للإكمال</th>
                        <th>تاريخ الطلب</th>
                        <th>الحالة</th>
                        <th style="text-align: center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests->where('type', 'makeup') as $req)
                    <tr>
                        <td class="table-cell-nowrap">
                            <div class="table-student-cell">
                                <div class="table-avatar">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span class="table-student-name" title="{{ $req->student?->user?->full_name ?? 'غير معروف' }}">{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td>
                            <span class="table-text-truncate" title="{{ $req->formatted_details }}">{{ $req->formatted_details }}</span>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td class="table-cell-nowrap">
                            @if($req->status == 'pending_affairs')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @else
                                <span class="badge badge-approved">تم تحويله لرئيس القسم</span>
                            @endif
                        </td>
                        <td class="table-cell-nowrap" style="text-align: center;">
                            @php $canRespond = ($req->status == 'pending_affairs'); @endphp
                            <div class="action-btns">
                                <button type="button" class="btn-action-square {{ $canRespond ? 'btn-view' : 'btn-disabled' }}"
                                    title="{{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}"
                                    data-type="إكمال"
                                    data-type-key="{{ $req->type }}"
                                    data-name="{{ $req->student?->user?->full_name ?? 'غير معروف' }}"
                                    data-id="{{ $req->student?->student_code ?? 'N/A' }}"
                                    data-year="{{ $req->student?->user?->academic_year ?? 'N/A' }}"
                                    data-department="{{ $req->student?->program?->department?->name ?? 'غير محدد' }}"
                                    data-specialization="{{ $req->student?->program?->name ?? 'غير محدد' }}"
                                    data-details="{{ $req->formatted_details }}"
                                    data-affairs-notes="{{ $req->affairs_notes ?? ($canRespond ? '' : 'تم إرسال الرأي مسبقاً') }}"
                                    data-req-id="{{ $req->id }}"
                                    data-can-respond="{{ $canRespond ? 'true' : 'false' }}"
                                    onclick="openRequestModalFromBtn(this)">
                                    <i class="fa-solid {{ $canRespond ? 'fa-pen-to-square' : 'fa-eye' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($requests->where('type', 'makeup')->isEmpty())
                    <tr><td colspan="7" style="text-align: center;">لا توجد طلبات</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. Device Reset Tab -->
    <div id="tab-device-reset" class="tab-content">
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الرقم الجامعي</th>
                        <th>العام الدراسي</th>
                        <th>السبب / التفاصيل</th>
                        <th>تاريخ الطلب</th>
                        <th>الحالة</th>
                        <th style="text-align: center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests->where('type', 'device_reset') as $req)
                    <tr>
                        <td class="table-cell-nowrap">
                            <div class="table-student-cell">
                                <div class="table-avatar">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span class="table-student-name" title="{{ $req->student?->user?->full_name ?? 'غير معروف' }}">{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td>
                            <span class="table-text-truncate" title="{{ $req->formatted_details }}">{{ $req->formatted_details }}</span>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td class="table-cell-nowrap">
                            @if($req->status == 'pending_affairs' || $req->status == 'pending')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @elseif($req->status == 'approved')
                                <span class="badge badge-approved">تم فك القفل</span>
                            @else
                                <span class="badge badge-rejected">مرفوض</span>
                            @endif
                        </td>
                        <td class="table-cell-nowrap" style="text-align: center;">
                            @php $canRespond = ($req->status == 'pending_affairs' || $req->status == 'pending'); @endphp
                            <div class="action-btns">
                                @if($canRespond)
                                <form method="POST" action="{{ route('affairs.student_services.direct_reset_device', $req->id) }}" style="display:inline; margin:0;" onsubmit="return confirm('هل أنت متأكد من فك قفل الجهاز وتصفير بيانات جهازه فوراً للطالب ({{ $req->student?->user?->full_name }})؟')">
                                    @csrf
                                    <button type="submit" class="btn-action-square btn-reset" title="فك قفل الجهاز فوراً">
                                        <i class="fa-solid fa-mobile-screen-button"></i>
                                    </button>
                                </form>
                                @endif
                                <button type="button" class="btn-action-square {{ $canRespond ? 'btn-view' : 'btn-disabled' }}"
                                    title="{{ $canRespond ? 'معالجة وتفاصيل الطلب' : 'معاينة (قراءة فقط)' }}"
                                    data-type="فك قفل الجهاز"
                                    data-type-key="{{ $req->type }}"
                                    data-name="{{ $req->student?->user?->full_name ?? 'غير معروف' }}"
                                    data-id="{{ $req->student?->student_code ?? 'N/A' }}"
                                    data-year="{{ $req->student?->user?->academic_year ?? 'N/A' }}"
                                    data-department="{{ $req->student?->program?->department?->name ?? 'غير محدد' }}"
                                    data-specialization="{{ $req->student?->program?->name ?? 'غير محدد' }}"
                                    data-details="{{ $req->formatted_details }}"
                                    data-affairs-notes="{{ $req->affairs_notes ?? ($canRespond ? '' : 'تم إتخاذ القرار مسبقاً') }}"
                                    data-req-id="{{ $req->id }}"
                                    data-can-respond="{{ $canRespond ? 'true' : 'false' }}"
                                    onclick="openRequestModalFromBtn(this)">
                                    <i class="fa-solid {{ $canRespond ? 'fa-pen-to-square' : 'fa-eye' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($requests->where('type', 'device_reset')->isEmpty())
                    <tr><td colspan="7" style="text-align: center;">لا توجد طلبات فك قفل جهاز</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. Face / Biometric Photo Tab -->
    <div id="tab-face-photo" class="tab-content">
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الرقم الجامعي</th>
                        <th>العام الدراسي</th>
                        <th style="text-align: center;">الصورة</th>
                        <th>السبب / التفاصيل</th>
                        <th>تاريخ الطلب</th>
                        <th>الحالة</th>
                        <th style="text-align: center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests->where('type', 'face_photo') as $req)
                    @php
                        $photoUrl = null;
                        if (!empty($req->details)) {
                            $decoded = json_decode($req->details, true);
                            if (is_array($decoded) && !empty($decoded['photo'])) {
                                $photoUrl = asset('storage/' . $decoded['photo']);
                            }
                        }
                        if (!$photoUrl && $req->student?->user?->avatar) {
                            $photoUrl = asset('storage/' . $req->student->user->avatar);
                        }
                    @endphp
                    <tr>
                        <td class="table-cell-nowrap">
                            <div class="table-student-cell">
                                @if($req->student?->user?->avatar)
                                    <img src="{{ asset('storage/' . $req->student->user->avatar) }}" alt="Avatar" style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid var(--accent-color); flex-shrink:0;">
                                @else
                                    <div class="table-avatar">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                @endif
                                <span class="table-student-name" title="{{ $req->student?->user?->full_name ?? 'غير معروف' }}">{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap" style="text-align: center;">
                            @if($photoUrl)
                                <a href="{{ $photoUrl }}" target="_blank" title="عرض الصورة">
                                    <img src="{{ $photoUrl }}" alt="صورة الطالب" style="width: 34px; height: 34px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color); vertical-align: middle;">
                                </a>
                            @else
                                <span style="color: var(--text-secondary); font-size: 0.8rem;">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="table-text-truncate" title="{{ $req->formatted_details }}">{{ $req->formatted_details }}</span>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td class="table-cell-nowrap">
                            @if($req->status == 'pending_affairs' || $req->status == 'pending')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @elseif($req->status == 'approved')
                                <span class="badge badge-approved">تم اعتماد الصورة</span>
                            @else
                                <span class="badge badge-rejected">مرفوض</span>
                            @endif
                        </td>
                        <td class="table-cell-nowrap" style="text-align: center;">
                            @php $canRespond = ($req->status == 'pending_affairs' || $req->status == 'pending'); @endphp
                            <div class="action-btns">
                                <button type="button" class="btn-action-square {{ $canRespond ? 'btn-view' : 'btn-disabled' }}"
                                    title="{{ $canRespond ? 'معالجة وتحديث الصورة' : 'معاينة (قراءة فقط)' }}"
                                    data-type="تحديث صورة بصمة الوجه"
                                    data-type-key="face_photo"
                                    data-photo-url="{{ $photoUrl ?? '' }}"
                                    data-name="{{ $req->student?->user?->full_name ?? 'غير معروف' }}"
                                    data-id="{{ $req->student?->student_code ?? 'N/A' }}"
                                    data-year="{{ $req->student?->user?->academic_year ?? 'N/A' }}"
                                    data-department="{{ $req->student?->program?->department?->name ?? 'غير محدد' }}"
                                    data-specialization="{{ $req->student?->program?->name ?? 'غير محدد' }}"
                                    data-details="{{ $req->formatted_details }}"
                                    data-affairs-notes="{{ $req->affairs_notes ?? ($canRespond ? '' : 'تم إتخاذ القرار مسبقاً') }}"
                                    data-req-id="{{ $req->id }}"
                                    data-can-respond="{{ $canRespond ? 'true' : 'false' }}"
                                    onclick="openRequestModalFromBtn(this)">
                                    <i class="fa-solid {{ $canRespond ? 'fa-user-pen' : 'fa-eye' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($requests->where('type', 'face_photo')->isEmpty())
                    <tr><td colspan="8" style="text-align: center;">لا توجد طلبات تحديث صورة شخصية أو بصمة وجه</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Request Details Modal - Compact Portrait Card -->
<div id="requestModal" class="modal-overlay" onclick="closeModalOnOutsideClick(event)">
    <form class="modal-content" id="decisionForm" method="POST" action="" enctype="multipart/form-data">
        @csrf
        
        <!-- Header -->
        <div class="modal-card-header">
            <div class="modal-card-title">
                <div class="modal-card-icon">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <h3>تفاصيل ومعالجة الطلب</h3>
                    <span id="modal-request-type"></span>
                </div>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeModal()" title="إغلاق">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Student Mini Card -->
        <div class="student-mini-card">
            <div class="student-mini-row">
                <span class="student-mini-label"><i class="fa-solid fa-user"></i> الطالب:</span>
                <span class="student-mini-value" id="modal-student-name"></span>
            </div>
            <div class="student-mini-row">
                <span class="student-mini-label"><i class="fa-solid fa-id-card"></i> الرقم الجامعي:</span>
                <span class="student-mini-value" id="modal-student-id"></span>
            </div>
            <div class="student-mini-row">
                <span class="student-mini-label"><i class="fa-solid fa-building-columns"></i> القسم والتخصص:</span>
                <span class="student-mini-value">
                    <span id="modal-student-department"></span> - <span id="modal-student-specialization"></span>
                </span>
            </div>
            <div class="student-mini-row">
                <span class="student-mini-label"><i class="fa-solid fa-calendar"></i> السنة الدراسية:</span>
                <span class="student-mini-value" id="modal-student-year" style="color: var(--accent-color, #f59e0b);"></span>
            </div>
        </div>

        <!-- Request Details -->
        <div class="modal-detail-box">
            <label><i class="fa-solid fa-comment-dots"></i> تفاصيل الطلب / السبب:</label>
            <div class="modal-detail-content" id="modal-request-details"></div>
        </div>

        <!-- Face Photo Preview & Upload Section -->
        <div id="modal-photo-section" style="display: none; padding: 0.75rem 0.85rem; border: 1.5px dashed var(--accent-color, #f59e0b); border-radius: 12px; background: rgba(245, 158, 11, 0.04);">
            <div style="display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.6rem;">
                <div id="modal-photo-preview-wrap" style="display: none;">
                    <img id="modal-photo-preview" src="" alt="صورة الطالب" style="width: 55px; height: 55px; object-fit: cover; border-radius: 8px; border: 2px solid var(--accent-color, #f59e0b); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                </div>
                <div>
                    <strong style="display: block; font-size: 0.86rem; color: var(--text-primary); margin-bottom: 0.2rem;">
                        <i class="fa-solid fa-camera"></i> صورة الطالب المرفقة
                    </strong>
                    <span id="modal-photo-status" style="font-size: 0.78rem; color: var(--text-secondary); line-height: 1.3; display: block;"></span>
                </div>
            </div>

            <div class="modal-detail-box" id="modal-photo-input-container" style="margin-bottom: 0;">
                <label style="font-size: 0.82rem; font-weight: 700; color: var(--text-primary); display: block;">
                    <i class="fa-solid fa-image"></i> اختيار صورة بديلة (JPG, PNG, WEBP):
                </label>
                <input type="file" name="photo" id="modal-photo-input" style="padding: 0.45rem; cursor: pointer; width: 100%; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-primary); font-size: 0.82rem;" accept="image/jpeg,image/png,image/jpg,image/webp">
            </div>
        </div>
        
        <!-- Affairs Notes -->
        <div class="modal-detail-box">
            <label><i class="fa-solid fa-pen"></i> رأي وملاحظات الشؤون:</label>
            <textarea class="notes-area-compact" name="notes" id="modal-notes" placeholder="اكتب ملاحظات الشؤون هنا..."></textarea>
            <input type="hidden" name="decision" id="decisionInput" value="approved">
        </div>

        <!-- Footer -->
        <div id="modal-readonly-badge" style="display: none; color: #6b7280; font-size: 0.85rem; font-weight: bold; text-align: center; padding: 0.5rem 0;">
            <i class="fa-solid fa-lock"></i> تم اتخاذ القرار وتسجيله مسبقاً
        </div>
        <div class="modal-card-footer" id="modal-footer-actions">
            <button type="button" class="btn-modal-reject" id="modal-btn-reject" onclick="submitDecision('rejected')">
                <i class="fa-solid fa-xmark"></i> <span id="modal-reject-text">عدم موافقة</span>
            </button>
            <button type="button" class="btn-modal-approve" id="modal-btn-approve" onclick="submitDecision('approved')">
                <i class="fa-solid fa-check"></i> <span id="modal-approve-text">موافقة</span>
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
    function switchTab(btnElement, tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        btnElement.classList.add('active');
        const targetTab = document.getElementById('tab-' + tabName);
        if (targetTab) {
            targetTab.classList.add('active');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        let tabParam = urlParams.get('tab') || urlParams.get('type');
        if (tabParam) {
            tabParam = tabParam.replace('_', '-');
            if (tabParam === 'document') tabParam = 'documents';
            
            const targetBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => {
                const onClickAttr = btn.getAttribute('onclick') || '';
                return onClickAttr.includes(tabParam);
            });

            if (targetBtn) {
                targetBtn.click();
            }
        }
    });

    function openRequestModalFromBtn(btn) {
        const type = btn.getAttribute('data-type') || '';
        const typeKey = btn.getAttribute('data-type-key') || '';
        const photoUrl = btn.getAttribute('data-photo-url') || '';
        const name = btn.getAttribute('data-name') || '';
        const id = btn.getAttribute('data-id') || '';
        const year = btn.getAttribute('data-year') || '';
        const department = btn.getAttribute('data-department') || '';
        const specialization = btn.getAttribute('data-specialization') || '';
        const details = btn.getAttribute('data-details') || '';
        const affairsNotes = btn.getAttribute('data-affairs-notes') || '';
        const reqId = btn.getAttribute('data-req-id') || '';
        const canRespond = btn.getAttribute('data-can-respond') === 'true';

        openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId, canRespond, typeKey, photoUrl);
    }

    function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId, canRespond, typeKey, photoUrl) {
        document.getElementById('modal-request-type').innerText = type;
        document.getElementById('modal-student-name').innerText = name;
        document.getElementById('modal-student-id').innerText = id;
        document.getElementById('modal-student-department').innerText = department;
        document.getElementById('modal-student-specialization').innerText = specialization;
        document.getElementById('modal-student-year').innerText = year;
        
        let displayDetails = details;
        if (details && (details.trim().startsWith('{') || details.trim().startsWith('['))) {
            try {
                const parsed = JSON.parse(details);
                let parts = [];
                if (parsed.reason) parts.push('السبب: ' + parsed.reason);
                if (parsed.new_device_id) parts.push('رمز تعريف الجهاز الجديد (Device ID): ' + parsed.new_device_id);
                if (parts.length > 0) displayDetails = parts.join('\n');
            } catch(e) {}
        }
        document.getElementById('modal-request-details').innerText = displayDetails;
        document.getElementById('decisionForm').action = '/affairs/student-services/' + reqId + '/process';
        
        // Handle Photo Section for face_photo
        const photoSection = document.getElementById('modal-photo-section');
        const photoPreviewWrap = document.getElementById('modal-photo-preview-wrap');
        const photoPreview = document.getElementById('modal-photo-preview');
        const photoStatus = document.getElementById('modal-photo-status');
        const photoInput = document.getElementById('modal-photo-input');
        const photoInputContainer = document.getElementById('modal-photo-input-container');

        if (photoInput) {
            photoInput.value = '';
        }

        if (typeKey === 'face_photo' || type.includes('صورة') || type.includes('بصمة')) {
            photoSection.style.display = 'block';
            if (photoUrl) {
                photoPreview.src = photoUrl;
                photoPreviewWrap.style.display = 'block';
                photoStatus.innerText = 'هذه هي الصورة المرفقة بالطلب حالياً، يمكنك اعتمادها أو رفع بديل عنها أدناه.';
            } else {
                photoPreviewWrap.style.display = 'none';
                photoStatus.innerText = 'لم يقم الطالب بإرفاق ملف صورة بالطلب، يمكنك اختيار صورة جديدة له أدناه.';
            }

            if (canRespond) {
                photoInputContainer.style.display = 'block';
                photoInput.disabled = false;
            } else {
                photoInputContainer.style.display = 'none';
                photoInput.disabled = true;
            }

            document.getElementById('modal-approve-text').innerText = 'موافقة واعتماد الصورة الجديدة';
            document.getElementById('modal-reject-text').innerText = 'رفض الطلب';
        } else if (typeKey === 'device_reset' || type.includes('قفل')) {
            photoSection.style.display = 'none';
            document.getElementById('modal-approve-text').innerText = 'موافقة وفك قفل الجهاز';
            document.getElementById('modal-reject-text').innerText = 'رفض الطلب';
        } else {
            photoSection.style.display = 'none';
            document.getElementById('modal-approve-text').innerText = 'موافقة وتحويل لرئيس القسم';
            document.getElementById('modal-reject-text').innerText = 'عدم موافقة وتحويل لرئيس القسم';
        }

        const notesElement = document.getElementById('modal-notes');
        const footerActions = document.getElementById('modal-footer-actions');
        const readonlyBadge = document.getElementById('modal-readonly-badge');

        notesElement.value = affairsNotes || '';
        
        if (canRespond) {
            notesElement.readOnly = false;
            footerActions.style.display = 'flex';
            readonlyBadge.style.display = 'none';
        } else {
            notesElement.readOnly = true;
            footerActions.style.display = 'none';
            readonlyBadge.style.display = 'block';
        }
        
        const modal = document.getElementById('requestModal');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
    }

    function closeModal() {
        const modal = document.getElementById('requestModal');
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    function closeModalOnOutsideClick(event) {
        const modalContent = document.querySelector('.modal-content');
        if (!modalContent.contains(event.target)) {
            closeModal();
        }
    }

    function submitDecision(decision) {
        const notesElement = document.getElementById('modal-notes');
        const notes = notesElement.value.trim();
        
        if (notes === '') {
            notesElement.style.borderColor = '#ef4444';
            notesElement.focus();
            notesElement.style.transform = 'translateX(5px)';
            setTimeout(() => notesElement.style.transform = 'translateX(-5px)', 100);
            setTimeout(() => notesElement.style.transform = 'translateX(5px)', 200);
            setTimeout(() => notesElement.style.transform = 'translateX(0)', 300);
            alert('❌ عذراً، يجب كتابة الملاحظات قبل اتخاذ قرار الرفض أو القبول!');
            return;
        }
        
        document.getElementById('decisionInput').value = decision;
        document.getElementById('decisionForm').submit();
    }

</script>
@endpush
