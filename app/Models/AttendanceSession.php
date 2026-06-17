<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'qr_token',
        'expires_at',
        'is_active',
        'session_expires_at',
    ];

    // عشان يسهل علينا التعامل مع الوقت
    protected $casts = [
        'expires_at'         => 'datetime',
        'session_expires_at' => 'datetime',
        'is_active'          => 'boolean',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id', 'lesson_id');
    }
}