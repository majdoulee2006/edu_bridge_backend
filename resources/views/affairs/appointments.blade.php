@extends('layouts.affairs')

@section('title', 'المواعيد واللقاءات | موظف الشؤون')

@push('styles')
<style>
    /* Card panel styling */
    .card-panel {
        background-color: var(--surface-light);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        transition: all 0.3s ease;
    }
    
    [data-theme="dark"] .card-panel {
        background-color: #131d27;
    }

    /* Tabs Navigation */
    .tabs-nav {
        display: flex;
        gap: 1rem;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 1.5rem;
    }
    
    .tab-btn {
        padding: 0.75rem 1.5rem;
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--text-secondary);
        border: none;
        background: transparent;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .tab-btn.active {
        color: var(--accent-color);
        border-bottom-color: var(--accent-color);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }

    /* Table styling */
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .data-table th {
        background-color: rgba(0, 0, 0, 0.02);
        padding: 1rem;
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border-color);
        text-align: right;
    }

    [data-theme="dark"] .data-table th {
        background-color: rgba(255, 255, 255, 0.02);
    }

    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .status-pending { background-color: rgba(245, 158, 11, 0.15); color: #d97706; }
    .status-approved { background-color: rgba(16, 185, 129, 0.15); color: #10b981; }
    .status-rejected { background-color: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .status-completed { background-color: rgba(59, 130, 246, 0.15); color: #3b82f6; }

    select option {
        background-color: #ffffff !important;
        color: #0f172a !important;
    }
    [data-theme="dark"] select option {
        background-color: #1a2633 !important;
        color: #f8fafc !important;
    }

    /* Solid opaque background for modals */
    .modal-card {
        background-color: #ffffff !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4) !important;
    }
    [data-theme="dark"] .modal-card {
        background-color: #0f172a !important;
        color: #f8fafc !important;
        border: 1px solid #334155 !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9) !important;
    }
    .modal-card input,
    .modal-card select,
    .modal-card textarea {
        background-color: #ffffff !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
    }
    [data-theme="dark"] .modal-card input,
    [data-theme="dark"] .modal-card select,
    [data-theme="dark"] .modal-card textarea {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border: 1px solid #334155 !important;
    }
</style>
@endpush

@section('content')
<div style="width: 100%;">
    <!-- Top Action Bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.25rem;">
                <i class="fa-solid fa-calendar-check" style="color: var(--accent-color); margin-left: 0.5rem;"></i>
                إدارة المواعيد واللقاءات الاستدعائية
            </h2>
            <p style="color: var(--text-secondary); font-size: 0.88rem;">متابعة طلبات لقاء أولياء الأمور وإرسال الاستدعاءات الرسمية للطلاب في جميع الأقسام</p>
        </div>
        <button onclick="openCreateSummonModal()" style="background-color: #ef4444; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(239,68,68,0.3); transition: all 0.2s;">
            <i class="fa-solid fa-plus"></i>
            استدعاء ولي أمر جديد
        </button>
    </div>

    <!-- Main Card Panel -->
    <div class="card-panel">
        <!-- Tabs Navigation -->
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab(event, 'meetings-tab')">
                <i class="fa-solid fa-comments"></i>
                طلبات اللقاء من أولياء الأمور
                <span style="background: var(--accent-color); color: #1a1a1a; border-radius: 1rem; padding: 0.1rem 0.6rem; font-size: 0.75rem; margin-right: 0.25rem;">
                    {{ $meetings->count() }}
                </span>
            </button>
            <button class="tab-btn" onclick="switchTab(event, 'summons-tab')">
                <i class="fa-solid fa-user-gear"></i>
                استدعاءات أولياء الأمور
                <span style="background: rgba(239,68,68,0.15); color: #ef4444; border-radius: 1rem; padding: 0.1rem 0.6rem; font-size: 0.75rem; margin-right: 0.25rem;">
                    {{ $summons->count() }}
                </span>
            </button>
        </div>

        <!-- Tab 1: Meetings from Parents -->
        <div id="meetings-tab" class="tab-content active">
            @if($meetings->isEmpty())
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p style="font-size: 1rem; font-weight: 600;">لا يوجد طلبات لقاء مقدمة من أولياء الأمور حالياً</p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ولي الأمر</th>
                                <th>الطالب التابع له</th>
                                <th>سبب اللقاء Requested</th>
                                <th>تاريخ التقديم</th>
                                <th>الموعد المحدد</th>
                                <th>الحالة</th>
                                <th style="text-align: center;">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($meetings as $meeting)
                                <tr>
                                    <td>
                                        <div style="font-weight: 700;">{{ $meeting->parent->full_name ?? 'غير معروف' }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ $meeting->parent->phone ?? '' }}</div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600;">{{ $meeting->student->user->full_name ?? 'غير معروف' }}</span>
                                        <span style="font-size: 0.75rem; color: var(--text-secondary); display: block;">[{{ $meeting->student->level ?? '' }} - {{ $meeting->student->user->department ?? '' }}]</span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;">{{ $meeting->reason_title }}</div>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $meeting->details }}
                                        </div>
                                    </td>
                                    <td>{{ $meeting->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($meeting->scheduled_at)
                                            <span style="font-weight: 700; color: #3b82f6;">
                                                <i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($meeting->scheduled_at)->format('Y-m-d H:i') }}
                                            </span>
                                        @else
                                            <span style="color: var(--text-secondary); font-size: 0.8rem;">لم يحدد بعد</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($meeting->status === 'pending')
                                            <span class="status-badge status-pending"><i class="fa-solid fa-hourglass-start"></i> قيد الانتظار</span>
                                        @elseif($meeting->status === 'approved')
                                            <span class="status-badge status-approved"><i class="fa-solid fa-circle-check"></i> تم التثبيت</span>
                                        @elseif($meeting->status === 'rejected')
                                            <span class="status-badge status-rejected"><i class="fa-solid fa-circle-xmark"></i> اعتذار</span>
                                        @else
                                            <span class="status-badge status-completed"><i class="fa-solid fa-check-double"></i> مكتمل</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <button onclick='openResponseModal(@json($meeting))' 
                                                style="padding: 0.4rem 0.8rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--surface-light); color: var(--text-primary); cursor: pointer; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            معالجة / رد
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Tab 2: Summons Sent -->
        <div id="summons-tab" class="tab-content">
            @if($summons->isEmpty())
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                    <i class="fa-solid fa-user-xmark" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p style="font-size: 1rem; font-weight: 600;">لا يوجد استدعاءات صادرة لأولياء الأمور حالياً</p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الجهة المستدعية</th>
                                <th>الطالب</th>
                                <th>ولي الأمر المستدعى</th>
                                <th>سبب الاستدعاء</th>
                                <th>التاريخ المحدد</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summons as $summon)
                                <tr>
                                    <td>
                                        <span style="font-weight: 700; color: var(--text-primary);">
                                            {{ $summon->sender->full_name ?? 'الإدارة العامة / الشؤون' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700;">{{ $summon->student->user->full_name ?? 'غير معروف' }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary);">[{{ $summon->student->level ?? '' }} - {{ $summon->student->user->department ?? '' }}]</div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;">{{ $summon->parent->full_name ?? 'ولي الأمر' }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary);">{{ $summon->parent->phone ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #ef4444;">{{ $summon->reason_title }}</div>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $summon->details }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($summon->summon_date)
                                            <span style="font-weight: 700; color: #ef4444;">
                                                <i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::parse($summon->summon_date)->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span style="color: var(--text-secondary);">في أقرب وقت</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($summon->status === 'sent')
                                            <span class="status-badge status-pending"><i class="fa-solid fa-paper-plane"></i> تم الإرسال</span>
                                        @elseif($summon->status === 'read')
                                            <span class="status-badge status-approved"><i class="fa-solid fa-eye"></i> تم الاطلاع</span>
                                        @else
                                            <span class="status-badge status-completed"><i class="fa-solid fa-check"></i> مكتمل</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- مودال إضافة استدعاء جديد --}}
<div id="createSummonModal" style="position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.75); backdrop-filter: blur(8px);">
    <div class="modal-card" style="border-radius: 1.25rem; padding: 1.75rem; width: 100%; max-width: 520px; font-size: 0.85rem; display: flex; flex-direction: column; gap: 1.2rem; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #ef4444; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-user-plus"></i>
                إرسال استدعاء رسمي لولي الأمر
            </h3>
            <button onclick="closeCreateSummonModal()" style="border: none; background: transparent; color: var(--text-secondary); cursor: pointer; font-size: 1.6rem; line-height: 1;">&times;</button>
        </div>

        <form action="{{ route('affairs.summons.store') }}" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">تصفية حسب الدورة / الاختصاص أولاً (اختياري):</label>
                <select id="program_filter_select" onchange="filterStudentsByProgram(this.value)" style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;">
                    <option value="all" selected>-- جميع الاختصاصات والبرامج --</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">اختر الطالب المستهدف:</label>
                <select name="student_id" id="student_select" required style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;">
                    <option value="" disabled selected>-- اختر الطالب لإرسال استدعاء لوليه --</option>
                    @foreach($students as $st)
                        <option value="{{ $st->student_id }}" data-program-id="{{ $st->program_id }}">
                            {{ $st->user->full_name ?? 'بدون اسم' }} - [{{ $st->program->name ?? $st->level }} - {{ $st->user->department ?? '' }}]
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">عنوان / سبب الاستدعاء المباشر:</label>
                <input type="text" name="reason_title" required placeholder="مثال: الحضور للشؤون لمتابعة وضع الطالب"
                       style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;" />
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">تفاصيل وتعليمات ولي الأمر:</label>
                <textarea name="details" required rows="4" placeholder="يرجى كتابة التفاصيل التي ستظهر للأهل لمساعدتهم على فهم السبب..."
                          style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;"></textarea>
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">التاريخ المطلوب للحضور:</label>
                <input type="date" name="summon_date"
                       style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;" />
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 0.5rem;">
                <button type="button" onclick="closeCreateSummonModal()" style="padding: 0.6rem 1.2rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: inherit; font-weight: bold; cursor: pointer;">إلغاء</button>
                <button type="submit" style="padding: 0.6rem 1.4rem; border: none; border-radius: 0.5rem; background-color: #ef4444; color: white; font-weight: bold; cursor: pointer;">إرسال الاستدعاء الآن</button>
            </div>
        </form>
    </div>
</div>

{{-- مودال الرد على طلب الموعد --}}
<div id="responseModal" style="position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.75); backdrop-filter: blur(8px);">
    <div class="modal-card" style="border-radius: 1.25rem; padding: 1.5rem; width: 100%; max-width: 450px; font-size: 0.85rem; display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <h3 id="modalTitle" style="font-size: 1rem; font-weight: 700;">الرد على موعد ولي الأمر</h3>
            <button onclick="closeResponseModal()" style="border: none; background: transparent; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">&times;</button>
        </div>

        <form id="modalForm" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">القرار النهائي:</label>
                <select name="status" id="modalStatus" required style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;">
                    <option value="approved">موافقة وتثبيت الموعد</option>
                    <option value="rejected">اعتذار عن اللقاء</option>
                    <option value="completed">تم اكتمال المقابلة</option>
                </select>
            </div>

            <div id="dateInputContainer">
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">تحديد التاريخ والوقت المثبت:</label>
                <input type="datetime-local" name="scheduled_at" id="modalScheduledAt"
                       style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;" />
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem;">ملاحظات ولي الأمر / رد الشؤون:</label>
                <textarea name="admin_response" id="modalAdminResponse" rows="3" placeholder="اكتب ملاحظاتك للأهل هنا..."
                          style="width: 100%; padding: 0.75rem; border-radius: 0.5rem;"></textarea>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 0.75rem; margin-top: 0.5rem;">
                <button type="button" onclick="closeResponseModal()" style="padding: 0.6rem 1.2rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: inherit; font-weight: bold; cursor: pointer;">إلغاء</button>
                <button type="submit" style="padding: 0.6rem 1.2rem; border: none; border-radius: 0.5rem; background-color: var(--accent-color); color: #1a1a1a; font-weight: bold; cursor: pointer;">حفظ الرد</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchTab(evt, tabName) {
        const contents = document.getElementsByClassName("tab-content");
        for (let i = 0; i < contents.length; i++) {
            contents[i].classList.remove("active");
        }
        const buttons = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove("active");
        }
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    function getCurrentLocalDateTimeString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function getCurrentLocalDateString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function openResponseModal(meeting) {
        document.getElementById('modalForm').action = `/affairs/appointments/${meeting.id}/respond`;
        document.getElementById('modalTitle').innerText = `الرد على اللقاء لـ: ${meeting.parent ? meeting.parent.full_name : ''}`;
        document.getElementById('modalStatus').value = meeting.status;
        document.getElementById('modalAdminResponse').value = meeting.admin_response || '';
        
        const scheduledInput = document.getElementById('modalScheduledAt');
        const currentNowStr = getCurrentLocalDateTimeString();
        
        // ضبط الحد الأدنى لمنع اختيار تاريخ أو وقت سابق
        scheduledInput.min = currentNowStr;

        if(meeting.scheduled_at) {
            const date = new Date(meeting.scheduled_at);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            scheduledInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        } else {
            scheduledInput.value = currentNowStr;
        }

        document.getElementById('responseModal').style.display = 'flex';
    }

    function closeResponseModal() {
        document.getElementById('responseModal').style.display = 'none';
    }

    document.getElementById('modalStatus').addEventListener('change', function() {
        const container = document.getElementById('dateInputContainer');
        if(this.value === 'rejected') {
            container.style.display = 'none';
        } else {
            container.style.display = 'block';
        }
    });

    document.getElementById('modalForm').addEventListener('submit', function(e) {
        const status = document.getElementById('modalStatus').value;
        const scheduledInput = document.getElementById('modalScheduledAt');
        if (status === 'approved' && scheduledInput.value) {
            const selectedDate = new Date(scheduledInput.value);
            const now = new Date();
            if (selectedDate.getTime() < now.getTime() - 60000) {
                e.preventDefault();
                alert('تنبيه: لا يمكنك اختيار موعد بتاريخ أو وقت سابق للوقت الحالي!');
                return false;
            }
        }
    });

    function openCreateSummonModal() {
        const summonDateInput = document.querySelector('input[name="summon_date"]');
        if (summonDateInput) {
            const todayStr = getCurrentLocalDateString();
            summonDateInput.min = todayStr;
            if (!summonDateInput.value) {
                summonDateInput.value = todayStr;
            }
        }
        document.getElementById('createSummonModal').style.display = 'flex';
    }

    function closeCreateSummonModal() {
        document.getElementById('createSummonModal').style.display = 'none';
    }

    function filterStudentsByProgram(selectedProgramId) {
        const studentSelect = document.getElementById('student_select');
        const options = studentSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return;

            if (selectedProgramId === 'all') {
                opt.style.display = '';
                opt.disabled = false;
            } else {
                const progId = opt.getAttribute('data-program-id');
                if (progId == selectedProgramId) {
                    opt.style.display = '';
                    opt.disabled = false;
                } else {
                    opt.style.display = 'none';
                    opt.disabled = true;
                }
            }
        });

        studentSelect.value = '';
    }
</script>
@endpush
