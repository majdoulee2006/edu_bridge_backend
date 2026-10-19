<?php

namespace App\Services\Schedule;

/**
 * يفحص مجموعة تقييمات امتحانية ضد القيود الصلبة. مصدر الحقيقة الوحيد لـ"هل هذا الجدول الامتحاني سليم؟":
 * يستعمله الاختبار على ناتج المولّد، ونقل تقييم يدوياً في المسودة، وتغيير قاعة طالب أو مراقب، وقرار الاعتماد.
 *
 * شكل التقييم: ['key','kind','course_id','group','date','slot','start','end','rooms' => [['name','capacity','students','proctor']]].
 * شكل المخالفة: ['type','message','entries' => [فهارس التقييمات المعنية]].
 */
final class ExamValidator
{
    public const TYPES = [
        'out_of_period' => 'خارج فترة النوع',
        'bad_slot' => 'فترة يومية غير معرّفة',
        'student_day_clash' => 'طالب لديه تقييمان في يوم واحد',
        'room_clash' => 'تعارض قاعة',
        'unknown_room' => 'قاعة غير مسجّلة',
        'room_capacity' => 'قاعة أصغر من عدد طلابها',
        'unseated' => 'طلاب بلا قاعة أو مكرّرون',
        'order' => 'المذاكرة ليست قبل الامتحان',
        'proctor_clash' => 'مراقب في قاعتين بنفس الوقت',
    ];

    /** @return array<int,array{type:string,message:string,entries:int[]}> */
    public static function validate(ExamProblem $p, array $entries): array
    {
        $v = [];
        $entries = array_values($entries);
        $byKey = [];
        foreach ($entries as $i => $e) {
            $byKey[$e['key']] = $i;
        }

        $roomUse = [];     // room|date => [[from,to,index]]
        $proctorUse = [];  // user|date => [[from,to,index,room]]

        foreach ($entries as $i => $e) {
            $label = ($p->tasks[$e['key']]['label'] ?? $e['group']) . ' — ' . ($p->tasks[$e['key']]['title'] ?? '#' . $e['course_id']) . ' (' . ExamProblem::KINDS[$e['kind']] . ')';
            $kind = $e['kind'];
            $cfg = $p->kinds[$kind] ?? null;

            if (!$cfg || !in_array($e['date'], $p->calendar[$kind] ?? [], true)) {
                $v[] = self::v('out_of_period', "{$label}: {$e['date']} خارج فترة " . (ExamProblem::KINDS[$kind] ?? $kind) . ' أو في يوم مستثنى', [$i]);
            }
            if ($cfg && (!isset($cfg['slots'][$e['slot']]) || $cfg['slots'][$e['slot']][0] !== $e['start'])) {
                $v[] = self::v('bad_slot', "{$label}: الفترة {$e['start']} ليست من الفترات اليومية المحددة", [$i]);
            }

            $from = ExamProblem::minutes($e['start']);
            $to = ExamProblem::minutes($e['end']);
            $seated = [];

            foreach ($e['rooms'] as $room) {
                $name = $room['name'];
                $cap = $p->rooms[$name] ?? null;

                if ($p->rooms && $cap === null) {
                    $v[] = self::v('unknown_room', "{$label}: القاعة «{$name}» غير مسجّلة", [$i]);
                } elseif ($cap !== null && count($room['students']) > $cap) {
                    $v[] = self::v('room_capacity', "{$label}: القاعة «{$name}» تتسع {$cap} وفيها " . count($room['students']) . ' طالباً', [$i]);
                }
                foreach ($room['students'] as $sid) {
                    $seated[$sid] = ($seated[$sid] ?? 0) + 1;
                }

                $roomUse[$name . '|' . $e['date']][] = [$from, $to, $i];
                if (!empty($room['proctor'])) {
                    $proctorUse[$room['proctor'] . '|' . $e['date']][] = [$from, $to, $i, $name];
                }
            }

            $expected = $p->tasks[$e['key']]['students'] ?? [];
            $missing = array_diff($expected, array_keys($seated));
            $dupes = array_filter($seated, fn ($n) => $n > 1);
            $extra = array_diff(array_keys($seated), $expected);
            if ($missing || $dupes || $extra) {
                $v[] = self::v('unseated', "{$label}: " . (count($missing) ? count($missing) . ' طالباً بلا قاعة. ' : '') . (count($dupes) ? count($dupes) . ' مكرّراً. ' : '') . (count($extra) ? count($extra) . ' غير مسجّل بالمقرر.' : ''), [$i]);
            }
        }

        // يوم واحد لطلاب مشتركين (يغطي: يوم واحد لتقييم واحد لكل شعبة)
        foreach ($entries as $i => $e) {
            foreach ($p->conflicts[$e['key']] ?? [] as $otherKey) {
                $j = $byKey[$otherKey] ?? null;
                if ($j !== null && $j > $i && $entries[$j]['date'] === $e['date']) {
                    $a = $p->tasks[$e['key']];
                    $b = $p->tasks[$otherKey];
                    $v[] = self::v('student_day_clash', "تقييمان لنفس الطلاب في {$e['date']}: {$a['title']} ({$a['label']}) و{$b['title']} ({$b['label']})", [$i, $j]);
                }
            }
        }

        // قاعة واحدة لتقييم واحد في الوقت الواحد
        foreach ($roomUse as $key => $uses) {
            [$room, $date] = explode('|', $key);
            for ($x = 0; $x < count($uses); $x++) {
                for ($y = $x + 1; $y < count($uses); $y++) {
                    if ($uses[$x][0] < $uses[$y][1] && $uses[$y][0] < $uses[$x][1]) {
                        $v[] = self::v('room_clash', "تعارض قاعة: «{$room}» مستعملة لتقييمين متداخلين في {$date}", [$uses[$x][2], $uses[$y][2]]);
                    }
                }
            }
        }

        // مراقب واحد في قاعة واحدة في الوقت الواحد
        foreach ($proctorUse as $key => $uses) {
            [$user, $date] = explode('|', $key);
            for ($x = 0; $x < count($uses); $x++) {
                for ($y = $x + 1; $y < count($uses); $y++) {
                    if ($uses[$x][0] < $uses[$y][1] && $uses[$y][0] < $uses[$x][1]) {
                        $v[] = self::v('proctor_clash', "مراقب واحد في قاعتين بنفس الوقت ({$uses[$x][3]} و{$uses[$y][3]}) في {$date}", [$uses[$x][2], $uses[$y][2]]);
                    }
                }
            }
        }

        // المذاكرة قبل الامتحان
        foreach ($entries as $i => $e) {
            if ($e['kind'] !== 'midterm') {
                continue;
            }
            $finalKey = $e['group'] . '|' . $e['course_id'] . '|final';
            $j = $byKey[$finalKey] ?? null;
            if ($j !== null && $entries[$j]['date'] <= $e['date']) {
                $t = $p->tasks[$e['key']];
                $v[] = self::v('order', "{$t['label']} — {$t['title']}: المذاكرة ({$e['date']}) ليست قبل الامتحان ({$entries[$j]['date']})", [$i, $j]);
            }
        }

        return $v;
    }

    /** المخالفات التي تمسّ تقييماً بعينه (للتحقق من تعديله قبل الحفظ). */
    public static function forEntry(ExamProblem $p, array $entries, int $index): array
    {
        return array_values(array_filter(self::validate($p, $entries), fn ($x) => in_array($index, $x['entries'], true)));
    }

    private static function v(string $type, string $message, array $entries): array
    {
        return ['type' => $type, 'message' => $message, 'entries' => $entries];
    }
}
