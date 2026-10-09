<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyAttempt extends Model
{
    protected $fillable = ['material_id', 'student_user_id', 'score', 'total', 'answers'];

    protected $casts = ['answers' => 'array'];
}
