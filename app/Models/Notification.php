<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];

    // علاقة لجلب بيانات الشخص المستلم للإشعار (الطالب)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // علاقة لجلب بيانات الشخص المرسل للإشعار (المدرب، الإدارة..)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }
}