<?php

namespace App\Services\Risk\Factors;

use App\Services\Risk\RiskContext;
use App\Services\Risk\Scale;
use Illuminate\Support\Facades\DB;

/**
 * مستوى الحضور في النافذة الأخيرة واتجاهه مقارنةً بالنافذة السابقة لها. الهبوط أهم من الرقم المطلق:
 * طالب نزل من 95% إلى 70% أخطر من طالب ثابت عند 70%.
 * الغياب المعذور (عذر/إجازة معتمدة) لا يُحتسب.
 */
final class AttendanceFactor implements RiskFactor
{
    public function key(): string
    {
        return 'attendance';
    }

    public function label(): string
    {
        return 'الحضور';
    }

    public function evaluate(RiskContext $ctx): ?array
    {
        $cfg = config('risk.attendance');
        $days = (int) config('risk.windows.attendance');
        $min = (int) config('risk.min_records.attendance');

        $recent = $this->rate($ctx, $ctx->windowStart($days), $ctx->windowEnd($days));
        if ($recent['total'] < $min) {
            return null;
        }

        $prior = $this->rate($ctx, $ctx->windowStart($days, 1), $ctx->windowEnd($days, 1));

        $level = Scale::linear($recent['rate'], $cfg['safe_rate'], $cfg['worst_rate']);
        $drop = null;
        $score = $level;

        if ($prior['total'] >= $min) {
            $drop = max(0.0, $prior['rate'] - $recent['rate']);
            $trend = Scale::linear($drop, 0, $cfg['drop_full']);
            $score = round($cfg['level_share'] * $level + (1 - $cfg['level_share']) * $trend, 1);
        }

        $reason = 'حضوره ' . Scale::pretty($recent['rate']) . '% في آخر ' . $days . ' يومًا';
        if ($drop !== null && $drop >= 10) {
            $reason .= ' (كان ' . Scale::pretty($prior['rate']) . '%)';
        }

        return [
            'score' => $score,
            'reason' => $reason,
            'facts' => [
                'recent_rate' => $recent['rate'], 'recent_total' => $recent['total'],
                'prior_rate' => $prior['total'] >= $min ? $prior['rate'] : null, 'prior_total' => $prior['total'],
                'drop' => $drop,
            ],
        ];
    }

    /** @return array{rate: float, total: int} */
    private function rate(RiskContext $ctx, $from, $to): array
    {
        $row = DB::table('attendance')
            ->where('student_id', $ctx->studentId)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', ['present', 'late', 'absent'])
            ->whereRaw("NOT (status = 'absent' AND excuse_status = 'approved')")
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) AS attended")
            ->first();

        $total = (int) ($row->total ?? 0);

        return ['total' => $total, 'rate' => $total > 0 ? round(((int) $row->attended / $total) * 100, 1) : 0.0];
    }
}
