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

    /* Tables */
    .table-container {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        overflow-x: auto;
    }
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    .custom-table th {
        text-align: right;
        padding: 1.2rem 1rem;
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
    </div>

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
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">أ</div>
                                <span>أحمد محمد</span>
                            </div>
                        </td>
                        <td>202401</td>
                        <td>2024-2025</td>
                        <td>إعادة تقييم مقرر البرمجة</td>
                        <td>2026-07-14</td>
                        <td><span class="badge badge-pending">قيد المراجعة</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('استرحام', 'أحمد محمد', '202401', '2024-2025', 'الهندسة المعلوماتية', 'هندسة البرمجيات', 'إعادة تقييم مقرر البرمجة')"><i class="fa-solid fa-eye"></i></button>
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
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">س</div>
                                <span>سارة خالد</span>
                            </div>
                        </td>
                        <td>202405</td>
                        <td>2024-2025</td>
                        <td>كشف علامات مفصل</td>
                        <td>2026-07-13</td>
                        <td><span class="badge badge-pending">قيد المراجعة</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('وثيقة', 'سارة خالد', '202405', '2024-2025', 'إدارة الأعمال', 'محاسبة', 'كشف علامات مفصل')"><i class="fa-solid fa-eye"></i></button>
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
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">م</div>
                                <span>محمد العبدالله</span>
                            </div>
                        </td>
                        <td>202410</td>
                        <td>2024-2025</td>
                        <td>هندسة البرمجيات، قواعد البيانات</td>
                        <td>2026-07-14</td>
                        <td><span class="badge badge-pending">قيد المراجعة</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-action btn-view" title="عرض التفاصيل" onclick="openRequestModal('امتحان إكمال', 'محمد العبدالله', '202410', '2024-2025', 'الهندسة المعلوماتية', 'الذكاء الاصطناعي', 'هندسة البرمجيات، قواعد البيانات')"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
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
                <div class="detail-value" id="modal-student-year" style="color: var(--accent-color);"></div>
            </div>

            <div class="detail-row">
                <label>تفاصيل الطلب:</label>
                <div class="detail-value" id="modal-request-details"></div>
            </div>
            
            <div class="detail-row" style="margin-top: 1.5rem;">
                <label><i class="fa-solid fa-pen"></i> ملاحظات موظف الشؤون <span style="color: #ef4444;">(مطلوب إجبارياً)</span>:</label>
                <textarea class="notes-area" id="modal-notes" placeholder="اكتب ملاحظاتك أو أسباب الرفض أو القبول التي ستظهر للطالب هنا..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-approve" onclick="submitDecision('approve')"><i class="fa-solid fa-check"></i> قبول الطلب</button>
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

    function openRequestModal(type, name, id, year, department, specialization, details) {
        document.getElementById('modal-request-type').innerText = type;
        document.getElementById('modal-student-name').innerText = name;
        document.getElementById('modal-student-id').innerText = id;
        document.getElementById('modal-student-department').innerText = department;
        document.getElementById('modal-student-specialization').innerText = specialization;
        document.getElementById('modal-student-year').innerText = year;
        document.getElementById('modal-request-details').innerText = details;
        
        const notesElement = document.getElementById('modal-notes');
        notesElement.value = ''; // clear previous notes
        notesElement.style.borderColor = 'var(--border-color)'; // reset border color
        
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
        const notesElement = document.getElementById('modal-notes');
        const notes = notesElement.value.trim();
        const studentName = document.getElementById('modal-student-name').innerText;
        
        // التحقق الإجباري من وجود الملاحظات
        if (notes === '') {
            notesElement.style.borderColor = '#ef4444'; // تلوين الحواف بالأحمر
            notesElement.focus();
            
            // إضافة اهتزاز خفيف للمربع للفت الانتباه (اختياري)
            notesElement.style.transform = 'translateX(5px)';
            setTimeout(() => notesElement.style.transform = 'translateX(-5px)', 100);
            setTimeout(() => notesElement.style.transform = 'translateX(5px)', 200);
            setTimeout(() => notesElement.style.transform = 'translateX(0)', 300);
            
            alert('❌ عذراً، يجب كتابة الملاحظات قبل اتخاذ قرار الرفض أو القبول!');
            return;
        }
        
        // إذا كان هناك ملاحظات يتم تنفيذ الطلب
        const decisionText = decision === 'approve' ? 'الموافقة على' : 'رفض';
        alert(`تم ${decisionText} طلب الطالب ${studentName} بنجاح!\nالملاحظات المرسلة: ${notes}`);
        closeModal();
    }
</script>
@endpush
