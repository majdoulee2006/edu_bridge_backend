@extends('layouts.hod')

@section('title', 'طلب التقارير')

@push('styles')
<style>
    .page-subtitle {
        color: var(--text-secondary);
        font-size: 1rem;
        margin-top: -1.5rem;
        margin-bottom: 2rem;
    }

    .form-card {
        background-color: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
    }
    
    .section-title-wrap {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .section-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    
    .icon-yellow { background-color: #fefce8; color: #ca8a04; }
    .icon-blue { background-color: #eff6ff; color: #3b82f6; }
    .icon-purple { background-color: #faf5ff; color: #a855f7; }
    
    .section-title {
        font-size: 1.2rem;
        font-weight: 700;
    }

    /* Input Fields */
    .search-input-wrap {
        position: relative;
        margin-bottom: 1rem;
    }
    
    .form-control {
        width: 100%;
        padding: 1rem;
        border-radius: 2rem;
        border: 1px solid var(--border-color);
        background-color: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit;
        font-size: 1rem;
        outline: none;
    }
    
    .search-input-wrap .form-control {
        padding-inline-end: 3rem; /* RTL padding for icon */
    }
    
    .search-icon {
        position: absolute;
        left: 1.5rem; /* Left in RTL */
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
    }
    
    /* Suggestions */
    .suggestions-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
        padding-inline-start: 1rem;
    }
    
    .suggestions-list {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .suggestion-chip {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.75rem 0.25rem 0.25rem;
        background-color: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 2rem;
        font-size: 0.9rem;
        cursor: pointer;
    }
    
    .suggestion-chip img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
    }
    
    /* Select Dropdown styling */
    .select-wrapper {
        position: relative;
    }
    
    .select-wrapper::after {
        content: '\f107';
        font-family: 'FontAwesome';
        position: absolute;
        left: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        pointer-events: none;
    }
    
    select.form-control {
        appearance: none;
        padding-inline-end: 1rem;
        padding-inline-start: 3rem; /* space for arrow in RTL */
    }
    
    /* Report Types */
    .report-types {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .type-card {
        flex: 1;
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .type-card.active {
        border-color: var(--accent-color);
        background-color: #fefce8;
    }
    
    .type-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background-color: var(--bg-primary);
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.25rem;
    }
    
    .type-card.active .type-icon {
        background-color: var(--accent-color);
        color: #1a1a1a;
    }
    
    .type-card h4 {
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    /* Textarea */
    textarea.form-control {
        border-radius: 1rem;
        resize: vertical;
        min-height: 100px;
    }
    
    .submit-btn-container {
        margin-top: 2rem;
    }
    
    .btn-submit {
        width: 100%;
        padding: 1.25rem;
        background-color: var(--accent-color);
        color: #1a1a1a;
        font-weight: 700;
        font-size: 1.1rem;
        border-radius: 2rem;
        border: none;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
    <p class="page-subtitle">تقديم طلب تقرير عن أداء طالب</p>

    <form action="{{ route('hod.reports.store') }}" method="POST" id="report-form">
        @csrf
        <input type="hidden" name="report_type" id="report_type_input" value="academic">

        <!-- Section 1 -->
        <div class="form-card">
            <div class="section-title-wrap">
                <div class="section-icon icon-yellow"><i class="fa-solid fa-user-graduate"></i></div>
                <h3 class="section-title">الطالب المعني</h3>
            </div>
            
            <div class="search-input-wrap" style="margin-bottom: 1rem;">
                <input type="text" id="student-search" onkeyup="filterStudents()" class="form-control" placeholder="ابحث باسم الطالب المكتوب أدناه...">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
            </div>

            <div class="select-wrapper" style="margin-bottom: 1.5rem;">
                <select class="form-control" name="student_id" id="student-select" required>
                    <option value="" disabled selected>اختر الطالب من القائمة...</option>
                    @foreach($students as $student)
                    <option value="{{ $student->student_id }}">{{ $student->full_name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="suggestions-label">مقترحات سريعة (انقر للاختيار الفوري)</div>
            <div class="suggestions-list">
                @foreach($students->take(3) as $student)
                <div class="suggestion-chip" onclick="selectStudent({{ $student->student_id }})">
                    <span>{{ $student->full_name }}</span>
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($student->full_name) }}&background=random" alt="Avatar">
                </div>
                @endforeach
            </div>
        </div>

        <!-- Section 2 -->
        <div class="form-card">
            <div class="section-title-wrap">
                <div class="section-icon icon-blue"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h3 class="section-title">المدرب المسؤول</h3>
            </div>

            <div class="search-input-wrap" style="margin-bottom: 1rem;">
                <input type="text" id="teacher-search" onkeyup="filterTeachers()" class="form-control" placeholder="ابحث باسم المدرب المسؤول...">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
            </div>
            
            <div class="select-wrapper">
                <select class="form-control" name="teacher_id" id="teacher-select" required>
                    <option value="" disabled selected>اختر المدرب من القائمة...</option>
                    @foreach($teachers as $teacher)
                    <option value="{{ $teacher->teacher_id }}">{{ $teacher->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Section 3 -->
        <div class="form-card">
            <div class="section-title-wrap">
                <div class="section-icon icon-purple"><i class="fa-solid fa-clipboard-list"></i></div>
                <h3 class="section-title">تفاصيل التقرير</h3>
            </div>
            
            <div class="report-types">
                <div class="type-card" id="type-behavioral" onclick="setReportType('behavioral')">
                    <div class="type-icon"><i class="fa-solid fa-user-check"></i></div>
                    <h4>سلوك وحضور</h4>
                </div>
                <div class="type-card active" id="type-academic" onclick="setReportType('academic')">
                    <div class="type-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <h4>أداء أكاديمي</h4>
                </div>
            </div>
            
            <label style="display:block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);">ملاحظات إضافية (اختياري)</label>
            <textarea class="form-control" name="recommendations" placeholder="اكتب أي نقاط محددة ترغب في التركيز عليها..."></textarea>
        </div>

        <div class="submit-btn-container">
            <button type="submit" class="btn-submit">إرسال الطلب</button>
        </div>
    </form>

    <!-- Section 4: Reports History -->
    <div class="form-card" style="margin-top: 2.5rem; overflow: hidden;">
        <div class="section-title-wrap">
            <div class="section-icon icon-yellow"><i class="fa-solid fa-file-invoice"></i></div>
            <h3 class="section-title">التقارير الصادرة والمنشأة في قاعدة البيانات</h3>
        </div>
        
        <div style="overflow-x: auto; margin-top: 1rem; border-radius: 1rem; background: var(--bg-primary);">
            <table style="width: 100%; border-collapse: collapse; text-align: right; color: var(--text-primary); font-size: 0.95rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary); background: var(--bg-secondary);">
                        <th style="padding: 1.25rem 1rem;">الطالب</th>
                        <th style="padding: 1.25rem 1rem;">نوع التقرير</th>
                        <th style="padding: 1.25rem 1rem; text-align: center;">نسبة الحضور</th>
                        <th style="padding: 1.25rem 1rem; text-align: center;">المعدل الأكاديمي</th>
                        <th style="padding: 1.25rem 1rem;">التوصيات والملاحظات</th>
                        <th style="padding: 1.25rem 1rem;">تاريخ الإصدار</th>
                        <th style="padding: 1.25rem 1rem; text-align: center;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s;" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 1.25rem 1rem; font-weight: bold; color: var(--accent-color);">{{ $report->student_name }}</td>
                        <td style="padding: 1.25rem 1rem;">
                            <span style="padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.85rem; font-weight: bold; background-color: {{ $report->report_type === 'academic' ? '#dbeafe' : '#fef3c7' }}; color: {{ $report->report_type === 'academic' ? '#1e40af' : '#92400e' }};">
                                {{ $report->report_type === 'academic' ? 'أكاديمي' : 'سلوكي' }}
                            </span>
                        </td>
                        <td style="padding: 1.25rem 1rem; font-weight: 600; text-align: center;">{{ number_format($report->attendance_rate, 0) }}%</td>
                        <td style="padding: 1.25rem 1rem; font-weight: 600; color: #10b981; text-align: center;">{{ number_format($report->average_grade, 0) }}%</td>
                        <td style="padding: 1.25rem 1rem; font-size: 0.9rem; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $report->recommendations }}">{{ $report->recommendations }}</td>
                        <td style="padding: 1.25rem 1rem; font-size: 0.85rem; color: var(--text-secondary);">{{ \Carbon\Carbon::parse($report->generated_at)->diffForHumans() }}</td>
                        <td style="padding: 1.25rem 1rem; text-align: center;">
                            <form action="{{ route('hod.reports.delete', $report->report_id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا التقرير؟')" style="margin: 0; padding: 0;">
                                @csrf
                                <button type="submit" style="background: transparent; border: none; color: #ef4444; cursor: pointer; font-size: 1.1rem; padding: 0.25rem 0.5rem; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 2.5rem; text-align: center; color: var(--text-secondary);">
                            <i class="fa-regular fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: var(--border-color);"></i>
                            لا توجد تقارير صادرة حالياً في قاعدة البيانات.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function selectStudent(studentId) {
        const select = document.getElementById('student-select');
        select.value = studentId;
        
        // Add a visual effect highlight
        select.style.borderColor = 'var(--accent-color)';
        setTimeout(() => {
            select.style.borderColor = 'var(--border-color)';
        }, 1000);
    }

    function filterStudents() {
        const query = document.getElementById('student-search').value.toLowerCase();
        const select = document.getElementById('student-select');
        const options = select.options;
        
        for (let i = 0; i < options.length; i++) {
            const text = options[i].text.toLowerCase();
            if (options[i].value === "" || text.includes(query)) {
                options[i].style.display = 'block';
            } else {
                options[i].style.display = 'none';
            }
        }
    }

    function filterTeachers() {
        const query = document.getElementById('teacher-search').value.toLowerCase();
        const select = document.getElementById('teacher-select');
        const options = select.options;
        
        for (let i = 0; i < options.length; i++) {
            const text = options[i].text.toLowerCase();
            if (options[i].value === "" || text.includes(query)) {
                options[i].style.display = 'block';
            } else {
                options[i].style.display = 'none';
            }
        }
    }

    function setReportType(type) {
        document.getElementById('report_type_input').value = type;
        
        // Update styling
        document.getElementById('type-behavioral').classList.remove('active');
        document.getElementById('type-academic').classList.remove('active');
        
        if (type === 'behavioral') {
            document.getElementById('type-behavioral').classList.add('active');
        } else {
            document.getElementById('type-academic').classList.add('active');
        }
    }
</script>
@endpush
