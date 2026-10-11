<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;


    protected $table = 'attendance';
    protected $primaryKey = 'attendance_id';

    protected $fillable = [
        'student_id',
        'lesson_id',
        'session_id',
        'status',
        'attendance_date',
        'excuse_text',
        'excuse_attachment',
        'excuse_status',
        'device_id',
        'latitude',
        'longitude',
        'reject_reason',
        'ip_address',
        'user_agent',
        'qr_age_seconds',
        'face_image',
        'face_score',
        'face_status',
        'source',
        'client_scan_id',
        'time_confidence',
        'synced_at',
        'countersigned',
        'review_status',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'synced_at'       => 'datetime',
        'countersigned'   => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id', 'lesson_id');
    }
}
