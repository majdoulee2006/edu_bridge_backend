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
    .badge-danger {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.85rem;
    }
    .export-btn {
        background: var(--bg-primary);
        border: 2px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 0.6rem 1.25rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }
    .export-btn.excel { color: #15803d; }
    .export-btn.excel:hover { border-color: #15803d; background: rgba(21, 128, 61, 0.08); }
    .export-btn.pdf { color: #b91c1c; }
    .export-btn.pdf:hover { border-color: #b91c1c; background: rgba(185, 28, 28, 0.08); }
    .export-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
        justify-content: flex-end;
    }
    .results-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .badge-success {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.85rem;
    }
    .sub-tabs {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .sub-tab-btn {
        background: var(--bg-primary);
        border: 2px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 0.5rem 1.25rem;
        font-weight: 700;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .sub-tab-btn:hover {
        border-color: var(--accent-color);
    }
    .sub-tab-btn.active {
        background: #10b981;
        border-color: #10b981;
        color: #fff;
    }
    .sub-tab-btn.sub-tab-fail.active {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }
    .overall-average-badge {
        font-size: 1.1rem;
        font-weight: 900;
        color: #1a1a1a;
        background: var(--accent-color);
        padding: 0.4rem 1.25rem;
        border-radius: 1rem;
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

    <!-- المستوى الثالث: السنة -->
    <div class="level-section" id="section-years">
        <div class="level-title">
            <i class="fa-solid fa-calendar-days"></i> 3. اختر السنة
        </div>
        <div class="cards-grid" id="grid-years">
            <div class="grid-card" onclick="selectYear(1, this)">سنة أولى</div>
            <div class="grid-card" onclick="selectYear(2, this)">سنة ثانية</div>
        </div>
    </div>

    <!-- المستوى الرابع: طريقة العرض -->
    <div class="level-section" id="section-filter">
        <div class="level-title">
            <i class="fa-solid fa-filter"></i> 4. اختر طريقة العرض
        </div>
        <div class="cards-grid" id="grid-filter">
            <div class="grid-card" onclick="selectFilterMode('courses', this)"><i class="fa-solid fa-book"></i> مواد</div>
            <div class="grid-card" onclick="selectFilterMode('all', this)"><i class="fa-solid fa-users"></i> الجميع</div>
        </div>
    </div>

    <!-- فرع "مواد": اختيار المادة -->
    <div class="level-section" id="section-courses">
        <div class="level-title">
            <i class="fa-solid fa-book"></i> 5. اختر المادة
        </div>
        <div class="cards-grid" id="grid-courses">
            <!-- يملأ عن طريق جافاسكربت -->
        </div>
    </div>

    <!-- فرع "مواد": نتائج طلاب المادة المختارة -->
    <div class="level-section" id="section-students">
        <div class="level-title" style="justify-content: space-between;">
            <div><i class="fa-solid fa-users"></i> 6. نتائج الطلاب</div>
            <span id="selected-course-title" style="font-size: 1rem; color: var(--accent-color); background: var(--bg-primary); padding: 0.25rem 1rem; border-radius: 1rem; border: 1px solid var(--border-color);"></span>
        </div>

        <div class="results-toolbar">
            <div class="sub-tabs" style="margin-bottom: 0;">
                <button type="button" class="sub-tab-btn active" id="subtab-btn-pass" onclick="setCourseResultsTab('pass')">
                    <i class="fa-solid fa-circle-check"></i> الناجحون (<span id="pass-count">0</span>)
                </button>
                <button type="button" class="sub-tab-btn sub-tab-fail" id="subtab-btn-fail" onclick="setCourseResultsTab('fail')">
                    <i class="fa-solid fa-circle-xmark"></i> الراسبون (<span id="fail-count">0</span>)
                </button>
            </div>
            <div class="export-actions" style="margin-top: 0;">
                <button type="button" class="export-btn excel" onclick="exportCourseResults('excel')">
                    <i class="fa-solid fa-file-excel"></i> تصدير Excel
                </button>
                <button type="button" class="export-btn pdf" onclick="exportCourseResults('pdf')">
                    <i class="fa-solid fa-file-pdf"></i> تصدير PDF
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="students-table">
                <thead>
                    <tr>
                        <th>اسم الطالب</th>
                        <th style="text-align: center;">علامة الامتحان</th>
                        <th style="text-align: center;">التثقيل (الوزن)</th>
                        <th style="text-align: center;">المعدل</th>
                    </tr>
                </thead>
                <tbody id="grid-students">
                    <!-- يملأ عن طريق جافاسكربت -->
                </tbody>
            </table>
        </div>
        <div id="students-empty" style="display: none; text-align: center; padding: 2rem; color: var(--text-secondary); font-weight: 700;">
            لا يوجد طلاب في هذا التصنيف لهذه المادة.
        </div>
    </div>

    <!-- فرع "الجميع": قائمة أسماء الطلاب بهذه الدورة والسنة -->
    <div class="level-section" id="section-all-students">
        <div class="level-title">
            <i class="fa-solid fa-user-graduate"></i> 5. اختر الطالب
        </div>
        <div class="cards-grid" id="grid-all-students">
            <!-- يملأ عن طريق جافاسكربت -->
        </div>
    </div>

    <!-- فرع "الجميع": كل مواد الطالب المختار مع المعدل العام -->
    <div class="level-section" id="section-student-detail">
        <div class="level-title" style="justify-content: space-between;">
            <div><i class="fa-solid fa-list-check"></i> 6. نتائج الطالب <span id="selected-student-name" style="color: var(--accent-color);"></span></div>
            <span class="overall-average-badge">المعدل العام: <span id="student-overall-average">0.00</span>%</span>
        </div>

        <div class="export-actions" style="margin-top: 0; margin-bottom: 1.25rem;">
            <button type="button" class="export-btn excel" onclick="exportStudentResults('excel')">
                <i class="fa-solid fa-file-excel"></i> تصدير Excel
            </button>
            <button type="button" class="export-btn pdf" onclick="exportStudentResults('pdf')">
                <i class="fa-solid fa-file-pdf"></i> تصدير PDF
            </button>
        </div>

        <div class="table-container">
            <table class="students-table">
                <thead>
                    <tr>
                        <th>اسم المادة</th>
                        <th style="text-align: center;">علامة الامتحان</th>
                        <th style="text-align: center;">التثقيل</th>
                        <th style="text-align: center;">المعدل</th>
                    </tr>
                </thead>
                <tbody id="grid-student-courses">
                    <!-- يملأ عن طريق جافاسكربت -->
                </tbody>
            </table>
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

    const courseYearMap = new Map();
    coursesData.forEach(c => { if (!courseYearMap.has(c.course_id)) courseYearMap.set(c.course_id, c.year); });

    const PASS_THRESHOLD = 50; // نسبة النجاح الدنيا
    const exportCourseUrl = '{{ route('affairs.course_weights.export_course') }}';
    const exportStudentUrl = '{{ route('affairs.course_weights.export_student') }}';

    let currentDeptId = null;
    let currentProgramId = null;
    let currentYear = null;
    let currentFilterMode = null;
    let currentCourseId = null;
    let currentSelectedStudentId = null;

    function exportCourseResults(format) {
        if (!currentCourseId || !currentProgramId) return;
        const status = document.getElementById('subtab-btn-fail').classList.contains('active') ? 'fail' : 'pass';
        const params = new URLSearchParams({ course_id: currentCourseId, program_id: currentProgramId, status, format });
        window.location.href = exportCourseUrl + '?' + params.toString();
    }

    function exportStudentResults(format) {
        if (!currentSelectedStudentId || !currentProgramId || !currentYear) return;
        const params = new URLSearchParams({ student_id: currentSelectedStudentId, program_id: currentProgramId, year: currentYear, format });
        window.location.href = exportStudentUrl + '?' + params.toString();
    }

    function hideSections(ids) {
        ids.forEach(id => document.getElementById(id).classList.remove('active'));
    }

    function selectDepartment(deptId, cardElement) {
        currentDeptId = deptId;
        currentProgramId = null;
        currentYear = null;
        currentFilterMode = null;
        currentCourseId = null;

        hideSections(['section-programs', 'section-years', 'section-filter', 'section-courses', 'section-students', 'section-all-students', 'section-student-detail']);

        document.querySelectorAll('#grid-departments .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

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
        currentYear = null;
        currentFilterMode = null;
        currentCourseId = null;

        hideSections(['section-filter', 'section-courses', 'section-students', 'section-all-students', 'section-student-detail']);
        document.querySelectorAll('#grid-years .grid-card').forEach(c => c.classList.remove('selected'));

        document.querySelectorAll('#grid-programs .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

        document.getElementById('section-years').classList.add('active');
        document.getElementById('section-years').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function selectYear(year, cardElement) {
        currentYear = year;
        currentFilterMode = null;
        currentCourseId = null;

        hideSections(['section-courses', 'section-students', 'section-all-students', 'section-student-detail']);
        document.querySelectorAll('#grid-filter .grid-card').forEach(c => c.classList.remove('selected'));

        document.querySelectorAll('#grid-years .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

        document.getElementById('section-filter').classList.add('active');
        document.getElementById('section-filter').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function selectFilterMode(mode, cardElement) {
        currentFilterMode = mode;
        currentCourseId = null;

        hideSections(['section-courses', 'section-students', 'section-all-students', 'section-student-detail']);
        document.querySelectorAll('#grid-filter .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

        if (mode === 'courses') {
            renderCoursesGrid();
            document.getElementById('section-courses').classList.add('active');
            document.getElementById('section-courses').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            renderAllStudentsGrid();
            document.getElementById('section-all-students').classList.add('active');
            document.getElementById('section-all-students').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function renderCoursesGrid() {
        const coursesGrid = document.getElementById('grid-courses');
        coursesGrid.innerHTML = '';

        const filteredCourses = coursesData.filter(c => c.program_id == currentProgramId && c.year == currentYear);

        if (filteredCourses.length === 0) {
            coursesGrid.innerHTML = '<div style="color: var(--text-secondary); font-weight: bold;">لا توجد مواد مسجلة في هذه السنة.</div>';
            return;
        }

        filteredCourses.forEach(course => {
            const div = document.createElement('div');
            div.className = 'grid-card';
            div.innerText = course.title;
            div.onclick = () => selectCourse(course.course_id, course.title, div);
            coursesGrid.appendChild(div);
        });
    }

    let currentCoursePassStudents = [];
    let currentCourseFailStudents = [];

    function selectCourse(courseId, courseTitle, cardElement) {
        currentCourseId = courseId;

        document.querySelectorAll('#grid-courses .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

        document.getElementById('selected-course-title').innerText = courseTitle;

        // مادة مشتركة بين أكثر من دورة (مثل c++ أو شبكات) لازم تعرض فقط طلاب الدورة المختارة حالياً
        const filteredStudents = studentsData.filter(s => s.course_id == courseId && s.student_program_id == currentProgramId);

        currentCoursePassStudents = [];
        currentCourseFailStudents = [];

        filteredStudents.forEach(student => {
            const examScore = parseFloat(student.exam_score || 0);
            const examMaxScore = parseFloat(student.exam_max_score || 100);
            const percentage = examMaxScore > 0 ? (examScore / examMaxScore) * 100 : 0;

            if (percentage >= PASS_THRESHOLD) {
                currentCoursePassStudents.push(student);
            } else {
                currentCourseFailStudents.push(student);
            }
        });

        document.getElementById('pass-count').innerText = currentCoursePassStudents.length;
        document.getElementById('fail-count').innerText = currentCourseFailStudents.length;

        setCourseResultsTab('pass');

        document.getElementById('section-students').classList.add('active');
        document.getElementById('section-students').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function setCourseResultsTab(tab) {
        document.getElementById('subtab-btn-pass').classList.toggle('active', tab === 'pass');
        document.getElementById('subtab-btn-fail').classList.toggle('active', tab === 'fail');

        const list = tab === 'pass' ? currentCoursePassStudents : currentCourseFailStudents;
        const studentsGrid = document.getElementById('grid-students');
        const emptyState = document.getElementById('students-empty');
        studentsGrid.innerHTML = '';

        if (list.length === 0) {
            emptyState.style.display = 'block';
            studentsGrid.parentElement.style.display = 'none';
            return;
        }

        emptyState.style.display = 'none';
        studentsGrid.parentElement.style.display = 'table';

        list.forEach(student => {
            const examScore = parseFloat(student.exam_score || 0);
            const examMaxScore = parseFloat(student.exam_max_score || 100);
            const weight = parseFloat(student.weight || 1);
            // المعدل كنسبة مئوية من علامة الامتحان
            const percentage = examMaxScore > 0 ? (examScore / examMaxScore) * 100 : 0;

            const isFail = percentage < PASS_THRESHOLD;
            const scoreClass = isFail ? 'badge-danger' : 'badge-primary';
            const avgClass = isFail ? 'badge-danger' : 'badge-success';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${student.student_name}</td>
                <td style="text-align: center;"><span class="${scoreClass}">${examScore.toFixed(2)} / ${examMaxScore.toFixed(0)}</span></td>
                <td style="text-align: center;"><span class="badge-accent">${weight}</span></td>
                <td style="text-align: center;"><span class="${avgClass}" style="font-weight: 900;">${percentage.toFixed(2)}%</span></td>
            `;
            studentsGrid.appendChild(tr);
        });
    }

    function renderAllStudentsGrid() {
        const grid = document.getElementById('grid-all-students');
        grid.innerHTML = '';

        // طلاب هذه الدورة والسنة تحديداً (بدون تكرار)
        const rows = studentsData.filter(s => s.student_program_id == currentProgramId && courseYearMap.get(s.course_id) == currentYear);
        const uniqueMap = new Map();
        rows.forEach(s => {
            if (!uniqueMap.has(s.student_id)) {
                uniqueMap.set(s.student_id, { student_id: s.student_id, student_name: s.student_name });
            }
        });
        const uniqueStudents = Array.from(uniqueMap.values()).sort((a, b) => a.student_name.localeCompare(b.student_name, 'ar'));

        if (uniqueStudents.length === 0) {
            grid.innerHTML = '<div style="color: var(--text-secondary); font-weight: bold;">لا يوجد طلاب مسجلين في هذه الدورة/السنة.</div>';
            return;
        }

        uniqueStudents.forEach(s => {
            const div = document.createElement('div');
            div.className = 'grid-card';
            div.innerText = s.student_name;
            div.onclick = () => selectAllStudent(s.student_id, s.student_name, div);
            grid.appendChild(div);
        });
    }

    function selectAllStudent(studentId, studentName, cardElement) {
        currentSelectedStudentId = studentId;
        document.querySelectorAll('#grid-all-students .grid-card').forEach(c => c.classList.remove('selected'));
        cardElement.classList.add('selected');

        document.getElementById('selected-student-name').innerText = '- ' + studentName;
        const tbody = document.getElementById('grid-student-courses');
        tbody.innerHTML = '';

        const rows = studentsData.filter(s => s.student_id == studentId && s.student_program_id == currentProgramId && courseYearMap.get(s.course_id) == currentYear);

        let sumWeighted = 0;
        let sumWeight = 0;

        rows.forEach(row => {
            const examScore = parseFloat(row.exam_score || 0);
            const examMaxScore = parseFloat(row.exam_max_score || 100);
            const weight = parseFloat(row.weight || 1);
            const percentage = examMaxScore > 0 ? (examScore / examMaxScore) : 0;
            const weightedAvg = percentage * weight;
            const courseInfo = coursesData.find(c => c.course_id == row.course_id);
            const courseTitle = courseInfo ? courseInfo.title : ('مادة #' + row.course_id);

            sumWeighted += weightedAvg;
            sumWeight += weight;

            const isFail = (percentage * 100) < PASS_THRESHOLD;
            const scoreClass = isFail ? 'badge-danger' : 'badge-primary';
            const avgClass = isFail ? 'badge-danger' : 'badge-success';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${courseTitle}</td>
                <td style="text-align: center;"><span class="${scoreClass}">${examScore.toFixed(2)} / ${examMaxScore.toFixed(0)}</span></td>
                <td style="text-align: center;"><span class="badge-accent">${weight}</span></td>
                <td style="text-align: center;"><span class="${avgClass}" style="font-weight: 900;">${(percentage * 100).toFixed(2)}%</span></td>
            `;
            tbody.appendChild(tr);
        });

        // المعدل العام = مجموع (نسبة العلامة × التثقيل) لكل المواد ÷ مجموع التثقيلات × 100
        const overallAverage = sumWeight > 0 ? (sumWeighted / sumWeight) * 100 : 0;
        document.getElementById('student-overall-average').innerText = overallAverage.toFixed(2);

        document.getElementById('section-student-detail').classList.add('active');
        document.getElementById('section-student-detail').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
</script>
@endpush
