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
    DB::beginTransaction();

    $targetDepartments = [
        'نظم معلومات و الكمبيوتر',
        'هندسي',
        'تجاري',
        'طبي'
    ];

    $deptIds = [];
    foreach ($targetDepartments as $deptName) {
        $dept = DB::table('departments')->where('name', $deptName)->first();
        if (!$dept) {
            $deptIds[$deptName] = DB::table('departments')->insertGetId([
                'name' => $deptName,
                'description' => 'قسم ' . $deptName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "تم إنشاء القسم: $deptName<br>";
        } else {
            $deptIds[$deptName] = $dept->department_id;
            echo "القسم موجود مسبقاً: $deptName<br>";
        }
    }

    $programs = DB::table('programs')->get();
    foreach ($programs as $prog) {
        $newName = $prog->name;
        $assignTo = 'نظم معلومات و الكمبيوتر';

        if (in_array($newName, ['ديكور', 'مدني', 'اعلان'])) {
            $assignTo = 'هندسي';
        } elseif (in_array($newName, ['محاسبة', 'مصارف', 'تجارة الكترونية', 'ادارة اعمال'])) {
            $assignTo = 'تجاري';
        } elseif (in_array($newName, ['صيدلة', 'مخابر', 'تمريض'])) {
            $assignTo = 'طبي';
        }

        DB::table('programs')
            ->where('id', $prog->id)
            ->update(['department_id' => $deptIds[$assignTo]]);
    }
    echo "تم نقل جميع الاختصاصات إلى الأقسام الجديدة بنجاح.<br>";

    $deletedCount = DB::table('departments')
        ->whereNotIn('department_id', array_values($deptIds))
        ->delete();
    
    echo "تم حذف $deletedCount قسم قديم بنجاح.<br>";

    DB::commit();
    echo "<b>تمت العملية بالكامل بنجاح!</b>";

} catch (Exception $e) {
    DB::rollBack();
    echo "حدث خطأ: " . $e->getMessage();
}
