<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "Restoring test data to the new database...\n";

// 1. Ensure Ali Hassan exists (Student 2)
$aliUser = DB::table('users')->where('username', 'ali2024')->first();
if (!$aliUser) {
    $aliUserId = DB::table('users')->insertGetId([
        'role_id' => 3,
        'full_name' => 'علي حسن',
        'username' => 'ali2024',
        'password' => Hash::make('password'),
        'email' => 'ali@edu.com',
        'academic_year' => 'السنة الثانية',
        'created_at' => now(),
    ]);
} else {
    $aliUserId = $aliUser->user_id;
}

$aliStudent = DB::table('students')->where('user_id', $aliUserId)->first();
if (!$aliStudent) {
    $aliStudentId = DB::table('students')->insertGetId([
        'user_id' => $aliUserId,
        'student_code' => '2024005',
        'level' => 'السنة الثانية',
        'created_at' => now(),
    ]);
} else {
    $aliStudentId = $aliStudent->student_id;
}

// 2. Link Omar (1) and Ali to Abu Omar (Parent 1)
$studentIds = [1, $aliStudentId];
foreach ($studentIds as $sId) {
    DB::table('parent_students')->updateOrInsert(
        ['parent_id' => 1, 'student_id' => $sId]
    );

    // Enroll in courses
    foreach ([1, 2] as $cId) {
        DB::table('enrollments')->updateOrInsert(
            ['student_id' => $sId, 'course_id' => $cId],
            ['enrollment_date' => now(), 'status' => 'active']
        );
    }
}

// 3. Ensure Exams exist for courses 1 and 2
foreach ([1, 2] as $cId) {
    if (DB::table('exams')->where('course_id', $cId)->count() == 0) {
        DB::table('exams')->insert([
            ['course_id' => $cId, 'exam_name' => 'مذاكرة 1', 'max_score' => 100, 'exam_date' => now()->subDays(10)],
            ['course_id' => $cId, 'exam_name' => 'امتحان نصفي', 'max_score' => 100, 'exam_date' => now()->subDays(5)],
        ]);
    }
}
$exams = DB::table('exams')->get();

// 4. Assignments & Submissions
foreach ($studentIds as $sId) {
    foreach ([1, 2] as $cId) {
        $aId = DB::table('assignments')->insertGetId([
            'course_id' => $cId,
            'title' => 'واجب برمجي ' . $cId,
            'description' => 'وصف الواجب',
            'due_date' => now()->addDays(3),
            'max_points' => 100,
            'created_at' => now(),
        ]);
        
        if ($sId == 1) { // Submit for one student only to see variety
             DB::table('assignment_submissions')->insert([
                'assignment_id' => $aId,
                'student_id' => $sId,
                'file_path' => 'solutions/sol.pdf',
                'grade' => rand(80, 100),
                'submitted_at' => now(),
                'created_at' => now(),
            ]);
        }
    }
}

// 5. Lessons for attendance
foreach ([1, 2] as $cId) {
    if (DB::table('lessons')->where('course_id', $cId)->count() == 0) {
        DB::table('lessons')->insert([
            ['course_id' => $cId, 'title' => 'المحاضرة الأولى', 'lesson_date' => now()->subDays(15)],
            ['course_id' => $cId, 'title' => 'المحاضرة الثانية', 'lesson_date' => now()->subDays(14)],
        ]);
    }
}
$lessons = DB::table('lessons')->get();

// 6. Seed Grades, Attendance, Reports
foreach ($studentIds as $sId) {
    // Grades
    foreach ($exams as $exam) {
        DB::table('grades')->updateOrInsert(
            ['student_id' => $sId, 'exam_id' => $exam->exam_id],
            [
                'score' => rand(75, 98),
                'remarks' => 'أداء متميز جداً',
                'created_at' => now(),
            ]
        );
    }

    // Attendance
    foreach ($lessons as $lesson) {
        DB::table('attendance')->insertOrIgnore([
            'student_id' => $sId,
            'lesson_id' => $lesson->lesson_id,
            'status' => 'present',
            'attendance_date' => $lesson->lesson_date ?? now()->subDays(1),
            'created_at' => now(),
        ]);
    }

    // Performance Reports
    DB::table('performance_reports')->updateOrInsert(
        ['student_id' => $sId],
        [
            'attendance_rate' => 95.0,
            'average_grade' => 88.5,
            'recommendations' => 'يُنصح بالاستمرار على هذا النهج الدراسي.',
            'generated_at' => now(),
            'created_at' => now(),
        ]
    );
}

// 7. Notifications for Parent (User 4)
DB::table('notifications')->insert([
    [
        'user_id' => 4,
        'title' => 'علامة جديدة 🎓',
        'message' => 'تم إضافة علامة جديدة لابنك في مادة البرمجيات.',
        'type' => 'grade',
        'is_read' => 0,
        'created_at' => now(),
    ]
]);

echo "Data restored successfully for Parent: Abu Omar (ID: 1) and his children!\n";
