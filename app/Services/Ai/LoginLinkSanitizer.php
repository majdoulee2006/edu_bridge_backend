<?php

namespace App\Services\Ai;

/**
 * بوابة الويب الوحيدة هي /login لكل الأدوار. أي رابط دخول قديم خاص بدور
 * (/student/login ...) أو دومين وهمي يكتبه النموذج يُستبدل بالرابط الفعلي.
 */
class LoginLinkSanitizer
{
    public const LOGIN_PATH = '/login';

    /** مسارات قديمة لم تعد موجودة */
    private const LEGACY_PATHS = [
        '/student/login', '/teacher/login', '/parent/login', '/parents/login',
        '/hod/login', '/affairs/login', '/admin/login',
    ];

    public function sanitize(string $text, string $role, string $baseHttp): string
    {
        $baseHttp = rtrim($baseHttp, '/');
        $url      = $baseHttp . self::LOGIN_PATH;
        $legacy   = implode('|', array_map(fn ($p) => preg_quote($p, '#'), self::LEGACY_PATHS));

        // 1) دومينات وهمية (edubridge.edu / .com ...) → الرابط الفعلي
        $text = preg_replace(
            '#https?://(?:www\.)?edubridge\.(?:edu|com|org|local)(?::\d+)?(?:' . $legacy . '|/login)#i',
            $url,
            $text
        );

        // 2) روابط كاملة لمسارات الدخول القديمة → /login على مضيفنا
        $text = preg_replace('#https?://[^\s`"\'\)\]<>]+(?:' . $legacy . ')#i', $url, $text);

        // 3) المسار القديم وحده (بدون مضيف)
        $text = preg_replace('#(?<![\w/.])(?:' . $legacy . ')(?![\w-])#i', self::LOGIN_PATH, $text);

        // 4) عنوان محلي/قديم لـ /login → المضيف الحالي
        $text = preg_replace(
            '#https?://(?:192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+|172\.(?:1[6-9]|2\d|3[01])\.\d+\.\d+|127\.0\.0\.1|localhost)(?::\d+)?/login#i',
            $url,
            $text
        );

        return $text;
    }
}
