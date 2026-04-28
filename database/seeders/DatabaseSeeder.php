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
        'role_id' =>1,
        'status' => 'active',
       ]);

    // 2. حساب رئيس قسم (Dept Head) - أضفنا username
    User::create([
        'full_name' => 'أحمد ديب',
        'username' => 'ahmad_head', // 👈 ضروري جداً
        'email' => 'head@test.com',
        'password' => Hash::make('12345678'),
        'role_id' =>5,
        'status' => 'active',
      ]);

        // 3. حساب طالب تجريبي
    // 3. حساب طالب تجريبي
        $studentUser = User::create([
            'full_name' => 'عمر الخالد',
            'university_id' => '2026100',
            'username' => '2026100',
            'email' => 'student@test.com',
            'password' => Hash::make('12345678'),
            'role_id' => 3,
            'status' => 'active',
            'department' => 'هندسة حواسب وشبكات', // ملاحظة: إذا هاد الحقل انحذف من users تجاهليه
            'academic_year' => 'السنة الخامسة',   // ملاحظة: إذا هاد الحقل انحذف من users تجاهليه
            'phone' => '0930000000',
            'birth_date' => '2002-05-20',
            'gender' => 'ذكر',
        ]);

        // إدخال ملف الطالب في جدول students المرتبط به
        \Illuminate\Support\Facades\DB::table('students')->insert([
            'user_id' => $studentUser->user_id, // أو $studentUser->id حسب اسم الـ primary key عندكم
            'student_code' => '2026100',
            'level' => 'السنة الخامسة',
            'birth_date' => '2002-05-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
       $this->call([
        // ... السيردرات التانية
        AnnouncementSeeder::class,
        ]);
        $this->command->info('✅ تم زراعة المستخدم والإعلانات بنجاح ومحمية من الحذف!');

        // 4. حساب ولي أمر تجريبي
        $parentUser = User::create([
            'full_name' => 'أبو عمر الخالد',
            'username' => '098638799',
            'phone' => '0986387993',
            'email' => 'parent@test.com',
            'password' => Hash::make('12345678'),
            'role_id' =>4,
            'status' => 'active',
        ]);

        // 5. حساب مدرس تجريبي
        User::create([
            'full_name' => 'د. سامر المحمد',
            'username' => '0986387992',
            'phone' => '0986387992',
            'email' => 'teacher@test.com',
            'password' => Hash::make('12345678'),
            'role_id' =>2,
            'status' => 'active',
        ]);

        // استدعاء السيدرز الأخرى التي تعتمد على وجود المستخدمين
        $this->call([
            ParentSeeder::class,
            NotificationSeeder::class,
        ]);
       $this->call([
           AcademicSeeder::class,
        ]);
         
    }
}
