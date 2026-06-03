@extends('layouts.hod')

@section('title', 'طلب تقرير جديد')

@push('styles')
<style>
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 2rem;
        padding: 0.5rem 1.25rem;
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--text-primary);
        text-decoration: none;
        margin-bottom: 1.75rem;
        transition: border-color 0.2s;
        cursor: pointer;
    }
    .back-btn:hover { border-color: var(--accent-color); color: var(--text-primary); }

    .form-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: var(--shadow);
    }

    .section-title-wrap {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .section-icon {
        width: 42px; height: 42px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }
    .icon-yellow  { background: #fefce8; color: #ca8a04; }
    .icon-blue    { background: #eff6ff; color: #3b82f6; }
    .icon-purple  { background: #faf5ff; color: #a855f7; }
    .section-title { font-size: 1.2rem; font-weight: 700; }

    .form-control {
        width: 100%; padding: 0.9rem 1rem;
        border-radius: 1.5rem;
        border: 1px solid var(--border-color);
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit; font-size: 1rem;
        outline: none; box-sizing: border-box;
    }
    .form-control:focus { border-color: var(--accent-color); }

    .search-wrap { position: relative; margin-bottom: 0.75rem; }
    .search-wrap .form-control { padding-right: 1rem; padding-left: 3rem; }
    .search-icon { position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }

    select.form-control { appearance: none; cursor: pointer; }

    .suggestions-label { font-size: 0.83rem; color: var(--text-secondary); margin-bottom: 0.5rem; padding-right: 0.5rem; }
    .suggestions-list  { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .suggestion-chip {
        display: flex; align-items: center; gap: 0.5rem;
        padding: 0.25rem 0.75rem 0.25rem 0.25rem;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 2rem; font-size: 0.9rem; cursor: pointer;
        transition: border-color 0.2s;
    }
    .suggestion-chip:hover { border-color: var(--accent-color); }
    .suggestion-chip img { width: 26px; height: 26px; border-radius: 50%; }

    .type-cards  { display: flex; gap: 1rem; }
    .type-card {
        flex: 1; border: 1px solid var(--border-color); border-radius: 1rem;
        padding: 1.5rem; text-align: center; cursor: pointer; transition: all 0.2s;
    }
    .type-card.active { border-color: var(--accent-color); background: #fefce8; }
    .type-icon {
        width: 48px; height: 48px; border-radius: 50%;
        background: var(--bg-primary); color: var(--text-secondary);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 0.75rem; font-size: 1.3rem;
    }
    .type-card.active .type-icon { background: var(--accent-color); color: #1a1a1a; }
    .type-card h4 { font-size: 1rem; font-weight: 700; }

    textarea.form-control { border-radius: 1rem; resize: vertical; min-height: 90px; }

    .submit-btn {
        width: 100%; padding: 1.1rem;
        background: var(--accent-color); color: #1a1a1a;
        font-weight: 800; font-size: 1.1rem;
        border-radius: 2rem; border: none; cursor: pointer;
        font-family: inherit; margin-top: 0.5rem;
        display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        transition: opacity 0.2s;
    }
    .submit-btn:hover { opacity: 0.9; }
</style>
@endpush

@section('content')

    {{-- زر الرجوع --}}
    <a href="{{ route('hod.reports') }}" class="back-btn">
        <i class="fa-solid fa-arrow-right"></i>
        رجوع إلى التقارير
    </a>

    @if($errors->any())
        <div style="background:#fef2f2;color:#dc2626;padding:1rem;border-radius:0.75rem;margin-bottom:1rem;font-weight:700;">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('hod.reports.store') }}" method="POST">
        @csrf
        <input type="hidden" name="report_type" id="report_type_input" value="academic">

        {{-- Section 1: الطالب --}}
        <div class="form-card">
            <div class="section-title-wrap">
                <div class="section-icon icon-yellow"><i class="fa-solid fa-user-graduate"></i></div>
                <h3 class="section-title">الطالب المعني</h3>
            </div>

            <div class="search-wrap">
                <input type="text" id="student-search" oninput="filterStudents()"
                       class="form-control" placeholder="ابحث باسم الطالب المكتوب أدناه...">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
            </div>

            <select class="form-control" name="student_id" id="student-select" required style="margin-bottom:1rem;">
                <option value="" disabled selected>اختر الطالب من القائمة...</option>
                @foreach($students as $s)
                    <option value="{{ $s->student_id }}">{{ $s->full_name }}</option>
                @endforeach
            </select>

            <div class="suggestions-label">مقترحات سريعة (انقر للاختيار الفوري)</div>
            <div class="suggestions-list">
                @foreach($students->take(3) as $s)
                <div class="suggestion-chip" onclick="selectStudent({{ $s->student_id }})">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($s->full_name) }}&background=random" alt="">
                    <span>{{ $s->full_name }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Section 2: المدرب --}}
        <div class="form-card">
            <div class="section-title-wrap">
                <div class="section-icon icon-blue"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h3 class="section-title">المدرب المسؤول</h3>
            </div>

            <div class="search-wrap">
                <input type="text" id="teacher-search" oninput="filterTeachers()"
                       class="form-control" placeholder="ابحث باسم المدرب المسؤول...">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
            </div>

            <select class="form-control" name="teacher_id" id="teacher-select" required>
                <option value="" disabled selected>اختر المدرب من القائمة...</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->teacher_id }}">{{ $t->full_name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Section 3: تفاصيل التقرير --}}
        <div class="form-card">
            <div class="section-title-wrap">
                <div class="section-icon icon-purple"><i class="fa-solid fa-clipboard-list"></i></div>
                <h3 class="section-title">تفاصيل التقرير</h3>
            </div>

            <div class="type-cards" style="margin-bottom:1.25rem;">
                <div class="type-card active" id="type-academic" onclick="setType('academic')">
                    <div class="type-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <h4>أداء أكاديمي</h4>
                </div>
                <div class="type-card" id="type-behavioral" onclick="setType('behavioral')">
                    <div class="type-icon"><i class="fa-solid fa-user-check"></i></div>
                    <h4>سلوك وحضور</h4>
                </div>
            </div>

            <label style="display:block;margin-bottom:0.5rem;font-size:0.9rem;color:var(--text-secondary);">
                ملاحظات إضافية (اختياري)
            </label>
            <textarea class="form-control" name="recommendations"
                      placeholder="اكتب أي نقاط محددة ترغب في التركيز عليها..."></textarea>
        </div>

        {{-- زر الإرسال --}}
        <button type="submit" class="submit-btn">
            <i class="fa-solid fa-paper-plane"></i>
            إرسال التقرير
        </button>
    </form>

@endsection

@push('scripts')
<script>
    function selectStudent(id) {
        const sel = document.getElementById('student-select');
        sel.value = id;
        sel.style.borderColor = 'var(--accent-color)';
        setTimeout(() => sel.style.borderColor = 'var(--border-color)', 1000);
    }

    function filterStudents() {
        const q = document.getElementById('student-search').value.toLowerCase();
        [...document.getElementById('student-select').options].forEach(o => {
            o.style.display = (!o.value || o.text.toLowerCase().includes(q)) ? '' : 'none';
        });
    }

    function filterTeachers() {
        const q = document.getElementById('teacher-search').value.toLowerCase();
        [...document.getElementById('teacher-select').options].forEach(o => {
            o.style.display = (!o.value || o.text.toLowerCase().includes(q)) ? '' : 'none';
        });
    }

    function setType(type) {
        document.getElementById('report_type_input').value = type;
        document.getElementById('type-academic').classList.toggle('active', type === 'academic');
        document.getElementById('type-behavioral').classList.toggle('active', type === 'behavioral');
    }
</script>
@endpush
