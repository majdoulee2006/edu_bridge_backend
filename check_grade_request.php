<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

// كل الطلبات
$all = DB::table('grade_report_requests')->get();
echo "=== All Grade Report Requests (" . $all->count() . ") ===\n";
foreach ($all as $r) {
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// المعلمين وتوكناتهم
echo "\n=== Teachers & device_tokens ===\n";
$teachers = DB::table('teachers')
    ->join('users', 'teachers.user_id', '=', 'users.user_id')
    ->get(['users.user_id', 'users.full_name', 'users.device_token']);
foreach ($teachers as $t) {
    echo "ID:{$t->user_id} | {$t->full_name} | token: " . ($t->device_token ? substr($t->device_token, 0, 30) . '...' : 'NULL') . "\n";
}

// هل في grade_entries؟
echo "\n=== Grade Entries Count per Course ===\n";
$entries = DB::table('grade_events')
    ->join('grade_entries', 'grade_events.id', '=', 'grade_entries.grade_event_id')
    ->join('courses', 'grade_events.course_id', '=', 'courses.course_id')
    ->whereNotNull('grade_entries.score')
    ->select('courses.title', DB::raw('count(*) as cnt'))
    ->groupBy('courses.title')
    ->get();
foreach ($entries as $e) {
    echo "{$e->title}: {$e->cnt} علامة مدخلة\n";
}
