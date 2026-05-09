<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select('SHOW TABLES');
foreach ($tables as $table) {
    foreach ($table as $key => $value) {
        echo $value . PHP_EOL;
        $columns = Schema::getColumnListing($value);
        echo "  - " . implode(', ', $columns) . PHP_EOL;
    }
}
