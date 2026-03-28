<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

   protected $primaryKey = 'teacher_id';

    protected $fillable = [
        'user_id',
        'specialization',
    ];
    // علاقة المعلم بالحساب الأساسي (User)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // علاقة المعلم بالمواد (Many to Many)
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_teachers', 'teacher_id', 'course_id')
                    ->withPivot('role'); // عشان حقل الـ role اللي بالجدول الوسيط
    }
}
