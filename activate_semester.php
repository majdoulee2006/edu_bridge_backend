<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Semester;

Semester::query()->update(['is_active' => false]);
Semester::where('semester_id', 1)->update(['is_active' => true]);

echo "✅ تم تفعيل الفصل الأول 2026 بنجاح!\n";
