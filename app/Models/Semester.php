<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $primaryKey = 'semester_id';
    protected $table = 'semesters';

    protected $fillable = ['name', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function courses()
    {
        return $this->hasMany(Course::class, 'semester_id', 'semester_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'semester_id', 'semester_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'semester_id', 'semester_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'semester_id', 'semester_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'semester_id', 'semester_id');
    }

    // Scope للفصل النشط
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
