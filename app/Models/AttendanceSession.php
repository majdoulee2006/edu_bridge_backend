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
        'closed_at',
        'analyzed_at',
        'offline_mode',
        'session_uuid',
        'session_secret',
        'require_face_verification',
        'sync_deadline_at',
        'finalized_at',
    ];

    // عشان يسهل علينا التعامل مع الوقت
    protected $casts = [
        'expires_at'                => 'datetime',
        'session_expires_at'        => 'datetime',
        'is_active'                 => 'boolean',
        'require_face_verification' => 'boolean',
        'previous_tokens'           => 'array',
        'closed_at'          => 'datetime',
        'analyzed_at'        => 'datetime',
        'sync_deadline_at'   => 'datetime',
        'finalized_at'       => 'datetime',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id', 'lesson_id');
    }
}