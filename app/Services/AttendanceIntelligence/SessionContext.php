<?php

namespace App\Services\AttendanceIntelligence;

use App\Models\AttendanceSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ما تحتاجه القواعد لتقييم جلسة واحدة: الجلسة نفسها، ومسوحاتها، وتاريخ الطالب السابق.
 */
class SessionContext
{
    /** @param Collection<int,\stdClass> $scans مسوحات الجلسة (حاضر/متأخر) */
    public function __construct(
        public readonly AttendanceSession $session,
        public readonly Collection $scans,
    ) {
    }

    /**
     * آخر مسوحات الطالب في الجلسات السابقة لهذه الجلسة (الأحدث أولًا). تُستعمل للقواعد التي تبحث عن نمط متكرر.
     *
     * @return Collection<int,\stdClass>
     */
    public function recentScans(int $studentId, int $limit): Collection
    {
        return DB::table('attendance')
            ->where('student_id', $studentId)
            ->whereNotNull('session_id')
            ->where('session_id', '<', $this->session->id) // التاريخ = ما قبل هذه الجلسة فقط
            ->whereIn('status', ['present', 'late'])
            ->orderByDesc('attendance_id')
            ->limit($limit)
            ->get(['attendance_id', 'session_id', 'face_score', 'qr_age_seconds']);
    }
}
