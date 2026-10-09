<?php

namespace App\Services\Risk;

use App\Models\StudentRiskHistory;
use App\Models\StudentRiskScore;
use App\Services\Risk\Factors\AssignmentsFactor;
use App\Services\Risk\Factors\AttendanceFactor;
use App\Services\Risk\Factors\EngagementFactor;
use App\Services\Risk\Factors\GradesFactor;
use App\Services\Risk\Factors\PatternsFactor;
use App\Services\Risk\Factors\RiskFactor;
use Carbon\Carbon;

/**
 * درجة الخطر الأكاديمي لطالب: مجموع موزون لخمسة عوامل، مع شرح مكتوب لكل درجة.
 *
 *  - العامل الذي لا تتوفر بياناته لا يُحتسب ولا يُعاقَب الطالب عليه؛ تُطبَّع الأوزان على المتوفر.
 *  - إذا قلّت البيانات المتوفرة عن الحد (risk.min_confidence) يكون المستوى "unknown" لا "low":
 *    "لا نعرف" ليست "آمن".
 *  - لا ذكاء اصطناعي: كل رقم يمكن إعادة حسابه يدويًا من الحقائق المخزَّنة في factors.
 */
class RiskScoreService
{
    /** @var RiskFactor[] */
    private array $factors;

    public function __construct()
    {
        $this->factors = [
            new AttendanceFactor(), new AssignmentsFactor(), new GradesFactor(),
            new EngagementFactor(), new PatternsFactor(),
        ];
    }

    /**
     * يحسب الدرجة دون حفظ.
     *
     * @return array{score: float, level: string, confidence: float, factors: array<int,array>, reasons: string[]}
     */
    public function compute(int $studentId, int $userId, Carbon $asOf): array
    {
        $ctx      = new RiskContext($studentId, $userId, $asOf);
        $weights  = config('risk.weights');
        $allW     = array_sum($weights);
        $evals    = [];
        $availW   = 0.0;

        foreach ($this->factors as $factor) {
            $w   = (float) ($weights[$factor->key()] ?? 0);
            $res = $w > 0 ? $factor->evaluate($ctx) : null;
            $evals[] = ['factor' => $factor, 'weight' => $w, 'result' => $res];
            if ($res !== null) {
                $availW += $w;
            }
        }

        $factors = [];
        $score   = 0.0;
        foreach ($evals as $e) {
            /** @var RiskFactor $f */
            $f = $e['factor'];
            if ($e['result'] === null) {
                $factors[] = [
                    'key' => $f->key(), 'label' => $f->label(), 'weight' => $e['weight'], 'available' => false,
                    'score' => null, 'contribution' => 0.0, 'reason' => 'لا بيانات كافية', 'facts' => [],
                ];
                continue;
            }

            $contribution = $availW > 0 ? round(($e['weight'] / $availW) * $e['result']['score'], 1) : 0.0;
            $score += $contribution;
            $factors[] = [
                'key' => $f->key(), 'label' => $f->label(), 'weight' => $e['weight'], 'available' => true,
                'score' => $e['result']['score'], 'contribution' => $contribution,
                'reason' => $e['result']['reason'], 'facts' => $e['result']['facts'],
            ];
        }

        $score      = round($score, 1);
        $confidence = $allW > 0 ? round($availW / $allW, 2) : 0.0;
        $level      = $this->level($score, $confidence);

        return [
            'score' => $score, 'level' => $level, 'confidence' => $confidence,
            'factors' => $factors, 'reasons' => $this->reasons($factors, $level),
        ];
    }

    /**
     * يحسب ويحفظ الدرجة الحالية ولقطة اليوم.
     * تقييم تاريخ أقدم من آخر حساب يُسجَّل في التاريخ فقط ولا يمسّ الدرجة الحالية.
     *
     * @return array{row: StudentRiskScore, level_rose: bool, from_level: ?string}
     */
    public function refresh(int $studentId, int $userId, Carbon $asOf): array
    {
        $res = $this->compute($studentId, $userId, $asOf);
        $day = $asOf->copy()->startOfDay();

        StudentRiskHistory::updateOrCreate(
            ['student_id' => $studentId, 'computed_on' => $day->toDateString()],
            ['score' => $res['score'], 'level' => $res['level'], 'reasons' => $res['reasons']],
        );

        $row = StudentRiskScore::firstOrNew(['student_id' => $studentId]);

        if ($row->exists && $row->computed_on && $day->lt($row->computed_on->copy()->startOfDay())) {
            return ['row' => $row, 'level_rose' => false, 'from_level' => null]; // إعادة بناء الماضي
        }

        $existed   = $row->exists;
        $newerDay  = !$existed || !$row->computed_on->copy()->startOfDay()->equalTo($day);
        $fromLevel = $existed ? $row->level : null;

        if ($existed && $newerDay) {
            $row->previous_score = $row->score;
            $row->previous_level = $row->level;
        }
        if ($existed && $fromLevel !== $res['level']) {
            $row->level_changed_at = now();
        }
        if (!$existed) {
            $row->level_changed_at = now();
        }

        $row->fill([
            'score' => $res['score'], 'level' => $res['level'], 'confidence' => $res['confidence'],
            'factors' => $res['factors'], 'reasons' => $res['reasons'], 'computed_on' => $day->toDateString(),
        ])->save();

        $rose = $existed && StudentRiskScore::rank($res['level']) > StudentRiskScore::rank($fromLevel);

        return ['row' => $row, 'level_rose' => $rose, 'from_level' => $fromLevel];
    }

    private function level(float $score, float $confidence): string
    {
        if ($confidence < (float) config('risk.min_confidence')) {
            return 'unknown';
        }

        return match (true) {
            $score >= config('risk.levels.high')   => 'high',
            $score >= config('risk.levels.medium') => 'medium',
            default                                => 'low',
        };
    }

    /** أهم الأسباب بنص عربي، مرتبة بحسب مساهمتها في الدرجة. */
    private function reasons(array $factors, string $level): array
    {
        $min = (float) config('risk.reason_min_contribution');
        $max = (int) config('risk.max_reasons');

        $ranked = collect($factors)->where('available', true)->sortByDesc('contribution')->values();

        $picked = $ranked->filter(fn ($f) => $f['contribution'] >= $min && $f['score'] >= 20)->take($max);

        // مستوى متوسط/عالٍ لا يجوز أن يبقى بلا سبب مكتوب
        if ($picked->isEmpty() && in_array($level, ['medium', 'high'], true) && $ranked->isNotEmpty()) {
            $picked = $ranked->take(1);
        }

        return $picked->pluck('reason')->values()->all();
    }
}
