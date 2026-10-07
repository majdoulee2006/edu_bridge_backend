<?php

namespace App\Services\Ai;

use App\Models\Parents;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AbsenceWarningService;
use App\Services\StudentAcademicService;
use App\Support\Access;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يبني بيانات المستخدم الحية كمصفوفة منظمة (تستهلكها المحرك المحلي)،
 * ويحوّلها إلى نص للـ prompt. مقيّد بالفصل النشط عند توفره.
 */
class AiContextBuilder
{
    private const DAY_AR = [
        'sunday' => 'الأحد', 'monday' => 'الاثنين', 'tuesday' => 'الثلاثاء', 'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس', 'friday' => 'الجمعة', 'saturday' => 'السبت',
    ];

    public const YEAR_AR = [1 => 'السنة الأولى', 2 => 'السنة الثانية', 3 => 'السنة الثالثة'];

    private const DAY_ORDER = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];

    /** @return array<string, mixed> */
    public function build($user, string $role): array
    {
        $role = AiRole::normalize($role);
        $data = ['role' => $role, 'name' => null];

        if (!$user) {
            return $data;
        }

        $data['name'] = $user->full_name ?? $user->name ?? null;

        try {
            $data += match ($role) {
                'student' => $this->student($user),
                'teacher' => $this->teacher($user),
                'parent'  => $this->parent($user),
                'hod'     => $this->hod($user),
                'affairs' => $this->affairs(),
                'admin'   => $this->admin(),
            };
        } catch (\Throwable $e) {
            // نقص بيانات لا يجب أن يُسقط المساعد
            \Log::warning('AiContextBuilder failed for role ' . $role . ': ' . $e->getMessage());
        }

        return $data;
    }

    // ───────────────────────── الطالب ─────────────────────────

    /** @return array<string, mixed> */
    public function student($user): array
    {
        $student = Student::where('user_id', $user->user_id)->first();
        if (!$student) {
            return [];
        }

        $semester = DB::table('semesters')->where('is_active', 1)->first();
        $semId    = $semester->semester_id ?? null;

        $out = [
            'code'     => $student->student_code ?? $user->university_id ?? null,
            'level'    => $student->level ?? $user->academic_year ?? null,
            'branch'   => $user->branch ?? DB::table('programs')->where('id', $student->program_id)->value('name'),
            'semester' => $semester->name ?? null,
        ];

        // المقررات: ضمن الفصل النشط إن وُجد تسجيل فيه، وإلا الكل
        $base = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.student_id', $student->student_id);

        $courses = ($semId && $this->hasColumn('enrollments', 'semester_id'))
            ? (clone $base)->where('enrollments.semester_id', $semId)->get(['courses.course_id', 'courses.title'])
            : collect();
        $out['courses_scoped_to_semester'] = $courses->isNotEmpty();
        if ($courses->isEmpty()) {
            $courses = $base->get(['courses.course_id', 'courses.title']);
        }
        $courseIds  = $courses->pluck('course_id')->unique()->values()->all();
        $out['courses'] = $courses->pluck('title')->unique()->values()->all();
        $out['course_teachers'] = $this->safe(fn () => $this->courseTeachers($courseIds), []);

        // كل قسم معزول: فشل أحدها (عمود ناقص مثلاً) لا يُسقط بقية بيانات المستخدم
        $out['schedule']    = $this->safe(fn () => $this->studentSchedule($user, $student, $courseIds), []);
        $out['attendance']  = $this->safe(fn () => $this->attendanceByCourse($student->student_id, $courseIds, $semId), []);
        $out['grades']      = $this->safe(fn () => $this->grades($student->student_id, $courseIds), []);
        $out['assignments'] = $this->safe(fn () => $this->pendingAssignments($student->student_id, $courseIds), []);
        $out['exams']       = $this->safe(fn () => $this->upcomingExams($courseIds), []);

        return $out;
    }

    /**
     * [مقرر => [أسماء الأساتذة]] للمقررات المعطاة.
     *
     * @return array<string, string[]>
     */
    protected function courseTeachers(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $rows = DB::table('courses as c')
            ->leftJoin('course_teachers as ct', 'ct.course_id', '=', 'c.course_id')
            ->leftJoin('teachers as t', 't.teacher_id', '=', 'ct.teacher_id')
            ->leftJoin('users as u', 'u.user_id', '=', 't.user_id')
            ->whereIn('c.course_id', $courseIds)
            ->orderBy('c.title')
            ->get(['c.title', 'u.full_name']);

        $map = [];
        foreach ($rows as $r) {
            $map[$r->title] ??= [];
            if ($r->full_name && !in_array($r->full_name, $map[$r->title], true)) {
                $map[$r->title][] = $r->full_name;
            }
        }

        return $map;
    }

    protected function studentSchedule($user, $student, array $courseIds): array
    {
        $rows = collect();
        if (!empty($courseIds)) {
            $rows = \App\Models\Schedule::whereIn('course_id', $courseIds)->with(['course', 'course.teachers.user'])->get();
        } else {
            // احتياط: مطابقة المجموعة الدراسية نصاً
            $year  = str_replace('السنة ال', 'سنة ', $user->academic_year ?? $student->level ?? '');
            $prog  = DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->branch ?? '';
            if ($prog !== '' && $year !== '') {
                $rows = \App\Models\Schedule::where('class_group', $prog . ' - ' . $year)->with(['course', 'course.teachers.user'])->get();
            }
        }

        return $rows->map(fn ($s) => [
            'day'     => $this->dayAr($s->day),
            'order'   => self::DAY_ORDER[strtolower((string) $s->day)] ?? 9,
            'course'  => $s->course->title ?? 'مقرر',
            'start'   => substr((string) $s->start_time, 0, 5),
            'end'     => substr((string) $s->end_time, 0, 5),
            'room'    => $s->room ?: null,
            'teacher' => $s->course->teachers->first()->user->full_name ?? null,
        ])->sortBy([['order', 'asc'], ['start', 'asc']])->values()->all();
    }

    /**
     * الغياب: تفصيل لكل مقرر للعرض فقط، أما الإنذار فمن عدد أيام الغياب غير المعذورة
     * عبر AbsenceWarningService (نفس ما يطبّقه النظام فعلاً).
     */
    protected function attendanceByCourse(int $studentId, array $courseIds, $semId): array
    {
        $q = DB::table('attendance as a')
            ->join('lessons as l', 'a.lesson_id', '=', 'l.lesson_id')
            ->join('courses as c', 'l.course_id', '=', 'c.course_id')
            ->where('a.student_id', $studentId);

        if ($semId && $this->hasColumn('attendance', 'semester_id')) {
            $q->where(fn ($w) => $w->where('a.semester_id', $semId)->orWhereNull('a.semester_id'));
        }
        if (!empty($courseIds)) {
            $q->whereIn('l.course_id', $courseIds);
        }

        $rows = $q->groupBy('l.course_id', 'c.title')->selectRaw(
            "c.title as title, COUNT(*) as total,
             SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) as present,
             SUM(CASE WHEN a.status = 'absent' AND COALESCE(a.excuse_status,'none') = 'approved' THEN 1 ELSE 0 END) as excused,
             SUM(CASE WHEN a.status = 'absent' AND COALESCE(a.excuse_status,'none') <> 'approved' THEN 1 ELSE 0 END) as absent"
        )->get();

        $courses = [];
        $tot = ['total' => 0, 'present' => 0, 'absent' => 0, 'excused' => 0];
        foreach ($rows as $r) {
            $total = (int) $r->total;
            $absent = (int) $r->absent;
            $courses[] = [
                'title'   => $r->title,
                'total'   => $total,
                'present' => (int) $r->present,
                'absent'  => $absent,
                'excused' => (int) $r->excused,
            ];
            $tot['total'] += $total;
            $tot['present'] += (int) $r->present;
            $tot['absent'] += $absent;
            $tot['excused'] += (int) $r->excused;
        }

        $days = AbsenceWarningService::countAbsenceDays($studentId);

        return ['courses' => $courses, 'absence_days' => $days, 'level' => AbsenceWarningService::levelFor($days)] + $tot;
    }

    protected function grades(int $studentId, array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }
        $summary = StudentAcademicService::getAcademicSummary($studentId);
        $ids = array_flip($courseIds);

        $courses = collect($summary['academic_card'])
            ->filter(fn ($c) => isset($ids[$c['course_id']]))
            ->map(fn ($c) => ['title' => $c['title'], 'total' => $c['total_score'], 'status' => $c['status']])
            ->values()->all();

        return [
            'average' => $summary['average'],
            'passed'  => $summary['passed_courses'],
            'failed'  => $summary['failed_courses'],
            'courses' => $courses,
        ];
    }

    protected function pendingAssignments(int $studentId, array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        return DB::table('assignments as a')
            ->join('courses as c', 'a.course_id', '=', 'c.course_id')
            ->whereIn('a.course_id', $courseIds)
            ->where('a.due_date', '>=', now())
            ->whereNotExists(fn ($s) => $s->select(DB::raw(1))->from('assignment_submissions as s')
                ->whereColumn('s.assignment_id', 'a.assignment_id')->where('s.student_id', $studentId))
            ->orderBy('a.due_date')->limit(5)
            ->get(['a.title', 'c.title as course', 'a.due_date'])
            ->map(fn ($r) => ['title' => $r->title, 'course' => $r->course, 'due' => substr((string) $r->due_date, 0, 16)])
            ->all();
    }

    protected function upcomingExams(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        return DB::table('exams as e')
            ->join('courses as c', 'e.course_id', '=', 'c.course_id')
            ->whereIn('e.course_id', $courseIds)
            ->where('e.exam_date', '>=', now())
            ->orderBy('e.exam_date')->limit(6)
            ->get(['e.exam_name', 'c.title as course', 'e.exam_date', 'e.room'])
            ->map(fn ($r) => ['name' => $r->exam_name, 'course' => $r->course, 'date' => substr((string) $r->exam_date, 0, 16), 'room' => $r->room ?: null])
            ->all();
    }

    // ───────────────────────── المعلم ─────────────────────────

    /** @return array<string, mixed> */
    public function teacher($user): array
    {
        $teacher = Teacher::where('user_id', $user->user_id)->first();
        if (!$teacher) {
            return [];
        }

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->get(['courses.course_id', 'courses.title', 'courses.year']);

        $ids = $courses->pluck('course_id')->all();
        $schedule = empty($ids) ? [] : \App\Models\Schedule::whereIn('course_id', $ids)->with('course')->get()
            ->map(fn ($s) => [
                'day'    => $this->dayAr($s->day),
                'order'  => self::DAY_ORDER[strtolower((string) $s->day)] ?? 9,
                'course' => $s->course->title ?? 'مقرر',
                'start'  => substr((string) $s->start_time, 0, 5),
                'end'    => substr((string) $s->end_time, 0, 5),
                'room'   => $s->room ?: null,
            ])->sortBy([['order', 'asc'], ['start', 'asc']])->values()->all();

        $pending = DB::table('assignment_submissions as s')
            ->join('assignments as a', 's.assignment_id', '=', 'a.assignment_id')
            ->where('a.teacher_id', $teacher->teacher_id)->whereNull('s.grade')->count();

        return [
            'courses'          => $courses->pluck('title')->unique()->values()->all(),
            'schedule'         => $schedule,
            'pending_grading'  => $pending,
            'advisor'          => trim(($teacher->advisor_branch ?? '') . ' - ' . ($teacher->advisor_year ?? ''), ' -') ?: null,
            'teaching'         => $this->safe(fn () => $this->teachingDetails($courses), []),
        ] + $this->safe(fn () => $this->teacherStudents($courses), []);
    }

    /**
     * مقررات المعلم مع السنة والدورات (البرامج) التي يُدرَّس فيها المقرر.
     *
     * @return array<int, array{title:string, year:?int, programs:string[]}>
     */
    protected function teachingDetails($courses): array
    {
        $ids = $courses->pluck('course_id')->all();
        if (empty($ids)) {
            return [];
        }

        $programs = DB::table('course_program as cp')
            ->join('programs as p', 'p.id', '=', 'cp.program_id')
            ->whereIn('cp.course_id', $ids)
            ->get(['cp.course_id', 'p.name'])
            ->groupBy('course_id')
            ->map(fn ($rows) => $rows->pluck('name')->unique()->values()->all());

        return $courses->map(fn ($c) => [
            'title'    => $c->title,
            'year'     => $c->year !== null ? (int) $c->year : null,
            'programs' => $programs[$c->course_id] ?? [],
        ])->unique(fn ($c) => $c['title'] . '|' . $c['year'])->values()->all();
    }

    /**
     * طلاب المعلم (المسجلون في مقرراته) مجمّعين حسب الدورة (البرنامج) والسنة،
     * مثل «معلوماتية - السنة الأولى»: العدد وأول 30 اسماً، وإجمالي الطلاب الفريدين.
     *
     * @return array{program_students: array<string, array{count:int,names:string[]}>, students_total:int}
     */
    protected function teacherStudents($courses): array
    {
        $ids = $courses->pluck('course_id')->all();
        if (empty($ids)) {
            return [];
        }

        $rows = DB::table('enrollments as e')
            ->join('students as s', 's.student_id', '=', 'e.student_id')
            ->join('users as u', 'u.user_id', '=', 's.user_id')
            ->leftJoin('programs as p', 'p.id', '=', 's.program_id')
            ->whereIn('e.course_id', $ids)
            ->orderBy('p.name')->orderBy('s.level')->orderBy('u.full_name')
            ->get(['s.student_id', 'u.full_name', 's.level', 'p.name as program']);

        $groups = [];
        foreach ($rows->unique('student_id') as $r) {
            $key = trim(($r->program ?: 'دورة غير محددة') . ($r->level ? ' - ' . $r->level : ''));
            $groups[$key][] = $r->full_name;
        }

        $out = [];
        foreach ($groups as $key => $names) {
            $out[$key] = ['count' => count($names), 'names' => array_slice($names, 0, 30)];
        }

        return ['program_students' => $out, 'students_total' => $rows->pluck('student_id')->unique()->count()];
    }

    // ───────────────────────── ولي الأمر ─────────────────────────

    /** @return array<string, mixed> */
    public function parent($user): array
    {
        $parent = Parents::where('user_id', $user->user_id)->first();
        if (!$parent) {
            return [];
        }

        $linked = DB::table('parent_students')
            ->whereIn('parent_id', [$user->user_id, $parent->parent_id])
            ->pluck('student_id');

        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->where(fn ($q) => $q->whereIn('students.user_id', $linked)->orWhereIn('students.student_id', $linked))
            ->select('students.student_id', 'students.student_code', 'students.level', 'users.full_name', DB::raw('COALESCE(users.branch, programs.name) as branch'))
            ->get()->unique('student_id')->take(6);

        $semId = DB::table('semesters')->where('is_active', 1)->value('semester_id');

        $children = [];
        foreach ($students as $s) {
            $att = $this->safe(fn () => $this->attendanceByCourse($s->student_id, [], $semId), []);
            $childCourseIds = DB::table('enrollments')->where('student_id', $s->student_id)->pluck('course_id')->unique()->values()->all();
            $children[] = [
                'name'       => $s->full_name,
                'code'       => $s->student_code,
                'level'      => $s->level,
                'branch'     => $s->branch,
                'attendance' => $att,
                'course_teachers' => $this->safe(fn () => $this->courseTeachers($childCourseIds), []),
                'average'    => $this->safe(fn () => StudentAcademicService::getAcademicSummary($s->student_id)['average'] ?? null, null),
            ];
        }

        return ['children' => $children];
    }

    // ───────────────────────── بقية الأدوار ─────────────────────────

    /** @return array<string, mixed> */
    public function hod($user): array
    {
        $dept = Access::headDepartment($user);
        $out  = ['department' => $dept['name']];

        if ($dept['id']) {
            $out += $this->safe(fn () => $this->departmentOverview((int) $dept['id'], (string) $dept['name'], (int) $user->user_id), []);
        }

        return $out;
    }

    /**
     * نظرة على قسم رئيس القسم: الدورات (البرامج)، الأساتذة ومقرراتهم والمرشدون، والطلاب حسب الدورة والسنة.
     * الأستاذ يُحسب من القسم إذا كان حقل قسمه يطابق، أو إذا درّس مقرراً ضمن دورات القسم.
     *
     * @return array<string, mixed>
     */
    protected function departmentOverview(int $deptId, string $deptName, int $headUserId = 0): array
    {
        $programs = DB::table('programs')->where('department_id', $deptId)->orderBy('name')->pluck('name', 'id');
        $progIds  = $programs->keys()->all();

        // ── الأساتذة
        $teachers = DB::table('teachers as t')
            ->join('users as u', 'u.user_id', '=', 't.user_id')
            ->where(function ($w) use ($deptName, $progIds) {
                $w->where('u.department', $deptName);
                if ($progIds) {
                    $w->orWhereIn('t.teacher_id', function ($q) use ($progIds) {
                        $q->select('ct.teacher_id')->from('course_teachers as ct')
                            ->join('course_program as cp', 'cp.course_id', '=', 'ct.course_id')
                            ->whereIn('cp.program_id', $progIds);
                    });
                }
            })
            ->orderBy('u.full_name')
            ->get(['t.teacher_id', 'u.full_name', 't.advisor_branch', 't.advisor_year']);
        $teacherIds = $teachers->pluck('teacher_id')->all();

        $coursesByTeacher = DB::table('course_teachers as ct')
            ->join('courses as c', 'c.course_id', '=', 'ct.course_id')
            ->whereIn('ct.teacher_id', $teachers->pluck('teacher_id')->all() ?: [0])
            ->get(['ct.teacher_id', 'c.title'])
            ->groupBy('teacher_id')
            ->map(fn ($rows) => $rows->pluck('title')->unique()->values()->all());

        $teacherList = $teachers->map(fn ($t) => [
            'name'    => $t->full_name,
            'courses' => $coursesByTeacher[$t->teacher_id] ?? [],
            'advisor' => trim(($t->advisor_branch ?? '') . ' - ' . ($t->advisor_year ?? ''), ' -') ?: null,
        ])->all();

        // ── الطلاب حسب الدورة والسنة
        $students = DB::table('students as s')
            ->join('users as u', 'u.user_id', '=', 's.user_id')
            ->leftJoin('programs as p', 'p.id', '=', 's.program_id')
            ->where(function ($w) use ($deptName, $progIds) {
                $w->where('u.department', $deptName);
                if ($progIds) {
                    $w->orWhereIn('s.program_id', $progIds);
                }
            })
            ->orderBy('p.name')->orderBy('s.level')->orderBy('u.full_name')
            ->get(['s.student_id', 's.user_id', 'u.full_name', 's.level', 'p.name as program']);

        $studentRows = $students->unique('student_id')->values();
        $groups = [];
        foreach ($studentRows as $r) {
            $key = trim(($r->program ?: 'دورة غير محددة') . ($r->level ? ' - ' . $r->level : ''));
            $groups[$key][] = $r->full_name;
        }
        $dept_students = [];
        foreach ($groups as $key => $names) {
            $dept_students[$key] = ['count' => count($names), 'names' => array_slice($names, 0, 40)];
        }

        return [
            'programs'       => $programs->values()->all(),
            'dept_teachers'  => $teacherList,
            'teachers_count' => count($teacherList),
            'dept_students'  => $dept_students,
            'students_count' => $studentRows->count(),
            'student_index'  => $studentRows->take(300)->map(fn ($r) => [
                'id' => $r->student_id, 'name' => $r->full_name, 'program' => $r->program, 'level' => $r->level,
            ])->all(),
        ]
            + $this->safe(fn () => $this->departmentAbsence($studentRows), [])
            + $this->safe(fn () => $this->departmentPending($studentRows, $teacherIds, $deptId, $headUserId), [])
            + $this->safe(fn () => $this->departmentCurriculum($progIds), [])
            + $this->safe(fn () => $this->departmentPerformance($studentRows), []);
    }

    /**
     * غياب طلاب القسم (أيام غياب غير معذورة، نفس أساس AbsenceWarningService) ومستويات الإنذار.
     *
     * @return array<string, mixed>
     */
    protected function departmentAbsence($studentRows): array
    {
        $ids = $studentRows->pluck('student_id')->all();
        if (empty($ids)) {
            return [];
        }

        $days = DB::table('attendance')
            ->whereIn('student_id', $ids)
            ->where('status', 'absent')
            ->where('excuse_status', '!=', 'approved')
            ->whereNotNull('attendance_date')
            ->groupBy('student_id')
            ->selectRaw('student_id, COUNT(DISTINCT attendance_date) as d')
            ->pluck('d', 'student_id');

        $list   = [];
        $counts = ['first' => 0, 'second' => 0, 'final' => 0];
        foreach ($studentRows as $r) {
            $d = (int) ($days[$r->student_id] ?? 0);
            if ($d <= 0) {
                continue;
            }
            $level = AbsenceWarningService::levelFor($d);
            if ($level) {
                $counts[$level]++;
            }
            $list[] = [
                'name'  => $r->full_name,
                'group' => trim(($r->program ?: '') . ($r->level ? ' - ' . $r->level : '')),
                'days'  => $d,
                'level' => $level,
            ];
        }
        usort($list, fn ($a, $b) => $b['days'] <=> $a['days']);

        return ['absence_list' => array_slice($list, 0, 60), 'warning_counts' => $counts];
    }

    /**
     * ما ينتظر قرار رئيس القسم: إجازات وطلبات خدمات بحالة pending_hod وطلبات لقاء أولياء الأمور.
     *
     * @return array<string, mixed>
     */
    protected function departmentPending($studentRows, array $teacherIds, int $deptId, int $headUserId): array
    {
        $studentIds = $studentRows->pluck('student_id')->all();
        $userIds    = $studentRows->pluck('user_id')->all();

        $leaves = DB::table('leave_requests')->where('status', 'pending_hod')
            ->where(function ($w) use ($userIds, $teacherIds) {
                $w->whereIn('student_id', $userIds ?: [0])->orWhereIn('teacher_id', $teacherIds ?: [0]);
            })->count();

        $requestsQ = DB::table('student_requests as r')
            ->join('students as s', 's.student_id', '=', 'r.student_id')
            ->join('users as u', 'u.user_id', '=', 's.user_id')
            ->where('r.status', 'pending_hod')
            ->whereIn('r.student_id', $studentIds ?: [0]);
        $requests = (clone $requestsQ)->orderByDesc('r.created_at')->limit(5)->get(['r.type', 'u.full_name', 'r.created_at'])
            ->map(fn ($r) => ['type' => $r->type, 'student' => $r->full_name, 'date' => substr((string) $r->created_at, 0, 10)])->all();

        $meetings = DB::table('parent_meeting_requests')->where('status', 'pending')
            ->where(function ($w) use ($deptId, $headUserId) {
                $w->where('department_id', $deptId);
                if ($headUserId) {
                    $w->orWhere('target_user_id', $headUserId);
                }
            })->count();

        return [
            'pending' => [
                'leaves'          => $leaves,
                'requests'        => $requestsQ->count(),
                'recent_requests' => $requests,
                'meetings'        => $meetings,
            ],
        ];
    }

    /**
     * مقررات القسم (من ربط المقرر بدورات القسم) مع الأساتذة، وجدولها وامتحاناتها القادمة.
     *
     * @return array<string, mixed>
     */
    protected function departmentCurriculum(array $progIds): array
    {
        if (empty($progIds)) {
            return [];
        }

        $rows = DB::table('courses as c')
            ->join('course_program as cp', 'cp.course_id', '=', 'c.course_id')
            ->join('programs as p', 'p.id', '=', 'cp.program_id')
            ->whereIn('cp.program_id', $progIds)
            ->get(['c.course_id', 'c.title', 'c.year', 'p.name as program']);
        if ($rows->isEmpty()) {
            return [];
        }
        $courseIds = $rows->pluck('course_id')->unique()->values()->all();

        $teachers = DB::table('course_teachers as ct')
            ->join('teachers as t', 't.teacher_id', '=', 'ct.teacher_id')
            ->join('users as u', 'u.user_id', '=', 't.user_id')
            ->whereIn('ct.course_id', $courseIds)
            ->get(['ct.course_id', 'u.full_name'])
            ->groupBy('course_id')->map(fn ($g) => $g->pluck('full_name')->unique()->values()->all());

        $courses = [];
        $progOf  = [];
        foreach ($rows as $r) {
            $courses[$r->course_id] ??= ['title' => $r->title, 'year' => $r->year !== null ? (int) $r->year : null, 'programs' => [], 'teachers' => $teachers[$r->course_id] ?? []];
            if (!in_array($r->program, $courses[$r->course_id]['programs'], true)) {
                $courses[$r->course_id]['programs'][] = $r->program;
            }
            $progOf[$r->course_id] = $courses[$r->course_id]['programs'];
        }

        $schedule = \App\Models\Schedule::whereIn('course_id', $courseIds)->get()->map(fn ($s) => [
            'day'      => $this->dayAr($s->day),
            'order'    => self::DAY_ORDER[strtolower((string) $s->day)] ?? 9,
            'start'    => substr((string) $s->start_time, 0, 5),
            'end'      => substr((string) $s->end_time, 0, 5),
            'room'     => $s->room ?: null,
            'course'   => $courses[$s->course_id]['title'] ?? 'مقرر',
            'year'     => $courses[$s->course_id]['year'] ?? null,
            'programs' => $courses[$s->course_id]['programs'] ?? [],
        ])->sortBy([['order', 'asc'], ['start', 'asc']])->values()->take(300)->all();

        $exams = DB::table('exams as e')->whereIn('e.course_id', $courseIds)->where('e.exam_date', '>=', now())
            ->orderBy('e.exam_date')->limit(15)->get(['e.course_id', 'e.exam_name', 'e.exam_date', 'e.room'])
            ->map(fn ($e) => [
                'name'     => $e->exam_name, 'date' => substr((string) $e->exam_date, 0, 16), 'room' => $e->room ?: null,
                'course'   => $courses[$e->course_id]['title'] ?? 'مقرر',
                'programs' => $courses[$e->course_id]['programs'] ?? [],
            ])->all();

        return ['dept_courses' => array_values($courses), 'dept_schedule' => $schedule, 'dept_exams' => $exams];
    }

    /**
     * أداء القسم: نسبة الحضور لكل دورة، ومتوسط المعدل (إن كان عدد الطلاب معقولاً).
     *
     * @return array<string, mixed>
     */
    protected function departmentPerformance($studentRows): array
    {
        $ids = $studentRows->pluck('student_id')->all();
        if (empty($ids)) {
            return [];
        }

        $att = DB::table('attendance as a')
            ->join('students as s', 's.student_id', '=', 'a.student_id')
            ->leftJoin('programs as p', 'p.id', '=', 's.program_id')
            ->whereIn('a.student_id', $ids)
            ->groupBy('p.name')
            ->selectRaw("p.name as program, COUNT(*) as total, SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) as present")
            ->get()
            ->mapWithKeys(fn ($r) => [$r->program ?: 'دورة غير محددة' => [
                'sessions' => (int) $r->total,
                'rate'     => $r->total > 0 ? round($r->present / $r->total * 100, 1) : null,
            ]])->all();

        $avg = [];
        if ($studentRows->count() <= 60) {
            $sums = [];
            foreach ($studentRows as $r) {
                $a = (float) (StudentAcademicService::getAcademicSummary($r->student_id)['average'] ?? 0);
                if ($a > 0) {
                    $sums[$r->program ?: 'دورة غير محددة'][] = $a;
                }
            }
            foreach ($sums as $prog => $list) {
                $avg[$prog] = round(array_sum($list) / count($list), 1);
            }
        }

        return ['attendance_by_program' => $att, 'average_by_program' => $avg];
    }

    /** @return array<string, mixed> */
    public function affairs(): array
    {
        return ['pending_requests' => DB::table('student_requests')->where('status', 'pending_affairs')->count()];
    }

    /** @return array<string, mixed> */
    public function admin(): array
    {
        $counts = DB::table('users')->selectRaw('role_id, COUNT(*) as c')->groupBy('role_id')->pluck('c', 'role_id');

        return ['users' => [
            'students' => (int) ($counts[3] ?? 0),
            'teachers' => (int) ($counts[2] ?? 0),
            'parents'  => (int) ($counts[4] ?? 0),
        ]];
    }

    // ───────────────────────── نص الـ prompt ─────────────────────────

    public function toPromptText(array $d): string
    {
        $t = "بيانات المستخدم الحقيقية من قاعدة البيانات (هذه هي المصدر الوحيد للأرقام والمواعيد، لا تخمّن غيرها):\n";
        $t .= '- الاسم: ' . ($d['name'] ?? 'غير معروف') . "\n- الدور: " . AiRole::title($d['role']) . "\n";

        foreach (['code' => 'الرقم الجامعي', 'level' => 'السنة/المستوى', 'branch' => 'التخصص', 'semester' => 'الفصل النشط'] as $k => $label) {
            if (!empty($d[$k])) {
                $t .= "- {$label}: {$d[$k]}\n";
            }
        }
        if (!empty($d['courses'])) {
            $t .= '- المقررات' . (!empty($d['courses_scoped_to_semester']) ? ' (الفصل الحالي)' : ' (قد تشمل فصولاً سابقة)') . ': ' . implode('، ', $d['courses']) . "\n";
        }
        if (!empty($d['schedule'])) {
            $t .= "- الجدول الأسبوعي:\n";
            foreach ($d['schedule'] as $s) {
                $t .= "  * {$s['day']}: {$s['course']} {$s['start']}-{$s['end']}"
                    . (!empty($s['room']) ? " ({$s['room']})" : '') . (!empty($s['teacher']) ? " - {$s['teacher']}" : '') . "\n";
            }
        }
        if (isset($d['attendance']['absence_days'])) {
            $t .= "- أيام الغياب غير المعذورة (أساس الإنذارات): {$d['attendance']['absence_days']} يوم، مستوى الإنذار الحالي: "
                . (['first' => 'أول', 'second' => 'ثانٍ', 'final' => 'نهائي'][$d['attendance']['level']] ?? 'لا يوجد') . "\n";
            foreach ($d['attendance']['courses'] ?? [] as $c) {
                $t .= "  * {$c['title']}: {$c['total']} جلسة، غياب غير معذور {$c['absent']}، معذور {$c['excused']}\n";
            }
        }
        if (!empty($d['grades'])) {
            $t .= "- المعدل الموزون التراكمي: {$d['grades']['average']} (ناجح {$d['grades']['passed']}، راسب {$d['grades']['failed']})\n";
            foreach ($d['grades']['courses'] as $c) {
                $t .= '  * ' . $c['title'] . ': ' . ($c['total'] ?? 'لم تُرصد') . " - {$c['status']}\n";
            }
        }
        foreach ($d['course_teachers'] ?? [] as $title => $teachers) {
            $t .= "- مدرّس {$title}: " . ($teachers ? implode('، ', $teachers) : 'غير محدد') . "\n";
        }
        foreach ($d['assignments'] ?? [] as $a) {
            $t .= "- واجب غير مسلَّم: {$a['title']} ({$a['course']}) آخر موعد {$a['due']}\n";
        }
        foreach ($d['exams'] ?? [] as $e) {
            $t .= "- امتحان قادم: {$e['name']} ({$e['course']}) {$e['date']}" . (!empty($e['room']) ? " في {$e['room']}" : '') . "\n";
        }
        foreach ($d['children'] ?? [] as $c) {
            $abs = $c['attendance']['absent'] ?? 0;
            $tot = $c['attendance']['total'] ?? 0;
            foreach ($c['course_teachers'] ?? [] as $title => $teachers) {
                $t .= "- مقرر {$c['name']}: {$title} (المدرّس: " . ($teachers ? implode('، ', $teachers) : 'غير محدد') . ")\n";
            }
            $t .= "- الابن: {$c['name']} (كود {$c['code']}) {$c['level']}: أيام غياب غير معذورة " . ($c['attendance']['absence_days'] ?? 0) . "، غياب {$abs} من {$tot} جلسة، المعدل " . ($c['average'] ?? 'غير متوفر') . "\n";
        }
        foreach ($d['teaching'] ?? [] as $c) {
            $t .= "- يدرّس {$c['title']}: " . (self::YEAR_AR[$c['year']] ?? 'سنة غير محددة')
                . '، الدورات: ' . ($c['programs'] ? implode('، ', $c['programs']) : 'غير محددة') . "\n";
        }
        if (!empty($d['advisor'])) {
            $t .= "- هو مرشد (مربي) دورة: {$d['advisor']}\n";
        }
        if (isset($d['students_total'])) {
            $t .= "- إجمالي طلابك (مجمّعون حسب الدورة والسنة): {$d['students_total']}\n";
            foreach ($d['program_students'] as $title => $info) {
                $t .= "  * دورة {$title}: {$info['count']} طالباً (" . implode('، ', array_slice($info['names'], 0, 15)) . ($info['count'] > 15 ? ' ...' : '') . ")\n";
            }
        }
        if (isset($d['pending_grading'])) {
            $t .= "- تسليمات بانتظار التصحيح: {$d['pending_grading']}\n";
        }
        if (isset($d['pending_requests'])) {
            $t .= "- طلبات الطلاب المعلقة لدى الشؤون: {$d['pending_requests']}\n";
        }
        if (!empty($d['department'])) {
            $t .= "- القسم: {$d['department']}" . (isset($d['students_count']) ? "، طلاب: {$d['students_count']}، أساتذة: {$d['teachers_count']}" : '') . "\n";
        }
        if (!empty($d['programs'])) {
            $t .= '- دورات القسم: ' . implode('، ', $d['programs']) . "\n";
        }
        foreach ($d['dept_teachers'] ?? [] as $x) {
            $t .= "  * أستاذ {$x['name']}: " . ($x['courses'] ? implode('، ', array_slice($x['courses'], 0, 8)) : 'بلا مقررات')
                . (!empty($x['advisor']) ? " (مرشد دورة {$x['advisor']})" : '') . "\n";
        }
        if (!empty($d['warning_counts'])) {
            $w = $d['warning_counts'];
            $t .= "- إنذارات طلاب القسم (حسب أيام الغياب غير المعذورة): أول {$w['first']}، ثانٍ {$w['second']}، نهائي {$w['final']}\n";
            foreach (array_slice($d['absence_list'] ?? [], 0, 10) as $x) {
                $t .= "  * {$x['name']} ({$x['group']}): {$x['days']} يوم غياب" . ($x['level'] ? ' — إنذار ' . ['first' => 'أول', 'second' => 'ثانٍ', 'final' => 'نهائي'][$x['level']] : '') . "\n";
            }
        }
        if (isset($d['pending'])) {
            $p = $d['pending'];
            $t .= "- بانتظار قراره: إجازات {$p['leaves']}، طلبات خدمات {$p['requests']}، طلبات لقاء أولياء أمور {$p['meetings']}\n";
        }
        foreach ($d['attendance_by_program'] ?? [] as $prog => $x) {
            $t .= "- حضور دورة {$prog}: " . ($x['rate'] ?? 'غير متوفر') . "% من {$x['sessions']} جلسة"
                . (isset($d['average_by_program'][$prog]) ? "، متوسط المعدل {$d['average_by_program'][$prog]}" : '') . "\n";
        }
        foreach ($d['dept_exams'] ?? [] as $e) {
            $t .= "- امتحان قادم بالقسم: {$e['course']} ({$e['name']}) {$e['date']}\n";
        }
        foreach ($d['dept_students'] ?? [] as $group => $info) {
            $t .= "  * طلاب دورة {$group}: {$info['count']}" . ($info['count'] <= 15 ? ' (' . implode('، ', $info['names']) . ')' : '') . "\n";
        }
        if (isset($d['users'])) {
            $t .= "- المستخدمون: طلاب {$d['users']['students']}، أساتذة {$d['users']['teachers']}، أولياء أمور {$d['users']['parents']}\n";
        }

        return $t;
    }

    /** ينفّذ قسماً من السياق ويعيد القيمة الافتراضية (مع تسجيل الخطأ) إن فشل. */
    private function safe(callable $fn, $default)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            \Log::warning('AiContextBuilder section failed: ' . $e->getMessage());

            return $default;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $cache = [];

        return $cache["$table.$column"] ??= Schema::hasColumn($table, $column);
    }

    private function dayAr($day): string
    {
        return self::DAY_AR[strtolower((string) $day)] ?? (string) $day;
    }
}
