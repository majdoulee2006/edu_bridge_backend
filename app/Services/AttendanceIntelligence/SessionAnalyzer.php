<?php

namespace App\Services\AttendanceIntelligence;

use App\Models\AttendanceFlag;
use App\Models\AttendanceSession;
use App\Models\Notification;
use App\Services\AttendanceIntelligence\Rules\DetectionRule;
use App\Services\AttendanceIntelligence\Rules\IdenticalCoordinatesRule;
use App\Services\AttendanceIntelligence\Rules\IpOutlierRule;
use App\Services\AttendanceIntelligence\Rules\LastSecondPatternRule;
use App\Services\AttendanceIntelligence\Rules\ReplayedFaceScoreRule;
use App\Services\AttendanceIntelligence\Rules\SharedDeviceRule;
use App\Services\FcmService;
use App\Support\Access;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * يحلّل جلسة حضور مغلقة: يشغّل القواعد على مسوحاتها ويحفظ الاشتباهات في attendance_flags.
 *
 * - آمن لإعادة التشغيل: الاشتباه الواحد (سجل حضور + قاعدة) لا يتكرر، وما راجعه المعلم لا يُمسّ.
 * - لا يغيّر أي سجل حضور. القرار دائمًا للمعلم.
 */
class SessionAnalyzer
{
    /** @var DetectionRule[] */
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            new SharedDeviceRule(),
            new IdenticalCoordinatesRule(),
            new ReplayedFaceScoreRule(),
            new IpOutlierRule(),
            new LastSecondPatternRule(),
        ];
    }

    /** مفاتيح القواعد المعروفة (للواجهات والاختبارات). */
    public static function ruleKeys(): array
    {
        return [
            SharedDeviceRule::KEY, IdenticalCoordinatesRule::KEY, ReplayedFaceScoreRule::KEY,
            IpOutlierRule::KEY, LastSecondPatternRule::KEY,
        ];
    }

    /** @return int عدد الاشتباهات الجديدة التي أُضيفت في هذا التحليل */
    public function analyze(AttendanceSession $session): int
    {
        $scans = DB::table('attendance')
            ->where('session_id', $session->id)
            ->whereIn('status', ['present', 'late'])
            ->get(['attendance_id', 'student_id', 'status', 'latitude', 'longitude', 'device_id',
                   'ip_address', 'face_score', 'qr_age_seconds', 'created_at']);

        $lesson = DB::table('lessons')->where('lesson_id', $session->lesson_id)->first();
        $created = 0;

        if ($scans->isNotEmpty()) {
            $ctx = new SessionContext($session, $scans);
            $produced = [];

            foreach ($this->rules as $rule) {
                foreach ($rule->evaluate($ctx) as $hit) {
                    $produced[] = $hit['attendance_id'] . ':' . $rule->key();
                    $created += $this->store($session, $lesson, $rule->key(), $hit);
                }
            }

            $this->dropStaleOpenFlags($session, $produced);
        }

        $session->forceFill(['analyzed_at' => now()])->save();

        if ($created > 0 && $lesson) {
            $this->notifyTeacher($session, $lesson, $created);
        }

        return $created;
    }

    /** @return int 1 إذا أُنشئ اشتباه جديد، 0 إذا كان موجودًا */
    private function store(AttendanceSession $session, ?object $lesson, string $rule, array $hit): int
    {
        $flag = AttendanceFlag::firstOrNew([
            'attendance_id' => $hit['attendance_id'],
            'rule'          => $rule,
        ]);

        $isNew = !$flag->exists;

        // ما راجعه المعلم (أُكّد/رُفض) لا يُعاد فتحه ولا تتغير تفاصيله
        if (!$isNew && $flag->status !== AttendanceFlag::OPEN) {
            return 0;
        }

        $flag->fill([
            'session_id' => $session->id,
            'student_id' => $hit['student_id'],
            'lesson_id'  => $lesson->lesson_id ?? null,
            'course_id'  => $lesson->course_id ?? null,
            'severity'   => $hit['severity'],
            'details'    => $hit['details'],
        ]);
        if ($isNew) {
            $flag->status = AttendanceFlag::OPEN;
        }
        $flag->save();

        return $isNew ? 1 : 0;
    }

    /** اشتباهات مفتوحة لم تعد تنتجها القواعد (تغيّرت البيانات) تُحذف لتبقى القائمة صادقة. */
    private function dropStaleOpenFlags(AttendanceSession $session, array $produced): void
    {
        AttendanceFlag::where('session_id', $session->id)
            ->where('status', AttendanceFlag::OPEN)
            ->get()
            ->each(function (AttendanceFlag $f) use ($produced) {
                if (!in_array($f->attendance_id . ':' . $f->rule, $produced, true)) {
                    $f->delete();
                }
            });
    }

    private function notifyTeacher(AttendanceSession $session, object $lesson, int $count): void
    {
        $teacherUserId = $lesson->teacher_id
            ? DB::table('teachers')->where('teacher_id', $lesson->teacher_id)->value('user_id')
            : null;
        if (!$teacherUserId) {
            return;
        }

        $title = '🔎 حالات حضور تحتاج مراجعة';
        $body  = "رُصد {$count} اشتباه في جلسة حضور مغلقة. راجعها من شاشة الحالات المشبوهة قبل اعتماد الحضور.";

        try {
            Notification::create([
                'user_id'    => $teacherUserId,
                'sender_id'  => Access::systemSenderId(),
                'title'      => $title,
                'message'    => $body,
                'type'       => 'attendance_flags',
                'category'   => 'administrative',
                'related_id' => $session->id,
                'is_read'    => false,
            ]);
            FcmService::sendToUser((int) $teacherUserId, $title, $body, [
                'type' => 'attendance_flags', 'related_id' => (string) $session->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Attendance flags notification failed: ' . $e->getMessage());
        }
    }
}
