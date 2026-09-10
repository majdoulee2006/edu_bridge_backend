<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$sqlFilePath = __DIR__ . '/app/Http/Controllers/edu_bridge_backend (11).sql';

if (!file_exists($sqlFilePath)) {
    die("❌ ملف SQL غير موجود في: $sqlFilePath\n");
}

echo "⏳ جاري قراءة ملف SQL واستخراج البيانات...\n";
$content = file_get_contents($sqlFilePath);

// تعطيل القيود مؤقتاً لتجنب مشاكل المفاتيح الأجنبية أثناء الاستيراد
DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// دالة لاستخراج وتنفيذ جمل INSERT الخاصة بجدول معين
function importTableData($tableName, $content) {
    // نبحث عن INSERT INTO `tableName`
    $pattern = '/INSERT INTO `' . preg_quote($tableName, '/') . '`[^\n]*\s*VALUES\s*(.+?);(?=\s*(--|\/\*|CREATE|DROP|INSERT|ALTER|COMMIT|\n\n|$))/s';
    
    if (preg_match($pattern, $content, $matches)) {
        $insertQuery = "INSERT IGNORE INTO `$tableName` " . substr($matches[0], strlen("INSERT INTO `$tableName` "));
        // تأمين استخدام INSERT IGNORE لعدم الاصطدام مع السجلات الموجودة
        $insertQuery = preg_replace('/^INSERT INTO/i', 'INSERT IGNORE INTO', $insertQuery);
        try {
            DB::unprepared($insertQuery);
            echo "✅ تم استيراد بيانات جدول: `$tableName` بنجاح.\n";
        } catch (\Exception $e) {
            echo "⚠️ تنبيه عند استيراد جدول `$tableName`: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️ لم يتم العثور على بيانات إدخال لجدول: `$tableName` أو الجدول فارغ.\n";
    }
}

// قائمة الجداول بالترتيب المنطقي للبيانات
$tablesToImport = [
    'roles',
    'departments',
    'programs',
    'semesters',
    'users',
    'students',
    'parents',
    'parent_students',
    'teachers',
    'heads',
    'courses',
    'course_teachers',
    'course_program',
    'schedules',
    'enrollments',
    'lessons',
    'attendance_sessions',
    'attendance',
    'assignments',
    'assignment_submissions',
    'exams',
    'grades',
    'announcements',
    'calendar_events',
    'messages',
    'notifications',
    'user_activities',
];

foreach ($tablesToImport as $tbl) {
    importTableData($tbl, $content);
}

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "\n🎉 تم الانتهاء من استيراد ودمج كافة البيانات المفقودة بنجاح!\n";
