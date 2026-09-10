<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

try {
    $depts = DB::table('departments')->get();
    foreach($depts as $dept) {
        echo $dept->department_id . ": " . $dept->name . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
