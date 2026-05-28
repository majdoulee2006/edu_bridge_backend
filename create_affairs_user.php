<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::updateOrCreate(
    ['email' => 'affairs@edu-bridge.com'],
    [
        'full_name' => 'موظف الشؤون',
        'username'  => 'affairs_user',
        'password'  => Hash::make('affairs123'),
        'role_id'   => 6,
        'status'    => 'active',
    ]
);

echo "✅ تم إنشاء مستخدم الشؤون بنجاح!\n";
echo "   User ID  : " . $user->user_id . "\n";
echo "   الاسم    : " . $user->full_name . "\n";
echo "   البريد   : " . $user->email . "\n";
echo "   كلمة المرور: affairs123\n";
