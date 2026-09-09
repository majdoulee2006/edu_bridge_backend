<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

echo "⏳ جاري إضافة محاضرات واقعية وتفصيلية لمواد دورة الديكور...\n";

// قائمة المحاضرات التخصصية لمواد الفصل الأول من دورة الديكور
$decorLessons = [
    'الاضاءة والالوان 2' => [
        [
            'title'       => 'المحاضرة 1: دراسة تباين الألوان وتأثير الدرجات الحارة والباردة في الفراغات السكنية',
            'type'        => 'pdf',
            'description' => 'ملف تفصيلي يشرح نظرية الألوان الحديثة وكيفية اختيار لوحة الألوان المناسبة للصالات وغرف النوم مع المخططات التوضيحية.',
            'content_url' => 'http://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'file_size'   => '3.2 MB',
            'duration'    => '45 دقيقة',
        ],
        [
            'title'       => 'المحاضرة 2: تقنيات توزيع الإضاءة المخفية (LED Profiles) والسبوتات في الأسقف المستعارة',
            'type'        => 'video',
            'description' => 'فيديو توضيحي يشرح حساب شدة الإضاءة (Lux) وتنسيق الإضاءة المباشرة والغير مباشرة مع الجبس بورد.',
            'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            'file_size'   => '18.5 MB',
            'duration'    => '60 دقيقة',
        ],
        [
            'title'       => 'المحاضرة 3: تطبيقات عملية في هندسة الإضاءة المعمارية للمحلات التجارية والمطاعم',
            'type'        => 'pdf',
            'description' => 'دراسة حالات واقعية لكيفية إبراز المنتجات التجارية باستخدام الإضاءة الموجهة (Track Lights).',
            'content_url' => 'http://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'file_size'   => '4.1 MB',
            'duration'    => '50 دقيقة',
        ],
    ],

    'تاريخ العمارة والديكور' => [
        [
            'title'       => 'المحاضرة 1: الطراز الكلاسيكي والإغريقي في التصميم الداخلي',
            'type'        => 'pdf',
            'description' => 'شرح العناصر المعمارية الكلاسيكية، الأعمدة، الكورنيش، والزخارف الجدارية الفاخرة.',
            'content_url' => 'http://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'file_size'   => '5.0 MB',
            'duration'    => '40 دقيقة',
        ],
        [
            'title'       => 'المحاضرة 2: العمارة الإسلامية وزخارف الأربيسك في الديكور الشرقي',
            'type'        => 'video',
            'description' => 'فيديو تعليمي يوضح استخدام المقرنصات والزجاج التعشيق والأخشب المخرطة في الفراغات المعاصرة.',
            'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            'file_size'   => '22.0 MB',
            'duration'    => '55 دقيقة',
        ],
    ],

    'برامج التصميم والإظهار المعماري 3D' => [
        [
            'title'       => 'المحاضرة 1: أساسيات النمذجة ثلاثية الأبعاد (3D Modeling) للفراغات المعمارية',
            'type'        => 'pdf',
            'description' => 'خطوات بناء الجدران، الأبواب، والنوافذ وإعداد الكاميرات الداخلية بالتفصيل.',
            'content_url' => 'http://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'file_size'   => '6.4 MB',
            'duration'    => '65 دقيقة',
        ],
        [
            'title'       => 'المحاضرة 2: إضافة الخامات الواقعية (Materials & Textures) وإعداد الرندر النهائي',
            'type'        => 'video',
            'description' => 'فيديو تطبيقي لضبط انعكاسات الخشب، الرخام، والزجاج للحصول على صور رندر واقعية Photorealistic.',
            'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            'file_size'   => '35.0 MB',
            'duration'    => '75 دقيقة',
        ],
    ],

    'نظريات التصميم الداخلي وتوزيع الفراغ' => [
        [
            'title'       => 'المحاضرة 1: أسس توزيع الأثاث ودراسة مسارات الحركة في المعيشة والاستقبال',
            'type'        => 'pdf',
            'description' => 'دليل شامل للمقاييس الإنسانية Ergonomics وتحديد المساحات الأدنى للممرات والأثاث.',
            'content_url' => 'http://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'file_size'   => '2.8 MB',
            'duration'    => '45 دقيقة',
        ],
    ],
];

$count = 0;
foreach ($decorLessons as $courseTitle => $lessons) {
    $course = Course::where('title', 'LIKE', '%' . $courseTitle . '%')->first();
    if (!$course) continue;

    // جلب المدرس المرتبط بالمادة أو معلم من القسم الهندسي
    $teacherId = DB::table('course_teachers')->where('course_id', $course->course_id)->value('teacher_id') ?? 14;

    foreach ($lessons as $lessonData) {
        Lesson::create([
            'course_id'     => $course->course_id,
            'teacher_id'    => $teacherId,
            'department_id' => 4, // القسم الهندسي
            'title'         => $lessonData['title'],
            'type'          => $lessonData['type'],
            'description'   => $lessonData['description'],
            'content_url'   => $lessonData['content_url'],
            'file_size'     => $lessonData['file_size'],
            'duration'      => $lessonData['duration'],
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $count++;
    }
}

echo "✅ تم بنجاح إضافة {$count} محاضرات واقعية لمواد دورة الديكور!\n";
