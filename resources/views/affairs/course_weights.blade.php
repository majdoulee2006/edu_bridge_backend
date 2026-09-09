@extends('layouts.affairs')

@section('title', 'تثقيلات المواد والنتائج')

@push('styles')
<style>
    .drilldown-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    .level-section {
        background: var(--bg-secondary);
        border-radius: 1.5rem;
        padding: 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        display: none; /* hidden by default */
        animation: fadeIn 0.3s ease-in-out;
    }
    .level-section.active {
        display: block;
    }
    .level-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .cards-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .grid-card {
        background: var(--bg-primary);
        border: 2px solid var(--border-color);
        border-radius: 1rem;
        padding: 1.25rem;
        min-width: 150px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 700;
        color: var(--text-primary);
        user-select: none;
    }
    .grid-card:hover {
        border-color: var(--accent-color);
        transform: translateY(-2px);
    }
    .grid-card.selected {
        background: var(--accent-color);
        border-color: var(--accent-color);
        color: #1a1a1a;
    }
    
    .table-container {
        overflow-x: auto;
    }
    .students-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 1rem;
    }
    .students-table th {
        background: var(--bg-primary);
        padding: 1rem;
        text-align: right;
        font-weight: 800;
        color: var(--text-secondary);
        border-bottom: 2px solid var(--border-color);
    }
    .students-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        font-weight: 600;
    }
    .students-table tr:last-child td {
        border-bottom: none;
    }
    .badge-primary {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.85rem;
    }
    .badge-accent {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.85rem;
    }
    .badge-success {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.85rem;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<div class="page-header" style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.8rem; font-weight: 900; margin-bottom: 0.5rem;">تثقيلات المواد ونتائج الطلاب</h2>
    <p style="color: var(--text-secondary);">استعراض معدلات الطلاب بناءً على التثقيلات المحددة لكل مادة في كل قسم.</p>
</div>

<div class="drilldown-container">
    
    <!-- المستوى الأول: الأقسام -->
    <div class="level-section active" id="section-departments">
        <div class="level-title">
            <i class="fa-solid fa-building"></i> 1. اختر القسم
        </div>
        <div class="cards-grid" id="grid-departments">
            @foreach($data['departments'] as $dept)
                <div class="grid-card" onclick="selectDepartment({{ $dept->department_id }}, this)">
                    {{ $dept->name }}
                </div>
            @endforeach
        </div>
    </div>

    <!-- المستوى الثاني: الدورات -->
    <div class="level-section" id="section-programs">
        <div class="level-title">
            <i class="fa-solid fa-graduation-cap"></i> 2. اختر الدورة
        </div>
        <div class="cards-grid" id="grid-programs">
            <!-- يملأ عن طريق جافاسكربت -->
        </div>
    </div>

    <!-- المستوى الثالث: المواد -->
    <div class="level-section" id="section-courses">
        <div class="level-title">
            <i class="fa-solid fa-book"></i> 3. اختر المادة
        </div>
        <div class="cards-grid" id="grid-courses">
            <!-- يملأ عن طريق جافاسكربت -->
        </div>
    </div>

    <!-- المستوى الرابع: الطلاب -->
    <div class="level-section" id="section-students">
        <div class="level-title" style="justify-content: space-between;">
            <div><i class="fa-solid fa-users"></i> 4. نتائج الطلاب</div>
            <span id="selected-course-title" style="font-size: 1rem; color: var(--accent-color); background: var(--bg-primary); padding: 0.25rem 1rem; border-radius: 1rem; border: 1px solid var(--border-color);"></span>
        </div>
        <div class="table-container">
            <table class="students-table">
                <thead>
                    <tr>
                        <th>اسم الطالب</th>
                        <th style="text-align: center;">المجموع النهائي</th>
                        <th style="text-align: center;">التثقيل (الوزن)</th>
                        <th style="text-align: center;">المعدل الموزون</th>
                    </tr>
                </thead>
                <tbody id="grid-students">
                    <!-- يملأ عن طريق جافاسكربت -->
                </tbody>
            </table>
        </div>
        <div id="students-empty" style="display: none; text-align: center; padding: 2rem; color: var(--text-secondary); font-weight: 700;">
            لا يوجد طلاب مسجلين في هذه المادة حالياً.
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    // تمرير البيانات من السيرفر
    const programsData = @json($data['programs']);
    const coursesData = @json($data['courses']);
    const studentsData = @json($data['students']);

    let currentDeptId = null;
    let currentProgramId = null;
    let currentCourseId = null;

    function selectDepartment(deptId, cardElement) {
        currentDeptId = deptId;
        currentProgramId = null;
        currentCourseId = null;

        // Reset lower levels
        document.getElementById('section-programs').classList.remove('active');
        document.getElementById('section-courses').classList.remove('active');
        document.getElementById('section-students').classList.remove('active');

        // Update active class
        document.querySelectorAll('#grid-departments .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

        // Populate programs
        const programsGrid = document.getElementById('grid-programs');
        programsGrid.innerHTML = '';
        
        const filteredPrograms = programsData.filter(p => p.department_id == deptId);
        
        if (filteredPrograms.length === 0) {
            programsGrid.innerHTML = '<div style="color: var(--text-secondary); font-weight: bold;">لا توجد دورات مسجلة في هذا القسم.</div>';
        } else {
            filteredPrograms.forEach(prog => {
                const div = document.createElement('div');
                div.className = 'grid-card';
                div.innerText = prog.name;
                div.onclick = () => selectProgram(prog.id, div);
                programsGrid.appendChild(div);
            });
        }
        
        document.getElementById('section-programs').classList.add('active');
        document.getElementById('section-programs').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function selectProgram(progId, cardElement) {
        currentProgramId = progId;
        currentCourseId = null;

        // Reset lower levels
        document.getElementById('section-courses').classList.remove('active');
        document.getElementById('section-students').classList.remove('active');

        // Update active class
        document.querySelectorAll('#grid-programs .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

        // Populate courses
        const coursesGrid = document.getElementById('grid-courses');
        coursesGrid.innerHTML = '';
        
        const filteredCourses = coursesData.filter(c => c.program_id == progId);
        
        if (filteredCourses.length === 0) {
            coursesGrid.innerHTML = '<div style="color: var(--text-secondary); font-weight: bold;">لا توجد مواد مسجلة في هذه الدورة.</div>';
        } else {
            filteredCourses.forEach(course => {
                const div = document.createElement('div');
                div.className = 'grid-card';
                div.innerText = course.title;
                div.onclick = () => selectCourse(course.course_id, course.title, div);
                coursesGrid.appendChild(div);
            });
        }

        document.getElementById('section-courses').classList.add('active');
        document.getElementById('section-courses').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function selectCourse(courseId, courseTitle, cardElement) {
        currentCourseId = courseId;

        // Update active class
        document.querySelectorAll('#grid-courses .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

        // Populate students
        document.getElementById('selected-course-title').innerText = courseTitle;
        const studentsGrid = document.getElementById('grid-students');
        const emptyState = document.getElementById('students-empty');
        studentsGrid.innerHTML = '';
        
        const filteredStudents = studentsData.filter(s => s.course_id == courseId);
        
        if (filteredStudents.length === 0) {
            emptyState.style.display = 'block';
            studentsGrid.parentElement.style.display = 'none';
        } else {
            emptyState.style.display = 'none';
            studentsGrid.parentElement.style.display = 'table';
            
            filteredStudents.forEach(student => {
                const finalGrade = parseFloat(student.final_grade || 0);
                const weight = parseFloat(student.weight || 1);
                // Weighted Average Equation: (Final Grade / 100) * Weight
                const weightedAvg = (finalGrade / 100) * weight;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${student.student_name}</td>
                    <td style="text-align: center;"><span class="badge-primary">${finalGrade.toFixed(2)}</span></td>
                    <td style="text-align: center;"><span class="badge-accent">${weight}</span></td>
                    <td style="text-align: center;"><span class="badge-success" style="font-weight: 900;">${weightedAvg.toFixed(2)}</span></td>
                `;
                studentsGrid.appendChild(tr);
            });
        }

        document.getElementById('section-students').classList.add('active');
        document.getElementById('section-students').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
</script>
@endpush
