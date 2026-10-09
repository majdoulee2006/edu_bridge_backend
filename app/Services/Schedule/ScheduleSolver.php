<?php

namespace App\Services\Schedule;

/**
 * يولّد جدولًا لقسم: بناء جشع بترتيب الأصعب أولًا (معلمون كثيرو الحصص وشعب مكتظة)، ثم إصلاح بإزاحة حصة واحدة
 * عند الانسداد، ثم تحسين محلي (نقل/تبديل حصص داخل الشعبة) يخفض التكلفة الناعمة. حتمي: نفس البذرة = نفس الجدول.
 *
 * القيود الصلبة لا تُكسر أبدًا (تعارض شعبة/معلم/قاعة، عدم توافر المعلم، حصة واحدة للمادة يوميًا، سعة القاعة).
 * ما لا يمكن وضعه بدون كسرها يُرجَع في unscheduled مع سبب صريح، ولا يُخفى ولا يُوضع قسرًا.
 */
final class ScheduleSolver
{
    /** @var array<string,bool> */
    private array $occGroup = [];
    private array $occTeacher = [];
    private array $occRoom = [];
    /** @var array<string,int> "group|course|day" => count */
    private array $courseDay = [];
    /** @var array<int,array> */
    private array $entries = [];
    /** @var array<string,string> آخر قاعة استعملتها كل شعبة (لثبات القاعة) */
    private array $lastRoom = [];
    private int $rng = 1;

    private ScheduleProblem $p;

    /** @return array{entries: array, unscheduled: array, quality: array, restarts: int, required: int, placed: int} */
    public function solve(ScheduleProblem $p): array
    {
        $best = null;
        $restarts = max(1, (int) config('schedule.search.restarts'));
        $ran = 0;

        for ($r = 0; $r < $restarts; $r++) {
            $ran++;
            $res = $this->attempt($p, $p->seed + $r);
            if ($best === null || $this->better($res, $best)) {
                $best = $res;
            }
            if (!$res['unscheduled'] && $res['quality']['total'] == 0.0) {
                break;   // لا يمكن التحسن أكثر
            }
        }

        $best['restarts'] = $ran;

        return $best;
    }

    private function better(array $a, array $b): bool
    {
        // الأكثر وضعًا للحصص أولًا، ثم الأقل تكلفة
        return [-$a['placed'], $a['quality']['total']] < [-$b['placed'], $b['quality']['total']];
    }

    // ------------------------------------------------------------------ محاولة واحدة

    private function attempt(ScheduleProblem $p, int $seed): array
    {
        $this->p = $p;
        $this->occGroup = $this->occTeacher = $this->occRoom = $this->courseDay = $this->lastRoom = [];
        $this->entries = [];
        $this->rng = max(1, $seed * 2654435761 % 2147483647);

        foreach ($p->fixed as $f) {
            $this->place($f['group'], $f['course_id'], $f['teacher'], $f['day'], $f['period'], $f['room'], true, $f['source'] ?? 'manual');
        }

        [$tasks, $unscheduled, $required] = $this->tasks();

        foreach ($tasks as $t) {
            if (!$this->placeTask($t)) {
                $this->recordUnscheduled($unscheduled, $t);
            }
        }

        $this->improve();

        $entries = array_values(array_map(fn ($e) => $e, $this->entries));
        $quality = ScheduleQuality::evaluate($p, $entries);

        return [
            'entries' => $entries, 'unscheduled' => array_values($unscheduled), 'quality' => $quality,
            'required' => $required, 'placed' => count($entries),
        ];
    }

    /** @return array{0: array, 1: array, 2: int} المهام مرتبة، غير المجدول، إجمالي المطلوب */
    private function tasks(): array
    {
        $p = $this->p;
        $placedFixed = [];
        foreach ($p->fixed as $f) {
            $k = $f['group'] . '|' . $f['course_id'];
            $placedFixed[$k] = ($placedFixed[$k] ?? 0) + 1;
        }

        $teacherLoad = [];
        foreach ($p->groups as $g) {
            foreach ($g['courses'] as $c) {
                if ($c['teacher']) {
                    $teacherLoad[$c['teacher']] = ($teacherLoad[$c['teacher']] ?? 0) + $c['sessions'];
                }
            }
        }

        $tasks = [];
        $unscheduled = [];
        $required = 0;

        foreach ($p->groups as $gk => $g) {
            $needed = array_sum(array_map(fn ($c) => $c['teacher'] ? $c['sessions'] : 0, $g['courses']));
            $density = $needed / max(1, $p->slotCount());

            foreach ($g['courses'] as $c) {
                $required += $c['sessions'];
                $remaining = $c['sessions'] - ($placedFixed[$gk . '|' . $c['course_id']] ?? 0);
                if ($remaining <= 0) {
                    continue;
                }

                if (!$c['teacher']) {
                    $unscheduled[$gk . '|' . $c['course_id']] = $this->unscheduledRow($gk, $c, $remaining, 'no_teacher',
                        'لا معلم مرتبط بالمادة، عيّني معلمًا لها أولًا');
                    continue;
                }

                for ($i = 0; $i < $remaining; $i++) {
                    $tasks[] = ['group' => $gk, 'course' => $c, 'load' => $teacherLoad[$c['teacher']], 'density' => $density, 'jitter' => $this->rand()];
                }
            }
        }

        usort($tasks, fn ($a, $b) => [$b['load'], $b['density'], $b['course']['sessions'], $a['jitter']] <=> [$a['load'], $a['density'], $a['course']['sessions'], $b['jitter']]);

        return [$tasks, $unscheduled, $required];
    }

    // ------------------------------------------------------------------ الوضع والفحص

    private function place(string $g, int $courseId, int $teacher, string $day, int $period, string $room, bool $locked = false, string $source = 'auto'): int
    {
        $idx = count($this->entries) ? max(array_keys($this->entries)) + 1 : 0;
        $this->entries[$idx] = ['group' => $g, 'course_id' => $courseId, 'teacher' => $teacher, 'day' => $day, 'period' => $period, 'room' => $room, 'locked' => $locked, 'source' => $source];
        $this->occGroup["$g|$day|$period"] = true;
        $this->occTeacher["$teacher|$day|$period"] = true;
        $this->occRoom["$room|$day|$period"] = true;
        $k = "$g|$courseId|$day";
        $this->courseDay[$k] = ($this->courseDay[$k] ?? 0) + 1;
        $this->lastRoom[$g] = $room;

        return $idx;
    }

    private function remove(int $idx): array
    {
        $e = $this->entries[$idx];
        unset($this->entries[$idx]);
        unset($this->occGroup["{$e['group']}|{$e['day']}|{$e['period']}"], $this->occTeacher["{$e['teacher']}|{$e['day']}|{$e['period']}"], $this->occRoom["{$e['room']}|{$e['day']}|{$e['period']}"]);
        $k = "{$e['group']}|{$e['course_id']}|{$e['day']}";
        $this->courseDay[$k]--;

        return $e;
    }

    /**
     * هل يمكن وضع حصة هنا بلا كسر قيد صلب؟ يرجع القاعة المختارة أو null.
     * السبب عند الفشل يُملأ في $why (لتقرير غير المجدول).
     */
    private function feasibleRoom(string $g, int $courseId, int $teacher, string $day, int $period, ?string &$why = null): ?string
    {
        $p = $this->p;
        $slot = "$day|$period";

        if (isset($this->occGroup["$g|$slot"])) {
            $why = 'group_busy';

            return null;
        }
        if (($this->courseDay["$g|$courseId|$day"] ?? 0) > 0) {
            $why = 'course_day';

            return null;
        }
        if (isset($p->unavailable["$teacher|$slot"])) {
            $why = 'teacher_unavailable';

            return null;
        }
        if (isset($this->occTeacher["$teacher|$slot"]) || isset($p->busyTeacher["$teacher|$slot"])) {
            $why = 'teacher_busy';

            return null;
        }

        if (!$p->rooms) {
            return 'قاعة ' . $p->groups[$g]['label'];   // قاعة ضمنية للشعبة: لا تعارض ممكن لأن الشعبة لا تحضر حصتين معًا
        }

        $size = $p->groups[$g]['size'];
        $pool = $p->rooms;
        usort($pool, function ($a, $b) use ($g, $p) {
            // نفضّل قاعة الشعبة السابقة (ثبات)، ثم الأصغر سعة الكافية
            $pa = ($this->lastRoom[$g] ?? null) === $a ? 0 : 1;
            $pb = ($this->lastRoom[$g] ?? null) === $b ? 0 : 1;

            return [$pa, $p->roomCapacity[$a] ?? PHP_INT_MAX, $a] <=> [$pb, $p->roomCapacity[$b] ?? PHP_INT_MAX, $b];
        });

        foreach ($pool as $room) {
            $cap = $p->roomCapacity[$room] ?? null;
            if ($cap !== null && $size !== null && $size > $cap) {
                continue;
            }
            if (isset($this->occRoom["$room|$slot"]) || isset($p->busyRoom["$room|$slot"])) {
                continue;
            }

            return $room;
        }

        $why = 'no_room';

        return null;
    }

    // ------------------------------------------------------------------ وضع مهمة

    private function placeTask(array $t): bool
    {
        $g = $t['group'];
        $c = $t['course'];
        $best = null;

        foreach ($this->p->days as $di => $day) {
            for ($period = 1; $period <= $this->p->periods; $period++) {
                $room = $this->feasibleRoom($g, $c['course_id'], $c['teacher'], $day, $period);
                if ($room === null) {
                    continue;
                }
                $cost = $this->marginalCost($g, $c, $day, $di, $period);
                $key = [$cost, $this->rand()];   // التعادل يُكسر بعشوائية حتمية
                if ($best === null || $key < $best['key']) {
                    $best = ['key' => $key, 'day' => $day, 'period' => $period, 'room' => $room];
                }
            }
        }

        if ($best) {
            $this->place($g, $c['course_id'], $c['teacher'], $best['day'], $best['period'], $best['room']);

            return true;
        }

        return $this->repair($t);
    }

    /** تكلفة ناعمة تقريبية لوضع الحصة (الفروق فقط، لاختيار أفضل مكان). */
    private function marginalCost(string $g, array $c, string $day, int $di, int $period): float
    {
        $w = $this->p->weights;
        $cost = 0.0;

        $periods = [];
        $load = [];
        $courseDays = [];
        foreach ($this->entries as $e) {
            if ($e['group'] !== $g) {
                continue;
            }
            $load[$e['day']] = ($load[$e['day']] ?? 0) + 1;
            if ($e['day'] === $day) {
                $periods[] = $e['period'];
            }
            if ($e['course_id'] === $c['course_id']) {
                $courseDays[] = array_search($e['day'], $this->p->days, true);
            }
        }

        if ($periods) {
            $before = (max($periods) - min($periods) + 1) - count($periods);
            $all = array_merge($periods, [$period]);
            $after = (max($all) - min($all) + 1) - count($all);
            $cost += $w['gap'] * ($after - $before);
        }

        $total = array_sum(array_map(fn ($cc) => $cc['sessions'], $this->p->groups[$g]['courses']));
        $mean = $total / max(1, count($this->p->days));
        $l = $load[$day] ?? 0;
        $cost += $w['imbalance'] * (abs($l + 1 - $mean) - abs($l - $mean));

        foreach ($courseDays as $d) {
            $cost += abs($d - $di) === 1 ? $w['adjacent_day'] : 0;
        }
        $cost += $period > 5 ? $w['late_period'] : 0;
        $cost += isset($this->p->avoid["{$c['teacher']}|$day|$period"]) ? $w['avoid_slot'] : 0;

        return $cost;
    }

    /**
     * إصلاح بإزاحة واحدة: إن كانت الفترة الوحيدة المناسبة لمهمتنا مشغولة بحصة غير مثبّتة من الشعبة نفسها،
     * نحاول نقل تلك الحصة إلى فترة أخرى صالحة ثم نضع مهمتنا مكانها.
     */
    private function repair(array $t): bool
    {
        $g = $t['group'];
        $c = $t['course'];

        foreach ($this->p->days as $day) {
            for ($period = 1; $period <= $this->p->periods; $period++) {
                $blocker = null;
                foreach ($this->entries as $i => $e) {
                    if ($e['group'] === $g && $e['day'] === $day && $e['period'] === $period && !$e['locked']) {
                        $blocker = $i;
                    }
                }
                if ($blocker === null) {
                    continue;
                }

                $old = $this->remove($blocker);
                $room = $this->feasibleRoom($g, $c['course_id'], $c['teacher'], $day, $period);
                if ($room === null) {
                    $this->restore($old);
                    continue;
                }

                $this->place($g, $c['course_id'], $c['teacher'], $day, $period, $room);
                $moved = $this->relocate($old);
                if ($moved) {
                    return true;
                }

                // تعذّر نقل الحصة المُزاحة: نتراجع بالكامل
                $mine = array_key_last($this->entries);
                $this->remove($mine);
                $this->restore($old);
            }
        }

        return false;
    }

    private function relocate(array $e): bool
    {
        foreach ($this->p->days as $day) {
            for ($period = 1; $period <= $this->p->periods; $period++) {
                $room = $this->feasibleRoom($e['group'], $e['course_id'], $e['teacher'], $day, $period);
                if ($room !== null) {
                    $this->place($e['group'], $e['course_id'], $e['teacher'], $day, $period, $room, false, $e['source']);

                    return true;
                }
            }
        }

        return false;
    }

    private function restore(array $e): void
    {
        $this->place($e['group'], $e['course_id'], $e['teacher'], $e['day'], $e['period'], $e['room'], $e['locked'], $e['source']);
    }

    // ------------------------------------------------------------------ تحسين محلي

    private function improve(): void
    {
        $iterations = (int) config('schedule.search.iterations');
        $movable = array_keys(array_filter($this->entries, fn ($e) => !$e['locked']));
        if (count($movable) < 2 || $iterations <= 0) {
            return;
        }

        for ($i = 0; $i < $iterations; $i++) {
            $a = $movable[(int) floor($this->rand() * count($movable))];
            if (!isset($this->entries[$a])) {
                continue;
            }
            $this->rand() < 0.7 ? $this->tryMove($a) : $this->trySwap($a, $movable[(int) floor($this->rand() * count($movable))]);
        }
    }

    private function groupCostOf(string $g): float
    {
        return ScheduleQuality::groupCost($this->p, array_values(array_filter($this->entries, fn ($e) => $e['group'] === $g)))['total'];
    }

    private function tryMove(int $idx): void
    {
        $e = $this->entries[$idx];
        $before = $this->groupCostOf($e['group']);
        $day = $this->p->days[(int) floor($this->rand() * count($this->p->days))];
        $period = 1 + (int) floor($this->rand() * $this->p->periods);
        if ($day === $e['day'] && $period === $e['period']) {
            return;
        }

        $old = $this->remove($idx);
        $room = $this->feasibleRoom($old['group'], $old['course_id'], $old['teacher'], $day, $period);
        if ($room === null) {
            $this->restoreAt($idx, $old);

            return;
        }

        $new = $this->place($old['group'], $old['course_id'], $old['teacher'], $day, $period, $room, false, $old['source']);
        if ($this->groupCostOf($old['group']) < $before - 1e-9) {
            return;   // تحسّن
        }

        $this->remove($new);
        $this->restoreAt($idx, $old);
    }

    private function trySwap(int $a, int $b): void
    {
        if ($a === $b || !isset($this->entries[$a], $this->entries[$b])) {
            return;
        }
        $ea = $this->entries[$a];
        $eb = $this->entries[$b];
        if ($ea['group'] !== $eb['group'] || ($ea['day'] === $eb['day'] && $ea['period'] === $eb['period'])) {
            return;
        }

        $before = $this->groupCostOf($ea['group']);
        $oa = $this->remove($a);
        $ob = $this->remove($b);

        $ra = $this->feasibleRoom($oa['group'], $oa['course_id'], $oa['teacher'], $ob['day'], $ob['period']);
        $na = $ra !== null ? $this->place($oa['group'], $oa['course_id'], $oa['teacher'], $ob['day'], $ob['period'], $ra, false, $oa['source']) : null;
        $rb = $na !== null ? $this->feasibleRoom($ob['group'], $ob['course_id'], $ob['teacher'], $oa['day'], $oa['period']) : null;

        if ($na !== null && $rb !== null) {
            $nb = $this->place($ob['group'], $ob['course_id'], $ob['teacher'], $oa['day'], $oa['period'], $rb, false, $ob['source']);
            if ($this->groupCostOf($oa['group']) < $before - 1e-9) {
                return;
            }
            $this->remove($nb);
        }
        if ($na !== null) {
            $this->remove($na);
        }

        $this->restoreAt($a, $oa);
        $this->restoreAt($b, $ob);
    }

    /** يعيد حصة بنفس فهرسها الأصلي (يحافظ على ثبات الفهارس أثناء البحث). */
    private function restoreAt(int $idx, array $e): void
    {
        $this->entries[$idx] = $e;
        $this->occGroup["{$e['group']}|{$e['day']}|{$e['period']}"] = true;
        $this->occTeacher["{$e['teacher']}|{$e['day']}|{$e['period']}"] = true;
        $this->occRoom["{$e['room']}|{$e['day']}|{$e['period']}"] = true;
        $k = "{$e['group']}|{$e['course_id']}|{$e['day']}";
        $this->courseDay[$k] = ($this->courseDay[$k] ?? 0) + 1;
    }

    // ------------------------------------------------------------------ تقرير غير المجدول

    private function recordUnscheduled(array &$unscheduled, array $t): void
    {
        $c = $t['course'];
        $key = $t['group'] . '|' . $c['course_id'];

        if (isset($unscheduled[$key])) {
            $unscheduled[$key]['missing']++;

            return;
        }

        [$reason, $message] = $this->explain($t);
        $unscheduled[$key] = $this->unscheduledRow($t['group'], $c, 1, $reason, $message);
    }

    /** @return array{0:string,1:string} سبب الفشل الغالب ونصه */
    private function explain(array $t): array
    {
        $g = $t['group'];
        $c = $t['course'];
        $counts = [];
        $free = 0;

        foreach ($this->p->days as $day) {
            for ($period = 1; $period <= $this->p->periods; $period++) {
                $why = null;
                if ($this->feasibleRoom($g, $c['course_id'], $c['teacher'], $day, $period, $why) === null) {
                    $counts[$why] = ($counts[$why] ?? 0) + 1;
                } else {
                    $free++;
                }
            }
        }

        $slots = $this->p->slotCount();

        // المادة وُزّعت على كل أيام الأسبوع وقاعدة "حصة واحدة يوميًا" تمنع حصة إضافية
        $usedDays = 0;
        foreach ($this->p->days as $day) {
            $usedDays += ($this->courseDay["$g|{$c['course_id']}|$day"] ?? 0) > 0 ? 1 : 0;
        }
        if ($usedDays >= count($this->p->days)) {
            return ['course_days_exhausted', 'المادة موزعة على كل أيام الأسبوع (' . count($this->p->days) . ') وقاعدة حصة واحدة يوميًا تمنع حصة إضافية'];
        }

        $groupFull = ($counts['group_busy'] ?? 0) >= $slots;
        $teacher = $this->p->teacherName($c['teacher']);

        if ($groupFull) {
            $needed = array_sum(array_map(fn ($cc) => $cc['teacher'] ? $cc['sessions'] : 0, $this->p->groups[$g]['courses']));

            return ['group_full', "الشعبة ممتلئة: تحتاج {$needed} حصة أسبوعيًا والشبكة فيها {$slots} فترة فقط"];
        }
        if (($counts['teacher_busy'] ?? 0) + ($counts['teacher_unavailable'] ?? 0) >= $slots - ($counts['group_busy'] ?? 0) - ($counts['course_day'] ?? 0)) {
            return ['teacher_conflict', "{$teacher} مشغول أو غير متاح في كل الفترات الفارغة للشعبة"];
        }
        if (($counts['no_room'] ?? 0) > 0 && $free === 0) {
            return ['no_room', 'لا قاعة متاحة بسعة كافية في الفترات الفارغة'];
        }

        return ['no_slot', 'لا فترة صالحة دون كسر قيد صلب (تعارض معلم أو قاعة أو قاعدة حصة واحدة يوميًا)'];
    }

    private function unscheduledRow(string $g, array $c, int $missing, string $reason, string $message): array
    {
        return [
            'group' => $g, 'group_label' => $this->p->groups[$g]['label'], 'course_id' => $c['course_id'], 'title' => $c['title'],
            'teacher' => $c['teacher'], 'missing' => $missing, 'of' => $c['sessions'], 'reason' => $reason, 'message' => $message,
        ];
    }

    /** مولّد أعداد حتمي (xorshift) مستقل عن mt_rand كي لا يتأثر بحالة عامة. */
    private function rand(): float
    {
        $x = $this->rng;
        $x ^= ($x << 13) & 0x7FFFFFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0x7FFFFFFF;
        $this->rng = $x === 0 ? 1 : $x;

        return ($this->rng % 1000003) / 1000003;
    }
}
