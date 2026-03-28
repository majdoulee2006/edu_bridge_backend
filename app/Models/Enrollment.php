<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $primaryKey = 'enrollment_id';

    protected $fillable = [
        'student_id',
        'course_id',
        'enrollment_date',
        'status'
    ];

    // ممكن نربطه بالطالب وبالكورس إذا احتجنا مستقبلاً
    public function student() { return $this->belongsTo(Student::class, 'student_id'); }
    public function course() { return $this->belongsTo(Course::class, 'course_id'); }
}
