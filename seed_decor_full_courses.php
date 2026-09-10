<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

echo "⏳ جاري إضافة وتحديث مواد دورة الديكور...\n";

// قائمة المواد للفصل الأول والفصل الثاني للسنة الثانية بدورة ديكور
$newCourses = [
    // الفصل الأول (نشط حالياً)
    [
        'title'       => 'تاريخ العمارة والديكور',
        'description' => 'دراسة الطرز المعمارية والتصميم الداخلي عبر العصور وتأثيرها على الديكور الحديث',
        'level'       => 'المستوى الثاني',
        'year'        => 2,
        'semester_id' => 1,
        'hours'       => 3,
    ],
    [
        'title'       => 'برامج التصميم والإظهار المعماري 3D',
        'description' => 'تطبيقات عملية على نمذجة الفراغات الداخلية باستخدام أحدث البرامج الهندسية',
        'level'       => 'المستوى الثاني',
        'year'        => 2,
        'semester_id' => 1,
        'hours'       => 4,
    ],
    [
        'title'       => 'نظريات التصميم الداخلي وتوزيع الفراغ',
        'description' => 'أسس توزيع الأثاث، دراسة الحركة، والمقاييس الإنسانية في التصميم الداخلي',
        'level'       => 'المستوى الثاني',
        'year'        => 2,
        'semester_id' => 1,
        'hours'       => 3,
    ],

    // الفصل الثاني (للسنة الثانية)
    [
        'title'       => 'مشروع التخرج في التصميم الداخلي والديكور',
        'description' => 'مشروع تطبيقي متكامل لتصميم وتنفيذ ديكور سكني أو تجاري متكامل',
        'level'       => 'المستوى الثاني',
        'year'        => 2,
        'semester_id' => 2,
        'hours'       => 5,
    ],
    [
        'title'       => 'المواصفات وحساب الكميات وتكاليف التنفيذ',
        'description' => 'طرق تسعير بنود الديكور ومواد الإكساء الداخلي وإدارة ورش التنفيذ',
        'level'       => 'المستوى الثاني',
        'year'        => 2,
        'semester_id' => 2,
        'hours'       => 3,
    ],
    [
        'title'       => 'خامات وتشطيبات الديكور الحديثة',
        'description' => 'دراسة أنواع الأخشاب، الرخام، الدهانات، والأقمشة المستخدمة في التشطيبات الفاخرة',
        'level'       => 'المستوى الثاني',
        'year'        => 2,
        'semester_id' => 2,
        'hours'       => 3,
    ],
];

// جلب طالبة روى
$student = Student::where('student_code', '202607')->first();

foreach ($newCourses as $cData) {
    // إنشاء المادة إذا لم تكن موجودة
    $course = Course::firstOrCreate(
        ['title' => $cData['title']],
        $cData
    );

    // ربط المادة بدورة الديكور (program_id = 8)
    DB::table('course_program')->insertOrIgnore([
        'course_id'  => $course->course_id,
        'program_id' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // تسجيل الطالبة روى في المادة
    if ($student) {
        DB::table('enrollments')->insertOrIgnore([
            'student_id'      => $student->student_id,
            'course_id'       => $course->course_id,
            'status'          => 'active',
            'enrollment_date' => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}

echo "✅ تم بنجاح إضافة مواد الفصل الأول والثاني لدورة الديكور وتسجيل الطالبة روى فيها!\n";
