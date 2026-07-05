<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'teacher_id', 'course_id', 'title', 'description',
        'duration_minutes', 'total_marks', 'start_at', 'end_at', 'is_published',
    ];

    protected $casts = [
        'start_at'     => 'datetime',
        'end_at'       => 'datetime',
        'is_published' => 'boolean',
    ];

    public function teacher()   { return $this->belongsTo(Teacher::class, 'teacher_id', 'teacher_id'); }
    public function course()    { return $this->belongsTo(Course::class,  'course_id',  'course_id');  }
    public function questions() { return $this->hasMany(QuizQuestion::class)->orderBy('order_num');    }
}
