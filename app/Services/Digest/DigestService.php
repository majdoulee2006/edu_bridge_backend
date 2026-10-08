<?php

namespace App\Services\Digest;

use App\Models\Notification;
use App\Models\ParentDigest;
use App\Models\Parents;
use App\Models\Student;
use App\Services\FcmService;
use App\Support\Access;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ينسّق دورة ملخص واحد: حقائق ← نص ← حفظ ← إشعار.
 * آمن لإعادة التشغيل: ملخص واحد لكل (ولي أمر، طالب، أسبوع).
 */
class DigestService
{
    public const SENT          = 'sent';
    public const SKIPPED_EMPTY = 'skipped_empty';
    public const SKIPPED_DONE  = 'skipped_done';
    public const SKIPPED_OPTED = 'skipped_opted_out';

    public function __construct(
        protected DigestBuilder $builder,
        protected DigestComposer $composer,
    ) {
    }

    /** @return string إحدى ثوابت الحالة أعلاه */
    public function process(Parents $parent, Student $student, Carbon $weekStart): string
    {
        if (!$parent->digest_enabled) {
            return self::SKIPPED_OPTED;
        }

        $existing = ParentDigest::where('parent_user_id', $parent->user_id)
            ->where('student_id', $student->student_id)
            ->where('week_start', $weekStart->toDateString())
            ->first();

        if ($existing && $existing->sent_at) {
            return self::SKIPPED_DONE;
        }

        $facts = $this->builder->build($student->student_id, $weekStart);
        if ($facts['empty']) {
            return self::SKIPPED_EMPTY;
        }

        $content = $this->composer->compose($facts, $student->user->full_name ?? '');

        $digest = ParentDigest::updateOrCreate(
            [
                'parent_user_id' => $parent->user_id,
                'student_id'     => $student->student_id,
                'week_start'     => $weekStart->toDateString(),
            ],
            [
                'week_end' => $facts['week_end'],
                'facts'    => $facts,
                'tone'     => $facts['tone'],
                'title'    => $content['title'],
                'body'     => $content['body'],
                'source'   => $content['source'],
            ]
        );

        $this->deliver($digest);

        return self::SENT;
    }

    protected function deliver(ParentDigest $digest): void
    {
        DB::transaction(function () use ($digest) {
            Notification::create([
                'user_id'    => $digest->parent_user_id,
                'sender_id'  => Access::systemSenderId(),
                'title'      => $digest->title,
                'message'    => $digest->body,
                'type'       => 'weekly_digest',
                'category'   => 'academic',
                'related_id' => $digest->id,
                'is_read'    => false,
            ]);

            $digest->update(['sent_at' => now()]);
        });

        // الدفع وتيليغرام خارج المعاملة: فشلهما لا يلغي الملخص المحفوظ داخل التطبيق
        try {
            FcmService::sendToUser($digest->parent_user_id, $digest->title, $digest->body, [
                'type'       => 'weekly_digest',
                'id'         => (string) $digest->id,
                'related_id' => (string) $digest->id, // التطبيق يقرأ related_id لفتح الشاشة المناسبة
            ]);
        } catch (\Throwable $e) {
            Log::warning('Digest push failed: ' . $e->getMessage());
        }
    }
}
