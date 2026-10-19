<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $primaryKey = 'exam_id';

    /** نوع التقييم: امتحان نهائي، أو «مذاكرة» (امتحان نصفي مصغّر). */
    public const TYPES = ['final' => 'امتحان', 'midterm' => 'مذاكرة'];

    protected $fillable = [
        'course_id',
        'exam_name',
        'exam_type',
        'exam_date',
        'duration_minutes',
        'room',
        'rooms',
        'class_group',
        'max_score'
    ];

    protected $casts = ['rooms' => 'array', 'duration_minutes' => 'integer'];

    // الامتحان تابع لمادة معينة
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /** قاعات الطلاب (توزيعهم على القاعات) والمراقبون */
    public function seatings()
    {
        return $this->hasMany(ExamSeating::class, 'exam_id', 'exam_id');
    }

    public function invigilators()
    {
        return $this->hasMany(ExamInvigilator::class, 'exam_id', 'exam_id');
    }

    // الامتحان الواحد له درجات كثيرة (لكل الطلاب)
    public function grades()
    {
        return $this->hasMany(Grade::class, 'exam_id', 'exam_id');
    }
}
