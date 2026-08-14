@extends('layouts.student')
@section('title', 'واجباتي')
@section('subtitle', 'الواجبات والتسليمات الموزعة حسب المواد')

@push('styles')
<style>
    .course-accordion-item {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        margin-bottom: 1.25rem;
        overflow: hidden;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
    }
    .course-accordion-item:hover {
        border-color: var(--accent-color);
    }
    .course-accordion-header {
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        background: var(--bg-secondary);
        transition: background 0.2s;
    }
    .course-accordion-header:hover {
        background: var(--bg-primary);
    }
    .assignment-card {
        background: var(--bg-primary);
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border-right: 4px solid var(--accent-color);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .assignment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    }
    .assignment-card.overdue   { border-right-color: #ef4444; }
    .assignment-card.submitted { border-right-color: #3b82f6; }
    .assignment-card.graded    { border-right-color: #22c55e; }

    .badge { padding: 0.2rem 0.65rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 700; }
    .badge-pending  { background: hsl(30,70%,90%);  color: hsl(30,50%,30%); }
    .badge-late     { background: hsl(0,70%,90%);   color: hsl(0,50%,30%); }
    .badge-submitted{ background: hsl(200,70%,90%); color: hsl(200,50%,30%); }
    .badge-graded   { background: hsl(120,70%,90%); color: hsl(120,50%,30%); }

    .upload-area {
        border: 2px dashed var(--border-color);
        border-radius: 0.75rem;
        padding: 1rem;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s;
        margin-top: 0.75rem;
    }
    .upload-area:hover { border-color: var(--accent-color); }
    .upload-area input[type="file"] { display: none; }

    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.5); z-index: 1000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        width: 94%; max-width: 500px;
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
        margin-bottom: 1.25rem; padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .btn-primary {
        background: var(--accent-color); color: #1a1a1a;
        border: none; padding: 0.75rem 1.5rem;
        border-radius: 0.75rem; font-size: 0.95rem; font-weight: 700;
        cursor: pointer; font-family: inherit; width: 100%;
        transition: transform 0.2s;
    }
    .btn-primary:hover { transform: translateY(-1px); }
</style>
@endpush

@section('content')

@php
    $pendingCount = 0;
    foreach($assignments as $item) {
        $dDate = \Carbon\Carbon::parse($item->due_date);
        if (!$item->submission_id && !$dDate->isPast()) {
            $pendingCount++;
        }
    }
    $groupedAssignments = $assignments->groupBy('course_id');
@endphp

<!-- Filter Tabs -->
<div class="filter-tabs" style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.5rem; -webkit-overflow-scrolling: touch;">
    <button class="filter-btn active" data-filter="all" style="background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color); padding: 0.5rem 1.25rem; border-radius: 2rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap;">عرض الكل</button>
    <button class="filter-btn" data-filter="pending" style="background: var(--bg-secondary); color: var(--text-secondary); border: 1px solid var(--border-color); padding: 0.5rem 1.25rem; border-radius: 2rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.4rem;">
        نشط (بانتظار التسليم)
        @if($pendingCount > 0)
            <span style="background: #ef4444; color: #ffffff; font-size: 0.72rem; font-weight: 800; padding: 0.1rem 0.45rem; border-radius: 1rem; min-width: 18px; text-align: center; line-height: 1.2;">{{ $pendingCount }}</span>
        @endif
    </button>
    <button class="filter-btn" data-filter="submitted" style="background: var(--bg-secondary); color: var(--text-secondary); border: 1px solid var(--border-color); padding: 0.5rem 1.25rem; border-radius: 2rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap;">تم التسليم</button>
    <button class="filter-btn" data-filter="graded" style="background: var(--bg-secondary); color: var(--text-secondary); border: 1px solid var(--border-color); padding: 0.5rem 1.25rem; border-radius: 2rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap;">تم التصحيح</button>
    <button class="filter-btn" data-filter="overdue" style="background: var(--bg-secondary); color: var(--text-secondary); border: 1px solid var(--border-color); padding: 0.5rem 1.25rem; border-radius: 2rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap;">منتهي</button>
</div>

<!-- Courses Accordion List -->
@forelse($courses as $index => $c)
    @php
        $courseAssigns = $groupedAssignments->get($c->course_id, collect());
        $hasPendingInCourse = false;
        foreach($courseAssigns as $ca) {
            if (!$ca->submission_id && !\Carbon\Carbon::parse($ca->due_date)->isPast()) {
                $hasPendingInCourse = true;
                break;
            }
        }
        $shouldExpand = $hasPendingInCourse || ($index === 0 && $courseAssigns->count() > 0);
    @endphp

    <div class="course-accordion-item" data-course-id="{{ $c->course_id }}">
        <!-- Accordion Header -->
        <div class="course-accordion-header" onclick="toggleAccordion('course-body-{{ $c->course_id }}', 'chevron-{{ $c->course_id }}')">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 1rem; background: rgba(234, 179, 8, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fa-solid fa-book-open" style="font-size: 1.3rem; color: #eab308;"></i>
                </div>
                <div>
                    <div style="font-size: 1.05rem; font-weight: 800; color: var(--text-primary);">
                        {{ $c->title }}
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.2rem; display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fa-solid fa-chalkboard-user" style="color: var(--accent-color);"></i>
                        <span><strong>المدرس:</strong> {{ $c->teacher_name }}</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <span style="font-size: 0.8rem; font-weight: 700; background: var(--bg-primary); padding: 0.35rem 0.85rem; border-radius: 2rem; color: var(--text-secondary); border: 1px solid var(--border-color);">
                    {{ $courseAssigns->count() }} واجب{{ $courseAssigns->count() == 1 ? '' : 'ات' }}
                </span>
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-primary); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                    <i id="chevron-{{ $c->course_id }}" class="fa-solid fa-chevron-down toggle-icon" style="transition: transform 0.3s ease; color: var(--text-secondary); transform: {{ $shouldExpand ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                </div>
            </div>
        </div>

        <!-- Accordion Body -->
        <div id="course-body-{{ $c->course_id }}" class="course-accordion-body" style="display: {{ $shouldExpand ? 'block' : 'none' }}; padding: 0 1.25rem 1.25rem 1.25rem; border-top: 1px solid var(--border-color);">
            <div style="padding-top: 1.25rem;">
                @forelse($courseAssigns as $a)
                    @php
                        $dueDate     = \Carbon\Carbon::parse($a->due_date);
                        $isOverdue   = $dueDate->isPast() && !$a->submission_id;
                        $isSubmitted = $a->submission_id && !$a->grade;
                        $isGraded    = $a->grade !== null;
                        $isPending   = !$a->submission_id && !$isOverdue;

                        if ($isGraded)        $cardClass = 'graded';
                        elseif ($isSubmitted) $cardClass = 'submitted';
                        elseif ($isOverdue)   $cardClass = 'overdue';
                        else $cardClass = '';
                    @endphp

                    <div class="assignment-card {{ $cardClass }}" data-status="{{ $isGraded ? 'graded' : ($isSubmitted ? 'submitted' : ($isOverdue ? 'overdue' : 'pending')) }}">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                @if($isGraded)
                                    <span class="badge badge-graded">تم التصحيح</span>
                                @elseif($isSubmitted)
                                    <span class="badge badge-submitted">تم التسليم</span>
                                @elseif($isOverdue)
                                    <span class="badge badge-late">منتهي</span>
                                @else
                                    <span class="badge badge-pending">نشط</span>
                                @endif
                                <span style="font-weight: 800; font-size: 1rem; color: var(--text-primary);">{{ $a->title }}</span>
                            </div>
                            @if($isGraded)
                                @php
                                    $isPassStudent = $a->grade >= (($a->max_points ?? 100) / 2);
                                    $studentGradeColor = $isPassStudent ? '#22c55e' : '#ef4444';
                                @endphp
                                <span style="font-size: 1.2rem; font-weight: 800; color: {{ $studentGradeColor }};">{{ $a->grade }}<span style="font-size: 0.85rem; color: var(--text-secondary);">/{{ $a->max_points }}</span></span>
                            @endif
                        </div>

                        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.75rem; line-height: 1.6;">
                            {{ Str::limit($a->description, 200) }}
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                            <span><i class="fa-solid fa-calendar" style="color: var(--accent-color);"></i> موعد التسليم: <strong>{{ $dueDate->format('Y-m-d H:i') }}</strong></span>
                        </div>

                        @if($a->file_path ?? false)
                            <div style="margin-bottom: 0.75rem;">
                                <a href="/storage/{{ $a->file_path }}" target="_blank" download="{{ $a->file_name ?? 'ملف_المعلم' }}"
                                   style="display: inline-flex; align-items: center; gap: 0.4rem; color: #eab308; font-size: 0.82rem; font-weight: 700; text-decoration: none; background: rgba(234, 179, 8, 0.1); padding: 0.35rem 0.85rem; border-radius: 0.5rem;">
                                    <i class="fa-solid fa-paperclip"></i> تحميل مرفق المعلم
                                </a>
                            </div>
                        @endif

                        @if($isGraded)
                            <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.75rem 1rem; font-size: 0.85rem; color: #22c55e; font-weight: 700; border-right: 3px solid #22c55e; line-height: 1.5;">
                                <i class="fa-solid fa-circle-check"></i> تم تصحيح وتقييم الواجب بنجاح
                                @if($a->feedback)
                                    <div style="margin-top: 0.4rem; color: var(--text-secondary); font-weight: normal;">
                                        <strong style="color: var(--text-primary);">ملاحظة المعلم:</strong> {{ $a->feedback }}
                                    </div>
                                @endif
                            </div>
                        @elseif($a->submission_id)
                            <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 0.75rem 1rem; font-size: 0.85rem; color: #3b82f6; font-weight: 700; border-right: 3px solid #3b82f6;">
                                <i class="fa-solid fa-circle-check"></i>
                                تم تسليم الواجب في {{ \Carbon\Carbon::parse($a->submitted_at)->format('Y-m-d H:i') }}
                                @if($a->submission_file)
                                    <div style="margin-top: 0.4rem; font-weight: normal;">
                                        <a href="/storage/{{ $a->submission_file }}" target="_blank" style="color: #eab308; text-decoration: none; font-weight: 700;">
                                            <i class="fa-solid fa-paperclip"></i> الملف المرفق
                                        </a>
                                    </div>
                                @endif
                                @if($a->solution_text)
                                    <div style="margin-top: 0.4rem; color: var(--text-primary); font-weight: normal; background: var(--bg-primary); padding: 0.5rem 0.75rem; border-radius: 0.5rem;">
                                        <strong>نص الحل:</strong> {{ $a->solution_text }}
                                    </div>
                                @endif
                                @if($a->student_notes)
                                    <div style="margin-top: 0.4rem; color: var(--text-secondary); font-weight: normal;">
                                        <strong>ملاحظاتك:</strong> {{ $a->student_notes }}
                                    </div>
                                @endif
                            </div>
                        @elseif(!$isOverdue)
                            <button onclick="openSubmitModal({{ $a->assignment_id }}, '{{ addslashes($a->title) }}')"
                                style="background: linear-gradient(135deg, #ffe600, #facc15); color: #111827; border: none; padding: 0.6rem 1.4rem; border-radius: 0.75rem; font-size: 0.88rem; font-weight: 800; cursor: pointer; font-family: inherit; box-shadow: 0 4px 12px rgba(250, 204, 21, 0.35); transition: transform 0.2s;">
                                <i class="fa-solid fa-upload"></i> تسليم الواجب
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="course-empty-msg" style="text-align: center; padding: 2rem; color: var(--text-secondary); font-size: 0.9rem;">
                        <i class="fa-solid fa-folder-open" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                        لا توجد واجبات مضافة لهذه المادة حالياً
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@empty
    <div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
        <i class="fa-solid fa-file-circle-check" style="font-size: 2.5rem; opacity: 0.4; display: block; margin-bottom: 0.75rem;"></i>
        لا توجد مواد مسجلة حالياً
    </div>
@endforelse

{{-- Submit Modal --}}
<div id="submit-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-size: 1.05rem; display: flex; align-items: center; gap: 0.4rem;">
                <span style="color: #eab308;">🔑</span>
                تسليم الواجب: <span id="modal-assignment-title"></span>
            </h3>
            <button onclick="closeSubmitModal()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-secondary); cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="submit-form" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div style="font-size: 0.78rem; color: #ef4444; font-weight: 700; margin-bottom: 0.85rem; background: rgba(239, 68, 68, 0.08); padding: 0.4rem 0.75rem; border-radius: 0.5rem; border-right: 3px solid #ef4444;">
                * يجب كتابة نص الحل أو إرفاق ملف على الأقل لإتمام التسليم.
            </div>

            {{-- 1. نص الحل / الإجابة الكتابية --}}
            <div style="margin-bottom: 1rem; text-align: right;">
                <label for="solution_text" style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.4rem;">
                    <i class="fa-solid fa-pen-to-square" style="color: var(--accent-color); margin-left: 0.3rem;"></i> نص الحل / الإجابة
                </label>
                <textarea id="solution_text" name="solution_text" rows="3" placeholder="اكتب إجابتك أو نص الحل هنا..."
                    style="width: 100%; background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 0.75rem; font-family: inherit; font-size: 0.88rem; outline: none; resize: vertical; box-sizing: border-box; transition: border-color 0.2s;"></textarea>
            </div>

            {{-- 2. منطقة رفع الملف --}}
            <div style="margin-bottom: 1rem; text-align: right;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.4rem;">
                    <i class="fa-solid fa-paperclip" style="color: var(--accent-color); margin-left: 0.3rem;"></i> إرفاق ملف الحل
                </label>
                <div class="upload-area" onclick="document.getElementById('submit-file').click()" style="border: 2px dashed #cbd5e1; border-radius: 1rem; padding: 1.25rem 1rem; text-align: center; cursor: pointer; transition: all 0.2s; background: var(--bg-primary);">
                    <input type="file" id="submit-file" name="file"
                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                        onchange="updateFileName(this)">
                    <div style="width: 52px; height: 52px; background: rgba(234, 179, 8, 0.18); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.6rem auto;">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 1.6rem; color: #eab308;"></i>
                    </div>
                    <div id="file-name-display" style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">اضغط لاختيار ملف</div>
                    <div style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.25rem;">PDF, DOC, JPG, ZIP — حتى 50 ميجابايت</div>
                </div>
            </div>

            {{-- 3. ملاحظات إضافية للمدرس --}}
            <div style="margin-bottom: 1.25rem; text-align: right;">
                <label for="student_notes" style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.4rem;">
                    <i class="fa-solid fa-comment-dots" style="color: var(--accent-color); margin-left: 0.3rem;"></i> ملاحظات للمدرس (اختياري)
                </label>
                <input type="text" id="student_notes" name="student_notes" placeholder="أضف أي ملاحظة ترغب بإرسالها للمدرس..."
                    style="width: 100%; background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 0.65rem 0.85rem; font-family: inherit; font-size: 0.85rem; outline: none; box-sizing: border-box;">
            </div>

            {{-- 4. زر التسليم المتوهج --}}
            <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #ffe600, #facc15); color: #111827; border: none; padding: 0.85rem 1.5rem; border-radius: 1rem; font-size: 1rem; font-weight: 800; cursor: pointer; font-family: inherit; width: 100%; box-shadow: 0 8px 25px rgba(250, 204, 21, 0.45); transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                تسليم الآن <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleAccordion(bodyId, iconId) {
    const body = document.getElementById(bodyId);
    const icon = document.getElementById(iconId);
    if (!body) return;
    
    if (body.style.display === 'none' || body.style.display === '') {
        body.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}

function openSubmitModal(id, title) {
    document.getElementById('modal-assignment-title').textContent = title;
    document.getElementById('submit-form').action = '/student/assignments/' + id + '/submit';
    document.getElementById('submit-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Clear previous input values
    const solText = document.getElementById('solution_text');
    if (solText) solText.value = '';
    const notesInput = document.getElementById('student_notes');
    if (notesInput) notesInput.value = '';
    const fileInput = document.getElementById('submit-file');
    if (fileInput) fileInput.value = '';
    const display = document.getElementById('file-name-display');
    if (display) display.textContent = 'اضغط لاختيار ملف';
}
function closeSubmitModal() {
    document.getElementById('submit-modal').classList.remove('active');
    document.body.style.overflow = '';
}
function updateFileName(input) {
    const display = document.getElementById('file-name-display');
    display.textContent = input.files[0] ? input.files[0].name : 'اضغط لاختيار ملف';
}
document.getElementById('submit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeSubmitModal();
});

// Validate that either solution_text OR file is provided
document.getElementById('submit-form').addEventListener('submit', function(e) {
    const solText = (document.getElementById('solution_text')?.value || '').trim();
    const fileInput = document.getElementById('submit-file');
    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
    
    if (!solText && !hasFile) {
        e.preventDefault();
        alert('⚠️ يرجى كتابة نص الحل أو إرفاق ملف على الأقل لتسليم الواجب.');
        return false;
    }
});

// Client-side status filtering
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = 'var(--bg-secondary)';
            b.style.color = 'var(--text-secondary)';
            b.style.borderColor = 'var(--border-color)';
        });
        this.classList.add('active');
        this.style.background = 'var(--accent-color)';
        this.style.color = '#1a1a1a';
        this.style.borderColor = 'var(--accent-color)';
        
        const filter = this.getAttribute('data-filter');

        document.querySelectorAll('.course-accordion-item').forEach(accordion => {
            let visibleCount = 0;
            const cards = accordion.querySelectorAll('.assignment-card');
            
            cards.forEach(card => {
                const status = card.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const emptyMsg = accordion.querySelector('.course-empty-msg');

            if (filter === 'all') {
                accordion.style.display = 'block';
                if (emptyMsg) emptyMsg.style.display = cards.length === 0 ? 'block' : 'none';
            } else {
                if (visibleCount > 0) {
                    accordion.style.display = 'block';
                    const body = accordion.querySelector('.course-accordion-body');
                    const icon = accordion.querySelector('.toggle-icon');
                    if (body) body.style.display = 'block';
                    if (icon) icon.style.transform = 'rotate(180deg)';
                    if (emptyMsg) emptyMsg.style.display = 'none';
                } else {
                    accordion.style.display = 'none';
                }
            }
        });
    });
});

// Set active style for initial load
const initialActive = document.querySelector('.filter-btn.active');
if (initialActive) {
    initialActive.style.background = 'var(--accent-color)';
    initialActive.style.color = '#1a1a1a';
    initialActive.style.borderColor = 'var(--accent-color)';
}
</script>
@endpush
