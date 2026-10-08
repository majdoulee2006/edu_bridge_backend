<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Services\AttendanceIntelligence\SessionAnalyzer;
use Illuminate\Console\Command;

class AnalyzeAttendanceSessions extends Command
{
    protected $signature = 'attendance:analyze
        {--session= : تحليل جلسة واحدة بمعرّفها (حتى لو حُلّلت من قبل)}
        {--all : إعادة تحليل كل الجلسات المنتهية}';

    protected $description = 'يحلّل جلسات الحضور المنتهية بحثًا عن أنماط مشبوهة ويحفظ النتائج للمراجعة';

    public function handle(SessionAnalyzer $analyzer): int
    {
        if (!config('attendance_intelligence.enabled')) {
            $this->warn('تحليل الحضور معطّل (ATTENDANCE_INTEL_ENABLED=false).');

            return self::SUCCESS;
        }

        if ($id = $this->option('session')) {
            $session = AttendanceSession::find($id);
            if (!$session) {
                $this->error("الجلسة {$id} غير موجودة.");

                return self::FAILURE;
            }
            $this->info("الجلسة {$id}: " . $analyzer->analyze($session) . ' اشتباه جديد.');

            return self::SUCCESS;
        }

        // الجلسة "منتهية" إذا أُغلقت يدويًا أو انقضت مدتها (10 دقائق) منذ أكثر من دقيقة
        $query = AttendanceSession::query()
            ->where(function ($q) {
                $q->whereNotNull('closed_at')
                  ->orWhere('session_expires_at', '<', now()->subMinute());
            });

        if (!$this->option('all')) {
            $query->whereNull('analyzed_at');
        }

        $sessions = 0;
        $flags = 0;
        $query->orderBy('id')->each(function (AttendanceSession $s) use ($analyzer, &$sessions, &$flags) {
            $flags += $analyzer->analyze($s);
            $sessions++;
        });

        $this->info("حُلّلت {$sessions} جلسة، وأُضيف {$flags} اشتباه جديد.");

        return self::SUCCESS;
    }
}
