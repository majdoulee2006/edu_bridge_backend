<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Notification;
use App\Models\ParentSummon;
use App\Models\Student;
use App\Models\StudentWarning;
use App\Models\User;
use App\Services\FcmService;
use App\Services\TelegramBotHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbsenceWarningService
{
    protected TelegramBotHandler $telegramBot;

    public function __construct(TelegramBotHandler $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }

    /**
     * التحقق من عدد أيام الغياب وإطلاق الإنذارات التلقائية المناسبة
     */
    public function checkAndWarn(int $studentId): void
    {
        $student = Student::with('user')->find($studentId);
        if (!$student || !$student->user) {
            return;
        }

        // حساب عدد أيام الغياب الفريدة إجمالاً عبر كل المواد
        $absenceDays = Attendance::where('student_id', $studentId)
            ->where('status', 'absent')
            ->pluck('attendance_date')
            ->map(fn($d) => $d ? Carbon::parse($d)->toDateString() : null)
            ->filter()
            ->unique()
            ->count();

        // 1. فحص حد الـ 15 يوم غياب (إنذار نهائي وإحالة للإدارة ورئيس القسم)
        if ($absenceDays >= 15) {
            $this->issueFinalWarning($student, $absenceDays);
        }
        // 2. فحص حد الـ 10 أيام غياب (إنذار ثانٍ واستدعاء ولي أمر تلقائي)
        elseif ($absenceDays >= 10) {
            $this->issueSecondWarning($student, $absenceDays);
        }
        // 3. فحص حد الـ 7 أيام غياب (إنذار أول وتنبيه)
        elseif ($absenceDays >= 7) {
            $this->issueFirstWarning($student, $absenceDays);
        }
    }

    /**
     * إنذار أول (عند بلوغ 7 أيام غياب)
     */
    protected function issueFirstWarning(Student $student, int $absenceDays): void
    {
        $exists = StudentWarning::where('student_id', $student->student_id)
            ->where('warning_level', 'first')
            ->exists();

        if ($exists) {
            return;
        }

        $title = "⚠️ إنذار غياب أول (7 أيام)";
        $message = "نحيطك علماً بأن عدد أيام غيابك قد بلغ ({$absenceDays} أيام). يرجى مراجعة إدارة شؤون الطلاب والالتزام بالدوام لتفادي استدعاء ولي الأمر والإنذارات التالية.";

        StudentWarning::create([
            'student_id'    => $student->student_id,
            'warning_level' => 'first',
            'absence_days'  => $absenceDays,
            'message'       => $message,
            'action_data'   => ['status' => 'issued'],
        ]);

        $this->notifyStudent($student->user, $title, $message);
    }

    /**
     * إنذار ثانٍ واستدعاء ولي أمر تلقائي (عند بلوغ 10 أيام غياب)
     */
    protected function issueSecondWarning(Student $student, int $absenceDays): void
    {
        $exists = StudentWarning::where('student_id', $student->student_id)
            ->where('warning_level', 'second')
            ->exists();

        if ($exists) {
            return;
        }

        $title = "🚨 إنذار غياب ثانٍ واستدعاء ولي أمر (10 أيام)";
        $message = "تحذير شديد: لقد بلغ عدد أيام غيابك ({$absenceDays} أيام). وبناءً على لائحة المعهد تم إصدار استدعاء رسمي لولي أمرك لمراجعة الإدارة.";

        // جلب معرف ولي الأمر
        $parentUserId = DB::table('parent_students')
            ->where('student_id', $student->student_id)
            ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
            ->value('parents.user_id');

        $summonId = null;
        if ($parentUserId) {
            $summonId = DB::table('parent_summons')->insertGetId([
                'sender_user_id' => 1, // النظام / الإدارة
                'student_id'     => $student->student_id,
                'parent_user_id' => $parentUserId,
                'reason_title'   => 'تجاوز حد الغياب المسموح به (10 أيام)',
                'details'        => "تم استدعاء ولي أمر الطالب {$student->user->full_name} تلقائياً بسبب وصول عدد أيام غيابه إلى {$absenceDays} يوماً.",
                'summon_date'    => now()->addDays(2)->toDateString(),
                'status'         => 'sent',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // إشعار ولي الأمر
            $parentTitle = "📋 استدعاء رسمي لولي الأمر";
            $parentMsg = "نرجو منكم مراجعة إدارة المعهد بخصوص غياب ابنكم/ابنتكم ({$student->user->full_name}) لتجاوزه 10 أيام غياب.";

            Notification::create([
                'user_id'    => $parentUserId,
                'sender_id'  => 1,
                'title'      => $parentTitle,
                'message'    => $parentMsg,
                'type'       => 'parent_summon',
                'category'   => 'administrative',
                'related_id' => $summonId,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($parentUserId, $parentTitle, $parentMsg, [
                'type' => 'parent_summon',
                'id'   => (string)$summonId,
            ]);
        }

        StudentWarning::create([
            'student_id'    => $student->student_id,
            'warning_level' => 'second',
            'absence_days'  => $absenceDays,
            'message'       => $message,
            'action_data'   => ['parent_summon_id' => $summonId],
        ]);

        $this->notifyStudent($student->user, $title, $message);
    }

    /**
     * إنذار نهائي وإحالة للإدارة ورئيس القسم لاتخاذ القرار (عند بلوغ 15 يوم غياب)
     */
    protected function issueFinalWarning(Student $student, int $absenceDays): void
    {
        $exists = StudentWarning::where('student_id', $student->student_id)
            ->where('warning_level', 'final')
            ->exists();

        if ($exists) {
            return;
        }

        $title = "🛑 إنذار نهائي - تجاوز 15 يوم غياب";
        $message = "لقد بلغت الحد الأقصى للغياب ({$absenceDays} يوماً). تم رفع ملفك كاملاً إلى إدارة شؤون الطلاب ورئيس القسم لاتخاذ القرار المناسب بحقك.";

        StudentWarning::create([
            'student_id'    => $student->student_id,
            'warning_level' => 'final',
            'absence_days'  => $absenceDays,
            'message'       => $message,
            'action_data'   => ['referred_to' => ['affairs', 'hod']],
        ]);

        // إشعار الطالب
        $this->notifyStudent($student->user, $title, $message);

        // إشعار موظفي شؤون الطلاب (Role 6)
        $affairsUsers = User::where('role_id', 6)->get();
        foreach ($affairsUsers as $affairUser) {
            Notification::create([
                'user_id'    => $affairUser->user_id,
                'sender_id'  => $student->user->user_id,
                'title'      => "🚨 تجاوز حد الغياب النهائي (15 يوم)",
                'message'    => "الطالب ({$student->user->full_name}) بلغ {$absenceDays} أيام غياب. يرجى اتخاذ القرار الإداري بحقه.",
                'type'       => 'student_warning',
                'category'   => 'administrative',
                'related_id' => $student->student_id,
                'is_read'    => false,
            ]);
        }

        // إشعار رئيس القسم (Role 5)
        $hodId = null;
        if ($student->program_id) {
            $departmentId = DB::table('programs')->where('id', $student->program_id)->value('department_id');
            if ($departmentId) {
                $hodId = DB::table('heads')->where('department_id', $departmentId)->value('user_id');
            }
        }
        if (!$hodId) {
            $hodId = User::where('role_id', 5)->value('user_id');
        }

        if ($hodId) {
            Notification::create([
                'user_id'    => $hodId,
                'sender_id'  => $student->user->user_id,
                'title'      => "🚨 قرار غياب نهائي مطلوب",
                'message'    => "وصل الطالب ({$student->user->full_name}) إلى {$absenceDays} يوماً من الغياب وبحاجة لقرار رئيس القسم.",
                'type'       => 'student_warning',
                'category'   => 'administrative',
                'related_id' => $student->student_id,
                'is_read'    => false,
            ]);
        }
    }

    /**
     * إرسال إشعار شامل للطالب عبر الموقع، الموبايل، وبوت التيليغرام
     */
    protected function notifyStudent(User $user, string $title, string $message): void
    {
        // 1. إشعار النظام المكتبي / الويب
        Notification::create([
            'user_id'    => $user->user_id,
            'sender_id'  => 1,
            'title'      => $title,
            'message'    => $message,
            'type'       => 'warning',
            'category'   => 'administrative',
            'is_read'    => false,
        ]);

        // 2. إشعار الموبايل عبر FCM
        FcmService::sendToUser($user->user_id, $title, $message, [
            'type' => 'warning',
        ]);

        // 3. إشعار التيليغرام إذا كان الحساب مربوطاً
        if (!empty($user->telegram_chat_id)) {
            $telegramMsg = "🚨 **{$title}**\n\n{$message}";
            $this->telegramBot->sendMessage($user->telegram_chat_id, $telegramMsg);
        }
    }
}
