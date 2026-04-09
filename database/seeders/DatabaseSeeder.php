<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء حساب طالب تجريبي
        User::create([
            'full_name' => 'Student User',
            'username' => 'student01',
            'email' => 'student@edubridge.com',
            'password' => Hash::make('password123'),
            'role' => 'student', // تأكدي أن هذا الحقل موجود في جدولك
        ]);

        // 2. إنشاء حساب ولي أمر تجريبي
        User::create([
            'full_name' => 'Parent User',
            'username' => 'parent01',
            'email' => 'parent@edubridge.com',
            'password' => Hash::make('password123'),
            'role' => 'parent',
        ]);

        // 3. إنشاء حساب موظف إداري
        User::create([
            'full_name' => 'Admin Staff',
            'username' => 'admin01',
            'email' => 'admin@edubridge.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
        // إضافة إعلان هام
        \App\Models\Announcement::create([
            'title' => 'تم إصدار جدول الامتحانات', 
            'content' => 'يرجى مراجعة الجدول والتأكد من القاعات.', 
            'type' => 'إعلان هام'
        ]);

        // إضافة نشاط طلابي
        \App\Models\Announcement::create([
            'title' => 'رحلة علمية', 
            'content' => 'نظم قسم البرمجيات رحلة إلى المعرض التقني.', 
            'type' => 'نشاط طلابي'
        ]);
    }
}
