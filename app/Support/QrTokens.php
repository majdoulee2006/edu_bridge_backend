<?php

namespace App\Support;

use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تدوير رمز QR للجلسة (كل 30 ثانية) وحلّ الرمز عند المسح.
 *
 * يدعم كلاً من:
 * 1. المسح المباشر المتصل: يقبل الرمز الحالي أو المستبدل حديثاً (خلال مهلة 90 ثانية).
 * 2. المسح الأوفلاين المؤجل (المرحلة 1): يقبل أي رمز جلسة إذا كان وقت المسح الفعلي (scanned_at)
 *    واقعاً ضمن نافذة صلاحية الرمز أثناء عرضه في القاعة، عبر جدول attendance_session_tokens.
 */
class QrTokens
{
    /** عمر الرمز الحالي بالثواني (يطابق TeacherController::generateQrSession). */
    public const LIFETIME = 30;

    /** كم رمزًا سابقًا نحتفظ به لكل جلسة (يغطي أي مهلة سماح معقولة). */
    private const KEEP = 6;

    /** تسجيل الرمز الأولي للجلسة فور إنشائها في الخط الزمني. */
    public static function recordIssued(int $sessionId, string $token, ?Carbon $issuedAt = null): void
    {
        $issuedAt ??= now();
        if (!Schema::hasTable('attendance_session_tokens')) {
            return;
        }

        DB::table('attendance_session_tokens')->updateOrInsert(
            ['session_id' => $sessionId, 'token' => $token],
            ['issued_at' => $issuedAt, 'created_at' => $issuedAt, 'updated_at' => $issuedAt]
        );
    }

    /** يستبدل رمز الجلسة بآخر جديد ويحفظ القديم مع وقت استبداله في الخط الزمني. */
    public static function rotate(int $sessionId, string $newToken, ?Carbon $now = null): void
    {
        $now ??= now();

        DB::transaction(function () use ($sessionId, $newToken, $now) {
            $row = DB::table('attendance_sessions')->where('id', $sessionId)->lockForUpdate()->first();
            if (!$row) {
                return;
            }

            $oldToken = $row->qr_token;
            $previous = json_decode($row->previous_tokens ?? '[]', true) ?: [];
            array_unshift($previous, ['token' => $oldToken, 'replaced_at' => $now->toIso8601String()]);

            DB::table('attendance_sessions')->where('id', $sessionId)->update([
                'qr_token'        => $newToken,
                'previous_tokens' => json_encode(array_slice($previous, 0, self::KEEP)),
                'expires_at'      => $now->copy()->addSeconds(self::LIFETIME),
                'updated_at'      => $now,
            ]);

            // حفظ الخط الزمني الكامل في جدول attendance_session_tokens (المرحلة 1)
            if (Schema::hasTable('attendance_session_tokens')) {
                // تحديث وقت استبدال الرمز القديم إن كان موجوداً
                $updatedOld = DB::table('attendance_session_tokens')
                    ->where('session_id', $sessionId)
                    ->where('token', $oldToken)
                    ->update(['replaced_at' => $now, 'updated_at' => $now]);

                if (!$updatedOld) {
                    $initialIssuedAt = $row->created_at ? Carbon::parse($row->created_at) : $now->copy()->subSeconds(self::LIFETIME);
                    DB::table('attendance_session_tokens')->insert([
                        'session_id'  => $sessionId,
                        'token'       => $oldToken,
                        'issued_at'   => $initialIssuedAt,
                        'replaced_at' => $now,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }

                // تسجيل الرمز الجديد
                DB::table('attendance_session_tokens')->insert([
                    'session_id'  => $sessionId,
                    'token'       => $newToken,
                    'issued_at'   => $now,
                    'replaced_at' => null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        });
    }

    /**
     * الجلسة التي يخصها هذا الرمز:
     * - الرمز الحالي دائماً.
     * - أو إذا تم تمرير وقت مسح فعلي ($scannedAt): التحقق من الخط الزمني الكامل للرموز.
     * - أو سابق استُبدل منذ أقل من مهلة السماح (للمسح المباشر المتصل).
     */
    public static function resolve(string $token, ?Carbon $scannedAt = null): ?AttendanceSession
    {
        // 0. فحص ما إذا كان الرمز بصيغة TOTP v2 (المرحلة 2: المعلم والطالب أوفلاين)
        $parsedTotp = TotpQrService::parsePayload($token);
        if ($parsedTotp) {
            $session = AttendanceSession::where('session_uuid', $parsedTotp['sid'])->first();
            if ($session && !empty($session->session_secret)) {
                $scanUnix = $scannedAt ? $scannedAt->timestamp : now()->timestamp;
                if (TotpQrService::verify($token, $session->session_secret, $session->session_uuid, $scanUnix, 1)) {
                    return $session;
                }
            }
            return null;
        }

        // 1. فحص الرمز الحالي المباشر
        $current = AttendanceSession::where('qr_token', $token)->first();
        if ($current) {
            return $current;
        }

        // 2. إذا تم تمرير وقت مسح فعلي (مسار الأوفلاين): البحث في الخط الزمني الكامل
        if ($scannedAt && Schema::hasTable('attendance_session_tokens')) {
            $record = DB::table('attendance_session_tokens')
                ->where('token', $token)
                ->first();

            if ($record) {
                $issuedAt = Carbon::parse($record->issued_at);
                // تسامح 30 ثانية قبل الإصدار لفروق التوقيت
                $validStart = $issuedAt->copy()->subSeconds(30);
                // نافذة الصلاحية: حتى استبدال الرمز + مهلة سماح 30 ثانية
                $validEnd = $record->replaced_at
                    ? Carbon::parse($record->replaced_at)->addSeconds(30)
                    : $issuedAt->copy()->addSeconds(self::LIFETIME + 60);

                if ($scannedAt->greaterThanOrEqualTo($validStart) && $scannedAt->lessThanOrEqualTo($validEnd)) {
                    $session = AttendanceSession::find($record->session_id);
                    if ($session) {
                        return $session;
                    }
                }
            }
        }

        $grace = (int) config('attendance.qr_grace_seconds', 90);
        // الرموز الحقيقية عشوائية أبجدية رقمية؛ أي شيء آخر لا يستحق البحث في السجل
        if ($grace <= 0 || !preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            return null;
        }

        // 3. المسار الاحتياطي للرموز السابقة (previous_tokens)
        foreach (AttendanceSession::where('previous_tokens', 'like', '%"' . $token . '"%')->get() as $session) {
            foreach ($session->previous_tokens ?? [] as $entry) {
                if (($entry['token'] ?? null) !== $token) {
                    continue;
                }
                $checkTime = $scannedAt ?: now();
                $sinceReplaced = Carbon::parse($entry['replaced_at'])->diffInSeconds($checkTime, false);
                if ($sinceReplaced >= -10 && $sinceReplaced <= $grace) {
                    return $session;
                }
            }
        }

        return null;
    }
}
