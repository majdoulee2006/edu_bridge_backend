<?php

namespace App\Services\TelegramBot;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Parents;
use App\Models\Schedule;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Exam;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\AbsenceRequest;
use App\Models\StudentRequest;
use App\Models\Program;
use App\Services\FcmService;
use App\Support\LoginThrottleGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Telegram bot: parent services
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
trait ParentHandlers
{
    // ==========================================
    // Parent Services Handlers
    // ==========================================

    private function sendParentMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '👨‍👦 أبنائي'], ['text' => '💯 علامات أبنائي']],
                [['text' => '🛑 غيابات أبنائي']],
                [['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        $servicesList = "📋 **الخدمات المتاحة لك كولي أمر عبر البوت:**\n"
            . "• 👨‍👦 **أبنائي**: استعراض قائمة أبنائك المسجلين بالمعهد\n"
            . "• 💯 **علامات أبنائي**: استعراض درجات أبنائك وامتحاناتهم\n"
            . "• 🛑 **غيابات أبنائي**: متابعة نسبة حضور أبنائك والإنذارات\n"
            . "• 🚪 **تسجيل خروج**: فك ربط الحساب من هذا الجهاز\n\n"
            . "👇 اختر الخدمة المطلوبة من الأزرار أدناه:";

        $fullText = $headerText ? "{$headerText}\n\n{$servicesList}" : $servicesList;

        $this->sendMessage($chatId, $fullText, $keyboard);
    }

    private function getParentChildren(User $user)
    {
        $children = collect();
        if ($user->parent) {
            $children = $user->parent->students()->with('user')->get();
        }

        if ($children->isEmpty()) {
            $parentRecord = DB::table('parents')->where('user_id', $user->user_id)->first();
            $parentIds = array_filter([$user->user_id, $parentRecord?->parent_id ?? null]);

            $studentUserIds = DB::table('parent_students')
                ->whereIn('parent_id', $parentIds)
                ->pluck('student_id');

            if ($studentUserIds->isNotEmpty()) {
                $children = Student::where(function($q) use ($studentUserIds) {
                    $q->whereIn('user_id', $studentUserIds)
                      ->orWhereIn('student_id', $studentUserIds);
                })->with('user')->get();
            }
        }

        return $children;
    }


    private function handleParentChildren(User $user, $chatId)
    {
        $children = $this->getParentChildren($user);
        
        if ($children->isEmpty()) {
            $this->sendMessage($chatId, "لا يوجد أبناء مسجلين بحسابك حالياً.");
            return;
        }

        $message = "👨‍👦 **قائمة أبنائك:**\n\n";
        foreach ($children as $child) {
            $childUser = $child->user;
            $message .= "🔹 **{$childUser->full_name}**\n";
            $message .= "التخصص: {$childUser->branch}\n";
            $message .= "السنة: {$childUser->academic_year}\n";
            $message .= "─────────────\n";
        }

        $this->sendMessage($chatId, $message);
    }

    private function handleParentGrades(User $user, $chatId)
    {
        $children = $this->getParentChildren($user);
        
        if ($children->isEmpty()) {
            $this->sendMessage($chatId, "لا يوجد أبناء مسجلين بحسابك حالياً.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($children as $child) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "💯 علامات " . ($child->user->first_name ?? $child->user->full_name ?? 'الابن'), 'callback_data' => "parent_student_grades_{$child->student_id}"]
            ];
        }

        $this->sendMessage($chatId, "يرجى اختيار الابن لعرض علاماته:", null, $keyboard);
    }

    private function handleParentAttendance(User $user, $chatId)
    {
        $children = $this->getParentChildren($user);
        
        if ($children->isEmpty()) {
            $this->sendMessage($chatId, "لا يوجد أبناء مسجلين بحسابك حالياً.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($children as $child) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🛑 غيابات " . ($child->user->first_name ?? $child->user->full_name ?? 'الابن'), 'callback_data' => "parent_student_attendance_{$child->student_id}"]
            ];
        }

        $this->sendMessage($chatId, "يرجى اختيار الابن لعرض تقرير دوامه:", null, $keyboard);
    }

    private function processParentStudentGrades(User $parentUser, $chatId, $studentId)
    {
        $student = Student::with('user')->find($studentId);
        if (!$student || !$student->user) return;
        
        $this->sendMessage($chatId, "👨‍👦 **تقرير علامات: {$student->user->full_name}**");
        $this->handleGrades($student->user, $chatId);
    }

    private function processParentStudentAttendance(User $parentUser, $chatId, $studentId)
    {
        $student = Student::with('user')->find($studentId);
        if (!$student || !$student->user) return;
        
        $this->sendMessage($chatId, "👨‍👦 **تقرير دوام: {$student->user->full_name}**");
        $this->handleAttendance($student->user, $chatId);
    }
}
