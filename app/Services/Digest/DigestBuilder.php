<?php

namespace App\Services\Digest;

use App\Services\AbsenceWarningService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * يجمّع حقائق أسبوع طالب واحد (حضور، واجبات، علامات) كمصفوفة بسيطة.
 *
 * هذا هو مصدر الحقيقة الوحيد للملخص: كل رقم يظهر لولي الأمر يخرج من هنا،
 * أما صياغة النص فتتم في DigestComposer ولا تضيف أي معلومة جديدة.
 */
class DigestBuilder
{
    /** بداية الأسبوع الدراسي (الأحد) للتاريخ المعطى. */
    public static function weekStartFor(Carbon $date): Carbon
    {
        return $date->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();
    }

    /**
     * @return array<string,mixed> الحقائق؛ المفتاح empty=true يعني لا نشاط يستحق الإرسال
     */
    public function build(int $studentId, Carbon $weekStart, ?Carbon $now = null): array
    {
        $now   = $now ?? now();
        $start = $weekStart->copy()->startOfDay();
        $end   = $start->copy()->addDays(6)->endOfDay();

        $attendance  = $this->attendance($studentId, $start, $end);
        $assignments = $this->assignments($studentId, $start, $end, $now);
        $grades      = $this->grades($studentId, $start, $end);

        $hasActivity = $attendance['total'] > 0
            || $assignments['due_this_week'] > 0
            || $assignments['upcoming'] > 0
            || $grades['count'] > 0;

        $facts = [
            'week_start'  => $start->toDateString(),
            'week_end'    => $end->toDateString(),
            'attendance'  => $attendance,
            'assignments' => $assignments,
            'grades'      => $grades,
        ];

        $facts['tone']       = $this->tone($facts);
        $facts['suggestion'] = $this->suggestion($facts);
        $facts['empty']      = !$hasActivity;

        return $facts;
    }

    protected function attendance(int $studentId, Carbon $start, Carbon $end): array
    {
        $rows = DB::table('attendance')
            ->where('student_id', $studentId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get(['status', 'excuse_status']);

        $total   = $rows->count();
        $present = $rows->where('status', 'present')->count();
        $late    = $rows->where('status', 'late')->count();
        $absent  = $rows->where('status', 'absent')->count();
        $excused = $rows->where('status', 'absent')->where('excuse_status', 'approved')->count();

        $prev = DB::table('attendance')
            ->where('student_id', $studentId)
            ->whereBetween('attendance_date', [
                $start->copy()->subDays(7)->toDateString(),
                $start->copy()->subDay()->toDateString(),
            ])
            ->get(['status']);

        $rate     = $total > 0 ? (int) round((($present + $late) / $total) * 100) : null;
        $prevRate = $prev->count() > 0
            ? (int) round(($prev->whereIn('status', ['present', 'late'])->count() / $prev->count()) * 100)
            : null;

        $totalAbsenceDays = AbsenceWarningService::countAbsenceDays($studentId);

        return [
            'total'              => $total,
            'present'            => $present,
            'late'               => $late,
            'absent'             => $absent,
            'excused_absent'     => $excused,
            'unexcused_absent'   => $absent - $excused,
            'rate'               => $rate,
            'prev_rate'          => $prevRate,
            'absence_days_total' => $totalAbsenceDays,
            'days_to_warning'    => max(0, AbsenceWarningService::FIRST_WARNING_DAYS - $totalAbsenceDays),
        ];
    }

    protected function assignments(int $studentId, Carbon $start, Carbon $end, Carbon $now): array
    {
        $empty = ['due_this_week' => 0, 'submitted' => 0, 'late' => 0, 'missing' => 0, 'upcoming' => 0, 'upcoming_items' => []];

        $courseIds = DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->pluck('course_id');

        if ($courseIds->isEmpty()) {
            return $empty;
        }

        $dueThisWeek = DB::table('assignments')
            ->whereIn('course_id', $courseIds)
            ->whereBetween('due_date', [$start, $end])
            ->get(['assignment_id', 'due_date']);

        $subs = DB::table('assignment_submissions')
            ->where('student_id', $studentId)
            ->whereIn('assignment_id', $dueThisWeek->pluck('assignment_id'))
            ->whereNotNull('submitted_at')
            ->get(['assignment_id', 'submitted_at'])
            ->keyBy('assignment_id');

        $submitted = $late = $missing = 0;
        foreach ($dueThisWeek as $a) {
            $s = $subs->get($a->assignment_id);
            if ($s) {
                $submitted++;
                if (Carbon::parse($s->submitted_at)->gt(Carbon::parse($a->due_date))) {
                    $late++;
                }
            } elseif (Carbon::parse($a->due_date)->lt($now)) {
                $missing++;
            }
        }

        $upcoming = DB::table('assignments')
            ->join('courses', 'courses.course_id', '=', 'assignments.course_id')
            ->whereIn('assignments.course_id', $courseIds)
            ->where('assignments.due_date', '>', $now)
            ->where('assignments.due_date', '<=', $now->copy()->addDays((int) config('digest.upcoming_days', 7)))
            ->whereNotExists(function ($q) use ($studentId) {
                $q->select(DB::raw(1))->from('assignment_submissions as s')
                    ->whereColumn('s.assignment_id', 'assignments.assignment_id')
                    ->where('s.student_id', $studentId)
                    ->whereNotNull('s.submitted_at');
            })
            ->orderBy('assignments.due_date')
            ->limit(3)
            ->get(['assignments.title', 'courses.title as course', 'assignments.due_date']);

        return [
            'due_this_week'  => $dueThisWeek->count(),
            'submitted'      => $submitted,
            'late'           => $late,
            'missing'        => $missing,
            'upcoming'       => $upcoming->count(),
            'upcoming_items' => $upcoming->map(fn ($u) => [
                'title'  => $u->title,
                'course' => $u->course,
                'due'    => Carbon::parse($u->due_date)->toDateString(),
            ])->all(),
        ];
    }

    protected function grades(int $studentId, Carbon $start, Carbon $end): array
    {
        $new  = $this->gradeRows($studentId, $start, $end);
        $prev = $this->gradeRows($studentId, $start->copy()->subDays(7), $start->copy()->subSecond());

        $avg     = $new->isNotEmpty() ? round($new->avg('percent'), 1) : null;
        $prevAvg = $prev->isNotEmpty() ? round($prev->avg('percent'), 1) : null;

        $trend = null;
        if ($avg !== null && $prevAvg !== null) {
            $delta = $avg - $prevAvg;
            $limit = (float) config('digest.grade_trend_delta', 3.0);
            $trend = $delta >= $limit ? 'up' : ($delta <= -$limit ? 'down' : 'stable');
        }

        return [
            'count'       => $new->count(),
            'avg_percent' => $avg,
            'prev_avg'    => $prevAvg,
            'trend'       => $trend,
            'items'       => $new->take(3)->values()->all(),
        ];
    }

    /** علامات أُدخلت في الفترة، من النظامين (امتحانات grades وفعاليات grade_entries). */
    protected function gradeRows(int $studentId, Carbon $from, Carbon $to)
    {
        $legacy = DB::table('grades')
            ->join('exams', 'exams.exam_id', '=', 'grades.exam_id')
            ->join('courses', 'courses.course_id', '=', 'exams.course_id')
            ->where('grades.student_id', $studentId)
            ->whereBetween('grades.created_at', [$from, $to])
            ->get(['courses.title as course', 'exams.exam_name as title', 'grades.score', 'exams.max_score as max']);

        $events = DB::table('grade_entries')
            ->join('grade_events', 'grade_events.id', '=', 'grade_entries.grade_event_id')
            ->join('courses', 'courses.course_id', '=', 'grade_events.course_id')
            ->where('grade_entries.student_id', $studentId)
            ->whereNotNull('grade_entries.score')
            ->whereBetween('grade_entries.created_at', [$from, $to])
            ->get(['courses.title as course', 'grade_events.title', 'grade_entries.score', 'grade_events.max_score as max']);

        return $legacy->concat($events)
            ->filter(fn ($r) => (float) $r->max > 0)
            ->map(fn ($r) => [
                'course'  => $r->course,
                'title'   => $r->title,
                'score'   => (float) $r->score + 0,
                'max'     => (float) $r->max + 0,
                'percent' => round(((float) $r->score / (float) $r->max) * 100, 1),
            ])
            ->values();
    }

    /** نبرة الملخص: good | attention | concern (بالقواعد فقط، بلا ذكاء اصطناعي). */
    protected function tone(array $f): string
    {
        $a = $f['attendance'];
        $w = $f['assignments'];
        $g = $f['grades'];

        if ($a['unexcused_absent'] >= (int) config('digest.concern_unexcused_absences', 3)
            || $w['missing'] >= (int) config('digest.concern_missing_assignments', 3)
            || $a['days_to_warning'] === 0) {
            return 'concern';
        }

        if ($a['unexcused_absent'] > 0 || $w['missing'] > 0 || $w['late'] > 0 || $g['trend'] === 'down') {
            return 'attention';
        }

        return 'good';
    }

    /** مفتاح التوصية الوحيدة المناسبة (يحوّله المؤلف إلى جملة). */
    protected function suggestion(array $f): string
    {
        $a = $f['attendance'];
        $w = $f['assignments'];
        $g = $f['grades'];

        return match (true) {
            $a['days_to_warning'] === 0      => 'contact_admin',
            $a['unexcused_absent'] >= 2      => 'talk_absence',
            $w['missing'] > 0                => 'review_assignments',
            $g['trend'] === 'down'           => 'support_grades',
            $w['upcoming'] > 0               => 'upcoming_assignments',
            $f['tone'] === 'good'            => 'celebrate',
            default                          => 'keep_following',
        };
    }
}
