<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * توزيع تطبيق الأندرويد (APK) خارج Google Play.
 *
 * الملفات تعيش في storage/app/app-release/:
 *   - edubridge.apk      : نسخة arm64-v8a (الأغلبية الساحقة من الهواتف)
 *   - edubridge-v7a.apk  : نسخة armeabi-v7a (هواتف 32-بت القديمة) — اختيارية
 *   - release.json       : {"version_name","version_code","min_version_code","changelog"}
 * (نشر نسخة جديدة = استبدال الملفات فقط، بدون migration أو deploy.)
 *
 * ملاحظة: version_code هنا هو الرقم الأساسي من pubspec (الرقم بعد +)،
 * وليس versionCode الذي يضيف إليه Flutter إزاحة لكل ABI (1000/2000).
 */
class AppReleaseController extends Controller
{
    /** abi => اسم الملف */
    private const FILES = [
        'arm64' => 'edubridge.apk',
        'v7a'   => 'edubridge-v7a.apk',
    ];

    private function path(string $file): string
    {
        return storage_path('app/app-release') . DIRECTORY_SEPARATOR . $file;
    }

    private function abi(Request $request): string
    {
        $abi = (string) $request->query('abi', 'arm64');
        // إن طُلبت v7a ولم تكن موجودة نرجع إلى arm64 بدل الفشل
        if (!isset(self::FILES[$abi]) || !is_file($this->path(self::FILES[$abi]))) {
            return 'arm64';
        }
        return $abi;
    }

    private function release(string $abi = 'arm64'): ?array
    {
        $meta = $this->path('release.json');
        $apk  = $this->path(self::FILES[$abi]);
        if (!is_file($meta) || !is_file($apk)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($meta), true);
        if (!is_array($data) || !isset($data['version_code'], $data['version_name'])) {
            return null;
        }

        return [
            'version_name'     => (string) $data['version_name'],
            'version_code'     => (int) $data['version_code'],
            'min_version_code' => (int) ($data['min_version_code'] ?? 1),
            'changelog'        => (string) ($data['changelog'] ?? ''),
            'size_bytes'       => filesize($apk),
            'sha256'           => hash_file('sha256', $apk),
            'apk_url'          => url('/app/download') . ($abi === 'arm64' ? '' : '?abi=' . $abi),
        ];
    }

    /** GET /api/app-version[?abi=v7a] — يفحصه التطبيق عند الفتح. */
    public function version(Request $request): JsonResponse
    {
        $release = $this->release($this->abi($request));
        if (!$release) {
            return response()->json(['available' => false]);
        }

        return response()->json(['available' => true] + $release);
    }

    /** GET /app — صفحة التحميل البسيطة (للمشاركة عبر واتساب/QR). */
    public function page()
    {
        $release = $this->release('arm64');
        $v7a     = $release && is_file($this->path(self::FILES['v7a'])) ? $this->release('v7a') : null;

        return view('app-download', ['release' => $release, 'v7a' => $v7a]);
    }

    /** GET /app/download[?abi=v7a] — تحميل مباشر للـ APK. */
    public function download(Request $request)
    {
        $abi = $this->abi($request);
        if (!$this->release($abi)) {
            abort(404, 'التطبيق غير متوفر حالياً');
        }

        return response()->download(
            $this->path(self::FILES[$abi]),
            $abi === 'arm64' ? 'EduBridge.apk' : 'EduBridge-32bit.apk',
            ['Content-Type' => 'application/vnd.android.package-archive']
        );
    }
}
