<?php

namespace App\Services\AttendanceIntelligence\Rules;

use App\Services\AttendanceIntelligence\SessionContext;

/**
 * طالب يسجّل حضوره دائمًا في آخر ثواني صلاحية الـ QR (يتلقّاه من زميل ثم يمسحه متأخرًا).
 * مسح واحد متأخر طبيعي، لذلك لا يُعلَّم إلا عند تكرار النمط عبر عدة جلسات.
 */
final class LastSecondPatternRule implements DetectionRule
{
    public const KEY = 'last_second_pattern';

    public function key(): string
    {
        return self::KEY;
    }

    public function evaluate(SessionContext $ctx): array
    {
        $threshold = (int) config('attendance_intelligence.last_second_age', 24);
        $minEvents = (int) config('attendance_intelligence.last_second_min_events', 3);
        $window    = (int) config('attendance_intelligence.last_second_window', 10);

        $flags = [];

        foreach ($ctx->scans as $row) {
            if ($row->qr_age_seconds === null || (int) $row->qr_age_seconds < $threshold) {
                continue;
            }

            // هذا المسح + مسوحات سابقة متأخرة ضمن آخر $window جلسة
            $late = 1 + $ctx->recentScans((int) $row->student_id, $window - 1)
                ->filter(fn ($p) => $p->qr_age_seconds !== null && (int) $p->qr_age_seconds >= $threshold)
                ->count();

            if ($late < $minEvents) {
                continue;
            }

            $flags[] = [
                'attendance_id' => (int) $row->attendance_id,
                'student_id'    => (int) $row->student_id,
                'severity'      => 'low',
                'details'       => [
                    'qr_age_seconds' => (int) $row->qr_age_seconds,
                    'late_scans'     => $late,
                    'window'         => $window,
                ],
            ];
        }

        return $flags;
    }
}
