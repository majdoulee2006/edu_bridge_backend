<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    DB::beginTransaction();
    $email = "test_trainer_" . rand(100, 999) . "@example.com";
    $username = explode('@', $email)[0] . rand(10, 99);

    $user = User::create([
        'full_name' => 'مدرب تجريبي من السكريبت',
        'username' => $username,
        'email' => $email,
        'password' => Hash::make('password123'),
        'role' => 'teacher',
        'role_id' => 2,
        'status' => 'active',
    ]);

    DB::table('teachers')->insert([
        'user_id' => $user->user_id,
        'specialization' => 'اختبار برمجيات',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::commit();
    echo "✅ Success: Created User ID {$user->user_id} with Email: {$email}\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
