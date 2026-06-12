<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parent = App\Models\Parents::where('user_id', 55)->first();
$childStudent = \App\Models\Student::where('student_code', '2026102')->first();

if ($parent && $childStudent) {
    try {
        $res = \App\Models\StudentParent::firstOrCreate([
            'parent_id'  => $parent->parent_id,
            'student_id' => $childStudent->student_id,
        ], [
            'relationship' => 'parent',
        ]);
        echo "Successfully inserted or found: " . json_encode($res) . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Parent or child not found.\n";
}
