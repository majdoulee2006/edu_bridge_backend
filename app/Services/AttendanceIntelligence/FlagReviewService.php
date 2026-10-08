<?php

namespace App\Services\AttendanceIntelligence;

use App\Models\Attendance;
use App\Models\AttendanceFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * منطق مراجعة الاشتباهات المشترك بين واجهة الـ API (التطبيق) وصفحات الويب.
 * التأكيد لا يغيّر الحضور إلا بطلب صريح من المعلّم (markAbsent).
 */
class FlagReviewService
{
    /** استعلام اشتباهات مقررات المعلّم مرتبة بالأخطر ثم الأحدث. */
    public function queryForCourses(array $courseIds, string $status = 'open', ?string $severity = null, ?int $courseId = null): Builder
    {
        return AttendanceFlag::with(['student.user:user_id,full_name', 'course:course_id,title', 'attendance'])
            ->whereIn('course_id', $courseIds)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($severity, fn ($q) => $q->where('severity', $severity))
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->orderByRaw("FIELD(severity, 'high', 'medium', 'low')")
            ->orderByDesc('id');
    }

    /** @return array{open:int, by_severity:array{high:int,medium:int,low:int}} */
    public function openSummary(array $courseIds): array
    {
        $counts = AttendanceFlag::whereIn('course_id', $courseIds)->where('status', AttendanceFlag::OPEN)
            ->selectRaw('severity, COUNT(*) AS n')->groupBy('severity')->pluck('n', 'severity');

        return [
            'open' => (int) $counts->sum(),
            'by_severity' => [
                'high' => (int) ($counts['high'] ?? 0),
                'medium' => (int) ($counts['medium'] ?? 0),
                'low' => (int) ($counts['low'] ?? 0),
            ],
        ];
    }

    /**
     * يسجّل قرار المعلّم على اشتباه مفتوح.
     *
     * @throws \DomainException إذا كان الاشتباه قد رُوجع من قبل
     */
    public function review(AttendanceFlag $flag, User $reviewer, string $status, ?string $note, bool $markAbsent): AttendanceFlag
    {
        if ($flag->status !== AttendanceFlag::OPEN) {
            throw new \DomainException('تمت مراجعة هذا الاشتباه مسبقًا');
        }

        $markAbsent = $status === AttendanceFlag::CONFIRMED && $markAbsent;

        DB::transaction(function () use ($flag, $reviewer, $status, $note, $markAbsent) {
            $flag->update([
                'status'      => $status,
                'review_note' => $note,
                'reviewed_by' => $reviewer->user_id,
                'reviewed_at' => now(),
                'details'     => array_merge($flag->details ?? [], ['marked_absent' => $markAbsent]),
            ]);

            if ($markAbsent) {
                // عبر الموديل ليعمل AttendanceObserver (إشعار الطالب وتحذيرات الغياب)
                Attendance::where('attendance_id', $flag->attendance_id)->first()?->update(['status' => 'absent']);
            }
        });

        return $flag->fresh(['student.user:user_id,full_name', 'course:course_id,title', 'attendance']);
    }
}
