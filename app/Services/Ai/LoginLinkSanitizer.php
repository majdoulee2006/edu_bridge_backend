<?php

namespace App\Services\Ai;

/**
 * يضمن ألا يحوي رد المساعد إلا رابط الدخول المخصص لدور المستخدم،
 * ويصحح الدومينات الوهمية وعناوين localhost القديمة.
 */
class LoginLinkSanitizer
{
    public const ROLE_PATHS = [
        'student' => '/student/login',
        'teacher' => '/teacher/login',
        'parent'  => '/parent/login',
        'hod'     => '/hod/login',
        'affairs' => '/affairs/login',
        'admin'   => '/admin/login',
    ];

    public static function pathFor(string $role): string
    {
        return self::ROLE_PATHS[AiRole::normalize($role)] ?? self::ROLE_PATHS['student'];
    }

    public function sanitize(string $text, string $role, string $baseHttp): string
    {
        $baseHttp    = rtrim($baseHttp, '/');
        $allowedPath = self::pathFor($role);
        $allowedUrl  = $baseHttp . $allowedPath;
        $anyPath     = implode('|', array_map(fn ($p) => preg_quote($p, '#'), array_values(self::ROLE_PATHS)));

        // 1) دومينات وهمية (edubridge.edu / .com ...) → الرابط الفعلي
        $text = preg_replace(
            '#https?://(?:www\.)?edubridge\.(?:edu|com|org|local)(?::\d+)?(' . $anyPath . '|/login)#i',
            $baseHttp . '$1',
            $text
        );

        // 2) أي رابط كامل لبوابة دور آخر → بوابة دور المستخدم
        foreach (self::ROLE_PATHS as $path) {
            if ($path === $allowedPath) {
                continue;
            }
            $text = preg_replace('#https?://[^\s`"\'\)\]<>]+' . preg_quote($path, '#') . '#i', $allowedUrl, $text);
            // 3) المسار وحده (بدون مضيف)
            $text = preg_replace('#(?<![\w/.])' . preg_quote($path, '#') . '(?![\w-])#i', $allowedPath, $text);
        }

        // 4) عنوان قديم/محلي لبوابته أو /login → المضيف الحالي
        $text = preg_replace(
            '#https?://(?:192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+|172\.(?:1[6-9]|2\d|3[01])\.\d+\.\d+|127\.0\.0\.1|localhost)(?::\d+)?('
                . preg_quote($allowedPath, '#') . '|/login)#i',
            $baseHttp . '$1',
            $text
        );

        return $text;
    }
}
