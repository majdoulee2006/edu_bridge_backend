<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher; 
use App\Models\Announcement;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        // شلنا أسطر الـ truncate عشان ما نمسح يوزرات رفقاتك
        
        // 1. إنشاء حسابك كمعلمة (إضافة فقط)
        $user = User::create([
            'full_name' => 'Hiba Isaa',
            'username'  => 'heba_2026',
            'email'     => 'heba8@test.com',
            'password'  => Hash::make('123456'),
            'role'      => 'teacher',
            'status'    => 'active',
            'phone'     => '093126666', 
            'department'=> 'Software Engineering',
            'branch'    => 'Main Campus',
        ]);

        // --- إضافة بيانات التخصص في جدول المعلمين ---
        Teacher::create([
            'user_id' => $user->user_id,
            'specialization' => 'Mobile Application Development',
        ]);

        // 2. إضافة إعلان الامتحان في جدول announcements
        Announcement::create([
            'user_id' => $user->user_id,
            'title'   => 'اختبار نصف الفصل (Midterm Exam)',
            'content' => 'مادة تطوير تطبيقات الموبايل. الموعد: الخميس 23 أبريل، من الساعة 10:00 صباحاً حتى 12:00 ظهراً.',
            'type'    => 'general',
            'course_id' => null, 
        ]);
    }
}