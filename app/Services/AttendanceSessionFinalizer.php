<?php

namespace App\Services;

use App\Jobs\AnalyzeAttendanceSessionJob;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Support\Access;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * دورة حياة جلسة الحضور بعد انتهاء المحاضرة:
 *
 *  1. close():    المعلم ينهي الجلسة (أو تنتهي مدتها). يُغلق مسح الـ QR الجديد، ولا يُسجَّل أي غياب بعد.
 *                 من لم يمسح بعد يبقى "قيد المزامنة" حتى منتصف الليل: قد يكون مسح بلا إنترنت وسيزامن.
 *  2. finalize(): عند الموعد النهائي (نهاية يوم الجلسة، 12 ليلاً) يُسجَّل غياب من ليس له سجل، مع مراعاة الإجازات
 *                 المعتمدة، فتعمل إنذارات الغياب وإشعارات الأهل ليلاً. هذا المسار الوحيد لتسجيل غياب الجلسات،
 *                 ويعمل حتى لو نسي المعلم إنهاء الجلسة.
 *
 * بعد finalize() يُرفض أي مسح متأخر (sync_window_closed)، والمعلم يملك زر "استثناء" بسبب مكتوب.
 */
class AttendanceSessionFinalizer
{
    /** الموعد النهائي لمزامنة المسح: نهاية يوم إنشاء الجلسة ما لم يُحدَّد موعد صريح. */
    public static function deadlineFor(AttendanceSession $session): Carbon
    {
        if ($session->sync_deadline_at) {
            return $session->sync_deadline_at->copy();
        }

        return ($session->created_at ?? now())->copy()->endOfDay();
    }

    /** هل أُغلقت نافذة المسح/المزامنة لهذه الجلسة؟ */
    public static function windowClosed(AttendanceSession $session): bool
    {
        return $session->finalized_at !== null || now()->greaterThan(self::deadlineFor($session));
    }

    /**
     * إنهاء الجلسة من جهة المعلم دون تسجيل غياب. آمن للتكرار: لا يمدّد الموعد النهائي ولا يعيد فتح جلسة مُنتهية.
     */
    public static function close(AttendanceSession $session): AttendanceSession
    {
        if ($session->finalized_at !== null) {
            return $session;
        }

        $session->update([
            'is_active'        => false,
            'closed_at'        => $session->closed_at ?? now(),
            'sync_deadline_at' => self::deadlineFor($session),
        ]);

        // تحليل الأنماط المشبوهة (يُعاد بعد الإنهاء النهائي لأن مسوحًا متأخرة قد تصل)
        AnalyzeAttendanceSessionJob::dispatch($session->id);

        return $session;
    }

    /**
     * طلاب المقرر المعنيون بالجلسة: كل من يحق له المسح فيها، أي من تنجح عنده Student::checkCourseEligibility
     * (اختصاص المقرر، السنة، التسجيل غير المنسحب). المصدر الوحيد للقاعدة، فمن يُسجَّل غائبًا هو من كان
     * بمقدوره أن يحضر.
     *
     * @return array<int, object{student_id:int, user_id:int}>
     */
    public static function eligibleStudents(int $courseId): array
    {
        $course = DB::table('courses')->where('course_id', $courseId)->first();
        if (!$course) {
            return [];
        }

        $enrolledIds = DB::table('enrollments')
            ->where('course_id', $courseId)
            ->where('status', '!=', 'dropped')
            ->pluck('student_id');

        return Student::with('user')->whereIn('student_id', $enrolledIds)->get()
            ->filter(fn (Student $s) => $s->checkCourseEligibility($course)['eligible'])
            ->map(fn (Student $s) => (object) ['student_id' => (int) $s->student_id, 'user_id' => (int) $s->user_id])
            ->values()
            ->all();
    }

    /**
     * الإنهاء النهائي: تسجيل الغياب لمن ليس له سجل، وإغلاق الجلسة نهائيًا.
     *
     * @return int عدد الغيابات المسجَّلة
     */
    public static function finalize(AttendanceSession $session): int
    {
        if ($session->finalized_at !== null) {
            return 0;
        }

        $lesson = $session->lesson;
        if (!$lesson || !$lesson->course) {
            $session->update(['is_active' => false, 'finalized_at' => now()]);

            return 0;
        }

        $sessionDate = ($session->created_at ?? now())->toDateString();
        $recorded    = Attendance::where('lesson_id', $lesson->lesson_id)
            ->whereDate('attendance_date', $sessionDate)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $absentCount = 0;

        foreach (self::eligibleStudents((int) $lesson->course_id) as $student) {
            if (in_array((int) $student->student_id, $recorded, true)) {
                continue;
            }

            $excused = Access::studentHasApprovedLeave($student->student_id, $student->user_id, $sessionDate);

            // Attendance::create (وليس insert) كي يعمل AttendanceObserver: إشعار الطالب وإنذارات الغياب
            Attendance::create([
                'student_id'      => $student->student_id,
                'lesson_id'       => $lesson->lesson_id,
                'session_id'      => $session->id,
                'attendance_date' => $sessionDate,
                'status'          => 'absent',
                'excuse_status'   => $excused ? 'approved' : 'none',
                'source'          => 'online',
            ]);

            $absentCount++;
        }

        $session->update([
            'is_active'    => false,
            'closed_at'    => $session->closed_at ?? now(),
            'finalized_at' => now(),
        ]);

        AnalyzeAttendanceSessionJob::dispatch($session->id);

        return $absentCount;
    }
}
