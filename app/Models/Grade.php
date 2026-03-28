<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $primaryKey = 'grade_id';

    protected $fillable = [
        'student_id',
        'exam_id',
        'score',
        'remarks'
    ];

    // الدرجة تابعة لطالب معين
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // الدرجة تابعة للامتحان المعين
    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'exam_id');
    }
}
