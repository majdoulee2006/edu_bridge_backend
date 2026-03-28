<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_id';

    protected $fillable = [
        'user_id',
        'parent_id',
        'student_code',
        'level',      // بالميجريشن مكتوب level وليس grade_level
        'birth_date', // بالميجريشن مكتوب birth_date وليس date_of_birth
    ];

    // علاقة الطالب بالحساب الأساسي (User)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // علاقة الطالب بالمواد اللي مسجل فيها (Many to Many عبر Enrollments)
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'enrollments', 'student_id', 'course_id')
                    ->withPivot('status', 'enrollment_date')
                    ->withTimestamps();
    }

    // علاقة الطالب بعلاماته
    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_id', 'student_id');
    }
}
