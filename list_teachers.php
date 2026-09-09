<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

// جلب جميع المدرسين في النظام
$teachers = DB::table('teachers')
    ->join('users', 'teachers.user_id', '=', 'users.user_id')
    ->select('teachers.teacher_id', 'users.full_name', 'users.department', 'teachers.specialization')
    ->get();

echo "=== قائمة المدرسين في النظام ===\n";
foreach ($teachers as $t) {
    echo "ID: {$t->teacher_id} | الاسم: {$t->full_name} | القسم: {$t->department} | التخصص: {$t->specialization}\n";
}
