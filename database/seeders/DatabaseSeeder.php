<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
       User::create([
        'full_name' => 'إدارة المعهد التقني',
        'username' => 'admin_main', // 👈 ضروري جداً
        'email' => 'admin@edu-bridge.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'status' => 'active',
    ]);

    // 2. حساب رئيس قسم (Dept Head) - أضفنا username
    User::create([
        'full_name' => 'أحمد ديب',
        'username' => 'ahmad_head', // 👈 ضروري جداً
        'email' => 'head@test.com',
        'password' => Hash::make('12345678'),
        'role' => 'head',
        'status' => 'active',
    ]);

        // 3. حساب طالب تجريبي
        $student = User::create([
            'full_name' => 'عمر الخالد',
            'university_id' => '2026100',
            'username' => '2026100',
            'email' => 'student@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'student',
            'status' => 'active',
        ]);

        // 4. حساب ولي أمر تجريبي
        $parentUser = User::create([
            'full_name' => 'أبو عمر الخالد',
            'username' => '098638799',
            'phone' => '0986387993',
            'email' => 'parent@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'parent',
            'status' => 'active',
        ]);

        // 5. حساب مدرس تجريبي
        User::create([
            'full_name' => 'د. سامر المحمد',
            'username' => '0986387992',
            'phone' => '0986387992',
            'email' => 'teacher@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'teacher',
            'status' => 'active',
        ]);

        // استدعاء السيدرز الأخرى التي تعتمد على وجود المستخدمين
        $this->call([
            ParentSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}