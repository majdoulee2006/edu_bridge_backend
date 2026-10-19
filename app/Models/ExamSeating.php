<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** قاعة طالب لتقييم معتمد (توزيع طلاب الشعبة على عدة قاعات). */
class ExamSeating extends Model
{
    protected $fillable = ['exam_id', 'student_id', 'room', 'seat'];

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'exam_id');
    }
}
