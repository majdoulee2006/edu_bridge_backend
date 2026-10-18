<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Services\AttendanceSessionFinalizer;
use Illuminate\Console\Command;

class FinalizeAttendanceSessionsCommand extends Command
{
    protected $signature = 'attendance:finalize-sessions';

    protected $description = 'إنهاء جلسات الحضور التي بلغت موعدها النهائي (12 ليلاً من يوم الجلسة) وتسجيل غياب من لم يسجّل، حتى لو نسي المعلم إنهاءها';

    public function handle(): int
    {
        // الجلسة تُنهى نهائيًا إذا: لها موعد نهائي مضى، أو بلا موعد (لم ينهِها المعلم) وأُنشئت قبل اليوم.
        $sessions = AttendanceSession::whereNull('finalized_at')
            ->where(function ($q) {
                $q->where(fn ($w) => $w->whereNotNull('sync_deadline_at')->where('sync_deadline_at', '<=', now()))
                    ->orWhere(fn ($w) => $w->whereNull('sync_deadline_at')->where('created_at', '<', now()->startOfDay()));
            })
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('لا توجد جلسات بلغت موعدها النهائي.');

            return self::SUCCESS;
        }

        $total = 0;
        foreach ($sessions as $session) {
            $absent = AttendanceSessionFinalizer::finalize($session);
            $total += $absent;
            $this->line("الجلسة #{$session->id}: سُجّل {$absent} غياب.");
        }

        $this->info("اكتمل إنهاء {$sessions->count()} جلسة وتسجيل {$total} غياب.");

        return self::SUCCESS;
    }
}
