<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * حفظ صور الوجه (بيانات حيوية) في تخزين خاص غير قابل للوصول عبر الويب.
 * تُحفظ في storage/app/private/faces ولا تُخدَّم إلا عبر مسار محمي بصلاحية.
 */
class FaceImageStore
{
    private const MAX_BYTES = 3 * 1024 * 1024;

    /**
     * @param string $base64 صورة base64 (مع أو بدون بادئة data:image/...)
     * @return string|null المسار النسبي داخل القرص الخاص، أو null إن لم تكن صورة صالحة
     */
    public static function save(string $base64, string $prefix): ?string
    {
        $base64 = preg_replace('#^data:image/[a-z0-9.+-]+;base64,#i', '', trim($base64));
        $binary = base64_decode($base64, true);

        if ($binary === false || $binary === '' || strlen($binary) > self::MAX_BYTES) {
            return null;
        }

        // يجب أن يكون الملف صورة فعلاً (jpeg/png)، وليس أي محتوى آخر بامتداد صورة
        $info = @getimagesizefromstring($binary);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return null;
        }

        $ext  = $info[2] === IMAGETYPE_PNG ? 'png' : 'jpg';
        $name = preg_replace('/[^A-Za-z0-9_-]/', '', $prefix) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = 'faces/' . $name;

        Storage::disk('local')->put($path, $binary);

        return $path;
    }
}
