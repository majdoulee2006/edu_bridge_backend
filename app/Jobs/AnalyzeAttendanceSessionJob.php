<?php

namespace App\Jobs;

use App\Models\AttendanceSession;
use App\Services\AttendanceIntelligence\SessionAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeAttendanceSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(public int $sessionId)
    {
    }

    public function handle(SessionAnalyzer $analyzer): void
    {
        if (!config('attendance_intelligence.enabled')) {
            return;
        }

        $session = AttendanceSession::find($this->sessionId);
        if ($session) {
            $analyzer->analyze($session);
        }
    }
}
