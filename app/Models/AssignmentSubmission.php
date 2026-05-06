<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $primaryKey = 'submission_id';

    protected $fillable = [
        'assignment_id', 'student_id', 'file_path', 'student_notes', 'grade', 'feedback'
    ];

    // علاقة التسليم بالواجب
    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id', 'assignment_id');
    }
}