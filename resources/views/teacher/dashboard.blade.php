@extends('layouts.teacher')
@section('title', 'الرئيسية')
@section('subtitle', 'مرحباً، ' . (auth()->user()->full_name ?? 'أستاذ'))

@push('styles')
<style>
    /* ===== Stat Cards ===== */
    .stat-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: 1.25rem;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--accent-color);
        opacity: 0;
        transition: opacity 0.2s;
        border-radius: inherit;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        border-color: var(--accent-color);
    }
    .stat-card-plain { cursor: default; }
    .stat-card-plain:hover { transform: none; border-color: transparent; }

    .stat-icon {
        width: 56px; height: 56px;
        border-radius: 1rem;
        background: var(--accent-color);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: #1a1a1a; flex-shrink: 0;
    }
    .stat-value { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-label { color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; }
    .stat-hint  { font-size: 0.75rem; color: var(--accent-color); margin-top: 0.3rem; font-weight: 600; }

    /* ===== Section title ===== */
    .section-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; }

    /* ===== Notif & Schedule ===== */
    .notif-card { background: var(--bg-secondary); border-radius: 1rem; padding: 1.25rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; display: flex; gap: 1rem; align-items: flex-start; }
    .notif-dot  { width: 10px; height: 10px; border-radius: 50%; background: var(--accent-color); flex-shrink: 0; margin-top: 5px; }
    .schedule-row { background: var(--bg-secondary); border-radius: 1rem; padding: 1rem 1.25rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow); border-right: 4px solid var(--accent-color); }

    /* ===== Announcement cards ===== */
    .announce-card {
        border-radius: 1.25rem; overflow: hidden;
        background-color: var(--bg-secondary);
        box-shadow: var(--shadow); margin-bottom: 1.25rem;
        transition: transform 0.2s;
    }
    .announce-card:hover { transform: translateY(-2px); }
    .announce-image-area {
        width: 100%; height: 160px;
        background-color: #fce30020;
        display: flex; align-items: center; justify-content: center;
        color: #ca8a04; position: relative;
    }
    .announce-image-area img { width: 100%; height: 160px; object-fit: cover; }
    .announce-badge {
        position: absolute; top: 0.75rem; right: 0.75rem;
        background-color: var(--accent-color); color: #1a1a1a;
        font-size: 0.78rem; font-weight: 700;
        padding: 0.2rem 0.65rem; border-radius: 2rem;
    }
    .announce-body { padding: 1.25rem 1.25rem 1rem; }
    .announce-meta { display: flex; justify-content: space-between; color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 0.5rem; }
    .announce-title { font-size: 1rem; font-weight: 800; margin-bottom: 0.35rem; color: var(--text-primary); }
    .announce-excerpt { color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6; }

    /* ===== Modals ===== */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.5); z-index: 1000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        width: 94%; max-width: 560px;
        max-height: 85vh; overflow-y: auto;
        padding: 2rem;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        animation: slideUp 0.25s ease;
    }
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .modal-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1.5rem; padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .modal-close {
        background: none; border: none; font-size: 1.3rem;
        color: var(--text-secondary); cursor: pointer;
    }
    .modal-close:hover { color: var(--text-primary); }

    /* Course list item */
    .list-item {
        background: var(--bg-primary);
        border-radius: 0.875rem;
        padding: 1rem 1.25rem;
        margin-bottom: 0.6rem;
        display: flex; align-items: center; gap: 1rem;
        border-right: 3px solid var(--accent-color);
    }
    .list-icon {
        width: 40px; height: 40px; border-radius: 0.75rem;
        background: var(--accent-color);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; color: #1a1a1a; flex-shrink: 0;
    }
    .list-item-title { font-weight: 700; font-size: 0.95rem; }
    .list-item-sub   { color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.15rem; }

    /* Assignment status badges */
    .badge-graded  { background: hsl(120,70%,90%); color: hsl(120,50%,30%); }
    .badge-pending { background: hsl(30,70%,90%);  color: hsl(30,50%,30%);  }
    .badge-active  { background: var(--accent-color); color: #1a1a1a; }
    .status-badge  { padding: 0.2rem 0.6rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; }
</style>
@endpush

@section('content')

{{-- ===== Stats (3 cards) ===== --}}
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 2rem;">

    {{-- Card 1: المواد الدراسية - clickable --}}
    <div class="stat-card" onclick="openModal('courses-modal')" title="اضغط لعرض قائمة المواد">
        <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
        <div>
            <div class="stat-value">{{ $courses->count() }}</div>
            <div class="stat-label">المواد الدراسية</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </div>

    {{-- Card 2: الواجبات - clickable --}}
    <div class="stat-card" onclick="openModal('assignments-modal')" title="اضغط لعرض قائمة الواجبات">
        <div class="stat-icon"><i class="fa-solid fa-file-pen"></i></div>
        <div>
            <div class="stat-value">{{ $recentAssignments->count() }}</div>
            <div class="stat-label">الواجبات النشطة</div>
            <div class="stat-hint"><i class="fa-solid fa-arrow-left"></i> اضغط للعرض</div>
        </div>
    </div>

    {{-- Card 3: حصص اليوم - plain --}}
    <div class="stat-card stat-card-plain">
        <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
        <div>
            <div class="stat-value">{{ $todayCount }}</div>
            <div class="stat-label">حصص اليوم</div>
        </div>
    </div>

</div>

{{-- ===== Announcements ===== --}}
<div style="margin-bottom: 2rem;">
    <p class="section-title">
        <i class="fa-solid fa-bullhorn" style="color: var(--accent-color);"></i>
        آخر الأخبار والإعلانات
    </p>

    @forelse($announcements as $index => $ann)
        @if($index === 0)
            <!-- Large Card -->
            <div class="announce-card-large">
                <div class="announce-large-header">
                    @if($ann->image_path ?? false)
                        <img src="{{ asset('storage/' . $ann->image_path) }}" class="announce-large-img" alt="صورة الإعلان">
                    @else
                        <div class="announce-large-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    @endif
                    <span class="announce-badge">{{ $ann->category ?? 'إعلان هام' }}</span>
                </div>
                <div class="announce-large-body">
                    <div class="announce-meta">
                        <i class="fa-regular fa-clock"></i>
                        <span>{{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
                        <span style="margin: 0 0.25rem;">·</span>
                        <span>موجه إلى: {{ (($ann->target_audience ?? '') === 'all' || ($ann->type ?? '') === 'general') ? 'الجميع' : ($ann->target_audience ?? 'المعلمون') }}</span>
                    </div>
                    <h4 class="announce-title">{{ $ann->title }}</h4>
                    <p class="announce-text">{{ Str::limit($ann->content, 150) }}</p>
                </div>
            </div>
        @else
            <!-- Compact Card -->
            <div class="announce-card-compact">
                <div class="announce-compact-icon">
                    @if($ann->image_path ?? false)
                        <img src="{{ asset('storage/' . $ann->image_path) }}" alt="صورة">
                    @else
                        <i class="fa-solid fa-file-lines"></i>
                    @endif
                </div>
                <div class="announce-compact-body">
                    <span class="announce-tag">{{ $ann->category ?? 'إعلان' }}</span>
                    <h4 class="announce-compact-title">{{ $ann->title }}</h4>
                    <div class="announce-meta" style="margin-bottom: 0;">
                        <span>{{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        @endif
    @empty
        <div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
            <i class="fa-solid fa-bullhorn" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; color: var(--accent-color); opacity: 0.5;"></i>
            لا توجد إعلانات حالياً
        </div>
    @endforelse
</div>


@if(isset($advisorCourse) && $advisorCourse)
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; margin-top: 3rem;">
        <h3 style="font-weight: 800; font-size: 1.25rem;">
            <i class="fa-solid fa-star" style="color: #ca8a04; margin-left: 0.5rem;"></i>
            أدوات مربي الدورة: {{ $advisorCourse->title }}
        </h3>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <button onclick="openModal('advisor-attendance-modal')" style="background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 1rem; padding: 1.5rem; text-align: right; cursor: pointer; transition: all 0.2s;">
            <i class="fa-solid fa-clipboard-user" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 1rem;"></i>
            <h4 style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary);">الحضور اليومي للقاعة</h4>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem;">تسجيل حضور طلاب هذه القاعة فقط</p>
        </button>
        
        <button onclick="openModal('advisor-report-modal')" style="background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 1rem; padding: 1.5rem; text-align: right; cursor: pointer; transition: all 0.2s;">
            <i class="fa-solid fa-file-signature" style="font-size: 2rem; color: #ef4444; margin-bottom: 1rem;"></i>
            <h4 style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary);">كتابة تقرير سلوكي</h4>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem;">إرسال تقرير سلوكي عن طالب لرئيس القسم</p>
        </button>
    </div>

    @if($parentReportRequests->count() > 0)
    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 1rem; padding: 1.5rem; margin-bottom: 2rem;">
        <h4 style="color: #991b1b; font-weight: 800; font-size: 1.1rem; margin-bottom: 1rem;">
            <i class="fa-solid fa-bell"></i> طلبات تقارير سلوكية من أولياء الأمور ({{ $parentReportRequests->count() }})
        </h4>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($parentReportRequests as $req)
            <div style="background: white; padding: 1rem; border-radius: 0.75rem; border: 1px solid #fca5a5; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: var(--text-primary);">للطالب: {{ $req->student_name }}</strong>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem;">بطلب من: {{ $req->parent_name }} | {{ \Carbon\Carbon::parse($req->created_at)->diffForHumans() }}</p>
                    <small style="color: #ef4444; display: block; margin-top: 0.25rem;">{{ $req->notes }}</small>
                </div>
                <button onclick="openReportModalWithRequest({{ $req->student_id }}, {{ $req->request_id }})" class="btn" style="background-color: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    استجابة للطلب وكتابة التقرير
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Advisor Attendance Modal -->
    <div id="advisor-attendance-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="font-weight: 800; font-size: 1.1rem;">
                    <i class="fa-solid fa-clipboard-user" style="color: var(--accent-color);"></i>
                    الحضور اليومي ({{ $advisorCourse->title }})
                </h3>
                <button class="modal-close" onclick="closeModal('advisor-attendance-modal')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <form action="{{ route('teacher.advisor.attendance') }}" method="POST">
                    @csrf
                    <input type="hidden" name="course_id" value="{{ $advisorCourse->course_id }}">
                    <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                    
                    <table style="width: 100%; text-align: right; border-collapse: collapse; margin-bottom: 1rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 0.75rem;">الطالب</th>
                                <th style="padding: 0.75rem;">حاضر</th>
                                <th style="padding: 0.75rem;">غائب</th>
                                <th style="padding: 0.75rem;">متأخر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($advisorStudents as $student)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 0.75rem;">{{ $student->full_name }}<br><small style="color: var(--text-secondary);">{{ $student->student_code }}</small></td>
                                <td style="padding: 0.75rem;"><input type="radio" name="attendance[{{ $student->student_id }}]" value="present" required checked></td>
                                <td style="padding: 0.75rem;"><input type="radio" name="attendance[{{ $student->student_id }}]" value="absent" required></td>
                                <td style="padding: 0.75rem;"><input type="radio" name="attendance[{{ $student->student_id }}]" value="late" required></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 1rem; color: var(--text-secondary);">لا يوجد طلاب مسجلين في هذه الدورة.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 0.75rem; font-weight: 700; padding: 0.75rem;">حفظ الحضور اليومي</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Advisor Report Modal -->
    <div id="advisor-report-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="font-weight: 800; font-size: 1.1rem;">
                    <i class="fa-solid fa-file-signature" style="color: #ef4444;"></i>
                    كتابة تقرير سلوكي
                </h3>
                <button class="modal-close" onclick="closeModal('advisor-report-modal')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('teacher.advisor.report') }}" method="POST">
                    @csrf
                    <input type="hidden" name="course_id" value="{{ $advisorCourse->course_id }}">
                    <input type="hidden" name="request_id" id="report-request-id" value="">
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">اختر الطالب</label>
                        <select name="student_id" id="report-student-id" required style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary);">
                            <option value="">-- اختر طالباً --</option>
                            @foreach($advisorStudents as $student)
                                <option value="{{ $student->student_id }}">{{ $student->full_name }} ({{ $student->student_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">تفاصيل التقرير السلوكي</label>
                        <textarea name="report_content" rows="4" required placeholder="اكتب ملاحظاتك عن سلوك الطالب هنا..." style="width: 100%; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; border-radius: 0.75rem; font-weight: 700; padding: 0.75rem; background-color: #ef4444; color: white;">إرسال التقرير لرئيس القسم</button>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- ================================================================ --}}
{{--  MODAL: قائمة المواد الدراسية                                    --}}
{{-- ================================================================ --}}
<div id="courses-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-size: 1.1rem;">
                <i class="fa-solid fa-book-open" style="color: var(--accent-color);"></i>
                موادي الدراسية
            </h3>
            <button class="modal-close" onclick="closeModal('courses-modal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        @forelse($courses as $c)
            <div class="list-item">
                <div class="list-icon"><i class="fa-solid fa-chalkboard"></i></div>
                <div style="flex: 1;">
                    <div class="list-item-title">{{ $c->title }}</div>
                    <div class="list-item-sub">
                        @if($c->level)
                            <i class="fa-solid fa-layer-group"></i> {{ $c->level }}
                        @endif
                        @if($c->credits ?? false)
                            &nbsp; · &nbsp; {{ $c->credits }} ساعة
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <i class="fa-solid fa-book-open" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                لا توجد مواد مسندة حالياً
            </div>
        @endforelse
    </div>
</div>


{{-- ================================================================ --}}
{{--  MODAL: قائمة الواجبات                                           --}}
{{-- ================================================================ --}}
<div id="assignments-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-size: 1.1rem;">
                <i class="fa-solid fa-file-pen" style="color: var(--accent-color);"></i>
                الواجبات النشطة
            </h3>
            <button class="modal-close" onclick="closeModal('assignments-modal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        @forelse($recentAssignments as $a)
            @php
                $statusClass = 'badge-active';
                $statusText  = 'نشط';
                if(($a->graded_count ?? 0) >= ($a->submissions_count ?? 1) && ($a->submissions_count ?? 0) > 0) {
                    $statusClass = 'badge-graded';
                    $statusText  = 'تم التصحيح';
                } elseif(($a->submissions_count ?? 0) > 0) {
                    $statusClass = 'badge-pending';
                    $statusText  = 'قيد التصحيح';
                }
            @endphp
            <div class="list-item">
                <div class="list-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.2rem;">
                        <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                        <span class="list-item-title">{{ $a->title }}</span>
                    </div>
                    <div class="list-item-sub">
                        <i class="fa-solid fa-book"></i> {{ $a->course_title }}
                        &nbsp;·&nbsp;
                        <i class="fa-solid fa-calendar"></i> {{ \Carbon\Carbon::parse($a->due_date)->format('Y-m-d') }}
                        @if(isset($a->submissions_count))
                            &nbsp;·&nbsp;
                            <i class="fa-solid fa-users"></i> {{ $a->submissions_count }} تسليم
                        @endif
                    </div>
                </div>
                <a href="{{ route('teacher.assignments.submissions', $a->assignment_id) }}"
                   style="color: var(--accent-color); font-size: 1rem;"
                   title="عرض الردود"
                   onclick="closeModal('assignments-modal')">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <i class="fa-solid fa-file-circle-plus" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                لا توجد واجبات حالياً
            </div>
        @endforelse

        <div style="margin-top: 1rem; text-align: center;">
            <a href="{{ route('teacher.assignments') }}"
               onclick="closeModal('assignments-modal')"
               style="color: var(--accent-color); font-weight: 700; font-size: 0.9rem; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> عرض كل الواجبات
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
        document.body.style.overflow = '';
        if (modalId === 'advisor-report-modal') {
            document.getElementById('report-request-id').value = '';
            document.getElementById('report-student-id').value = '';
            document.getElementById('report-student-id').disabled = false;
        }
    }

    function openReportModalWithRequest(studentId, requestId) {
        document.getElementById('report-request-id').value = requestId;
        let select = document.getElementById('report-student-id');
        select.value = studentId;
        // select.disabled = true; // Optional: disable changing student if it's for a specific request
        openModal('advisor-report-modal');
    }

    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });
});
</script>
@endpush
