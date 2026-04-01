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
        'full_name',
        'username',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'university_id',
        'department',
        'branch',
        'children_ids',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'children_ids' => 'array',
        ];
    }

    // ✅ أضيفي هذه العلاقة ليعمل الـ load('student') في الراوت
    public function student()
    {
        // تأكدي أن اسم المودل Student وأن الحقل الأجنبي هو user_id في جدول الطلاب
        return $this->hasOne(Student::class, 'user_id', 'user_id');
    }
}
