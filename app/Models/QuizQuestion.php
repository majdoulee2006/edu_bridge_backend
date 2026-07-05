<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['quiz_id', 'question_text', 'type', 'marks', 'order_num'];

    public function quiz()    { return $this->belongsTo(Quiz::class); }
    public function options() { return $this->hasMany(QuizOption::class, 'question_id'); }
}
