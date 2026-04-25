<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use HasFactory;

    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'course_id',
        'teacher_id',
        'day',           // تم تعديل الاسم ليتطابق مع المايجريشن الخاص بكِ
        'start_time',
        'end_time',
        'room',          // تم تعديل الاسم ليتطابق مع المايجريشن الخاص بكِ
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function teacher()
    {
        // تم التعديل ليرتبط بجدول المستخدمين (User) لأن المعلم مخزن هناك
        return $this->belongsTo(User::class, 'teacher_id', 'id');
    }
}