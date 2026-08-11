@extends('layouts.hod')

@section('title', 'المواعيد والاستدعاءات')
@section('header-title', 'إدارة اللقاءات والمواعيد')
@section('header-subtitle', 'جدول لقاءات أولياء الأمور واستدعائهم')

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

    /* Form and tables */
    .table-container {
        background: var(--surface-light, #ffffff);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06);
    }
    [data-theme="dark"] .table-container {
        background: var(--surface-dark, #1a2633);
        box-shadow: none;
    }
    .grid-layout {
        display: grid;
        grid-template-cols: 1fr;
        gap: 1.5rem;
    }
    @media(min-width: 1024px) {
        .grid-layout {
            grid-template-cols: 2fr 1fr;
        }
    }
    .card-panel {
        background: var(--surface-light, #ffffff);
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color, #e2e8f0);
    }
    [data-theme="dark"] .card-panel {
        background: var(--surface-dark, #1a2633);
        border-color: #334155;
    }
    select option {
        background-color: #ffffff !important;
        color: #0f172a !important;
    }
    [data-theme="dark"] select option {
        background-color: #1a2633 !important;
        color: #f8fafc !important;
    }
</style>
@endpush

@section('content')
<div style="margin-bottom: 2rem;">
    @if(session('success'))
        <div style="padding: 1rem; background-color: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #10b981; border-radius: 0.75rem; margin-bottom: 1.5rem; font-weight: bold; font-size: 0.9rem;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="padding: 1rem; background-color: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; border-radius: 0.75rem; margin-bottom: 1.5rem; font-weight: bold; font-size: 0.9rem;">
            {{ session('error') }}
        </div>
    @endif

    <div class="custom-tabs">
        <button class="tab-btn active" onclick="switchTab(event, 'meetings-tab')">طلبات المواعيد من الأهالي</button>
        <button class="tab-btn" onclick="switchTab(event, 'summons-tab')">استدعاءات أولياء الأمور</button>
    </div>

    <div class="grid-layout">
        {{-- اليسار: الجداول --}}
        <div>
            {{-- تبويب طلبات الأهالي --}}
            <div id="meetings-tab" class="tab-content active">
                <div class="table-container">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 5px; height: 20px; background-color: #f2f20d; border-radius: 5px;"></span>
                        طلبات اللقاءات مع رئيس القسم
                    </h3>

                    @if($meetings->isEmpty())
                        <div style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                            لا توجد طلبات مواعيد مقدمة إليك حالياً.
                        </div>
                    @else
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.9rem;">
                                <thead>
                                    <tr style="border-b: 2px solid var(--border-color); color: var(--text-secondary); font-weight: 700;">
                                        <th style="padding: 1rem;">ولي الأمر</th>
                                        <th style="padding: 1rem;">الطالب</th>
                                        <th style="padding: 1rem;">الموضوع والسبب</th>
                                        <th style="padding: 1rem;">تاريخ الزيارة</th>
                                        <th style="padding: 1rem;">الحالة</th>
                                        <th style="padding: 1rem; text-align: left;">التحكم</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($meetings as $meeting)
                                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;">
                                            <td style="padding: 1rem; font-weight: bold;">{{ $meeting->parent->full_name ?? 'ولي أمر' }}</td>
                                            <td style="padding: 1rem;">{{ $meeting->student->user->full_name ?? 'غير معروف' }}</td>
                                            <td style="padding: 1rem;">
                                                <div style="font-weight: bold;">{{ $meeting->subject }}</div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ $meeting->reason }}</div>
                                            </td>
                                            <td style="padding: 1rem;">
                                                @if($meeting->scheduled_at)
                                                    <span style="color: #10b981; font-weight: bold;">{{ date('Y-m-d h:i A', strtotime($meeting->scheduled_at)) }}</span>
                                                @elseif($meeting->preferred_date)
                                                    <span style="color: var(--text-secondary);">مفضل: {{ date('Y-m-d', strtotime($meeting->preferred_date)) }}</span>
                                                @else
                                                    <span style="color: var(--text-secondary);">غير محدد</span>
                                                @endif
                                            </td>
                                            <td style="padding: 1rem;">
                                                @if($meeting->status === 'pending')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(245,158,11,0.1); color: #f59e0b; font-size: 0.75rem; font-weight: 700;">قيد الانتظار</span>
                                                @elseif($meeting->status === 'approved')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(16,185,129,0.1); color: #10b981; font-size: 0.75rem; font-weight: 700;">مقبول</span>
                                                @elseif($meeting->status === 'rejected')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.75rem; font-weight: 700;">مرفوض</span>
                                                @elseif($meeting->status === 'completed')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(59,130,246,0.1); color: #3b82f6; font-size: 0.75rem; font-weight: 700;">مكتمل</span>
                                                @endif
                                            </td>
                                            <td style="padding: 1rem; text-align: left;">
                                                <button onclick="openResponseModal({{ json_encode($meeting) }})"
                                                        style="padding: 0.4rem 0.8rem; border: none; border-radius: 0.5rem; background-color: var(--bg-secondary); color: var(--text-primary); cursor: pointer; font-weight: bold; font-size: 0.8rem; transition: background-color 0.2s;">
                                                    الرد
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- تبويب الاستدعاءات --}}
            <div id="summons-tab" class="tab-content">
                <div class="table-container">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 5px; height: 20px; background-color: #ef4444; border-radius: 5px;"></span>
                        استدعاءات أولياء الأمور المرسلة من القسم
                    </h3>

                    @if($summons->isEmpty())
                        <div style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                            لا توجد استدعاءات صادرة من قسمك حتى الآن.
                        </div>
                    @else
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.9rem;">
                                <thead>
                                    <tr style="border-b: 2px solid var(--border-color); color: var(--text-secondary); font-weight: 700;">
                                        <th style="padding: 1rem;">المرسل</th>
                                        <th style="padding: 1rem;">الطالب</th>
                                        <th style="padding: 1rem;">السبب والبيانات</th>
                                        <th style="padding: 1rem;">التاريخ</th>
                                        <th style="padding: 1rem; text-align: left;">حالة اطلاع الأهل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summons as $summon)
                                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;">
                                            <td style="padding: 1rem; font-weight: bold;">{{ $summon->sender->full_name ?? 'الإدارة' }}</td>
                                            <td style="padding: 1rem;">{{ $summon->student->user->full_name ?? 'غير معروف' }}</td>
                                            <td style="padding: 1rem;">
                                                <div style="font-weight: bold;">{{ $summon->reason_title }}</div>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ $summon->details }}</div>
                                            </td>
                                            <td style="padding: 1rem;">{{ $summon->summon_date ? date('Y-m-d', strtotime($summon->summon_date)) : 'غير محدد' }}</td>
                                            <td style="padding: 1rem; text-align: left;">
                                                @if($summon->status === 'sent')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(59,130,246,0.1); color: #3b82f6; font-size: 0.75rem; font-weight: 700;">تم الإرسال</span>
                                                @elseif($summon->status === 'acknowledged')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(16,185,129,0.1); color: #10b981; font-size: 0.75rem; font-weight: 700;">تأكيد حضور ولي الأمر ✓</span>
                                                @elseif($summon->status === 'cancelled')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.75rem; font-weight: 700;">معتذر عن الحضور</span>
                                                @elseif($summon->status === 'attended')
                                                    <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; background-color: rgba(100,116,139,0.1); color: #64748b; font-size: 0.75rem; font-weight: 700;">حضر الاجتماع</span>
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

        {{-- اليمين: إرسال استدعاء جديد --}}
        <div>
            <div class="card-panel">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; color: #ef4444;">
                    <span style="width: 5px; height: 20px; background-color: #ef4444; border-radius: 5px;"></span>
                    استدعاء ولي أمر جديد
                </h3>

                <form action="{{ route('hod.summons.store') }}" method="POST" style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.85rem;">
                    @csrf
                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">اختر الدورة / المادة أولاً:</label>
                        <select id="course_filter_select" onchange="filterStudentsByCourse(this.value)" style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);">
                            <option value="all" selected>-- جميع الدورات / المواد --</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->course_id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">اختر الطالب:</label>
                        <select name="student_id" id="student_select" required style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);">
                            <option value="" disabled selected>-- اختر الطالب للاستدعاء --</option>
                            @foreach($students as $st)
                                @php
                                    $cIds = $st->courses ? $st->courses->pluck('course_id')->toArray() : [];
                                    $cIdsJson = json_encode($cIds);
                                @endphp
                                <option value="{{ $st->student_id }}" data-courses="{{ $cIdsJson }}">
                                    {{ $st->user->full_name ?? 'بدون اسم' }} - [{{ $st->level }}]
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">سبب الاستدعاء المباشر:</label>
                        <input type="text" name="reason_title" required placeholder="مثال: مناقشة أداء الطالب الأكاديمي"
                               style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);" />
                    </div>

                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">تفاصيل إضافية للوالدين:</label>
                        <textarea name="details" required rows="4" placeholder="يرجى كتابة التفاصيل التي ستظهر للأهل لمساعدتهم على فهم الموضوع وتنسيق اللقاء..."
                                  style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);"></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">التاريخ المطلوب للحضور:</label>
                        <input type="date" name="summon_date"
                               style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);" />
                    </div>

                    <button type="submit" style="width: 100%; padding: 0.75rem; border: none; border-radius: 0.5rem; background-color: #ef4444; color: white; font-weight: bold; cursor: pointer; transition: background-color 0.2s;">
                        إرسال الاستدعاء الآن
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- مودال الرد على طلب الموعد --}}
<div id="responseModal" style="position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
    <div style="background-color: var(--surface-light); border: 1px solid var(--border-color); border-radius: 1rem; padding: 1.5rem; width: 100%; max-width: 450px; box-shadow: var(--shadow); font-size: 0.85rem; display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <h3 id="modalTitle" style="font-size: 1rem; font-weight: 700;">الرد على موعد ولي الأمر</h3>
            <button onclick="closeResponseModal()" style="border: none; background: transparent; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">&times;</button>
        </div>

        <form id="modalForm" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            @csrf
            
            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">القرار النهائي:</label>
                <select name="status" id="modalStatus" required style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);">
                    <option value="approved">موافقة وتثبيت الموعد</option>
                    <option value="rejected">اعتذار عن اللقاء</option>
                    <option value="completed">تم اكتمال المقابلة</option>
                </select>
            </div>

            <div id="dateInputContainer">
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">تحديد التاريخ والوقت المثبت:</label>
                <input type="datetime-local" name="scheduled_at" id="modalScheduledAt"
                       style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);" />
            </div>

            <div>
                <label style="display: block; font-weight: bold; margin-bottom: 0.4rem; color: var(--text-secondary);">ملاحظات ولي الأمر:</label>
                <textarea name="admin_response" id="modalAdminResponse" rows="3" placeholder="اكتب ملاحظاتك للأهل هنا..."
                          style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary);"></textarea>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 0.75rem; margin-top: 0.5rem;">
                <button type="button" onclick="closeResponseModal()" style="padding: 0.6rem 1.2rem; border-radius: 0.5rem; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary); font-weight: bold; cursor: pointer;">إلغاء</button>
                <button type="submit" style="padding: 0.6rem 1.2rem; border: none; border-radius: 0.5rem; background-color: #f2f20d; color: #1a1a1a; font-weight: bold; cursor: pointer;">حفظ الرد</button>
            </div>
        </form>
    </div>
</div>

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

    function openResponseModal(meeting) {
        document.getElementById('modalForm').action = `/hod/appointments/${meeting.id}/respond`;
        document.getElementById('modalTitle').innerText = `الرد على اللقاء لـ: ${meeting.parent.full_name}`;
        document.getElementById('modalStatus').value = meeting.status;
        document.getElementById('modalAdminResponse').value = meeting.admin_response || '';
        
        if(meeting.scheduled_at) {
            const date = new Date(meeting.scheduled_at);
            const formatted = date.toISOString().slice(0, 16);
            document.getElementById('modalScheduledAt').value = formatted;
        } else {
            document.getElementById('modalScheduledAt').value = '';
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

    function filterStudentsByCourse(selectedCourseId) {
        const studentSelect = document.getElementById('student_select');
        const options = studentSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return; // skip placeholder option

            if (selectedCourseId === 'all') {
                opt.style.display = '';
                opt.disabled = false;
            } else {
                const courseIds = JSON.parse(opt.getAttribute('data-courses') || '[]');
                if (courseIds.includes(parseInt(selectedCourseId))) {
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
@endsection
