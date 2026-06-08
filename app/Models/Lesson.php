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
        'type',
        'description',
        'content_url',
        'file_size',
        'duration',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'teacher_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'lesson_id', 'lesson_id');
    }
}
