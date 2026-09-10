<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;

echo "⏳ جاري إنشاء ملفات PDF حقيقية وقابلة للتحميل للمحاضرات...\n";

// التأكد من وجود مجلد lectures في التخزين العام
if (!Storage::disk('public')->exists('lectures')) {
    Storage::disk('public')->makeDirectory('lectures');
}

// محتوى PDF مبسط لكل محاضرة
$pdfTemplates = [
    'الاضاءة والالوان 2' => "Edu-Bridge Institute - Lighting & Colors Course 2\nLecture Content & Study Guide.",
    'تاريخ العمارة والديكور' => "Edu-Bridge Institute - Architecture & Decor History\nClassical & Modern Decor Principles.",
    'برامج التصميم والإظهار المعماري 3D' => "Edu-Bridge Institute - 3D Architectural Rendering\n3D Modeling & Lighting Guide.",
    'نظريات التصميم الداخلي وتوزيع الفراغ' => "Edu-Bridge Institute - Interior Design Theories\nSpace Distribution & Ergonomics Manual.",
];

$lessons = Lesson::all();
foreach ($lessons as $index => $lesson) {
    $courseTitle = $lesson->course->title ?? 'المحاضرة';
    $fileName = 'lecture_' . $lesson->lesson_id . '.pdf';
    $filePath = 'lectures/' . $fileName;

    $content = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<>/Contents 4 0 R>>endobj 4 0 obj<</Length 85>>stream\nBT /Helv 12 Tf 50 700 TD (" . addslashes($courseTitle . " - " . $lesson->title) . ") Tj ET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000056 00000 n \n0000000111 00000 n \n0000000212 00000 n \ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n347\n%%EOF";

    Storage::disk('public')->put($filePath, $content);

    $lesson->update([
        'file_path' => $filePath,
        'file_name' => $lesson->title . '.pdf',
        'type'      => 'pdf',
    ]);
}

echo "✅ تم إنشاء جميع ملفات PDF الحقيقية المباشرة للتحميل وتحديث المحاضرات بها!\n";
