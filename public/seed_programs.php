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
    // 1. Get the department IDs
    $departments = DB::table('departments')->get()->keyBy('name');

    // Mappings of department names to partial matches or exact matches
    $deptMap = [];
    foreach ($departments as $dept) {
        if (str_contains($dept->name, 'نظم')) {
            $deptMap['cis'] = $dept->department_id;
        } elseif (str_contains($dept->name, 'طبي')) {
            $deptMap['medical'] = $dept->department_id;
        } elseif (str_contains($dept->name, 'هندسي')) {
            $deptMap['engineering'] = $dept->department_id;
        } elseif (str_contains($dept->name, 'تجاري')) {
            $deptMap['commercial'] = $dept->department_id;
        }
    }

    $programsToAdd = [
        'cis' => ['معلوماتية', 'اتصالات', 'الكترون', 'ذكاء اصطناعي'],
        'medical' => ['مخبري', 'صيدلة'],
        'engineering' => ['هندسة مدني', 'هندسة عمارة', 'هندسة ديكور وتصميم داخلي'],
        'commercial' => ['إدارة اعمال', 'محاسبة', 'مصارف و تأمين']
    ];

    $insertedCount = 0;
    foreach ($programsToAdd as $deptKey => $programs) {
        if (!isset($deptMap[$deptKey])) {
            echo "لم يتم العثور على القسم الخاص بـ: $deptKey<br>";
            continue;
        }

        $deptId = $deptMap[$deptKey];

        foreach ($programs as $progName) {
            DB::table('programs')->insert([
                'name' => $progName,
                'department_id' => $deptId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $insertedCount++;
        }
    }

    echo "<b>تم إضافة $insertedCount دورة/اختصاص بنجاح إلى قاعدة البيانات!</b>";

} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
