<?php

namespace App\Services\Schedule;

/**
 * يفحص مجموعة حصص ضد القيود الصلبة. مصدر الحقيقة الوحيد لـ"هل هذا الجدول سليم؟": يستعمله الاختبار
 * على ناتج المولّد، وإضافة حصة يدويًا، ونقل حصة في المسودة، فلا تختلف القواعد بين المسارات.
 *
 * شكل الحصة: ['group' => مفتاح الشعبة، 'course_id', 'teacher', 'day', 'period', 'room'].
 * شكل المخالفة: ['type', 'message', 'entries' => [فهارس الحصص المعنية]].
 */
final class ScheduleValidator
{
    public const TYPES = [
        'out_of_range' => 'خارج الشبكة الزمنية',
        'unknown_course' => 'مادة ليست من هذه الشعبة',
        'group_clash' => 'تعارض في الشعبة',
        'teacher_clash' => 'تعارض معلم',
        'external_teacher_clash' => 'المعلم مشغول بحصة في قسم آخر',
        'room_clash' => 'تعارض قاعة',
        'external_room_clash' => 'القاعة مشغولة بحصة في قسم آخر',
        'teacher_unavailable' => 'المعلم غير متاح في هذا الوقت',
        'room_capacity' => 'القاعة أصغر من عدد طلاب الشعبة',
        'same_course_day' => 'المادة نفسها مرتين في يوم واحد',
    ];

    /** @return array<int,array{type:string,message:string,entries:int[]}> */
    public static function validate(ScheduleProblem $p, array $entries): array
    {
        $v = [];
        $group = $teacher = $room = $courseDay = [];

        foreach (array_values($entries) as $i => $e) {
            $label = $p->groups[$e['group']]['label'] ?? $e['group'];
            $when = (config('schedule.day_labels')[$e['day']] ?? $e['day']) . ' الفترة ' . $e['period'];

            if (!in_array($e['day'], $p->days, true) || $e['period'] < 1 || $e['period'] > $p->periods) {
                $v[] = self::v('out_of_range', "{$label}: {$when} خارج الشبكة الزمنية المحددة", [$i]);
            }

            if (!self::courseInGroup($p, $e['group'], $e['course_id'])) {
                $v[] = self::v('unknown_course', "{$label}: المادة #{$e['course_id']} ليست من مواد هذه الشعبة", [$i]);
            }

            $slot = $e['day'] . '|' . $e['period'];
            $group[$e['group'] . '|' . $slot][] = $i;
            $teacher[$e['teacher'] . '|' . $slot][] = $i;
            $courseDay[$e['group'] . '|' . $e['course_id'] . '|' . $e['day']][] = $i;
            if ($e['room'] !== '' && $p->rooms) {
                $room[$e['room'] . '|' . $slot][] = $i;
            }

            if (isset($p->unavailable["{$e['teacher']}|{$slot}"])) {
                $v[] = self::v('teacher_unavailable', $p->teacherName($e['teacher']) . " غير متاح {$when}", [$i]);
            }
            if (isset($p->busyTeacher["{$e['teacher']}|{$slot}"])) {
                $v[] = self::v('external_teacher_clash', $p->teacherName($e['teacher']) . " لديه حصة أخرى (قسم آخر) {$when}", [$i]);
            }
            if ($p->rooms && isset($p->busyRoom["{$e['room']}|{$slot}"])) {
                $v[] = self::v('external_room_clash', "القاعة {$e['room']} مشغولة (قسم آخر) {$when}", [$i]);
            }

            $cap = $p->roomCapacity[$e['room']] ?? null;
            $size = $p->groups[$e['group']]['size'] ?? null;
            if ($cap !== null && $size !== null && $size > $cap) {
                $v[] = self::v('room_capacity', "القاعة {$e['room']} تتسع {$cap} وشعبة {$label} فيها {$size} طالبًا", [$i]);
            }
        }

        foreach ($group as $key => $idx) {
            if (count($idx) > 1) {
                [$g, $d, $per] = explode('|', $key);
                $v[] = self::v('group_clash', 'تعارض في الشعبة ' . ($p->groups[$g]['label'] ?? $g) . ': أكثر من مادة ' . (config('schedule.day_labels')[$d] ?? $d) . " الفترة {$per}", $idx);
            }
        }
        foreach ($teacher as $key => $idx) {
            if (count($idx) > 1) {
                [$t, $d, $per] = explode('|', $key);
                $v[] = self::v('teacher_clash', 'تعارض معلم: ' . $p->teacherName((int) $t) . ' لديه ' . count($idx) . ' حصص ' . (config('schedule.day_labels')[$d] ?? $d) . " الفترة {$per}", $idx);
            }
        }
        foreach ($room as $key => $idx) {
            if (count($idx) > 1) {
                [$r, $d, $per] = explode('|', $key);
                $v[] = self::v('room_clash', "تعارض قاعة: {$r} مستعملة لأكثر من حصة " . (config('schedule.day_labels')[$d] ?? $d) . " الفترة {$per}", $idx);
            }
        }
        foreach ($courseDay as $key => $idx) {
            if (count($idx) > 1) {
                [$g, $c, $d] = explode('|', $key);
                $v[] = self::v('same_course_day', 'المادة نفسها مرتين في يوم ' . (config('schedule.day_labels')[$d] ?? $d) . ' للشعبة ' . ($p->groups[$g]['label'] ?? $g), $idx);
            }
        }

        return $v;
    }

    /** المخالفات التي تمسّ حصة بعينها (للتحقق من نقلها قبل الحفظ). */
    public static function forEntry(ScheduleProblem $p, array $entries, int $index): array
    {
        return array_values(array_filter(self::validate($p, $entries), fn ($x) => in_array($index, $x['entries'], true)));
    }

    private static function courseInGroup(ScheduleProblem $p, string $group, int $courseId): bool
    {
        foreach ($p->groups[$group]['courses'] ?? [] as $c) {
            if ($c['course_id'] === $courseId) {
                return true;
            }
        }

        return false;
    }

    private static function v(string $type, string $message, array $entries): array
    {
        return ['type' => $type, 'message' => $message, 'entries' => $entries];
    }
}
