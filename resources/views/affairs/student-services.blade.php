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
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
        color: white;
        font-size: 0.85rem;
        font-weight: 700;
        white-space: nowrap;
        font-family: 'Cairo', sans-serif;
    }
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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
        <button class="tab-btn" onclick="switchTab(this, 'academic-card')">
            <i class="fa-solid fa-graduation-cap"></i> كشف علامات الطلاب
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
                    @foreach($requests->where('type', 'mercy') as $req)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span>{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td>{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td>{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td>{{ Str::limit($req->formatted_details, 40) }}</td>
                        <td>{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td>
                            @if($req->status == 'pending_affairs')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @else
                                <span class="badge badge-approved">تم تحويله لرئيس القسم</span>
                            @endif</td>
                        <td>
                            @php $canRespond = ($req->status == 'pending_affairs'); @endphp
                            <div class="action-btns">
                                <button class="btn-action {{ $canRespond ? 'btn-view' : '' }}" style="{{ $canRespond ? '' : 'background:#6b7280;' }}"
                                    title="{{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}"
                                    data-type="استرحام"
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
                                    <i class="fa-solid {{ $canRespond ? 'fa-pen-to-square' : 'fa-eye' }}"></i> {{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests->where('type', 'document') as $req)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span>{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td>{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td>{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td>{{ Str::limit($req->formatted_details, 40) }}</td>
                        <td>{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td>
                            @if($req->status == 'pending_affairs')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @else
                                <span class="badge badge-approved">تم تحويله لرئيس القسم</span>
                            @endif</td>
                        <td>
                            @php $canRespond = ($req->status == 'pending_affairs'); @endphp
                            <div class="action-btns">
                                <button class="btn-action {{ $canRespond ? 'btn-view' : '' }}" style="{{ $canRespond ? '' : 'background:#6b7280;' }}"
                                    title="{{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}"
                                    data-type="وثيقة"
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
                                    <i class="fa-solid {{ $canRespond ? 'fa-pen-to-square' : 'fa-eye' }}"></i> {{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}
                                </button>
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests->where('type', 'makeup') as $req)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span>{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td>{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td>{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td>{{ Str::limit($req->formatted_details, 40) }}</td>
                        <td>{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td>
                            @if($req->status == 'pending_affairs')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @else
                                <span class="badge badge-approved">تم تحويله لرئيس القسم</span>
                            @endif</td>
                        <td>
                            @php $canRespond = ($req->status == 'pending_affairs'); @endphp
                            <div class="action-btns">
                                <button class="btn-action {{ $canRespond ? 'btn-view' : '' }}" style="{{ $canRespond ? '' : 'background:#6b7280;' }}"
                                    title="{{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}"
                                    data-type="إكمال"
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
                                    <i class="fa-solid {{ $canRespond ? 'fa-pen-to-square' : 'fa-eye' }}"></i> {{ $canRespond ? 'إبداء رأي الشؤون' : 'معاينة (قراءة فقط)' }}
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

    <!-- Device Reset Tab -->
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
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests->where('type', 'device_reset') as $req)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.8rem;">
                                <div style="width:35px; height:35px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#1a1a1a; font-weight:bold;">{{ mb_substr($req->student?->user?->full_name ?? 'ط', 0, 1) }}</div>
                                <span>{{ $req->student?->user?->full_name ?? 'غير معروف' }}</span>
                            </div>
                        </td>
                        <td>{{ $req->student?->student_code ?? 'N/A' }}</td>
                        <td>{{ $req->student?->user?->academic_year ?? 'N/A' }}</td>
                        <td>{{ Str::limit($req->formatted_details, 40) }}</td>
                        <td>{{ $req->created_at?->format('Y-m-d') }}</td>
                        <td>
                            @if($req->status == 'pending_affairs' || $req->status == 'pending')
                                <span class="badge badge-pending">بانتظار قرارك</span>
                            @elseif($req->status == 'approved')
                                <span class="badge badge-approved">تمت الموافقة وفك القفل</span>
                            @else
                                <span class="badge badge-rejected">مرفوض</span>
                            @endif
                        </td>
                        <td>
                            @php $canRespond = ($req->status == 'pending_affairs' || $req->status == 'pending'); @endphp
                            <div class="action-btns">
                                <button class="btn-action {{ $canRespond ? 'btn-view' : '' }}" style="{{ $canRespond ? '' : 'background:#6b7280;' }}"
                                    title="{{ $canRespond ? 'معالجة فك القفل' : 'معاينة (قراءة فقط)' }}"
                                    data-type="فك قفل الجهاز"
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
                                    <i class="fa-solid {{ $canRespond ? 'fa-pen-to-square' : 'fa-eye' }}"></i> {{ $canRespond ? 'معالجة طلب فك القفل' : 'معاينة (قراءة فقط)' }}
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

    <!-- 4. Academic Student Card Tab -->
    <div id="tab-academic-card" class="tab-content">
        <div style="background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
            <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-filter" style="color: var(--accent-color);"></i> تصفية واستعلام كشف العلامات للأداء الأكاديمي
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; align-items: end;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem; color: var(--text-secondary);">1. القسم:</label>
                    <select id="affairs-filter-department" onchange="loadFilteredStudents()" style="width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                        <option value="">جميع الأقسام</option>
                        @foreach(DB::table('departments')->get() as $dept)
                            <option value="{{ $dept->department_id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem; color: var(--text-secondary);">2. الدورة / الفصل الدراسي:</label>
                    <select id="affairs-filter-semester" onchange="loadFilteredStudents()" style="width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                        <option value="الكل">جميع الدورات / الفصول</option>
                        @foreach(DB::table('semesters')->get() as $sem)
                            <option value="{{ $sem->semester_id }}">{{ $sem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem; color: var(--text-secondary);">3. السنة الدراسية:</label>
                    <select id="affairs-filter-year" onchange="loadFilteredStudents()" style="width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                        <option value="الكل">جميع السنوات (السنة الأولى والسنة الثانية)</option>
                        <option value="السنة الأولى">السنة الأولى</option>
                        <option value="السنة الثانية">السنة الثانية</option>
                    </select>
                </div>
                <div style="grid-column: span 3;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.4rem; color: var(--text-secondary);">اختر الطالب (مرتب أبجدياً):</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="affairs-student-search" placeholder="بحث باسم الطالب أو الرقم الجامعي..." onkeyup="filterStudentDropdownList()" style="flex: 1; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 600;">
                        <select id="affairs-student-select" onchange="fetchAcademicCardForSelectedStudent()" style="flex: 2; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-weight: 700;">
                            <option value="">-- اختر طالباً من القائمة --</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div id="academic-card-display" style="display: none;">
            <div style="background: linear-gradient(135deg, #181818, #0a0a0a); border-radius: 1.25rem; padding: 1.5rem 2rem; color: white; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow); flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 id="card-student-name" style="font-size: 1.4rem; font-weight: 800; color: #facc15; margin: 0 0 0.5rem 0;">--</h3>
                    <p style="margin: 0; font-size: 0.95rem; opacity: 0.85;">
                        الرقم الجامعي: <span id="card-student-code" style="font-weight: 800; color: white;">--</span> &nbsp;|&nbsp;
                        التخصص: <span id="card-student-dept" style="font-weight: 800; color: white;">--</span> &nbsp;|&nbsp;
                        السنة: <span id="card-student-level" style="font-weight: 800; color: white;">--</span>
                    </p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="exportCard('pdf')" style="background: #ef4444; color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 10px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-file-pdf"></i> تصدير PDF
                    </button>
                    <button type="button" onclick="exportCard('excel')" style="background: #10b981; color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 10px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-file-excel"></i> تصدير Excel
                    </button>
                </div>
            </div>

            <!-- Summary Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: var(--bg-secondary); border-radius: 1rem; padding: 1.2rem; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 700;">المعدل التراكمي</div>
                    <div id="card-stat-gpa" style="font-size: 1.8rem; font-weight: 900; color: var(--accent-color); margin-top: 0.25rem;">0%</div>
                </div>
                <div style="background: var(--bg-secondary); border-radius: 1rem; padding: 1.2rem; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 700;">المواد المجتازة</div>
                    <div id="card-stat-passed" style="font-size: 1.8rem; font-weight: 900; color: #10b981; margin-top: 0.25rem;">0</div>
                </div>
                <div style="background: var(--bg-secondary); border-radius: 1rem; padding: 1.2rem; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 700;">المواد المتبقية / راسب</div>
                    <div id="card-stat-failed" style="font-size: 1.8rem; font-weight: 900; color: #ef4444; margin-top: 0.25rem;">0</div>
                </div>
                <div style="background: var(--bg-secondary); border-radius: 1rem; padding: 1.2rem; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 700;">لم يتم التقدم لها</div>
                    <div id="card-stat-notattended" style="font-size: 1.8rem; font-weight: 900; color: #6b7280; margin-top: 0.25rem;">0</div>
                </div>
            </div>

            <!-- Academic Card Table -->
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المادة الدراسية</th>
                            <th>السنة/الفصل</th>
                            <th style="text-align: center;">علامة المذاكرة (25)</th>
                            <th style="text-align: center;">علامة الشفهي/العملي (25)</th>
                            <th style="text-align: center;">علامة الامتحان (50)</th>
                            <th style="text-align: center;">المجموع الكلي (100)</th>
                            <th style="text-align: center;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody id="academic-card-table-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                <div class="detail-value" id="modal-request-details" style="word-break: break-all; overflow-wrap: anywhere; white-space: pre-wrap; background: var(--bg-secondary); padding: 0.75rem 1rem; border-radius: 0.6rem; border: 1px solid var(--border-color); font-size: 0.88rem; max-width: 100%;"></div>
            </div>
            
            <div class="detail-row">
                <label>رأي وملاحظات الشؤون:</label>
                <textarea class="notes-area" name="notes" id="modal-notes" placeholder="اكتب ملاحظات الشؤون هنا..."></textarea>
                <input type="hidden" name="decision" id="decisionInput" value="approved">
            </div>
        </div>
        <div class="modal-footer">
            <div id="modal-readonly-badge" style="display: none; color: #6b7280; font-weight: bold; align-self: center;">
                <i class="fa-solid fa-lock"></i> تم تحويل الطلب ورأيك مسجل مسبقاً
            </div>
            <div id="modal-footer-actions" style="display: flex; gap: 1rem; width: 100%;">
                <button type="button" class="btn-modal-approve" onclick="submitDecision('approved')"><i class="fa-solid fa-check"></i> موافقة وتحويل لرئيس القسم</button>
                <button type="button" class="btn-modal-reject" onclick="submitDecision('rejected')"><i class="fa-solid fa-xmark"></i> عدم موافقة وتحويل لرئيس القسم</button>
            </div>
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
        const name = btn.getAttribute('data-name') || '';
        const id = btn.getAttribute('data-id') || '';
        const year = btn.getAttribute('data-year') || '';
        const department = btn.getAttribute('data-department') || '';
        const specialization = btn.getAttribute('data-specialization') || '';
        const details = btn.getAttribute('data-details') || '';
        const affairsNotes = btn.getAttribute('data-affairs-notes') || '';
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
                if (parsed.reason) parts.push('السبب: ' + parsed.reason);
                if (parsed.new_device_id) parts.push('رمز تعريف الجهاز الجديد (Device ID): ' + parsed.new_device_id);
                if (parts.length > 0) displayDetails = parts.join('\n');
            } catch(e) {}
        }
        document.getElementById('modal-request-details').innerText = displayDetails;
        document.getElementById('decisionForm').action = '/affairs/student-services/' + reqId + '/process';
        
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

    // ─────────────────────────── Academic Card JS ───────────────────────────
    let allLoadedStudents = [];
    let currentSelectedStudentId = null;

    document.addEventListener("DOMContentLoaded", function() {
        if (document.getElementById('tab-academic-card')) {
            loadFilteredStudents();
        }
    });

    function loadFilteredStudents() {
        const departmentId = document.getElementById('affairs-filter-department')?.value || '';
        const semesterId = document.getElementById('affairs-filter-semester')?.value || '';
        const level = document.getElementById('affairs-filter-year')?.value || '';
        const search = document.getElementById('affairs-student-search')?.value || '';

        fetch(`/affairs/academic-card/students?department_id=${departmentId}&semester_id=${semesterId}&level=${encodeURIComponent(level)}&search=${encodeURIComponent(search)}`)
            .then(res => res.json())
            .then(data => {
                const studentsList = data.data || data.students || [];
                if (data.success && studentsList) {
                    allLoadedStudents = studentsList;
                    renderStudentDropdownOptions(allLoadedStudents);
                }
            })
            .catch(err => console.error(err));
    }

    function filterStudentDropdownList() {
        const search = document.getElementById('affairs-student-search').value.toLowerCase();
        const filtered = allLoadedStudents.filter(st => 
            (st.full_name && st.full_name.toLowerCase().includes(search)) ||
            (st.university_id && st.university_id.toLowerCase().includes(search)) ||
            (st.student_code && st.student_code.toLowerCase().includes(search))
        );
        renderStudentDropdownOptions(filtered);
    }

    function renderStudentDropdownOptions(students) {
        const select = document.getElementById('affairs-student-select');
        if (!select) return;
        select.innerHTML = '<option value="">-- اختر طالباً من القائمة (إجمالي ' + students.length + ') --</option>';
        students.forEach(st => {
            const code = st.university_id || st.student_code || '';
            const yearStr = st.level || '';
            select.innerHTML += `<option value="${st.student_id}">${st.full_name} (${code}) - ${yearStr}</option>`;
        });
    }

    function fetchAcademicCardForSelectedStudent() {
        const select = document.getElementById('affairs-student-select');
        const studentId = select.value;
        if (!studentId) {
            document.getElementById('academic-card-display').style.display = 'none';
            return;
        }
        currentSelectedStudentId = studentId;

        const level = document.getElementById('affairs-filter-year')?.value || '';

        fetch(`/affairs/academic-card/data?student_id=${studentId}&level=${encodeURIComponent(level)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderAcademicCard(data);
                } else {
                    alert(data.message || 'تعذر جلب بيانات كشف العلامات');
                }
            })
            .catch(err => alert('حدث خطأ أثناء تحميل الكشف: ' + err));
    }

    function renderAcademicCard(data) {
        const st = data.student;
        const summary = data.summary;
        const card = data.academic_card;

        document.getElementById('card-student-name').innerText = st.full_name || '';
        document.getElementById('card-student-code').innerText = st.university_id || st.student_code || '';
        document.getElementById('card-student-dept').innerText = st.department || st.branch || '';
        document.getElementById('card-student-level').innerText = st.level || '';

        document.getElementById('card-stat-gpa').innerText = summary.average + '%';
        document.getElementById('card-stat-passed').innerText = summary.passed_courses;
        document.getElementById('card-stat-failed').innerText = summary.failed_courses;
        document.getElementById('card-stat-notattended').innerText = summary.not_attended;

        const tbody = document.getElementById('academic-card-table-body');
        tbody.innerHTML = '';

        if (!card || card.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:2rem; color:var(--text-secondary);">لا توجد مواد مسجلة لهذا الطالب</td></tr>';
        } else {
            card.forEach((row, idx) => {
                let statusBadge = '<span class="badge badge-pending">لم يتم التقدم</span>';
                if (row.status === 'ناجح') {
                    statusBadge = '<span class="badge badge-approved">ناجح</span>';
                } else if (row.status === 'راسب') {
                    statusBadge = '<span class="badge badge-rejected">راسب</span>';
                }

                tbody.innerHTML += `
                    <tr>
                        <td>${idx + 1}</td>
                        <td style="font-weight:700;">${row.title}</td>
                        <td style="color:var(--text-secondary); font-size:0.85rem;">سنة ${row.year || 1} - فصل ${row.semester || 1}</td>
                        <td style="text-align:center;">${row.quiz_score !== null ? row.quiz_score : '-'}</td>
                        <td style="text-align:center;">${row.oral_score !== null ? row.oral_score : '-'}</td>
                        <td style="text-align:center;">${row.final_score !== null ? row.final_score : '-'}</td>
                        <td style="text-align:center; font-weight:800; font-size:1.05rem;">${row.total_score !== null ? row.total_score : '-'}</td>
                        <td style="text-align:center;">${statusBadge}</td>
                    </tr>
                `;
            });
        }

        document.getElementById('academic-card-display').style.display = 'block';
    }

    function exportCard(type) {
        if (!currentSelectedStudentId) {
            alert('يرجى اختيار طالب أولاً');
            return;
        }
        const level = document.getElementById('affairs-filter-year')?.value || '';
        const url = `/affairs/academic-card/export-${type}?student_id=${currentSelectedStudentId}&level=${encodeURIComponent(level)}`;
        window.location.href = url;
    }
</script>
@endpush
