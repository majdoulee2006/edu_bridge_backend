<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $courses = DB::table('courses')->get();

        if ($courses->isEmpty()) {
            $this->command->info('⚠️ لا توجد مواد دراسية في قاعدة البيانات.');
            return;
        }

        foreach ($courses as $course) {
            DB::table('exams')->updateOrInsert(
                ['course_id' => $course->course_id, 'exam_name' => 'الامتحان النصفي'],
                [
                    'exam_date'  => Carbon::now()->addDays(10)->setTime(9, 0),
                    'max_score'  => 40,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            DB::table('exams')->updateOrInsert(
                ['course_id' => $course->course_id, 'exam_name' => 'الامتحان النهائي'],
                [
                    'exam_date'  => Carbon::now()->addDays(25)->setTime(10, 0),
                    'max_score'  => 60,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ تمت إضافة بيانات الامتحانات بنجاح');
    }
}
