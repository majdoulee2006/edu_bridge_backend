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
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    $departments = DB::table('departments')->get();
    
    // الخطوة الأولى: تغيير كل الـ IDs الحالية لأرقام عالية (1000 وما فوق) لتجنب التعارض
    foreach ($departments as $dept) {
        $oldId = $dept->department_id;
        if ($oldId < 1000) {
            $tempId = $oldId + 1000;
            DB::table('departments')->where('department_id', $oldId)->update(['department_id' => $tempId]);
            DB::table('programs')->where('department_id', $oldId)->update(['department_id' => $tempId]);
            DB::table('heads')->where('department_id', $oldId)->update(['department_id' => $tempId]);
        }
    }

    // الخطوة الثانية: تعيين الـ IDs 1, 2, 3, 4 بالضبط للأقسام المطلوبة
    $targetMapping = [
        1 => 'نظم معلومات و الكمبيوتر',
        2 => 'هندسي',
        3 => 'تجاري',
        4 => 'طبي'
    ];

    foreach ($targetMapping as $newId => $deptName) {
        $dept = DB::table('departments')->where('name', $deptName)->first();
        
        if ($dept) {
            $currentId = $dept->department_id;
            
            DB::table('departments')->where('department_id', $currentId)->update(['department_id' => $newId]);
            DB::table('programs')->where('department_id', $currentId)->update(['department_id' => $newId]);
            DB::table('heads')->where('department_id', $currentId)->update(['department_id' => $newId]);
            
            echo "تم تغيير معرف القسم ($deptName) بنجاح إلى $newId مع التحديثات المرتبطة.<br>";
        } else {
            echo "القسم ($deptName) غير موجود.<br>";
        }
    }

    // إعادة تفعيل التحقق
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
    echo "<b>تم تعديل المعرفات (ID) بنجاح لتصبح 1, 2, 3, 4!</b>";

} catch (Exception $e) {
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "حدث خطأ: " . $e->getMessage();
}
