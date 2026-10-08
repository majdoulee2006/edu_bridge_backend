<?php

namespace App\Services\AttendanceIntelligence;

use App\Models\AttendanceFlag;

/**
 * نصوص عربية مفهومة للمعلم عن كل اشتباه: اسم القاعدة وشرح ما رُصد. لا منطق كشف هنا.
 */
class FlagPresenter
{
    private const LABELS = [
        'shared_device'       => 'جهاز مشترك',
        'identical_coordinates' => 'إحداثيات متطابقة',
        'replayed_face_score' => 'درجة وجه مكرّرة',
        'ip_outlier'          => 'شبكة مختلفة عن الصف',
        'last_second_pattern' => 'مسح في آخر ثواني الرمز',
    ];

    private const SEVERITIES = ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'];

    public static function label(string $rule): string
    {
        return self::LABELS[$rule] ?? $rule;
    }

    public static function severityLabel(string $severity): string
    {
        return self::SEVERITIES[$severity] ?? $severity;
    }

    /** @return array<string,string> مفتاح القاعدة => اسمها العربي */
    public static function allLabels(): array
    {
        return self::LABELS;
    }

    public static function explain(AttendanceFlag $flag): string
    {
        $d = $flag->details ?? [];

        return match ($flag->rule) {
            'shared_device' => sprintf(
                'سُجّل حضور %d طلاب في هذه الجلسة من الجهاز نفسه (ينتهي معرّفه بـ %s). الطالب مربوط بجهاز واحد، فقد يكون أحدهم حضّر زميله.',
                $d['group_size'] ?? 2, $d['device_tail'] ?? '—'
            ),
            'identical_coordinates' => sprintf(
                '%d طلاب سجّلوا الموقع الجغرافي نفسه بالضبط. هذا شبه مستحيل مع GPS حقيقي، وهو أثر شائع لتطبيقات الموقع الوهمي.',
                $d['group_size'] ?? 2
            ),
            'replayed_face_score' => self::faceText($d),
            'ip_outlier' => sprintf(
                'سجّل الطالب من شبكة مختلفة عن %d%% من الصف الذين سجّلوا من الشبكة نفسها. قد يكون يستعمل بيانات الجوال فقط، فالإشارة ضعيفة وحدها.',
                (int) round(($d['dominant_ratio'] ?? 0) * 100)
            ),
            'last_second_pattern' => sprintf(
                'يسجّل الطالب حضوره في آخر ثواني صلاحية الرمز بشكل متكرر (%d مرات من آخر %d جلسات)، وقد يتلقّى الرمز من زميل.',
                $d['late_scans'] ?? 0, $d['window'] ?? 0
            ),
            default => 'اشتباه يحتاج مراجعة.',
        };
    }

    private static function faceText(array $d): string
    {
        $parts = [];
        $reasons = $d['reasons'] ?? [];

        if (in_array('perfect_score', $reasons, true)) {
            $parts[] = sprintf('درجة تطابق الوجه %s%% شبه مثالية (الوجه الحي لا يعطي ذلك عادةً)', rtrim(rtrim(number_format((float) ($d['face_score'] ?? 0), 2), '0'), '.'));
        }
        if (in_array('repeated_exact_score', $reasons, true)) {
            $parts[] = sprintf('الدرجة نفسها حرفيًا تكررت في %d جلسات سابقة', $d['repeats'] ?? 0);
        }

        return ($parts ? implode('، و', $parts) : 'درجة الوجه مشبوهة') . '. قد تكون بصمة مخزَّنة أُعيد إرسالها بدل وجه مصوَّر.';
    }
}
