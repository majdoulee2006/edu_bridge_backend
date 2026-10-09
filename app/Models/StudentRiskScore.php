<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** الدرجة الحالية للخطر الأكاديمي لطالب (انظر RiskScoreService). */
class StudentRiskScore extends Model
{
    public const LEVELS = ['unknown' => 0, 'low' => 1, 'medium' => 2, 'high' => 3];

    protected $fillable = [
        'student_id', 'score', 'level', 'confidence', 'factors', 'reasons',
        'previous_score', 'previous_level', 'level_changed_at', 'notified_level', 'computed_on',
    ];

    protected $casts = [
        'score'            => 'float',
        'confidence'       => 'float',
        'previous_score'   => 'float',
        'factors'          => 'array',
        'reasons'          => 'array',
        'level_changed_at' => 'datetime',
        'computed_on'      => 'date',
    ];

    public static function rank(?string $level): int
    {
        return self::LEVELS[$level ?? 'unknown'] ?? 0;
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
