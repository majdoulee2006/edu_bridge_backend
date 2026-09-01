@extends('layouts.student')
@section('title', 'طلبات الإذن')
@section('subtitle', 'طلبات الغياب المسبق')

@push('styles')
<style>
    .form-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.75rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }
    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-primary); }
    
    /* 🌟 دعم ممتاز وتلقائي للوضعين الفاتح والداكن (Light & Dark Theme) */
    .form-control, input[type="date"], input[type="time"], select.form-control, textarea.form-control {
        width: 100%;
        padding: 0.85rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: 0.75rem;
        background-color: var(--bg-primary) !important;
        color: var(--text-primary) !important;
        font-family: inherit;
        font-size: 1rem;
        font-weight: 700;
        transition: all 0.2s ease;
        outline: none;
    }

    /* 🌙 الوضع الداكن */
    [data-theme="dark"] input[type="date"],
    [data-theme="dark"] input[type="time"],
    html.dark input[type="date"],
    html.dark input[type="time"] {
        color-scheme: dark !important;
        color: #ffffff !important;
        background-color: var(--bg-primary, #000000) !important;
        border-color: var(--border-color, #242424) !important;
    }
    [data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator,
    [data-theme="dark"] input[type="time"]::-webkit-calendar-picker-indicator,
    html.dark input[type="date"]::-webkit-calendar-picker-indicator,
    html.dark input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(0.9) sepia(1) saturate(5) hue-rotate(15deg);
        cursor: pointer;
    }

    /* ☀️ الوضع الفاتح (علاج مشكلة الخط الأبيض والضبابية) */
    [data-theme="light"] input[type="date"],
    [data-theme="light"] input[type="time"],
    [data-theme="light"] .form-control,
    html:not(.dark):not([data-theme="dark"]) input[type="date"],
    html:not(.dark):not([data-theme="dark"]) input[type="time"],
    html:not(.dark):not([data-theme="dark"]) .form-control {
        color-scheme: light !important;
        color: #0f172a !important; /* لون كحلي/أسود غامق واضح جداً */
        background-color: #f1f5f9 !important; /* خلفية رمادية فاتحة ناعمة بدلاً من البياض المفقود */
        border-color: #cbd5e1 !important;
    }
    [data-theme="light"] input[type="date"]::-webkit-calendar-picker-indicator,
    [data-theme="light"] input[type="time"]::-webkit-calendar-picker-indicator,
    html:not(.dark):not([data-theme="dark"]) input[type="date"]::-webkit-calendar-picker-indicator,
    html:not(.dark):not([data-theme="dark"]) input[type="time"]::-webkit-calendar-picker-indicator {
        filter: none !important;
        opacity: 0.85;
        cursor: pointer;
    }

    .form-control:focus, input[type="date"]:focus, input[type="time"]:focus {
        border-color: var(--accent-color, #f2f20d) !important;
        box-shadow: 0 0 0 3px rgba(242, 242, 13, 0.25) !important;
    }

    .btn-submit {
        background: var(--accent-color); color: #1a1a1a;
        border: none; padding: 0.875rem 2rem;
        border-radius: 0.75rem; font-size: 0.95rem; font-weight: 700;
        cursor: pointer; font-family: inherit; transition: transform 0.2s;
    }
    .btn-submit:hover { transform: translateY(-2px); }
</style>
@endpush

@section('content')

@if(session('success'))
    <div style="background: #dcfce7; color: #15803d; padding: 0.85rem 1.25rem; border-radius: 0.75rem; font-weight: 700; margin-bottom: 1.25rem; border: 1px solid #86efac; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div style="background: #fee2e2; color: #b91c1c; padding: 0.85rem 1.25rem; border-radius: 0.75rem; font-weight: 700; margin-bottom: 1.25rem; border: 1px solid #fca5a5; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
    </div>
@endif

{{-- New Request Form --}}
<div class="form-card">
    <p style="font-size: 1.05rem; font-weight: 800; margin-bottom: 1.25rem;">
        <i class="fa-solid fa-envelope-open-text" style="color: var(--accent-color);"></i>
        تقديم طلب إذن جديد
    </p>
    <form action="{{ route('student.leave_requests.store') }}" method="POST" enctype="multipart/form-data" id="leaveRequestForm" onsubmit="return validateLeaveTimeForm(event)">
        @csrf
        <div class="form-group">
            <label class="form-label">نوع الإذن</label>
            <select name="type" id="leaveTypeSelect" class="form-control" onchange="toggleHourlyFields(this.value)" required>
                <option value="full_day">إذن يوم كامل (يومي)</option>
                <option value="hourly">إذن ساعي (ساعات محددة)</option>
            </select>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-regular fa-calendar-days" style="color: var(--accent-color);"></i> تاريخ الغياب والإذن</span>
                    <span style="font-size: 0.78rem; color: var(--text-secondary); font-weight: 700;">(السنة - الشهر - اليوم)</span>
                </label>
                <input type="date" name="date" id="leaveDateInput" class="form-control" required min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" onchange="updateTimeLimits()">
            </div>
            <div id="fullDayTimeField">
                <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> وقت/ساعة الإذن</span>
                    <span style="font-size: 0.78rem; background: var(--accent-color); color: #1a1a1a; padding: 0.15rem 0.5rem; border-radius: 0.4rem; font-weight: 800;">الساعة : الدقيقة</span>
                </label>
                <input type="time" name="leave_time" id="leaveTimeInput" class="form-control" required value="{{ date('H:i') }}">
            </div>
        </div>
        <div id="hourlyTimeFields" style="display: none; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> من الساعة</span>
                    <span style="font-size: 0.78rem; background: var(--accent-color); color: #1a1a1a; padding: 0.15rem 0.5rem; border-radius: 0.4rem; font-weight: 800;">الساعة : الدقيقة</span>
                </label>
                <input type="time" name="from_time" id="fromTimeInput" class="form-control" value="{{ date('H:i') }}">
            </div>
            <div>
                <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> إلى الساعة</span>
                    <span style="font-size: 0.78rem; background: var(--accent-color); color: #1a1a1a; padding: 0.15rem 0.5rem; border-radius: 0.4rem; font-weight: 800;">الساعة : الدقيقة</span>
                </label>
                <input type="time" name="to_time" id="toTimeInput" class="form-control" value="{{ date('H:i', strtotime('+2 hours')) }}">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">سبب الغياب بالتفصيل</label>
            <textarea name="reason" class="form-control" rows="3" placeholder="اكتب سبب طلب الإذن..." required></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">مستند مرفق (تقرير طبي أو عذر رسمي - اختياري)</label>
            <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
        </div>
        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-paper-plane"></i> تقديم الطلب
        </button>
    </form>
</div>

{{-- Previous Requests --}}
<p style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem;">
    <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-color);"></i>
    الطلبات السابقة
</p>

@forelse($requests as $r)
<div class="request-card">
    <div style="flex: 1;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.3rem;">
            @if($r->status === 'approved')
                <span class="badge badge-approved">موافق عليه</span>
            @elseif($r->status === 'rejected')
                <span class="badge badge-rejected">مرفوض</span>
            @else
                <span class="badge badge-pending">قيد المراجعة</span>
            @endif
            <span style="font-weight: 700; font-size: 0.9rem;">
                تاريخ الإذن: {{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}
            </span>
        </div>
        <div style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.5; margin-top: 0.25rem;">{{ $r->reason }}</div>
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.75rem; flex-wrap: wrap; gap: 0.5rem; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
            <div style="color: var(--text-primary); font-weight: 700; font-size: 0.82rem;">
                <i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> تاريخ ووقت التقديم: {{ \Carbon\Carbon::parse($r->created_at)->format('Y-m-d - h:i A') }}
            </div>
            
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                @if($r->document)
                    <a href="/storage/{{ $r->document }}" target="_blank" download
                       style="color: var(--text-secondary); font-weight: 700; font-size: 0.82rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="fa-solid fa-paperclip"></i> المستند المرفق
                    </a>
                @endif
                
                {{-- زر عرض بطاقة الخروج الرسمية للحراس --}}
                <button type="button" 
                        onclick="openExitPassModal('{{ $r->request_id ?? $r->id }}', '{{ addslashes(auth()->user()->full_name ?? 'الطالب') }}', '{{ addslashes($student->student_code ?? 'غير محدد') }}', '{{ addslashes(auth()->user()->department ?? 'قسم العام') }}', '{{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}', '{{ addslashes($r->reason ?? '') }}', '{{ $r->status }}', '{{ $r->created_at }}')"
                        style="background: {{ $r->status === 'approved' ? '#10b981' : ($r->status === 'rejected' ? '#ef4444' : 'var(--accent-color)') }}; color: {{ $r->status === 'approved' || $r->status === 'rejected' ? '#ffffff' : '#1a1a1a' }}; border: none; padding: 0.45rem 0.95rem; border-radius: 0.6rem; font-size: 0.83rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; font-family: inherit;">
                    <i class="fa-solid fa-id-card"></i> {{ $r->status === 'approved' ? 'عرض تصريح الخروج (للحراس)' : 'عرض تفاصيل الطلب' }}
                </button>
            </div>
        </div>
    </div>
</div>
@empty
<div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-envelope-open-text" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
    لا توجد طلبات سابقة
</div>
@endforelse

{{-- 🌟 نافذة تصريح الخروج الرسمي للحراس (Official Exit Pass Modal - Edu-Bridge System) 🌟 --}}
<div id="exitPassModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-secondary); border-radius: 1.25rem; width: 100%; max-width: 460px; box-shadow: var(--shadow); border: 1px solid var(--border-color); border-top: 4px solid var(--accent-color); overflow: hidden; position: relative; animation: modalPop 0.25s ease-out;">
        
        {{-- Modal Header --}}
        <div style="background: var(--bg-primary); padding: 1.25rem; text-align: center; position: relative; border-bottom: 1px solid var(--border-color);">
            <button onclick="closeExitPassModal()" style="position: absolute; left: 1rem; top: 1rem; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
            
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; border-radius: 50%; background: var(--bg-secondary); border: 2px solid var(--accent-color); margin-bottom: 0.5rem;">
                <i class="fa-solid fa-graduation-cap" style="font-size: 1.5rem; color: var(--accent-color);"></i>
            </div>
            <h3 style="font-size: 1.2rem; font-weight: 800; margin: 0; color: var(--text-primary);">Edu-Bridge | بطاقة خروج رسمية</h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem; font-weight: 600;">نظام التراخيص والتصاريح الأكاديمية</p>
        </div>

        {{-- Modal Content Body --}}
        <div style="padding: 1.25rem;" dir="rtl">
            
            {{-- Pass Status Banner --}}
            <div id="passStatusBanner" style="text-align: center; padding: 0.75rem 1rem; border-radius: 0.75rem; font-weight: 800; font-size: 0.9rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.55rem; transition: all 0.3s;">
                <i id="passStatusIcon" class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
                <span id="passStatusText">موافق عليه رسمياً - يُسمح بالمغادرة</span>
            </div>

            {{-- Student Information Card --}}
            <div style="background: var(--bg-primary); border-radius: 0.85rem; padding: 0.9rem; margin-bottom: 0.9rem; border: 1px solid var(--border-color); border-right: 4px solid var(--accent-color); display: flex; align-items: center; gap: 0.85rem;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--accent-color); color: #1a1a1a; font-size: 1.35rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    {{ mb_substr(auth()->user()->full_name ?? 'ط', 0, 1) }}
                </div>
                <div>
                    <h4 id="passStudentName" style="font-size: 1rem; font-weight: 800; color: var(--text-primary); margin: 0;">{{ auth()->user()->full_name }}</h4>
                    <div style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 0.15rem;">
                        الرقم الجامعي: <strong id="passStudentCode" style="color: var(--text-primary); font-weight: 700;">{{ $student->student_code ?? 'غير محدد' }}</strong>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                        القسم الأكاديمي: <span id="passDepartment" style="color: var(--text-primary); font-weight: 600;">{{ auth()->user()->department ?? 'عام' }}</span>
                    </div>
                </div>
            </div>

            {{-- Details List --}}
            <div style="background: var(--bg-primary); border-radius: 0.85rem; padding: 0.85rem; margin-bottom: 0.9rem; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 0.4rem;">
                    <span style="color: var(--text-secondary);"><i class="fa-regular fa-calendar-check" style="color: var(--accent-color);"></i> تاريخ الإذن:</span>
                    <strong id="passDate" style="color: var(--text-primary); font-weight: 700;"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 0.4rem;">
                    <span style="color: var(--text-secondary);"><i class="fa-solid fa-comment-dots" style="color: var(--accent-color);"></i> سبب الخروج:</span>
                    <strong id="passReason" style="color: var(--text-primary); max-width: 220px; text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600;"></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);"><i class="fa-solid fa-barcode" style="color: var(--accent-color);"></i> رقم التصريح:</span>
                    <strong id="passSerial" style="color: var(--text-primary); font-family: monospace; font-size: 0.9rem; font-weight: 700;"></strong>
                </div>
            </div>

            {{-- Sequential Approvals Verification Chain --}}
            <div style="background: var(--bg-primary); padding: 0.85rem; border-radius: 0.85rem; font-size: 0.8rem; margin-bottom: 1rem; border: 1px solid var(--border-color);">
                <div style="font-weight: 800; color: var(--text-primary); margin-bottom: 0.45rem; display: flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-shield-cat" style="color: var(--accent-color); font-size: 0.95rem;"></i> تسلسل الاعتمادات الرسمية:
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.35rem;" id="passApprovalsChain">
                    {{-- Rendered dynamically via JS --}}
                </div>
            </div>

            {{-- QR Code / Security Stamp Mockup --}}
            <div style="text-align: center; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                <div style="display: inline-block; background: #ffffff; padding: 0.45rem 0.65rem; border-radius: 0.5rem; border: 1px solid var(--border-color); margin-bottom: 0.3rem;">
                    <i class="fa-solid fa-qrcode" style="font-size: 3rem; color: #1a1a1a;"></i>
                </div>
                <div style="font-size: 0.74rem; color: var(--text-secondary); font-weight: 600;">رمز التحقق الأمني المعتمد لحراس البوابة الإلكترونية</div>
            </div>

            {{-- Action Buttons --}}
            <div style="display: flex; gap: 0.65rem; margin-top: 1rem;">
                <button onclick="window.print()" style="flex: 1; background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.7rem; border-radius: 0.75rem; font-size: 0.88rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.2s;">
                    <i class="fa-solid fa-print"></i> طباعة البطاقة
                </button>
                <button onclick="closeExitPassModal()" style="flex: 1; background: var(--accent-color); color: #1a1a1a; border: none; padding: 0.7rem; border-radius: 0.75rem; font-size: 0.88rem; font-weight: 800; cursor: pointer; font-family: inherit; transition: all 0.2s;">
                    إغلاق
                </button>
            </div>

        </div>
    </div>
</div>

<style>
@keyframes modalPop {
    from { transform: scale(0.93); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

@endsection

@push('scripts')
<script>
function updateTimeLimits() {
    const dateInput = document.getElementById('leaveDateInput');
    const leaveTimeInput = document.getElementById('leaveTimeInput');
    const fromTimeInput = document.getElementById('fromTimeInput');
    if (!dateInput) return;

    const todayStr = new Date().toISOString().split('T')[0];
    const now = new Date();
    const currentHours = String(now.getHours()).padStart(2, '0');
    const currentMinutes = String(now.getMinutes()).padStart(2, '0');
    const currentTimeStr = `${currentHours}:${currentMinutes}`;

    if (dateInput.value === todayStr) {
        if (leaveTimeInput) leaveTimeInput.setAttribute('min', currentTimeStr);
        if (fromTimeInput) fromTimeInput.setAttribute('min', currentTimeStr);
    } else {
        if (leaveTimeInput) leaveTimeInput.removeAttribute('min');
        if (fromTimeInput) fromTimeInput.removeAttribute('min');
    }
}

function validateLeaveTimeForm(event) {
    const dateInput = document.getElementById('leaveDateInput');
    const typeSelect = document.getElementById('leaveTypeSelect');
    const leaveTimeInput = document.getElementById('leaveTimeInput');
    const fromTimeInput = document.getElementById('fromTimeInput');

    if (!dateInput) return true;

    const todayStr = new Date().toISOString().split('T')[0];
    if (dateInput.value === todayStr) {
        const now = new Date();
        const currentHours = String(now.getHours()).padStart(2, '0');
        const currentMinutes = String(now.getMinutes()).padStart(2, '0');
        const currentTimeStr = `${currentHours}:${currentMinutes}`;

        let selectedTime = '';
        if (typeSelect.value === 'hourly') {
            selectedTime = fromTimeInput ? fromTimeInput.value : '';
        } else {
            selectedTime = leaveTimeInput ? leaveTimeInput.value : '';
        }

        if (selectedTime && selectedTime < currentTimeStr) {
            alert('⚠️ تنبيه: لا يمكن اختيار وقت سابق لوقتنا الحالي لليوم! يرجى اختيار الوقت الحالي أو وقت قادم.');
            event.preventDefault();
            return false;
        }
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    updateTimeLimits();
});

function toggleHourlyFields(typeVal) {
    const fields = document.getElementById('hourlyTimeFields');
    if (!fields) return;
    if (typeVal === 'hourly') {
        fields.style.display = 'grid';
    } else {
        fields.style.display = 'none';
    }
}

function openExitPassModal(reqId, studentName, studentCode, department, date, reason, status, createdAt) {
    const modal = document.getElementById('exitPassModal');
    if (!modal) return;

    document.getElementById('passStudentName').innerText = studentName;
    document.getElementById('passStudentCode').innerText = studentCode;
    document.getElementById('passDepartment').innerText = department;
    document.getElementById('passDate').innerText = date;
    document.getElementById('passReason').innerText = reason;
    document.getElementById('passSerial').innerText = '#EX-' + String(reqId).padStart(5, '0');

    const banner = document.getElementById('passStatusBanner');
    const icon = document.getElementById('passStatusIcon');
    const text = document.getElementById('passStatusText');
    const chain = document.getElementById('passApprovalsChain');

    if (status === 'approved') {
        banner.style.background = 'var(--accent-color)';
        banner.style.color = '#1a1a1a';
        banner.style.border = '1px solid var(--accent-color)';
        icon.className = 'fa-solid fa-circle-check';
        text.innerText = 'تصريح خروج معتمد نهائياً - يُسمح بالمغادرة ✓';

        chain.innerHTML = `
            <div style="color: #10b981; font-weight: 700;"><i class="fa-solid fa-check"></i> موافقة ولي الأمر: تمت بنجاح ✓</div>
            <div style="color: #10b981; font-weight: 700;"><i class="fa-solid fa-check"></i> موافقة رئيس القسم: تمت بنجاح ✓</div>
            <div style="color: #10b981; font-weight: 800;"><i class="fa-solid fa-circle-check"></i> اعتماد شؤون الطلاب: تمت الموافقة وتثبيت الخروج ✓</div>
        `;
    } else if (status === 'rejected') {
        banner.style.background = 'hsl(0,70%,90%)';
        banner.style.color = 'hsl(0,50%,30%)';
        banner.style.border = '1px solid hsl(0,50%,80%)';
        icon.className = 'fa-solid fa-circle-xmark';
        text.innerText = 'طلب مرفوض - لا يُسمح بالمغادرة من البوابة';

        chain.innerHTML = `
            <div style="color: hsl(0,50%,30%); font-weight: 700;"><i class="fa-solid fa-xmark"></i> القرار النهائي: تم رفض طلب الخروج</div>
        `;
    } else {
        banner.style.background = 'hsl(30,70%,90%)';
        banner.style.color = 'hsl(30,50%,30%)';
        banner.style.border = '1px solid hsl(30,50%,80%)';
        icon.className = 'fa-solid fa-clock';
        text.innerText = 'الطلب قيد المراجعة - في انتظار الاعتماد النهائي';

        let stageText = 'بانتظار موافقة ولي الأمر';
        if (status === 'pending_hod') stageText = 'بانتظار موافقة رئيس القسم';
        if (status === 'pending_affairs') stageText = 'بانتظار اعتماد شؤون الطلاب';

        chain.innerHTML = `
            <div style="color: hsl(30,50%,30%); font-weight: 700;"><i class="fa-solid fa-spinner fa-spin"></i> المرحلة الحالية: ${stageText}</div>
        `;
    }

    modal.style.display = 'flex';
}

function closeExitPassModal() {
    const modal = document.getElementById('exitPassModal');
    if (modal) modal.style.display = 'none';
}

// Auto open pass if request_id parameter is present in URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const reqId = urlParams.get('request_id') || urlParams.get('open_pass');
    if (reqId) {
        const btn = document.querySelector(`button[onclick*="'${reqId}'"]`);
        if (btn) btn.click();
    }
});
</script>
@endpush
