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

try {
    $students = \App\Models\Student::with(['user', 'program'])
        ->whereHas('program', function($q) {
            $q->where('name', 'like', '%الكترون%')->orWhere('name', 'like', '%إلكترون%');
        })
        ->where(function($q) {
            $q->where('level', 'like', '%ثاني%')->orWhere('level', 'like', '%2%');
        })
        ->get();

    foreach($students as $student) {
        echo "اسم الطالب: " . ($student->user->full_name ?? 'بدون اسم') . " - ";
        echo "الاختصاص: " . ($student->program->name ?? 'بدون اختصاص') . " - ";
        echo "السنة: " . ($student->level ?? 'بدون سنة') . "\n<br>";
    }
    if($students->isEmpty()) {
        echo "لم يتم العثور على أي طالب في اختصاص الإلكترون سنة ثانية.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
