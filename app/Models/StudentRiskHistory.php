<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRiskHistory extends Model
{
    protected $table = 'student_risk_history';

    protected $fillable = ['student_id', 'computed_on', 'score', 'level', 'reasons'];

    protected $casts = ['score' => 'float', 'reasons' => 'array', 'computed_on' => 'date'];
}
