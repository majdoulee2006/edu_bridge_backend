<?php

$firstNames = ['محمد', 'أحمد', 'عمر', 'خالد', 'عبدالله', 'فاطمة', 'عائشة', 'مريم', 'سارة', 'نورة', 'يوسف', 'طارق', 'حسن', 'حسين', 'منى', 'ريم', 'ندى', 'محمود', 'علي', 'ليلى'];
$lastNames = ['المحمد', 'الخالد', 'العلي', 'الحسن', 'العبدالله', 'السالم', 'النجار', 'الحداد', 'العبيد', 'المحمود', 'اليوسف', 'الطارق', 'الحسين', 'العمر', 'الصالح', 'الأحمد', 'الناصر', 'الدوسري', 'القحطاني', 'الزهراني'];

$filename = __DIR__ . '/student_accounts.csv';

// فتح الملف للكتابة
$file = fopen($filename, 'w');

// إضافة BOM لدعم اللغة العربية في برنامج Excel
fputs($file, "\xEF\xBB\xBF");

// كتابة الترويسة
fputcsv($file, ['الرقم', 'الرقم الجامعي', 'اسم الحساب', 'البريد الإلكتروني', 'كلمة السر', 'الدورة المسجل بها']);

$startId = 2026200;

for ($i = 0; $i < 20; $i++) {
    $uniId = (string)($startId + $i);
    $fName = $firstNames[$i % count($firstNames)];
    $lName = $lastNames[$i % count($lastNames)];
    $fullName = "$fName $lName";
    $email = "student{$uniId}@cis.edu";
    
    $courseNumber = floor($i / 5) + 1;
    $courseName = "دورة نظم معلومات " . $courseNumber;

    fputcsv($file, [
        $i + 1,
        $uniId,
        $fullName,
        $email,
        '12345678',
        $courseName
    ]);
}

fclose($file);

echo "تم إنشاء ملف الإكسل (CSV) بنجاح باسم: student_accounts.csv\n";
