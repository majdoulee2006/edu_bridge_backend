@extends('layouts.teacher')
@section('title', 'الاختبارات والتقييمات')

@push('styles')
<style>
    .event-card   { background: var(--bg-secondary); border-radius: 1.25rem; padding: 1.25rem 1.5rem; margin-bottom: 0.75rem; box-shadow: var(--shadow); border-right: 4px solid var(--accent-color); display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .badge        { padding: 0.25rem 0.85rem; border-radius: 2rem; font-size: 0.78rem; font-weight: 700; display: inline-block; }
    .badge-exam   { background: hsl(0,70%,93%);   color: hsl(0,50%,35%); }
    .badge-quiz   { background: hsl(220,70%,93%); color: hsl(220,50%,35%); }
    .badge-oral   { background: hsl(140,60%,90%); color: hsl(140,45%,30%); }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
    .modal-overlay.active { display: flex; }
    .modal-card   { background: var(--bg-secondary); border-radius: 1.5rem; padding: 2rem; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
    .form-input   { width: 100%; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; box-sizing: border-box; }
    .form-input:focus { outline: none; border-color: var(--accent-color); }
    .form-label   { font-size: 0.88rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.4rem; display: block; }
    .btn-primary  { background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; padding: 0.75rem 1.5rem; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 0.95rem; width: 100%; }
    .btn-danger   { background: hsl(0,70%,95%); border: none; color: hsl(0,50%,40%); border-radius: 0.5rem; padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.35rem; }
    .type-tabs    { display: flex; background: var(--bg-primary); border-radius: 0.75rem; padding: 4px; gap: 4px; margin-bottom: 1rem; }
    .type-tab     { flex: 1; padding: 0.5rem; border-radius: 0.6rem; border: none; cursor: pointer; font-family: inherit; font-size: 0.9rem; font-weight: 600; background: transparent; color: var(--text-secondary); transition: .15s; }
    .type-tab.active { background: var(--accent-color); color: #1a1a1a; }
    .row-2        { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media(max-width:480px){ .row-2 { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div style="color: var(--text-secondary); font-size: 0.95rem;">{{ $events->count() }} تقييم</div>
    <button onclick="document.getElementById('create-modal').classList.add('active')"
            style="background: var(--accent-color); color: #1a1a1a; border: none; border-radius: 0.75rem; padding: 0.6rem 1.25rem; font-weight: 700; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
        <i class="fa-solid fa-plus"></i> تقييم جديد
    </button>
</div>

{{-- Events list --}}
@forelse($events as $event)
<div class="event-card">
    <div style="flex: 1; min-width: 0;">
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.4rem;">
            @if($event->type === 'exam')
                <span class="badge badge-exam"><i class="fa-solid fa-file-circle-check"></i> امتحان</span>
            @elseif($event->type === 'quiz')
                <span class="badge badge-quiz"><i class="fa-solid fa-pen-clip"></i> مذاكرة</span>
            @else
                <span class="badge badge-oral"><i class="fa-solid fa-microphone"></i> شفهي</span>
            @endif
            <span style="font-weight: 800; font-size: 1rem;">{{ $event->title }}</span>
        </div>
        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; color: var(--text-secondary); font-size: 0.83rem;">
            <span><i class="fa-solid fa-book"></i> {{ $event->course_title ?? '—' }}</span>
            <span><i class="fa-solid fa-star"></i> {{ $event->max_score }} علامة</span>
            <span><i class="fa-solid fa-calendar"></i> {{ \Carbon\Carbon::parse($event->date)->format('Y-m-d') }}</span>
            @if(isset($event->time) && $event->time)
                <span><i class="fa-solid fa-clock"></i> {{ $event->time }}</span>
            @endif
            @if(isset($event->duration) && $event->duration)
                <span><i class="fa-solid fa-hourglass-half"></i> {{ $event->duration }}</span>
            @endif
            @if($event->notes)
                <span><i class="fa-solid fa-note-sticky"></i> {{ Str::limit($event->notes, 50) }}</span>
            @endif
        </div>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <a href="{{ route('teacher.grade_events.students', $event->id) }}" class="btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem; width: auto; background: var(--accent-color); color: #1a1a1a; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;">
            <i class="fa-solid fa-list-check"></i> إدخال العلامات
        </a>
        <form action="{{ route('teacher.grade_events.delete', $event->id) }}" method="POST"
              onsubmit="return confirm('حذف هذا التقييم؟')">
            @csrf
            <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i></button>
        </form>
    </div>
</div>
@empty
<div style="text-align: center; padding: 3rem; background: var(--bg-secondary); border-radius: 1.25rem; color: var(--text-secondary);">
    <i class="fa-solid fa-file-circle-question" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--accent-color);"></i>
    لا توجد تقييمات بعد — أنشئ أول تقييم
</div>
@endforelse

{{-- Modal: إنشاء تقييم --}}
<div class="modal-overlay" id="create-modal" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0;">تقييم جديد</h3>
            <button onclick="document.getElementById('create-modal').classList.remove('active')"
                    style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-secondary);">✕</button>
        </div>

        <form action="{{ route('teacher.grade_events.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" id="selected-type" value="exam">

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Type selector --}}
                <div>
                    <label class="form-label">نوع التقييم</label>
                    <div class="type-tabs">
                        <button type="button" class="type-tab active" onclick="selectType('exam', this)">
                            <i class="fa-solid fa-file-circle-check"></i> امتحان
                        </button>
                        <button type="button" class="type-tab" onclick="selectType('quiz', this)">
                            <i class="fa-solid fa-pen-clip"></i> مذاكرة
                        </button>
                        <button type="button" class="type-tab" onclick="selectType('oral', this)">
                            <i class="fa-solid fa-microphone"></i> شفهي
                        </button>
                    </div>
                </div>

                {{-- Course --}}
                <div>
                    <label class="form-label">المادة الدراسية *</label>
                    <select name="course_id" class="form-input" required>
                        <option value="">اختر المادة</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Title --}}
                <div>
                    <label class="form-label">الاسم / العنوان *</label>
                    <input type="text" name="title" class="form-input" placeholder="مثال: امتحان الفصل الأول" required>
                </div>

                {{-- Score + Date --}}
                <div class="row-2">
                    <div>
                        <label class="form-label">العلامة الكلية *</label>
                        <input type="number" name="max_score" class="form-input" value="100" min="1" required>
                    </div>
                    <div>
                        <label class="form-label">التاريخ *</label>
                        <input type="date" name="date" class="form-input" required>
                    </div>
                </div>

                {{-- Time + Duration --}}
                <div class="row-2">
                    <div>
                        <label class="form-label">ساعة الامتحان (اختياري)</label>
                        <input type="time" name="time" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">مدة الامتحان (دقائق - اختياري)</label>
                        <input type="text" name="duration" class="form-input" placeholder="مثال: 90 دقيقة">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="form-label">ملاحظات (اختياري)</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="أي ملاحظات إضافية..."></textarea>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 0.25rem;">
                    <i class="fa-solid fa-check"></i> إضافة التقييم
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function selectType(type, btn) {
    document.getElementById('selected-type').value = type;
    document.querySelectorAll('.type-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

@if($errors->any())
    document.getElementById('create-modal').classList.add('active');
@endif
</script>

@endsection
