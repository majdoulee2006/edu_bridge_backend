@extends('layouts.student')
@section('title', 'واجباتي')
@section('subtitle', 'الواجبات والتسليمات')

@push('styles')
<style>
    .assignment-card {
        background: var(--bg-secondary);
        border-radius: 1.25rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow);
        border-right: 4px solid var(--accent-color);
    }
    .assignment-card.overdue { border-right-color: #ef4444; }
    .assignment-card.submitted { border-right-color: #3b82f6; }
    .assignment-card.graded { border-right-color: #22c55e; }

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

@forelse($assignments as $a)
@php
    $dueDate  = \Carbon\Carbon::parse($a->due_date);
    $isOverdue  = $dueDate->isPast() && !$a->submission_id;
    $isSubmitted = $a->submission_id && !$a->grade;
    $isGraded    = $a->grade !== null;
    $isPending   = !$a->submission_id && !$isOverdue;

    if ($isGraded)    $cardClass = 'graded';
    elseif ($isSubmitted) $cardClass = 'submitted';
    elseif ($isOverdue)   $cardClass = 'overdue';
    else $cardClass = '';
@endphp

<div class="assignment-card {{ $cardClass }}">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            @if($isGraded)
                <span class="badge badge-graded">تم التصحيح</span>
            @elseif($isSubmitted)
                <span class="badge badge-submitted">تم التسليم</span>
            @elseif($isOverdue)
                <span class="badge badge-late">منتهي</span>
            @else
                <span class="badge badge-pending">نشط</span>
            @endif
            <span style="font-weight: 800; font-size: 1rem;">{{ $a->title }}</span>
        </div>
        @if($isGraded)
            <span style="font-size: 1.2rem; font-weight: 800; color: #22c55e;">{{ $a->grade }}<span style="font-size: 0.85rem; color: var(--text-secondary);">/{{ $a->max_points }}</span></span>
        @endif
    </div>

    <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.75rem; line-height: 1.6;">
        {{ Str::limit($a->description, 150) }}
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
        <span><i class="fa-solid fa-book"></i> {{ $a->course_title }}</span>
        <span><i class="fa-solid fa-calendar"></i> الموعد: {{ $dueDate->format('Y-m-d') }}</span>
        <span><i class="fa-solid fa-star"></i> {{ $a->max_points }} نقطة</span>
    </div>

    @if($a->file_path ?? false)
        <a href="{{ asset('storage/' . $a->file_path) }}" target="_blank" download
           style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--accent-color); font-size: 0.82rem; font-weight: 700; text-decoration: none; margin-bottom: 0.75rem;">
            <i class="fa-solid fa-paperclip"></i> ملف المعلم
        </a>
    @endif

    @if($isGraded && $a->feedback)
        <div style="background: var(--bg-primary); border-radius: 0.75rem; padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--text-secondary); border-right: 3px solid #22c55e;">
            <strong style="color: var(--text-primary);">ملاحظة المعلم:</strong> {{ $a->feedback }}
        </div>
    @elseif($isSubmitted)
        <div style="background: var(--bg-primary); border-radius: 0.75rem; padding: 0.75rem 1rem; font-size: 0.85rem; color: #3b82f6; font-weight: 700;">
            <i class="fa-solid fa-circle-check"></i>
            تم تسليم الواجب في {{ \Carbon\Carbon::parse($a->submitted_at)->format('Y-m-d H:i') }}
        </div>
    @elseif(!$isOverdue)
        <button onclick="openSubmitModal({{ $a->assignment_id }}, '{{ addslashes($a->title) }}')"
            style="background: var(--accent-color); color: #1a1a1a; border: none; padding: 0.6rem 1.2rem; border-radius: 0.75rem; font-size: 0.88rem; font-weight: 700; cursor: pointer; font-family: inherit;">
            <i class="fa-solid fa-upload"></i> تسليم الواجب
        </button>
    @endif
</div>
@empty
<div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-file-circle-check" style="font-size: 2.5rem; opacity: 0.4; display: block; margin-bottom: 0.75rem;"></i>
    لا توجد واجبات حالياً
</div>
@endforelse

{{-- Submit Modal --}}
<div id="submit-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-weight: 800; font-size: 1.05rem;">
                <i class="fa-solid fa-upload" style="color: var(--accent-color);"></i>
                تسليم الواجب: <span id="modal-assignment-title"></span>
            </h3>
            <button onclick="closeSubmitModal()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-secondary); cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="submit-form" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="upload-area" onclick="document.getElementById('submit-file').click()">
                <input type="file" id="submit-file" name="file"
                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
                    onchange="updateFileName(this)">
                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 0.5rem; display: block;"></i>
                <div id="file-name-display" style="font-size: 0.88rem; color: var(--text-secondary);">اضغط لاختيار ملف</div>
                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.3rem;">PDF, DOC, JPG, ZIP — حتى 50 ميجابايت</div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top: 1.25rem;">
                <i class="fa-solid fa-paper-plane"></i> تسليم الآن
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openSubmitModal(id, title) {
    document.getElementById('modal-assignment-title').textContent = title;
    document.getElementById('submit-form').action = '/student/assignments/' + id + '/submit';
    document.getElementById('submit-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
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
</script>
@endpush
