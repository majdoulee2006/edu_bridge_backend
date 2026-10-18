<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * مقارنة ساعة هاتف الطالب بساعة السيرفر عند مسح الحضور.
 *
 * التطبيق يرسل وقت المسح (scanned_at) ووقت الهاتف لحظة إرسال الطلب (device_time). الفرق بين device_time
 * ووقت السيرفر الفعلي هو انحراف ساعة الهاتف. نطرحه من scanned_at فنحصل على وقت المسح بتوقيت السيرفر، وبذلك
 * لا تنجح حيلة "تقديم/تأخير ساعة الهاتف" إذا بقيت الساعة معدّلة حتى لحظة الإرسال.
 *
 * حدّ معروف: من يعدّل الساعة قبل المسح ثم يعيدها قبل الإرسال لا ينكشف بهذه الطريقة (يلزم مرساة زمنية رتيبة
 * على الجهاز). نخفف أثره بإغلاق النافذة عند 12 ليلاً، وبتحليل الأنماط (ذكاء الحضور)، وبمراجعة المعلم.
 */
class ClockIntegrityService
{
    public const KIND_SKEW = 'clock_skew';
    public const KIND_AFTER_SEND = 'scan_after_send';

    /**
     * @return array{
     *   corrected: Carbon, skew: ?int, tampered: bool, kind: ?string,
     *   confidence: string, offline: bool
     * }
     */
    public static function assess(Request $request, Carbon $serverNow): array
    {
        $tolerance = (int) config('attendance.clock.tolerance_seconds', 120);

        $scanned = $request->filled('scanned_at') ? Carbon::parse($request->scanned_at) : null;
        $device  = $request->filled('device_time') ? Carbon::parse($request->device_time) : null;

        $skew      = $device ? $device->timestamp - $serverNow->timestamp : null;
        $tampered  = false;
        $kind      = null;
        $corrected = $scanned ?? $serverNow->copy();

        if ($skew !== null && abs($skew) > $tolerance) {
            $tampered  = true;
            $kind      = self::KIND_SKEW;
            $corrected = $scanned ? $scanned->copy()->subSeconds($skew) : $serverNow->copy();
        } elseif ($scanned && $device && $scanned->timestamp > $device->timestamp + $tolerance) {
            // وقت المسح بعد وقت الإرسال بحسب الهاتف نفسه: مستحيل
            $tampered  = true;
            $kind      = self::KIND_AFTER_SEND;
            $corrected = $serverNow->copy();
        }

        $delay   = $serverNow->timestamp - $corrected->timestamp;
        $offline = $delay > $tolerance;

        $confidence = match (true) {
            $tampered                      => 'low',
            $device !== null               => 'high',
            $scanned === null || !$offline => 'high',   // مسح فوري دون أي مؤشر تلاعب
            default                        => 'medium', // مسح مؤجَّل من تطبيق قديم لا يرسل device_time: لا يمكن التحقق
        };

        return [
            'corrected'  => $corrected,
            'skew'       => $skew,
            'tampered'   => $tampered,
            'kind'       => $kind,
            'confidence' => $confidence,
            'offline'    => $offline,
        ];
    }
}
