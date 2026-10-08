<?php

namespace App\Support;

use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * تدوير رمز QR للجلسة (كل 30 ثانية) وحلّ الرمز عند المسح.
 *
 * المشكلة التي يعالجها: التطبيق يقرأ الـ QR ثم يطلب من الطالب خطوة الوجه (رمشة واستقامة) ثم يرسل الحضور،
 * وهذا قد يستغرق أكثر من 30 ثانية، فيكون الرمز قد استُبدل. لذلك نحتفظ بالرموز السابقة ونقبل الرمز المستبدَل
 * مدة قصيرة (attendance.qr_grace_seconds، الافتراضي 90 ثانية) بعد استبداله، ولا نقبله بعدها.
 */
class QrTokens
{
    /** عمر الرمز الحالي بالثواني (يطابق TeacherController::generateQrSession). */
    public const LIFETIME = 30;

    /** كم رمزًا سابقًا نحتفظ به لكل جلسة (يغطي أي مهلة سماح معقولة). */
    private const KEEP = 6;

    /** يستبدل رمز الجلسة بآخر جديد ويحفظ القديم مع وقت استبداله. */
    public static function rotate(int $sessionId, string $newToken, ?Carbon $now = null): void
    {
        $now ??= now();

        DB::transaction(function () use ($sessionId, $newToken, $now) {
            $row = DB::table('attendance_sessions')->where('id', $sessionId)->lockForUpdate()->first();
            if (!$row) {
                return;
            }

            $previous = json_decode($row->previous_tokens ?? '[]', true) ?: [];
            array_unshift($previous, ['token' => $row->qr_token, 'replaced_at' => $now->toIso8601String()]);

            DB::table('attendance_sessions')->where('id', $sessionId)->update([
                'qr_token'        => $newToken,
                'previous_tokens' => json_encode(array_slice($previous, 0, self::KEEP)),
                'expires_at'      => $now->copy()->addSeconds(self::LIFETIME),
                'updated_at'      => $now,
            ]);
        });
    }

    /**
     * الجلسة التي يخصها هذا الرمز: الحالي دائمًا، أو سابق استُبدل منذ أقل من مهلة السماح.
     */
    public static function resolve(string $token): ?AttendanceSession
    {
        $current = AttendanceSession::where('qr_token', $token)->first();
        if ($current) {
            return $current;
        }

        $grace = (int) config('attendance.qr_grace_seconds', 90);
        // الرموز الحقيقية عشوائية أبجدية رقمية؛ أي شيء آخر لا يستحق البحث في السجل
        if ($grace <= 0 || !preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            return null;
        }

        foreach (AttendanceSession::where('previous_tokens', 'like', '%"' . $token . '"%')->get() as $session) {
            foreach ($session->previous_tokens ?? [] as $entry) {
                if (($entry['token'] ?? null) !== $token) {
                    continue;
                }
                $sinceReplaced = Carbon::parse($entry['replaced_at'])->diffInSeconds(now(), false);
                if ($sinceReplaced >= -2 && $sinceReplaced <= $grace) {
                    return $session;
                }
            }
        }

        return null;
    }
}
