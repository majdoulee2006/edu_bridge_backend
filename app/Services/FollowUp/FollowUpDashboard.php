<?php

namespace App\Services\FollowUp;

use App\Models\RiskIntervention;
use App\Models\StudentRiskScore;
use App\Models\User;
use App\Services\Risk\RiskScope;
use App\Services\Risk\Scale;
use App\Support\Access;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * لوحة متابعة الطلاب لرئيس القسم (قسمه كله) وللمرشد (طلاب فرعه وسنته): "ما الذي يحتاج انتباهي اليوم؟".
 *
 *  - مؤشرات علوية قابلة للنقر: معرَّضون، ارتفعوا هذا الأسبوع، متابعات فاتت، تنبيهات ساعة بلا رد، جلسات بانتظار الإغلاق.
 *  - الدورات (المقررات) مرتّبة بدرجة اهتمام تجمع الحضور الأخير ونسبة المعرَّضين، مع أسباب مكتوبة.
 *  - جدول المرشدين (لرئيس القسم): من عنده معرَّضون ولا يتابعهم.
 *  - كل الأرقام محسوبة على طلاب نطاق المستخدم فقط (RiskScope)، فالمرشد يرى مقرراته من زاوية طلابه هو.
 *
 * لا تحسب هذه الخدمة درجات الخطر نفسها؛ تقرأ ما حسبه risk:compute ليلاً (لذلك تعرض تاريخ آخر تحديث).
 */
class FollowUpDashboard
{
    public const SORTS = ['attention', 'attendance', 'risk', 'name'];
    public const STATUSES = ['attention', 'watch', 'ok', 'nodata'];

    /**
     * @param array{program_id?:?int, year?:?int, status?:?string, search?:?string, sort?:?string, days?:?int} $f
     * @return array|null null إن لم تكن للمستخدم صلاحية
     */
    public function build(User $user, array $f = []): ?array
    {
        $base = RiskScope::students($user);
        if ($base === null) {
            return null;
        }
        if (!empty($f['program_id'])) {
            $base->where('students.program_id', (int) $f['program_id']);
        }

        $students = $base->select(
            'students.student_id', 'students.user_id', 'users.full_name', 'users.academic_year',
            'programs.id as program_id', 'programs.name as program', 'programs.department_id'
        )->get();

        $ids   = $students->pluck('student_id')->map(fn ($i) => (int) $i)->all();
        $days  = max(1, (int) ($f['days'] ?? config('followup.days')));
        $kind  = RiskScope::kind($user);
        $scores = $ids ? DB::table('student_risk_scores')->whereIn('student_id', $ids)->get()->keyBy('student_id') : collect();
        $latest = $this->latestInterventions($ids);

        $overdue = $latest->filter(fn ($i) => $i->follow_up_on && $i->follow_up_on->lte(today()));

        $programs = $this->programs($students, $scores, $f, $days);

        // الدورة (مقرر) قد تتبع أكثر من برنامج: نعدّ جلساتها المعلّقة مرة واحدة
        $pendingTotal = (int) collect($programs)->pluck('courses')->flatten(1)->unique('course_id')->sum('pending_sessions');

        return [
            'kind'        => $kind,
            'period_days' => $days,
            'computed_on' => $scores->max('computed_on'),
            'kpis'        => $this->kpis($ids, $scores, $overdue, $pendingTotal),
            'today'       => $this->todayLists($students, $scores, $overdue, $latest, $ids),
            'programs'    => $programs,
            'advisors'    => in_array($kind, [RiskScope::HEAD, RiskScope::ADMIN], true)
                ? $this->advisors($user, $kind, $students, $scores, $latest)
                : [],
            'filters'     => [
                'programs' => $students->filter(fn ($s) => $s->program_id)->unique('program_id')
                    ->map(fn ($s) => ['id' => (int) $s->program_id, 'name' => $s->program])->sortBy('name')->values()->all(),
            ],
        ];
    }

    // ───────────────────────────── المؤشرات العلوية ─────────────────────────────

    private function kpis(array $ids, Collection $scores, Collection $overdue, int $pending): array
    {
        $levels = ['high' => 0, 'medium' => 0, 'low' => 0, 'unknown' => 0];
        foreach ($ids as $id) {
            $lvl = $scores->get($id)->level ?? 'unknown';
            $levels[$lvl] = ($levels[$lvl] ?? 0) + 1;
        }

        $rising = $scores->filter(function ($s) {
            return in_array($s->level, ['high', 'medium'], true)
                && $s->level_changed_at && now()->subDays(7)->lte($s->level_changed_at)
                && StudentRiskScore::rank($s->level) > StudentRiskScore::rank($s->previous_level);
        })->count();

        $alerts = $ids
            ? DB::table('attendance_clock_alerts')->whereIn('student_id', $ids)->whereNull('acknowledged_at')
                ->selectRaw('COUNT(*) AS open_n, SUM(CASE WHEN escalated_at IS NOT NULL THEN 1 ELSE 0 END) AS escalated_n')->first()
            : null;

        return [
            'students'            => count($ids),
            'high'                => $levels['high'],
            'medium'              => $levels['medium'],
            'low'                 => $levels['low'],
            'unknown'             => $levels['unknown'],
            'rising_week'         => $rising,
            'overdue_followups'   => $overdue->count(),
            'clock_alerts_open'   => (int) ($alerts->open_n ?? 0),
            'clock_alerts_escalated' => (int) ($alerts->escalated_n ?? 0),
            'pending_sessions'    => $pending,
        ];
    }

    // ───────────────────────────── قوائم "عليك اليوم" ─────────────────────────────

    private function todayLists(Collection $students, Collection $scores, Collection $overdue, Collection $latest, array $ids): array
    {
        $size = (int) config('followup.list_size', 8);
        $byId = $students->keyBy('student_id');

        $top = $scores->filter(fn ($s) => in_array($s->level, ['high', 'medium'], true))
            ->sortByDesc('score')->take($size)
            ->map(function ($s) use ($byId) {
                $st = $byId->get($s->student_id);

                return [
                    'student_id' => (int) $s->student_id, 'name' => $st->full_name ?? '', 'program' => $st->program ?? null,
                    'year' => $st->academic_year ?? null, 'level' => $s->level, 'score' => (float) $s->score,
                    'change' => $s->previous_score !== null ? round($s->score - $s->previous_score, 1) : null,
                    'reasons' => $s->reasons ? (json_decode($s->reasons, true) ?: []) : [],
                ];
            })->values()->all();

        $overdueList = $overdue->sortBy('follow_up_on')->take($size)->map(function ($i) use ($byId) {
            $st = $byId->get($i->student_id);

            return [
                'student_id' => (int) $i->student_id, 'name' => $st->full_name ?? '',
                'follow_up_on' => $i->follow_up_on->toDateString(), 'days_late' => (int) $i->follow_up_on->diffInDays(today()),
                'last_action' => RiskIntervention::TYPES[$i->type] ?? $i->type,
            ];
        })->values()->all();

        $clock = $ids
            ? DB::table('attendance_clock_alerts')->whereIn('student_id', $ids)
                ->whereNull('acknowledged_at')->whereNotNull('escalated_at')
                ->selectRaw('student_id, COUNT(*) AS n, MAX(created_at) AS last_at')->groupBy('student_id')
                ->orderByDesc('last_at')->limit($size)->get()
                ->map(fn ($r) => ['student_id' => (int) $r->student_id, 'name' => $byId->get($r->student_id)->full_name ?? '', 'count' => (int) $r->n, 'last_at' => $r->last_at])
                ->all()
            : [];

        return ['top_risk' => $top, 'overdue' => $overdueList, 'clock_alerts' => $clock];
    }

    // ───────────────────────────── البرامج (الدورات) ─────────────────────────────

    /**
     * كل برنامج (معلوماتية، ذكاء اصطناعي، ...) بملخصه ومقرراته. المقررات تُحسب على طلاب البرنامج نفسه،
     * فمقرر مشترك بين برنامجين يظهر في كل منهما بأرقام طلابه هو.
     *
     * @return array<int, array>
     */
    private function programs(Collection $students, Collection $scores, array $f, int $days): array
    {
        if ($students->isEmpty()) {
            return [];
        }

        $deptNames = DB::table('departments')->pluck('name', 'department_id');
        $from = today()->subDays($days - 1)->toDateString();
        $minRecords = (int) config('followup.min_attendance_records');

        // نسبة حضور البرنامج كله (كل مقرراته) في النافذة، بدون الغياب المعذور
        $att = DB::table('attendance as a')
            ->join('students as st', 'st.student_id', '=', 'a.student_id')
            ->whereIn('a.student_id', $students->pluck('student_id')->all())
            ->whereBetween('a.attendance_date', [$from, today()->toDateString()])
            ->whereIn('a.status', ['present', 'late', 'absent'])
            ->whereRaw("NOT (a.status = 'absent' AND a.excuse_status = 'approved')")
            ->groupBy('st.program_id')
            ->selectRaw("st.program_id, COUNT(*) AS total, SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) AS attended")
            ->get()->keyBy('program_id');

        $out = [];
        foreach ($students->groupBy(fn ($s) => $s->program_id ?? 0) as $programId => $group) {
            $ids     = $group->pluck('student_id')->map(fn ($i) => (int) $i)->all();
            $courses = $this->courses($ids, $f, $days)->values()->all();

            if (!$courses && (!empty($f['status']) || !empty($f['year']) || !empty($f['search']))) {
                continue; // الفلتر استبعد كل مقررات هذا البرنامج
            }

            $high = $group->filter(fn ($s) => ($scores->get($s->student_id)->level ?? null) === 'high')->count();
            $med  = $group->filter(fn ($s) => ($scores->get($s->student_id)->level ?? null) === 'medium')->count();
            $hasRisk = $group->contains(fn ($s) => $scores->has($s->student_id));

            $a = $att->get($programId ?: '');
            $total = (int) ($a->total ?? 0);
            $rate = $total >= $minRecords ? round(((int) $a->attended / $total) * 100, 1) : null;
            $share = $group->count() > 0 ? ($high + 0.5 * $med) / $group->count() : 0.0;
            [$score, $status] = $this->attention($rate, $share, $hasRisk);

            $first = $group->first();
            $out[] = [
                'program_id' => $programId ?: null,
                'name'       => $first->program ?: 'بدون برنامج',
                'department' => $deptNames[$first->department_id] ?? null,
                'students'   => $group->count(), 'high' => $high, 'medium' => $med,
                'attendance_rate' => $rate,
                'attention_score' => $score, 'status' => $status,
                'reasons'    => $this->reasons($rate, $high, $med, 0, $days),
                'courses'    => $courses,
            ];
        }

        return collect($out)->sortBy(fn ($p) => match ($f['sort'] ?? 'attention') {
            'name'  => [$p['name']],
            default => [self::statusRank($p['status']), -($p['attention_score'] ?? 0), $p['name']],
        })->values()->all();
    }

    // ───────────────────────────── الدورات ─────────────────────────────

    /** @return Collection<int, array> */
    private function courses(array $ids, array $f, int $days): Collection
    {
        if (!$ids) {
            return collect();
        }

        $rows = DB::table('enrollments as e')
            ->join('courses as c', 'c.course_id', '=', 'e.course_id')
            ->leftJoin('student_risk_scores as r', 'r.student_id', '=', 'e.student_id')
            ->whereIn('e.student_id', $ids)
            ->where('e.status', '!=', 'dropped')
            ->when(!empty($f['year']), fn ($q) => $q->where('c.year', (int) $f['year']))
            ->when(!empty($f['search']), fn ($q) => $q->where(fn ($w) => $w->where('c.title', 'like', '%' . $f['search'] . '%')->orWhere('c.code', 'like', '%' . $f['search'] . '%')))
            ->groupBy('e.course_id', 'c.title', 'c.code', 'c.year')
            ->selectRaw("e.course_id, c.title, c.code, c.year, COUNT(*) AS enrolled,
                SUM(CASE WHEN r.level = 'high' THEN 1 ELSE 0 END) AS high,
                SUM(CASE WHEN r.level = 'medium' THEN 1 ELSE 0 END) AS medium,
                AVG(r.score) AS avg_score")
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $courseIds = $rows->pluck('course_id')->all();
        $from = today()->subDays($days - 1)->toDateString();

        $att = DB::table('attendance as a')
            ->join('lessons as l', 'l.lesson_id', '=', 'a.lesson_id')
            ->whereIn('a.student_id', $ids)->whereIn('l.course_id', $courseIds)
            ->whereBetween('a.attendance_date', [$from, today()->toDateString()])
            ->whereIn('a.status', ['present', 'late', 'absent'])
            ->whereRaw("NOT (a.status = 'absent' AND a.excuse_status = 'approved')")
            ->groupBy('l.course_id')
            ->selectRaw("l.course_id, COUNT(*) AS total, SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) AS attended")
            ->get()->keyBy('course_id');

        $sessions = DB::table('attendance_sessions as s')
            ->join('lessons as l', 'l.lesson_id', '=', 's.lesson_id')
            ->whereIn('l.course_id', $courseIds)
            ->groupBy('l.course_id')
            ->selectRaw('l.course_id, MAX(s.created_at) AS last_session,
                SUM(CASE WHEN s.finalized_at IS NULL AND s.created_at >= ? THEN 1 ELSE 0 END) AS pending', [today()->startOfDay()])
            ->get()->keyBy('course_id');

        $teachers = DB::table('course_teachers as ct')
            ->join('teachers as t', 't.teacher_id', '=', 'ct.teacher_id')
            ->join('users as u', 'u.user_id', '=', 't.user_id')
            ->whereIn('ct.course_id', $courseIds)
            ->get(['ct.course_id', 'u.full_name'])
            ->groupBy('course_id')->map(fn ($g) => $g->pluck('full_name')->unique()->values()->all());

        $programs = DB::table('course_program as cp')
            ->join('programs as p', 'p.id', '=', 'cp.program_id')
            ->whereIn('cp.course_id', $courseIds)
            ->get(['cp.course_id', 'p.name'])
            ->groupBy('course_id')->map(fn ($g) => $g->pluck('name')->unique()->values()->all());

        $out = $rows->map(function ($c) use ($att, $sessions, $teachers, $programs, $days) {
            $a = $att->get($c->course_id);
            $total = (int) ($a->total ?? 0);
            $rate = $total >= (int) config('followup.min_attendance_records') ? round(((int) $a->attended / $total) * 100, 1) : null;

            $high = (int) $c->high;
            $med  = (int) $c->medium;
            $share = $c->enrolled > 0 ? ($high + 0.5 * $med) / (int) $c->enrolled : 0.0;
            $pending = (int) ($sessions->get($c->course_id)->pending ?? 0);

            [$score, $status] = $this->attention($rate, $share, $c->avg_score !== null);

            return [
                'course_id' => (int) $c->course_id, 'title' => $c->title, 'code' => $c->code, 'year' => $c->year !== null ? (int) $c->year : null,
                'programs'  => $programs->get($c->course_id, []), 'teachers' => $teachers->get($c->course_id, []),
                'enrolled'  => (int) $c->enrolled, 'high' => $high, 'medium' => $med,
                'avg_risk'  => $c->avg_score !== null ? round((float) $c->avg_score, 1) : null,
                'attendance_rate' => $rate, 'attendance_records' => $total,
                'pending_sessions' => $pending,
                'last_session' => $sessions->get($c->course_id)->last_session ?? null,
                'attention_score' => $score, 'status' => $status,
                'reasons' => $this->reasons($rate, $high, $med, $pending, $days),
            ];
        });

        if (!empty($f['status']) && in_array($f['status'], self::STATUSES, true)) {
            $out = $out->where('status', $f['status']);
        }

        return $this->sorted($out, $f['sort'] ?? 'attention');
    }

    /** @return array{0:?float,1:string} [درجة الاهتمام، الحالة] */
    private function attention(?float $rate, float $riskShare, bool $hasRiskData): array
    {
        $cfg = config('followup');
        $wA = (float) $cfg['weights']['attendance'];
        $wR = (float) $cfg['weights']['risk'];

        $parts = [];
        if ($rate !== null) {
            $parts[] = [$wA, Scale::linear($rate, $cfg['attendance']['safe'], $cfg['attendance']['worst'])];
        }
        if ($hasRiskData) {
            $parts[] = [$wR, Scale::linear($riskShare, 0, $cfg['risk_share_full'])];
        }
        if (!$parts) {
            return [null, 'nodata'];
        }

        // بلا حضور كافٍ لا نعتمد على الخطر وحده لنصنّفها "تحتاج اهتمامًا": نكتفي بـ "راقب" كحد أقصى
        $w = array_sum(array_column($parts, 0));
        $score = round(array_sum(array_map(fn ($p) => $p[0] * $p[1], $parts)) / ($w ?: 1), 1);

        $status = match (true) {
            $score >= $cfg['status']['attention'] => 'attention',
            $score >= $cfg['status']['watch']     => 'watch',
            default                               => 'ok',
        };
        if ($rate === null && $status === 'attention') {
            $status = 'watch';
        }

        return [$score, $status];
    }

    private function reasons(?float $rate, int $high, int $med, int $pending, int $days): array
    {
        $r = [];
        if ($rate !== null && $rate < config('followup.attendance.safe')) {
            $r[] = 'الحضور ' . Scale::pretty($rate) . '% خلال آخر ' . $days . ' يومًا';
        }
        if ($high > 0) {
            $r[] = $high . ($high === 1 ? ' طالب' : ' طلاب') . ' بخطر عالٍ';
        }
        if ($med > 0) {
            $r[] = $med . ($med === 1 ? ' طالب' : ' طلاب') . ' بخطر متوسط';
        }
        if ($pending > 0) {
            $r[] = $pending . ' جلسة اليوم بانتظار الإغلاق النهائي';
        }

        return $r;
    }

    /** الحالة أولاً (تحتاج اهتمامًا ثم راقبها ثم جيدة ثم بلا بيانات)، فالدرجة داخل الحالة الواحدة. */
    private static function statusRank(string $status): int
    {
        return ['attention' => 0, 'watch' => 1, 'ok' => 2, 'nodata' => 3][$status] ?? 4;
    }

    private function sorted(Collection $c, string $sort): Collection
    {
        return match ($sort) {
            // الأحوج للاهتمام أولاً؛ من لا بيانات له في الآخر
            'attendance' => $c->sortBy(fn ($x) => [$x['attendance_rate'] === null ? 1 : 0, $x['attendance_rate'] ?? 0]),
            'risk'       => $c->sortByDesc(fn ($x) => [$x['high'] * 2 + $x['medium'], $x['enrolled']]),
            'name'       => $c->sortBy('title'),
            default      => $c->sortBy(fn ($x) => [self::statusRank($x['status']), -($x['attention_score'] ?? 0), $x['title']]),
        };
    }

    // ───────────────────────────── المرشدون ─────────────────────────────

    private function advisors(User $user, string $kind, Collection $students, Collection $scores, Collection $latest): array
    {
        $q = DB::table('teachers as t')
            ->join('users as u', 'u.user_id', '=', 't.user_id')
            ->where('u.status', 'active')
            ->whereNotNull('t.advisor_branch')->where('t.advisor_branch', '!=', '');

        if ($kind === RiskScope::HEAD) {
            $deptId = Access::headDepartment($user)['id'];
            $names  = $deptId ? DB::table('programs')->where('department_id', $deptId)->pluck('name')->all() : [];
            $q->whereIn('t.advisor_branch', $names ?: ['__none__']);
        }

        $idle = (int) config('followup.advisor_idle_days', 14);

        return $q->get(['t.teacher_id', 't.user_id', 'u.full_name', 't.advisor_branch', 't.advisor_year'])->map(function ($a) use ($students, $scores, $latest, $idle) {
            $cohort = $students->filter(fn ($s) => $s->program === $a->advisor_branch && $s->academic_year === $a->advisor_year);
            $ids    = $cohort->pluck('student_id')->map(fn ($i) => (int) $i);

            $high = $ids->filter(fn ($id) => ($scores->get($id)->level ?? null) === 'high')->count();
            $med  = $ids->filter(fn ($id) => ($scores->get($id)->level ?? null) === 'medium')->count();
            $overdue = $ids->filter(function ($id) use ($latest) {
                $i = $latest->get($id);

                return $i && $i->follow_up_on && $i->follow_up_on->lte(today());
            })->count();

            $recent = RiskIntervention::where('author_user_id', $a->user_id)->where('created_at', '>=', now()->subDays($idle));

            return [
                'teacher_id' => (int) $a->teacher_id, 'name' => $a->full_name, 'branch' => $a->advisor_branch, 'year' => $a->advisor_year,
                'students' => $cohort->count(), 'high' => $high, 'medium' => $med, 'overdue_followups' => $overdue,
                'actions_recent' => (int) $recent->count(), 'idle_days' => $idle,
                'needs_nudge' => ($high + $med) > 0 && $recent->count() === 0,
            ];
        })->sortByDesc(fn ($a) => [(int) $a['needs_nudge'], $a['high'], $a['overdue_followups']])->values()->all();
    }

    // ───────────────────────────── مساعدة ─────────────────────────────

    /** آخر إجراء متابعة لكل طالب. @return Collection<int, RiskIntervention> keyed by student_id */
    private function latestInterventions(array $ids): Collection
    {
        if (!$ids) {
            return collect();
        }

        $maxIds = DB::table('risk_interventions')->whereIn('student_id', $ids)->groupBy('student_id')->selectRaw('MAX(id) AS id')->pluck('id');

        return RiskIntervention::whereIn('id', $maxIds)->get()->keyBy('student_id');
    }
}
