<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $primaryKey = 'lesson_id';

    protected $fillable = [
        'course_id', 'title', 'type', 'description', 'content_url', 'file_size', 'duration'
    ];

    // علاقة المحاضرة بالمادة
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
}