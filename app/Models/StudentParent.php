<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentParent extends Model
{
    // اسم الجدول الوسيط الجديد
    protected $table = 'parent_students';

    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'parent_id',
        'student_id',
        'relationship', // father, mother, guardian
    ];

    // parent_id/student_id هما FK على users.user_id (وليس parents.parent_id/students.student_id)

    // علاقة بولي الأمر
    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id', 'user_id');
    }

    // علاقة بالطالب
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'user_id');
    }
}
