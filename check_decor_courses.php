<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// عرض المواد التابعة لدورة ديكور (ID: 8)
$courses = DB::table('course_program')
    ->join('courses', 'course_program.course_id', '=', 'courses.course_id')
    ->where('course_program.program_id', 8)
    ->select('courses.course_id', 'courses.title', 'courses.year', 'courses.semester_id')
    ->get();

echo "=== مواد دورة ديكور (Program ID: 8) في قاعدة البيانات ===\n";
foreach ($courses as $c) {
    echo "- مادة: {$c->title} | السنة: {$c->year} | الفصل: {$c->semester_id}\n";
}
