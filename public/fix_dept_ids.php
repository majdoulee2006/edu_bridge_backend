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

    // الأقسام والأيديات المطلوبة
    $targetMapping = [
        1 => 'نظم معلومات و الكمبيوتر',
        2 => 'هندسي',
        3 => 'تجاري',
        4 => 'طبي'
    ];

    foreach ($targetMapping as $newId => $deptName) {
        $dept = DB::table('departments')->where('name', $deptName)->first();
        
        if ($dept && $dept->department_id != $newId) {
            $oldId = $dept->department_id;
            
            // تحديث جدول departments
            DB::table('departments')
                ->where('department_id', $oldId)
                ->update(['department_id' => $newId]);
                
            // تحديث الجداول المرتبطة
            DB::table('programs')
                ->where('department_id', $oldId)
                ->update(['department_id' => $newId]);
                
            DB::table('heads')
                ->where('department_id', $oldId)
                ->update(['department_id' => $newId]);
                
            // محاولة تحديث course_departments إن وجد
            try {
                DB::table('course_departments')
                    ->where('department_id', $oldId)
                    ->update(['department_id' => $newId]);
            } catch (Exception $e) {
                // الجدول غير موجود، لا مشكلة
            }
            
            echo "تم تحديث معرف القسم ($deptName) من $oldId إلى $newId مع كافة الارتباطات بنجاح.<br>";
        } else if ($dept && $dept->department_id == $newId) {
            echo "القسم ($deptName) يملك المعرف $newId مسبقاً.<br>";
        } else {
            echo "القسم ($deptName) غير موجود.<br>";
        }
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "<b>تم تعديل المعرفات (ID) لتصبح 1, 2, 3, 4 بنجاح!</b>";

} catch (Exception $e) {
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "حدث خطأ: " . $e->getMessage();
}
