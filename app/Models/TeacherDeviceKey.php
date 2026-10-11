<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherDeviceKey extends Model
{
    use HasFactory;

    protected $table = 'teacher_device_keys';

    protected $fillable = [
        'teacher_user_id',
        'device_id',
        'public_key',
        'registered_at',
        'revoked_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'revoked_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'teacher_user_id', 'user_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
