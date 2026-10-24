<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ScheduleStructureService
{
    /**
     * الأيام المدعومة في الأسبوع
     */
    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

    public const DAY_NAMES = [
        'Sunday'    => 'الأحد',
        'Monday'    => 'الإثنين',
        'Tuesday'   => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday'  => 'الخميس',
    ];

    /**
     * جلب هيكل البرنامج المعتمد للقسم
     */
    public static function getStructure(?int $departmentId = null): array
    {
        $query = DB::table('department_schedule_structures')->where('is_active', true);
        if ($departmentId) {
            $query->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)->orWhereNull('department_id');
            })->orderByRaw('department_id IS NULL ASC'); // الأولوية للمخصص للقسم
        }

        $record = $query->first();

        if ($record && !empty($record->rules)) {
            $rules = is_array($record->rules) ? $record->rules : json_decode($record->rules, true);
            if (!empty($rules)) {
                return $rules;
            }
        }

        return self::defaultRules();
    }

    /**
     * الهيكل الافتراضي لمعهد دمشق المتوسط (DTC)
     */
    public static function defaultRules(): array
    {
        return [
            [
                'days'                    => ['Sunday', 'Monday', 'Tuesday'],
                'is_holiday'              => false,
                'first_period_start'      => '08:15',
                'last_period_end'         => '15:35',
                'period_duration_minutes' => 45,
                'break1_after_period'     => 3,
                'break1_duration_minutes' => 20,
                'break2_after_period'     => 6,
                'break2_duration_minutes' => 15,
            ],
            [
                'days'                    => ['Wednesday'],
                'is_holiday'              => false,
                'first_period_start'      => '08:15',
                'last_period_end'         => '13:50',
                'period_duration_minutes' => 45,
                'break1_after_period'     => 3,
                'break1_duration_minutes' => 20,
                'break2_after_period'     => null,
                'break2_duration_minutes' => 0,
            ],
            [
                'days'                    => ['Thursday'],
                'is_holiday'              => true,
                'first_period_start'      => '08:15',
                'last_period_end'         => '14:00',
                'period_duration_minutes' => 45,
                'break1_after_period'     => null,
                'break1_duration_minutes' => 0,
                'break2_after_period'     => null,
                'break2_duration_minutes' => 0,
            ],
        ];
    }

    /**
     * حساب وتوليد الحصص والبريكات ليوم محدد
     */
    public static function calculateDayTimeline(?int $departmentId, string $day): array
    {
        $rules = self::getStructure($departmentId);
        $card = null;

        foreach ($rules as $r) {
            $days = (array) ($r['days'] ?? []);
            if (in_array($day, $days, true)) {
                $card = $r;
                break;
            }
        }

        // إذا لم يعثر على قاعدة مخصصة لليوم، نأخذ أول قاعدة غير عطلة
        if (!$card) {
            $card = $rules[0] ?? self::defaultRules()[0];
        }

        $isHoliday = !empty($card['is_holiday']);
        if ($isHoliday) {
            return [
                'day'         => $day,
                'is_holiday'  => true,
                'periods'     => [],
                'breaks'      => [],
                'total_slots' => 0,
            ];
        }

        $firstStart   = $card['first_period_start'] ?? '08:15';
        $durationMin  = max(20, (int) ($card['period_duration_minutes'] ?? 45));
        $break1After  = !empty($card['break1_after_period']) ? (int) $card['break1_after_period'] : null;
        $break1Dur    = !empty($card['break1_duration_minutes']) ? (int) $card['break1_duration_minutes'] : 0;
        $break2After  = !empty($card['break2_after_period']) ? (int) $card['break2_after_period'] : null;
        $break2Dur    = !empty($card['break2_duration_minutes']) ? (int) $card['break2_duration_minutes'] : 0;
        
        $lastEndStr   = !empty($card['last_period_end']) ? $card['last_period_end'] : '16:00';
        $lastEndMin   = self::timeToMinutes($lastEndStr);

        $currentTimeMin = self::timeToMinutes($firstStart);
        $periods = [];
        $breaks = [];

        // نولد الحصص حتى 9 حصص
        for ($p = 1; $p <= 9; $p++) {
            $slotStart = $currentTimeMin;
            $slotEnd   = $slotStart + $durationMin;

            // إذا تجاوزنا نهاية الدوام المحدد لليوم نكتفي إذا أنجزنا الحد الأدنى
            if ($p > 5 && $slotStart >= $lastEndMin) {
                break;
            }

            $periods[$p] = [
                'period' => $p,
                'start'  => self::minutesToTime($slotStart),
                'end'    => self::minutesToTime($slotEnd),
                'label'  => "الحصة {$p} (" . self::minutesToTime($slotStart) . " - " . self::minutesToTime($slotEnd) . ")",
            ];

            $currentTimeMin = $slotEnd;

            // هل يوجد بريك بعد هذه الحصة؟
            if ($break1After && $p === $break1After && $break1Dur > 0) {
                $bStart = $currentTimeMin;
                $bEnd   = $bStart + $break1Dur;
                $breaks[] = [
                    'after_period' => $p,
                    'start'        => self::minutesToTime($bStart),
                    'end'          => self::minutesToTime($bEnd),
                    'duration'     => $break1Dur,
                    'label'        => "استراحة ({$break1Dur} دقيقة)",
                ];
                $currentTimeMin = $bEnd;
            } elseif ($break2After && $p === $break2After && $break2Dur > 0) {
                $bStart = $currentTimeMin;
                $bEnd   = $bStart + $break2Dur;
                $breaks[] = [
                    'after_period' => $p,
                    'start'        => self::minutesToTime($bStart),
                    'end'          => self::minutesToTime($bEnd),
                    'duration'     => $break2Dur,
                    'label'        => "استراحة ({$break2Dur} دقيقة)",
                ];
                $currentTimeMin = $bEnd;
            }
        }

        return [
            'day'         => $day,
            'is_holiday'  => false,
            'periods'     => $periods,
            'breaks'      => $breaks,
            'total_slots' => count($periods),
        ];
    }

    /**
     * جلب أوقات البداية والنهاية لمجال حصص (من X إلى Y)
     */
    public static function getRangeTimes(?int $departmentId, string $day, int $fromPeriod, int $toPeriod): array
    {
        $timeline = self::calculateDayTimeline($departmentId, $day);
        $periods  = $timeline['periods'] ?? [];

        $fromPeriod = max(1, $fromPeriod);
        $toPeriod   = max($fromPeriod, $toPeriod);

        $startTime = $periods[$fromPeriod]['start'] ?? '08:15';
        $endTime   = $periods[$toPeriod]['end'] ?? ($periods[$fromPeriod]['end'] ?? '09:00');

        return [
            'start' => $startTime,
            'end'   => $endTime,
        ];
    }

    /**
     * تحويل "HH:MM" إلى دقائق
     */
    public static function timeToMinutes(string $time): int
    {
        $parts = explode(':', trim($time));
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        return ($h * 60) + $m;
    }

    /**
     * تحويل الدقائق إلى "HH:MM"
     */
    public static function minutesToTime(int $totalMinutes): string
    {
        $h = floor($totalMinutes / 60);
        $m = $totalMinutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }
}
