<?php

namespace App\Services\Schedule;

/**
 * جودة الجدول (القيود الناعمة). كلها تُحسب لكل شعبة على حدة، لأن تفضيلات الشعبة لا تتأثر بغيرها.
 * التكلفة الأقل أفضل؛ صفر = لا فراغات ولا اختلال ولا تجاور أيام ولا فترات متأخرة ولا تجاوز لتفضيلات المعلمين.
 */
final class ScheduleQuality
{
    /**
     * @param array<int,array> $entries حصص شعبة واحدة
     * @return array{total: float, gaps: int, imbalance: float, adjacent: int, late: int, avoid: int}
     */
    public static function groupCost(ScheduleProblem $p, array $entries): array
    {
        $w = $p->weights;
        $dayIndex = array_flip($p->days);

        $perDay = [];
        $courseDays = [];
        $late = $avoid = 0;

        foreach ($entries as $e) {
            $perDay[$e['day']][] = $e['period'];
            $courseDays[$e['course_id']][] = $dayIndex[$e['day']] ?? 0;
            $late += $e['period'] > 5 ? 1 : 0;
            $avoid += isset($p->avoid["{$e['teacher']}|{$e['day']}|{$e['period']}"]) ? 1 : 0;
        }

        $gaps = 0;
        foreach ($perDay as $periods) {
            $gaps += (max($periods) - min($periods) + 1) - count($periods);
        }

        $n = count($entries);
        $mean = $n / max(1, count($p->days));
        $imbalance = 0.0;
        foreach ($p->days as $d) {
            $imbalance += abs(count($perDay[$d] ?? []) - $mean);
        }

        $adjacent = 0;
        foreach ($courseDays as $days) {
            sort($days);
            for ($i = 1; $i < count($days); $i++) {
                $adjacent += ($days[$i] - $days[$i - 1] === 1) ? 1 : 0;
            }
        }

        $total = $w['gap'] * $gaps + $w['imbalance'] * $imbalance + $w['adjacent_day'] * $adjacent
            + $w['late_period'] * $late + $w['avoid_slot'] * $avoid;

        return ['total' => round($total, 2), 'gaps' => $gaps, 'imbalance' => round($imbalance, 2), 'adjacent' => $adjacent, 'late' => $late, 'avoid' => $avoid];
    }

    /**
     * جودة الجدول كله مع تفصيل كل شعبة.
     *
     * @param array<int,array> $entries
     * @return array{total: float, gaps: int, imbalance: float, adjacent: int, late: int, avoid: int, groups: array<string,array>}
     */
    public static function evaluate(ScheduleProblem $p, array $entries): array
    {
        $by = [];
        foreach ($entries as $e) {
            $by[$e['group']][] = $e;
        }

        $sum = ['total' => 0.0, 'gaps' => 0, 'imbalance' => 0.0, 'adjacent' => 0, 'late' => 0, 'avoid' => 0, 'groups' => []];
        foreach ($p->groups as $key => $_) {
            $c = self::groupCost($p, $by[$key] ?? []);
            $sum['groups'][$key] = $c;
            foreach (['total', 'gaps', 'imbalance', 'adjacent', 'late', 'avoid'] as $f) {
                $sum[$f] += $c[$f];
            }
        }
        $sum['total'] = round($sum['total'], 2);
        $sum['imbalance'] = round($sum['imbalance'], 2);

        return $sum;
    }
}
