@extends('layouts.student')
@section('title', 'طلبات الإذن')
@section('subtitle', 'سجل طلبات الإذن والغياب المسبق')

@push('styles')
<style>
    /* 🌟 رأس الصفحة العلوي مع زر الإضافة (+ طلب إذن جديد) */
    .page-header-banner {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem 1.75rem;
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        border: 1px solid var(--border-color);
        border-right: 5px solid var(--accent-color);
    }
    .page-header-info h2 {
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0 0 0.3rem 0;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .page-header-info p {
        font-size: 0.88rem;
        color: var(--text-secondary);
        margin: 0;
        font-weight: 600;
    }
    .btn-create-leave {
        background: var(--accent-color);
        color: #1a1a1a;
        border: none;
        padding: 0.85rem 1.6rem;
        border-radius: 0.85rem;
        font-size: 0.95rem;
        font-weight: 800;
        cursor: pointer;
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        box-shadow: 0 4px 14px rgba(242, 242, 13, 0.35);
        transition: all 0.25s ease;
    }
    .btn-create-leave:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(242, 242, 13, 0.5);
    }

    /* 🌟 دعم الأشكال المدخلة */
    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-primary); }
    
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

    /* ☀️ الوضع الفاتح */
    [data-theme="light"] input[type="date"],
    [data-theme="light"] input[type="time"],
    [data-theme="light"] .form-control,
    html:not(.dark):not([data-theme="dark"]) input[type="date"],
    html:not(.dark):not([data-theme="dark"]) input[type="time"],
    html:not(.dark):not([data-theme="dark"]) .form-control {
        color-scheme: light !important;
        color: #0f172a !important;
        background-color: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
    }

    .btn-submit {
        background: var(--accent-color); color: #1a1a1a;
        border: none; padding: 0.875rem 2rem; width: 100%;
        border-radius: 0.75rem; font-size: 1rem; font-weight: 800;
        cursor: pointer; font-family: inherit; transition: all 0.2s;
        display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    }
    .btn-submit:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.05); }
    .btn-submit:disabled {
        opacity: 0.65;
        cursor: not-allowed !important;
        transform: none !important;
    }

    /* 🌟 بطاقات الطلبات السابقة */
    .request-card {
        background: var(--bg-secondary);
        border-radius: 1.1rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
        transition: transform 0.2s ease, border-color 0.2s ease;
        animation: fadeInCard 0.3s ease-out;
    }
    .request-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent-color);
    }
    .badge {
        padding: 0.3rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.78rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .badge-approved { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .badge-rejected { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    .badge-pending  { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }

    /* تخصيص شريط التمرير السلس داخل الـ Modals */
    .modal-scrollable-body::-webkit-scrollbar {
        width: 6px;
    }
    .modal-scrollable-body::-webkit-scrollbar-track {
        background: var(--bg-primary);
        border-radius: 4px;
    }
    .modal-scrollable-body::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 4px;
    }
    .modal-scrollable-body::-webkit-scrollbar-thumb:hover {
        background: var(--accent-color);
    }

    @keyframes fadeInCard {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')

{{-- تنبيهات الرسائل الديناميكية --}}
<div id="dynamicAlertContainer">
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
</div>

{{-- 🌟 الهيدر الرئيسي وزر إضافة طلب إذن جديد --}}
<div class="page-header-banner">
    <div class="page-header-info">
        <h2>
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-color);"></i>
            سجل طلبات الإذن والغياب
        </h2>
        <p>استعراض الأذون السابقة ومتابعة حالة الاعتمادات الأكاديمية</p>
    </div>
    <button type="button" class="btn-create-leave" onclick="openNewLeaveRequestModal()">
        <i class="fa-solid fa-plus" style="font-size: 1.1rem;"></i>
        تقديم طلب إذن جديد
    </button>
</div>

{{-- 🌟 قائمة الطلبات السابقة --}}
<div style="margin-bottom: 2rem;">
    <p style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-list-check" style="color: var(--accent-color);"></i>
        <span>طلباتك السابقة (<span id="requestsCount">{{ $requests->count() }}</span>)</span>
    </p>

    <div id="requestsListContainer">
        @forelse($requests as $r)
        <div class="request-card" id="req-card-{{ $r->request_id ?? $r->id }}">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.4rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        @if($r->status === 'approved')
                            <span class="badge badge-approved"><i class="fa-solid fa-check"></i> موافق عليه</span>
                        @elseif($r->status === 'rejected')
                            <span class="badge badge-rejected"><i class="fa-solid fa-xmark"></i> مرفوض</span>
                        @else
                            <span class="badge badge-pending"><i class="fa-solid fa-hourglass-half"></i> قيد المراجعة (معلق)</span>
                        @endif
                        <span style="font-weight: 800; font-size: 0.95rem; color: var(--text-primary);">
                            <i class="fa-regular fa-calendar-days" style="color: var(--accent-color);"></i>
                            تاريخ الإذن: {{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}
                        </span>
                    </div>
                    <div style="color: var(--text-secondary); font-size: 0.8rem; font-weight: 600;">
                        <i class="fa-regular fa-clock" style="color: var(--accent-color);"></i>
                        تاريخ التقديم: {{ \Carbon\Carbon::parse($r->created_at)->format('Y-m-d - h:i A') }}
                    </div>
                </div>
                
                <div style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6; margin-top: 0.5rem; background: var(--bg-primary); padding: 0.75rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color);">
                    <strong style="color: var(--text-primary);">السبب:</strong> {{ $r->reason }}
                </div>
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.85rem; flex-wrap: wrap; gap: 0.6rem; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                    <div>
                        @if($r->document)
                            <a href="/storage/{{ $r->document }}" target="_blank" download
                               style="color: var(--text-secondary); font-weight: 700; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; background: var(--bg-primary); padding: 0.4rem 0.8rem; border-radius: 0.5rem; border: 1px solid var(--border-color);">
                                <i class="fa-solid fa-paperclip" style="color: var(--accent-color);"></i> المستند المرفق
                            </a>
                        @else
                            <span style="font-size: 0.8rem; color: var(--text-secondary);"><i class="fa-regular fa-file-excel"></i> لا يوجد مستند مرفق</span>
                        @endif
                    </div>
                    
                    {{-- زر عرض بطاقة الخروج الرسمية للحراس --}}
                    <button type="button" 
                            onclick="openExitPassModal('{{ $r->request_id ?? $r->id }}', '{{ addslashes(auth()->user()->full_name ?? 'الطالب') }}', '{{ addslashes($student->student_code ?? 'غير محدد') }}', '{{ addslashes(auth()->user()->department ?? 'قسم العام') }}', '{{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}', '{{ addslashes($r->reason ?? '') }}', '{{ $r->status }}', '{{ $r->created_at }}')"
                            style="background: {{ $r->status === 'approved' ? '#10b981' : ($r->status === 'rejected' ? '#ef4444' : 'var(--accent-color)') }}; color: {{ $r->status === 'approved' || $r->status === 'rejected' ? '#ffffff' : '#1a1a1a' }}; border: none; padding: 0.5rem 1.1rem; border-radius: 0.65rem; font-size: 0.85rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 0.45rem; font-family: inherit; transition: all 0.2s;">
                        <i class="fa-solid fa-id-card"></i> {{ $r->status === 'approved' ? 'عرض تصريح الخروج (للحراس)' : 'عرض تفاصيل الاعتماد' }}
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div id="emptyRequestsState" style="text-align: center; padding: 3.5rem 1.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-envelope-open-text" style="font-size: 2.8rem; color: var(--accent-color); opacity: 0.6; display: block; margin-bottom: 0.8rem;"></i>
            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.3rem;">لا توجد طلبات إذن سابقة</h3>
            <p style="font-size: 0.88rem; margin-bottom: 1.2rem;">يمكنك تقديم طلب إذن جديد بالضغط على زر "تقديم طلب إذن جديد" أعلاه.</p>
            <button type="button" class="btn-create-leave" onclick="openNewLeaveRequestModal()">
                <i class="fa-solid fa-plus"></i> تقديم طلب الآن
            </button>
        </div>
        @endforelse
    </div>
</div>

{{-- 🌟 1. النافذة المنبثقة لتقديم طلب إذن جديد (New Leave Request Modal) 🌟 --}}
<div id="newLeaveRequestModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-secondary); border-radius: 1.35rem; width: 100%; max-width: 580px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.4); border: 1px solid var(--border-color); border-top: 5px solid var(--accent-color); overflow: hidden; position: relative; animation: modalPop 0.25s ease-out;">
        
        {{-- Modal Header مع زر الرجوع والإغلاق --}}
        <div style="background: var(--bg-primary); padding: 1rem 1.35rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 0.65rem;">
                <button type="button" onclick="closeNewLeaveRequestModal()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.4rem 0.8rem; border-radius: 0.65rem; cursor: pointer; font-size: 0.85rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem; font-family: inherit; transition: all 0.2s;">
                    <i class="fa-solid fa-arrow-right" style="color: var(--accent-color);"></i> رجوع
                </button>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0; color: var(--text-primary);">تقديم طلب إذن جديد</h3>
                </div>
            </div>
            <button type="button" onclick="closeNewLeaveRequestModal()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
        </div>

        {{-- Modal Form Body قابلة للسكرول بالسحب --}}
        <div class="modal-scrollable-body" style="padding: 1.5rem; overflow-y: auto; flex: 1;" dir="rtl">
            <form action="{{ route('student.leave_requests.store') }}" method="POST" enctype="multipart/form-data" id="leaveRequestForm" novalidate onsubmit="handleLeaveFormAjaxSubmit(event)">
                @csrf
                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-list" style="color: var(--accent-color);"></i> نوع الإذن</label>
                    <select name="type" id="leaveTypeSelect" class="form-control" onchange="toggleHourlyFields(this.value)">
                        <option value="full_day">إذن يوم كامل (يومي)</option>
                        <option value="hourly">إذن ساعي (ساعات محددة)</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fa-regular fa-calendar-days" style="color: var(--accent-color);"></i> تاريخ الإذن</span>
                            <span style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 700;">(YYYY-MM-DD)</span>
                        </label>
                        <input type="date" name="date" id="leaveDateInput" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    <div id="fullDayTimeField">
                        <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> وقت الإذن</span>
                            <span style="font-size: 0.75rem; background: var(--accent-color); color: #1a1a1a; padding: 0.1rem 0.4rem; border-radius: 0.3rem; font-weight: 800;">ساعة : دقيقة</span>
                        </label>
                        <input type="time" name="leave_time" id="leaveTimeInput" class="form-control" value="{{ date('H:i') }}">
                    </div>
                </div>

                <div id="hourlyTimeFields" style="display: none; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> من الساعة</span>
                            <span style="font-size: 0.75rem; background: var(--accent-color); color: #1a1a1a; padding: 0.1rem 0.4rem; border-radius: 0.3rem; font-weight: 800;">ساعة : دقيقة</span>
                        </label>
                        <input type="time" name="from_time" id="fromTimeInput" class="form-control" value="{{ date('H:i') }}" disabled>
                    </div>
                    <div>
                        <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fa-regular fa-clock" style="color: var(--accent-color);"></i> إلى الساعة</span>
                            <span style="font-size: 0.75rem; background: var(--accent-color); color: #1a1a1a; padding: 0.1rem 0.4rem; border-radius: 0.3rem; font-weight: 800;">ساعة : دقيقة</span>
                        </label>
                        <input type="time" name="to_time" id="toTimeInput" class="form-control" value="{{ date('H:i', strtotime('+2 hours')) }}" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-align-right" style="color: var(--accent-color);"></i> سبب طلب الإذن بالتفصيل</label>
                    <textarea name="reason" id="leaveReasonInput" class="form-control" rows="3" placeholder="اكتب سبب طلب الإذن هنا..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-paperclip" style="color: var(--accent-color);"></i> مستند مرفق (تقرير طبي أو عذر رسمياً - اختياري)</label>
                    <input type="file" name="document" id="leaveDocumentInput" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="submit" id="submitLeaveBtn" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> تقديم الطلب
                    </button>
                    <button type="button" onclick="closeNewLeaveRequestModal()" style="background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.875rem 1.5rem; border-radius: 0.75rem; font-size: 0.95rem; font-weight: 700; cursor: pointer; font-family: inherit;">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 🌟 2. نافذة تفاصيل الإذن وتصريح الخروج الرسمي للحراس (Exit Pass & Details Modal) 🌟 --}}
<div id="exitPassModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: var(--bg-secondary); border-radius: 1.35rem; width: 100%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.4); border: 1px solid var(--border-color); border-top: 5px solid var(--accent-color); overflow: hidden; position: relative; animation: modalPop 0.25s ease-out;">
        
        {{-- Modal Header مع سهم رجوع بارز وزر إغلاق --}}
        <div style="background: var(--bg-primary); padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <button type="button" onclick="closeExitPassModal()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.45rem 0.9rem; border-radius: 0.65rem; cursor: pointer; font-size: 0.85rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.45rem; font-family: inherit; transition: all 0.2s;">
                <i class="fa-solid fa-arrow-right" style="color: var(--accent-color); font-size: 0.95rem;"></i> رجوع
            </button>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-graduation-cap" style="font-size: 1.25rem; color: var(--accent-color);"></i>
                <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0; color: var(--text-primary);">Edu-Bridge | تفاصيل الإذن</h3>
            </div>
            <button type="button" onclick="closeExitPassModal()" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
        </div>

        {{-- Modal Content Body قابلة للسكرول والتصفح الكامل بالماوس أو اللمس --}}
        <div class="modal-scrollable-body" style="padding: 1.25rem; overflow-y: auto; flex: 1;" dir="rtl">
            
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
                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 0.4rem; gap: 0.5rem;">
                    <span style="color: var(--text-secondary); white-space: nowrap;"><i class="fa-solid fa-comment-dots" style="color: var(--accent-color);"></i> سبب الخروج:</span>
                    <strong id="passReason" style="color: var(--text-primary); font-weight: 600; text-align: left; word-break: break-word;"></strong>
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

            {{-- QR Code --}}
            <div style="text-align: center; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                <div style="display: inline-block; background: #ffffff; padding: 0.45rem 0.65rem; border-radius: 0.5rem; border: 1px solid var(--border-color); margin-bottom: 0.3rem;">
                    <i class="fa-solid fa-qrcode" style="font-size: 3rem; color: #1a1a1a;"></i>
                </div>
                <div style="font-size: 0.74rem; color: var(--text-secondary); font-weight: 600;">رمز التحقق الأمني المعتمد لحراس البوابة الإلكترونية</div>
            </div>

            {{-- Action Buttons --}}
            <div style="margin-top: 1.25rem;">
                <button type="button" onclick="window.print()" style="width: 100%; background: var(--accent-color); color: #1a1a1a; border: none; padding: 0.85rem; border-radius: 0.75rem; font-size: 0.95rem; font-weight: 800; cursor: pointer; font-family: inherit; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 14px rgba(242, 242, 13, 0.3);">
                    <i class="fa-solid fa-print" style="font-size: 1.1rem;"></i> طباعة البطاقة
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
function openNewLeaveRequestModal() {
    const modal = document.getElementById('newLeaveRequestModal');
    if (modal) {
        modal.style.display = 'flex';
        const submitBtn = document.getElementById('submitLeaveBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> تقديم الطلب';
        }
    }
}

function closeNewLeaveRequestModal() {
    const modal = document.getElementById('newLeaveRequestModal');
    if (modal) modal.style.display = 'none';
}

function toggleHourlyFields(typeVal) {
    const fullDayTimeField = document.getElementById('fullDayTimeField');
    const hourlyTimeFields = document.getElementById('hourlyTimeFields');
    const leaveTimeInput = document.getElementById('leaveTimeInput');
    const fromTimeInput = document.getElementById('fromTimeInput');
    const toTimeInput = document.getElementById('toTimeInput');

    if (typeVal === 'hourly') {
        if (fullDayTimeField) fullDayTimeField.style.display = 'none';
        if (hourlyTimeFields) hourlyTimeFields.style.display = 'grid';

        if (leaveTimeInput) leaveTimeInput.disabled = true;
        if (fromTimeInput) fromTimeInput.disabled = false;
        if (toTimeInput) toTimeInput.disabled = false;
    } else {
        if (fullDayTimeField) fullDayTimeField.style.display = 'block';
        if (hourlyTimeFields) hourlyTimeFields.style.display = 'none';

        if (leaveTimeInput) leaveTimeInput.disabled = false;
        if (fromTimeInput) fromTimeInput.disabled = true;
        if (toTimeInput) toTimeInput.disabled = true;
    }
}

// 🚀 إرسال التقديم عبر AJAX بسلاسة مع التجاوز المطلق للـ Validation المعطل للمتصفح
let isSubmittingLeave = false;

function handleLeaveFormAjaxSubmit(event) {
    event.preventDefault();

    if (isSubmittingLeave) return false;

    const form = document.getElementById('leaveRequestForm');
    const submitBtn = document.getElementById('submitLeaveBtn');
    const reasonInput = document.getElementById('leaveReasonInput');

    if (!reasonInput || !reasonInput.value.trim()) {
        alert('⚠️ يرجى كتابة سبب طلب الإذن أولاً.');
        if (reasonInput) reasonInput.focus();
        return false;
    }

    // قفل الزر مباشرة وتغيير نصه
    isSubmittingLeave = true;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري إرسال الطلب...';
    }

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw err; });
        }
        return response.json();
    })
    .then(data => {
        isSubmittingLeave = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> تقديم الطلب';
        }

        if (data.success) {
            closeNewLeaveRequestModal();

            const emptyState = document.getElementById('emptyRequestsState');
            if (emptyState) emptyState.style.display = 'none';

            const countEl = document.getElementById('requestsCount');
            if (countEl) {
                const currentCount = parseInt(countEl.innerText) || 0;
                countEl.innerText = currentCount + 1;
            }

            const req = data.request;
            const studentName = "{{ addslashes(auth()->user()->full_name ?? 'الطالب') }}";
            const studentCode = "{{ addslashes($student->student_code ?? 'غير محدد') }}";
            const department  = "{{ addslashes(auth()->user()->department ?? 'قسم العام') }}";

            const newCardHtml = `
                <div class="request-card" id="req-card-${req.id}">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.4rem;">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <span class="badge badge-pending"><i class="fa-solid fa-hourglass-half"></i> قيد المراجعة (معلق)</span>
                                <span style="font-weight: 800; font-size: 0.95rem; color: var(--text-primary);">
                                    <i class="fa-regular fa-calendar-days" style="color: var(--accent-color);"></i>
                                    تاريخ الإذن: ${req.date}
                                </span>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.8rem; font-weight: 600;">
                                <i class="fa-regular fa-clock" style="color: var(--accent-color);"></i>
                                تاريخ التقديم: الآن
                            </div>
                        </div>
                        
                        <div style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6; margin-top: 0.5rem; background: var(--bg-primary); padding: 0.75rem 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color);">
                            <strong style="color: var(--text-primary);">السبب:</strong> ${req.reason}
                        </div>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.85rem; flex-wrap: wrap; gap: 0.6rem; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                            <div>
                                ${req.document ? `
                                    <a href="/storage/${req.document}" target="_blank" download
                                       style="color: var(--text-secondary); font-weight: 700; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; background: var(--bg-primary); padding: 0.4rem 0.8rem; border-radius: 0.5rem; border: 1px solid var(--border-color);">
                                        <i class="fa-solid fa-paperclip" style="color: var(--accent-color);"></i> المستند المرفق
                                    </a>
                                ` : `<span style="font-size: 0.8rem; color: var(--text-secondary);"><i class="fa-regular fa-file-excel"></i> لا يوجد مستند مرفق</span>`}
                            </div>
                            
                            <button type="button" 
                                    onclick="openExitPassModal('${req.id}', '${studentName}', '${studentCode}', '${department}', '${req.date}', '${req.reason.replace(/'/g, "\\'")}', 'pending_parent', '${req.created_at}')"
                                    style="background: var(--accent-color); color: #1a1a1a; border: none; padding: 0.5rem 1.1rem; border-radius: 0.65rem; font-size: 0.85rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 0.45rem; font-family: inherit;">
                                <i class="fa-solid fa-id-card"></i> عرض تفاصيل الاعتماد
                            </button>
                        </div>
                    </div>
                </div>
            `;

            const container = document.getElementById('requestsListContainer');
            if (container) {
                container.insertAdjacentHTML('afterbegin', newCardHtml);
            }

            const alertBox = document.getElementById('dynamicAlertContainer');
            if (alertBox) {
                alertBox.innerHTML = `
                    <div style="background: #dcfce7; color: #15803d; padding: 0.85rem 1.25rem; border-radius: 0.75rem; font-weight: 700; margin-bottom: 1.25rem; border: 1px solid #86efac; display: flex; align-items: center; gap: 0.5rem; animation: modalPop 0.3s ease-out;">
                        <i class="fa-solid fa-circle-check"></i> ${data.message}
                    </div>
                `;
            }

            form.reset();
            toggleHourlyFields('full_day');
        } else {
            alert('⚠️ ' + (data.message || 'حدث خطأ أثناء تقديم الطلب.'));
        }
    })
    .catch(err => {
        isSubmittingLeave = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> تقديم الطلب';
        }
        console.error('Submission error:', err);
        const errorMsg = err.message || (err.errors ? Object.values(err.errors).flat().join('\n') : 'تعذر الاتصال بالسيرفر.');
        alert('⚠️ تنبيه: ' + errorMsg);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    toggleHourlyFields(document.getElementById('leaveTypeSelect')?.value || 'full_day');
});

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
        text.innerText = 'الطلب قيد المراجعة (معلق) - في انتظار الاعتماد النهائي';

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
