<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['name', 'department_id'];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_program', 'program_id', 'course_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
}
