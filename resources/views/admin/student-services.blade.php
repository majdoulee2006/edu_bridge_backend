@extends('layouts.admin')
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

    <!-- Tabs Navigation -->
    <div class="custom-tabs">
        <button class="tab-btn active" onclick="switchTab('mercy')">
            <i class="fa-solid fa-gavel"></i> طلبات الاسترحام
        </button>
        <button class="tab-btn" onclick="switchTab('documents')">
            <i class="fa-solid fa-file-invoice"></i> طلبات الوثائق
        </button>
        <button class="tab-btn" onclick="switchTab('makeup')">
            <i class="fa-solid fa-pen-to-square"></i> امتحانات الإكمال
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:#f2f20d; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">أ</div>
                                <span>أحمد محمد</span>
                            </div>
                        </td>
                        <td>202401</td>
                        <td>2024-2025</td>
                        <td>إعادة تقييم مقرر البرمجة</td>
                        <td>2026-07-14</td>
                        <td><span class="badge badge-admin-review">بانتظار الإدارة</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('استرحام', 'أحمد محمد', '202401', '2024-2025', 'الهندسة المعلوماتية', 'هندسة البرمجيات', 'إعادة تقييم مقرر البرمجة', 'الطالب يستحق إعادة التقييم نظراً لظروفه الصحية.', 'أوافق على إعادة التقييم، يرجى من الإدارة الاعتماد.')"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </td>
                    </tr>
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:#f2f20d; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">س</div>
                                <span>سارة خالد</span>
                            </div>
                        </td>
                        <td>202405</td>
                        <td>2024-2025</td>
                        <td>كشف علامات مفصل</td>
                        <td>2026-07-13</td>
                        <td><span class="badge badge-admin-review">بانتظار الإدارة</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('وثيقة', 'سارة خالد', '202405', '2024-2025', 'إدارة الأعمال', 'محاسبة', 'كشف علامات مفصل', 'تم التأكد من براءة ذمة الطالبة المالية.', 'لا مانع أكاديمياً من منحها كشف العلامات.')"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </td>
                    </tr>
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:#f2f20d; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">م</div>
                                <span>محمد العبدالله</span>
                            </div>
                        </td>
                        <td>202410</td>
                        <td>2024-2025</td>
                        <td>هندسة البرمجيات، قواعد البيانات</td>
                        <td>2026-07-14</td>
                        <td><span class="badge badge-admin-review">بانتظار الإدارة</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('امتحان إكمال', 'محمد العبدالله', '202410', '2024-2025', 'الهندسة المعلوماتية', 'الذكاء الاصطناعي', 'هندسة البرمجيات، قواعد البيانات', 'الطالب مسجل بشكل نظامي، نسبة الغياب ضمن المسموح.', 'يُسمح له بدخول الامتحان لمادة هندسة البرمجيات، أما قواعد البيانات فتحتاج استثناء إداري.')"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

<!-- Request Details Modal -->
<div id="requestModal" class="modal-overlay" onclick="closeModalOnOutsideClick(event)">
    <div class="modal-content">
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
                    <div class="detail-value" id="modal-hod-notes-readonly" style="color: #1e1b4b; font-size: 0.95rem; margin-top: 0.5rem; line-height: 1.6;"></div>
                </div>
            </div>
            
            <!-- ملاحظات الإدارة (Mandatory) -->
            <div class="detail-row" style="margin-top: 1.5rem;">
                <label><i class="fa-solid fa-pen-nib"></i> قرار وملاحظات الإدارة <span style="color: #ef4444;">(مطلوب إجبارياً)</span>:</label>
                <textarea class="notes-area" id="modal-admin-notes" placeholder="اكتب قرار الإدارة النهائي أو أسباب الرفض/القبول ليتم اعتماده رسمياً وإشعار الطالب به..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-approve" onclick="submitDecision('approve')"><i class="fa-solid fa-check-double"></i> اعتماد نهائي (موافقة)</button>
            <button class="btn-modal-reject" onclick="submitDecision('reject')"><i class="fa-solid fa-xmark"></i> رفض الطلب</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    }

    function openRequestModal(type, name, id, year, department, specialization, details, affairsNotes, hodNotes) {
        document.getElementById('modal-request-type').innerText = type;
        document.getElementById('modal-student-name').innerText = name;
        document.getElementById('modal-student-id').innerText = id;
        document.getElementById('modal-student-department').innerText = department;
        document.getElementById('modal-student-specialization').innerText = specialization;
        document.getElementById('modal-student-year').innerText = year;
        document.getElementById('modal-request-details').innerText = details;
        document.getElementById('modal-affairs-notes').innerText = affairsNotes;
        document.getElementById('modal-hod-notes-readonly').innerText = hodNotes;
        
        const notesElement = document.getElementById('modal-admin-notes');
        notesElement.value = ''; // clear previous notes
        notesElement.style.borderColor = ''; // reset border color
        
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
        const notesElement = document.getElementById('modal-admin-notes');
        const notes = notesElement.value.trim();
        const studentName = document.getElementById('modal-student-name').innerText;
        
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
        
        const decisionText = decision === 'approve' ? 'الموافقة النهائية على' : 'رفض';
        alert(`تم ${decisionText} طلب الطالب ${studentName} بنجاح!\nقرار الإدارة: ${notes}`);
        closeModal();
    }
</script>
@endpush
