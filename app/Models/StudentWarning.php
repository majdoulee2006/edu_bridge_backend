<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentWarning extends Model
{
    use HasFactory;

    protected $table = 'student_warnings';
    protected $primaryKey = 'warning_id';

    protected $fillable = [
        'student_id',
        'warning_level',
        'absence_days',
        'message',
        'is_read',
        'action_data',
    ];

    protected $casts = [
        'action_data' => 'array',
        'is_read'     => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
