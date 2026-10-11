<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceTeacherReceipt extends Model
{
    use HasFactory;

    protected $table = 'attendance_teacher_receipts';

    protected $fillable = [
        'session_id',
        'student_id',
        'client_scan_id',
        'device_id',
        'teacher_time',
        'signature',
        'verified',
    ];

    protected $casts = [
        'teacher_time' => 'datetime',
        'verified'     => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
