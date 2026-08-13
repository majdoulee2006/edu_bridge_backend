@extends('layouts.parent')
@section('title', 'المواعيد والاستدعاءات')

@push('styles')
<style>
    .page-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .btn-create-meeting {
        background: linear-gradient(135deg, var(--accent-color) 0%, #e6b800 100%);
        color: #101924;
        font-weight: 800;
        font-size: 0.95rem;
        padding: 0.8rem 1.6rem;
        border-radius: 2rem;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        box-shadow: 0 4px 20px rgba(242, 242, 13, 0.35);
        transition: all 0.25s ease;
    }
    .btn-create-meeting:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(242, 242, 13, 0.5);
    }
    .btn-create-meeting i {
        font-size: 1.1rem;
        background: rgba(16, 25, 36, 0.15);
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .stat-card-custom {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        box-shadow: var(--shadow);
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .stat-card-custom:hover {
        transform: translateY(-2px);
        border-color: var(--accent-color);
    }

    .stat-icon-wrapper {
        width: 52px;
        height: 52px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    /* Custom Tabs */
    .tabs-header {
        display: flex;
        gap: 0.75rem;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 1.75rem;
        padding-bottom: 0.25rem;
        flex-wrap: wrap;
    }

    .tab-btn {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        font-family: inherit;
        font-size: 1rem;
        font-weight: 700;
        padding: 0.75rem 1.25rem;
        border-radius: 0.75rem 0.75rem 0 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        position: relative;
        transition: all 0.2s ease;
    }

    .tab-btn:hover {
        color: var(--text-primary);
        background: rgba(255, 255, 255, 0.03);
    }

    .tab-btn.active {
        color: var(--accent-color);
        font-weight: 800;
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -0.35rem;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--accent-color);
        border-radius: 4px 4px 0 0;
        box-shadow: 0 0 12px var(--accent-color);
    }

    .tab-badge {
        background: var(--bg-primary);
        color: var(--text-secondary);
        font-size: 0.75rem;
        padding: 0.15rem 0.64rem;
        border-radius: 1rem;
        border: 1px solid var(--border-color);
    }

    .tab-btn.active .tab-badge {
        background: var(--accent-color);
        color: #101924;
        border-color: var(--accent-color);
        font-weight: 800;
    }

    /* Tab Content Sections */
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    /* Cards */
    .cards-container {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .custom-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        border: 1px solid var(--border-color);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .custom-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }

    .card-summon {
        border-right: 5px solid #ef4444;
    }

    .card-meeting {
        border-right: 5px solid var(--accent-color);
    }

    .card-top-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .card-title-main {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .status-pill {
        padding: 0.4rem 1rem;
        border-radius: 2rem;
        font-size: 0.82rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .pill-red {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .pill-amber {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .pill-green {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .pill-blue {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .content-box-readable {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        padding: 1.1rem 1.25rem;
        border-radius: 0.9rem;
        font-size: 0.95rem;
        color: var(--text-primary);
        line-height: 1.7;
        margin-block: 1rem;
    }

    .admin-response-highlight {
        background: rgba(242, 242, 13, 0.08);
        border: 1px dashed rgba(242, 242, 13, 0.35);
        padding: 1rem;
        border-radius: 0.85rem;
        font-size: 0.92rem;
        color: var(--text-primary);
        margin-top: 1rem;
    }

    .scheduled-banner {
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.3);
        padding: 0.85rem 1.25rem;
        border-radius: 0.85rem;
        color: #10b981;
        font-weight: 800;
        font-size: 0.95rem;
        margin-top: 1rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .info-grid-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        padding-top: 0.85rem;
        border-top: 1px dashed var(--border-color);
        font-size: 0.88rem;
        color: var(--text-secondary);
    }

    .info-pill-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-pill-item i {
        color: var(--accent-color);
    }

    /* Modal */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(6px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal-overlay.active {
        display: flex;
        animation: fadeIn 0.2s ease-out;
    }

    .modal-box-content {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 1.5rem;
        max-width: 540px;
        width: 100%;
        padding: 2rem;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.5);
        position: relative;
        animation: slideUp 0.25s ease-out;
    }

    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-close-btn {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: all 0.2s;
    }
    .modal-close-btn:hover {
        background: #ef4444;
        color: #fff;
        border-color: #ef4444;
    }

    .form-group-custom {
        margin-bottom: 1.25rem;
    }

    .form-group-custom label {
        display: block;
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .form-control-custom {
        width: 100%;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.85rem 1.1rem;
        border-radius: 0.85rem;
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s;
    }
    .form-control-custom:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px rgba(242, 242, 13, 0.15);
    }

    .modal-actions {
        display: flex;
        gap: 0.8rem;
        margin-top: 1.75rem;
    }

    .btn-modal-submit {
        flex: 2;
        padding: 0.9rem;
        background: var(--accent-color);
        color: #101924;
        border: none;
        border-radius: 0.85rem;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: opacity 0.2s;
    }
    .btn-modal-submit:hover { opacity: 0.9; }

    .btn-modal-cancel {
        flex: 1;
        padding: 0.9rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        border-radius: 0.85rem;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        text-align: center;
    }
</style>
@endpush

@section('content')

@section('subtitle')
    متابعة الاستدعاءات الرسمية الواردة وطلبات المقابلات مع إدارة المعهد
@endsection

<!-- Top Header with Primary Plus Button -->
<div class="page-header-flex">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.3rem;">
            سجل المواعيد والاستدعاءات
        </h2>
        <p style="font-size: 0.9rem; color: var(--text-secondary);">
            يمكنك استعراض جميع الاستدعاءات الواردة أو تقديم طلب موعد مقابلة جديد مع إدارة المعهد.
        </p>
    </div>

    <!-- The Requested "+" Plus Button -->
    <button type="button" class="btn-create-meeting" onclick="openMeetingModal()">
        <i class="fa-solid fa-plus"></i>
        طلب موعد مقابلة جديد
    </button>
</div>

<!-- Stats Quick Summary -->
@php
    $summonsCount = isset($summons) ? $summons->count() : 0;
    $processedMeetingsCount = isset($meetings) ? $meetings->whereIn('status', ['approved', 'completed', 'rejected'])->count() : 0;
    $pendingMeetingsCount = isset($meetings) ? $meetings->where('status', 'pending')->count() : 0;
@endphp
<div class="stats-grid">
    <div class="stat-card-custom" onclick="switchTab('summons')" style="cursor: pointer;">
        <div class="stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <div style="font-size: 1.75rem; font-weight: 800; color: var(--text-primary); line-height: 1;">
                {{ $summonsCount }}
            </div>
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.3rem;">
                الاستدعاءات الرسمية الواردة
            </div>
        </div>
    </div>

    <div class="stat-card-custom" onclick="switchTab('meetings')" style="cursor: pointer;">
        <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div>
            <div style="font-size: 1.75rem; font-weight: 800; color: var(--text-primary); line-height: 1;">
                {{ $processedMeetingsCount }}
            </div>
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.3rem;">
                طلبات المعالجة والردود
            </div>
        </div>
    </div>

    <div class="stat-card-custom" onclick="switchTab('meetings')" style="cursor: pointer;">
        <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div>
            <div style="font-size: 1.75rem; font-weight: 800; color: var(--text-primary); line-height: 1;">
                {{ $pendingMeetingsCount }}
            </div>
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.3rem;">
                طلبات قيد المراجعة
            </div>
        </div>
    </div>
</div>

<!-- Tabs Switcher -->
<div class="tabs-header">
    <button type="button" class="tab-btn active" id="tab-btn-summons" onclick="switchTab('summons')">
        <i class="fa-solid fa-bullhorn" style="color: #ef4444;"></i>
        الاستدعاءات الرسمية
        <span class="tab-badge">{{ $summonsCount }}</span>
    </button>
    <button type="button" class="tab-btn" id="tab-btn-meetings" onclick="switchTab('meetings')">
        <i class="fa-solid fa-comments"></i>
        طلبات المواعيد والمقابلات
        <span class="tab-badge">{{ isset($meetings) ? $meetings->count() : 0 }}</span>
    </button>
</div>

<!-- TAB 1: SUMMONS (الاستدعاءات الرسمية) -->
<div class="tab-content active" id="tab-content-summons">
    @if(isset($summons) && $summons->isNotEmpty())
        <div class="cards-container">
            @foreach($summons as $sum)
                <div class="custom-card card-summon">
                    <div class="card-top-row">
                        <div class="card-title-main">
                            <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444; font-size:1.2rem;"></i>
                            {{ $sum->title }}
                        </div>
                        <span class="status-pill pill-red">
                            <i class="fa-solid fa-circle-dot"></i>
                            استدعاء رسمـي
                        </span>
                    </div>

                    <div class="content-box-readable">
                        {{ $sum->details_text }}
                    </div>

                    <div class="info-grid-row">
                        @if(!empty($sum->student_name))
                            <div class="info-pill-item">
                                <i class="fa-solid fa-user-graduate"></i>
                                الطالب المعني: <strong>{{ $sum->student_name }}</strong>
                            </div>
                        @endif
                        @if($sum->formatted_date)
                            <div class="info-pill-item">
                                <i class="fa-solid fa-calendar-day"></i>
                                تاريخ الحضور المطلوب: <strong>{{ \Carbon\Carbon::parse($sum->formatted_date)->format('Y-m-d') }}</strong>
                            </div>
                        @endif
                        @if(!empty($sum->sender_name))
                            <div class="info-pill-item">
                                <i class="fa-solid fa-user-shield"></i>
                                الجهة المُرْسِلة: {{ $sum->sender_name }}
                            </div>
                        @endif
                        <div class="info-pill-item">
                            <i class="fa-solid fa-clock"></i>
                            تاريخ الإرسال: {{ \Carbon\Carbon::parse($sum->created_at)->format('Y-m-d H:i') }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 3.5rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color); color: var(--text-secondary);">
            <div style="width:70px; height:70px; background:rgba(16, 185, 129, 0.15); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
                <i class="fa-solid fa-circle-check" style="font-size: 2.2rem; color:#10b981;"></i>
            </div>
            <h4 style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem;">لا توجد استدعاءات رسمية</h4>
            <p style="font-size: 0.92rem; color: var(--text-secondary);">لم يتم إرسال أي استدعاء رسمي لحسابك حتى الآن.</p>
        </div>
    @endif
</div>

<!-- TAB 2: MEETINGS (طلبات المواعيد) -->
<div class="tab-content" id="tab-content-meetings">
    @if(isset($meetings) && $meetings->isNotEmpty())
        <div class="cards-container">
            @foreach($meetings as $m)
                <div class="custom-card card-meeting">
                    <div class="card-top-row">
                        <div class="card-title-main">
                            <i class="fa-solid fa-calendar-check" style="color:var(--accent-color); font-size:1.2rem;"></i>
                            {{ $m->subject }}
                        </div>
                        @php
                            $statusMap = [
                                'pending'   => ['label' => 'قيد المراجعة والانتظار', 'class' => 'pill-amber', 'icon' => 'fa-clock'],
                                'approved'  => ['label' => 'تمت الموافقة وتحديد الموعد', 'class' => 'pill-green', 'icon' => 'fa-circle-check'],
                                'rejected'  => ['label' => 'تم الاعتذار عن الموعد', 'class' => 'pill-red', 'icon' => 'fa-circle-xmark'],
                                'completed' => ['label' => 'تمت المقابلة بنجاح', 'class' => 'pill-blue', 'icon' => 'fa-check-double'],
                            ];
                            $st = $statusMap[$m->status] ?? ['label' => $m->status, 'class' => 'pill-amber', 'icon' => 'fa-info-circle'];
                        @endphp
                        <span class="status-pill {{ $st['class'] }}">
                            <i class="fa-solid {{ $st['icon'] }}"></i>
                            {{ $st['label'] }}
                        </span>
                    </div>

                    <div class="content-box-readable">
                        <strong style="color:var(--accent-color);">سبب الطلب والتفاصيل:</strong><br>
                        {{ $m->reason }}
                    </div>

                    @if($m->scheduled_at && in_array($m->status, ['approved', 'completed']))
                        <div class="scheduled-banner">
                            <i class="fa-solid fa-clock-desk" style="font-size:1.2rem;"></i>
                            تاريخ ووقت المقابلة المعتمد: <strong>{{ \Carbon\Carbon::parse($m->scheduled_at)->format('Y-m-d  الساعة h:i A') }}</strong>
                        </div>
                    @endif

                    @if($m->admin_response)
                        <div class="admin-response-highlight">
                            <i class="fa-solid fa-reply-all" style="color:var(--accent-color); margin-left:0.5rem;"></i>
                            <strong>ملاحظة/رد الإدارة:</strong> {{ $m->admin_response }}
                        </div>
                    @endif

                    <div class="info-grid-row" style="margin-top: 1rem;">
                        @if(!empty($m->student_name))
                            <div class="info-pill-item">
                                <i class="fa-solid fa-child"></i>
                                بخصوص الابن: <strong>{{ $m->student_name }}</strong>
                            </div>
                        @endif
                        @if($m->preferred_date)
                            <div class="info-pill-item">
                                <i class="fa-solid fa-calendar-day"></i>
                                التاريخ المقترح من ولي الأمر: {{ \Carbon\Carbon::parse($m->preferred_date)->format('Y-m-d') }}
                            </div>
                        @endif
                        <div class="info-pill-item">
                            <i class="fa-solid fa-clock"></i>
                            تاريخ تقديم الطلب: {{ \Carbon\Carbon::parse($m->created_at)->format('Y-m-d H:i') }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 3.5rem 2rem; background: var(--bg-secondary); border-radius: 1.5rem; border: 1px dashed var(--border-color); color: var(--text-secondary);">
            <div style="width:70px; height:70px; background:rgba(242, 242, 13, 0.15); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;">
                <i class="fa-solid fa-folder-open" style="font-size: 2.2rem; color:var(--accent-color);"></i>
            </div>
            <h4 style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem;">لا توجد طلبات مواعيد مسجلة</h4>
            <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 1.5rem;">يمكنك الضغط على زر (+ طلب موعد مقابلة جديد) لتقديم طلب مقابلة للإدارة.</p>
            <button type="button" class="btn-create-meeting" onclick="openMeetingModal()" style="margin: 0 auto;">
                <i class="fa-solid fa-plus"></i>
                طلب موعد مقابلة جديد
            </button>
        </div>
    @endif
</div>

<!-- MODAL: REQUEST NEW MEETING FORM (النافذة المنبثقة للطلب) -->
<div class="modal-overlay" id="meetingModal">
    <div class="modal-box-content">
        <div class="modal-header-flex">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <div style="width:44px; height:44px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#101924; font-size:1.2rem; font-weight:800;">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary);">طلب موعد مقابلة جديد</h3>
                    <p style="font-size: 0.83rem; color: var(--text-secondary);">سيتم إرسال الطلب لإدارة المعهد والرد عليك بأقرب وقت</p>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeMeetingModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('parent.appointments.request') }}" method="POST">
            @csrf
            <div class="form-group-custom">
                <label>الابن المعني بالمقابلة <span style="color:#ef4444;">*</span></label>
                <select name="student_id" class="form-control-custom" required>
                    @if(isset($parent_children) && $parent_children->isNotEmpty())
                        @foreach($parent_children as $child)
                            <option value="{{ $child->student_id }}" {{ (isset($selected_child_id) && $selected_child_id == $child->student_id) ? 'selected' : '' }}>
                                {{ $child->full_name }}
                            </option>
                        @endforeach
                    @else
                        <option value="">لا يوجد أبناء مسجلين</option>
                    @endif
                </select>
            </div>

            <div class="form-group-custom">
                <label for="meeting-subject">موضوع المقابلة <span style="color:#ef4444;">*</span></label>
                <input type="text" name="subject" id="meeting-subject" class="form-control-custom" placeholder="مثال: استفسار عن المستوى الأكاديمي أو السلوكي" required>
            </div>

            <div class="form-group-custom">
                <label for="meeting-date">التاريخ المقترح (اختياري)</label>
                <input type="date" name="preferred_date" id="meeting-date" class="form-control-custom" min="{{ date('Y-m-d') }}">
            </div>

            <div class="form-group-custom">
                <label for="meeting-reason">السبب والتفاصيل <span style="color:#ef4444;">*</span></label>
                <textarea name="reason" id="meeting-reason" class="form-control-custom" rows="4" placeholder="اكتب تفاصيل الطلب والسبب وراء تحديد موعد المقابلة..." required></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeMeetingModal()">إلغاء</button>
                <button type="submit" class="btn-modal-submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    إرسال الطلب الآن
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    document.getElementById('tab-btn-' + tabName).classList.add('active');
    document.getElementById('tab-content-' + tabName).classList.add('active');
}

function openMeetingModal() {
    document.getElementById('meetingModal').classList.add('active');
}

function closeMeetingModal() {
    document.getElementById('meetingModal').classList.remove('active');
}

// Close modal on backdrop click
document.getElementById('meetingModal').addEventListener('click', function(e) {
    if (e.target === this) closeMeetingModal();
});
</script>
@endpush
