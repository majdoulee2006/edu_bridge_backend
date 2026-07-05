@extends('layouts.teacher')
@section('title', 'الاختبارات والمذاكرات')

@push('styles')
<style>
    .quiz-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; border-right: 4px solid var(--accent-color); }
    .badge { padding: 0.2rem 0.75rem; border-radius: 2rem; font-size: 0.78rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem; }
    .badge-published  { background: hsl(120,70%,90%); color: hsl(120,50%,30%); }
    .badge-draft      { background: #f1f5f9; color: #64748b; }
    .modal-overlay    { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-card       { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 100%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
    .form-input       { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; box-sizing: border-box; }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
    .form-label       { font-size: 0.88rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.4rem; display: block; }
    .btn-primary      { background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; padding: 0.75rem 1.5rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.95rem; width: 100%; }
    .btn-icon         { background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.5rem; padding: 0.4rem 0.75rem; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; }
    .btn-danger       { background: hsl(0,70%,95%); border: none; color: hsl(0,50%,40%); border-radius: 0.5rem; padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.4rem; }
    .row-2            { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media(max-width:480px){ .row-2 { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

@if(session('success'))
    <div style="background: hsl(120,70%,90%); color: hsl(120,40%,30%); border-radius: 0.75rem; padding: 0.9rem 1.25rem; margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div style="font-size: 1rem; color: var(--text-secondary);">{{ $quizzes->count() }} اختبار</div>
    <button onclick="document.getElementById('create-modal').classList.add('active')"
            style="background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; padding: 0.6rem 1.25rem; font-weight: 700; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-plus"></i> اختبار جديد
    </button>
</div>

@forelse($quizzes as $quiz)
<div class="quiz-card">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                @if($quiz->is_published)
                    <span class="badge badge-published"><i class="fa-solid fa-circle-check"></i> منشور</span>
                @else
                    <span class="badge badge-draft"><i class="fa-solid fa-pencil"></i> مسودة</span>
                @endif
                <span style="font-weight: 800; font-size: 1rem;">{{ $quiz->title }}</span>
            </div>
            <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.4rem;">
                {{ $quiz->course->title ?? '—' }}
            </div>
            <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; color: var(--text-secondary); font-size: 0.82rem;">
                <span><i class="fa-solid fa-circle-question"></i> {{ $quiz->questions_count }} سؤال</span>
                <span><i class="fa-solid fa-star"></i> {{ $quiz->total_marks }} علامة</span>
                <span><i class="fa-solid fa-clock"></i> {{ $quiz->duration_minutes }} دقيقة</span>
                @if($quiz->start_at)
                    <span><i class="fa-solid fa-calendar"></i> {{ $quiz->start_at->format('Y-m-d H:i') }}</span>
                @endif
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end;">
            <a href="{{ route('teacher.quizzes.builder', $quiz->id) }}" class="btn-icon">
                <i class="fa-solid fa-pen-to-square"></i> الأسئلة
            </a>
            <form action="{{ route('teacher.quizzes.publish', $quiz->id) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn-icon" style="{{ $quiz->is_published ? 'color:#ef4444;' : '' }}">
                    <i class="fa-solid fa-{{ $quiz->is_published ? 'eye-slash' : 'paper-plane' }}"></i>
                    {{ $quiz->is_published ? 'إلغاء النشر' : 'نشر' }}
                </button>
            </form>
            <form action="{{ route('teacher.quizzes.delete', $quiz->id) }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('هل أنت متأكد من حذف الاختبار؟')">
                @csrf
                <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i></button>
            </form>
        </div>
    </div>
</div>
@empty
<div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-circle-question" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--accent-color);"></i>
    لا توجد اختبارات حتى الآن — أنشئ اختباراً جديداً
</div>
@endforelse

{{-- Modal: إنشاء اختبار --}}
<div class="modal-overlay" id="create-modal" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0;">اختبار جديد</h3>
            <button onclick="document.getElementById('create-modal').classList.remove('active')"
                    style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-secondary);">✕</button>
        </div>
        <form action="{{ route('teacher.quizzes.store') }}" method="POST">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label class="form-label">عنوان الاختبار *</label>
                    <input type="text" name="title" class="form-input" placeholder="مثال: اختبار الفصل الأول" required>
                </div>
                <div>
                    <label class="form-label">المادة الدراسية *</label>
                    <select name="course_id" class="form-input" required>
                        <option value="">اختر المادة</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">الوصف (اختياري)</label>
                    <textarea name="description" class="form-input" rows="2" placeholder="تعليمات الاختبار..."></textarea>
                </div>
                <div class="row-2">
                    <div>
                        <label class="form-label">المدة (دقيقة) *</label>
                        <input type="number" name="duration_minutes" class="form-input" value="60" min="1" max="300" required>
                    </div>
                    <div>
                        <label class="form-label">العلامة الكلية *</label>
                        <input type="number" name="total_marks" class="form-input" value="100" min="1" required>
                    </div>
                </div>
                <div class="row-2">
                    <div>
                        <label class="form-label">تاريخ البداية</label>
                        <input type="datetime-local" name="start_at" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">تاريخ الانتهاء</label>
                        <input type="datetime-local" name="end_at" class="form-input">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">
                    <i class="fa-solid fa-arrow-left"></i> إنشاء الاختبار وإضافة الأسئلة
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
