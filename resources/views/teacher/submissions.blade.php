@extends('layouts.teacher')
@section('title', 'الردود - ' . ($assignment->title ?? ''))
@section('subtitle', $assignment->course_title ?? '')

@push('styles')
<style>
    .submission-card { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; box-shadow: var(--shadow); margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; cursor: pointer; border-right: 4px solid transparent; transition: all 0.2s; }
    .submission-card:hover { border-right-color: var(--accent-color); }
    .avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--accent-color); color: #1a1a1a; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .grade-badge { padding: 0.3rem 0.8rem; border-radius: 2rem; font-weight: 800; font-size: 0.88rem; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-card { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 100%; max-width: 600px; max-height: 85vh; overflow-y: auto; }
    .form-input { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
</style>
@endpush

@section('content')
    <!-- Back button -->
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('teacher.assignments') }}" style="color: var(--text-secondary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">
            <i class="fa-solid fa-arrow-right"></i> العودة للواجبات
        </a>
    </div>

    <!-- Assignment Info -->
    <div style="background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; box-shadow: var(--shadow); margin-bottom: 1.5rem; border-right: 4px solid var(--accent-color);">
        <div style="font-weight: 800; font-size: 1.1rem;">{{ $assignment->title }}</div>
        <div style="color: var(--text-secondary); font-size: 0.88rem; margin-top: 0.25rem;">
            <i class="fa-solid fa-book"></i> {{ $assignment->course_title }}
            &nbsp;|&nbsp;
            <i class="fa-solid fa-calendar"></i> تاريخ التسليم: {{ \Carbon\Carbon::parse($assignment->due_date)->format('Y-m-d') }}
            &nbsp;|&nbsp;
            <i class="fa-solid fa-users"></i> {{ $submissions->count() }} تسليم
        </div>
    </div>

    <!-- Submissions List -->
    @forelse($submissions as $s)
        <div class="submission-card" onclick="openGradeModal({{ $s->submission_id }}, '{{ addslashes($s->student_name) }}', {{ $s->grade ?? 'null' }}, '{{ addslashes($s->feedback ?? '') }}', '{{ addslashes($s->student_notes ?? '') }}', '{{ addslashes($s->file_path ?? '') }}')">
            <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                <div class="avatar">{{ mb_substr($s->student_name, 0, 1) }}</div>
                <div>
                    <div style="font-weight: 700;">{{ $s->student_name }}</div>
                    <div style="color: var(--text-secondary); font-size: 0.82rem; margin-top: 0.2rem;">
                        <i class="fa-solid fa-clock"></i>
                        {{ $s->submitted_at ? \Carbon\Carbon::parse($s->submitted_at)->format('Y-m-d h:i A') : 'غير محدد' }}
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                @if($s->grade !== null)
                    <span class="grade-badge" style="background: hsl(120,70%,90%); color: hsl(120,50%,30%);">{{ $s->grade }}/{{ $assignment->max_points }}</span>
                    <span style="font-size: 0.8rem; color: hsl(120,50%,30%); font-weight: 700;">تم التصحيح</span>
                @else
                    <span class="grade-badge" style="background: hsl(30,70%,90%); color: hsl(30,50%,30%);">بانتظار التصحيح</span>
                @endif
                <i class="fa-solid fa-chevron-left" style="color: var(--text-secondary);"></i>
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 4rem; background: var(--bg-secondary); border-radius: 1.5rem; color: var(--text-secondary);">
            <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent-color);"></i>
            <p>لا توجد تسليمات لهذا الواجب بعد</p>
        </div>
    @endforelse

    <!-- Grade Modal -->
    <div id="grade-modal" class="modal-overlay">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h3 style="font-weight: 800; font-size: 1.1rem;" id="modal-student-name">تصحيح الواجب</h3>
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">{{ $assignment->title }}</p>
                </div>
                <button onclick="document.getElementById('grade-modal').classList.remove('active')" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Student Answer & File -->
            <div style="background: var(--bg-primary); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.25rem;">
                <p style="font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.5rem;"><i class="fa-solid fa-quote-right" style="color: var(--accent-color);"></i> إجابة الطالب / الملاحظات</p>
                <p id="modal-content" style="font-size: 0.92rem; line-height: 1.6; color: var(--text-primary); margin-bottom: 1rem;"></p>
                <p style="font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.5rem;"><i class="fa-solid fa-paperclip" style="color: var(--accent-color);"></i> الملف المرفق</p>
                <div id="modal-file-container" style="font-size: 0.92rem;"></div>
            </div>

            <form id="grade-form" method="POST">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.9rem;">
                        الدرجة المستحقة (من {{ $assignment->max_points }})
                    </label>
                    <input type="number" name="grade" id="modal-grade" class="form-input" placeholder="0" min="0" max="{{ $assignment->max_points }}" required>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.9rem;">ملاحظات المعلم</label>
                    <textarea name="feedback" id="modal-feedback" class="form-input" rows="3" placeholder="اكتب ملاحظاتك للطالب هنا..." style="resize: vertical;"></textarea>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="flex: 1; padding: 0.85rem; background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; font-weight: 800; cursor: pointer; font-family: inherit; font-size: 1rem;">
                        <i class="fa-solid fa-floppy-disk"></i> حفظ التصحيح
                    </button>
                    <button type="button" onclick="document.getElementById('grade-modal').classList.remove('active')" style="flex: 1; padding: 0.85rem; background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 1rem;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function openGradeModal(submissionId, studentName, grade, feedback, content, filePath) {
    document.getElementById('modal-student-name').textContent = studentName;
    document.getElementById('modal-grade').value = grade ?? '';
    document.getElementById('modal-feedback').value = feedback ?? '';
    document.getElementById('modal-content').textContent = content || 'لا يوجد نص مرفق.';
    
    const fileContainer = document.getElementById('modal-file-container');
    if (filePath) {
        fileContainer.innerHTML = `<a href="/storage/${filePath}" target="_blank" style="color: var(--accent-color); text-decoration: underline; font-weight: bold;"><i class="fa-solid fa-file-arrow-down"></i> اضغط هنا لعرض/تحميل الملف المرفق</a>`;
    } else {
        fileContainer.innerHTML = '<span style="color: var(--text-secondary);">لا يوجد ملف مرفق</span>';
    }

    document.getElementById('grade-form').action = '/teacher/assignments/submissions/' + submissionId + '/grade';
    document.getElementById('grade-modal').classList.add('active');
}
</script>
@endpush
