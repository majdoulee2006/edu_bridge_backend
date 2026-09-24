@extends('layouts.hod')
@section('title', 'الخدمات الطلابية')
@section('header-title', 'الخدمات والطلبات الطلابية')
@section('header-subtitle', 'إدارة الطلبات من مختلف الأقسام')

@push('styles')
<style>
    /* Tabs Styling */
    .custom-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid var(--border-color, #e2e8f0);
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
        color: var(--text-secondary, #64748b);
        font-size: 1.05rem;
        font-weight: 700;
        padding: 0.8rem 1.5rem;
        cursor: pointer;
        position: relative;
        transition: color 0.3s;
        white-space: nowrap !important;
        border-radius: 8px 8px 0 0;
    }
    [data-theme="dark"] .tab-btn { color: #94a3b8; }
    .tab-btn:hover {
        color: var(--text-primary, #0f172a);
        background: var(--bg-secondary, #f8fafc);
    }
    [data-theme="dark"] .tab-btn:hover {
        color: #f8fafc;
        background: #1e293b;
    }
    .tab-btn.active {
        color: #f2f20d;
    }
    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -0.65rem;
        left: 0;
        width: 100%;
        height: 3px;
        background: #f2f20d;
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
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Tables - Fixed Height & Strictly Single-Line */
    .table-container {
        background: var(--surface-light, #ffffff);
        border-radius: 1.25rem;
        padding: 1.2rem;
        box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06);
        overflow-x: auto;
    }
    [data-theme="dark"] .table-container {
        background: var(--surface-dark, #1a2633);
        box-shadow: none;
    }
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
        white-space: nowrap !important;
    }
    .custom-table th {
        text-align: right;
        padding: 0.9rem 1rem !important;
        color: var(--text-secondary, #64748b);
        font-weight: 800;
        border-bottom: 2px solid var(--border-color, #e2e8f0);
        white-space: nowrap !important;
        font-size: 0.9rem;
    }
    [data-theme="dark"] .custom-table th {
        color: #94a3b8;
        border-bottom-color: #334155;
    }
    .custom-table td {
        padding: 0.65rem 1rem !important;
        color: var(--text-primary, #0f172a);
        font-weight: 600;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        vertical-align: middle !important;
        white-space: nowrap !important;
        height: 56px;
        line-height: 1.2;
    }
    [data-theme="dark"] .custom-table td {
        color: #f8fafc;
        border-bottom-color: #1e293b;
    }
    .custom-table tr:hover td {
        background: rgba(0, 0, 0, 0.02);
    }
    [data-theme="dark"] .custom-table tr:hover td {
        background: rgba(255, 255, 255, 0.02);
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
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap !important;
        vertical-align: middle;
        font-size: 0.9rem;
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
    .badge-admin-review { background: #c4b5fd; color: #4c1d95; } /* بانتظار قرار الإدارة */
    [data-theme="dark"] .badge-admin-review { background: #4c1d95; color: #ddd6fe; }

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    .btn-action {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s;
        color: white;
        flex-shrink: 0;
    }
    .btn-action:hover {
        transform: scale(1.1);
    }
    .btn-view { background: #3b82f6; }

    /* Modal Styling - Compact Portrait Card (like affairs) */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(4px);
        z-index: 9999;
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
    .modal-content {
        background: var(--bg-primary, #ffffff) !important;
        border-radius: 1.25rem !important;
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
    [data-theme="dark"] .modal-content {
        background: var(--bg-secondary, #121212) !important;
        border-color: #262626 !important;
        color: white;
    }
    .modal-overlay.active .modal-content {
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
    [data-theme="dark"] .notes-area-compact {
        background: #0f172a;
        border-color: #334155;
        color: white;
    }
    .notes-area-compact:focus {
        outline: none;
        border-color: var(--accent-color, #f2f20d);
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

    <!-- Main Tabs Navigation -->
    <div class="custom-tabs" style="border-bottom: 3px solid var(--accent-color); margin-bottom: 2rem;">
        <button class="tab-btn active" onclick="switchMainTab(this, 'pending')" style="font-size: 1.2rem; border-radius: 12px 12px 0 0;">
            <i class="fa-regular fa-clock"></i> طلبات معلقة
        </button>
        <button class="tab-btn" onclick="switchMainTab(this, 'completed')" style="font-size: 1.2rem; border-radius: 12px 12px 0 0;">
            <i class="fa-solid fa-check-double"></i> طلبات منتهية
        </button>
    </div>

    @foreach(['pending', 'completed'] as $statusGrp)
    <div id="main-tab-{{ $statusGrp }}" class="main-tab-content" style="display: {{ $loop->first ? 'block' : 'none' }}; animation: fadeIn 0.3s ease;">
        
        <!-- Sub Tabs Navigation -->
        <div class="custom-tabs">
            <button class="tab-btn active" onclick="switchSubTab(this, '{{ $statusGrp }}-mercy', '{{ $statusGrp }}')">
                <i class="fa-solid fa-gavel"></i> طلبات الاسترحام
            </button>
            <button class="tab-btn" onclick="switchSubTab(this, '{{ $statusGrp }}-documents', '{{ $statusGrp }}')">
                <i class="fa-solid fa-file-invoice"></i> طلبات الوثائق
            </button>
            <button class="tab-btn" onclick="switchSubTab(this, '{{ $statusGrp }}-makeup', '{{ $statusGrp }}')">
                <i class="fa-solid fa-pen-to-square"></i> امتحانات الإكمال
            </button>
        </div>

    <!-- 1. Mercy Petitions Tab -->
    <div id="tab-{{ $statusGrp }}-mercy" class="sub-tab-content-{{ $statusGrp }}" style="display: block; animation: fadeIn 0.3s ease;">
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @php $filtered = $requests->where('type', 'mercy')->filter(function($r) use ($statusGrp) { return $statusGrp == 'pending' ? ($r->status == 'pending_hod') : ($r->status != 'pending_hod'); }); @endphp
                    @foreach($filtered as $req)
                    <tr>
                        <td class="table-cell-nowrap">
                            <div class="table-student-cell">
                                <div class="table-avatar">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span class="table-student-name" title="{{ $req->student?->user?->full_name ?? 'غير معروف' }}">{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">
                            <span class="table-text-truncate" title="{{ $req->formatted_details }}">{{ $req->formatted_details }}</span>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td class="table-cell-nowrap">
                            @if($req->status == 'pending_hod')
                                <span class="badge badge-pending">بانتظار مراجعتك</span>
                            @elseif($req->status == 'pending_admin')
                                <span class="badge badge-pending">قيد مراجعة الإدارة</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif
                        </td>
                        <td class="table-cell-nowrap">
                            <div class="action-btns">
                                @php $canRespond = ($req->status == 'pending_hod'); @endphp
                                <button class="btn-action btn-view" title="عرض التفاصيل" 
                                    data-type="{{ $req->type == 'mercy' ? 'استرحام' : ($req->type == 'makeup' ? 'إكمال' : 'وثيقة') }}"
                                    data-name="{{ $req->student?->user?->full_name ?? 'غير معروف' }}"
                                    data-id="{{ $req->student?->student_code ?? 'N/A' }}"
                                    data-year="{{ $req->student?->user?->academic_year ?? 'N/A' }}"
                                    data-department="{{ $req->student?->program?->department?->name ?? 'غير محدد' }}"
                                    data-specialization="{{ $req->student?->program?->name ?? 'غير محدد' }}"
                                    data-details="{{ $req->formatted_details }}"
                                    data-affairs-notes="{{ $req->affairs_notes ?? 'لا توجد ملاحظات' }}"
                                    data-req-id="{{ $req->id }}"
                                    data-can-respond="{{ $canRespond ? 'true' : 'false' }}"
                                    onclick="openRequestModalFromBtn(this)"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($filtered->isEmpty())
                    <tr><td colspan="7" style="text-align: center;">لا توجد طلبات</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Documents Requests Tab -->
    <div id="tab-{{ $statusGrp }}-documents" class="sub-tab-content-{{ $statusGrp }}" style="display: none; animation: fadeIn 0.3s ease;">
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @php $filtered = $requests->where('type', 'document')->filter(function($r) use ($statusGrp) { return $statusGrp == 'pending' ? ($r->status == 'pending_hod') : ($r->status != 'pending_hod'); }); @endphp
                    @foreach($filtered as $req)
                    <tr>
                        <td class="table-cell-nowrap">
                            <div class="table-student-cell">
                                <div class="table-avatar">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span class="table-student-name" title="{{ $req->student?->user?->full_name ?? 'غير معروف' }}">{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">
                            <span class="table-text-truncate" title="{{ $req->formatted_details }}">{{ $req->formatted_details }}</span>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td class="table-cell-nowrap">
                            @if($req->status == 'pending_hod')
                                <span class="badge badge-pending">بانتظار مراجعتك</span>
                            @elseif($req->status == 'pending_admin')
                                <span class="badge badge-pending">قيد مراجعة الإدارة</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif
                        </td>
                        <td class="table-cell-nowrap">
                            <div class="action-btns">
                                @php $canRespond = ($req->status == 'pending_hod'); @endphp
                                <button class="btn-action btn-view" title="عرض التفاصيل" 
                                    data-type="وثيقة"
                                    data-name="{{ $req->student?->user?->full_name ?? 'غير معروف' }}"
                                    data-id="{{ $req->student?->student_code ?? 'N/A' }}"
                                    data-year="{{ $req->student?->user?->academic_year ?? 'N/A' }}"
                                    data-department="{{ $req->student?->program?->department?->name ?? 'غير محدد' }}"
                                    data-specialization="{{ $req->student?->program?->name ?? 'غير محدد' }}"
                                    data-details="{{ $req->formatted_details }}"
                                    data-affairs-notes="{{ $req->affairs_notes ?? 'لا توجد ملاحظات' }}"
                                    data-req-id="{{ $req->id }}"
                                    data-can-respond="{{ $canRespond ? 'true' : 'false' }}"
                                    onclick="openRequestModalFromBtn(this)"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($filtered->isEmpty())
                    <tr><td colspan="7" style="text-align: center;">لا توجد طلبات</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Makeup Exams Tab -->
    <div id="tab-{{ $statusGrp }}-makeup" class="sub-tab-content-{{ $statusGrp }}" style="display: none; animation: fadeIn 0.3s ease;">
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @php $filtered = $requests->where('type', 'makeup')->filter(function($r) use ($statusGrp) { return $statusGrp == 'pending' ? ($r->status == 'pending_hod') : ($r->status != 'pending_hod'); }); @endphp
                    @foreach($filtered as $req)
                    <tr>
                        <td class="table-cell-nowrap">
                            <div class="table-student-cell">
                                <div class="table-avatar">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span class="table-student-name" title="{{ $req->student?->user?->full_name ?? 'غير معروف' }}">{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td class="table-cell-nowrap">
                            <span class="table-text-truncate" title="{{ $req->formatted_details }}">{{ $req->formatted_details }}</span>
                        </td>
                        <td class="table-cell-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td class="table-cell-nowrap">
                            @if($req->status == 'pending_hod')
                                <span class="badge badge-pending">بانتظار مراجعتك</span>
                            @elseif($req->status == 'pending_admin')
                                <span class="badge badge-pending">قيد مراجعة الإدارة</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif
                        </td>
                        <td class="table-cell-nowrap">
                            <div class="action-btns">
                                @php $canRespond = ($req->status == 'pending_hod'); @endphp
                                <button class="btn-action btn-view" title="عرض التفاصيل" 
                                    data-type="إكمال"
                                    data-name="{{ $req->student?->user?->full_name ?? 'غير معروف' }}"
                                    data-id="{{ $req->student?->student_code ?? 'N/A' }}"
                                    data-year="{{ $req->student?->user?->academic_year ?? 'N/A' }}"
                                    data-department="{{ $req->student?->program?->department?->name ?? 'غير محدد' }}"
                                    data-specialization="{{ $req->student?->program?->name ?? 'غير محدد' }}"
                                    data-details="{{ $req->formatted_details }}"
                                    data-affairs-notes="{{ $req->affairs_notes ?? 'لا توجد ملاحظات' }}"
                                    data-req-id="{{ $req->id }}"
                                    data-can-respond="{{ $canRespond ? 'true' : 'false' }}"
                                    onclick="openRequestModalFromBtn(this)"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($filtered->isEmpty())
                    <tr><td colspan="7" style="text-align: center;">لا توجد طلبات</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    </div>
    @endforeach
<!-- Request Details Modal - Compact Portrait Card -->
<div id="requestModal" class="modal-overlay" onclick="closeModalOnOutsideClick(event)">
    <form class="modal-content" id="decisionForm" method="POST" action="">
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

        <!-- Affairs Notes (Read-only) -->
        <div class="modal-detail-box" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 12px; padding: 0.7rem 0.85rem;">
            <label style="color: #b45309;"><i class="fa-solid fa-clipboard-check"></i> رأي وملاحظات الشؤون:</label>
            <div id="modal-affairs-notes" style="font-size: 0.86rem; font-weight: 600; line-height: 1.45; margin-top: 0.25rem;"></div>
        </div>
        
        <!-- HOD Notes Area -->
        <div class="modal-detail-box">
            <label><i class="fa-solid fa-pen-nib"></i> رأي وملاحظات رئيس القسم <span style="color: #ef4444;">(مطلوب إجبارياً)</span>:</label>
            <textarea class="notes-area-compact" name="notes" id="modal-hod-notes" placeholder="اكتب رأيك أسباب القبول أو الرفض ليتم تحويل الطلب مع رأيك إلى الإدارة..."></textarea>
            <input type="hidden" name="decision" id="modal-decision" value="approved">
        </div>

        <!-- Footer -->
        <div class="modal-card-footer">
            <button type="button" class="btn-modal-reject" onclick="submitDecision('reject')">
                <i class="fa-solid fa-xmark"></i> عدم موافقة وتحويل للإدارة
            </button>
            <button type="button" class="btn-modal-approve" onclick="submitDecision('approve')">
                <i class="fa-solid fa-check"></i> موافقة وتحويل للإدارة
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
    function switchMainTab(btnElement, statusGrp) {
        btnElement.parentElement.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
        
        document.querySelectorAll('.main-tab-content').forEach(content => {
            content.style.display = 'none';
        });
        const targetMainTab = document.getElementById('main-tab-' + statusGrp);
        if (targetMainTab) {
            targetMainTab.style.display = 'block';
        }
    }

    function switchSubTab(btnElement, tabId, statusGrp) {
        btnElement.parentElement.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
        
        document.querySelectorAll('.sub-tab-content-' + statusGrp).forEach(content => {
            content.style.display = 'none';
        });
        const targetTab = document.getElementById('tab-' + tabId);
        if (targetTab) {
            targetTab.style.display = 'block';
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
        const name = btn.getAttribute('data-name') || '';
        const id = btn.getAttribute('data-id') || '';
        const year = btn.getAttribute('data-year') || '';
        const department = btn.getAttribute('data-department') || '';
        const specialization = btn.getAttribute('data-specialization') || '';
        const details = btn.getAttribute('data-details') || '';
        const affairsNotes = btn.getAttribute('data-affairs-notes') || 'لا توجد ملاحظات';
        const reqId = btn.getAttribute('data-req-id') || '';
        const canRespond = btn.getAttribute('data-can-respond') === 'true';

        openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId, canRespond);
    }

    function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId, canRespond) {
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
                if (parsed.reason) parts.push('سبب الطلب: ' + parsed.reason);
                if (parsed.new_device_id) parts.push('معرف الجهاز الجديد: ' + parsed.new_device_id);
                if (parts.length > 0) displayDetails = parts.join('\n');
            } catch(e) {}
        }
        document.getElementById('modal-request-details').innerText = displayDetails;
        document.getElementById('modal-affairs-notes').innerText = affairsNotes;
        
        document.getElementById('decisionForm').action = '/hod/student-services/' + reqId + '/process';
        
        const notesElement = document.getElementById('modal-hod-notes');
        notesElement.style.borderColor = '';
        
        const footer = document.querySelector('.modal-card-footer');
        if (canRespond) {
            notesElement.value = '';
            notesElement.readOnly = false;
            footer.style.display = 'flex';
        } else {
            notesElement.value = 'تم إبداء رأيك مسبقاً، لا يمكن التعديل.';
            notesElement.readOnly = true;
            footer.style.display = 'none';
        }
        
        const modal = document.getElementById('requestModal');
        modal.style.display = 'flex';
        // Trigger animation after display flex
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
        const notesElement = document.getElementById('modal-hod-notes');
        const notes = notesElement.value.trim();
        
        // التحقق الإجباري من وجود الملاحظات
        if (notes === '') {
            notesElement.style.borderColor = '#ef4444'; // تلوين الحواف بالأحمر
            notesElement.focus();
            
            // اهتزاز خفيف للفت الانتباه
            notesElement.style.transform = 'translateX(5px)';
            setTimeout(() => notesElement.style.transform = 'translateX(-5px)', 100);
            setTimeout(() => notesElement.style.transform = 'translateX(5px)', 200);
            setTimeout(() => notesElement.style.transform = 'translateX(0)', 300);
            
            alert('❌ عذراً، يجب كتابة القرار النهائي للإدارة قبل الموافقة أو الرفض!');
            return;
        }
        
        document.getElementById('modal-decision').value = decision === 'approve' ? 'approved' : 'rejected';
        document.getElementById('decisionForm').submit();
    }
</script>
@endpush
