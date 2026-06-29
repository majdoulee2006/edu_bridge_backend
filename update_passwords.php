<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$usernames = ['st_test1','st_test2','st_test3','st_test4','st_test5','st_test6','st_test7'];
$newPassword = bcrypt('12345678');

foreach ($usernames as $u) {
    $user = DB::table('users')->where('username', $u)->first();
    if (!$user) { echo "Not found: $u\n"; continue; }
    DB::table('users')->where('username', $u)->update(['password' => $newPassword]);
    echo "✓ {$user->full_name} | university_id: {$user->university_id} | username: {$u}\n";
}
