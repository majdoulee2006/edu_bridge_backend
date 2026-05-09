<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseTeacher extends Model
{
    protected $primaryKey = 'course_teacher_id';
    public $timestamps = true;

    protected $fillable = ['course_id', 'teacher_id', 'role'];

    public function course()  { return $this->belongsTo(Course::class, 'course_id'); }
    public function teacher() { return $this->belongsTo(Teacher::class, 'teacher_id'); }
}
