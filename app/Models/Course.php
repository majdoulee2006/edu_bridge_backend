<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $primaryKey = 'course_id';

    protected $fillable = [
        'title',
        'description',
        'level',
    ];

    // علاقة المادة بالمعلمين (Many to Many)
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'course_teachers', 'course_id', 'teacher_id')
                    ->withPivot('role');
    }

    // علاقة المادة بالأقسام (Many to Many)
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'course_departments', 'course_id', 'department_id');
    }

    // علاقة المادة بالواجبات
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'course_id', 'course_id');
    }
}
