<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * تنبيه تلاعب/خلل بساعة هاتف الطالب أثناء مسح الحضور.
 * يُرسَل تنبيه للطالب، فإن تكرر دون "رد" (acknowledged_at) يُصعَّد لرئيس القسم مرة واحدة (escalated_at).
 */
class AttendanceClockAlert extends Model
{
    protected $table = 'attendance_clock_alerts';

    protected $fillable = ['student_id', 'session_id', 'kind', 'skew_seconds', 'acknowledged_at', 'ack_note', 'escalated_at'];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'escalated_at'    => 'datetime',
    ];
}
