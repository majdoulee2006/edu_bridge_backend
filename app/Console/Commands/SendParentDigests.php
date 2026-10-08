<?php

namespace App\Console\Commands;

use App\Jobs\SendParentDigestJob;
use App\Models\Parents;
use App\Services\Digest\DigestBuilder;
use App\Services\Digest\DigestComposer;
use App\Services\Digest\DigestService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendParentDigests extends Command
{
    protected $signature = 'digest:send
        {--week= : أي تاريخ داخل الأسبوع المطلوب (الافتراضي: الأسبوع الحالي)}
        {--parent= : معرّف user_id لولي أمر واحد فقط}
        {--now : تنفيذ فوري بدل وضع المهام في الطابور}
        {--dry-run : عرض النص الناتج فقط دون حفظ أو إرسال}';

    protected $description = 'يرسل الملخص الأسبوعي (حضور، واجبات، علامات) لأولياء الأمور عن أبنائهم';

    public function handle(DigestService $service, DigestBuilder $builder, DigestComposer $composer): int
    {
        if (!config('digest.enabled') && !$this->option('dry-run')) {
            $this->warn('الملخص الأسبوعي معطّل (DIGEST_ENABLED=false).');

            return self::SUCCESS;
        }

        $weekStart = DigestBuilder::weekStartFor($this->option('week') ? Carbon::parse($this->option('week')) : now());

        $query = Parents::with(['user', 'students.user'])
            ->where('digest_enabled', true)
            ->whereHas('user', fn ($q) => $q->where('status', 'active'));

        if ($this->option('parent')) {
            $query->where('user_id', (int) $this->option('parent'));
        }

        $queued = $done = $skipped = 0;

        foreach ($query->cursor() as $parent) {
            foreach ($parent->students as $student) {
                if (($student->user->status ?? null) !== 'active') {
                    continue;
                }

                if ($this->option('dry-run')) {
                    $facts = $builder->build($student->student_id, $weekStart);
                    if ($facts['empty']) {
                        $skipped++;
                        continue;
                    }
                    $out = $composer->compose($facts, $student->user->full_name ?? '');
                    $this->line("== ولي الأمر #{$parent->user_id} / طالب #{$student->student_id} [{$out['source']}] ==");
                    $this->line($out['title']);
                    $this->line($out['body']);
                    $this->newLine();
                    $done++;
                    continue;
                }

                if ($this->option('now')) {
                    $service->process($parent, $student, $weekStart) === DigestService::SENT ? $done++ : $skipped++;
                } else {
                    SendParentDigestJob::dispatch($parent->parent_id, $student->student_id, $weekStart->toDateString());
                    $queued++;
                }
            }
        }

        $this->info("الأسبوع {$weekStart->toDateString()}: منفَّذ/معروض {$done}، في الطابور {$queued}، متخطّى {$skipped}.");

        return self::SUCCESS;
    }
}
