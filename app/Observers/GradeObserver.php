<?php

namespace App\Observers;

use App\Models\Grade;
use App\Services\TelegramBotHandler;
use Illuminate\Support\Facades\Cache;

class GradeObserver
{
    protected $telegramBot;

    public function __construct(TelegramBotHandler $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }

    public function created(Grade $grade)
    {
        $this->notifyStudent($grade);
        $this->clearCache($grade);
    }

    public function updated(Grade $grade)
    {
        if ($grade->wasChanged('score')) {
            $this->notifyStudent($grade, true);
            $this->clearCache($grade);
        }
    }

    protected function notifyStudent(Grade $grade, $isUpdate = false)
    {
        $grade->loadMissing(['student.user', 'exam.course']);
        
        $user = $grade->student->user ?? null;
        if ($user && $user->telegram_chat_id) {
            $courseName = $grade->exam->course->title ?? 'مادة غير محددة';
            $examName = $grade->exam->exam_name ?? 'امتحان';
            $score = $grade->score;
            $maxScore = $grade->exam->max_score ?? 100;
            
            $title = $isUpdate ? "🔄 تم تعديل علامتك!" : "🎉 نتيجة جديدة نزلت لك!";
            
            $message = "{$title}\n\n";
            $message .= "📘 **المادة:** {$courseName}\n";
            $message .= "📝 **الامتحان:** {$examName}\n";
            $message .= "🎯 **العلامة:** {$score} / {$maxScore}\n";
            
            if ($score >= ($maxScore / 2)) {
                $message .= "\nمبارك النجاح! 🌟";
            } else {
                $message .= "\nمعوضة إن شاء الله، شد حيلك! 💪";
            }

            $this->telegramBot->sendMessage($user->telegram_chat_id, $message);
        }
    }

    protected function clearCache(Grade $grade)
    {
        Cache::forget("telegram_grades_student_{$grade->student_id}");
    }
}
