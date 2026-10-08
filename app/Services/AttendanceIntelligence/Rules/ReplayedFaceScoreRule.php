<?php

namespace App\Services\AttendanceIntelligence\Rules;

use App\Services\AttendanceIntelligence\SessionContext;

/**
 * درجة تطابق الوجه "مثالية" أو متكررة حرفيًا. الوجه الحي يعطي درجة مختلفة كل مرة (ضجيج الإضاءة والزاوية)،
 * أما إرسال البصمة المخزَّنة نفسها (هجوم S-11 الموثَّق) فيعطي ≈100 دائمًا أو القيمة ذاتها مرارًا.
 */
final class ReplayedFaceScoreRule implements DetectionRule
{
    public const KEY = 'replayed_face_score';

    private const PERFECT = 99.9;

    public function key(): string
    {
        return self::KEY;
    }

    public function evaluate(SessionContext $ctx): array
    {
        $decimals = (int) config('attendance_intelligence.face_score_decimals', 3);
        $flags = [];

        foreach ($ctx->scans as $row) {
            if ($row->face_score === null) {
                continue;
            }

            $score = (float) $row->face_score;
            $reasons = [];

            if ($score >= self::PERFECT) {
                $reasons[] = 'perfect_score';
            }

            // نفس الدرجة حرفيًا في مرات سابقة لنفس الطالب
            $repeats = $ctx->recentScans((int) $row->student_id, 10)
                ->filter(fn ($p) => $p->face_score !== null && round((float) $p->face_score, $decimals) === round($score, $decimals))
                ->count();
            if ($repeats >= 2) {
                $reasons[] = 'repeated_exact_score';
            }

            if (!$reasons) {
                continue;
            }

            $flags[] = [
                'attendance_id' => (int) $row->attendance_id,
                'student_id'    => (int) $row->student_id,
                'severity'      => count($reasons) > 1 ? 'high' : 'medium',
                'details'       => [
                    'face_score' => $score,
                    'reasons'    => $reasons,
                    'repeats'    => $repeats,
                ],
            ];
        }

        return $flags;
    }
}
