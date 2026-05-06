<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $primaryKey = 'assignment_id'; // لأنك مسميتيه هيك بالداتا بيز

    protected $fillable = [
        'course_id', 'title', 'description', 'type', 'due_date', 'max_points'
    ];

    protected $casts = [
        'due_date' => 'datetime', // عشان نقدر نقارن التاريخ بسهولة
    ];

    // علاقة الواجب بالمادة
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // علاقة الواجب بالتسليمات
    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id', 'assignment_id');
    }
}
