<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('logs:clean {--days=90}', function ($days = 90) {
    $cutoffDate = now()->subDays((int) $days);
    $deletedCount = \App\Models\UserActivity::where('created_at', '<', $cutoffDate)->delete();
    $this->info("تم حذف {$deletedCount} سجلاً قديماً تتجاوز عمرها {$days} يوماً بنجاح.");
})->purpose('تنظيف سجلات الحركة القديمة لحفظ كفاءة وسرعة قاعدة البيانات');

Schedule::command('logs:clean --days=90')->daily();

