<?php

namespace App\Support;

use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * البصمة التي تُحفظ مع كل عملية مسح حضور لتحليلها لاحقًا (AttendanceIntelligence).
 * لا تؤثر على قبول الحضور أو رفضه: هي تسجيل فقط.
 */
class ScanFingerprint
{
    /** عمر الـ QR الافتراضي بالثواني (يطابق TeacherController::generateQrSession). */
    private const QR_LIFETIME = 30;

    /**
     * @return array{session_id:int, ip_address:?string, user_agent:?string, qr_age_seconds:?int}
     */
    public static function capture(Request $request, AttendanceSession $session, ?Carbon $scannedAt = null): array
    {
        $ua = $request->userAgent();

        return [
            'session_id'     => $session->id,
            'ip_address'     => $request->ip(),
            'user_agent'     => $ua !== null ? mb_substr($ua, 0, 250) : null,
            'qr_age_seconds' => self::qrAge($session, $scannedAt),
        ];
    }

    /**
     * كم ثانية مضت منذ إصدار الـ QR الحالي حتى المسح. يُحسب فقط للمسح الفوري؛
     * المسح المؤجَّل (أوفلاين) لا معنى لعمر الـ QR فيه فيرجع null.
     */
    public static function qrAge(AttendanceSession $session, ?Carbon $scannedAt = null): ?int
    {
        if (!$session->expires_at) {
            return null;
        }

        $scannedAt = $scannedAt ?? now();
        if (abs($scannedAt->diffInSeconds(now(), false)) > 60) {
            return null; // مسح مؤجَّل
        }

        $issuedAt = $session->expires_at->copy()->subSeconds(self::QR_LIFETIME);
        $age      = (int) $issuedAt->diffInSeconds($scannedAt, false);

        return ($age < 0 || $age > self::QR_LIFETIME + 5) ? null : $age;
    }
}
