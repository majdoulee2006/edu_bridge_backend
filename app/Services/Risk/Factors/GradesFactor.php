<?php

namespace App\Services\Risk\Factors;

use App\Services\Risk\RiskContext;
use App\Services\Risk\Scale;
use Illuminate\Support\Facades\DB;

/**
 * مستوى العلامات الأخيرة واتجاهها مقارنةً بالفترة السابقة. تُقرأ من النظامين (امتحانات `grades` وفعاليات `grade_entries`)
 * وبتاريخ الامتحان/الفعالية نفسه لا بتاريخ إدخالها.
 */
final class GradesFactor implements RiskFactor
{
    public function key(): string
    {
        return 'grades';
    }

    public function label(): string
    {
        return 'العلامات';
    }

    public function evaluate(RiskContext $ctx): ?array
    {
        $cfg  = config('risk.grades');
        $days = (int) config('risk.windows.grades');

        $recent = $this->percents($ctx, $ctx->windowStart($days), $ctx->windowEnd($days));
        if (count($recent) < (int) config('risk.min_records.grades')) {
            return null;
        }
        $prior = $this->percents($ctx, $ctx->windowStart($days, 1), $ctx->windowEnd($days, 1));

        $avg = array_sum($recent) / count($recent);
        $level = Scale::linear($avg, $cfg['safe_avg'], $cfg['worst_avg']);
        $score = $level;
        $drop = null;
        $priorAvg = null;

        if (count($prior) >= 1) {
            $priorAvg = array_sum($prior) / count($prior);
            $drop = max(0.0, $priorAvg - $avg);
            $score = round($cfg['level_share'] * $level + (1 - $cfg['level_share']) * Scale::linear($drop, 0, $cfg['drop_full']), 1);
        }

        $fails = count(array_filter($recent, fn ($p) => $p < $cfg['fail_below']));

        $reason = 'متوسط علاماته ' . Scale::pretty($avg) . '% في آخر ' . $days . ' يومًا';
        if ($drop !== null && $drop >= 8) {
            $reason .= ' (كان ' . Scale::pretty($priorAvg) . '%)';
        }
        if ($fails > 0) {
            $reason .= '، ' . $fails . ' علامة تحت ' . $cfg['fail_below'] . '%';
        }

        return [
            'score' => $score,
            'reason' => $reason,
            'facts' => [
                'recent_avg' => round($avg, 1), 'recent_count' => count($recent),
                'prior_avg' => $priorAvg !== null ? round($priorAvg, 1) : null, 'prior_count' => count($prior),
                'drop' => $drop !== null ? round($drop, 1) : null, 'fails' => $fails,
            ],
        ];
    }

    /** @return float[] نسب مئوية (0-100) للعلامات المؤرَّخة ضمن الفترة. */
    private function percents(RiskContext $ctx, $from, $to): array
    {
        $events = DB::table('grade_entries')
            ->join('grade_events', 'grade_events.id', '=', 'grade_entries.grade_event_id')
            ->where('grade_entries.student_id', $ctx->studentId)
            ->whereNotNull('grade_entries.score')
            ->whereBetween('grade_events.date', [$from->toDateString(), $to->toDateString()])
            ->get(['grade_entries.score', 'grade_events.max_score as max']);

        $exams = DB::table('grades')
            ->join('exams', 'exams.exam_id', '=', 'grades.exam_id')
            ->where('grades.student_id', $ctx->studentId)
            ->whereBetween('exams.exam_date', [$from, $to])
            ->get(['grades.score', 'exams.max_score as max']);

        return $events->concat($exams)
            ->filter(fn ($r) => (float) $r->max > 0)
            ->map(fn ($r) => ((float) $r->score / (float) $r->max) * 100)
            ->values()
            ->all();
    }
}
