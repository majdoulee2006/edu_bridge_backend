<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $primaryKey = 'announcement_id'; 
    protected $guarded = [];

    // هاي الدالة هي اللي بتجيب بيانات صاحب الإعلان
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    protected $fillable = [
    'user_id', 'title', 'content', 'type', 'course_id', 
    'target_role', 'department_id', 'academic_year' // 👈 الأعمدة الجديدة
];
}