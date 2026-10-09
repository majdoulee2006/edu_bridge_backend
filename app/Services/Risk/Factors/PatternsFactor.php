<?php

namespace App\Services\Risk\Factors;

use App\Services\Risk\RiskContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * أنماط الغياب التي تخفيها النسبة العامة: غياب متركز في مادة بعينها، أو في يوم بعينه من الأسبوع
 * (يغيب كل أحد)، أو أيام دوام متتالية. تُرجع أقوى نمط رُصد، أو 0 إذا لم يُرصد شيء.
 */
final class PatternsFactor implements RiskFactor
{
    private const DAYS = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    public function key(): string
    {
        return 'patterns';
    }

    public function label(): string
    {
        return 'أنماط الغياب';
    }

    public function evaluate(RiskContext $ctx): ?array
    {
        $cfg  = config('risk.patterns');
        $days = (int) config('risk.windows.patterns');

        $rows = DB::table('attendance as a')
            ->join('lessons as l', 'l.lesson_id', '=', 'a.lesson_id')
            ->join('courses as c', 'c.course_id', '=', 'l.course_id')
            ->where('a.student_id', $ctx->studentId)
            ->whereBetween('a.attendance_date', [$ctx->windowStart($days)->toDateString(), $ctx->asOf->toDateString()])
            ->whereIn('a.status', ['present', 'late', 'absent'])
            ->whereRaw("NOT (a.status = 'absent' AND a.excuse_status = 'approved')")
            ->get(['a.attendance_date', 'a.status', 'c.course_id', 'c.title']);

        if ($rows->count() < (int) config('risk.min_records.attendance')) {
            return null;
        }

        $candidates = array_filter([
            $this->course($rows, $cfg),
            $this->weekday($rows, $cfg),
            $this->streak($rows, $ctx, $cfg),
        ]);

        if (!$candidates) {
            return ['score' => 0.0, 'reason' => 'لا أنماط غياب ملحوظة', 'facts' => ['patterns' => []]];
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return [
            'score' => (float) $candidates[0]['score'],
            'reason' => $candidates[0]['reason'],
            'facts' => ['patterns' => array_map(fn ($c) => ['type' => $c['type'], 'score' => $c['score'], 'reason' => $c['reason']], $candidates)],
        ];
    }

    /** غياب متركز في مادة واحدة. */
    private function course($rows, array $cfg): ?array
    {
        $best = null;

        foreach ($rows->groupBy('course_id') as $items) {
            $absent = $items->where('status', 'absent')->count();
            $share = $absent / $items->count();

            if ($absent >= $cfg['course_min_absent'] && $share >= $cfg['course_min_share'] && ($best === null || $share > $best['share'])) {
                $best = [
                    'share' => $share,
                    'type' => 'course', 'score' => $cfg['scores']['course'],
                    'reason' => 'غيابه متكرر في مادة «' . $items->first()->title . '» (' . $absent . ' من ' . $items->count() . ')',
                ];
            }
        }

        return $best;
    }

    /** غياب في اليوم نفسه من الأسبوع مرة بعد مرة. */
    private function weekday($rows, array $cfg): ?array
    {
        $best = null;

        foreach ($rows->groupBy(fn ($r) => Carbon::parse($r->attendance_date)->dayOfWeek) as $dow => $items) {
            // نعدّ الأيام المميزة لا السجلات، كي لا يتضخم الرقم بتعدد المواد في اليوم نفسه
            $absentDays = $items->where('status', 'absent')->pluck('attendance_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->count();
            $allDays = $items->pluck('attendance_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->count();

            if ($absentDays >= $cfg['weekday_min'] && $absentDays / max(1, $allDays) >= 0.5 && ($best === null || $absentDays > $best['n'])) {
                $best = [
                    'n' => $absentDays,
                    'type' => 'weekday', 'score' => $cfg['scores']['weekday'],
                    'reason' => 'يغيب غالبًا يوم ' . self::DAYS[$dow] . ' (' . $absentDays . ' مرات)',
                ];
            }
        }

        return $best;
    }

    /** أيام دوام متتالية غاب فيها كل المحاضرات. اليوم بلا أي سجل يقطع التتابع (حذرًا). */
    private function streak($rows, RiskContext $ctx, array $cfg): ?array
    {
        $schoolDays = config('risk.school_days');
        $byDate = $rows->groupBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());

        $run = $best = 0;
        $cursor = $ctx->windowStart((int) config('risk.windows.patterns'));

        while ($cursor->lte($ctx->asOfDay)) {
            if (in_array($cursor->dayOfWeek, $schoolDays, true)) {
                $day = $byDate->get($cursor->toDateString());
                $fullyAbsent = $day && $day->every(fn ($r) => $r->status === 'absent');
                $run = $fullyAbsent ? $run + 1 : 0;
                $best = max($best, $run);
            }
            $cursor->addDay();
        }

        if ($best < $cfg['streak_min']) {
            return null;
        }

        // العدد من 3 إلى 10 يُجمع (أيام)، وما فوقه يأتي معه المفرد (11 يوم دوام متتاليًا)
        $text = $best <= 10 ? "غاب {$best} أيام دوام متتالية" : "غاب {$best} يوم دوام متتاليًا";

        return ['type' => 'streak', 'score' => $cfg['scores']['streak'], 'reason' => $text];
    }
}
