<?php

namespace App\Services\AttendanceIntelligence\Rules;

use App\Services\AttendanceIntelligence\SessionContext;

/**
 * عندما يسجّل معظم الصف من شبكة واحدة (واي فاي المعهد)، يُعلَّم من سجّل من عنوان مختلف تمامًا.
 * إشارة ضعيفة (بعض الطلاب يستعملون بيانات الجوال)، لذلك خطورتها منخفضة وتُقرأ مع بقية القواعد.
 */
final class IpOutlierRule implements DetectionRule
{
    public const KEY = 'ip_outlier';

    public function key(): string
    {
        return self::KEY;
    }

    public function evaluate(SessionContext $ctx): array
    {
        $withIp = $ctx->scans->filter(fn ($s) => !empty($s->ip_address));
        if ($withIp->count() < (int) config('attendance_intelligence.min_scans_for_class_rules', 5)) {
            return [];
        }

        $counts = $withIp->countBy('ip_address')->sortDesc();
        $dominantIp = $counts->keys()->first();
        $ratio = $counts->first() / $withIp->count();

        if ($ratio < (float) config('attendance_intelligence.dominant_ip_ratio', 0.6)) {
            return []; // لا توجد شبكة سائدة واضحة؛ لا معنى للمقارنة
        }

        $flags = [];
        foreach ($withIp as $row) {
            // نقارن داخل العائلة نفسها فقط: جهاز على IPv6 لا يُقارن بـ IPv4 السائد
            if ($row->ip_address === $dominantIp || $this->family($row->ip_address) !== $this->family($dominantIp)) {
                continue;
            }

            $flags[] = [
                'attendance_id' => (int) $row->attendance_id,
                'student_id'    => (int) $row->student_id,
                'severity'      => 'low',
                'details'       => [
                    'ip'          => $row->ip_address,
                    'dominant_ip' => $dominantIp,
                    'dominant_ratio' => round($ratio, 2),
                ],
            ];
        }

        return $flags;
    }

    private function family(string $ip): string
    {
        return str_contains($ip, ':') ? 'v6' : 'v4';
    }
}
