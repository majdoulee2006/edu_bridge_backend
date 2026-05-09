<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// جلب مدرب وطالب حقيقيين
$teacher = DB::table('teachers')->first();
$student = DB::table('students')->first();

if (!$teacher || !$student) {
    echo "Please ensure you have at least one teacher and one student in the database." . PHP_EOL;
    exit;
}

// إضافة طلب إجازة للمدرب
DB::table('leave_requests')->insert([
    'teacher_id' => $teacher->teacher_id,
    'type' => 'full_day',
    'leave_category' => 'daily',
    'date' => now()->toDateString(),
    'reason' => 'ظرف عائلي طارئ - تجربة الربط',
    'status' => 'pending',
    'created_at' => now(),
    'updated_at' => now(),
]);

// إضافة طلب إجازة للطالب
DB::table('leave_requests')->insert([
    'student_id' => $student->student_id,
    'type' => 'hourly',
    'leave_category' => 'hourly',
    'date' => now()->toDateString(),
    'reason' => 'موعد طبي - تجربة الربط',
    'status' => 'pending',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Seed data for testing HOD features added successfully." . PHP_EOL;
