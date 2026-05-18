<?php
use Illuminate\Support\Facades\DB;

$courses = DB::table('courses')->pluck('course_id')->toArray();
$teachers = DB::table('teachers')->pluck('teacher_id')->toArray();

if (empty($courses) || empty($teachers)) {
    echo "No courses or teachers found. Please seed them first.\n";
    exit;
}

$days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];
$groups = ['معلوماتية 1', 'معلوماتية 2', 'معلوماتية 3', 'معلوماتية 4'];

foreach ($groups as $group) {
    foreach ($days as $day) {
        DB::table('schedules')->insert([
            'course_id' => $courses[0],
            'teacher_id' => $teachers[0],
            'day' => $day,
            'class_group' => $group,
            'start_time' => '08:00',
            'end_time' => '09:30',
            'room' => 'قاعة 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::table('schedules')->insert([
            'course_id' => $courses[min(1, count($courses)-1)],
            'teacher_id' => $teachers[0],
            'day' => $day,
            'class_group' => $group,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'room' => 'قاعة 2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

foreach ($groups as $group) {
    DB::table('exams')->insert([
        'course_id' => $courses[0],
        'exam_name' => 'امتحان نصفي',
        'exam_date' => '2026-06-15 09:00:00',
        'room' => 'المدرج الكبير',
        'max_score' => 100,
        'class_group' => $group,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

echo "Seeded " . (count($groups) * count($days) * 2) . " schedule records and " . count($groups) . " exam records.\n";
