<?php

namespace App\Services\Schedule;

use App\Models\Exam;
use App\Models\ExamDraftEntry;
use App\Models\ExamInvigilator;
use App\Models\ExamSeating;
use App\Models\Notification;
use App\Models\ScheduleDraft;
use App\Models\User;
use App\Support\Access;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * دورة حياة مسودة الجدول الامتحاني (مذاكرات + امتحانات): توليد ← تعديل (نقل، تثبيت، نقل طالب بين قاعتين، مراقب لكل قاعة)
 * ← اعتماد ← (تراجع). المسودة لا تمسّ جدول exams الحقيقي ولا يراها أحد غير رئيس القسم حتى الاعتماد.
 *
 * الاعتماد آمن: يحدّث صفوف exams الموجودة (نفس المقرر والشعبة والنوع) ويضيف الجديدة، ولا يحذف أي صف أبداً
 * (grades.exam_id حذفه متسلسل فيمحو العلامات). التراجع يعيد ما غيّره ويحذف ما أضافه فقط إن لم تكن له علامات.
 */
class ExamDraftService
{
    public function __construct(private ScheduleDraftService $weekly)
    {
    }

    public function departmentOf(User $user): int
    {
        return $this->weekly->departmentOf($user);
    }

    // ------------------------------------------------------------------ توليد

    /**
     * @param array{name?:string,seed?:int,semester_id?:int,kinds:array} $options
     * @throws ScheduleRuleException
     */
    public function generate(User $by, array $options): ScheduleDraft
    {
        $deptId = $this->weekly->departmentOf($by);
        $problem = ExamProblem::forDepartment($deptId, $options);
        $result = (new ExamSolver())->solve($problem);

        $kinds = array_keys($problem->kinds);
        $title = count($kinds) === 2 ? 'جدول مذاكرات وامتحانات' : ($kinds[0] === 'midterm' ? 'جدول مذاكرات' : 'جدول امتحانات');

        $draft = DB::transaction(function () use ($by, $deptId, $problem, $options, $result, $title) {
            $draft = ScheduleDraft::create([
                'department_id' => $deptId, 'semester_id' => $problem->semesterId, 'type' => ScheduleDraft::TYPE_EXAM, 'created_by' => $by->user_id,
                'name' => $options['name'] ?? ($title . ': ' . $problem->semesterName), 'status' => ScheduleDraft::DRAFT,
                'settings' => ['kinds' => $problem->kinds, 'seed' => $problem->seed, 'semester_id' => $problem->semesterId],
                'stats' => null,
            ]);
            $this->storeEntries($draft, $problem, $result['entries']);
            $draft->update(['stats' => $this->stats($problem, $result['entries'], $result['unscheduled'], $result['restarts'])]);

            return $draft;
        });

        $this->pruneOld($deptId, (int) $problem->semesterId);

        return $draft->fresh();
    }

    /** يعيد توزيع غير المثبّت فقط، والمثبّت يبقى بقاعاته ومراقبيه. */
    public function regenerate(ScheduleDraft $draft, ?int $seed = null): ScheduleDraft
    {
        $this->assertEditable($draft);
        $seed ??= (($draft->settings['seed'] ?? 1) + 7);

        $problem = $this->problemFor($draft, $seed);
        $locked = $draft->examEntries()->where('locked', true)->get()->map(fn ($e) => $this->toEntry($e, $problem))->all();
        $result = (new ExamSolver())->solve($problem, $locked);

        DB::transaction(function () use ($draft, $problem, $result, $seed) {
            $draft->examEntries()->where('locked', false)->delete();
            $this->storeEntries($draft, $problem, array_filter($result['entries'], fn ($e) => !$e['locked']));
            $settings = $draft->settings;
            $settings['seed'] = $seed;
            $draft->update(['settings' => $settings]);
        });

        return $this->refreshStats($draft->fresh(), $result['unscheduled'], $result['restarts']);
    }

    /**
     * يحوّل مدخلات نموذج رئيس القسم (ويب أو تطبيق) إلى شكل ExamProblem::normalizeKinds.
     * كل نوع: enabled، from، to، duration، excluded (نص تفصله فواصل/أسطر أو مصفوفة)، slots = [{start,end}].
     *
     * @param array<string,mixed> $input
     * @return array<string,array>
     */
    public static function kindsFromInput(array $input): array
    {
        $out = [];
        foreach (array_keys(ExamProblem::KINDS) as $kind) {
            $in = $input[$kind] ?? null;
            if (!is_array($in) || !filter_var($in['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }
            $excluded = $in['excluded'] ?? [];
            if (is_string($excluded)) {
                $excluded = preg_split('/[\s,،;]+/u', trim($excluded), -1, PREG_SPLIT_NO_EMPTY);
            }
            $slots = [];
            foreach ((array) ($in['slots'] ?? []) as $s) {
                $a = trim((string) ($s['start'] ?? $s[0] ?? ''));
                $b = trim((string) ($s['end'] ?? $s[1] ?? ''));
                if ($a === '' && $b === '') {
                    continue;   // صف فارغ في النموذج
                }
                $slots[] = [$a, $b];
            }
            $out[$kind] = [
                'from' => trim((string) ($in['from'] ?? '')), 'to' => trim((string) ($in['to'] ?? '')),
                'duration' => $in['duration'] ?? null, 'excluded' => $excluded, 'slots' => $slots,
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------------ تعديل يدوي

    /** ينقل تقييماً إلى يوم وفترة يومية، فيُعاد اختيار قاعاته وتوزيع طلابه، بشرط ألا يكسر قيداً صلباً. */
    public function move(ScheduleDraft $draft, int $entryId, string $date, int $slot): ScheduleDraft
    {
        $this->assertEditable($draft);
        $row = $draft->examEntries()->findOrFail($entryId);

        [$problem, $entries, $i] = $this->snapshot($draft, $entryId);
        $cfg = $problem->kinds[$row->kind] ?? null;
        if (!$cfg || !isset($cfg['slots'][$slot])) {
            throw new ScheduleRuleException(['الفترة اليومية غير معرّفة لهذا النوع']);
        }

        $start = $cfg['slots'][$slot][0];
        $end = sprintf('%02d:%02d', intdiv(ExamProblem::minutes($start) + $cfg['duration'], 60), (ExamProblem::minutes($start) + $cfg['duration']) % 60);

        $rooms = ExamSolver::roomsFor($problem, $entries, $i, $date, $start, $end);
        if ($rooms === null) {
            throw new ScheduleRuleException(['لا قاعات حرة تتسع لكل الطلاب في هذا الوقت']);
        }
        $rooms = $this->keepProctors($rooms, $entries[$i]['rooms']);

        $entries[$i] = ['date' => $date, 'slot' => $slot, 'start' => $start, 'end' => $end, 'rooms' => $rooms, 'source' => 'manual'] + $entries[$i];
        $violations = ExamValidator::forEntry($problem, $entries, $i);
        if ($violations) {
            throw new ScheduleRuleException(array_values(array_unique(array_column($violations, 'message'))));
        }

        $row->update(['exam_date' => $date, 'start_time' => $start, 'end_time' => $end, 'rooms' => $rooms, 'source' => 'manual']);

        return $this->refreshStats($draft->fresh());
    }

    public function setLocked(ScheduleDraft $draft, int $entryId, bool $locked): void
    {
        $this->assertEditable($draft);
        $draft->examEntries()->findOrFail($entryId)->update(['locked' => $locked]);
    }

    /** ينقل طالباً من قاعة إلى أخرى ضمن قاعات التقييم نفسه (بشرط السعة). */
    public function moveStudent(ScheduleDraft $draft, int $entryId, int $studentId, string $toRoom): ScheduleDraft
    {
        $this->assertEditable($draft);
        $row = $draft->examEntries()->findOrFail($entryId);
        [$problem, $entries, $i] = $this->snapshot($draft, $entryId);

        $rooms = $entries[$i]['rooms'];
        $from = null;
        $to = null;
        foreach ($rooms as $k => $r) {
            if (in_array($studentId, $r['students'], true)) {
                $from = $k;
            }
            if ($r['name'] === $toRoom) {
                $to = $k;
            }
        }
        if ($from === null) {
            throw new ScheduleRuleException(['هذا الطالب ليس في أي قاعة من قاعات هذا التقييم']);
        }
        if ($to === null) {
            throw new ScheduleRuleException(['القاعة المطلوبة ليست من قاعات هذا التقييم']);
        }
        if ($from === $to) {
            return $draft;
        }

        $rooms[$from]['students'] = array_values(array_diff($rooms[$from]['students'], [$studentId]));
        $rooms[$to]['students'][] = $studentId;
        $entries[$i]['rooms'] = $rooms;

        $violations = ExamValidator::forEntry($problem, $entries, $i);
        if ($violations) {
            throw new ScheduleRuleException(array_values(array_unique(array_column($violations, 'message'))));
        }

        $row->update(['rooms' => $rooms, 'source' => 'manual']);

        return $this->refreshStats($draft->fresh());
    }

    /** يعيّن (أو يزيل بـ null) مراقب قاعة من أساتذة القسم. */
    public function setProctor(ScheduleDraft $draft, int $entryId, string $room, ?int $userId): ScheduleDraft
    {
        $this->assertEditable($draft);
        $row = $draft->examEntries()->findOrFail($entryId);

        if ($userId !== null && !$this->departmentTeachers($draft->department_id)->contains('user_id', $userId)) {
            throw new ScheduleRuleException(['هذا المعلم ليس من أساتذة قسمك']);
        }

        [$problem, $entries, $i] = $this->snapshot($draft, $entryId);
        $found = false;
        foreach ($entries[$i]['rooms'] as $k => $r) {
            if ($r['name'] === $room) {
                $entries[$i]['rooms'][$k]['proctor'] = $userId;
                $found = true;
            }
        }
        if (!$found) {
            throw new ScheduleRuleException(['القاعة ليست من قاعات هذا التقييم']);
        }

        $violations = ExamValidator::forEntry($problem, $entries, $i);
        if ($violations) {
            throw new ScheduleRuleException(array_values(array_unique(array_column($violations, 'message'))));
        }

        $row->update(['rooms' => $entries[$i]['rooms'], 'source' => 'manual']);

        return $this->refreshStats($draft->fresh());
    }

    /** كل أساتذة القسم النشطين: من ينتمي لقسمه، أو يدرّس أحد مقرراته. */
    public function departmentTeachers(int $deptId)
    {
        $name = DB::table('departments')->where('department_id', $deptId)->value('name');
        $teachingIds = DB::table('course_teachers')
            ->join('teachers', 'teachers.teacher_id', '=', 'course_teachers.teacher_id')
            ->join('course_program', 'course_program.course_id', '=', 'course_teachers.course_id')
            ->join('programs', 'programs.id', '=', 'course_program.program_id')
            ->where('programs.department_id', $deptId)->pluck('teachers.user_id');

        return DB::table('teachers')
            ->join('users', 'users.user_id', '=', 'teachers.user_id')
            ->where('users.status', 'active')
            ->where(fn ($q) => $q->where('users.department', $name)->orWhereIn('users.user_id', $teachingIds))
            ->select('users.user_id', 'users.full_name')->distinct()->orderBy('users.full_name')->get();
    }

    // ------------------------------------------------------------------ اعتماد وتراجع

    /**
     * ما سيحدث بالضبط عند الاعتماد: كم تقييماً سيُحدَّث وكم سيُضاف، ومن سيُشعَر. (للتأكيد قبل الاعتماد)
     *
     * @return array{updated:int,created:int,students:int,teachers:int,rooms_without_proctor:int}
     */
    public function impact(ScheduleDraft $draft): array
    {
        $updated = $created = 0;
        $students = [];
        $teachers = [];
        $noProctor = 0;
        $labels = $this->labels($draft);

        foreach ($draft->examEntries()->get() as $e) {
            $this->findExam($e, $labels) ? $updated++ : $created++;
            foreach ($e->rooms as $r) {
                array_push($students, ...$r['students']);
                if (!empty($r['proctor'])) {
                    $teachers[] = $r['proctor'];
                } else {
                    $noProctor++;
                }
            }
            array_push($teachers, ...DB::table('course_teachers')->join('teachers', 'teachers.teacher_id', '=', 'course_teachers.teacher_id')
                ->where('course_teachers.course_id', $e->course_id)->pluck('teachers.user_id')->all());
        }

        return [
            'updated' => $updated, 'created' => $created, 'students' => count(array_unique($students)),
            'teachers' => count(array_unique($teachers)), 'rooms_without_proctor' => $noProctor,
        ];
    }

    /**
     * يعتمد المسودة: يحدّث/يضيف صفوف exams ويحفظ التوزيع والمراقبين، ويُشعر الطلاب والمعلمين. معاملة واحدة، ولا حذف أبداً.
     *
     * @throws ScheduleRuleException
     */
    public function approve(ScheduleDraft $draft, User $by, bool $acknowledge = false): ScheduleDraft
    {
        $this->assertEditable($draft);

        if ($draft->isSavedForNext()) {
            $name = $draft->semester?->name ?? ('#' . $draft->semester_id);
            throw new ScheduleRuleException(["هذا الجدول لفصل «{$name}» وهو ليس الفصل الفعّال حالياً، فهو محفوظ للفصل القادم ولا يُعتمد الآن. اعتمده عندما يصبح هذا الفصل فعّالاً."]);
        }

        $draft = $this->refreshStats($draft);
        $stats = $draft->stats;

        if (!empty($stats['violations'])) {
            throw new ScheduleRuleException(['لا يمكن اعتماد جدول فيه تعارضات: ' . implode('، ', array_slice(array_column($stats['violations'], 'message'), 0, 3))]);
        }
        if ($draft->examEntries()->count() === 0) {
            throw new ScheduleRuleException(['الجدول فارغ: لا شيء لاعتماده']);
        }
        if ((!empty($stats['unscheduled']) || ($stats['rooms_without_proctor'] ?? 0) > 0) && !$acknowledge) {
            throw new ScheduleRuleException(['فيه تقييمات لم تُجدول، أو قاعات بلا مراقب. أقرّ بذلك صراحةً إن أردت اعتماده كما هو']);
        }

        $labels = $this->labels($draft);
        $titles = DB::table('courses')->whereIn('course_id', $draft->examEntries()->pluck('course_id'))->pluck('title', 'course_id');
        $backup = ['updated' => [], 'inserted' => []];

        DB::transaction(function () use ($draft, $by, $labels, $titles, &$backup) {
            foreach ($draft->examEntries()->get() as $e) {
                $existing = $this->findExam($e, $labels);
                $names = array_column($e->rooms, 'name');
                $data = [
                    'course_id' => $e->course_id, 'exam_type' => $e->kind, 'class_group' => $e->class_group,
                    'exam_name' => (Exam::TYPES[$e->kind] === 'مذاكرة' ? 'مذاكرة' : 'الامتحان النهائي') . ' - ' . ($titles[$e->course_id] ?? ''),
                    'exam_date' => $e->exam_date->toDateString() . ' ' . $e->start_time . ':00', 'duration_minutes' => $e->duration_minutes,
                    'room' => implode('، ', $names), 'rooms' => $names,
                ];

                if ($existing) {
                    $backup['updated'][] = [
                        'exam' => $existing->only(['exam_id', 'exam_name', 'exam_type', 'class_group', 'duration_minutes', 'room']) + ['exam_date' => (string) $existing->getRawOriginal('exam_date'), 'rooms' => $existing->getRawOriginal('rooms')],
                        'seatings' => $existing->seatings()->get(['student_id', 'room', 'seat'])->toArray(),
                        'invigilators' => $existing->invigilators()->get(['room', 'user_id'])->toArray(),
                    ];
                    $existing->update($data);
                    $exam = $existing;
                } else {
                    $exam = Exam::create($data + ['max_score' => 100]);
                    $backup['inserted'][] = $exam->exam_id;
                }

                ExamSeating::where('exam_id', $exam->exam_id)->delete();
                ExamInvigilator::where('exam_id', $exam->exam_id)->delete();
                $now = now();
                $seats = [];
                $proctors = [];
                foreach ($e->rooms as $r) {
                    foreach (array_values($r['students']) as $n => $sid) {
                        $seats[] = ['exam_id' => $exam->exam_id, 'student_id' => $sid, 'room' => $r['name'], 'seat' => $n + 1, 'created_at' => $now, 'updated_at' => $now];
                    }
                    if (!empty($r['proctor'])) {
                        $proctors[] = ['exam_id' => $exam->exam_id, 'room' => $r['name'], 'user_id' => $r['proctor'], 'created_at' => $now, 'updated_at' => $now];
                    }
                }
                foreach (array_chunk($seats, 300) as $chunk) {
                    ExamSeating::insert($chunk);
                }
                if ($proctors) {
                    ExamInvigilator::insert($proctors);
                }
            }

            $draft->update(['backup' => $backup, 'status' => ScheduleDraft::APPROVED, 'approved_by' => $by->user_id, 'approved_at' => now(), 'reverted_at' => null]);
        });

        $this->notifyApproved($draft->fresh());

        return $draft->fresh();
    }

    /** يتراجع عن آخر اعتماد: يعيد الصفوف المحدَّثة كما كانت، ويحذف المُضافة إن لم تكن لها علامات. */
    public function revert(ScheduleDraft $draft, User $by): ScheduleDraft
    {
        if ($draft->status !== ScheduleDraft::APPROVED || $draft->reverted_at !== null) {
            throw new ScheduleRuleException(['هذا الجدول ليس معتمَداً أو سبق التراجع عنه']);
        }

        $newer = ScheduleDraft::where('department_id', $draft->department_id)->where('type', ScheduleDraft::TYPE_EXAM)->where('status', ScheduleDraft::APPROVED)
            ->whereNull('reverted_at')->where('approved_at', '>', $draft->approved_at)->exists();
        if ($newer) {
            throw new ScheduleRuleException(['اعتُمد جدول أحدث بعده: تراجع عن الأحدث أولاً']);
        }

        $backup = $draft->backup ?? ['updated' => [], 'inserted' => []];
        $kept = 0;

        DB::transaction(function () use ($draft, $backup, &$kept) {
            foreach ($backup['updated'] as $u) {
                $exam = Exam::find($u['exam']['exam_id']);
                if (!$exam) {
                    continue;
                }
                $o = $u['exam'];
                DB::table('exams')->where('exam_id', $exam->exam_id)->update([
                    'exam_name' => $o['exam_name'], 'exam_type' => $o['exam_type'], 'class_group' => $o['class_group'], 'duration_minutes' => $o['duration_minutes'],
                    'room' => $o['room'], 'rooms' => $o['rooms'], 'exam_date' => $o['exam_date'], 'updated_at' => now(),
                ]);
                ExamSeating::where('exam_id', $exam->exam_id)->delete();
                ExamInvigilator::where('exam_id', $exam->exam_id)->delete();
                $now = now();
                foreach ($u['seatings'] as $s) {
                    ExamSeating::insert($s + ['exam_id' => $exam->exam_id, 'created_at' => $now, 'updated_at' => $now]);
                }
                foreach ($u['invigilators'] as $v) {
                    ExamInvigilator::insert($v + ['exam_id' => $exam->exam_id, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
            foreach ($backup['inserted'] as $examId) {
                if (DB::table('grades')->where('exam_id', $examId)->exists()) {
                    $kept++;   // له علامات: لا نحذفه أبداً
                    continue;
                }
                Exam::where('exam_id', $examId)->delete();
            }
            $draft->update(['reverted_at' => now()]);
        });

        return $draft->fresh();
    }

    public function discard(ScheduleDraft $draft): void
    {
        $this->assertEditable($draft);
        $draft->update(['status' => ScheduleDraft::DISCARDED]);
    }

    // ------------------------------------------------------------------ إحصاءات وفحص

    public function refreshStats(ScheduleDraft $draft, ?array $unscheduled = null, ?int $restarts = null): ScheduleDraft
    {
        $problem = $this->problemFor($draft);
        $entries = $draft->examEntries()->get()->map(fn ($e) => $this->toEntry($e, $problem))->all();

        $unscheduled ??= $this->missing($problem, $entries, $draft->stats['unscheduled'] ?? []);
        $draft->update(['stats' => $this->stats($problem, $entries, $unscheduled, $restarts ?? ($draft->stats['restarts'] ?? 1))]);

        return $draft->fresh();
    }

    private function stats(ExamProblem $p, array $entries, array $unscheduled, int $restarts): array
    {
        $entries = array_values($entries);
        $noProctor = 0;
        foreach ($entries as $e) {
            foreach ($e['rooms'] as $r) {
                $noProctor += empty($r['proctor']) ? 1 : 0;
            }
        }

        return [
            'required' => count($p->tasks), 'placed' => count($entries), 'restarts' => $restarts,
            'unscheduled' => array_values($unscheduled),
            'violations' => ExamValidator::validate($p, $entries),
            'capacity' => $p->capacityReport(),
            'rooms_without_proctor' => $noProctor,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /** ما لم يُوضع بعد تعديل يدوي (يحفظ السبب المعروف سابقاً). */
    private function missing(ExamProblem $p, array $entries, array $known): array
    {
        $placed = array_column($entries, 'key');
        $reasons = collect($known)->keyBy('key');
        $out = [];
        foreach ($p->tasks as $key => $t) {
            if (in_array($key, $placed, true)) {
                continue;
            }
            $prev = $reasons->get($key);
            $out[] = [
                'key' => $key, 'group' => $t['group'], 'group_label' => $t['label'], 'course_id' => $t['course_id'], 'title' => $t['title'],
                'kind' => $t['kind'], 'kind_label' => ExamProblem::KINDS[$t['kind']], 'reason' => $prev['reason'] ?? 'not_placed',
                'message' => $prev['message'] ?? 'لم يوضع بعد',
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------------ جاهزية وعرض

    /**
     * جاهزية بيانات الجدول الامتحاني قبل أن يدخل رئيس القسم التواريخ: مقررات، طلاب، قاعات، مراقبون.
     *
     * @return array{ready:bool,errors:int,warnings:int,items:array<int,array{key:string,level:string,title:string,detail:string,action:?string}>}
     */
    public function readiness(int $deptId, int $semesterId): array
    {
        $p = ScheduleProblem::forDepartment($deptId, ['semester_id' => $semesterId]);
        $item = fn (string $k, string $l, string $t, string $d = '', ?string $a = null) => ['key' => $k, 'level' => $l, 'title' => $t, 'detail' => $d, 'action' => $a];
        $items = [];

        $courses = array_sum(array_map(fn ($g) => count($g['courses']), $p->groups));
        if ($courses === 0) {
            $items[] = $item('courses', 'error', "لا مقررات في «{$p->semesterName}» لقسمك", 'اختر فصلاً آخر.');
        } else {
            $items[] = $item('courses', 'ok', "{$courses} مقرراً في " . count($p->groups) . ' شعبة', "كل مقرر له مذاكرة وامتحان في فصل «{$p->semesterName}»");
        }

        $empty = [];
        $courseIds = collect($p->groups)->flatMap(fn ($g) => array_column($g['courses'], 'course_id'))->unique()->all();
        $enrolled = DB::table('enrollments')->join('students', 'students.student_id', '=', 'enrollments.student_id')
            ->join('users', 'users.user_id', '=', 'students.user_id')->whereIn('enrollments.course_id', $courseIds)
            ->where('enrollments.status', '!=', 'dropped')->where('users.status', 'active')
            ->get(['enrollments.course_id', 'students.program_id'])->groupBy(fn ($r) => $r->course_id . '|' . $r->program_id);
        foreach ($p->groups as $g) {
            foreach ($g['courses'] as $c) {
                if (!isset($enrolled[$c['course_id'] . '|' . $g['program_id']])) {
                    $empty[] = $g['label'] . ' — ' . $c['title'];
                }
            }
        }
        if ($empty) {
            $items[] = $item('no_students', 'warn', count($empty) . ' مقرر بلا طلاب مسجّلين', implode('، ', array_slice($empty, 0, 5)) . (count($empty) > 5 ? ' ...' : '') . '. سيُجدول بلا طلاب في قاعاته.');
        }

        $caps = array_filter($p->roomCapacity, fn ($c) => $c !== null);
        $biggest = max(array_merge([0], array_column($p->groups, 'size')));
        if (!$caps) {
            $items[] = $item('rooms', 'error', 'لا قاعات مسجّلة', 'الامتحانات تحتاج قاعات بسعاتها لتوزيع الطلاب. أضفها من مولّد الجدول ← القاعات.', 'open_rooms');
        } elseif (array_sum($caps) < $biggest) {
            $items[] = $item('rooms', 'error', 'مجموع سعات القاعات أقل من أكبر شعبة', "أكبر شعبة {$biggest} طالباً ومجموع المقاعد " . array_sum($caps) . '.', 'open_rooms');
        } else {
            $items[] = $item('rooms', 'ok', count($caps) . ' قاعة بمجموع ' . array_sum($caps) . ' مقعداً', "الطلاب يتوزعون على عدة قاعات عند الحاجة (أكبر شعبة {$biggest}).");
        }

        $n = $this->departmentTeachers($deptId)->count();
        $items[] = $n > 0
            ? $item('proctors', 'ok', "{$n} معلماً متاحاً للمراقبة", 'تختار مراقباً لكل قاعة بعد التوليد.')
            : $item('proctors', 'warn', 'لا معلمين في قسمك للمراقبة', 'ستعتمد الجدول بلا مراقبين.');

        $count = fn (string $l) => count(array_filter($items, fn ($i) => $i['level'] === $l));

        return ['ready' => $count('error') === 0, 'errors' => $count('error'), 'warnings' => $count('warn'), 'items' => $items];
    }

    /**
     * مسودة جاهزة للعرض (ويب وتطبيق): تقييمات مرتبة بالتاريخ مع أسماء المقررات والمراقبين والطلاب لكل قاعة، وإحصاءات.
     *
     * @return array<string,mixed>
     */
    public function describe(ScheduleDraft $draft): array
    {
        $problem = $this->problemFor($draft);
        $teachers = $this->departmentTeachers($draft->department_id);
        $names = $teachers->pluck('full_name', 'user_id')->all();
        $dayNames = config('schedule.day_labels');

        $entries = $draft->examEntries()->orderBy('exam_date')->orderBy('start_time')->orderBy('id')->get()->map(function (ExamDraftEntry $e) use ($problem, $names, $dayNames) {
            $t = $problem->tasks[$e->taskKey()] ?? null;

            return [
                'id' => $e->id, 'course_id' => (int) $e->course_id, 'title' => $t['title'] ?? ('#' . $e->course_id), 'group' => $e->program_id . '-' . $e->year,
                'group_label' => $e->class_group, 'kind' => $e->kind, 'kind_label' => ExamProblem::KINDS[$e->kind] ?? $e->kind,
                'date' => $e->exam_date->toDateString(), 'day' => $dayNames[$e->exam_date->englishDayOfWeek] ?? $e->exam_date->englishDayOfWeek,
                'start' => $e->start_time, 'end' => $e->end_time, 'duration' => $e->duration_minutes, 'locked' => (bool) $e->locked, 'source' => $e->source,
                'rooms' => array_map(fn ($r) => [
                    'name' => $r['name'], 'capacity' => $r['capacity'], 'count' => count($r['students']), 'proctor_id' => $r['proctor'] ?? null,
                    'proctor' => !empty($r['proctor']) ? ($names[$r['proctor']] ?? ('#' . $r['proctor'])) : null,
                    'students' => array_map(fn ($sid) => ['id' => $sid, 'name' => $problem->studentNames[$sid] ?? ('#' . $sid)], $r['students']),
                ], $e->rooms),
            ];
        })->all();

        $slots = [];
        foreach ($problem->kinds as $kind => $cfg) {
            $slots[$kind] = array_map(fn ($s, $i) => ['slot' => $i, 'start' => $s[0], 'end' => $s[1]], $cfg['slots'], array_keys($cfg['slots']));
        }

        return [
            'id' => $draft->id, 'name' => $draft->name, 'status' => $draft->status, 'semester_id' => $draft->semester_id,
            'semester' => $draft->semester?->name, 'saved_for_next' => $draft->isSavedForNext(), 'reverted' => $draft->reverted_at !== null,
            'approved_at' => $draft->approved_at?->toDateTimeString(), 'kinds' => $draft->settings['kinds'] ?? [], 'slots' => $slots,
            'stats' => $draft->stats, 'entries' => $entries,
            'teachers' => $teachers->map(fn ($t) => ['id' => (int) $t->user_id, 'name' => $t->full_name])->values()->all(),
            'dates' => $problem->calendar,
        ];
    }

    // ------------------------------------------------------------------ مساعدة

    public function problemFor(ScheduleDraft $draft, ?int $seed = null): ExamProblem
    {
        $s = $draft->settings;

        return ExamProblem::forDepartment($draft->department_id, [
            'semester_id' => $draft->semester_id, 'kinds' => $s['kinds'], 'seed' => $seed ?? $s['seed'],
        ]);
    }

    public function toEntry(ExamDraftEntry $e, ExamProblem $p): array
    {
        $slot = -1;
        foreach ($p->kinds[$e->kind]['slots'] ?? [] as $i => $s) {
            if ($s[0] === $e->start_time) {
                $slot = $i;
            }
        }

        return [
            'id' => $e->id, 'key' => $e->taskKey(), 'kind' => $e->kind, 'course_id' => (int) $e->course_id, 'group' => $e->program_id . '-' . $e->year,
            'date' => $e->exam_date->toDateString(), 'slot' => $slot, 'start' => $e->start_time, 'end' => $e->end_time, 'duration' => $e->duration_minutes,
            'rooms' => $e->rooms, 'locked' => (bool) $e->locked, 'source' => $e->source,
        ];
    }

    /** @return array{0: ExamProblem, 1: array, 2: int} */
    private function snapshot(ScheduleDraft $draft, int $entryId): array
    {
        $problem = $this->problemFor($draft);
        $entries = $draft->examEntries()->orderBy('id')->get()->map(fn ($e) => $this->toEntry($e, $problem))->values()->all();
        $index = collect($entries)->search(fn ($e) => $e['id'] === $entryId);

        return [$problem, $entries, (int) $index];
    }

    /** يبقي مراقب القاعة إن بقيت القاعة نفسها بعد نقل التقييم. */
    private function keepProctors(array $newRooms, array $oldRooms): array
    {
        $byName = [];
        foreach ($oldRooms as $r) {
            $byName[$r['name']] = $r['proctor'] ?? null;
        }
        foreach ($newRooms as $k => $r) {
            $newRooms[$k]['proctor'] = $byName[$r['name']] ?? null;
        }

        return $newRooms;
    }

    private function storeEntries(ScheduleDraft $draft, ExamProblem $p, array $entries): void
    {
        $now = now();
        $rows = [];
        foreach ($entries as $e) {
            $g = $p->groups[$e['group']];
            $rows[] = [
                'draft_id' => $draft->id, 'course_id' => $e['course_id'], 'program_id' => $g['program_id'], 'year' => $g['year'], 'class_group' => $g['label'],
                'kind' => $e['kind'], 'exam_date' => $e['date'], 'start_time' => $e['start'], 'end_time' => $e['end'], 'duration_minutes' => $e['duration'],
                'rooms' => json_encode($e['rooms'], JSON_UNESCAPED_UNICODE), 'locked' => $e['locked'] ?? false, 'source' => $e['source'] ?? 'auto',
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            ExamDraftEntry::insert($chunk);
        }
    }

    private function assertEditable(ScheduleDraft $draft): void
    {
        if ($draft->type !== ScheduleDraft::TYPE_EXAM) {
            throw new ScheduleRuleException(['هذه ليست مسودة جدول امتحاني']);
        }
        if ($draft->status !== ScheduleDraft::DRAFT) {
            throw new ScheduleRuleException(['المسودة مغلقة (معتمَدة أو مُهمَلة) ولا يمكن تعديلها']);
        }
    }

    private function pruneOld(int $deptId, int $semesterId): void
    {
        $keep = (int) config('exam.max_drafts_per_department');
        ScheduleDraft::where('department_id', $deptId)->where('type', ScheduleDraft::TYPE_EXAM)->where('semester_id', $semesterId)->where('status', '!=', ScheduleDraft::APPROVED)
            ->orderByDesc('id')->skip($keep)->take(1000)->get()->each->delete();
    }

    /** @return array<string,string> مفتاح الشعبة => التسمية المخزَّنة في exams.class_group */
    private function labels(ScheduleDraft $draft): array
    {
        return $draft->examEntries()->get()->mapWithKeys(fn ($e) => [$e->program_id . '-' . $e->year => $e->class_group])->all();
    }

    private function findExam(ExamDraftEntry $e, array $labels): ?Exam
    {
        return Exam::where('course_id', $e->course_id)->where('class_group', $e->class_group)->where('exam_type', $e->kind)->orderBy('exam_id')->first();
    }

    private function notifyApproved(ScheduleDraft $draft): void
    {
        try {
            $students = [];
            $proctors = [];
            $teachers = [];
            foreach ($draft->examEntries()->get() as $e) {
                foreach ($e->rooms as $r) {
                    array_push($students, ...$r['students']);
                    if (!empty($r['proctor'])) {
                        $proctors[] = (int) $r['proctor'];
                    }
                }
                array_push($teachers, ...DB::table('course_teachers')->join('teachers', 'teachers.teacher_id', '=', 'course_teachers.teacher_id')
                    ->where('course_teachers.course_id', $e->course_id)->pluck('teachers.user_id')->all());
            }

            $studentUsers = DB::table('students')->whereIn('student_id', array_unique($students))->pluck('user_id');
            $kinds = array_keys($draft->settings['kinds'] ?? []);
            $what = count($kinds) === 2 ? 'جدول المذاكرات والامتحانات' : ($kinds === ['midterm'] ? 'جدول المذاكرات' : 'جدول الامتحانات');
            $now = now();
            $system = Access::systemSenderId();

            $make = fn ($uid, string $title, string $body, string $type) => [
                'user_id' => $uid, 'sender_id' => $system, 'title' => $title, 'message' => $body, 'type' => $type,
                'category' => 'academic', 'related_id' => $draft->id, 'is_read' => false, 'created_at' => $now, 'updated_at' => $now,
            ];

            $rows = [];
            foreach ($studentUsers->unique() as $uid) {
                $rows[] = $make($uid, "📝 اعتُمد {$what}", 'راجع موعد وقاعة كل مقرر من شاشة الامتحانات.', 'exam_schedule_approved');
            }
            foreach (array_unique(array_diff($teachers, $proctors)) as $uid) {
                $rows[] = $make($uid, "📝 اعتُمد {$what}", 'راجع مواعيد وقاعات تقييمات مقرراتك.', 'exam_schedule_approved');
            }
            foreach (array_unique($proctors) as $uid) {
                $rows[] = $make($uid, '👁️ عُيّنت مراقباً', "عُيّنت مراقباً في {$what}. راجع قاعاتك وفتراتك وقوائم طلابها.", 'exam_invigilation');
            }
            foreach (array_chunk($rows, 300) as $chunk) {
                Notification::insert($chunk);
            }
        } catch (\Throwable $e) {
            Log::warning('Exam schedule approval notification failed: ' . $e->getMessage());
        }
    }
}
