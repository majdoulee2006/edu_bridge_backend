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
        'level',
        'birth_date',
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



    public function parentStudents() {
    return $this->hasMany(StudentParent::class, 'student_id');
}

public function parents() {
    return $this->belongsToMany(Parents::class, 'parent_students', 'student_id', 'parent_id');
}



}
