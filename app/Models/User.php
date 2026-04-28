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
        'department',
        'branch',
        'children_ids',
    ];

    protected $appends = ['role']; // ✅ لإضافة الـ role تلقائياً للـ JSON

    // ✅ تحويل role_id الرقمي إلى نص (admin, parent, etc)
    public function getRoleAttribute()
    {
        $roles = [
            1 => 'admin',
            2 => 'teacher',
            3 => 'student',
            4 => 'parent',
            5 => 'head'
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
            'password' => 'hashed',
            'children_ids' => 'array',
            'birth_date' => 'date',

        ];

    }

    // ✅ أضيفي هذه العلاقة ليعمل الـ load('student') في الراوت
    public function student()
    {
        // تأكدي أن اسم المودل Student وأن الحقل الأجنبي هو user_id في جدول الطلاب
        return $this->hasOne(Student::class, 'user_id', 'user_id');
    }

}
