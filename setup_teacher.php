<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// تعيين كلمة مرور للمعلمين الموجودين
DB::table('users')
    ->where('email', 'SHADEZU@GMAIL.COM')
    ->update(['password' => Hash::make('123456')]);

// أيضاً إنشاء معلم تجريبي جديد إذا أردنا
$existing = DB::table('users')->where('email', 'teacher@test.com')->first();
if (!$existing) {
    $userId = DB::table('users')->insertGetId([
        'full_name'  => 'أستاذ تجريبي',
        'email'      => 'teacher@test.com',
        'password'   => Hash::make('123456'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('teachers')->insert([
        'user_id'        => $userId,
        'specialization' => 'علوم الحاسب',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
    echo "✅ تم إنشاء معلم تجريبي: teacher@test.com / 123456\n";
} else {
    echo "ℹ️ المعلم التجريبي موجود مسبقاً\n";
}

echo "✅ تم تعيين كلمة مرور 123456 للمعلمة شهد\n";
echo "\n=== حسابات المعلمين ===\n";
$teachers = DB::select('SELECT users.email, users.full_name FROM teachers JOIN users ON teachers.user_id = users.user_id');
foreach ($teachers as $t) {
    echo "- {$t->full_name} | {$t->email}\n";
}
