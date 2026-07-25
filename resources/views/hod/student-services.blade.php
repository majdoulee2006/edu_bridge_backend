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
        white-space: nowrap;
        border-radius: 8px 8px 0 0;
    }
    .dark .tab-btn { color: #94a3b8; }
    .tab-btn:hover {
        color: var(--text-primary, #0f172a);
        background: var(--bg-secondary, #f8fafc);
    }
    .dark .tab-btn:hover {
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

    /* Tables */
    .table-container {
        background: var(--surface-light, #ffffff);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06);
        overflow-x: auto;
    }
    .dark .table-container {
        background: var(--surface-dark, #1a2633);
        box-shadow: none;
    }
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    .custom-table th {
        text-align: right;
        padding: 1.2rem 1rem;
        color: var(--text-secondary, #64748b);
        font-weight: 800;
        border-bottom: 2px solid var(--border-color, #e2e8f0);
        white-space: nowrap;
    }
    .dark .custom-table th {
        color: #94a3b8;
        border-bottom-color: #334155;
    }
    .custom-table td {
        padding: 1rem;
        color: var(--text-primary, #0f172a);
        font-weight: 600;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        vertical-align: middle;
    }
    .dark .custom-table td {
        color: #f8fafc;
        border-bottom-color: #1e293b;
    }
    .custom-table tr:hover td {
        background: rgba(0, 0, 0, 0.02);
    }
    .dark .custom-table tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }

    /* Status Badges */
    .badge {
        padding: 0.4rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.85rem;
        font-weight: 700;
        display: inline-block;
    }
    .badge-pending { background: #fef08a; color: #854d0e; }
    .badge-approved { background: #bbf7d0; color: #166534; }
    .badge-rejected { background: #fecaca; color: #991b1b; }
    .badge-admin-review { background: #c4b5fd; color: #4c1d95; } /* بانتظار قرار الإدارة */
    .dark .badge-admin-review { background: #4c1d95; color: #ddd6fe; }

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 0.5rem;
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
    }
    .btn-action:hover {
        transform: scale(1.1);
    }
    .btn-view { background: #3b82f6; }

    /* Modal Styling */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.active {
        display: flex;
        opacity: 1;
    }
    .modal-content {
        background: var(--surface-light, #ffffff);
        border-radius: 1.25rem;
        width: 90%;
        max-width: 600px;
        padding: 2rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        transform: scale(0.9);
        transition: transform 0.3s ease;
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
    }
    .dark .modal-content {
        background: var(--surface-dark, #1a2633);
        color: white;
    }
    .modal-overlay.active .modal-content {
        transform: scale(1);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        padding-bottom: 1rem;
    }
    .dark .modal-header { border-bottom-color: #334155; }
    .modal-header h3 {
        font-size: 1.4rem;
        font-weight: 800;
        margin: 0;
    }
    .btn-close-modal {
        background: transparent;
        border: none;
        font-size: 1.5rem;
        color: var(--text-secondary, #64748b);
        cursor: pointer;
    }
    .modal-body .detail-row {
        margin-bottom: 1rem;
    }
    .detail-row label {
        display: block;
        font-size: 0.9rem;
        color: var(--text-secondary, #64748b);
        font-weight: 700;
        margin-bottom: 0.3rem;
    }
    .dark .detail-row label { color: #94a3b8; }
    .detail-row .detail-value {
        font-size: 1.05rem;
        font-weight: 600;
    }
    .modal-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .notes-area {
        width: 100%;
        min-height: 100px;
        border: 2px solid var(--border-color, #e2e8f0);
        border-radius: 10px;
        padding: 0.8rem;
        background: var(--bg-secondary, #f8fafc);
        color: var(--text-primary, #0f172a);
        font-family: 'Cairo', sans-serif;
        resize: vertical;
        margin-top: 0.5rem;
        transition: border-color 0.3s;
    }
    .dark .notes-area {
        background: #0f172a;
        border-color: #334155;
        color: white;
    }
    .notes-area:focus {
        outline: none;
        border-color: #f2f20d;
    }
    .modal-footer {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }
    .modal-footer button {
        flex: 1;
        padding: 0.8rem;
        border-radius: 10px;
        border: none;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        font-family: 'Cairo', sans-serif;
        transition: opacity 0.2s;
    }
    .modal-footer button:hover { opacity: 0.9; }
    .btn-modal-approve { background: #10b981; color: white; }
    .btn-modal-reject { background: #ef4444; color: white; }
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
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">{{ mb_substr($req->student->user->full_name ?? 'ط', 0, 1) }}</div>
                                <span>{{ $req->student->user->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td>{{ $req->student->student_code ?? 'N/A' }}</td>
                        <td>{{ $req->student->user->academic_year ?? 'N/A' }}</td>
                        <td>{{ Str::limit($req->details, 30) }}</td>
                        <td>{{ $req->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if($req->status == 'pending_hod')
                                <span class="badge badge-pending">بانتظار مراجعتك</span>
                            @elseif($req->status == 'pending_admin')
                                <span class="badge badge-pending">قيد مراجعة الإدارة</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif</td>
                        <td>
                            <div class="action-btns">
                                @php $canRespond = ($req->status == 'pending_hod'); @endphp
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('mercy', '{{ $req->student->user->full_name ?? '' }}', '{{ $req->student->student_code ?? '' }}', '{{ $req->student->user->academic_year ?? '' }}', '{{ $req->student->program->department->name ?? 'غير محدد' }}', '{{ $req->student->program->name ?? 'غير محدد' }}', `{{ addslashes($req->details) }}`, `{{ addslashes($req->affairs_notes ?? "لا توجد ملاحظات") }}`, {{ $req->id }}, {{ $canRespond ? "true" : "false" }})"><i class="fa-solid fa-eye"></i></button>
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
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">{{ mb_substr($req->student->user->full_name ?? 'ط', 0, 1) }}</div>
                                <span>{{ $req->student->user->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td>{{ $req->student->student_code ?? 'N/A' }}</td>
                        <td>{{ $req->student->user->academic_year ?? 'N/A' }}</td>
                        <td>{{ Str::limit($req->details, 30) }}</td>
                        <td>{{ $req->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if($req->status == 'pending_hod')
                                <span class="badge badge-pending">بانتظار مراجعتك</span>
                            @elseif($req->status == 'pending_admin')
                                <span class="badge badge-pending">قيد مراجعة الإدارة</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif</td>
                        <td>
                            <div class="action-btns">
                                @php $canRespond = ($req->status == 'pending_hod'); @endphp
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('document', '{{ $req->student->user->full_name ?? '' }}', '{{ $req->student->student_code ?? '' }}', '{{ $req->student->user->academic_year ?? '' }}', '{{ $req->student->program->department->name ?? 'غير محدد' }}', '{{ $req->student->program->name ?? 'غير محدد' }}', `{{ addslashes($req->details) }}`, `{{ addslashes($req->affairs_notes ?? "لا توجد ملاحظات") }}`, {{ $req->id }}, {{ $canRespond ? "true" : "false" }})"><i class="fa-solid fa-eye"></i></button>
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
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">{{ mb_substr($req->student->user->full_name ?? 'ط', 0, 1) }}</div>
                                <span>{{ $req->student->user->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td>{{ $req->student->student_code ?? 'N/A' }}</td>
                        <td>{{ $req->student->user->academic_year ?? 'N/A' }}</td>
                        <td>{{ Str::limit($req->details, 30) }}</td>
                        <td>{{ $req->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if($req->status == 'pending_hod')
                                <span class="badge badge-pending">بانتظار مراجعتك</span>
                            @elseif($req->status == 'pending_admin')
                                <span class="badge badge-pending">قيد مراجعة الإدارة</span>
                            @else
                                <span class="badge badge-approved">منتهي</span>
                            @endif</td>
                        <td>
                            <div class="action-btns">
                                @php $canRespond = ($req->status == 'pending_hod'); @endphp
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('makeup', '{{ $req->student->user->full_name ?? '' }}', '{{ $req->student->student_code ?? '' }}', '{{ $req->student->user->academic_year ?? '' }}', '{{ $req->student->program->department->name ?? 'غير محدد' }}', '{{ $req->student->program->name ?? 'غير محدد' }}', `{{ addslashes($req->details) }}`, `{{ addslashes($req->affairs_notes ?? "لا توجد ملاحظات") }}`, {{ $req->id }}, {{ $canRespond ? "true" : "false" }})"><i class="fa-solid fa-eye"></i></button>
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
<!-- Request Details Modal -->
<div id="requestModal" class="modal-overlay" onclick="closeModalOnOutsideClick(event)">
    <form class="modal-content" id="decisionForm" method="POST" action="">
        @csrf
        <div class="modal-header">
            <h3>تفاصيل الطلب (<span id="modal-request-type"></span>)</h3>
            <button class="btn-close-modal" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="modal-grid-2">
                <div class="detail-row">
                    <label>اسم الطالب:</label>
                    <div class="detail-value" id="modal-student-name"></div>
                </div>
                <div class="detail-row">
                    <label>الرقم الجامعي:</label>
                    <div class="detail-value" id="modal-student-id"></div>
                </div>
            </div>
            
            <div class="modal-grid-2">
                <div class="detail-row">
                    <label>القسم:</label>
                    <div class="detail-value" id="modal-student-department"></div>
                </div>
                <div class="detail-row">
                    <label>التخصص:</label>
                    <div class="detail-value" id="modal-student-specialization"></div>
                </div>
            </div>
            
            <div class="detail-row">
                <label>العام الدراسي:</label>
                <div class="detail-value" id="modal-student-year" style="color: #f2f20d;"></div>
            </div>

            <div class="detail-row">
                <label>تفاصيل الطلب:</label>
                <div class="detail-value" id="modal-request-details"></div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                <!-- ملاحظات الشؤون (Read-only) -->
                <div class="detail-row" style="background: #fef08a; padding: 1rem; border-radius: 8px; border-right: 4px solid #ca8a04;">
                    <label style="color: #854d0e; font-size: 1rem;"><i class="fa-solid fa-clipboard-check"></i> رأي الشؤون:</label>
                    <div class="detail-value" id="modal-affairs-notes" style="color: #422006; font-size: 0.95rem; margin-top: 0.5rem; line-height: 1.6;"></div>
                </div>

                <!-- ملاحظات رئيس القسم (Read-only) -->
                <div class="detail-row" style="background: #e0e7ff; padding: 1rem; border-radius: 8px; border-right: 4px solid #4f46e5;">
                    <label style="color: #3730a3; font-size: 1rem;"><i class="fa-solid fa-user-tie"></i> رأي رئيس القسم:</label>
                    <div class="detail-value" id="modal-affairs-notes-readonly-hod-not-used" style="color: #1e1b4b; font-size: 0.95rem; margin-top: 0.5rem; line-height: 1.6;"></div>
                </div>
            </div>
            
            <!-- ملاحظات الإدارة (Mandatory) -->
            <div class="detail-row" style="margin-top: 1.5rem;">
                <label><i class="fa-solid fa-pen-nib"></i> قرار وملاحظات الإدارة <span style="color: #ef4444;">(مطلوب إجبارياً)</span>:</label>
                <textarea class="notes-area" name="notes" id="modal-hod-notes" placeholder="اكتب قرار الإدارة النهائي أو أسباب الرفض/القبول ليتم اعتماده رسمياً وإشعار الطالب به..."></textarea>
                <input type="hidden" name="decision" id="modal-decision" value="approved">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal-approve" onclick="submitDecision('approve')"><i class="fa-solid fa-check-double"></i> اعتماد نهائي (موافقة)</button>
            <button type="button" class="btn-modal-reject" onclick="submitDecision('reject')"><i class="fa-solid fa-xmark"></i> رفض الطلب</button>
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

    function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, reqId, canRespond) {
        document.getElementById('modal-request-type').innerText = type;
        document.getElementById('modal-student-name').innerText = name;
        document.getElementById('modal-student-id').innerText = id;
        document.getElementById('modal-student-department').innerText = department;
        document.getElementById('modal-student-specialization').innerText = specialization;
        document.getElementById('modal-student-year').innerText = year;
        document.getElementById('modal-request-details').innerText = details;
        document.getElementById('modal-affairs-notes').innerText = affairsNotes;
        
        document.getElementById('decisionForm').action = '/hod/student-services/' + reqId + '/process';
        
        const notesElement = document.getElementById('modal-hod-notes');
        notesElement.style.borderColor = '';
        
        const footer = document.querySelector('.modal-footer');
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
