<?php

namespace App\Services\AttendanceIntelligence\Rules;

use App\Services\AttendanceIntelligence\SessionContext;

/**
 * طلاب مختلفون سجّلوا نفس الإحداثيات تمامًا (حتى 6 خانات ≈ 10 سم). مستحيل عمليًا مع GPS حقيقي
 * (لكل جهاز ضجيجه)، وهو أثر معروف لتطبيقات "الموقع الوهمي".
 * إحداثيات الشبكة التقريبية (قليلة الخانات) تتطابق طبيعيًا فتُهمل.
 */
final class IdenticalCoordinatesRule implements DetectionRule
{
    public const KEY = 'identical_coordinates';

    public function key(): string
    {
        return self::KEY;
    }

    public function evaluate(SessionContext $ctx): array
    {
        $decimals = (int) config('attendance_intelligence.coordinate_decimals', 6);
        $minPrecise = (int) config('attendance_intelligence.min_precise_decimals', 5);
        $highAt = (int) config('attendance_intelligence.identical_coordinates_high_at', 3);

        $groups = $ctx->scans
            ->filter(fn ($s) => $s->latitude !== null && $s->longitude !== null
                && $this->isPrecise((float) $s->latitude, $minPrecise)
                && $this->isPrecise((float) $s->longitude, $minPrecise))
            ->groupBy(fn ($s) => number_format((float) $s->latitude, $decimals, '.', '') . '|' . number_format((float) $s->longitude, $decimals, '.', ''));

        $flags = [];
        foreach ($groups as $rows) {
            $students = $rows->pluck('student_id')->unique();
            if ($students->count() < 2) {
                continue;
            }

            foreach ($rows as $row) {
                $flags[] = [
                    'attendance_id' => (int) $row->attendance_id,
                    'student_id'    => (int) $row->student_id,
                    'severity'      => $students->count() >= $highAt ? 'high' : 'medium',
                    'details'       => [
                        'group_size' => $students->count(),
                        'latitude'   => (float) $row->latitude,
                        'longitude'  => (float) $row->longitude,
                    ],
                ];
            }
        }

        return $flags;
    }

    /** هل للقيمة خانات عشرية أبعد من $min (أي ليست إحداثية تقريبية)؟ */
    private function isPrecise(float $value, int $min): bool
    {
        return abs($value - round($value, $min)) > 1e-9;
    }
}
