<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Services\AttendanceSessionFinalizer;
use Illuminate\Console\Command;

class FinalizeAttendanceSessionsCommand extends Command
{
    /**
     * اسم الأمر في سطر الأوامر.
     *
     * @var string
     */
    protected $signature = 'attendance:finalize-sessions';

    /**
     * وصف وظيفة الأمر.
     *
     * @var string
     */
    protected $description = 'إغلاق جلسات الحضور الأوفلاين وتسجيل الغياب بعد انتهاء مهلة المزامنة (المرحلة 3)';

    public function handle(): int
    {
        $sessions = AttendanceSession::where('is_active', false)
            ->whereNull('finalized_at')
            ->where('offline_mode', '!=', 'none')
            ->whereNotNull('sync_deadline_at')
            ->where('sync_deadline_at', '<=', now())
            ->get();

        $count = $sessions->count();
        if ($count === 0) {
            $this->info('لا توجد جلسات انتهت مهلة مزامنتها حالياً.');
            return 0;
        }

        $totalAbsent = 0;
        foreach ($sessions as $session) {
            $absent = AttendanceSessionFinalizer::finalize($session);
            $totalAbsent += $absent;
            $this->line("تم إنهاء الجلسة #{$session->id} وتسجيل {$absent} غياب.");
        }

        $this->info("اكتمل إنهاء {$count} جلسة وتسجيل {$totalAbsent} غياب إجمالي.");
        return 0;
    }
}
