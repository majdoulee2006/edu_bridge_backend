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
}
