<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('username', 'hudashbli8')->first();
$user->password = Hash::make('12345678');
$user->save();

echo "Password updated to 12345678 successfully.";
