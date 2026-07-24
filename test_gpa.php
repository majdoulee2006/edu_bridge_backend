<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentId = 31;
$res = app(\App\Http\Controllers\StudentParentController::class)->getFullPerformance($studentId);
echo $res->getContent();
