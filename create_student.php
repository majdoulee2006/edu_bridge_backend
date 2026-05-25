<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::firstOrCreate(
    ['username' => '2026101'],
    [
        'full_name' => 'عمر خليل',
        'email' => 'student2@test.com',
        'password' => bcrypt('123456'),
        'role' => 'student',
        'status' => 'active',
        'department' => 'طب أسنان',
    ]
);
App\Models\Student::firstOrCreate(
    ['student_code' => '2026101'],
    [
        'user_id' => $user->user_id,
        'level' => 'سنة أولى'
    ]
);
echo "Student 2 created successfully!\n";
