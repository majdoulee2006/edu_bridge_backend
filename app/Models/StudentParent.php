<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentParent extends Model
{
    // 1. تحديد اسم الجدول لأنه مختلف عن اسم الموديل
    protected $table = 'parents';

    // 2. تحديد المفتاح الأساسي لأنه ليس id
    protected $primaryKey = 'parent_id';

    // 3. الحقول اللي مسموح نعدلها ونضيفها (حسب ملف الـ Migration تبعك)
    protected $fillable = [
        'user_id',
        'phone_number', // تأكدي من الاسم في ملف الميجريشن لو كان مختلف
        'address',
    ];

    // 4. علاقة ولي الأمر باليوزر (كل ولي أمر هو يوزر في الأساس)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // 5. علاقة ولي الأمر بأبنائه (ولي الأمر عنده كثير طلاب)
    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id', 'parent_id');
    }
}
