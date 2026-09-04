<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Services\AbsenceWarningService;
use App\Services\TelegramBotHandler;
use Illuminate\Support\Facades\Cache;

class AttendanceObserver
{
    protected $telegramBot;
    protected $warningService;

    public function __construct(TelegramBotHandler $telegramBot, AbsenceWarningService $warningService)
    {
        $this->telegramBot    = $telegramBot;
        $this->warningService = $warningService;
    }

    public function created(Attendance $attendance)
    {
        if ($attendance->status === 'absent') {
            $this->notifyStudent($attendance);
            $this->warningService->checkAndWarn($attendance->student_id);
        }
        $this->clearCache($attendance);
    }

    public function updated(Attendance $attendance)
    {
        if ($attendance->wasChanged('status') && $attendance->status === 'absent') {
            $this->notifyStudent($attendance);
            $this->warningService->checkAndWarn($attendance->student_id);
        }
        $this->clearCache($attendance);
    }

    protected function notifyStudent(Attendance $attendance)
    {
        $attendance->loadMissing(['student.user', 'lesson.course']);
        
        $user = $attendance->student->user ?? null;
        if ($user && $user->telegram_chat_id) {
            $courseName = $attendance->lesson->course->title ?? 'مادة غير محددة';
            $lessonTitle = $attendance->lesson->title ?? 'جلسة';
            $date = \Carbon\Carbon::parse($attendance->attendance_date)->translatedFormat('d F Y');
            
            $message = "⚠️ **تنبيه غياب!**\n\n";
            $message .= "تم تسجيل غياب لك في:\n";
            $message .= "📘 **المادة:** {$courseName}\n";
            $message .= "📝 **المحاضرة:** {$lessonTitle}\n";
            $message .= "📅 **التاريخ:** {$date}\n\n";
            $message .= "إذا كان لديك عذر، يرجى تقديمه للقسم الإداري في أسرع وقت.";

            $this->telegramBot->sendMessage($user->telegram_chat_id, $message);
        }
    }

    protected function clearCache(Attendance $attendance)
    {
        Cache::forget("telegram_attendance_student_{$attendance->student_id}");
    }
}
