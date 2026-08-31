@extends('layouts.student')
@section('title', 'الخدمات الطلابية')
@section('subtitle', 'تقديم ومتابعة كافة الطلبات الإلكترونية كالتطبيق الجوال')

@push('styles')
<style>
    /* Section Titles */
    .section-head-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Grid of Service Cards matching Flutter App */
    .services-flutter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2.5rem;
    }

    .flutter-service-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.4rem 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        cursor: pointer;
        transition: all 0.25s ease;
        display: flex;
        align-items: flex-start;
        gap: 1.1rem;
        position: relative;
        overflow: hidden;
    }
    .flutter-service-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: var(--accent-color);
        opacity: 0;
        transition: opacity 0.25s;
    }
    .flutter-service-card:hover {
        border-color: var(--accent-color);
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.15);
    }
    .flutter-service-card:hover::after {
        opacity: 1;
    }

    .service-icon-box {
        width: 52px; height: 52px;
        border-radius: 1.1rem;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0;
        color: var(--accent-color);
    }

    .service-card-title {
        font-size: 1rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 0.35rem;
    }
    .service-card-sub {
        font-size: 0.82rem;
        color: var(--text-secondary);
        line-height: 1.45;
    }

    .service-card-action {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.75rem;
        font-size: 0.8rem;
        font-weight: 800;
        color: var(--accent-color);
    }

    /* Filter Tabs */
    .filter-tabs-wrapper {
        display: flex;
        gap: 0.6rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .filter-tab-btn {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        padding: 0.55rem 1.1rem;
        border-radius: 2rem;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .filter-tab-btn:hover, .filter-tab-btn.active {
        background: var(--accent-color);
        color: #1a1a1a;
        border-color: var(--accent-color);
        font-weight: 800;
    }

    /* Requests List */
    .request-record-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.35rem 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        transition: all 0.2s;
    }
    .request-record-card:hover {
        border-color: var(--accent-color);
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.85rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        font-weight: 800;
    }
    .pill-pending  { background: rgba(250, 204, 21, 0.15); color: #ca8a04; border: 1px solid rgba(250, 204, 21, 0.3); }
    .pill-approved { background: rgba(22, 163, 74, 0.15);  color: #16a34a; border: 1px solid rgba(22, 163, 74, 0.3); }
    .pill-rejected { background: rgba(220, 38, 38, 0.15);  color: #dc2626; border: 1px solid rgba(220, 38, 38, 0.3); }

    .type-badge {
        background: var(--bg-primary);
        color: var(--text-primary);
        padding: 0.3rem 0.75rem;
        border-radius: 0.6rem;
        font-size: 0.82rem;
        font-weight: 700;
        border: 1px solid var(--border-color);
    }

    .notes-box {
        background: var(--bg-primary);
        border-radius: 0.875rem;
        padding: 0.85rem 1.1rem;
        margin-top: 1rem;
        border-right: 3px solid var(--accent-color);
        font-size: 0.88rem;
    }

    /* Modal Overlay & Card */
    .modal-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        width: 92%; max-width: 580px;
        padding: 2rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        border: 1px solid var(--border-color);
        animation: modalSlideUp 0.25s ease;
    }
    @keyframes modalSlideUp {
        from { transform: translateY(20px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: var(--text-primary); }
    .form-control {
        width: 100%;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.75rem 1rem;
        border-radius: 0.85rem;
        font-family: inherit;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s;
    }
    .form-control:focus { border-color: var(--accent-color); }

    .selected-type-badge-box {
        background: var(--bg-primary);
        padding: 0.85rem 1.1rem;
        border-radius: 0.85rem;
        border: 1px solid var(--accent-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }
</style>
@endpush

@section('content')

    <!-- Header Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.4rem; font-weight: 900; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-hand-holding-hand" style="color: var(--accent-color);"></i> الخدمات والطلبات الإلكترونية
            </h2>
            <p style="color: var(--text-secondary); font-size: 0.88rem; margin-top: 0.25rem;">تقديم ومتابعة كافة الطلبات الطلابية والإدارية كالتطبيق الجوال</p>
        </div>

        <button onclick="openNewRequestModal('general')" style="background: var(--accent-color); color: #1a1a1a; border: none; padding: 0.75rem 1.4rem; border-radius: 0.85rem; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-family: inherit; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.2s;">
            <i class="fa-solid fa-plus-circle"></i> تقديم طلب جديد
        </button>
    </div>

    <!-- Section 1: Flutter Services Cards Grid -->
    <div class="section-head-title">
        <i class="fa-solid fa-layer-group" style="color: var(--accent-color);"></i> قائمة الخدمات الطلابية المتاحة
    </div>

    <div class="services-flutter-grid">
        
        <!-- Service 1: Mercy Petition -->
        <div class="flutter-service-card" onclick="openNewRequestModal('mercy')">
            <div class="service-icon-box" style="color: #f59e0b;">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div>
                <div class="service-card-title">تقديم طلب استرحام</div>
                <div class="service-card-sub">تقديم طلب عذر طبي، إعادة اختبار، أو مراجعة درجات إلى إدارة المعهد والشؤون</div>
                <div class="service-card-action">تقديم الطلب الآن <i class="fa-solid fa-arrow-left"></i></div>
            </div>
        </div>

        <!-- Service 2: Documents -->
        <div class="flutter-service-card" onclick="openNewRequestModal('document')">
            <div class="service-icon-box" style="color: #3b82f6;">
                <i class="fa-solid fa-file-contract"></i>
            </div>
            <div>
                <div class="service-card-title">استخراج الوثائق الطلابية</div>
                <div class="service-card-sub">استخراج شهادة قيد، كشف علامات ورقي مصدق، ومستندات أخرى</div>
                <div class="service-card-action">طلب وثيقة رسمية <i class="fa-solid fa-arrow-left"></i></div>
            </div>
        </div>

        <!-- Service 3: Makeup Exam -->
        <div class="flutter-service-card" onclick="openNewRequestModal('makeup')">
            <div class="service-icon-box" style="color: #ef4444;">
                <i class="fa-solid fa-file-pen"></i>
            </div>
            <div>
                <div class="service-card-title">تقديم طلب امتحان إكمال</div>
                <div class="service-card-sub">تقديم طلب لإجراء امتحان إكمال للمواد التي لم يتم النجاح بها بعذر مقبول</div>
                <div class="service-card-action">طلب امتحان إكمال <i class="fa-solid fa-arrow-left"></i></div>
            </div>
        </div>

        <!-- Service 4: Device Reset -->
        <div class="flutter-service-card" onclick="openNewRequestModal('device_reset')">
            <div class="service-icon-box" style="color: #8b5cf6;">
                <i class="fa-solid fa-mobile-screen-button"></i>
            </div>
            <div>
                <div class="service-card-title">تقديم طلب فك قفل الجهاز</div>
                <div class="service-card-sub">تقديم طلب لشؤون الطلاب لفك قفل الحساب عن الجهاز القديم لربطه بجهاز جديد</div>
                <div class="service-card-action">طلب فك قفل الجهاز <i class="fa-solid fa-arrow-left"></i></div>
            </div>
        </div>

        <!-- Service 5: Face Photo -->
        <div class="flutter-service-card" onclick="openNewRequestModal('face_photo')">
            <div class="service-icon-box" style="color: #10b981;">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div>
                <div class="service-card-title">طلب تحديث صورة بصمة الوجه</div>
                <div class="service-card-sub">تقديم طلب لتغيير صورة بصمة الوجه للتحقق عند تسجيل الحضور بالجوال</div>
                <div class="service-card-action">تحديث صورة البصمة <i class="fa-solid fa-arrow-left"></i></div>
            </div>
        </div>

    </div>

    <!-- Section 2: Requests Tracker with Filter Tabs -->
    <div class="section-head-title" style="margin-top: 1rem;">
        <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-color);"></i> سجل الطلبات المرفوعة ومتابعة حالتها
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs-wrapper">
        <button class="filter-tab-btn active" onclick="filterRequests('all', this)">
            <i class="fa-solid fa-globe"></i> كافة الطلبات ({{ $stats['total'] }})
        </button>
        <button class="filter-tab-btn" onclick="filterRequests('mercy', this)">
            <i class="fa-solid fa-scale-balanced"></i> طلبات الاسترحام
        </button>
        <button class="filter-tab-btn" onclick="filterRequests('document', this)">
            <i class="fa-solid fa-file-contract"></i> الوثائق الطلابية
        </button>
        <button class="filter-tab-btn" onclick="filterRequests('makeup', this)">
            <i class="fa-solid fa-file-pen"></i> امتحانات الإكمال
        </button>
        <button class="filter-tab-btn" onclick="filterRequests('device_reset', this)">
            <i class="fa-solid fa-mobile-screen"></i> فك قفل الجهاز
        </button>
        <button class="filter-tab-btn" onclick="filterRequests('face_photo', this)">
            <i class="fa-solid fa-user-gear"></i> بصمة الوجه
        </button>
    </div>

    <!-- Requests Records Container -->
    <div id="requests-list-container">
        @forelse($requests as $req)
            @php
                $typeNames = [
                    'mercy'        => ['name' => 'طلب استرحام / إعادة نظر', 'icon' => 'fa-scale-balanced', 'color' => '#f59e0b'],
                    'document'     => ['name' => 'طلب وثيقة / كشف علامات', 'icon' => 'fa-file-contract',   'color' => '#3b82f6'],
                    'makeup'       => ['name' => 'طلب امتحان إكمال',        'icon' => 'fa-file-pen',        'color' => '#ef4444'],
                    'device_reset' => ['name' => 'طلب فك قفل الجهاز',       'icon' => 'fa-mobile-screen',   'color' => '#8b5cf6'],
                    'face_photo'   => ['name' => 'طلب صورة بصمة الوجه',     'icon' => 'fa-user-gear',       'color' => '#10b981'],
                    'general'      => ['name' => 'طلب عام / استفسار',       'icon' => 'fa-comment-dots',    'color' => '#ca8a04'],
                ];
                $typeInfo = $typeNames[$req->type] ?? ['name' => 'طلب خدمة طلابية', 'icon' => 'fa-paper-plane', 'color' => '#ca8a04'];

                // Status pill mapping
                $statusTitle = 'قيد المراجعة';
                $pillClass = 'pill-pending';
                $finalDecision = null;

                if ($req->status === 'completed' || $req->admin_decision !== null || ($req->type === 'device_reset' && $req->affairs_decision !== null)) {
                    $isApproved = ($req->admin_decision === 'approved') || ($req->type === 'device_reset' && $req->affairs_decision === 'approved');
                    if ($isApproved) {
                        $statusTitle = 'تم القبول النهائي';
                        $pillClass = 'pill-approved';
                        $finalDecision = 'approved';
                    } else {
                        $statusTitle = 'تم الرفض النهائي';
                        $pillClass = 'pill-rejected';
                        $finalDecision = 'rejected';
                    }
                } else {
                    $statusTitle = match($req->status) {
                        'pending_affairs' => 'قيد مراجعة الشؤون الطلابية',
                        'pending_hod'     => 'قيد مراجعة رئيس القسم',
                        'pending_admin'   => 'قيد مراجعة إدارة المعهد',
                        'approved'        => 'مقبول',
                        'rejected'        => 'مرفوض',
                        'completed'       => 'مكتمل',
                        default           => 'قيد المعالجة',
                    };
                    $pillClass = 'pill-pending';
                }
            @endphp

            <div class="request-record-card" data-type="{{ $req->type }}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                        <div style="width: 44px; height: 44px; border-radius: 0.9rem; background: var(--bg-primary); color: {{ $typeInfo['color'] }}; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                            <i class="fa-solid {{ $typeInfo['icon'] }}"></i>
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                                <span class="type-badge">{{ $typeInfo['name'] }}</span>
                                <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 700;">#{{ $req->id }}</span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                <i class="fa-regular fa-clock"></i> تم تقديم الطلب بتاريخ: {{ \Carbon\Carbon::parse($req->created_at)->format('Y-m-d H:i') }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="status-pill {{ $pillClass }}">
                            @if($finalDecision === 'approved')
                                <i class="fa-solid fa-circle-check"></i> تم القبول
                            @elseif($finalDecision === 'rejected')
                                <i class="fa-solid fa-circle-xmark"></i> تم الرفض
                            @else
                                <i class="fa-solid fa-hourglass-half"></i> {{ $statusTitle }}
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Request Details -->
                <div style="margin-top: 1rem; color: var(--text-primary); font-size: 0.92rem; line-height: 1.6; background: var(--bg-primary); padding: 0.85rem 1.1rem; border-radius: 0.85rem; border: 1px solid var(--border-color);">
                    <strong>تفاصيل الطلب والأسباب:</strong> {{ $req->formatted_details }}
                </div>

                <!-- Decisions & Notes Section -->
                @if($req->admin_notes || $req->affairs_notes || $req->hod_notes)
                    <div class="notes-box">
                        <div style="font-weight: 800; color: var(--text-primary); margin-bottom: 0.25rem;">
                            <i class="fa-solid fa-comment-dots" style="color: var(--accent-color);"></i> رد وملاحظات الإدارة والشؤون:
                        </div>
                        @if($req->admin_notes)
                            <div><strong>قرار الإدارة:</strong> {{ $req->admin_notes }}</div>
                        @endif
                        @if($req->affairs_notes)
                            <div><strong>ملاحظات الشؤون:</strong> {{ $req->affairs_notes }}</div>
                        @endif
                        @if($req->hod_notes)
                            <div><strong>ملاحظات رئيس القسم:</strong> {{ $req->hod_notes }}</div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div style="text-align: center; padding: 3.5rem; background: var(--bg-secondary); border-radius: 1.5rem; color: var(--text-secondary); border: 1px dashed var(--border-color);">
                <i class="fa-solid fa-hand-holding-hand" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; color: var(--accent-color);"></i>
                <p style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">لا توجد طلبات سابقة في هذا التصنيف</p>
                <p style="font-size: 0.88rem; margin-top: 0.3rem;">يمكنك تقديم طلب جديد عبر الضغط على إحدى بطاقات الخدمات المتاحة في الأعلى.</p>
            </div>
        @endforelse
    </div>

    <!-- Submit Request Modal -->
    <div id="newRequestModal" class="modal-overlay">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--border-color);">
                <h3 style="font-weight: 800; font-size: 1.15rem; margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-plus-circle" style="color: var(--accent-color);"></i> تقديم طلب خدمة طلابية جديد
                </h3>
                <button onclick="closeNewRequestModal()" style="background: none; border: none; font-size: 1.4rem; color: var(--text-secondary); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('student.services.store') }}" method="POST">
                @csrf

                <!-- Selected Badge Box (Shown when opened from a specific service card) -->
                <div id="serviceTypeSelectedBox" class="selected-type-badge-box" style="display: none;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span style="font-size: 0.82rem; color: var(--text-secondary); font-weight: 700;">الخدمة المحددة:</span>
                        <span id="selectedServiceTitleText" style="font-weight: 800; color: var(--text-primary); font-size: 0.95rem;"></span>
                    </div>
                    <button type="button" onclick="showSelectDropdown()" style="background: none; border: none; color: var(--accent-color); font-weight: 800; font-size: 0.8rem; cursor: pointer; text-decoration: underline; font-family: inherit;">تغيير نوع الخدمة</button>
                </div>

                <!-- Dropdown Select Menu (Hidden when opened from a specific card, shown if user changes or opens from general button) -->
                <div id="serviceTypeSelectGroup" class="form-group">
                    <label class="form-label">نوع الخدمة المطلوب:</label>
                    <select name="type" id="modalServiceTypeSelect" class="form-control" onchange="checkFailedSubjectVisibility(this.value)" required>
                        <option value="mercy">⚖️ تقديم طلب استرحام (عذر طبي / إعادة اختبار / مراجعة)</option>
                        <option value="document">📄 استخراج الوثائق الطلابية (شهادة قيد / كشف علامات)</option>
                        <option value="makeup">📝 تقديم طلب امتحان إكمال (للمواد المتبقية / الراسب بها)</option>
                        <option value="device_reset">📱 تقديم طلب فك قفل الجهاز القديم</option>
                        <option value="face_photo">👤 طلب تحديث صورة بصمة الوجه للتحقق</option>
                        <option value="general">💬 طلب عام / استفسار موجّه للإدارة</option>
                    </select>
                </div>

                <!-- Failed Subject Selection (Shown specifically for Makeup Exam requests) -->
                <div id="failedSubjectSelectGroup" class="form-group" style="display: none; background: rgba(239, 68, 68, 0.05); padding: 1rem; border-radius: 0.85rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                    <label class="form-label" style="color: #ef4444; font-weight: 800; display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i> اختر المادة الراسب بها لتقديم طلب الإكمال:
                    </label>
                    <select name="subject_name" id="failedSubjectSelect" class="form-control" style="border-color: #fca5a5;">
                        @if(count($failedCourses) > 0)
                            @foreach($failedCourses as $fc)
                                <option value="{{ $fc['title'] }}">{{ $fc['title'] }} (النتيجة الحالية: {{ $fc['status'] }} - {{ $fc['total_score'] ?? 'بدون علامة' }} درجة)</option>
                            @endforeach
                        @else
                            @foreach($enrolledCourses as $ec)
                                <option value="{{ $ec->title }}">{{ $ec->title }} (السنة {{ $ec->year }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">تفاصيل الطلب والأسباب بالتفصيل:</label>
                    <textarea name="details" class="form-control" rows="5" placeholder="اكتب هنا تفاصيل طلبك والأسباب الموجبة بالتفصيل..." required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" onclick="closeNewRequestModal()" style="background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.7rem 1.25rem; border-radius: 0.8rem; font-weight: 700; cursor: pointer; font-family: inherit;">إلغاء</button>
                    <button type="submit" style="background: var(--accent-color); color: #1a1a1a; border: none; padding: 0.7rem 1.5rem; border-radius: 0.8rem; font-weight: 800; cursor: pointer; font-family: inherit;">إرسال الطلب الآن</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const serviceMap = {
        'mercy': '⚖️ تقديم طلب استرحام (عذر طبي / إعادة اختبار / مراجعة)',
        'document': '📄 استخراج الوثائق الطلابية (شهادة قيد / كشف علامات)',
        'makeup': '📝 تقديم طلب امتحان إكمال (للمواد المتبقية / الراسب بها)',
        'device_reset': '📱 تقديم طلب فك قفل الجهاز القديم',
        'face_photo': '👤 طلب تحديث صورة بصمة الوجه للتحقق',
        'general': '💬 طلب عام / استفسار موجّه للإدارة'
    };

    function checkFailedSubjectVisibility(selectedType) {
        const failedGroup = document.getElementById('failedSubjectSelectGroup');
        if (failedGroup) {
            if (selectedType === 'makeup') {
                failedGroup.style.display = 'block';
            } else {
                failedGroup.style.display = 'none';
            }
        }
    }

    function openNewRequestModal(defaultType) {
        const select = document.getElementById('modalServiceTypeSelect');
        const selectedBox = document.getElementById('serviceTypeSelectedBox');
        const selectGroup = document.getElementById('serviceTypeSelectGroup');
        const titleText = document.getElementById('selectedServiceTitleText');

        if (select && defaultType) {
            select.value = defaultType;
            if (defaultType !== 'general' && serviceMap[defaultType]) {
                titleText.textContent = serviceMap[defaultType];
                selectedBox.style.display = 'flex';
                selectGroup.style.display = 'none';
            } else {
                selectedBox.style.display = 'none';
                selectGroup.style.display = 'block';
            }
        } else {
            selectedBox.style.display = 'none';
            selectGroup.style.display = 'block';
        }
        checkFailedSubjectVisibility(defaultType || (select ? select.value : ''));
        document.getElementById('newRequestModal').classList.add('active');
    }

    function showSelectDropdown() {
        document.getElementById('serviceTypeSelectedBox').style.display = 'none';
        document.getElementById('serviceTypeSelectGroup').style.display = 'block';
    }

    function closeNewRequestModal() {
        document.getElementById('newRequestModal').classList.remove('active');
    }

    function filterRequests(type, btn) {
        document.querySelectorAll('.filter-tab-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const cards = document.querySelectorAll('.request-record-card');
        cards.forEach(card => {
            const cardType = card.getAttribute('data-type');
            if (type === 'all' || cardType === type) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        let tabParam = urlParams.get('tab') || urlParams.get('type');
        if (tabParam) {
            if (tabParam === 'documents') tabParam = 'document';
            const targetBtn = document.querySelector(`.filter-tab-btn[onclick*="'${tabParam}'"]`) || 
                              document.querySelector(`.filter-tab-btn[onclick*="${tabParam}"]`);
            if (targetBtn) {
                filterRequests(tabParam, targetBtn);
            }
        }
    });
</script>
@endpush
