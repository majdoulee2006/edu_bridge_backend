<?php

namespace App\Console\Commands;

use App\Models\StudentRiskHistory;
use App\Models\StudentRiskScore;
use App\Services\Risk\RiskNotifier;
use App\Services\Risk\RiskScoreService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputeRiskScores extends Command
{
    protected $signature = 'risk:compute
        {--student= : حساب طالب واحد (student_id)}
        {--date= : تقييم "كأننا في" هذا التاريخ (YYYY-MM-DD)، الافتراضي اليوم}
        {--backfill-weeks=0 : إعادة بناء التاريخ بلقطة أسبوعية لآخر N أسبوعًا (من الأقدم)، بلا تنبيهات}
        {--no-notify : لا ترسل تنبيهات للمرشدين}';

    protected $description = 'يحسب درجة الخطر الأكاديمي لكل طالب نشط ويحفظها مع أسبابها، وينبّه المرشد عند ارتفاع الخطر';

    public function handle(RiskScoreService $service, RiskNotifier $notifier): int
    {
        if (!config('risk.enabled')) {
            $this->warn('الإنذار المبكر معطّل (RISK_ENABLED=false).');

            return self::SUCCESS;
        }

        $asOf  = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $weeks = (int) $this->option('backfill-weeks');

        $query = DB::table('students')
            ->join('users', 'users.user_id', '=', 'students.user_id')
            ->where('users.status', 'active')
            ->when($this->option('student'), fn ($q) => $q->where('students.student_id', (int) $this->option('student')))
            ->orderBy('students.student_id');

        $counts = ['high' => 0, 'medium' => 0, 'low' => 0, 'unknown' => 0];
        $rises  = [];
        $n      = 0;

        $query->select('students.student_id', 'students.user_id', 'users.full_name')->chunk(200, function ($students) use ($service, $asOf, $weeks, &$counts, &$rises, &$n) {
            foreach ($students as $st) {
                // إعادة بناء التاريخ: لقطات أسبوعية من الأقدم إلى الأحدث ثم اليوم
                foreach (range($weeks, 1) as $w) {
                    if ($weeks > 0) {
                        $service->refresh($st->student_id, $st->user_id, $asOf->copy()->subWeeks($w));
                    }
                }

                $out = $service->refresh($st->student_id, $st->user_id, $asOf);
                $row = $out['row'];
                $counts[$row->level]++;
                $n++;

                // --no-notify لا يغيّر حالة "ما أُرسل عنه": وإلا ضاع التنبيه نهائيًا دون أن يُرسَل
                if ($weeks === 0 && !$this->option('no-notify') && $this->shouldNotify($row)) {
                    $rises[] = ['student_id' => $st->student_id, 'name' => $st->full_name, 'level' => $row->level];
                }
            }
        });

        $notified = 0;
        if ($rises && !$this->option('no-notify')) {
            $notified = $notifier->notify($rises);
        }

        $pruned = StudentRiskHistory::where('computed_on', '<', now()->subDays((int) config('risk.history_keep_days'))->toDateString())->delete();

        $this->info("حُسبت درجة {$n} طالب: عالٍ {$counts['high']}، متوسط {$counts['medium']}، منخفض {$counts['low']}، بيانات غير كافية {$counts['unknown']}. "
            . 'مستلمو التنبيه: ' . $notified . ($pruned ? "، حُذف {$pruned} لقطة قديمة" : '') . '.');

        return self::SUCCESS;
    }

    /**
     * ينبّه مرة واحدة عند دخول مستوى مُنبَّه (high افتراضيًا) أو الارتفاع إليه.
     * إذا انخفض المستوى يُعاد ضبط ما أُرسل عنه ليُنبَّه من جديد عند أي ارتفاع لاحق.
     */
    private function shouldNotify(StudentRiskScore $row): bool
    {
        $levels = (array) config('risk.notify_levels', ['high']);
        $rank = StudentRiskScore::rank($row->level);
        $sent = StudentRiskScore::rank($row->notified_level);

        if (in_array($row->level, $levels, true) && $rank > $sent) {
            $row->forceFill(['notified_level' => $row->level])->save();

            return true;
        }

        if ($rank < $sent) {
            $row->forceFill(['notified_level' => $row->level])->save();
        }

        return false;
    }
}
