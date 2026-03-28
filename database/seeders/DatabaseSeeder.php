<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
{
    // 1. إنشاء مستخدم (أب) ليكون معلماً
    $user = \App\Models\User::create([
        'full_name' => 'أحمد علي',
        'email' => 'teacher1@example.com',
        'password' => bcrypt('password123'),
        'phone' => '0912345678',
        'role' => 'teacher',
        'status' => 'active',
    ]);

    // 2. إنشاء المعلم وربطه بالمستخدم
    $teacher = \App\Models\Teacher::create([
        'user_id' => $user->user_id,
        'specialization' => 'Computer Science',
    ]);

    // 3. إنشاء مادة (Course)
    $course = \App\Models\Course::create([
        'title' => 'Laravel Basics',
        'description' => 'تعلم أساسيات إطار عمل لارافل',
        'level' => 'Beginner',
    ]);

    // 4. الربط بين المعلم والمادة في الجدول الوسيط (الذي يحتوي على حقل role)
    $teacher->courses()->attach($course->course_id, ['role' => 'Lead Instructor']);
}
}
