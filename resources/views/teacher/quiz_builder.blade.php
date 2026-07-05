@extends('layouts.teacher')
@section('title', 'أسئلة: ' . $quiz->title)

@push('styles')
<style>
    .q-card  { background: var(--bg-secondary); border-radius: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 0.75rem; box-shadow: var(--shadow); border-right: 4px solid var(--accent-color); }
    .opt-row { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem; }
    .opt-row input[type=text] { flex: 1; }
    .form-input  { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.9rem; box-sizing: border-box; }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
    .form-label  { font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.4rem; display: block; }
    .btn-primary { background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; padding: 0.7rem 1.5rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.9rem; }
    .btn-danger  { background: hsl(0,70%,95%); border: none; color: hsl(0,50%,40%); border-radius: 0.5rem; padding: 0.35rem 0.65rem; cursor: pointer; font-size: 0.82rem; }
    .correct-radio { accent-color: var(--accent-color); width: 18px; height: 18px; cursor: pointer; }
    .add-opt-btn { background: none; border: 1px dashed var(--border-color); border-radius: 0.5rem; padding: 0.4rem 0.85rem; color: var(--text-secondary); cursor: pointer; font-size: 0.82rem; font-family: inherit; margin-top: 0.5rem; }
    .add-opt-btn:hover { border-color: var(--accent-color); color: var(--accent-color); }
    .type-toggle { display: flex; background: var(--bg-primary); border-radius: 0.75rem; padding: 4px; margin-bottom: 1rem; width: fit-content; gap: 4px; }
    .type-btn    { padding: 0.4rem 1rem; border-radius: 0.6rem; border: none; cursor: pointer; font-family: inherit; font-size: 0.85rem; font-weight: 600; background: transparent; color: var(--text-secondary); }
    .type-btn.active { background: var(--accent-color); color: #1a1a1a; }
</style>
@endpush

@section('content')

@if(session('success'))
<div style="background: hsl(120,70%,90%); color: hsl(120,40%,30%); border-radius: 0.75rem; padding: 0.9rem 1.25rem; margin-bottom: 1.5rem; font-weight: 600;">
    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
</div>
@endif

{{-- Header --}}
<div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <a href="{{ route('teacher.quizzes') }}" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">
        <i class="fa-solid fa-arrow-right"></i> الاختبارات
    </a>
    <span style="color: var(--border-color);">›</span>
    <span style="font-weight: 800;">{{ $quiz->title }}</span>
    <span style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 2rem; padding: 0.2rem 0.75rem; font-size: 0.78rem; color: var(--text-secondary);">
        {{ $quiz->course->title ?? '—' }} • {{ $quiz->duration_minutes }} دقيقة • {{ $quiz->total_marks }} علامة
    </span>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">

    {{-- Left: Questions list --}}
    <div>
        <div style="font-weight: 800; margin-bottom: 1rem; font-size: 1rem;">
            الأسئلة ({{ $quiz->questions->count() }})
        </div>

        @forelse($quiz->questions as $q)
        <div class="q-card">
            <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: flex-start;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; margin-bottom: 0.5rem;">
                        {{ $loop->iteration }}. {{ $q->question_text }}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                        {{ $q->type === 'mcq' ? 'اختيار من متعدد' : 'إجابة نصية' }} • {{ $q->marks }} علامة
                    </div>
                    @if($q->type === 'mcq')
                    <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                        @foreach($q->options as $opt)
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;
                                    {{ $opt->is_correct ? 'color: hsl(120,50%,30%); font-weight: 700;' : 'color: var(--text-secondary);' }}">
                            @if($opt->is_correct)
                                <i class="fa-solid fa-circle-check" style="color: hsl(120,50%,40%);"></i>
                            @else
                                <i class="fa-regular fa-circle" style="color: var(--border-color);"></i>
                            @endif
                            {{ $opt->option_text }}
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div style="font-size: 0.82rem; color: var(--text-secondary); font-style: italic;">إجابة مفتوحة</div>
                    @endif
                </div>
                <form action="{{ route('teacher.quizzes.question.delete', [$quiz->id, $q->id]) }}" method="POST"
                      onsubmit="return confirm('حذف السؤال؟')">
                    @csrf
                    <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
        </div>
        @empty
        <div style="text-align: center; padding: 2.5rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
            <i class="fa-solid fa-circle-question" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: var(--accent-color);"></i>
            لا توجد أسئلة بعد — أضف أول سؤال من اليمين
        </div>
        @endforelse
    </div>

    {{-- Right: Add question form --}}
    <div style="background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.5rem; box-shadow: var(--shadow); position: sticky; top: 1rem;">
        <div style="font-weight: 800; margin-bottom: 1rem; font-size: 0.95rem;">إضافة سؤال</div>

        <form action="{{ route('teacher.quizzes.question.store', $quiz->id) }}" method="POST" id="q-form">
            @csrf
            <input type="hidden" name="type" id="q-type" value="mcq">

            <div class="type-toggle">
                <button type="button" class="type-btn active" onclick="setType('mcq', this)">اختيار متعدد</button>
                <button type="button" class="type-btn" onclick="setType('text', this)">إجابة نصية</button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                <div>
                    <label class="form-label">نص السؤال *</label>
                    <textarea name="question_text" class="form-input" rows="3" placeholder="اكتب السؤال هنا..." required></textarea>
                </div>
                <div>
                    <label class="form-label">العلامة *</label>
                    <input type="number" name="marks" class="form-input" value="1" min="1" required>
                </div>

                {{-- MCQ Options --}}
                <div id="options-section">
                    <label class="form-label">الخيارات (حدد الصحيح)</label>
                    <div id="options-list">
                        @foreach(['أ', 'ب', 'ج', 'د'] as $i => $letter)
                        <div class="opt-row" id="opt-{{ $i }}">
                            <input type="radio" name="correct" value="{{ $i }}" class="correct-radio" {{ $i === 0 ? 'required' : '' }}>
                            <input type="text" name="options[]" class="form-input" placeholder="الخيار {{ $letter }}">
                            @if($i >= 2)
                            <button type="button" onclick="removeOpt({{ $i }})" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1rem;">✕</button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="add-opt-btn" onclick="addOpt()">
                        <i class="fa-solid fa-plus"></i> خيار إضافي
                    </button>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-plus"></i> إضافة السؤال
                </button>
            </div>
        </form>
    </div>

</div>

<script>
let optCount = 4;

function setType(type, btn) {
    document.getElementById('q-type').value = type;
    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('options-section').style.display = type === 'mcq' ? 'block' : 'none';
    document.querySelectorAll('[name="correct"]').forEach(r => r.required = type === 'mcq');
}

function addOpt() {
    const list = document.getElementById('options-list');
    const div = document.createElement('div');
    div.className = 'opt-row';
    div.id = 'opt-' + optCount;
    div.innerHTML = `<input type="radio" name="correct" value="${optCount}" class="correct-radio">
        <input type="text" name="options[]" class="form-input" placeholder="خيار جديد">
        <button type="button" onclick="removeOpt(${optCount})" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1rem;">✕</button>`;
    list.appendChild(div);
    optCount++;
}

function removeOpt(id) {
    const el = document.getElementById('opt-' + id);
    if (el) el.remove();
}
</script>

@media(max-width: 768px) {
    div[style*="grid-template-columns: 1fr 340px"] {
        grid-template-columns: 1fr !important;
    }
}
@endsection
