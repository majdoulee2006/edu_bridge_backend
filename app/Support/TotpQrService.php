<?php

namespace App\Support;

class TotpQrService
{
    public const PROTOCOL_PREFIX = 'edu-bridge://attendance';
    public const DEFAULT_STEP = 30; // 30 ثانية لكل دورة رمز
    public const TRUNCATION_LENGTH = 20; // 20 خانة Hex = 10 بايت = 80 بت

    /**
     * حساب رقم العداد للوقت المعطى.
     */
    public static function calculateCounter(int $unixTime, int $step = self::DEFAULT_STEP): int
    {
        return intdiv($unixTime, $step);
    }

    /**
     * توليد الرمز المقتطع HMAC-SHA256 لعداد وجلسة محددين.
     */
    public static function generateToken(string $secret, string $sessionUuid, int $counter): string
    {
        $message = "edu-bridge-v2|{$sessionUuid}|{$counter}";
        $hash = hash_hmac('sha256', $message, $secret);

        return substr($hash, 0, self::TRUNCATION_LENGTH);
    }

    /**
     * توليد حمولة QR الكاملة للجلسة والوقت المحددين.
     */
    public static function generatePayload(
        string $secret,
        string $sessionUuid,
        int $unixTime,
        int $step = self::DEFAULT_STEP
    ): string {
        $counter = self::calculateCounter($unixTime, $step);
        $token   = self::generateToken($secret, $sessionUuid, $counter);

        return self::PROTOCOL_PREFIX . "?v=2&sid={$sessionUuid}&c={$counter}&t={$token}";
    }

    /**
     * تحليل حمولة QR واستخراج مكوناتها.
     *
     * @return array{version: int, sid: string, counter: int, token: string}|null
     */
    public static function parsePayload(string $payload): ?array
    {
        $trimmed = trim($payload);

        // فحص البادئة
        if (!str_starts_with($trimmed, self::PROTOCOL_PREFIX) && !str_contains($trimmed, 'v=2&sid=')) {
            return null;
        }

        $queryString = '';
        $pos = strpos($trimmed, '?');
        if ($pos !== false) {
            $queryString = substr($trimmed, $pos + 1);
        } else {
            $queryString = $trimmed;
        }

        parse_str($queryString, $params);

        if (!isset($params['v'], $params['sid'], $params['c'], $params['t'])) {
            return null;
        }

        return [
            'version' => (int) $params['v'],
            'sid'     => (string) $params['sid'],
            'counter' => (int) $params['c'],
            'token'   => (string) $params['t'],
        ];
    }

    /**
     * التحقق من صحة حمولة TOTP مع مراعاة انحراف ساعة الأجهزة (Tolerance Window).
     */
    public static function verify(
        string $payload,
        string $secret,
        string $sessionUuid,
        int $scannedAtUnix,
        int $toleranceWindows = 1
    ): bool {
        $parsed = self::parsePayload($payload);
        if (!$parsed) {
            return false;
        }

        if ($parsed['version'] !== 2 || $parsed['sid'] !== $sessionUuid) {
            return false;
        }

        $expectedCounter = self::calculateCounter($scannedAtUnix);
        $diff = abs($parsed['counter'] - $expectedCounter);

        // إذا كان الفارق أكبر من نافذة التسامح (مثلاً أكثر من دورة واحدة ±30 ثانية)
        if ($diff > $toleranceWindows) {
            return false;
        }

        // حساب الرمز المتوقع للعداد الموجود بالحمولة ومقارنته بزمن ثابت
        $expectedToken = self::generateToken($secret, $sessionUuid, $parsed['counter']);

        return hash_equals($expectedToken, $parsed['token']);
    }
}
