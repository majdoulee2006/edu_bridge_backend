<?php

namespace App\Jobs;

use App\Models\StudyMaterial;
use App\Services\Study\StudyException;
use App\Services\Study\StudyMaterialGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** يولّد مسودة مادة المذاكرة في الخلفية. أي فشل يُسجَّل على المادة نفسها برسالة عربية يراها المعلم. */
class GenerateStudyMaterialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(public int $materialId)
    {
        $this->tries = (int) config('study.job_tries');
        $this->timeout = (int) config('study.job_timeout');
    }

    public function handle(StudyMaterialGenerator $generator): void
    {
        $material = StudyMaterial::with('lesson')->find($this->materialId);
        if (!$material || $material->status !== StudyMaterial::PENDING) {
            return;
        }

        try {
            $generator->generate($material);
        } catch (StudyException $e) {
            $material->update(['status' => StudyMaterial::FAILED, 'failure_reason' => mb_substr($e->getMessage(), 0, 250)]);
        } catch (\Throwable $e) {
            Log::error('Study material generation crashed: ' . $e->getMessage());
            $material->update(['status' => StudyMaterial::FAILED, 'failure_reason' => 'حدث خطأ غير متوقع أثناء التوليد.']);
        }
    }
}
