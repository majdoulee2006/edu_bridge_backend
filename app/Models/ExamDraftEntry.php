<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** تقييم (مذاكرة أو امتحان) داخل مسودة الجدول الامتحاني. rooms = [{name, capacity, students:[ids], proctor:user_id|null}]. */
class ExamDraftEntry extends Model
{
    protected $fillable = ['draft_id', 'course_id', 'program_id', 'year', 'class_group', 'kind', 'exam_date', 'start_time', 'end_time', 'duration_minutes', 'rooms', 'locked', 'source'];

    protected $casts = ['rooms' => 'array', 'locked' => 'boolean', 'year' => 'integer', 'duration_minutes' => 'integer', 'exam_date' => 'date:Y-m-d'];

    public function draft()
    {
        return $this->belongsTo(ScheduleDraft::class, 'draft_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /** مفتاح المهمة الذي يستعمله المولّد: شعبة|مقرر|نوع. */
    public function taskKey(): string
    {
        return $this->program_id . '-' . $this->year . '|' . $this->course_id . '|' . $this->kind;
    }
}
