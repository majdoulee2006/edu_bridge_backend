<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SecureFaceImages extends Command
{
    protected $signature   = 'faces:secure {--dry-run : عرض ما سيحدث بدون تنفيذ} {--source= : مجلد الصور القديمة (الافتراضي public/uploads/faces)}';
    protected $description = 'ينقل صور الوجه القديمة من public/uploads/faces إلى التخزين الخاص (storage/app/private/faces) ويحدّث سجلات الحضور';

    public function handle(): int
    {
        $dry    = (bool) $this->option('dry-run');
        $dir    = $this->option('source') ?: public_path('uploads/faces');
        $moved  = 0;
        $orphan = 0;

        if (!is_dir($dir)) {
            $this->info('لا يوجد مجلد صور قديم. لا شيء للنقل.');
            return self::SUCCESS;
        }

        foreach (File::files($dir) as $file) {
            $name   = $file->getFilename();
            $oldRef = 'uploads/faces/' . $name;
            $newRef = 'faces/' . $name;

            $rows = DB::table('attendance')->where('face_image', $oldRef)->count();
            $rows ? $moved++ : $orphan++;

            $this->line(($dry ? '[dry-run] ' : '') . "{$oldRef} -> {$newRef} ({$rows} سجل)");

            if ($dry) {
                continue;
            }

            Storage::disk('local')->put($newRef, File::get($file->getRealPath()));
            DB::table('attendance')->where('face_image', $oldRef)->update(['face_image' => $newRef]);
            File::delete($file->getRealPath());
        }

        $this->info("تم: {$moved} صورة مرتبطة بسجلات، و{$orphan} صورة غير مرتبطة" . ($dry ? ' (تجربة فقط)' : '') . '.');

        return self::SUCCESS;
    }
}
