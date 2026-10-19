<?php

namespace App\Services\Schedule;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * مسألة الجدول الامتحاني لقسم وفصل: لكل مقرر في كل شعبة (دورة + سنة) «مذاكرة» و/أو «امتحان».
 *
 * كل ما يخص الأيام والفترات والمدة يدخله رئيس القسم عند التوليد (لا قيم ثابتة):
 *   kinds: ['midterm' => فترة المذاكرات، 'final' => فترة الامتحانات]. كل نوع له: from, to, excluded[], slots[[start,end]], duration.
 *
 * التعارض يُحسب على مستوى الطالب: تقييمان يشترك فيهما طالب لا يقعان في اليوم نفسه. وطلاب الشعبة كلهم يتشاركون المقررات،
 * فيعني هذا عملياً «يوم واحد لتقييم واحد لكل شعبة»، ويغطي تلقائياً المعيدين والمقررات المشتركة.
 */
final class ExamProblem
{
    public const KINDS = ['midterm' => 'مذاكرة', 'final' => 'امتحان'];

    public int $semesterId;
    public string $semesterName = '';
    public int $seed = 1;

    /** @var array<string,array{from:string,to:string,excluded:string[],slots:array<int,array{0:string,1:string}>,duration:int}> */
    public array $kinds = [];
    /** @var array<string,string[]> kind => التواريخ الصالحة (Y-m-d) */
    public array $calendar = [];

    /** @var array<string,array> key => task */
    public array $tasks = [];
    /** @var array<string,string[]> key => مفاتيح المهام التي تشترك معها بطالب */
    public array $conflicts = [];

    /** @var array<string,int> اسم القاعة => السعة */
    public array $rooms = [];
    /** @var array<string,string> مفتاح الشعبة => قاعتها الخاصة */
    public array $homeRoom = [];
    /** @var array<int,string> طلاب (id => الاسم) لتوزيع مرتب */
    public array $studentNames = [];
    /** @var array<string,float> */
    public array $weights = [];
    /** @var array<string,array> مفتاح الشعبة => ['label','program_id','year','size','courses'] */
    public array $groups = [];

    /**
     * @param array{semester_id?:int,seed?:int,kinds:array} $options
     * @throws ScheduleRuleException
     */
    public static function forDepartment(int $deptId, array $options): self
    {
        $base = ScheduleProblem::forDepartment($deptId, ['semester_id' => $options['semester_id'] ?? null]);

        $p = new self();
        $p->semesterId = $base->semesterId;
        $p->semesterName = $base->semesterName;
        $p->seed = (int) ($options['seed'] ?? 1);
        $p->groups = $base->groups;
        $p->rooms = array_filter($base->roomCapacity, fn ($c) => $c !== null);
        $p->homeRoom = $base->homeRoom;
        $p->weights = array_merge(config('exam.weights'), $options['weights'] ?? []);
        $p->kinds = self::normalizeKinds($options['kinds'] ?? []);

        foreach ($p->kinds as $kind => $cfg) {
            $p->calendar[$kind] = self::workingDates($cfg['from'], $cfg['to'], $cfg['excluded']);
        }

        $p->loadTasks();
        $p->buildConflicts();

        return $p;
    }

    // ───────────────────────────── التحقق من مدخلات رئيس القسم ─────────────────────────────

    /**
     * @param array<string,array> $kinds
     * @return array<string,array>
     * @throws ScheduleRuleException برسالة عربية جاهزة للعرض
     */
    public static function normalizeKinds(array $kinds): array
    {
        $out = [];
        $errors = [];

        foreach (self::KINDS as $kind => $label) {
            $cfg = $kinds[$kind] ?? null;
            if (!$cfg || (empty($cfg['from']) && empty($cfg['to']) && empty($cfg['slots']))) {
                continue;   // النوع غير مطلوب
            }

            $from = self::parseDate($cfg['from'] ?? null);
            $to = self::parseDate($cfg['to'] ?? null);
            if (!$from || !$to) {
                $errors[] = "حدّد تاريخ بداية ونهاية صالحين لفترة {$label} (بصيغة 2026-12-06).";
                continue;
            }
            if ($to->lt($from)) {
                $errors[] = "فترة {$label}: النهاية قبل البداية.";
                continue;
            }
            if ($from->diffInDays($to) > (int) config('exam.max_period_days')) {
                $errors[] = "فترة {$label} أطول من " . config('exam.max_period_days') . ' يوماً.';
                continue;
            }

            $slots = [];
            foreach ((array) ($cfg['slots'] ?? []) as $s) {
                $a = $s[0] ?? $s['start'] ?? null;
                $b = $s[1] ?? $s['end'] ?? null;
                if (!is_string($a) || !is_string($b) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $a) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $b) || $a >= $b) {
                    $errors[] = "فترة يومية غير صالحة في {$label} (الصيغة 09:00 إلى 10:30، والبداية قبل النهاية).";
                    continue 2;
                }
                $slots[] = [$a, $b];
            }
            if (!$slots) {
                $errors[] = "أضف فترة يومية واحدة على الأقل لـ{$label}.";
                continue;
            }
            if (count($slots) > (int) config('exam.max_slots_per_day')) {
                $errors[] = "أكثر من " . config('exam.max_slots_per_day') . " فترات يومية في {$label}.";
                continue;
            }
            usort($slots, fn ($x, $y) => $x[0] <=> $y[0]);
            for ($i = 1; $i < count($slots); $i++) {
                if ($slots[$i][0] < $slots[$i - 1][1]) {
                    $errors[] = "فترتان متداخلتان في {$label}: {$slots[$i - 1][0]}–{$slots[$i - 1][1]} و{$slots[$i][0]}–{$slots[$i][1]}.";
                    continue 2;
                }
            }

            $duration = (int) ($cfg['duration'] ?? 0);
            if ($duration < 15 || $duration > 360) {
                $errors[] = "مدة {$label} يجب أن تكون بين 15 و360 دقيقة.";
                continue;
            }
            $longest = max(array_map(fn ($s) => self::minutes($s[1]) - self::minutes($s[0]), $slots));
            if ($duration > $longest) {
                $errors[] = "مدة {$label} ({$duration} دقيقة) أطول من أطول فترة يومية ({$longest} دقيقة).";
                continue;
            }

            $excluded = [];
            foreach ((array) ($cfg['excluded'] ?? []) as $d) {
                $day = self::parseDate($d);
                if (!$day) {
                    $errors[] = "تاريخ مستثنى غير صالح في {$label}: «" . (is_scalar($d) ? $d : '') . '».';
                    continue 2;
                }
                $excluded[] = $day->toDateString();
            }
            $excluded = array_values(array_unique($excluded));
            $out[$kind] = [
                'from' => $from->toDateString(), 'to' => $to->toDateString(), 'excluded' => $excluded, 'slots' => $slots, 'duration' => $duration,
            ];
        }

        if (!$errors && !$out) {
            $errors[] = 'حدّد فترة المذاكرات أو فترة الامتحانات (أو كلتيهما).';
        }
        if (!$errors && isset($out['midterm'], $out['final']) && $out['midterm']['to'] >= $out['final']['to']) {
            $errors[] = 'فترة الامتحانات يجب أن تنتهي بعد فترة المذاكرات.';
        }
        if ($errors) {
            throw new ScheduleRuleException($errors);
        }

        return $out;
    }

    /** تاريخ بصيغة YYYY-MM-DD حصراً (Carbon::parse يقبل أي نص تقريباً، مثل «x»، فلا نعتمد عليه لمدخلات المستخدم). */
    public static function parseDate(mixed $value): ?Carbon
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $d = Carbon::createFromFormat('!Y-m-d', $value);

        return $d && $d->format('Y-m-d') === $value ? $d : null;
    }

    public static function minutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }

    /** @return string[] أيام العمل (من config/schedule.days) ضمن المجال بلا المستثنى. */
    public static function workingDates(string $from, string $to, array $excluded): array
    {
        $days = config('schedule.days');
        $out = [];
        for ($d = Carbon::parse($from); $d->lte(Carbon::parse($to)); $d->addDay()) {
            if (in_array($d->englishDayOfWeek, $days, true) && !in_array($d->toDateString(), $excluded, true)) {
                $out[] = $d->toDateString();
            }
        }

        return $out;
    }

    // ───────────────────────────── المهام والتعارضات ─────────────────────────────

    private function loadTasks(): void
    {
        $courseIds = [];
        foreach ($this->groups as $g) {
            foreach ($g['courses'] as $c) {
                $courseIds[$c['course_id']] = true;
            }
        }
        if (!$courseIds) {
            return;
        }

        // طلاب كل مقرر في كل برنامج (نشطون وغير منسحبين). مقرر مشترك بين برنامجين له مهمة لكل برنامج بطلابه هو.
        $rows = DB::table('enrollments')
            ->join('students', 'students.student_id', '=', 'enrollments.student_id')
            ->join('users', 'users.user_id', '=', 'students.user_id')
            ->whereIn('enrollments.course_id', array_keys($courseIds))
            ->where('enrollments.status', '!=', 'dropped')
            ->where('users.status', 'active')
            ->get(['enrollments.course_id', 'students.program_id', 'students.student_id', 'users.full_name']);

        $byCourseProgram = [];
        foreach ($rows as $r) {
            $byCourseProgram[$r->course_id . '|' . $r->program_id][(int) $r->student_id] = $r->full_name;
            $this->studentNames[(int) $r->student_id] = (string) $r->full_name;
        }

        foreach ($this->groups as $gk => $g) {
            foreach ($g['courses'] as $c) {
                $students = $byCourseProgram[$c['course_id'] . '|' . $g['program_id']] ?? [];
                foreach (array_keys($this->kinds) as $kind) {
                    $key = $gk . '|' . $c['course_id'] . '|' . $kind;
                    $this->tasks[$key] = [
                        'key' => $key, 'kind' => $kind, 'course_id' => $c['course_id'], 'title' => $c['title'],
                        'group' => $gk, 'label' => $g['label'], 'program_id' => $g['program_id'], 'year' => $g['year'],
                        'students' => array_keys($students), 'size' => count($students),
                    ];
                }
            }
        }
    }

    private function buildConflicts(): void
    {
        $byStudent = [];
        foreach ($this->tasks as $key => $t) {
            foreach ($t['students'] as $sid) {
                $byStudent[$sid][] = $key;
            }
        }
        foreach ($this->tasks as $key => $_) {
            $this->conflicts[$key] = [];
        }
        foreach ($byStudent as $keys) {
            foreach ($keys as $a) {
                foreach ($keys as $b) {
                    if ($a !== $b) {
                        $this->conflicts[$a][$b] = $b;
                    }
                }
            }
        }
        $this->conflicts = array_map('array_values', $this->conflicts);
    }

    public function slotMinutes(string $kind, int $slot): array
    {
        [$a, $b] = $this->kinds[$kind]['slots'][$slot];

        return [self::minutes($a), self::minutes($b)];
    }

    // ───────────────────────────── تقرير القدرة ─────────────────────────────

    /**
     * هل تتسع فترات رئيس القسم لتقييمات كل شعبة (يوم واحد لتقييم واحد)، وهل تتسع القاعات لأكبر شعبة؟
     *
     * @return array<int,array{group:string,label:string,kind:string,kind_label:string,needed_days:int,available_days:int,overflow:int,size:int}>
     */
    public function capacityReport(): array
    {
        $out = [];
        foreach ($this->groups as $gk => $g) {
            foreach (array_keys($this->kinds) as $kind) {
                $needed = count($g['courses']);
                $available = count($this->calendar[$kind] ?? []);
                $out[] = [
                    'group' => $gk, 'label' => $g['label'], 'kind' => $kind, 'kind_label' => self::KINDS[$kind],
                    'needed_days' => $needed, 'available_days' => $available, 'overflow' => max(0, $needed - $available), 'size' => (int) ($g['size'] ?? 0),
                ];
            }
        }

        return $out;
    }

    public function totalRoomCapacity(): int
    {
        return (int) array_sum($this->rooms);
    }
}
