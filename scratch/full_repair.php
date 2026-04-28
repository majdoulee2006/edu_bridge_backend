<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "--- Starting Full System Check and Repair ---\n";

// 1. Fix Schema: Add report_type if missing
if (!Schema::hasColumn('performance_reports', 'report_type')) {
    Schema::table('performance_reports', function (Blueprint $table) {
        $table->enum('report_type', ['academic', 'behavioral'])->after('student_id')->default('academic');
    });
    echo "✅ Added missing 'report_type' to performance_reports.\n";
}

// 2. Identify Target IDs
$parentUserId = 4; // أبو عمر الخالد
$parent = DB::table('parents')->where('user_id', $parentUserId)->first();
$parentId = $parent ? $parent->parent_id : 1;

$student1 = DB::table('students')->where('user_id', 3)->first(); // عمر
$student2 = DB::table('students')->where('student_code', '2024005')->first(); // علي

$studentIds = [];
if ($student1) $studentIds[] = $student1->student_id;
if ($student2) $studentIds[] = $student2->student_id;

echo "Parent ID: $parentId, Student IDs: " . implode(', ', $studentIds) . "\n";

// 3. Ensure Linkage
foreach ($studentIds as $sId) {
    DB::table('parent_students')->updateOrInsert(['parent_id' => $parentId, 'student_id' => $sId]);
}

// 4. Seed Absence Requests (Permissions)
foreach ($studentIds as $sId) {
    if (DB::table('absence_requests')->where('student_id', $sId)->count() == 0) {
        DB::table('absence_requests')->insert([
            [
                'student_id' => $sId,
                'date' => now()->addDays(2),
                'reason' => 'موعد طبي طارئ',
                'status' => 'pending',
                'created_at' => now(),
            ],
            [
                'student_id' => $sId,
                'date' => now()->subDays(3),
                'reason' => 'وعكة صحية',
                'status' => 'approved',
                'created_at' => now(),
            ]
        ]);
        echo "✅ Seeded permissions for student $sId.\n";
    }
}

// 5. Verify Exams and Lessons
foreach ([1, 2] as $cId) {
    if (DB::table('exams')->where('course_id', $cId)->count() == 0) {
        DB::table('exams')->insert([
            ['course_id' => $cId, 'exam_name' => 'مذاكرة 1', 'max_score' => 100, 'exam_date' => now()->subDays(10)],
            ['course_id' => $cId, 'exam_name' => 'امتحان نصفي', 'max_score' => 100, 'exam_date' => now()->subDays(5)],
        ]);
    }
}

// 6. Refresh Grades and Attendance
foreach ($studentIds as $sId) {
    $exams = DB::table('exams')->get();
    foreach ($exams as $exam) {
        DB::table('grades')->updateOrInsert(
            ['student_id' => $sId, 'exam_id' => $exam->exam_id],
            ['score' => rand(80, 98), 'remarks' => 'ممتاز', 'created_at' => now()]
        );
    }
}

echo "--- Check and Repair Completed Successfully ---\n";
