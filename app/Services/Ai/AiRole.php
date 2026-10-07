<?php

namespace App\Services\Ai;

class AiRole
{
    public const TITLES = [
        'student' => 'طالب في المعهد',
        'teacher' => 'أستاذ / عضو هيئة تدريسية',
        'parent'  => 'ولي أمر طالب',
        'hod'     => 'رئيس قسم أكاديمي',
        'affairs' => 'موظف شؤون طلاب',
        'admin'   => 'مدير عام النظام',
    ];

    /** يوحّد التسميات (boss/head/hod) ويرجع student لأي قيمة غير معروفة. */
    public static function normalize(?string $role): string
    {
        $r = strtolower(trim((string) $role));
        if (in_array($r, ['boss', 'head', 'hod'], true)) {
            return 'hod';
        }

        return isset(self::TITLES[$r]) ? $r : 'student';
    }

    public static function title(string $role): string
    {
        return self::TITLES[self::normalize($role)];
    }
}
