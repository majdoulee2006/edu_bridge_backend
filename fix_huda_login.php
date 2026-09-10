<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== Ensuring Huda Shbli (2026100) Exists ===\n";

// 1. تحرير الرقم 2026100 من أي حساب آخر
DB::table('users')
    ->where('university_id', '2026100')
    ->where('email', '!=', 'hudashbli8@gmail.com')
    ->where('full_name', 'not like', '%هدى%')
    ->update([
        'university_id' => '2026099',
        'username'      => '2026099',
    ]);

// 2. البحث عن هدى شبلي أو إنشاؤها إن لم تكن موجودة
$huda = DB::table('users')
    ->where('email', 'hudashbli8@gmail.com')
    ->orWhere('username', 'hudashbli8')
    ->orWhere('full_name', 'like', '%هدى%شبلي%')
    ->orWhere('university_id', '2026100')
    ->first();

if ($huda) {
    DB::table('users')->where('user_id', $huda->user_id)->update([
        'full_name'     => 'هدى شبلي',
        'first_name'    => 'هدى',
        'last_name'     => 'شبلي',
        'university_id' => '2026100',
        'username'      => '2026100',
        'email'         => 'hudashbli8@gmail.com',
        'password'      => Hash::make('12345678'),
        'role_id'       => 3,
        'status'        => 'active',
        'gender'        => 'أنثى',
        'academic_year' => 'السنة الأولى',
        'department'    => 'نظم معلومات',
        'phone'         => '0986387552',
        'birth_date'    => '2006-07-01',
        'updated_at'    => now(),
    ]);
    $userId = $huda->user_id;
} else {
    $userId = DB::table('users')->insertGetId([
        'full_name'     => 'هدى شبلي',
        'first_name'    => 'هدى',
        'last_name'     => 'شبلي',
        'university_id' => '2026100',
        'username'      => '2026100',
        'email'         => 'hudashbli8@gmail.com',
        'password'      => Hash::make('12345678'),
        'role_id'       => 3,
        'status'        => 'active',
        'gender'        => 'أنثى',
        'academic_year' => 'السنة الأولى',
        'department'    => 'نظم معلومات',
        'phone'         => '0986387552',
        'birth_date'    => '2006-07-01',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
}

// 3. تحديث أو إنشاء سجل في جدول students
$student = DB::table('students')->where('user_id', $userId)->first();
if ($student) {
    DB::table('students')->where('user_id', $userId)->update([
        'student_code' => '2026100',
        'level'        => 'السنة الأولى',
        'birth_date'   => '2006-07-01',
        'updated_at'   => now(),
    ]);
    $studentId = $student->student_id;
} else {
    $studentId = DB::table('students')->insertGetId([
        'user_id'      => $userId,
        'student_code' => '2026100',
        'level'        => 'السنة الأولى',
        'birth_date'   => '2006-07-01',
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
}

// 4. ربط مع ولي الأمر ثناء شبلي إن وجدت مع معالجة الـ Foreign Key بأمان
$parent = DB::table('users')->where('email', 'thanaashbli@gmail.com')->orWhere('full_name', 'like', '%ثناء%شبلي%')->first();
if ($parent) {
    try {
        $linked = DB::table('parent_students')
            ->where('parent_id', $parent->user_id)
            ->where('student_id', $studentId)
            ->exists();
        if (!$linked) {
            DB::table('parent_students')->insert([
                'parent_id'  => $parent->user_id,
                'student_id' => $studentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    } catch (\Throwable $e) {
        // Safe fallback
    }
}

echo "\nSUCCESS! Account created & updated successfully:\n";
echo "Name: هدى شبلي\n";
echo "University ID: 2026100\n";
echo "Username: 2026100\n";
echo "Email: hudashbli8@gmail.com\n";
echo "Password: 12345678\n";
