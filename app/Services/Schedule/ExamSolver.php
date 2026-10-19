<?php

namespace App\Services\Schedule;

/**
 * يولّد جدولاً امتحانياً: لكل مهمة (شعبة × مقرر × نوع) يوم وفترة وقاعات، وتوزيع طلابها على القاعات.
 *
 * بناء جشع بترتيب الأصعب أولاً (أكثر تعارضاً، ثم أكثر طلاباً)، وإعادة بدء حتمية بعدة بذور ونختار الأفضل
 * (أقل غير مجدول، ثم أقل تكلفة ناعمة). نفس البذرة = نفس الجدول.
 *
 * القيود الصلبة لا تُكسر أبداً: يوم واحد لتقييم واحد للطلاب المشتركين، قاعة واحدة لتقييم واحد في الوقت الواحد،
 * سعة القاعات تتسع لكل الطلاب، المذاكرة قبل امتحان المقرر نفسه، والتقييم داخل فترة نوعه. ما لا يمكن وضعه
 * يُرجَع في unscheduled مع سبب صريح، ولا يُخفى ولا يُوضع قسراً.
 */
class ExamSolver
{
    private ExamProblem $p;
    private int $state = 1;

    /** @var array<string,array> key => entry */
    private array $placed = [];
    /** @var array<string,array<string,array<int,array{0:int,1:int}>>> room => date => [[start,end],...] */
    private array $occRoom = [];
    /** @var array<string,int> date => عدد التقييمات */
    private array $dayLoad = [];

    /** @var array<int,array> تقييمات مثبّتة لا يغيّرها المولّد */
    private array $fixed = [];

    /**
     * @param array<int,array> $fixed تقييمات مثبّتة (بنفس شكل ناتج المولّد) تُحجز أولاً ولا تُنقل
     * @return array{entries: array<int,array>, unscheduled: array<int,array>, required: int, placed: int, restarts: int, cost: float}
     */
    public function solve(ExamProblem $p, array $fixed = []): array
    {
        $this->p = $p;
        $this->fixed = $fixed;
        $best = null;
        $restarts = max(1, (int) config('exam.search.restarts'));

        for ($i = 0; $i < $restarts; $i++) {
            $r = $this->attempt($p->seed + $i * 7919);
            if ($best === null || [count($r['unscheduled']), $r['cost']] < [count($best['unscheduled']), $best['cost']]) {
                $best = $r;
            }
        }

        $best['restarts'] = $restarts;

        return $best;
    }

    private function attempt(int $seed): array
    {
        $this->state = max(1, $seed);
        $this->placed = [];
        $this->occRoom = [];
        $this->dayLoad = [];

        // المثبّتة أولاً: تحجز يومها وقاعاتها فلا يتعدّى عليها غيرها
        foreach ($this->fixed as $f) {
            $this->placed[$f['key']] = $f + ['cost' => 0.0];
            $this->dayLoad[$f['date']] = ($this->dayLoad[$f['date']] ?? 0) + 1;
            foreach ($f['rooms'] as $r) {
                $this->occRoom[$r['name']][$f['date']][] = [ExamProblem::minutes($f['start']), ExamProblem::minutes($f['end'])];
            }
        }

        $tasks = array_values(array_filter($this->orderedTasks(), fn ($t) => !isset($this->placed[$t['key']])));
        $unscheduled = [];
        $cost = 0.0;

        foreach ($tasks as $t) {
            if (!$this->placeTask($t, $why)) {
                $unscheduled[] = $this->unscheduledRow($t, $why ?? 'no_free_day');
            }
        }
        foreach ($this->placed as $e) {
            $cost += $e['cost'];
        }

        $entries = array_values(array_map(function ($e) {
            unset($e['cost']);

            return $e;
        }, $this->placed));
        usort($entries, fn ($a, $b) => [$a['date'], $a['start'], $a['group']] <=> [$b['date'], $b['start'], $b['group']]);

        return ['entries' => $entries, 'unscheduled' => $unscheduled, 'required' => count($this->p->tasks), 'placed' => count($entries), 'cost' => round($cost, 2)];
    }

    /**
     * قاعات تصلح لتقييم محدد في (تاريخ، وقت) بالنظر إلى بقية التقييمات (يُستثنى هو نفسه)، مع إعادة توزيع طلابه.
     * تستعملها خدمة المسودة عند نقل تقييم يدوياً. null = لا قاعات كافية.
     *
     * @param array<int,array> $entries كل تقييمات المسودة
     * @return array<int,array{name:string,capacity:int,students:int[],proctor:?int}>|null
     */
    public static function roomsFor(ExamProblem $p, array $entries, int $index, string $date, string $start, string $end): ?array
    {
        $s = new self();
        $s->p = $p;
        foreach ($entries as $i => $e) {
            if ($i === $index) {
                continue;
            }
            foreach ($e['rooms'] as $r) {
                $s->occRoom[$r['name']][$e['date']][] = [ExamProblem::minutes($e['start']), ExamProblem::minutes($e['end'])];
            }
        }
        $t = $p->tasks[$entries[$index]['key']];
        $rooms = $s->pickRooms($t, $date, ExamProblem::minutes($start), ExamProblem::minutes($end));

        return $rooms === null ? null : self::distribute($t['students'], $rooms, $p->studentNames);
    }

    private function orderedTasks(): array
    {
        $tasks = array_values($this->p->tasks);
        $jitter = [];
        foreach ($tasks as $t) {
            $jitter[$t['key']] = $this->rand();
        }
        usort($tasks, fn ($a, $b) => [-count($this->p->conflicts[$a['key']]), -$a['size'], $a['kind'] === 'midterm' ? 0 : 1, $jitter[$a['key']]] <=> [-count($this->p->conflicts[$b['key']]), -$b['size'], $b['kind'] === 'midterm' ? 0 : 1, $jitter[$b['key']]]);

        return $tasks;
    }

    private function placeTask(array $t, ?string &$why): bool
    {
        $why = null;
        $best = null;
        $reasons = ['no_free_day' => 0, 'no_room' => 0, 'order' => 0];
        $cfg = $this->p->kinds[$t['kind']];

        foreach ($this->p->calendar[$t['kind']] as $di => $date) {
            if (!$this->dayFree($t, $date)) {
                $reasons['no_free_day']++;
                continue;
            }
            if (!$this->orderOk($t, $date)) {
                $reasons['order']++;
                continue;
            }
            foreach ($cfg['slots'] as $si => [$start, $end]) {
                // المدة داخل الفترة اليومية؛ الامتحان يبدأ مع بداية الفترة
                $finish = ExamProblem::minutes($start) + $cfg['duration'];
                $rooms = $this->pickRooms($t, $date, ExamProblem::minutes($start), $finish);
                if ($rooms === null) {
                    $reasons['no_room']++;
                    continue;
                }
                $c = $this->cost($t, $date, $di, $si, $rooms);
                $key = [$c, $this->rand()];
                if ($best === null || $key < $best['key']) {
                    $best = ['key' => $key, 'date' => $date, 'slot' => $si, 'start' => $start, 'finish' => $finish, 'rooms' => $rooms, 'cost' => $c];
                }
            }
        }

        if (!$best) {
            arsort($reasons);
            $why = array_key_first($reasons);

            return false;
        }

        $this->commit($t, $best);

        return true;
    }

    /** لا تقييم آخر في اليوم نفسه لأي طالب مشترك مع هذه المهمة. */
    private function dayFree(array $t, string $date): bool
    {
        foreach ($this->p->conflicts[$t['key']] as $other) {
            if (isset($this->placed[$other]) && $this->placed[$other]['date'] === $date) {
                return false;
            }
        }

        return true;
    }

    /** المذاكرة قبل امتحان المقرر نفسه (للشعبة نفسها) إذا كان النوعان مطلوبين. */
    private function orderOk(array $t, string $date): bool
    {
        $mate = $t['group'] . '|' . $t['course_id'] . '|' . ($t['kind'] === 'midterm' ? 'final' : 'midterm');
        if (!isset($this->placed[$mate])) {
            return true;
        }

        return $t['kind'] === 'final' ? $this->placed[$mate]['date'] < $date : $date < $this->placed[$mate]['date'];
    }

    /**
     * قاعات حرة تتسع لكل طلاب المهمة: قاعة الشعبة الخاصة أولاً، ثم أكبر القاعات الحرة حتى تكفي السعة.
     * يرجع null إن لم تتسع. كل القاعات متاحة وقت الامتحان (لا حصص أسبوعية)، بخلاف قاعات الشعب في الجدول الأسبوعي.
     *
     * @return array<int,array{name:string,capacity:int}>|null
     */
    private function pickRooms(array $t, string $date, int $from, int $to): ?array
    {
        if ($t['size'] === 0) {
            $home = $this->p->homeRoom[$t['group']] ?? null;
            $name = $home ?? (array_key_first($this->p->rooms) ?? 'قاعة ' . $t['label']);

            return [['name' => $name, 'capacity' => (int) ($this->p->rooms[$name] ?? 0)]];
        }

        $free = [];
        foreach ($this->p->rooms as $name => $cap) {
            if ($this->roomFree($name, $date, $from, $to)) {
                $free[$name] = $cap;
            }
        }
        if (!$free) {
            return null;
        }

        $chosen = [];
        $sum = 0;
        $home = $this->p->homeRoom[$t['group']] ?? null;
        if ($home !== null && isset($free[$home])) {
            $chosen[] = ['name' => $home, 'capacity' => $free[$home]];
            $sum += $free[$home];
            unset($free[$home]);
        }

        uksort($free, fn ($a, $b) => [-$free[$a], $a] <=> [-$free[$b], $b]);
        foreach ($free as $name => $cap) {
            if ($sum >= $t['size']) {
                break;
            }
            $chosen[] = ['name' => $name, 'capacity' => $cap];
            $sum += $cap;
        }

        return $sum >= $t['size'] ? $chosen : null;
    }

    private function roomFree(string $room, string $date, int $from, int $to): bool
    {
        foreach ($this->occRoom[$room][$date] ?? [] as [$a, $b]) {
            if ($from < $b && $a < $to) {
                return false;
            }
        }

        return true;
    }

    private function cost(array $t, string $date, int $di, int $si, array $rooms): float
    {
        $w = $this->p->weights;
        $c = $w['early_bias'] * $di + $w['day_load'] * ($this->dayLoad[$date] ?? 0) + $w['late_slot'] * $si;
        $home = $this->p->homeRoom[$t['group']] ?? null;
        $c += $w['extra_room'] * count(array_filter($rooms, fn ($r) => $r['name'] !== $home));

        // تباعد: تقييم لنفس الطلاب بعد يوم أو يومين فقط أسوأ من الأبعد
        foreach ($this->p->conflicts[$t['key']] as $other) {
            if (!isset($this->placed[$other])) {
                continue;
            }
            $gap = abs((strtotime($date) - strtotime($this->placed[$other]['date'])) / 86400);
            if ($gap < 3) {
                $c += $w['tight_gap'] * (3 - $gap) / 2;
            }
        }

        return round($c, 3);
    }

    private function commit(array $t, array $b): void
    {
        $distributed = self::distribute($t['students'], $b['rooms'], $this->p->studentNames);

        $entry = [
            'key' => $t['key'], 'kind' => $t['kind'], 'course_id' => $t['course_id'], 'group' => $t['group'],
            'date' => $b['date'], 'slot' => $b['slot'], 'start' => $b['start'], 'end' => self::hhmm($b['finish']),
            'duration' => $this->p->kinds[$t['kind']]['duration'], 'rooms' => $distributed,
            'locked' => false, 'source' => 'auto', 'cost' => $b['cost'],
        ];
        $this->placed[$t['key']] = $entry;
        $this->dayLoad[$b['date']] = ($this->dayLoad[$b['date']] ?? 0) + 1;
        foreach ($b['rooms'] as $r) {
            $this->occRoom[$r['name']][$b['date']][] = [ExamProblem::minutes($b['start']), $b['finish']];
        }
    }

    /**
     * يوزّع الطلاب (مرتبين بالاسم) على القاعات بشكل متوازن متناسب مع السعة، وبحيث لا تتجاوز قاعة سعتها.
     *
     * @param int[] $studentIds
     * @param array<int,array{name:string,capacity:int}> $rooms
     * @param array<int,string> $names
     * @return array<int,array{name:string,capacity:int,students:int[],proctor:?int}>
     */
    public static function distribute(array $studentIds, array $rooms, array $names): array
    {
        usort($studentIds, fn ($a, $b) => [$names[$a] ?? '', $a] <=> [$names[$b] ?? '', $b]);
        $n = count($studentIds);
        $total = array_sum(array_column($rooms, 'capacity')) ?: 1;

        // حصة كل قاعة بالتناسب مع سعتها، ثم نوزّع الباقي على القاعات التي فيها متسع
        $share = [];
        foreach ($rooms as $i => $r) {
            $share[$i] = min($r['capacity'], (int) floor($n * $r['capacity'] / $total));
        }
        $left = $n - array_sum($share);
        for ($guard = 0; $left > 0 && $guard < 100000; $guard++) {
            foreach ($rooms as $i => $r) {
                if ($left > 0 && $share[$i] < $r['capacity']) {
                    $share[$i]++;
                    $left--;
                }
            }
        }

        $out = [];
        $offset = 0;
        foreach ($rooms as $i => $r) {
            $out[] = ['name' => $r['name'], 'capacity' => $r['capacity'], 'students' => array_slice($studentIds, $offset, $share[$i]), 'proctor' => null];
            $offset += $share[$i];
        }

        return $out;
    }

    private function unscheduledRow(array $t, string $why): array
    {
        $kindLabel = ExamProblem::KINDS[$t['kind']];
        $messages = [
            'no_free_day' => 'لا يوجد يوم خالٍ لطلاب هذا المقرر في الفترة (يوم واحد لتقييم واحد). مدّد الفترة أو أضف أياماً.',
            'no_room' => 'لا قاعات حرة تتسع لكل طلابه في أي فترة. أضف قاعات أو فترات يومية.',
            'order' => 'تعذّر وضعه قبل/بعد التقييم الآخر لنفس المقرر. مدّد الفترتين.',
        ];

        return [
            'key' => $t['key'], 'group' => $t['group'], 'group_label' => $t['label'], 'course_id' => $t['course_id'], 'title' => $t['title'],
            'kind' => $t['kind'], 'kind_label' => $kindLabel, 'reason' => $why, 'message' => $messages[$why] ?? $messages['no_free_day'],
        ];
    }

    private static function hhmm(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /** مولّد أعداد حتمي (xorshift) مستقل عن mt_rand. */
    private function rand(): float
    {
        $x = $this->state & 0xFFFFFFFF;
        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0xFFFFFFFF;
        $this->state = $x ?: 1;

        return $x / 4294967296;
    }
}
