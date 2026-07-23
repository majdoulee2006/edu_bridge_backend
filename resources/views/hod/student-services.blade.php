@extends('layouts.hod')
@section('title', 'الخدمات الطلابية - رئيس القسم')

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
    .dept-badge {
        background: var(--accent-color);
        color: #1a1a1a;
        padding: 0.5rem 1.2rem;
        border-radius: 2rem;
        font-weight: 800;
        font-size: 0.95rem;
    }

    /* Tabs Styling */
    .custom-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 0.5rem;
        overflow-x: auto;
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

    /* Branch Cards */
    .branch-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
    }
    .branch-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.2rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 0.8rem;
    }
    .branch-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    /* Tables */
    .table-container {
        overflow-x: auto;
    }
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    .custom-table th {
        text-align: right;
        padding: 1rem;
        color: var(--text-secondary);
        font-weight: 800;
        border-bottom: 2px solid var(--border-color);
        white-space: nowrap;
    }
    .custom-table td {
        padding: 1rem;
        color: var(--text-primary);
        font-weight: 600;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    .custom-table tr:hover td {
        background: rgba(0, 0, 0, 0.02);
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
    .badge-hod-review { background: #bfdbfe; color: #1e3a8a; }

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 0.5rem;
    }
    .btn-action {
        padding: 0.4rem 0.9rem;
        border-radius: 0.5rem;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 700;
        font-family: inherit;
        transition: transform 0.2s;
        color: white;
    }
    .btn-action:hover {
        transform: scale(1.05);
    }
    .btn-view-active { background: #2563eb; }
    .btn-view-readonly { background: #6b7280; }

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
        background: var(--bg-primary);
        border-radius: 1.25rem;
        width: 90%;
        max-width: 550px;
        padding: 2rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        transform: scale(0.9);
        transition: transform 0.3s ease;
        position: relative;
    }
    .modal-overlay.active .modal-content {
        transform: scale(1);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }
    .modal-header h3 {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0;
    }
    .btn-close-modal {
        background: transparent;
        border: none;
        font-size: 1.5rem;
        color: var(--text-secondary);
        cursor: pointer;
    }
    .modal-body .detail-row {
        margin-bottom: 1rem;
    }
    .detail-row label {
        display: block;
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 700;
        margin-bottom: 0.3rem;
    }
    .detail-row .detail-value {
        font-size: 1.05rem;
        color: var(--text-primary);
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
        border: 2px solid var(--border-color);
        border-radius: 10px;
        padding: 0.8rem;
        background: var(--bg-secondary);
        color: var(--text-primary);
        font-family: 'Cairo', sans-serif;
        resize: vertical;
        margin-top: 0.5rem;
        transition: border-color 0.3s;
    }
    .notes-area:focus {
        outline: none;
        border-color: var(--accent-color);
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
    .modal-footer button:hover {
        opacity: 0.9;
    }
    .btn-modal-approve {
        background: #10b981;
        color: white;
    }
    .btn-modal-reject {
        background: #ef4444;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="services-container">
    <div class="page-header">
        <h2>الخدمات والطلبات الطلابية</h2>
        <span class="dept-badge"><i class="fa-solid fa-building-columns"></i> قسم: {{ auth()->user()->department ?? 'قسم عام' }}</span>
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
    </div>

    @php
        $types = [
            'mercy' => 'طلبات الاسترحام',
            'document' => 'طلبات الوثائق',
            'makeup' => 'امتحانات الإكمال'
        ];
    @endphp

    @foreach(['mercy', 'document', 'makeup'] as $typeKey)
    <div id="tab-{{ $typeKey }}" class="tab-content {{ $loop->first ? 'active' : '' }}">
        @php $foundAny = false; @endphp
        
        @foreach($requestsByBranch as $branchName => $branchRequests)
            @php $filtered = $branchRequests->where('type', $typeKey); @endphp
            @if($filtered->isNotEmpty())
                @php $foundAny = true; @endphp
                <div class="branch-card">
                    <div class="branch-header">
                        <div class="branch-title">
                            <i class="fa-solid fa-code-branch" style="color: var(--accent-color);"></i>
                            تخصص / فرع: {{ $branchName }}
                        </div>
                        <span class="badge badge-hod-review">{{ $filtered->count() }} طلب(ات)</span>
                    </div>

                    <div class="table-container">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th>الرقم الجامعي</th>
                                    <th>العام الدراسي</th>
                                    <th>مضمون الطلب</th>
                                    <th>تاريخ الطلب</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
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
                                            <span class="badge badge-pending">بانتظار قرارك</span>
                                        @else
                                            <span class="badge badge-approved">تم إبداء رأيك وتحويله للإدارة</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $canRespond = ($req->status == 'pending_hod');
                                        @endphp
                                        <div class="action-btns">
                                            @if($canRespond)
                                                <button class="btn-action btn-view-active" title="إدخال القرار" onclick="openRequestModal('{{ $types[$typeKey] }}', '{{ $req->student->user->full_name ?? '' }}', '{{ $req->student->student_code ?? '' }}', '{{ $req->student->user->academic_year ?? '' }}', '{{ $req->student->program->department->name ?? ($req->student->user->department ?? 'غير محدد') }}', '{{ $req->student->program->name ?? ($req->student->user->branch ?? 'غير محدد') }}', `{{ addslashes($req->details) }}`, `{{ addslashes($req->affairs_notes ?? 'لا توجد ملاحظات من الشؤون') }}`, `{{ addslashes($req->hod_notes ?? '') }}`, {{ $req->id }}, true)"><i class="fa-solid fa-pen-to-square"></i> إبداء القرار</button>
                                            @else
                                                <button class="btn-action btn-view-readonly" title="معاينة القرار (قراءة فقط)" onclick="openRequestModal('{{ $types[$typeKey] }}', '{{ $req->student->user->full_name ?? '' }}', '{{ $req->student->student_code ?? '' }}', '{{ $req->student->user->academic_year ?? '' }}', '{{ $req->student->program->department->name ?? ($req->student->user->department ?? 'غير محدد') }}', '{{ $req->student->program->name ?? ($req->student->user->branch ?? 'غير محدد') }}', `{{ addslashes($req->details) }}`, `{{ addslashes($req->affairs_notes ?? 'لا توجد ملاحظات من الشؤون') }}`, `{{ addslashes($req->hod_notes ?? 'تم اتخاذ القرار مسبقاً') }}`, {{ $req->id }}, false)"><i class="fa-solid fa-eye"></i> معاينة (قراءة فقط)</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach

        @if(!$foundAny)
            <div class="branch-card" style="text-align: center; color: var(--text-secondary); padding: 3rem;">
                <i class="fa-solid fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>لا توجد طلبات مسجلة ضمن هذا التبويب لقسمك حالياً.</p>
            </div>
        @endif
    </div>
    @endforeach

</div>

<!-- Request Details Modal -->
<div id="requestModal" class="modal-overlay" onclick="closeModalOnOutsideClick(event)">
    <form class="modal-content" id="decisionForm" method="POST" action="">
        @csrf
        <div class="modal-header">
            <h3>تفاصيل الطلب (<span id="modal-request-type"></span>)</h3>
            <button type="button" class="btn-close-modal" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
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
                    <label>التخصص / الفرع:</label>
                    <div class="detail-value" id="modal-student-specialization"></div>
                </div>
            </div>
            
            <div class="detail-row">
                <label>العام الدراسي:</label>
                <div class="detail-value" id="modal-student-year" style="color: var(--accent-color);"></div>
            </div>

            <div class="detail-row">
                <label>مضمون/تفاصيل الطلب:</label>
                <div class="detail-value" id="modal-request-details" style="background: var(--bg-secondary); padding: 0.8rem; border-radius: 8px; font-size: 0.95rem; line-height: 1.6;"></div>
            </div>
            
            <!-- ملاحظات الشؤون (Read-only) -->
            <div class="detail-row" style="margin-top: 1.2rem; background: #fef08a; padding: 1rem; border-radius: 8px; border-right: 4px solid #ca8a04;">
                <label style="color: #854d0e; font-size: 0.95rem;"><i class="fa-solid fa-clipboard-check"></i> رأي وملاحظات موظف الشؤون:</label>
                <div class="detail-value" id="modal-affairs-notes" style="color: #422006; font-size: 0.95rem; margin-top: 0.4rem; line-height: 1.5;"></div>
            </div>
            
            <!-- ملاحظات رئيس القسم -->
            <div class="detail-row" style="margin-top: 1.2rem;">
                <label><i class="fa-solid fa-pen"></i> ملاحظات وقرار رئيس القسم:</label>
                <textarea class="notes-area" name="notes" id="modal-hod-notes" placeholder="اكتب قرارك النهائي وأسباب القبول أو الرفض ليتم تحويله للإدارة..."></textarea>
                <input type="hidden" name="decision" id="modal-decision" value="approved">
            </div>

            <!-- تنبيه القفل لقراءة فقط -->
            <div id="modal-readonly-badge" style="display: none; background: #e0e7ff; color: #3730a3; padding: 0.8rem; border-radius: 8px; font-weight: 700; text-align: center; margin-top: 1rem;">
                <i class="fa-solid fa-lock"></i> تم إرسال رأيك للإدارة مسبقاً وتَم قفل التعديل (رد واحد فقط مسموح به).
            </div>
        </div>
        <div class="modal-footer" id="modal-footer-actions">
            <button type="button" class="btn-modal-approve" onclick="submitDecision('approve')"><i class="fa-solid fa-check"></i> اعتماد وتحويل للإدارة</button>
            <button type="button" class="btn-modal-reject" onclick="submitDecision('reject')"><i class="fa-solid fa-xmark"></i> رفض الطلب</button>
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

    function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, hodNotes, reqId, canRespond) {
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
        const footerActions = document.getElementById('modal-footer-actions');
        const readonlyBadge = document.getElementById('modal-readonly-badge');
        
        notesElement.value = hodNotes || '';
        notesElement.style.borderColor = 'var(--border-color)';
        
        if (canRespond) {
            notesElement.readOnly = false;
            notesElement.style.background = 'var(--bg-secondary)';
            footerActions.style.display = 'flex';
            readonlyBadge.style.display = 'none';
        } else {
            notesElement.readOnly = true;
            notesElement.style.background = '#f3f4f6';
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
        const notesElement = document.getElementById('modal-hod-notes');
        const notes = notesElement.value.trim();
        
        if (notes === '') {
            notesElement.style.borderColor = '#ef4444';
            notesElement.focus();
            
            notesElement.style.transform = 'translateX(5px)';
            setTimeout(() => notesElement.style.transform = 'translateX(-5px)', 100);
            setTimeout(() => notesElement.style.transform = 'translateX(5px)', 200);
            setTimeout(() => notesElement.style.transform = 'translateX(0)', 300);
            
            alert('❌ عذراً، يجب كتابة الملاحظات والقرار النهائي قبل اعتماد الطلب أو رفضه!');
            return;
        }
        
        document.getElementById('modal-decision').value = decision === 'approve' ? 'approved' : 'rejected';
        document.getElementById('decisionForm').submit();
    }
</script>
@endpush
