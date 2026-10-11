<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceTeacherReceipt;
use App\Models\TeacherDeviceKey;
use App\Models\User;
use Carbon\Carbon;

class TeacherReceiptService
{
    /**
     * تسجيل أو تحديث المفتاح العام لجهاز المعلم.
     */
    public static function registerDeviceKey(int $teacherUserId, string $deviceId, string $publicKey): TeacherDeviceKey
    {
        return TeacherDeviceKey::updateOrCreate(
            [
                'teacher_user_id' => $teacherUserId,
                'device_id'       => $deviceId,
            ],
            [
                'public_key'    => $publicKey,
                'registered_at' => now(),
                'revoked_at'    => null,
            ]
        );
    }

    /**
     * إلغاء تفعيل (Revoke) مفتاح جهاز المعلم فورياً عند الشك أو الفقدان.
     */
    public static function revokeDeviceKey(int $teacherUserId, string $deviceId): bool
    {
        $key = TeacherDeviceKey::where('teacher_user_id', $teacherUserId)
            ->where('device_id', $deviceId)
            ->first();

        if (!$key) {
            return false;
        }

        $key->update(['revoked_at' => now()]);

        return true;
    }

    /**
     * التحقق من التوقيع الرقمي Ed25519 باستخدام امتداد libsodium.
     */
    public static function verifySignature(string $publicKey, string $message, string $signature): bool
    {
        // دعم فك الترميز سواء أُرسل المفتاح والتوقيع بـ Hex أو Base64
        $binaryKey = self::decodeCryptoString($publicKey);
        $binarySig = self::decodeCryptoString($signature);

        if (!$binaryKey || !$binarySig) {
            return false;
        }

        if (function_exists('sodium_crypto_sign_verify_detached')) {
            try {
                return sodium_crypto_sign_verify_detached($binarySig, $message, $binaryKey);
            } catch (\SodiumException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * معالجة وتدقيق إيصال معلم موقّع لرفع مستوى الثقة (Countersigning).
     *
     * @return array{success: bool, error?: string, client_scan_id?: string}
     */
    public static function processReceipt(User $teacherUser, array $data): array
    {
        $clientScanId = $data['client_scan_id'] ?? null;
        $sessionUuid  = $data['session_uuid'] ?? null;
        $studentId    = (int) ($data['student_id'] ?? 0);
        $deviceId     = $data['device_id'] ?? null;
        $signature    = $data['signature'] ?? null;
        $teacherTime  = $data['teacher_time'] ?? now()->toIso8601String();

        if (!$clientScanId || !$sessionUuid || !$studentId || !$deviceId || !$signature) {
            return ['success' => false, 'error' => 'missing_fields'];
        }

        // 1. التحقق من وجود مفتاح معتمد للجهاز وعدم سحبه (Revoked)
        $deviceKey = TeacherDeviceKey::where('teacher_user_id', $teacherUser->user_id)
            ->where('device_id', $deviceId)
            ->first();

        if (!$deviceKey || $deviceKey->isRevoked()) {
            return ['success' => false, 'error' => 'revoked_key', 'client_scan_id' => $clientScanId];
        }

        // 2. التحقق من وجود الجلسة المعنية
        $session = AttendanceSession::where('session_uuid', $sessionUuid)->first();
        if (!$session) {
            return ['success' => false, 'error' => 'session_not_found', 'client_scan_id' => $clientScanId];
        }

        // 3. التحقق من صحة التوقيع الرقمي
        $message = "edu-bridge-receipt|{$session->session_uuid}|{$studentId}|{$clientScanId}|{$teacherTime}";
        $isValid = self::verifySignature($deviceKey->public_key, $message, $signature);

        if (!$isValid) {
            return ['success' => false, 'error' => 'bad_receipt', 'client_scan_id' => $clientScanId];
        }

        // 4. حفظ الإيصال في جدول إيصالات المعلم
        AttendanceTeacherReceipt::updateOrCreate(
            [
                'session_id'     => $session->id,
                'client_scan_id' => $clientScanId,
            ],
            [
                'student_id'   => $studentId,
                'device_id'    => $deviceId,
                'teacher_time' => Carbon::parse($teacherTime),
                'signature'    => $signature,
                'verified'     => true,
            ]
        );

        // 5. ربط الإيصال بسجل حضور الطالب (إن وُجد مسبقاً أو لاحقاً) وترقيته إلى high & countersigned
        $attendance = Attendance::where('lesson_id', $session->lesson_id)
            ->where(function ($q) use ($studentId, $clientScanId) {
                $q->where('client_scan_id', $clientScanId)
                  ->orWhere('student_id', $studentId);
            })
            ->first();

        if ($attendance) {
            $attendance->update([
                'countersigned'   => true,
                'time_confidence' => 'high',
                'review_status'   => 'confirmed',
            ]);
        }

        return [
            'success'        => true,
            'client_scan_id' => $clientScanId,
            'countersigned'  => true,
        ];
    }

    private static function decodeCryptoString(string $val): ?string
    {
        $trimmed = trim($val);
        // إذا كان تمثيلاً سداسي عشري (Hex)
        if (ctype_xdigit($trimmed) && strlen($trimmed) % 2 === 0) {
            return hex2bin($trimmed) ?: null;
        }

        // تجربة Base64
        $b64 = base64_decode($trimmed, true);
        if ($b64 !== false) {
            return $b64;
        }

        return $trimmed;
    }
}
