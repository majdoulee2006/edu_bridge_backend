<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // 🌟 جلب أول طالب وأول درس من الداتابيز
        $student = User::where('role', 'student')->first();
        $lesson = Lesson::first();

        if (!$student || !$lesson) {
            $this->command->info('⚠️ يرجى التأكد من وجود طالب واحد ودرس واحد على الأقل في قاعدة البيانات.');
            return;
        }

        // 🌟 1. بيانات وهمية للحضور والغياب (Attendance)
        $attendances = [
            [
                'student_id' => $student->user_id,
                'lesson_id' => $lesson->lesson_id ?? $lesson->id, // حسب اسم الـ ID بجدول الدروس عندك
                'status' => 'present',
                'attendance_date' => Carbon::now()->subDays(1),
                'excuse_status' => 'none',
            ],
            [
                'student_id' => $student->user_id,
                'lesson_id' => $lesson->lesson_id ?? $lesson->id,
                'status' => 'absent',
                'attendance_date' => Carbon::now()->subDays(3),
                'excuse_status' => 'none', // غياب غير مبرر (بيطلعله زر تقديم عذر)
            ],
            [
                'student_id' => $student->user_id,
                'lesson_id' => $lesson->lesson_id ?? $lesson->id,
                'status' => 'absent',
                'attendance_date' => Carbon::now()->subDays(5),
                'excuse_text' => 'كنت أعاني من زكام شديد.',
                'excuse_status' => 'pending', // عذر قيد المراجعة
            ],
            [
                'student_id' => $student->user_id,
                'lesson_id' => $lesson->lesson_id ?? $lesson->id,
                'status' => 'absent',
                'attendance_date' => Carbon::now()->subDays(10),
                'excuse_text' => 'ظرف عائلي طارئ.',
                'excuse_status' => 'approved', // غياب بعذر (مقبول)
            ],
        ];

        foreach ($attendances as $att) {
            Attendance::create($att);
        }

        // 🌟 2. بيانات وهمية لطلبات الإجازة (Leave Requests)
        $leaveRequests = [
            [
                'student_id' => $student->user_id,
                'type' => 'full_day',
                'date' => Carbon::now()->addDays(2), // إجازة بعد يومين
                'reason' => 'عندي موعد في السفارة لتجديد الأوراق.',
                'status' => 'pending', // قيد المراجعة (متل الصورة اللي بعتيها)
            ],
            [
                'student_id' => $student->user_id,
                'type' => 'hourly',
                'date' => Carbon::now()->addDays(5),
                'reason' => 'موعد طبيب أسنان لمدة ساعتين.',
                'status' => 'approved', // إجازة مقبولة
            ],
        ];

        foreach ($leaveRequests as $leave) {
            LeaveRequest::create($leave);
        }

        $this->command->info('✅ تمت إضافة بيانات الحضور والإجازات بنجاح! 🚀');
    }
}