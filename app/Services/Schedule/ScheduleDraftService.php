<?php

namespace App\Services\Schedule;

use App\Models\Notification;
use App\Models\ScheduleDraft;
use App\Models\ScheduleDraftEntry;
use App\Models\User;
use App\Support\Access;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * دورة حياة مسودة الجدول: توليد ← تعديل (نقل/تبديل/تثبيت) ← إعادة توليد للمتبقي ← اعتماد ← (تراجع).
 *
 * المسودة لا تمسّ جدول schedules الحقيقي إلا عند الاعتماد، وفي معاملة واحدة تحفظ ما استبدلته للتراجع عنه.
 * كل تعديل يمر عبر ScheduleValidator نفسه، فلا يمكن حفظ مسودة تخالف قيدًا صلبًا.
 */
class ScheduleDraftService
{
    // ------------------------------------------------------------------ توليد

    /**
     * @param array{name?:string,seed?:int,periods?:int,program_ids?:int[]} $options
     */
    public function generate(User $by, array $options = []): ScheduleDraft
    {
        $deptId = $this->departmentOf($by);
        $problem = ScheduleProblem::forDepartment($deptId, $options);
        $result = (new ScheduleSolver())->solve($problem);

        $draft = DB::transaction(function () use ($by, $deptId, $problem, $options, $result) {
            $draft = ScheduleDraft::create([
                'department_id' => $deptId, 'created_by' => $by->user_id,
                'name' => $options['name'] ?? ('مسودة ' . now()->format('Y-m-d H:i')),
                'status' => ScheduleDraft::DRAFT,
                'settings' => ['days' => $problem->days, 'periods' => $problem->periods, 'seed' => $problem->seed, 'program_ids' => $options['program_ids'] ?? []],
                'stats' => null,
            ]);
            $this->storeEntries($draft, $problem, $result['entries']);
            $draft->update(['stats' => $this->stats($problem, $result['entries'], $result['unscheduled'], $result['restarts'])]);

            return $draft;
        });

        $this->pruneOld($deptId);

        return $draft->fresh();
    }

    /** يعيد التوليد للحصص غير المثبّتة فقط (المثبّتة تبقى كما هي). */
    public function regenerate(ScheduleDraft $draft, ?int $seed = null): ScheduleDraft
    {
        $this->assertEditable($draft);

        $seed ??= (($draft->settings['seed'] ?? 1) + 7);
        $locked = $draft->entries()->where('locked', true)->get()->map(fn ($e) => $this->toEntry($e))->all();

        $problem = ScheduleProblem::forDepartment($draft->department_id, $this->optionsOf($draft, $seed), $locked);
        $result = (new ScheduleSolver())->solve($problem);

        DB::transaction(function () use ($draft, $problem, $result, $seed) {
            $draft->entries()->where('locked', false)->delete();
            // المثبّتة موجودة أصلًا؛ نخزّن الباقي فقط
            $this->storeEntries($draft, $problem, array_filter($result['entries'], fn ($e) => !$e['locked']));
            $settings = $draft->settings;
            $settings['seed'] = $seed;
            $draft->update(['settings' => $settings]);
        });

        return $this->refreshStats($draft->fresh(), $result['unscheduled'], $result['restarts']);
    }

    // ------------------------------------------------------------------ تعديل يدوي

    /** ينقل حصة إلى يوم/فترة (وقاعة اختيارية) بشرط ألا يكسر قيدًا صلبًا. */
    public function move(ScheduleDraft $draft, int $entryId, string $day, int $period, ?string $room = null): ScheduleDraft
    {
        $this->assertEditable($draft);
        $entry = $draft->entries()->findOrFail($entryId);

        [$problem, $entries, $index] = $this->snapshot($draft, $entryId);
        $entries[$index]['day'] = $day;
        $entries[$index]['period'] = $period;
        if ($room !== null && $room !== '') {
            $entries[$index]['room'] = $room;
        }

        $violations = ScheduleValidator::forEntry($problem, $entries, $index);
        if ($violations) {
            throw new ScheduleRuleException(array_column($violations, 'message'));
        }

        $entry->update(['day' => $day, 'period' => $period, 'room' => $entries[$index]['room'], 'source' => 'manual']);

        return $this->refreshStats($draft->fresh());
    }

    /** يبدّل موعدي حصتين (من الشعبة نفسها أو من شعبتين) بشرط سلامة الناتج. */
    public function swap(ScheduleDraft $draft, int $a, int $b): ScheduleDraft
    {
        $this->assertEditable($draft);
        $ea = $draft->entries()->findOrFail($a);
        $eb = $draft->entries()->findOrFail($b);

        [$problem, $entries, $ia] = $this->snapshot($draft, $a);
        $ib = collect($entries)->search(fn ($e) => $e['id'] === $b);

        [$entries[$ia]['day'], $entries[$ib]['day']] = [$entries[$ib]['day'], $entries[$ia]['day']];
        [$entries[$ia]['period'], $entries[$ib]['period']] = [$entries[$ib]['period'], $entries[$ia]['period']];

        $violations = array_merge(ScheduleValidator::forEntry($problem, $entries, $ia), ScheduleValidator::forEntry($problem, $entries, $ib));
        if ($violations) {
            throw new ScheduleRuleException(array_values(array_unique(array_column($violations, 'message'))));
        }

        DB::transaction(function () use ($ea, $eb, $entries, $ia, $ib) {
            $ea->update(['day' => $entries[$ia]['day'], 'period' => $entries[$ia]['period'], 'source' => 'manual']);
            $eb->update(['day' => $entries[$ib]['day'], 'period' => $entries[$ib]['period'], 'source' => 'manual']);
        });

        return $this->refreshStats($draft->fresh());
    }

    public function setLocked(ScheduleDraft $draft, int $entryId, bool $locked): void
    {
        $this->assertEditable($draft);
        $draft->entries()->findOrFail($entryId)->update(['locked' => $locked]);
    }

    // ------------------------------------------------------------------ اعتماد وتراجع

    /**
     * يستبدل حصص القسم في schedules بحصص المسودة (معاملة واحدة) ويحفظ المستبدَل للتراجع.
     * يرفض إذا بقيت مخالفة صلبة، أو إذا وُجد غير مجدول دون إقرار صريح من رئيس القسم.
     */
    public function approve(ScheduleDraft $draft, User $by, bool $acknowledgeUnscheduled = false): ScheduleDraft
    {
        $this->assertEditable($draft);
        $draft = $this->refreshStats($draft);
        $stats = $draft->stats;

        if (!empty($stats['violations'])) {
            throw new ScheduleRuleException(['لا يمكن اعتماد مسودة فيها تعارضات: ' . implode('، ', array_slice(array_column($stats['violations'], 'message'), 0, 3))]);
        }
        if ($draft->entries()->count() === 0) {
            throw new ScheduleRuleException(['المسودة فارغة: لا حصص لاعتمادها']);
        }
        if (!empty($stats['unscheduled']) && !$acknowledgeUnscheduled) {
            throw new ScheduleRuleException(['في المسودة مواد لم تُجدول بالكامل. أقرّ بذلك صراحةً إن أردت اعتمادها كما هي']);
        }

        $problem = $this->problemFor($draft);
        $labels = array_values(array_map(fn ($g) => $g['label'], $problem->groups));
        $deptCourses = $this->departmentCourseIds($draft->department_id);

        DB::transaction(function () use ($draft, $by, $labels, $deptCourses) {
            $old = $this->scopedSchedules($labels, $deptCourses)->get();
            $draft->update(['backup' => $old->map(fn ($r) => (array) $r)->all()]);
            $this->scopedSchedules($labels, $deptCourses)->delete();

            $now = now();
            $periods = config('schedule.periods');
            $rows = $draft->entries()->get()->map(fn ($e) => [
                'course_id' => $e->course_id, 'teacher_id' => $e->teacher_user_id, 'class_group' => $e->class_group, 'day' => $e->day,
                'start_time' => $periods[$e->period][0] . ':00', 'end_time' => $periods[$e->period][1] . ':00', 'room' => $e->room,
                'created_at' => $now, 'updated_at' => $now,
            ])->all();
            DB::table('schedules')->insert($rows);

            $draft->update(['status' => ScheduleDraft::APPROVED, 'approved_by' => $by->user_id, 'approved_at' => $now, 'reverted_at' => null]);
        });

        $this->notifyApproved($draft->fresh(), $labels);

        return $draft->fresh();
    }

    /** يتراجع عن اعتماد آخر مسودة معتمدة للقسم: يعيد الحصص التي استبدلها كما كانت. */
    public function revert(ScheduleDraft $draft, User $by): ScheduleDraft
    {
        if ($draft->status !== ScheduleDraft::APPROVED || $draft->reverted_at !== null) {
            throw new ScheduleRuleException(['هذه المسودة ليست معتمَدة أو سبق التراجع عنها']);
        }

        $newer = ScheduleDraft::where('department_id', $draft->department_id)->where('status', ScheduleDraft::APPROVED)
            ->whereNull('reverted_at')->where('approved_at', '>', $draft->approved_at)->exists();
        if ($newer) {
            throw new ScheduleRuleException(['اعتُمدت مسودة أحدث بعدها: تراجعي عن الأحدث أولًا']);
        }

        $problem = $this->problemFor($draft);
        $labels = array_values(array_map(fn ($g) => $g['label'], $problem->groups));
        $deptCourses = $this->departmentCourseIds($draft->department_id);

        DB::transaction(function () use ($draft, $labels, $deptCourses) {
            $this->scopedSchedules($labels, $deptCourses)->delete();
            $rows = array_map(function ($r) {
                unset($r['schedule_id']);

                return $r;
            }, $draft->backup ?? []);
            if ($rows) {
                DB::table('schedules')->insert($rows);
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

    /** يعيد حساب الجودة والمخالفات وغير المجدول (بعد أي تعديل). */
    public function refreshStats(ScheduleDraft $draft, ?array $unscheduled = null, ?int $restarts = null): ScheduleDraft
    {
        $problem = $this->problemFor($draft);
        $entries = $draft->entries()->get()->map(fn ($e) => $this->toEntry($e))->all();

        $unscheduled ??= $this->missing($problem, $entries, $draft->stats['unscheduled'] ?? []);
        $draft->update(['stats' => $this->stats($problem, $entries, $unscheduled, $restarts ?? ($draft->stats['restarts'] ?? 1))]);

        return $draft->fresh();
    }

    private function stats(ScheduleProblem $p, array $entries, array $unscheduled, int $restarts): array
    {
        $entries = array_values($entries);
        $required = 0;
        foreach ($p->groups as $g) {
            foreach ($g['courses'] as $c) {
                $required += $c['sessions'];
            }
        }

        return [
            'required' => $required, 'placed' => count($entries), 'restarts' => $restarts,
            'unscheduled' => array_values($unscheduled),
            'violations' => ScheduleValidator::validate($p, $entries),
            'quality' => ScheduleQuality::evaluate($p, $entries),
            'capacity' => $p->capacityReport(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /** غير المجدول بعد تعديل يدوي: ما نقص من حصص كل مادة، مع سبب محفوظ إن عُرف. */
    private function missing(ScheduleProblem $p, array $entries, array $known): array
    {
        $placed = [];
        foreach ($entries as $e) {
            $placed[$e['group'] . '|' . $e['course_id']] = ($placed[$e['group'] . '|' . $e['course_id']] ?? 0) + 1;
        }
        $reasons = collect($known)->keyBy(fn ($u) => $u['group'] . '|' . $u['course_id']);

        $out = [];
        foreach ($p->groups as $gk => $g) {
            foreach ($g['courses'] as $c) {
                $miss = $c['sessions'] - ($placed[$gk . '|' . $c['course_id']] ?? 0);
                if ($miss <= 0) {
                    continue;
                }
                $prev = $reasons->get($gk . '|' . $c['course_id']);
                $out[] = [
                    'group' => $gk, 'group_label' => $g['label'], 'course_id' => $c['course_id'], 'title' => $c['title'], 'teacher' => $c['teacher'],
                    'missing' => $miss, 'of' => $c['sessions'],
                    'reason' => $prev['reason'] ?? ($c['teacher'] ? 'not_placed' : 'no_teacher'),
                    'message' => $prev['message'] ?? ($c['teacher'] ? 'لم توضع بعد' : 'لا معلم مرتبط بالمادة'),
                ];
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------ مساعدة

    public function departmentOf(User $user): int
    {
        $dept = (int) $user->role_id === 5 ? Access::headDepartment($user)['id'] : null;
        if (!$dept) {
            throw new ScheduleRuleException(['لا قسم مرتبط بحسابك لإنشاء جدول']);
        }

        return $dept;
    }

    public function problemFor(ScheduleDraft $draft): ScheduleProblem
    {
        return ScheduleProblem::forDepartment($draft->department_id, $this->optionsOf($draft));
    }

    private function optionsOf(ScheduleDraft $draft, ?int $seed = null): array
    {
        $s = $draft->settings;

        return ['days' => $s['days'], 'periods' => $s['periods'], 'seed' => $seed ?? $s['seed'], 'program_ids' => $s['program_ids'] ?? []];
    }

    /** @return array{0: ScheduleProblem, 1: array, 2: int} المسألة وكل الحصص وفهرس الحصة المطلوبة */
    private function snapshot(ScheduleDraft $draft, int $entryId): array
    {
        $problem = $this->problemFor($draft);
        $entries = $draft->entries()->orderBy('id')->get()->map(fn ($e) => $this->toEntry($e))->values()->all();
        $index = collect($entries)->search(fn ($e) => $e['id'] === $entryId);

        return [$problem, $entries, (int) $index];
    }

    public function toEntry(ScheduleDraftEntry $e): array
    {
        return [
            'id' => $e->id, 'group' => $e->program_id . '-' . $e->year, 'course_id' => (int) $e->course_id, 'teacher' => (int) $e->teacher_user_id,
            'day' => $e->day, 'period' => (int) $e->period, 'room' => $e->room, 'locked' => (bool) $e->locked, 'source' => $e->source,
        ];
    }

    private function storeEntries(ScheduleDraft $draft, ScheduleProblem $p, array $entries): void
    {
        $now = now();
        $rows = [];
        foreach ($entries as $e) {
            $g = $p->groups[$e['group']];
            $rows[] = [
                'draft_id' => $draft->id, 'course_id' => $e['course_id'], 'teacher_user_id' => $e['teacher'], 'program_id' => $g['program_id'],
                'year' => $g['year'], 'class_group' => $g['label'], 'day' => $e['day'], 'period' => $e['period'], 'room' => $e['room'],
                'locked' => $e['locked'] ?? false, 'source' => $e['source'] ?? 'auto', 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            ScheduleDraftEntry::insert($chunk);
        }
    }

    private function assertEditable(ScheduleDraft $draft): void
    {
        if ($draft->status !== ScheduleDraft::DRAFT) {
            throw new ScheduleRuleException(['المسودة مغلقة (معتمَدة أو مُهمَلة) ولا يمكن تعديلها']);
        }
    }

    private function pruneOld(int $deptId): void
    {
        $keep = (int) config('schedule.max_drafts_per_department');
        ScheduleDraft::where('department_id', $deptId)->where('status', '!=', ScheduleDraft::APPROVED)
            ->orderByDesc('id')->skip($keep)->take(1000)->get()->each->delete();
    }

    /** مواد القسم (كل المرتبطة ببرامجه). */
    public function departmentCourseIds(int $deptId): array
    {
        return DB::table('course_program')->join('programs', 'programs.id', '=', 'course_program.program_id')
            ->where('programs.department_id', $deptId)->pluck('course_program.course_id')->unique()->values()->all();
    }

    /** حصص القسم في schedules: شعبه المسماة، أو حصص قديمة بلا شعبة لمواد القسم. */
    private function scopedSchedules(array $labels, array $deptCourses)
    {
        return DB::table('schedules')->where(function ($q) use ($labels, $deptCourses) {
            $q->whereIn('class_group', $labels)
              ->orWhere(fn ($w) => $w->whereNull('class_group')->whereIn('course_id', $deptCourses));
        });
    }

    private function notifyApproved(ScheduleDraft $draft, array $labels): void
    {
        try {
            $teachers = $draft->entries()->distinct()->pluck('teacher_user_id');
            $students = DB::table('students')->join('users', 'users.user_id', '=', 'students.user_id')
                ->join('programs', 'programs.id', '=', 'students.program_id')
                ->where('users.status', 'active')->where('programs.department_id', $draft->department_id)
                ->pluck('users.user_id');

            $title = '📅 اعتُمد جدول دراسي جديد';
            $body = 'اعتمد رئيس القسم جدولًا دراسيًا جديدًا. راجع جدولك من شاشة الجدول الدراسي.';
            $now = now();
            $system = Access::systemSenderId();

            $rows = $teachers->merge($students)->unique()->map(fn ($uid) => [
                'user_id' => $uid, 'sender_id' => $system, 'title' => $title, 'message' => $body, 'type' => 'schedule_approved',
                'category' => 'academic', 'related_id' => $draft->id, 'is_read' => false, 'created_at' => $now, 'updated_at' => $now,
            ])->values()->all();

            foreach (array_chunk($rows, 300) as $chunk) {
                Notification::insert($chunk);
            }
        } catch (\Throwable $e) {
            Log::warning('Schedule approval notification failed: ' . $e->getMessage());
        }
    }
}
