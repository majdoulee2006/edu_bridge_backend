<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $primaryKey = 'course_id';

    protected $fillable = [
        'title',       // بالميجريشن مكتوب title
        'description',
        'level',       // مستوى المادة
    ];

    // علاقة المادة بالمعلمين (Many to Many)
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'course_teachers', 'course_id', 'teacher_id')
                    ->withPivot('role');
    }
    
    // إضافة علاقة المادة مع الطلاب
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'course_id', 'student_id');
    }
    
    // علاقة المادة بالمحاضرات (الدروس) - شغلك 
    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'course_id', 'course_id')
                    ->orderBy('created_at', 'desc'); // عشان تترتب من الأحدث للأقدم
    }

    // 👇 الإضافات الجديدة المطلوبة من زميلك 👇
    
    // علاقة المادة بالجدول الدراسي
    public function schedule()
    {
        return $this->hasOne(Schedule::class, 'course_id', 'course_id');
    }

    // علاقة المادة بالإعلانات
    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'course_id', 'course_id');
    }
}