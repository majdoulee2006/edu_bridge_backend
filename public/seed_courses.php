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
    $data = [
        'معلوماتية' => [
            1 => ['خوارزميات', 'رياضيات حاسوبية'],
            2 => ['laravel', 'Flutter']
        ],
        'اتصالات' => [
            1 => ['c++', 'شبكات'],
            2 => ['اتصالات خليوية', 'مايكروية']
        ],
        'الكترون' => [
            1 => ['c++', 'شبكات'],
            2 => ['طاقة شمسية', 'معالجات']
        ],
        'ذكاء اصطناعي' => [
            1 => ['c#', 'شبكات'],
            2 => ['تصميم العاب', 'رؤية حاسوبية']
        ]
    ];

    $programs = DB::table('programs')->get()->keyBy('name');
    
    // Check and create a default semester if it doesn't exist
    $semester = DB::table('semesters')->where('semester_id', 1)->first();
    if (!$semester) {
        DB::table('semesters')->insert([
            'semester_id' => 1,
            'name' => 'الفصل الدراسي الأول',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $insertedCoursesCount = 0;
    $attachedCount = 0;

    foreach ($data as $progName => $years) {
        if (!isset($programs[$progName])) {
            echo "لم يتم العثور على الاختصاص: $progName<br>";
            continue;
        }

        $programId = $programs[$progName]->id;

        foreach ($years as $year => $courses) {
            foreach ($courses as $courseTitle) {
                // Check if course already exists to avoid duplication
                $course = DB::table('courses')->where('title', $courseTitle)->where('year', $year)->first();
                
                if (!$course) {
                    $courseId = DB::table('courses')->insertGetId([
                        'title' => $courseTitle,
                        'year' => (string)$year,
                        'semester_id' => 1, // Default to semester 1
                        'description' => "مقرر $courseTitle",
                        'level' => 'beginner', // Default level
                        'hours' => 3, // Default hours
                        'weight' => 3, // Default weight
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $insertedCoursesCount++;
                } else {
                    $courseId = $course->course_id;
                }

                // Attach to program
                $exists = DB::table('course_program')
                            ->where('course_id', $courseId)
                            ->where('program_id', $programId)
                            ->exists();

                if (!$exists) {
                    DB::table('course_program')->insert([
                        'course_id' => $courseId,
                        'program_id' => $programId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $attachedCount++;
                }
            }
        }
    }

    echo "تمت إضافة $insertedCoursesCount مقرر دراسي جديد بنجاح!\n";
    echo "تم ربط المقررات بـ $attachedCount اختصاص (دورة) بنجاح!";

} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
