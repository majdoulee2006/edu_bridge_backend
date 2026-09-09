<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

echo "⏳ جاري تنزيل ملف PDF حقيقي واحترافي سليم 100% لتجربة التحميل...\n";

// تنزيل ملف PDF حقيقي سليم من الإنترنت (مستند قياسي معتمد)
$validPdfUrl = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
$pdfData = @file_get_contents($validPdfUrl);

if (!$pdfData) {
    // إذا لم يتوفر اتصال بالأنترنت، نستخدم هيدر PDF قياسي بدون ترميز عربي معقد يسبب خطأ القراءة
    $pdfData = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<>/Contents 4 0 R>>endobj 4 0 obj<</Length 44>>stream\nBT /Helv 12 Tf 50 700 TD (Edu-Bridge Lecture File) Tj ET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000056 00000 n \n0000000111 00000 n \n0000000212 00000 n \ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n306\n%%EOF";
}

if (!Storage::disk('public')->exists('lectures')) {
    Storage::disk('public')->makeDirectory('lectures');
}

$lessons = Lesson::all();

foreach ($lessons as $lesson) {
    $fileName = 'lecture_' . $lesson->lesson_id . '.pdf';
    $filePath = 'lectures/' . $fileName;

    Storage::disk('public')->put($filePath, $pdfData);

    $lesson->update([
        'file_path' => $filePath,
        'file_name' => 'Lecture_' . $lesson->lesson_id . '.pdf',
        'type'      => 'pdf',
    ]);
}

echo "✅ تم تحديث جميع المحاضرات بملفات PDF قياسية وسليمة 100% جاهزة للفتح والتنزيل!\n";
