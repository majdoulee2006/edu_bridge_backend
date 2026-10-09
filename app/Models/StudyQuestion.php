<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyQuestion extends Model
{
    protected $fillable = ['material_id', 'question', 'options', 'correct_index', 'explanation', 'position'];

    protected $casts = ['options' => 'array', 'correct_index' => 'integer'];
}
