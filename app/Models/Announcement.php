<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $primaryKey = 'announcement_id'; 
    
    // شلنا الـ guarded واعتمدنا على الـ fillable لأنو أأمن وهو اللي طلبه زميلك
    protected $fillable = [
        'user_id', 
        'title', 
        'content', 
        'type', 
        'course_id', 
        'target_role', 
        'department_id', 
        'academic_year',
        'category', // 👈 جديد من طلبات زميلك
        'image'     // 👈 جديد من طلبات زميلك
    ];

    // هاي الدالة هي اللي بتجيب بيانات صاحب الإعلان (شغلك القديم الممتاز)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // علاقة الإعلان بالمادة (كما طلب زميلك)
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // دالة إضافية طلبها زميلك (هو سماها author، بس هي نفس دالة user اللي انتي عملتيها)
    // ضفتها احتياطاً عشان لو زميلك استخدمها بكوده ما يضرب عنده إيرور
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}