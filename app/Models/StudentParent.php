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

    // علاقة بولي الأمر
    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id', 'parent_id');
    }

    // علاقة بالطالب
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
