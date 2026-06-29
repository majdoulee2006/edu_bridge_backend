<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$now = now();

// أضف enrollments للطلاب st_test1 و st_test2
foreach (['st_test1','st_test2'] as $uname) {
    $user = DB::table('users')->where('username',$uname)->first();
    if (!$user) { echo "User {$uname} not found\n"; continue; }
    $student = DB::table('students')->where('user_id',$user->user_id)->first();
    if (!$student) {
        // أضف students record
        $sid = DB::table('students')->insertGetId([
            'user_id'      => $user->user_id,
            'program_id'   => 1,
            'student_code' => $user->university_id,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        echo "Added student record for {$uname}: sid:{$sid}\n";
        $student = (object)['student_id'=>$sid];
    } else {
        echo "Student {$uname} already exists: sid:{$student->student_id}\n";
    }

    foreach ([1,6] as $courseId) {
        $exists = DB::table('enrollments')->where('student_id',$student->student_id)->where('course_id',$courseId)->exists();
        if (!$exists) {
            DB::table('enrollments')->insert([
                'student_id'      => $student->student_id,
                'course_id'       => $courseId,
                'enrollment_date' => now()->toDateString(),
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
            echo "  Enrolled in course {$courseId}\n";
        }
    }
}

// تحقق النتيجة
echo "\n=== Enrollments for Khaled's courses ===\n";
foreach ([1,6,47] as $cid) {
    $count = DB::table('enrollments')->where('course_id',$cid)->count();
    $course = DB::table('courses')->where('course_id',$cid)->value('title');
    echo "{$course} (cid:{$cid}): {$count} students\n";
}
