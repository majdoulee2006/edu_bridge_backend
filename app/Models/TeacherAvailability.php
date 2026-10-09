<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** وقت لا يستطيعه المعلم (unavailable: صلب) أو يفضّل تجنبه (avoid: ناعم). */
class TeacherAvailability extends Model
{
    protected $table = 'teacher_availability';

    protected $fillable = ['teacher_user_id', 'day', 'period', 'kind'];
}
