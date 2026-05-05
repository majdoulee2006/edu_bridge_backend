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
        'status',
        'attendance_date',
        'excuse_text',       
        'excuse_attachment', 
        'excuse_status',     
    ];

    // علاقة لجلب بيانات الطالب
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // علاقة لجلب بيانات الدرس/المادة
    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id', 'lesson_id');
    }
}
