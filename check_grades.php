<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = DB::table('grade_entries')
    ->join('grade_events', 'grade_entries.grade_event_id', '=', 'grade_events.id')
    ->where('student_id', 31)
    ->select('grade_entries.score', 'grade_events.max_score')
    ->get();

$totalScore = 0;
$totalMax = 0;
foreach($entries as $e) {
    $totalScore += $e->score;
    $totalMax += $e->max_score;
}

$perc = $totalMax > 0 ? round(($totalScore / $totalMax) * 100, 1) : 0;
echo "Student 31 Grade Entries Percentage: $perc %\n";
echo "Old grade table average: " . DB::table('grades')->where('student_id', 31)->avg('score') . "\n";
