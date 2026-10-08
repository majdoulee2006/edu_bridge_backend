<?php

namespace App\Jobs;

use App\Models\Parents;
use App\Models\Student;
use App\Services\Digest\DigestService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendParentDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        public int $parentId,
        public int $studentId,
        public string $weekStart,
    ) {
    }

    public function handle(DigestService $service): void
    {
        $parent  = Parents::find($this->parentId);
        $student = Student::with('user')->find($this->studentId);

        if (!$parent || !$student) {
            return;
        }

        $service->process($parent, $student, Carbon::parse($this->weekStart));
    }
}
