<?php

namespace App\Services;

use App\Models\AttendanceClockAlert;
use App\Models\AttendanceSession;
use App\Models\Notification;
use App\Models\Student;
use App\Support\Access;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * تنبيه الطالب عند رصد تلاعب بالساعة، وتصعيد رئيس القسم إذا تكرر التنبيه دون رد.
 *
 *  - تنبيه واحد لكل (طالب، جلسة، نوع): لا نغمر الطالب بتنبيهات.
 *  - "الرد" = تأكيد الطالب للتنبيه من التطبيق (AttendanceClockAlert::acknowledged_at).
 *  - عند وجود تنبيهين غير مُجاب عليهما أو أكثر (قابل للضبط) خلال المدة، يُشعَر رئيس قسم الطالب مرة واحدة.
 *    إن لم يوجد رئيس قسم لا نعلّم التنبيهات "مُصعَّدة"، كي لا يضيع التصعيد إلى الأبد.
 */
class ClockAlertService
{
    public static function record(Student $student, AttendanceSession $session, array $clock): ?AttendanceClockAlert
    {
        $alert = AttendanceClockAlert::firstOrCreate(
            ['student_id' => $student->student_id, 'session_id' => $session->id, 'kind' => $clock['kind']],
            ['skew_seconds' => $clock['skew']],
        );

        if (!$alert->wasRecentlyCreated) {
            return $alert;
        }

        self::notifyStudent($student, $alert);
        self::escalateIfNeeded($student);

        return $alert;
    }

    private static function notifyStudent(Student $student, AttendanceClockAlert $alert): void
    {
        $minutes = $alert->skew_seconds !== null ? (int) round(abs($alert->skew_seconds) / 60) : null;
        $detail  = $alert->kind === ClockIntegrityService::KIND_SKEW && $minutes
            ? ($alert->skew_seconds > 0 ? "ساعة هاتفك متقدّمة بنحو {$minutes} دقيقة" : "ساعة هاتفك متأخرة بنحو {$minutes} دقيقة")
            : 'وقت مسح الحضور المرسل من هاتفك غير منطقي';

        $title = '⏰ تنبيه: ساعة هاتفك غير مضبوطة';
        $body  = "{$detail} عن وقت النظام عند مسح الحضور. فعّل ضبط التاريخ والوقت التلقائي. "
            . 'تكرار هذا التنبيه دون رد يُبلَّغ به رئيس القسم. افتح التنبيه واضغط "تأكيد" لإبلاغنا أنك صحّحت الساعة.';

        try {
            Notification::create([
                'user_id' => $student->user_id, 'sender_id' => Access::systemSenderId(), 'title' => $title, 'message' => $body,
                'type' => 'clock_alert', 'category' => 'academic', 'related_id' => $alert->id, 'is_read' => false,
            ]);
            FcmService::sendToUser((int) $student->user_id, $title, $body, ['type' => 'clock_alert', 'alert_id' => (string) $alert->id]);
        } catch (\Throwable $e) {
            Log::warning('Clock alert notification failed: ' . $e->getMessage());
        }
    }

    private static function escalateIfNeeded(Student $student): void
    {
        $threshold = (int) config('attendance.clock.escalate_after', 2);
        $days      = (int) config('attendance.clock.alert_window_days', 14);

        $open = AttendanceClockAlert::where('student_id', $student->student_id)
            ->whereNull('acknowledged_at')
            ->whereNull('escalated_at')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($open->count() < $threshold) {
            return;
        }

        $heads = self::headsOf($student);
        if (!$heads) {
            return;
        }

        $name  = DB::table('users')->where('user_id', $student->user_id)->value('full_name') ?? 'طالب';
        $title = '⚠️ طالب يتكرر منه التلاعب بساعة الهاتف عند الحضور';
        $body  = "الطالب {$name} رُصد انحراف/تلاعب بساعة هاتفه {$open->count()} مرات عند مسح الحضور، وأُرسل له تنبيه ولم يردّ عليه. يُرجى متابعته.";

        foreach ($heads as $headUserId) {
            try {
                Notification::create([
                    'user_id' => $headUserId, 'sender_id' => Access::systemSenderId(), 'title' => $title, 'message' => $body,
                    'type' => 'clock_alert_escalation', 'category' => 'administrative', 'related_id' => $student->student_id, 'is_read' => false,
                ]);
                FcmService::sendToUser($headUserId, $title, $body, ['type' => 'clock_alert_escalation']);
            } catch (\Throwable $e) {
                Log::warning('Clock alert escalation failed: ' . $e->getMessage());
            }
        }

        AttendanceClockAlert::whereIn('id', $open->pluck('id'))->update(['escalated_at' => now()]);
    }

    /** @return int[] user_id لرؤساء قسم الطالب النشطين */
    private static function headsOf(Student $student): array
    {
        $deptId = DB::table('programs')->where('id', $student->program_id)->value('department_id');
        if (!$deptId) {
            return [];
        }

        return DB::table('heads')
            ->join('users', 'users.user_id', '=', 'heads.user_id')
            ->where('users.status', 'active')
            ->where('heads.department_id', $deptId)
            ->pluck('heads.user_id')->map(fn ($i) => (int) $i)->all();
    }
}
