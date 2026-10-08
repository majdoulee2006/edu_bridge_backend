<?php

namespace App\Services\AttendanceIntelligence;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * تحليل إحصائي للحضور: خريطة حرارية (يوم × حصة)، ترتيب المواد والمحاضرات والدفعات،
 * اتجاه أسبوعي، وطلاب تغيّر سلوكهم. كله قراءة فقط من جدول attendance.
 *
 * المعدّل = (حاضر + متأخر) ÷ كل السجلات، مع استبعاد الغياب المعذور (إجازة/عذر معتمد) من المقام.
 * نطاق البيانات يحدّده المستدعي عبر $courseIds (null = كل المقررات) كي لا يرى رئيس القسم غير قسمه.
 */
class AttendanceAnalyticsService
{
    private const DAY_LABELS = [0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];

    /**
     * @param  int[]|null $courseIds
     * @return array<string,mixed>
     */
    public function build(?array $courseIds, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        if ($courseIds !== null && count($courseIds) === 0) {
            return $this->empty($from, $to);
        }

        $heatmap = $this->heatmap($courseIds, $from, $to);

        return [
            'period'   => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'overall'  => $heatmap['overall'],
            'heatmap'  => ['days' => $heatmap['days'], 'slots' => $heatmap['slots'], 'cells' => $heatmap['cells']],
            'courses'  => $this->courseRanking($courseIds, $from, $to),
            'lessons'  => $this->worstSessions($courseIds, $from, $to),
            'programs' => $this->programRanking($courseIds, $from, $to),
            'weekly'   => $this->weeklyTrend($courseIds, $from, $to),
            'behavior_changes' => $this->behaviorChanges($courseIds, $to),
            'flags'    => $this->flagSummary($courseIds, $from, $to),
        ];
    }

    // ------------------------------------------------------------------ أساس الاستعلام

    /** السجلات المحتسبة في المعدّل ضمن النطاق: بدون الغياب المعذور. */
    private function base(?array $courseIds, Carbon $from, Carbon $to)
    {
        $q = DB::table('attendance as a')
            ->join('lessons as l', 'l.lesson_id', '=', 'a.lesson_id')
            ->whereBetween('a.attendance_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('a.status', ['present', 'late', 'absent'])
            ->whereRaw("NOT (a.status = 'absent' AND a.excuse_status = 'approved')");

        if ($courseIds !== null) {
            $q->whereIn('l.course_id', $courseIds);
        }

        return $q;
    }

    private const ATTENDED = "SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END)";

    private function rate($attended, $total): ?float
    {
        return $total > 0 ? round(($attended / $total) * 100, 1) : null;
    }

    // ------------------------------------------------------------------ الخريطة الحرارية

    private function heatmap(?array $courseIds, Carbon $from, Carbon $to): array
    {
        // وقت الحصة: من الجدول الدراسي للمقرر في ذلك اليوم، وإلا ساعة المسح
        $slot = "COALESCE(
            TIME_FORMAT((SELECT MIN(s.start_time) FROM schedules s WHERE s.course_id = l.course_id AND s.day = DAYNAME(a.attendance_date)), '%H:%i'),
            CONCAT(LPAD(HOUR(a.created_at), 2, '0'), ':00')
        )";

        $rows = $this->base($courseIds, $from, $to)
            ->selectRaw("DAYOFWEEK(a.attendance_date) - 1 AS dow, {$slot} AS slot, COUNT(*) AS total, " . self::ATTENDED . ' AS attended')
            ->groupBy('dow', 'slot')
            ->get();

        $cells = $rows->map(fn ($r) => [
            'day'      => (int) $r->dow,
            'slot'     => $r->slot,
            'total'    => (int) $r->total,
            'attended' => (int) $r->attended,
            'rate'     => $this->rate($r->attended, $r->total),
        ])->sortBy([['day', 'asc'], ['slot', 'asc']])->values();

        $days = $cells->pluck('day')->unique()->sort()->values()
            ->map(fn ($d) => ['key' => $d, 'label' => self::DAY_LABELS[$d]])->all();

        $total = (int) $cells->sum('total');
        $attended = (int) $cells->sum('attended');

        return [
            'days'    => $days,
            'slots'   => $cells->pluck('slot')->unique()->sort()->values()->all(),
            'cells'   => $cells->all(),
            'overall' => ['total' => $total, 'attended' => $attended, 'rate' => $this->rate($attended, $total)],
        ];
    }

    // ------------------------------------------------------------------ الترتيب

    private function courseRanking(?array $courseIds, Carbon $from, Carbon $to): array
    {
        $min = (int) config('attendance_intelligence.analytics.min_records_for_ranking', 5);

        return $this->base($courseIds, $from, $to)
            ->join('courses as c', 'c.course_id', '=', 'l.course_id')
            ->selectRaw('c.course_id, c.title, COUNT(*) AS total, ' . self::ATTENDED . ' AS attended')
            ->groupBy('c.course_id', 'c.title')
            ->having('total', '>=', $min)
            ->get()
            ->map(fn ($r) => [
                'course_id' => (int) $r->course_id, 'title' => $r->title,
                'total' => (int) $r->total, 'attended' => (int) $r->attended,
                'rate' => $this->rate($r->attended, $r->total),
            ])
            ->sortBy('rate')->values()->take(15)->all();
    }

    /** أضعف الجلسات حضورًا (محاضرة محددة في يوم محدد). */
    private function worstSessions(?array $courseIds, Carbon $from, Carbon $to): array
    {
        $min = max(3, (int) config('attendance_intelligence.analytics.min_records_for_ranking', 5) - 2);

        return $this->base($courseIds, $from, $to)
            ->join('courses as c', 'c.course_id', '=', 'l.course_id')
            ->selectRaw('l.lesson_id, c.title AS course, MIN(a.attendance_date) AS day, COUNT(*) AS total, ' . self::ATTENDED . ' AS attended')
            ->groupBy('l.lesson_id', 'c.title')
            ->having('total', '>=', $min)
            ->get()
            ->map(fn ($r) => [
                'lesson_id' => (int) $r->lesson_id, 'course' => $r->course, 'date' => $r->day,
                'total' => (int) $r->total, 'rate' => $this->rate($r->attended, $r->total),
            ])
            ->sortBy('rate')->values()->take(10)->all();
    }

    /** مقارنة الدفعات/البرامج بحسب برنامج الطالب. */
    private function programRanking(?array $courseIds, Carbon $from, Carbon $to): array
    {
        $min = (int) config('attendance_intelligence.analytics.min_records_for_ranking', 5);

        return $this->base($courseIds, $from, $to)
            ->join('students as st', 'st.student_id', '=', 'a.student_id')
            ->join('programs as p', 'p.id', '=', 'st.program_id')
            ->selectRaw('p.id, p.name, COUNT(*) AS total, ' . self::ATTENDED . ' AS attended')
            ->groupBy('p.id', 'p.name')
            ->having('total', '>=', $min)
            ->get()
            ->map(fn ($r) => [
                'program_id' => (int) $r->id, 'name' => $r->name,
                'total' => (int) $r->total, 'rate' => $this->rate($r->attended, $r->total),
            ])
            ->sortBy('rate')->values()->all();
    }

    private function weeklyTrend(?array $courseIds, Carbon $from, Carbon $to): array
    {
        return $this->base($courseIds, $from, $to)
            ->selectRaw('DATE_SUB(a.attendance_date, INTERVAL (DAYOFWEEK(a.attendance_date) - 1) DAY) AS week_start, COUNT(*) AS total, ' . self::ATTENDED . ' AS attended')
            ->groupBy('week_start')
            ->orderBy('week_start')
            ->get()
            ->map(fn ($r) => [
                'week_start' => $r->week_start, 'total' => (int) $r->total,
                'rate' => $this->rate($r->attended, $r->total),
            ])->all();
    }

    // ------------------------------------------------------------------ تغيّر السلوك

    /**
     * طلاب كان حضورهم ممتازًا ثم هبط فجأة (آخر أسبوعين مقابل الأسابيع الأربعة قبلها).
     * ليست اتهامًا بل إشارة مبكرة للمرشد (وأساس الإنذار المبكر لاحقًا).
     */
    private function behaviorChanges(?array $courseIds, Carbon $to): array
    {
        $cfg = config('attendance_intelligence.analytics');
        $recentFrom   = $to->copy()->subDays(13)->startOfDay();
        $baselineFrom = $recentFrom->copy()->subDays(28);
        $baselineTo   = $recentFrom->copy()->subDay();

        $stats = function (Carbon $f, Carbon $t) use ($courseIds): Collection {
            return $this->base($courseIds, $f, $t)
                ->selectRaw('a.student_id, COUNT(*) AS total, ' . self::ATTENDED . ' AS attended')
                ->groupBy('a.student_id')
                ->get()
                ->keyBy('student_id');
        };

        $recent   = $stats($recentFrom, $to);
        $baseline = $stats($baselineFrom, $baselineTo);

        $out = [];
        foreach ($recent as $studentId => $r) {
            $b = $baseline->get($studentId);
            if (!$b || $r->total < 4 || $b->total < 8) {
                continue;
            }
            $recentRate   = $this->rate($r->attended, $r->total);
            $baselineRate = $this->rate($b->attended, $b->total);

            if ($baselineRate >= $cfg['behavior_good_rate'] && $recentRate <= $cfg['behavior_drop_rate']) {
                $out[] = ['student_id' => (int) $studentId, 'baseline_rate' => $baselineRate, 'recent_rate' => $recentRate];
            }
        }

        if (!$out) {
            return [];
        }

        $names = DB::table('students as s')->join('users as u', 'u.user_id', '=', 's.user_id')
            ->whereIn('s.student_id', array_column($out, 'student_id'))
            ->pluck('u.full_name', 's.student_id');

        return collect($out)
            ->map(fn ($o) => $o + ['name' => $names[$o['student_id']] ?? ''])
            ->sortBy(fn ($o) => $o['recent_rate'] - $o['baseline_rate'])
            ->values()->take(10)->all();
    }

    // ------------------------------------------------------------------ الاشتباهات

    private function flagSummary(?array $courseIds, Carbon $from, Carbon $to): array
    {
        $q = DB::table('attendance_flags')->whereBetween('created_at', [$from, $to]);
        if ($courseIds !== null) {
            $q->whereIn('course_id', $courseIds);
        }

        $rows = $q->get(['status', 'severity', 'rule']);

        return [
            'total'       => $rows->count(),
            'open'        => $rows->where('status', 'open')->count(),
            'confirmed'   => $rows->where('status', 'confirmed')->count(),
            'dismissed'   => $rows->where('status', 'dismissed')->count(),
            'by_severity' => $rows->countBy('severity')->all(),
            'by_rule'     => $rows->countBy('rule')->all(),
        ];
    }

    private function empty(Carbon $from, Carbon $to): array
    {
        return [
            'period'   => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'overall'  => ['total' => 0, 'attended' => 0, 'rate' => null],
            'heatmap'  => ['days' => [], 'slots' => [], 'cells' => []],
            'courses' => [], 'lessons' => [], 'programs' => [], 'weekly' => [],
            'behavior_changes' => [],
            'flags'    => ['total' => 0, 'open' => 0, 'confirmed' => 0, 'dismissed' => 0, 'by_severity' => [], 'by_rule' => []],
        ];
    }
}
