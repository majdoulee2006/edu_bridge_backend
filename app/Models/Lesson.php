<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $primaryKey = 'lesson_id';

    protected $fillable = [
        'course_id',
        'teacher_id',
        'department_id',
        'title',
        'description',
        'content_url',
    ];

    // علاقة المحاضرة بالمادة
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // علاقة المحاضرة بالمعلم
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'teacher_id');
    }

    // علاقة المحاضرة بالقسم
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
}
