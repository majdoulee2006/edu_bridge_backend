<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    // 1. المفتاح الأساسي
    protected $primaryKey = 'department_id';

    // 2. الحقول المسموح بتعبئتها
    protected $fillable = [
        'department_name',
        'description',
    ];

    // 3. علاقة القسم بالكورسات (Many-to-Many عبر الجدول الوسيط)
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_departments', 'department_id', 'course_id');
    }

    // 4. علاقة القسم برئيس القسم (واحد لواحد One-to-One)
    public function head()
    {
        return $this->hasOne(Head::class, 'department_id', 'department_id');
    }
}
