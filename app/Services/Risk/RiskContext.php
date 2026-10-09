<?php

namespace App\Services\Risk;

use Carbon\Carbon;

/**
 * ما يحتاجه أي عامل لتقييم طالب في تاريخ معيّن.
 * $asOf يُحسب "كأننا في ذلك اليوم": أي بيانات لاحقة له تُتجاهل (وهذا ما يجعل إعادة بناء التاريخ ممكنة).
 */
final class RiskContext
{
    public readonly Carbon $asOf;      // نهاية يوم التقييم
    public readonly Carbon $asOfDay;   // بدايته

    public function __construct(
        public readonly int $studentId,
        public readonly int $userId,
        Carbon $asOf,
    ) {
        $this->asOf    = $asOf->copy()->endOfDay();
        $this->asOfDay = $asOf->copy()->startOfDay();
    }

    /** بداية نافذة طولها $days تنتهي في يوم التقييم (شاملة). */
    public function windowStart(int $days, int $periodsBack = 0): Carbon
    {
        return $this->asOfDay->copy()->subDays($days * ($periodsBack + 1) - 1);
    }

    /** نهاية النافذة السابقة بعدد $periodsBack من النوافذ. */
    public function windowEnd(int $days, int $periodsBack = 0): Carbon
    {
        return $periodsBack === 0
            ? $this->asOf->copy()
            : $this->asOfDay->copy()->subDays($days * $periodsBack)->endOfDay();
    }
}
