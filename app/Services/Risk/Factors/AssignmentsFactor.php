<?php

namespace App\Services\Risk\Factors;

use App\Services\Risk\RiskContext;
use App\Services\Risk\Scale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * الواجبات التي حلّ موعدها في النافذة: الفائت (لم يُسلَّم) والمتأخر (سُلِّم بعد الموعد).
 * يُحتسب فقط ما سُلِّم قبل يوم التقييم، فيصح تقييم الماضي عند إعادة بناء التاريخ.
 */
final class AssignmentsFactor implements RiskFactor
{
    public function key(): string
    {
        return 'assignments';
    }

    public function label(): string
    {
        return 'الواجبات';
    }

    public function evaluate(RiskContext $ctx): ?array
    {
        $cfg  = config('risk.assignments');
        $days = (int) config('risk.windows.assignments');

        $courseIds = DB::table('enrollments')
            ->where('student_id', $ctx->studentId)
            ->where('status', '!=', 'dropped')
            ->pluck('course_id');
        if ($courseIds->isEmpty()) {
            return null;
        }

        $due = DB::table('assignments')
            ->whereIn('course_id', $courseIds)
            ->where('due_date', '>=', $ctx->windowStart($days))
            ->where('due_date', '<=', $ctx->asOf)
            ->get(['assignment_id', 'due_date']);

        if ($due->count() < (int) config('risk.min_records.assignments')) {
            return null;
        }

        $subs = DB::table('assignment_submissions')
            ->where('student_id', $ctx->studentId)
            ->whereIn('assignment_id', $due->pluck('assignment_id'))
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<=', $ctx->asOf)
            ->pluck('submitted_at', 'assignment_id');

        $missing = $late = 0;
        foreach ($due as $a) {
            $at = $subs->get($a->assignment_id);
            if ($at === null) {
                $missing++;
            } elseif (Carbon::parse($at)->gt(Carbon::parse($a->due_date))) {
                $late++;
            }
        }

        $total = $due->count();
        $ratio = ($missing + $cfg['late_weight'] * $late) / $total;
        $score = round(max(0.0, min(100.0, $ratio * $cfg['scale'])), 1);

        $parts = [];
        if ($missing > 0) {
            $parts[] = $missing . ' لم تُسلَّم';
        }
        if ($late > 0) {
            $parts[] = $late . ' متأخرة';
        }

        return [
            'score' => $score,
            'reason' => $parts ? 'واجبات: ' . implode(' و', $parts) . ' من أصل ' . $total : 'سلّم كل واجباته في موعدها',
            'facts' => ['due' => $total, 'missing' => $missing, 'late' => $late],
        ];
    }
}
