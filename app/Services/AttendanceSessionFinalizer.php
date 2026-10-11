<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSession;

class AttendanceSessionFinalizer
{
    /**
     * إغلاق الجلسة نهائياً وتسجيل الغياب لمن لم يسجل حضوره بعد انتهاء مهلة المزامنة.
     */
    public static function finalize(AttendanceSession $session): int
    {
        if ($session->finalized_at !== null) {
            return 0;
        }

        $lesson = $session->lesson;
        if (!$lesson || !$lesson->course) {
            $session->update([
                'is_active'    => false,
                'finalized_at' => now(),
            ]);
            return 0;
        }

        $course = $lesson->course;
        $allStudents = $course->students()->get();

        $sessionDate = $session->created_at ? $session->created_at->toDateString() : now()->toDateString();
        $recordedIds = Attendance::where('lesson_id', $lesson->lesson_id)
            ->whereDate('attendance_date', $sessionDate)
            ->pluck('student_id')
            ->toArray();

        $absentCount = 0;

        foreach ($allStudents as $student) {
            if (in_array($student->student_id, $recordedIds, true)) {
                continue;
            }

            // فحص الإجازة المعتمدة لهذا اليوم
            $excused = \App\Support\Access::studentHasApprovedLeave($student->student_id, $student->user_id, $sessionDate);

            Attendance::create([
                'student_id'      => $student->student_id,
                'lesson_id'       => $lesson->lesson_id,
                'session_id'      => $session->id,
                'attendance_date' => $sessionDate,
                'status'          => 'absent',
                'excuse_status'   => $excused ? 'approved' : 'none',
                'source'          => 'online',
            ]);

            $absentCount++;
        }

        $session->update([
            'is_active'    => false,
            'closed_at'    => $session->closed_at ?? now(),
            'finalized_at' => now(),
        ]);

        // تحليل الأنماط المشبوهة للجلسة بعد اكتمال جميع السجلات
        \App\Jobs\AnalyzeAttendanceSessionJob::dispatch($session->id);

        return $absentCount;
    }
}
