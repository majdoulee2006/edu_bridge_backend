<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * حالة اشتباه في سجل حضور واحد. تُنتجها قواعد AttendanceIntelligence ويراجعها المعلم؛
 * لا يترتب عليها أي عقوبة تلقائية.
 */
class AttendanceFlag extends Model
{
    public const OPEN      = 'open';
    public const CONFIRMED = 'confirmed';
    public const DISMISSED = 'dismissed';

    protected $fillable = [
        'attendance_id', 'session_id', 'student_id', 'lesson_id', 'course_id',
        'rule', 'severity', 'details', 'status',
        'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'details'     => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'attendance_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
}
