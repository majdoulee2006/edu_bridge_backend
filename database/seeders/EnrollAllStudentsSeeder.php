<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollAllStudentsSeeder extends Seeder
{
    public function run()
    {
        $students = DB::table('students')->pluck('student_id');
        $courses  = DB::table('courses')->pluck('course_id');

        foreach ($students as $studentId) {
            foreach ($courses as $courseId) {
                DB::table('enrollments')->updateOrInsert(
                    ['student_id' => $studentId, 'course_id' => $courseId],
                    ['enrollment_date' => now(), 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $this->command->info('✅ تم تسجيل جميع الطلاب في جميع الكورسات');
    }
}
