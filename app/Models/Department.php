<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    // 1. المفتاح الأساسي
    protected $primaryKey = 'department_id';

    // 2. الحقول المسموح بتعبئتها
    protected $fillable = [
        'name',
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

    // 5. الاسم المترجم بحسب اللغة الحالية
    public function getDisplayNameAttribute(): string
    {
        $name = trim($this->attributes['name'] ?? $this->attributes['department_name'] ?? '');
        $map = [
            'هندسي'                  => 'dept_engineering',
            'نظم المعلومات الحاسوبية' => 'dept_cis',
            'طبي'                    => 'dept_medical',
            'تجاري'                  => 'dept_business',
        ];

        $key = $map[$name] ?? null;
        if ($key && \Illuminate\Support\Facades\Lang::has("messages.{$key}")) {
            return __("messages.{$key}");
        }

        return $name;
    }
}
