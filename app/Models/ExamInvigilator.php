<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** مراقب قاعة في تقييم معتمد، يختاره رئيس القسم من أساتذة القسم. */
class ExamInvigilator extends Model
{
    protected $fillable = ['exam_id', 'room', 'user_id'];

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'exam_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
