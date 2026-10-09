<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleDraftEntry extends Model
{
    protected $fillable = ['draft_id', 'course_id', 'teacher_user_id', 'program_id', 'year', 'class_group', 'day', 'period', 'room', 'locked', 'source'];

    protected $casts = ['locked' => 'boolean', 'period' => 'integer', 'year' => 'integer'];

    public function draft()
    {
        return $this->belongsTo(ScheduleDraft::class, 'draft_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
}
