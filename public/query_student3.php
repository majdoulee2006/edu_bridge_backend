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
    $students = \App\Models\Student::with(['user', 'program'])->get();

    echo "<table border='1'>";
    echo "<tr><th>الاسم</th><th>الكود</th><th>الاختصاص</th><th>السنة</th></tr>";
    foreach($students as $student) {
        $name = $student->user->full_name ?? 'بدون اسم';
        $code = $student->student_code;
        $prog = $student->program->name ?? 'بدون اختصاص';
        $year = $student->level ?? 'بدون سنة';
        
        echo "<tr><td>$name</td><td>$code</td><td>$prog</td><td>$year</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
