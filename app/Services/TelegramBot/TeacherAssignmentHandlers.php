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
 * Telegram bot: teacher services (attendance, assignments, grading, lecture upload)
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
/**
 * Telegram bot: teacher assignment creation and grading
 * (moved as-is from TeacherHandlers; methods keep their names and behaviour)
 */
trait TeacherAssignmentHandlers
{
    // ==========================================
    // Teacher Assignment Creation & Grading
    // ==========================================

    private function handleTeacherCreateAssignmentChooseCourse(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);
        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك لإنشاء واجبات.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($courses as $c) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📝 إنشاء واجب لمادة: {$c->title}", 'callback_data' => "teacher_create_assign_course_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "📝 **إنشاء ونشر واجب جديد** 🎓\n\nيرجى اختيار المادة المراد إضافة الواجب لها:", null, $keyboard);
    }

    private function handleTeacherCreateAssignmentStart(User $user, $chatId, int $courseId)
    {
        $course = Course::find($courseId);
        if (!$course) return;

        Cache::put("telegram_teacher_assign_course_{$chatId}", $courseId, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_assign_title_{$courseId}", 1800);

        $this->sendMessage($chatId, "📝 **إنشاء واجب جديد لمادة: {$course->title}**\n\nيرجى كتابة عنوان وتفاصيل الواجب (مثال: `حل تمارين الوحدة الثالثة صفحة 45`):");
    }

    private function handleTeacherAssignTitleInput($chatId, $text, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_assign_title_', '', $state);
        $title = trim($text);

        Cache::put("telegram_teacher_assign_title_{$chatId}", $title, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_assign_due_{$courseId}", 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📅 بعد 3 أيام', 'callback_data' => "teacher_assign_due_preset_{$courseId}_3"],
                    ['text' => '📅 بعد أسبوع', 'callback_data' => "teacher_assign_due_preset_{$courseId}_7"],
                ],
                [
                    ['text' => '📅 بعد 10 أيام', 'callback_data' => "teacher_assign_due_preset_{$courseId}_10"],
                    ['text' => '📅 بعد أسبوعين', 'callback_data' => "teacher_assign_due_preset_{$courseId}_14"],
                ]
            ]
        ];

        $this->sendMessage(
            $chatId,
            "📅 **موعد تسليم الواجب (Due Date)**\n\nتم حفظ العنوان: **{$title}**\n\nاختر المدة من الأزرار أو اكتب التاريخ بصيغة YYYY-MM-DD (مثال: " . now()->addDays(7)->format('Y-m-d') . "):",
            null,
            $keyboard
        );
    }

    private function handleTeacherAssignDueInput($chatId, $text, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_assign_due_', '', $state);
        $time = strtotime(trim($text));

        if (!$time) {
            $this->sendMessage($chatId, "❌ التاريخ غير صحيح. يرجى إرسال تاريخ صالح بصيغة YYYY-MM-DD (مثال: " . now()->addDays(7)->format('Y-m-d') . "):");
            return;
        }

        $dueDate = date('Y-m-d 23:59:59', $time);
        Cache::put("telegram_teacher_assign_due_{$chatId}", $dueDate, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_assign_points_{$courseId}", 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💯 10 درجات', 'callback_data' => "teacher_assign_points_preset_{$courseId}_10"],
                    ['text' => '💯 20 درجة', 'callback_data' => "teacher_assign_points_preset_{$courseId}_20"],
                ],
                [
                    ['text' => '💯 50 درجة', 'callback_data' => "teacher_assign_points_preset_{$courseId}_50"],
                    ['text' => '💯 100 درجة', 'callback_data' => "teacher_assign_points_preset_{$courseId}_100"],
                ]
            ]
        ];

        $displayDate = date('Y-m-d', $time);
        $this->sendMessage(
            $chatId,
            "💯 **الدرجة الكلية للواجب (Max Score)**\n\nموعد التسليم: `{$displayDate}`\n\nاختر الدرجة من الأزرار أو اكتب الرقم المطلوب:",
            null,
            $keyboard
        );
    }

    private function handleTeacherAssignPointsInput(User $user, $chatId, $text, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_assign_points_', '', $state);
        $points = (float)trim($text);

        if ($points <= 0) {
            $this->sendMessage($chatId, "❌ يرجى إدخال درجة موجبة أكبر من الصفر:");
            return;
        }

        $course = Course::with('students.user')->find($courseId);
        if (!$course) {
            $this->sendMessage($chatId, "❌ المادة غير موجودة.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $title = Cache::get("telegram_teacher_assign_title_{$chatId}", 'واجب جديد');
        $dueDate = Cache::get("telegram_teacher_assign_due_{$chatId}", now()->addDays(7)->toDateTimeString());
        $teacher = $user->teacher;

        $assignment = Assignment::create([
            'course_id'   => $courseId,
            'teacher_id'  => $teacher?->teacher_id,
            'title'       => $title,
            'description' => "تم الإنشاء عبر بوت التيليغرام بواسطة الأستاذ {$user->full_name}",
            'due_date'    => $dueDate,
            'max_points'  => $points,
        ]);

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_teacher_assign_title_{$chatId}");
        Cache::forget("telegram_teacher_assign_due_{$chatId}");
        Cache::forget("telegram_teacher_assign_course_{$chatId}");

        // إشعار جميع طلاب المادة
        $students = $course->students;
        $dueStr = date('Y-m-d', strtotime($dueDate));
        $notifTitle = "واجب جديد: {$course->title}";
        $notifMsg = "قام الأستاذ ({$user->full_name}) بنشر واجب جديد ({$title})، موعد التسليم: {$dueStr}، الدرجة: {$points}.";

        foreach ($students as $student) {
            if (!$student->user) continue;

            Notification::create([
                'user_id'    => $student->user->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'new_assignment',
                'category'   => 'academic',
                'related_id' => $assignment->assignment_id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($student->user->user_id, $notifTitle, $notifMsg, [
                'type' => 'new_assignment',
                'assignment_id' => (string)$assignment->assignment_id,
                'course_id' => (string)$courseId,
            ]);

            if ($student->user->telegram_chat_id) {
                $this->sendMessage($student->user->telegram_chat_id, "📝 **واجب جديد مسند إليك!** 🎓\n\n📘 **المادة:** {$course->title}\n📌 **العنوان:** {$title}\n📅 **موعد التسليم:** `{$dueStr}`\n💯 **الدرجة:** `{$points}`");
            }
        }

        $this->sendMessage(
            $chatId,
            "✅ **تم إنشاء ونشر الواجب بنجاح!** 🎓\n\n📘 **المادة:** {$course->title}\n📌 **العنوان:** {$title}\n📅 **موعد التسليم:** `{$dueStr}`\n💯 **الدرجة:** `{$points}`\n👥 **عدد الطلاب المستلمين:** `{$students->count()}` طالب"
        );
    }

    private function handleTeacherStartGradeSubmission(User $user, $chatId, int $submissionId)
    {
        $submission = AssignmentSubmission::with(['student.user', 'assignment.course'])->find($submissionId);
        if (!$submission) {
            $this->sendMessage($chatId, "❌ التسليم غير موجود.");
            return;
        }

        $stName = $submission->student->user->full_name ?? 'طالب';
        $assignTitle = $submission->assignment->title ?? 'واجب';
        $maxPoints = $submission->assignment->max_points ?? 20;
        $currentGrade = $submission->grade !== null ? "{$submission->grade} / {$maxPoints}" : "غير مرصودة بعد";

        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_sub_grade_{$submissionId}", 1800);

        $msg = "📝 **تصحيح ورصد علامة الواجب** 🎓\n\n"
            . "👤 **الطالب:** {$stName}\n"
            . "📘 **الواجب:** {$assignTitle}\n"
            . "💯 **الدرجة الكلية للواجب:** `{$maxPoints}`\n"
            . "📊 **الدرجة الحالية:** `{$currentGrade}`\n";

        if ($submission->solution_text) {
            $msg .= "\n✍️ **حل الطالب:**\n\"{$submission->solution_text}\"\n";
        }

        if ($submission->student_notes) {
            $msg .= "\n💬 **ملاحظة الطالب:**\n\"{$submission->student_notes}\"\n";
        }

        if ($submission->feedback) {
            $msg .= "\n💬 **ملاحظاتك السابقة:**\n\"{$submission->feedback}\"\n";
        }

        $msg .= "\n👇 **يرجى إرسال الدرجة المستحقة (من 0 إلى {$maxPoints})**\n"
            . "يمكنك كتابة ملاحظات للمعلم بعد فاصلة (مثال: `18, حل ممتاز ومنظم` أو فقط `19`):";

        $this->sendMessage($chatId, $msg);

        // إذا كان هناك ملف مرفق من الطالب أرسله للمعلم ليفحصه
        if (!empty($submission->file_path)) {
            $this->sendDocument($chatId, $submission->file_path, "📎 ملف حل الطالب: {$stName}");
        }
    }

    private function handleTeacherGradeSubmissionInput(User $user, $chatId, $text, $state)
    {
        $submissionId = (int)str_replace('awaiting_teacher_sub_grade_', '', $state);
        $submission = AssignmentSubmission::with(['student.user', 'assignment.course'])->find($submissionId);
        if (!$submission) {
            $this->sendMessage($chatId, "❌ التسليم غير موجود.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $maxPoints = $submission->assignment->max_points ?? 20;

        // تحليل النص (الدرجة والملاحظات)
        $parts = explode(',', $text, 2);
        if (count($parts) < 2) {
            $parts = explode('،', $text, 2);
        }

        $scoreInput = trim($parts[0]);
        $feedback = isset($parts[1]) ? trim($parts[1]) : null;

        if (!is_numeric($scoreInput)) {
            $this->sendMessage($chatId, "❌ يرجى إدخال رقم صحيح أو عشري للدرجة (بين 0 و {$maxPoints}). مثال: `18` أو `18, ممتاز`:");
            return;
        }

        $score = (float)$scoreInput;
        if ($score < 0 || $score > $maxPoints) {
            $this->sendMessage($chatId, "❌ الدرجة المدخلة ({$score}) خارج النطاق المسموح (0 إلى {$maxPoints}). يرجى إعادة الإدخال:");
            return;
        }

        $submission->grade = $score;
        if ($feedback) {
            $submission->feedback = $feedback;
        }
        $submission->save();

        Cache::forget("telegram_state_{$chatId}");

        $stName = $submission->student->user->full_name ?? 'طالب';
        $assignTitle = $submission->assignment->title ?? 'واجب';
        $courseTitle = $submission->assignment->course->title ?? 'مادة';

        // إشعار الطالب فوراً
        $studentUser = $submission->student->user ?? null;
        if ($studentUser) {
            $notifTitle = "تم تصحيح واجب: {$assignTitle}";
            $notifMsg = "قام الأستاذ ({$user->full_name}) برصد علامتك: ({$score} / {$maxPoints}) في مادة ({$courseTitle})." . ($feedback ? "\nملاحظات: {$feedback}" : "");

            Notification::create([
                'user_id'    => $studentUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'assignment_grade',
                'category'   => 'academic',
                'related_id' => $submission->assignment_id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($studentUser->user_id, $notifTitle, $notifMsg, [
                'type' => 'assignment_grade',
                'assignment_id' => (string)$submission->assignment_id
            ]);

            if ($studentUser->telegram_chat_id) {
                $this->sendMessage($studentUser->telegram_chat_id, "🔔 **تم تصحيح واجبك!** 🎓\n\n📘 **المادة:** {$courseTitle}\n📝 **الواجب:** {$assignTitle}\n💯 **علامتك:** `{$score} / {$maxPoints}`" . ($feedback ? "\n💬 **ملاحظة الأستاذ:** {$feedback}" : ""));
            }
        }

        $feedbackStr = $feedback ? "\n💬 **الملاحظات:** {$feedback}" : "";
        $this->sendMessage(
            $chatId,
            "✅ **تم رصد العلامة بنجاح!** 🎓\n\n👤 **الطالب:** {$stName}\n📘 **الواجب:** {$assignTitle}\n💯 **الدرجة المرصودة:** `{$score} / {$maxPoints}`{$feedbackStr}\n\n📨 تم إرسال إشعار فوري للطالب بالنتيجة."
        );
    }
}
