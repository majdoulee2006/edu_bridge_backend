<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$parentUserId = 4; // Ahmad
$parentId = 1;
$studentIds = [4, 6]; // Omar and Ali
$courseIds = [1, 2, 3];

echo "Starting seeding for Ahmad's children...\n";

// 1. Ensure Exams exist
if (DB::table('exams')->count() == 0) {
    foreach ($courseIds as $cId) {
        DB::table('exams')->insert([
            ['course_id' => $cId, 'exam_name' => 'مذاكرة 1', 'max_score' => 100, 'exam_date' => now()->subDays(30)],
            ['course_id' => $cId, 'exam_name' => 'امتحان نصفي', 'max_score' => 100, 'exam_date' => now()->subDays(10)],
        ]);
    }
}
$exams = DB::table('exams')->get();

foreach ($studentIds as $sId) {
    echo "Seeding for student $sId...\n";
    
    // 2. Attendance (80-90% presence)
    $lessons = DB::table('lessons')->whereIn('course_id', $courseIds)->limit(10)->get();
    foreach ($lessons as $index => $lesson) {
        DB::table('attendance')->insertOrIgnore([
            'student_id' => $sId,
            'lesson_id' => $lesson->lesson_id,
            'status' => ($index % 5 == 0) ? 'absent' : 'present',
            'attendance_date' => now()->subDays(10 - $index),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // 3. Grades
    foreach ($exams as $exam) {
        DB::table('grades')->updateOrInsert(
            ['student_id' => $sId, 'exam_id' => $exam->exam_id],
            [
                'course_id' => $exam->course_id,
                'score' => rand(70, 95),
                'remarks' => 'أداء جيد جداً ومستقر',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    // 4. Assignments & Submissions
    foreach ($courseIds as $cId) {
        // Create 2 assignments per course
        $a1Id = DB::table('assignments')->insertGetId([
            'course_id' => $cId,
            'title' => 'وظيفة برمجية 1',
            'description' => 'حل التمارين المطلوبة في المحاضرة',
            'due_date' => now()->addDays(5),
            'max_points' => 10,
            'created_at' => now(),
        ]);
        
        $a2Id = DB::table('assignments')->insertGetId([
            'course_id' => $cId,
            'title' => 'مشروع بحثي',
            'description' => 'كتابة تقرير عن خوارزميات البحث',
            'due_date' => now()->subDays(2),
            'max_points' => 10,
            'created_at' => now(),
        ]);

        // One submitted, one missing
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $a2Id,
            'student_id' => $sId,
            'file_path' => 'homeworks/solution.pdf',
            'grade' => rand(8, 10),
            'submitted_at' => now()->subDays(3),
            'created_at' => now(),
        ]);
    }

    // 5. Absence Requests
    DB::table('absence_requests')->insert([
        [
            'student_id' => $sId,
            'reason' => 'ظرف عائلي طارئ',
            'date' => now()->addDays(1),
            'status' => 'pending',
            'created_at' => now(),
        ],
        [
            'student_id' => $sId,
            'reason' => 'موعد طبيب أسنان',
            'date' => now()->subDays(5),
            'status' => 'approved',
            'created_at' => now(),
        ]
    ]);
}

// 6. Notifications for Parent (Ahmad)
DB::table('notifications')->insert([
    [
        'user_id' => $parentUserId,
        'title' => 'تنبيه غياب 🔴',
        'message' => 'نحيطكم علماً بأن الطالب عمر قد غاب عن حصة البرمجيات اليوم.',
        'type' => 'attendance',
        'is_read' => 0,
        'created_at' => now()->subHours(2),
    ],
    [
        'user_id' => $parentUserId,
        'title' => 'علامة جديدة 🎓',
        'message' => 'تم رصد علامة الطالب علي في امتحان قواعد البيانات: 92/100',
        'type' => 'grade',
        'is_read' => 1,
        'created_at' => now()->subDays(1),
    ]
]);

echo "Seeding completed successfully!\n";
