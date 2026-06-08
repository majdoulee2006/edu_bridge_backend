<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Lesson;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $student = Student::first();
        $lesson = Lesson::first();

        if (!$student || !$lesson) {
            $this->command->info('⚠️ يرجى التأكد من وجود طالب ودرس في قاعدة البيانات.');
            return;
        }

        $records = [
            ['status' => 'present', 'days' => 1,  'excuse_status' => 'none'],
            ['status' => 'present', 'days' => 2,  'excuse_status' => 'none'],
            ['status' => 'absent',  'days' => 5,  'excuse_status' => 'none'],
            ['status' => 'present', 'days' => 7,  'excuse_status' => 'none'],
            ['status' => 'absent',  'days' => 9,  'excuse_status' => 'pending',  'excuse_text' => 'كنت مريضاً بالزكام.'],
            ['status' => 'present', 'days' => 12, 'excuse_status' => 'none'],
            ['status' => 'absent',  'days' => 14, 'excuse_status' => 'approved', 'excuse_text' => 'ظرف عائلي طارئ.'],
            ['status' => 'present', 'days' => 16, 'excuse_status' => 'none'],
        ];

        foreach ($records as $r) {
            Attendance::firstOrCreate(
                [
                    'student_id'      => $student->student_id,
                    'lesson_id'       => $lesson->lesson_id,
                    'attendance_date' => Carbon::now()->subDays($r['days'])->toDateString(),
                ],
                [
                    'status'          => $r['status'],
                    'excuse_status'   => $r['excuse_status'],
                    'excuse_text'     => $r['excuse_text'] ?? null,
                ]
            );
        }

        $this->command->info('✅ تمت إضافة بيانات الحضور بنجاح');
    }
}
