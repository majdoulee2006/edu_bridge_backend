<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$roles = ['teacher', 'student', 'parent'];
foreach ($roles as $role) {
    echo "--- Role: $role ---\n";
    $query = DB::table('users');
    if ($role === 'teacher') {
        $query->join('teachers', 'users.user_id', '=', 'teachers.user_id')
              ->select('users.*', 'teachers.specialization', 'teachers.teacher_id');
    } elseif ($role === 'student') {
        $query->join('students', 'users.user_id', '=', 'students.user_id')
              ->select('users.*', 'students.student_code', 'students.level', 'students.student_id');
    } elseif ($role === 'parent') {
        $query->join('parents', 'users.user_id', '=', 'parents.user_id')
              ->select('users.*', 'parents.parent_id');
    }
    $users = $query->get();
    echo "Count: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "- {$user->full_name} ({$user->email})\n";
    }
}
