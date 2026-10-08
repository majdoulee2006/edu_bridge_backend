<?php

namespace App\Services\AttendanceIntelligence\Rules;

use App\Services\AttendanceIntelligence\SessionContext;

/**
 * قاعدة كشف واحدة. تقرأ مسوحات جلسة وتُرجع قائمة اشتباهات؛ لا تكتب في القاعدة ولا تعاقب أحدًا.
 *
 * كل عنصر مُرجَع: ['attendance_id' => int, 'student_id' => int, 'severity' => low|medium|high, 'details' => array]
 */
interface DetectionRule
{
    /** مفتاح ثابت يُخزَّن في attendance_flags.rule ويُترجم في الواجهات. */
    public function key(): string;

    /** @return array<int,array{attendance_id:int,student_id:int,severity:string,details:array}> */
    public function evaluate(SessionContext $ctx): array;
}
