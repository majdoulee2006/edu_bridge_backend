<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use Illuminate\Support\Facades\DB;

echo "⏳ جاري ربط مدرسي دورة الديكور بالمواد...\n";

// المدرسان المتخصصان في دورة الديكور بالقسم الهندسي:
// ID: 13 -> طارق زيدان
// ID: 14 -> ليلى عثمان

$courseTeacherMapping = [
    // مادة 1: الإضاءة والألوان 2 -> ليلى عثمان (معلم أساسي)
    'الاضاءة والالوان 2' => 14,
    'الاضاءة  والالوان'   => 14,

    // مادة 2: برامج التصميم والإظهار المعماري 3D -> طارق زيدان
    'برامج التصميم والإظهار المعماري 3D' => 13,

    // مادة 3: تاريخ العمارة والديكور -> ليلى عثمان
    'تاريخ العمارة والديكور' => 14,

    // مادة 4: نظريات التصميم الداخلي وتوزيع الفراغ -> طارق زيدان
    'نظريات التصميم الداخلي وتوزيع الفراغ' => 13,

    // مواد الفصل الثاني:
    'مشروع التخرج في التصميم الداخلي والديكور' => 13,
    'المواصفات وحساب الكميات وتكاليف التنفيذ' => 14,
    'خامات وتشطيبات الديكور الحديثة' => 14,
    'مواد الرسم2' => 13,
];

foreach ($courseTeacherMapping as $title => $teacherId) {
    $course = Course::where('title', 'LIKE', '%' . $title . '%')->first();
    if ($course) {
        DB::table('course_teachers')->updateOrInsert(
            ['course_id' => $course->course_id],
            [
                'teacher_id' => $teacherId,
                'role'       => 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $teacherName = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->where('teachers.teacher_id', $teacherId)
            ->value('users.full_name');
            
        echo "✅ مادة [{$course->title}] -> تم ربطها بالمدرس [{$teacherName}]\n";
    }
}

echo "\n🎉 تم ربط جميع مواد دورة الديكور بمدرسي القسم الهندسي بنجاح!\n";
