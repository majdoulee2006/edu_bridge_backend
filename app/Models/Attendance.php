<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';
    protected $primaryKey = 'attendance_id';
    
    protected $fillable = [
        'student_id',
        'lesson_id',
        'status',
        'attendance_date',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];
}
