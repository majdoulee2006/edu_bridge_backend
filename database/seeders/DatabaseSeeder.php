<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
       // 1. حساب الأدمن
       $existingAdmin = User::where('email', 'admin@edu-bridge.com')
           ->orWhere('username', 'admin_main')
           ->first();
       if (!$existingAdmin) {
           User::create([
               'full_name' => 'إدارة المعهد التقني',
               'username' => 'admin_main',
               'email' => 'admin@edu-bridge.com',
               'password' => Hash::make('12345678'),
               'role_id' => 1,
               'status' => 'active',
           ]);
       } else {
           $existingAdmin->update([
               'username' => 'admin_main',
               'password' => Hash::make('12345678'),
               'role_id' => 1,
           ]);
       }

       // 2. حساب رئيس قسم (Dept Head)
       $existingHead = User::where('email', 'head@test.com')
           ->orWhere('username', 'ahmad_head')
           ->first();
       if (!$existingHead) {
           User::create([
               'full_name' => 'أحمد ديب',
               'username' => 'ahmad_head',
               'email' => 'head@test.com',
               'password' => Hash::make('12345678'),
               'role_id' => 5,
               'status' => 'active',
           ]);
       } else {
           $existingHead->update([
               'username' => 'ahmad_head',
               'password' => Hash::make('12345678'),
               'role_id' => 5,
           ]);
       }

       // 3. حساب طالب تجريبي
       $studentUser = User::where('university_id', '2026100')
           ->orWhere('username', '2026100')
           ->orWhere('email', 'student@test.com')
           ->first();

       if (!$studentUser) {
           $studentUser = User::create([
               'email' => 'student@test.com',
               'university_id' => '2026100',
               'username' => '2026100',
               'full_name' => 'عمر الخالد',
               'password' => Hash::make('12345678'),
               'role_id' => 3,
               'status' => 'active',
               'department' => 'هندسة حواسب وشبكات',
               'academic_year' => 'السنة الخامسة',
               'phone' => '0930000000',
               'birth_date' => '2002-05-20',
               'gender' => 'ذكر',
           ]);
       } else {
           $studentUser->update([
               'username' => '2026100',
               'university_id' => '2026100',
               'password' => Hash::make('12345678'),
               'role_id' => 3,
           ]);
       }

       // إدخال ملف الطالب في جدول students إذا لم يكن موجوداً
       if (!\Illuminate\Support\Facades\DB::table('students')->where('student_code', '2026100')->exists()) {
           \Illuminate\Support\Facades\DB::table('students')->insert([
               'user_id' => $studentUser->user_id,
               'student_code' => '2026100',
               'level' => 'السنة الخامسة',
               'birth_date' => '2002-05-20',
               'created_at' => now(),
               'updated_at' => now(),
           ]);
       }

       // استدعاء السيردرات التانية
       try {
           $this->call([
               AnnouncementSeeder::class,
           ]);
       } catch (\Exception $e) {
           // تخطي خطأ التكرار إن وجد
       }
       $this->command->info('✅ تم زراعة المستخدم والإعلانات بنجاح ومحمية من الحذف!');

       // 4. حساب ولي أمر تجريبي
       $parentUser = User::where('email', 'parent@test.com')
           ->orWhere('username', '098638799')
           ->first();
       if (!$parentUser) {
           $parentUser = User::create([
               'full_name' => 'أبو عمر الخالد',
               'username' => '098638799',
               'phone' => '0986387993',
               'email' => 'parent@test.com',
               'password' => Hash::make('12345678'),
               'role_id' => 4,
               'status' => 'active',
           ]);
       } else {
           $parentUser->update([
               'username' => '098638799',
               'password' => Hash::make('12345678'),
               'role_id' => 4,
           ]);
       }

       // 5. حساب مدرس تجريبي
       $teacherUser = User::where('email', 'teacher@test.com')
           ->orWhere('username', '0986387992')
           ->first();
       if (!$teacherUser) {
           $teacherUser = User::create([
               'full_name' => 'د. سامر المحمد',
               'username' => '0986387992',
               'phone' => '0986387992',
               'email' => 'teacher@test.com',
               'password' => Hash::make('12345678'),
               'role_id' => 2,
               'status' => 'active',
           ]);
       } else {
           $teacherUser->update([
               'username' => '0986387992',
               'password' => Hash::make('12345678'),
               'role_id' => 2,
           ]);
       }

       // إدخال ملف المدرس في جدول teachers إذا لم يكن موجوداً
       if (!\Illuminate\Support\Facades\DB::table('teachers')->where('user_id', $teacherUser->user_id)->exists()) {
           \Illuminate\Support\Facades\DB::table('teachers')->insert([
               'user_id'        => $teacherUser->user_id,
               'specialization' => 'علوم الحاسوب',
               'created_at'     => now(),
               'updated_at'     => now(),
           ]);
       }

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
