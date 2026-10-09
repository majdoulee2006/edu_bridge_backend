<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyFlashcard extends Model
{
    protected $fillable = ['material_id', 'front', 'back', 'position'];
}
