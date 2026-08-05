<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentSummon extends Model
{
    use HasFactory;

    protected $table = 'parent_summons';

    protected $fillable = [
        'sender_user_id',
        'student_id',
        'parent_user_id',
        'reason_title',
        'details',
        'summon_date',
        'status',
    ];

    protected $casts = [
        'summon_date' => 'date',
    ];

    // علاقة الاستدعاء بالمرسل (معلم أو رئيس قسم أو إدارة)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id', 'user_id');
    }

    // علاقة الاستدعاء بالطالب المعني
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // علاقة الاستدعاء بولي الأمر (User)
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_user_id', 'user_id');
    }
}
