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
    // إيقاف فحص المفاتيح الأجنبية لتجنب أخطاء الحذف المرتبط
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // قائمة الجداول المطلوب تصفيرها
    $tablesToTruncate = [
        'departments',
        'programs',
        'courses',
        'course_program',
        'course_teachers',
        'semesters'
    ];

    foreach ($tablesToTruncate as $table) {
        DB::table($table)->truncate();
        echo "تم مسح بيانات الجدول وتصفير العداد: $table<br>";
    }

    // إعادة تشغيل فحص المفاتيح الأجنبية
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    echo "<br><b style='color:green'>تم مسح جميع البيانات من الجداول المحددة بنجاح!</b>";

} catch (Exception $e) {
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "<b style='color:red'>حدث خطأ:</b> " . $e->getMessage();
}
