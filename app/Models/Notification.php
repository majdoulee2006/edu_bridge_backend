<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'sender_id', 'title', 'message', 'type', 'category', 'is_read', 'related_id'];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Accessor for localized Title
    public function getTitleAttribute($value)
    {
        return function_exists('localizeNotificationTitle') ? localizeNotificationTitle($value) : $value;
    }

    // Accessor for localized Message
    public function getMessageAttribute($value)
    {
        return function_exists('localizeNotificationBody') ? localizeNotificationBody($value) : $value;
    }

    // Accessor for localized Body (alias)
    public function getBodyAttribute($value)
    {
        $val = $value ?? $this->attributes['message'] ?? null;
        return function_exists('localizeNotificationBody') ? localizeNotificationBody($val) : $val;
    }

    // Raw Arabic Title (if ever needed explicitly)
    public function getRawTitleAttribute()
    {
        return $this->attributes['title'] ?? '';
    }

    // Raw Arabic Message (if ever needed explicitly)
    public function getRawMessageAttribute()
    {
        return $this->attributes['message'] ?? '';
    }

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