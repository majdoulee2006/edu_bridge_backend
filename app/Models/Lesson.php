<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $primaryKey = 'lesson_id';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'content_url',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'lesson_id', 'lesson_id');
    }
}
