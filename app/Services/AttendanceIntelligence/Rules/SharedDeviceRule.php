<?php

namespace App\Services\AttendanceIntelligence\Rules;

use App\Services\AttendanceIntelligence\SessionContext;

/**
 * جهاز واحد سجّل حضور أكثر من طالب في الجلسة نفسها. الطالب مربوط بجهاز واحد،
 * فهذا يعني غالبًا أن شخصًا حضّر زميله من هاتفه.
 */
final class SharedDeviceRule implements DetectionRule
{
    public const KEY = 'shared_device';

    public function key(): string
    {
        return self::KEY;
    }

    public function evaluate(SessionContext $ctx): array
    {
        $flags = [];

        $groups = $ctx->scans
            ->filter(fn ($s) => !empty($s->device_id))
            ->groupBy('device_id');

        foreach ($groups as $deviceId => $rows) {
            $students = $rows->pluck('student_id')->unique();
            if ($students->count() < 2) {
                continue;
            }

            foreach ($rows as $row) {
                $flags[] = [
                    'attendance_id' => (int) $row->attendance_id,
                    'student_id'    => (int) $row->student_id,
                    'severity'      => 'high',
                    'details'       => [
                        'group_size' => $students->count(),
                        // لا نخزّن معرّف الجهاز كاملًا في تفاصيل تُعرض للمعلم، يكفي طرفه للتمييز
                        'device_tail' => mb_substr((string) $deviceId, -6),
                    ],
                ];
            }
        }

        return $flags;
    }
}
