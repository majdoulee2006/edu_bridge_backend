<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentMeetingRequest extends Model
{
    use HasFactory;

    protected $table = 'parent_meeting_requests';

    protected $fillable = [
        'parent_user_id',
        'student_id',
        'subject',
        'reason',
        'preferred_date',
        'status',
        'admin_response',
        'scheduled_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'scheduled_at' => 'datetime',
    ];

    // علاقة طلب الموعد بولي الأمر (User)
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_user_id', 'user_id');
    }

    // علاقة طلب الموعد بالطالب المعني
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
