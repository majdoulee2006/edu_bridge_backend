<?php
// app/Models/AbsenceRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsenceRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'request_id';
    protected $table = 'absence_requests';

    protected $fillable = [
        'student_id',
        'date',
        'reason',
        'document',
        'status',
        'reviewed_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
