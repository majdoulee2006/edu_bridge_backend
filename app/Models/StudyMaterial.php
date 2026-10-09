<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyMaterial extends Model
{
    public const PENDING = 'pending';
    public const DRAFT = 'draft';
    public const APPROVED = 'approved';
    public const FAILED = 'failed';

    protected $fillable = ['lesson_id', 'course_id', 'teacher_id', 'status', 'summary', 'key_terms', 'source_hash', 'source_chars', 'model', 'failure_reason', 'approved_at', 'approved_by'];

    protected $casts = ['key_terms' => 'array', 'approved_at' => 'datetime'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id', 'lesson_id');
    }

    public function flashcards()
    {
        return $this->hasMany(StudyFlashcard::class, 'material_id')->orderBy('position');
    }

    public function questions()
    {
        return $this->hasMany(StudyQuestion::class, 'material_id')->orderBy('position');
    }
}
