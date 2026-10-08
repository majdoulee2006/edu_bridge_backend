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
 * Telegram bot: teacher lecture and resource upload
 * (moved as-is from TeacherHandlers; methods keep their names and behaviour)
 */
trait TeacherLectureHandlers
{
    // ==========================================
    // Teacher Lecture & Resource Upload Handlers
    // ==========================================

    private function handleTeacherUploadLessonChooseCourse(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);
        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك لرفع ملفات.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($courses as $c) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📤 رفع لمحاضرة مادة: {$c->title}", 'callback_data' => "teacher_upload_lesson_start_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "📤 **رفع محاضرة أو ملف تعليمي جديد** 🎓\n\nيرجى اختيار المادة المراد إضافة الملف أو المحاضرة لها:", null, $keyboard);
    }

    private function handleTeacherUploadLessonStart(User $user, $chatId, int $courseId)
    {
        $course = Course::find($courseId);
        if (!$course) return;

        Cache::put("telegram_teacher_lesson_course_{$chatId}", $courseId, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_lesson_title_{$courseId}", 1800);

        $this->sendMessage($chatId, "📤 **رفع محاضرة / ملف تعليمي لمادة: {$course->title}** 📚\n\nيرجى كتابة عنوان المحاضرة أو الملف (مثال: `المحاضرة 5 - مقدمة في هياكل البيانات`):");
    }

    private function handleTeacherLessonTitleInput($chatId, $text, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_lesson_title_', '', $state);
        $title = trim($text);

        Cache::put("telegram_teacher_lesson_title_{$chatId}", $title, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_lesson_file_{$courseId}", 1800);

        $this->sendMessage(
            $chatId,
            "📎 **إرفاق الملف أو الرابط**\n\nتم حفظ العنوان: **{$title}**\n\nالآن يرجى إرسال **المستند / الملف (PDF, Word, PPTX, صورة)** مباشرة في المحادثة، أو إرسال **رابط المحاضرة / الفيديو** كنص:"
        );
    }

    private function handleTeacherLessonFileInput(User $user, $chatId, array $message, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_lesson_file_', '', $state);
        $course = Course::with('students.user')->find($courseId);
        if (!$course) {
            $this->sendMessage($chatId, "❌ المادة غير موجودة.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $title = Cache::get("telegram_teacher_lesson_title_{$chatId}", 'محاضرة جديدة');
        $teacher = $user->teacher;

        $filePath = null;
        $contentUrl = null;
        $type = 'document';
        $fileSize = null;

        if (isset($message['document'])) {
            $doc = $message['document'];
            $fileId = $doc['file_id'] ?? null;
            $origName = $doc['file_name'] ?? 'document.pdf';
            $fileSize = isset($doc['file_size']) ? round($doc['file_size'] / 1024, 1) . ' KB' : null;
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $type = in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip']) ? $ext : 'document';

            if ($fileId) {
                $filePath = $this->downloadTelegramDocument($fileId, $origName, 'lessons');
            }
        } elseif (isset($message['photo'])) {
            $photos = $message['photo'];
            $best = end($photos);
            $fileId = $best['file_id'] ?? null;
            $type = 'image';
            if ($fileId) {
                $filePath = $this->downloadTelegramPhoto($fileId);
            }
        } elseif (!empty($message['text'])) {
            $textUrl = trim($message['text']);
            $contentUrl = $textUrl;
            $type = (str_contains($textUrl, 'youtube') || str_contains($textUrl, 'youtu.be')) ? 'video' : 'link';
        }

        if (!$filePath && !$contentUrl) {
            $this->sendMessage($chatId, "❌ تعذر استلام الملف أو الرابط. يرجى إرسال ملف مستند أو صورة أو رابط إنترنت صالح:");
            return;
        }

        $lesson = Lesson::create([
            'course_id'     => $courseId,
            'teacher_id'    => $teacher?->teacher_id,
            'department_id' => $user->department_id ?? $course->department_id,
            'title'         => $title,
            'type'          => $type,
            'description'   => "تم الرفع عبر بوت التيليغرام بواسطة الأستاذ {$user->full_name}",
            'content_url'   => $contentUrl ?: ($filePath ? url($filePath) : null),
            'file_size'     => $fileSize,
        ]);

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_teacher_lesson_title_{$chatId}");
        Cache::forget("telegram_teacher_lesson_course_{$chatId}");

        // إشعار جميع طلاب المادة
        $students = $course->students;
        $notifTitle = "محاضرة / ملف جديد: {$course->title}";
        $notifMsg = "قام الأستاذ ({$user->full_name}) برفع ({$title}) لمادة ({$course->title}).";

        foreach ($students as $student) {
            if (!$student->user) continue;

            Notification::create([
                'user_id'    => $student->user->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'new_lesson',
                'category'   => 'academic',
                'related_id' => $lesson->lesson_id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($student->user->user_id, $notifTitle, $notifMsg, [
                'type' => 'new_lesson',
                'lesson_id' => (string)$lesson->lesson_id,
                'course_id' => (string)$courseId,
            ]);

            if ($student->user->telegram_chat_id) {
                $this->sendMessage($student->user->telegram_chat_id, "📢 **محاضرة / ملف جديد!** 🎓\n\n📘 **المادة:** {$course->title}\n📄 **العنوان:** {$title}\n👨‍🏫 **الأستاذ:** {$user->full_name}");
            }
        }

        $this->sendMessage(
            $chatId,
            "✅ **تم نشر المحاضرة / الملف بنجاح!** 🎓\n\n📘 **المادة:** {$course->title}\n📄 **العنوان:** {$title}\n📊 **النوع:** {$type}\n👥 **عدد الطلاب المستلمين للإشعار:** `{$students->count()}` طالب"
        );
    }

    private function downloadTelegramDocument(string $fileId, string $originalName = 'file', string $subDir = 'lessons'): ?string
    {
        try {
            $res = Http::get("{$this->apiUrl}/getFile", ['file_id' => $fileId]);
            if ($res->successful()) {
                $filePath = $res->json('result.file_path');
                if ($filePath) {
                    $token = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
                    $fileUrl = "https://api.telegram.org/file/bot{$token}/{$filePath}";
                    $fileContents = Http::get($fileUrl)->body();

                    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                    if (empty($ext) && !empty($originalName)) {
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    }
                    if (empty($ext)) {
                        $ext = 'pdf';
                    }

                    $cleanName = pathinfo($originalName, PATHINFO_FILENAME);
                    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cleanName) ?: 'file';
                    $filename = $cleanName . '_' . time() . '.' . $ext;

                    $destDir = public_path('uploads/' . $subDir);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    file_put_contents($destDir . '/' . $filename, $fileContents);

                    return 'uploads/' . $subDir . '/' . $filename;
                }
            }
        } catch (\Exception $e) {
            Log::error('Telegram download document error: ' . $e->getMessage());
        }
        return null;
    }
}
