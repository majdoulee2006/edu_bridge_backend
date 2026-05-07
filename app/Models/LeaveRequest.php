<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',     // مضاف من مايجريشن زميلك
        'type',
        'leave_category', // مضاف من مايجريشن زميلك
        'date',
        'reason',
        'attachment',     // مضاف لرفع الملفات
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id', 'user_id');
    }
}