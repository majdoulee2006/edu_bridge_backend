<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $primaryKey = 'schedule_id';

    // 💡 التعديل هنا: طابقنا الأسماء مع الداتا بيز تماماً
    protected $fillable = [
        'course_id',
        'day',           // كانت day_of_week
        'start_time',
        'end_time',
        'room',          // كانت room_number
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // 💡 تم إزالة دالة teacher() لأن teacher_id غير موجود بجدول schedules. 
    // لنجلب المدرس نستخدم: $schedule->course->teacher
}