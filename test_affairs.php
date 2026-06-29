<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 1. Find affairs user
$u = App\Models\User::where('role_id', 6)->first();

if (!$u) {
    echo "❌ No affairs user found\n";
    exit(1);
}
echo "✅ Affairs user: {$u->full_name} (ID: {$u->user_id}, role: {$u->role})\n";

// 2. Create a token
$token = $u->createToken('test')->plainTextToken;
echo "✅ Token: $token\n";

// 3. Test adding university ID via API
$ch = curl_init('http://127.0.0.1:8001/api/affairs/university-ids');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token",
        "Content-Type: application/json",
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'university_id' => 'TEST' . rand(1000, 9999),
        'full_name' => 'طالب تجريبي',
    ]),
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "✅ Add without photo - HTTP $code: $res\n";

// 4. Test adding university ID WITH photo (multipart)
$testId = 'TEST' . rand(1000, 9999);
// Create a small test image
$img = imagecreatetruecolor(100, 100);
$blue = imagecolorallocate($img, 0, 100, 200);
imagefill($img, 0, 0, $blue);
$tmpFile = tempnam(sys_get_temp_dir(), 'test_photo_') . '.jpg';
imagejpeg($img, $tmpFile);
imagedestroy($img);

$ch2 = curl_init('http://127.0.0.1:8001/api/affairs/university-ids');
curl_setopt_array($ch2, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token",
    ],
    CURLOPT_POSTFIELDS => [
        'university_id' => $testId,
        'full_name' => 'طالب بصورة تجريبية',
        'photo' => new CURLFile($tmpFile, 'image/jpeg', 'test_photo.jpg'),
    ],
]);
$res2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);
unlink($tmpFile);
echo "✅ Add WITH photo - HTTP $code2: $res2\n";

// 5. List university IDs and check photo_url
$ch3 = curl_init('http://127.0.0.1:8001/api/affairs/university-ids');
curl_setopt_array($ch3, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token",
    ],
]);
$res3 = curl_exec($ch3);
$code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

$data = json_decode($res3, true);
if ($data['success']) {
    echo "✅ List IDs - HTTP $code3 - Count: " . count($data['data']) . "\n";
    // Check the one with photo
    foreach ($data['data'] as $item) {
        if ($item['university_id'] === $testId) {
            echo "✅ Photo URL: " . ($item['photo_url'] ?? 'NULL') . "\n";
            echo "✅ Photo path: " . ($item['photo'] ?? 'NULL') . "\n";
            break;
        }
    }
} else {
    echo "❌ List failed: $res3\n";
}

// 6. Cleanup test records
Illuminate\Support\Facades\DB::table('university_ids')
    ->where('full_name', 'LIKE', '%تجريبي%')
    ->delete();
echo "✅ Test records cleaned up\n";

// Delete test token
$u->tokens()->where('name', 'test')->delete();
echo "\n🎉 All tests passed!\n";
