<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $primaryKey = 'exam_id';

    protected $fillable = [
        'course_id',
        'exam_name',
        'exam_date',
        'max_score'
    ];

    // الامتحان تابع لمادة معينة
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // الامتحان الواحد له درجات كثيرة (لكل الطلاب)
    public function grades()
    {
        return $this->hasMany(Grade::class, 'exam_id', 'exam_id');
    }
}
