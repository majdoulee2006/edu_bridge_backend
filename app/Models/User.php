<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'role_id',
        'full_name',
        'username',
        'email',
        'password',
        'phone',
        'status',
        'university_id',
        'gender',
        'birth_date',
        'academic_year',
        'department',
        'branch',
        'children_ids',
        'avatar',
        'device_token',
        'last_login',
    ];

    protected $appends = ['role'];

    public function getRoleAttribute()
    {
        $roles = [
            1 => 'admin',
            2 => 'teacher',
            3 => 'student',
            4 => 'parent',
            5 => 'head',
            6 => 'affairs',
        ];
        return $roles[$this->role_id] ?? 'student';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'children_ids'      => 'array',
            'birth_date'        => 'date',
        ];
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', 'user_id');
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'user_id', 'user_id');
    }

    public function parent()
    {
        return $this->hasOne(Parents::class, 'user_id', 'user_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id', 'user_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id', 'user_id');
    }
}
