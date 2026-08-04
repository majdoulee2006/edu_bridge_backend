<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \DB::table('users')->get();
echo json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
