<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'doctor_name',
        'room',
        'time',
    ];

    public function exams()
    {
        return $this->hasMany(Exam::class, 'subject_id', 'id');
    }
}
