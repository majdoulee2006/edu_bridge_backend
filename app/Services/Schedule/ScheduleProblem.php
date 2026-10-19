<?php

namespace App\Services\Schedule;

use App\Models\Semester;
use Illuminate\Support\Facades\DB;

/**
 * مسألة جدولة قسم واحد: ما يجب وضعه (شعب ومواد وعدد حصصها ومعلموها)، وما يشغل الأوقات مسبقًا
 * (حصص أقسام أخرى لنفس المعلمين/القاعات)، وما يمنعه المعلمون أو يفضّلون تجنبه، وإعدادات الشبكة الزمنية.
 *
 * كائن بيانات فقط؛ القراءة من القاعدة في forDepartment، وكل ما عداه خوارزميات نقية يسهل اختبارها.
 */
final class ScheduleProblem
{
    /** @var string[] */
    public array $days = [];
    public int $periods = 5;
    /** @var array<string,array{key:string,label:string,program_id:int,year:int,size:?int,courses:array}> */
    public array $groups = [];
    /** @var string[] أسماء القاعات المتاحة (فارغ = قاعة ضمنية لكل شعبة) */
    public array $rooms = [];
    /** @var array<string,?int> */
    public array $roomCapacity = [];
    /** @var array<string,bool> "teacher|day|period" مشغول بحصص من خارج نطاق المسودة */
    public array $busyTeacher = [];
    /** @var array<string,bool> "room|day|period" */
    public array $busyRoom = [];
    /** @var array<string,bool> "teacher|day|period" لا يستطيعه المعلم (صلب) */
    public array $unavailable = [];
    /** @var array<string,bool> "teacher|day|period" يفضّل تجنبه (ناعم) */
    public array $avoid = [];
    /** @var array<int,array> حصص مثبّتة (locked) لا يغيّرها المولّد */
    public array $fixed = [];
    public int $seed = 1;
    /** الفصل الدراسي الذي نجدول مقرراته فقط */
    public ?int $semesterId = null;
    public string $semesterName = '';
    /** @var array<string,float> */
    public array $weights = [];
    /** @var array<int,string> أسماء المعلمين (للرسائل والواجهات) */
    public array $teacherNames = [];

    public static function yearLabel(int $year): string
    {
        return [1 => 'سنة أولى', 2 => 'سنة ثانية', 3 => 'سنة ثالثة'][$year] ?? ('سنة ' . $year);
    }

    /** قيمة users.academic_year المقابلة لسنة الشعبة. */
    public static function academicYear(int $year): string
    {
        return [1 => 'السنة الأولى', 2 => 'السنة الثانية', 3 => 'السنة الثالثة', 4 => 'السنة الرابعة', 5 => 'السنة الخامسة'][$year] ?? ('السنة ' . $year);
    }

    public static function groupLabel(string $programName, int $year): string
    {
        return $programName . ' - ' . self::yearLabel($year);
    }

    public function slotCount(): int
    {
        return count($this->days) * $this->periods;
    }

    public function periodOf(string $time): ?int
    {
        $hhmm = substr($time, 0, 5);
        foreach (config('schedule.periods') as $n => [$start]) {
            if ($start === $hhmm) {
                return (int) $n;
            }
        }

        return null;
    }

    /**
     * يبني المسألة لقسم من القاعدة.
     *
     * @param array{days?:string[],periods?:int,seed?:int,weights?:array,program_ids?:int[],semester_id?:int} $options
     * @param array<int,array> $locked حصص مثبّتة يجب الحفاظ عليها
     */
    public static function forDepartment(int $departmentId, array $options = [], array $locked = []): self
    {
        // لا جدولة بلا فصل: مقررات الفصل المختار فقط (الافتراضي الفعّال). الخلط بين فصلين كان يضاعف حصص الشعبة.
        $semester = !empty($options['semester_id']) ? Semester::find((int) $options['semester_id']) : Semester::current();
        if (!$semester) {
            throw new ScheduleRuleException([!empty($options['semester_id']) ? 'الفصل الدراسي المختار غير موجود' : 'لا يوجد فصل دراسي فعّال. اختر فصلاً لتوليد جدوله.']);
        }

        $p = new self();
        $p->semesterId = (int) $semester->semester_id;
        $p->semesterName = (string) $semester->name;
        $p->days = $options['days'] ?? config('schedule.days');
        $p->periods = max(1, min((int) ($options['periods'] ?? config('schedule.default_periods')), (int) config('schedule.max_periods')));
        $p->seed = (int) ($options['seed'] ?? 1);
        $p->weights = array_merge(config('schedule.weights'), $options['weights'] ?? []);
        $p->fixed = $locked;

        $rows = DB::table('course_program')
            ->join('courses', 'courses.course_id', '=', 'course_program.course_id')
            ->join('programs', 'programs.id', '=', 'course_program.program_id')
            ->where('programs.department_id', $departmentId)
            ->where('courses.semester_id', $p->semesterId)
            ->when(!empty($options['program_ids']), fn ($q) => $q->whereIn('programs.id', $options['program_ids']))
            ->orderBy('programs.id')->orderBy('courses.year')->orderBy('courses.course_id')
            ->get(['programs.id as program_id', 'programs.name as program', 'courses.course_id', 'courses.title', 'courses.hours', 'courses.year']);

        $courseTeachers = self::courseTeachers($rows->pluck('course_id')->unique()->all());
        $rotation = [];   // مادة مشتركة بين شعب: نوزّع معلميها بالتناوب (حتمي)

        foreach ($rows as $r) {
            $year = (int) ($r->year ?: 1);
            $key = $r->program_id . '-' . $year;
            if (!isset($p->groups[$key])) {
                $p->groups[$key] = [
                    'key' => $key, 'label' => self::groupLabel($r->program, $year), 'program_id' => (int) $r->program_id,
                    'year' => $year, 'size' => null, 'courses' => [],
                ];
            }

            $teachers = $courseTeachers[$r->course_id] ?? [];
            $i = $rotation[$r->course_id] = ($rotation[$r->course_id] ?? -1) + 1;
            $hours = (int) $r->hours > 0 ? (int) $r->hours : (int) config('schedule.default_course_hours');

            $p->groups[$key]['courses'][] = [
                'course_id' => (int) $r->course_id, 'title' => $r->title,
                'sessions' => max(1, (int) round($hours / (float) config('schedule.session_hours'))),
                'teacher' => $teachers ? $teachers[$i % count($teachers)] : null,
            ];
        }

        // حجم كل شعبة (طلاب نشطون في البرنامج والسنة) لمراعاة سعة القاعة
        foreach ($p->groups as $k => $g) {
            $p->groups[$k]['size'] = (int) DB::table('students')
                ->join('users', 'users.user_id', '=', 'students.user_id')
                ->where('students.program_id', $g['program_id'])->where('users.academic_year', self::academicYear($g['year']))
                ->where('users.status', 'active')->count();
        }

        $p->loadRooms($departmentId);
        $p->loadExternalBusy($departmentId);
        $p->loadAvailability();
        $p->loadTeacherNames();

        return $p;
    }

    /** @return array<int,int[]> course_id => [teacher user_ids] (ترتيب ثابت) */
    private static function courseTeachers(array $courseIds): array
    {
        if (!$courseIds) {
            return [];
        }

        $out = [];
        $rows = DB::table('course_teachers')
            ->join('teachers', 'teachers.teacher_id', '=', 'course_teachers.teacher_id')
            ->join('users', 'users.user_id', '=', 'teachers.user_id')
            ->whereIn('course_teachers.course_id', $courseIds)->where('users.status', 'active')
            ->orderBy('course_teachers.course_id')->orderBy('teachers.teacher_id')
            ->get(['course_teachers.course_id', 'teachers.user_id']);
        foreach ($rows as $r) {
            $out[$r->course_id][] = (int) $r->user_id;
        }

        return $out;
    }

    private function loadRooms(int $departmentId): void
    {
        $rooms = DB::table('rooms')->where('is_active', true)
            ->where(fn ($q) => $q->where('department_id', $departmentId)->orWhereNull('department_id'))
            ->orderBy('name')->get(['name', 'capacity']);

        foreach ($rooms as $r) {
            $this->rooms[] = $r->name;
            $this->roomCapacity[$r->name] = $r->capacity !== null ? (int) $r->capacity : null;
        }
    }

    /** حصص موجودة لمواد خارج هذا القسم تشغل معلمين/قاعات مشتركة: لا يجوز التعارض معها. */
    private function loadExternalBusy(int $departmentId): void
    {
        $own = DB::table('course_program')->join('programs', 'programs.id', '=', 'course_program.program_id')
            ->where('programs.department_id', $departmentId)->pluck('course_program.course_id');

        // مادة مشتركة مع قسم آخر تُعدّ من هذا القسم أيضًا (ستُستبدل عند الاعتماد)، فلا تُحسب مشغولة
        $rows = DB::table('schedules')->whereNotIn('course_id', $own)->get(['teacher_id', 'day', 'start_time', 'room']);
        foreach ($rows as $r) {
            $period = $this->periodOf($r->start_time);
            if ($period === null) {
                continue;   // وقت خارج الشبكة المعتمدة: لا نستطيع ربطه بفترة
            }
            $this->busyTeacher["{$r->teacher_id}|{$r->day}|{$period}"] = true;
            $this->busyRoom["{$r->room}|{$r->day}|{$period}"] = true;
        }
    }

    private function loadAvailability(): void
    {
        $teachers = [];
        foreach ($this->groups as $g) {
            foreach ($g['courses'] as $c) {
                if ($c['teacher']) {
                    $teachers[$c['teacher']] = true;
                }
            }
        }
        if (!$teachers) {
            return;
        }

        foreach (DB::table('teacher_availability')->whereIn('teacher_user_id', array_keys($teachers))->get() as $a) {
            $key = "{$a->teacher_user_id}|{$a->day}|{$a->period}";
            $a->kind === 'unavailable' ? $this->unavailable[$key] = true : $this->avoid[$key] = true;
        }
    }

    private function loadTeacherNames(): void
    {
        $ids = [];
        foreach ($this->groups as $g) {
            foreach ($g['courses'] as $c) {
                if ($c['teacher']) {
                    $ids[$c['teacher']] = true;
                }
            }
        }
        foreach ($this->fixed as $f) {
            $ids[$f['teacher']] = true;
        }
        if ($ids) {
            $this->teacherNames = DB::table('users')->whereIn('user_id', array_keys($ids))->pluck('full_name', 'user_id')->all();
        }
    }

    public function teacherName(?int $id): string
    {
        return $id ? ($this->teacherNames[$id] ?? ('معلم #' . $id)) : 'بلا معلم';
    }

    /**
     * تقرير القدرة قبل التوليد: هل تتسع الشبكة لحصص كل شعبة؟ (يكشف الاستحالة بدل إخفائها)
     *
     * @return array<int,array{group:string,label:string,needed:int,available:int,overflow:int,no_teacher:int}>
     */
    public function capacityReport(): array
    {
        $report = [];
        foreach ($this->groups as $g) {
            $needed = 0;
            $noTeacher = 0;
            foreach ($g['courses'] as $c) {
                $c['teacher'] ? $needed += $c['sessions'] : $noTeacher += $c['sessions'];
            }
            $report[] = [
                'group' => $g['key'], 'label' => $g['label'], 'needed' => $needed, 'available' => $this->slotCount(),
                'overflow' => max(0, $needed - $this->slotCount()), 'no_teacher' => $noTeacher,
            ];
        }

        return $report;
    }
}
