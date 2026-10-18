<?php

namespace App\Services\Risk;

use App\Models\RiskIntervention;
use App\Models\StudentRiskHistory;
use App\Models\StudentRiskScore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * قراءة الطلاب المعرَّضين للتعثر وتسجيل المتابعة ضمن نطاق المستخدم. تستعملها واجهة الـ API وصفحات الويب معًا،
 * فلا يتكرر منطق النطاق والفلاتر. كل دالة تفترض أن المستدعي تحقق من RiskScope::kind($user) !== null.
 */
class RiskDirectory
{
    public const LEVEL_LABELS = ['high' => 'عالٍ', 'medium' => 'متوسط', 'low' => 'منخفض', 'unknown' => 'بيانات غير كافية'];

    /**
     * @param array{level?:?string,search?:?string,program_id?:?int,sort?:?string,followup?:?string} $f
     * @return array{summary: array, rows: array, paginator: LengthAwarePaginator}
     */
    public function list(User $user, array $f, int $perPage = 20): array
    {
        $scoped = RiskScope::students($user)
            ->leftJoin('student_risk_scores as r', 'r.student_id', '=', 'students.student_id')
            ->when(!empty($f['program_id']), fn ($q) => $q->where('students.program_id', (int) $f['program_id']))
            ->when(!empty($f['search']), function ($q) use ($f) {
                $term = '%' . $f['search'] . '%';
                $q->where(fn ($w) => $w->where('users.full_name', 'like', $term)->orWhere('students.student_code', 'like', $term));
            });

        // ملخص المستويات ضمن النطاق (قبل فلتر المستوى، كي تبقى الأعداد ثابتة عند التبديل بين التبويبات)
        $counts = (clone $scoped)->selectRaw("COALESCE(r.level, 'unknown') AS lvl, COUNT(*) AS n")->groupBy('lvl')->pluck('n', 'lvl');

        // طلاب موعد متابعتهم الأخيرة حلّ (آخر إجراء لكل طالب)
        $overdueIds = $this->overdueStudentIds();
        $overdueCount = (clone $scoped)->whereIn('students.student_id', $overdueIds)->count();

        $level = $f['level'] ?? null;
        $q = (clone $scoped)
            ->when(($f['followup'] ?? null) === 'overdue', fn ($x) => $x->whereIn('students.student_id', $overdueIds))
            ->when($level === 'at_risk', fn ($x) => $x->whereIn('r.level', ['high', 'medium']))
            ->when($level && $level !== 'at_risk', function ($x) use ($level) {
                $level === 'unknown'
                    ? $x->where(fn ($w) => $w->whereNull('r.level')->orWhere('r.level', 'unknown'))
                    : $x->where('r.level', $level);
            })
            ->select(
                'students.student_id', 'students.student_code', 'users.full_name', 'users.academic_year', 'programs.name as program',
                'r.score', 'r.level', 'r.confidence', 'r.reasons', 'r.previous_score', 'r.previous_level', 'r.computed_on'
            );

        ($f['sort'] ?? null) === 'change'
            ? $q->orderByRaw('(r.score - COALESCE(r.previous_score, r.score)) DESC')->orderByDesc('r.score')
            : $q->orderByRaw('r.score IS NULL')->orderByDesc('r.score');

        $page = $q->paginate(min($perPage, 50))->withQueryString();
        $ids  = collect($page->items())->pluck('student_id');
        $last = RiskIntervention::whereIn('student_id', $ids)->orderByDesc('id')->get()->unique('student_id')->keyBy('student_id');

        return [
            'summary' => [
                'high' => (int) ($counts['high'] ?? 0), 'medium' => (int) ($counts['medium'] ?? 0),
                'low' => (int) ($counts['low'] ?? 0), 'unknown' => (int) ($counts['unknown'] ?? 0),
                'total' => (int) $counts->sum(), 'overdue' => $overdueCount,
            ],
            'rows' => collect($page->items())->map(fn ($r) => $this->row($r, $last->get($r->student_id)))->values()->all(),
            'paginator' => $page,
        ];
    }

    /** @return int[] طلاب آخر إجراء متابعة لهم موعده اليوم أو قبله */
    private function overdueStudentIds(): array
    {
        $latest = DB::table('risk_interventions')->selectRaw('MAX(id) AS id')->groupBy('student_id');

        return DB::table('risk_interventions')
            ->joinSub($latest, 'l', 'l.id', '=', 'risk_interventions.id')
            ->whereNotNull('risk_interventions.follow_up_on')
            ->where('risk_interventions.follow_up_on', '<=', today()->toDateString())
            ->pluck('risk_interventions.student_id')->map(fn ($i) => (int) $i)->all();
    }

    /** تفاصيل طالب ضمن النطاق، أو null إن لم يكن ضمنه. */
    public function detail(User $user, int $studentId): ?array
    {
        $student = $this->scopedStudent($user, $studentId);
        if (!$student) {
            return null;
        }

        $score = StudentRiskScore::where('student_id', $student->student_id)->first();
        $history = StudentRiskHistory::where('student_id', $student->student_id)
            ->where('computed_on', '>=', now()->subWeeks(12)->toDateString())
            ->orderBy('computed_on')->get(['computed_on', 'score', 'level']);
        $interventions = RiskIntervention::with('author:user_id,full_name')
            ->where('student_id', $student->student_id)->orderByDesc('id')->limit(30)->get();
        $lastIv = $interventions->first();

        return [
            'student' => [
                'id' => $student->student_id, 'name' => $student->full_name, 'code' => $student->student_code,
                'program' => $student->program, 'year' => $student->academic_year,
            ],
            'risk' => $score ? [
                'score' => $score->score, 'level' => $score->level, 'level_label' => self::LEVEL_LABELS[$score->level],
                'confidence' => $score->confidence, 'reasons' => $score->reasons, 'factors' => $score->factors,
                'previous_score' => $score->previous_score, 'previous_level' => $score->previous_level,
                'computed_on' => $score->computed_on->toDateString(),
            ] : null,
            'history' => $history->map(fn ($h) => ['date' => $h->computed_on->toDateString(), 'score' => $h->score, 'level' => $h->level])->values()->all(),
            'interventions' => $interventions->map(fn ($i) => $this->intervention($i))->values()->all(),
            // أثر المتابعة: كيف تغيّرت الدرجة منذ آخر إجراء
            'since_last_intervention' => ($lastIv && $lastIv->score_at_time !== null && $score)
                ? ['date' => $lastIv->created_at->toDateString(), 'from' => $lastIv->score_at_time, 'to' => $score->score, 'delta' => round($score->score - $lastIv->score_at_time, 1)]
                : null,
            'intervention_types' => RiskIntervention::TYPES,
        ];
    }

    /** يسجّل إجراء متابعة بدرجة الطالب لحظتها. null إن كان الطالب خارج النطاق. */
    public function addIntervention(User $user, int $studentId, array $data): ?array
    {
        $student = $this->scopedStudent($user, $studentId);
        if (!$student) {
            return null;
        }

        $score = StudentRiskScore::where('student_id', $student->student_id)->first();

        $iv = RiskIntervention::create([
            'student_id'     => $student->student_id,
            'author_user_id' => $user->user_id,
            'type'           => $data['type'],
            'note'           => $data['note'] ?? null,
            'follow_up_on'   => $data['follow_up_on'] ?? null,
            'score_at_time'  => $score?->score,
            'level_at_time'  => $score?->level,
        ])->load('author:user_id,full_name');

        return $this->intervention($iv);
    }

    /** الطالب إن كان ضمن نطاق المستخدم، وإلا null (يبدو كأنه غير موجود). */
    private function scopedStudent(User $user, int $studentId): ?object
    {
        $base = RiskScope::students($user);

        return $base?->where('students.student_id', $studentId)
            ->first(['students.student_id', 'students.student_code', 'users.full_name', 'users.academic_year', 'programs.name as program']);
    }

    private function row(object $r, ?RiskIntervention $last): array
    {
        $level = $r->level ?? 'unknown';

        return [
            'student' => ['id' => $r->student_id, 'name' => $r->full_name, 'code' => $r->student_code, 'program' => $r->program, 'year' => $r->academic_year],
            'score' => $r->score !== null ? (float) $r->score : null,
            'level' => $level,
            'level_label' => self::LEVEL_LABELS[$level],
            'confidence' => $r->confidence !== null ? (float) $r->confidence : null,
            'reasons' => $r->reasons ? json_decode($r->reasons, true) : [],
            'change' => $r->score !== null && $r->previous_score !== null ? round($r->score - $r->previous_score, 1) : null,
            'previous_level' => $r->previous_level,
            'computed_on' => $r->computed_on,
            'last_intervention' => $last ? ['type' => $last->type, 'type_label' => RiskIntervention::TYPES[$last->type], 'date' => $last->created_at->toDateString()] : null,
            'follow_up_due' => $last && $last->follow_up_on && $last->follow_up_on->lte(Carbon::today()),
        ];
    }

    private function intervention(RiskIntervention $i): array
    {
        return [
            'id' => $i->id, 'type' => $i->type, 'type_label' => RiskIntervention::TYPES[$i->type], 'note' => $i->note,
            'follow_up_on' => optional($i->follow_up_on)->toDateString(), 'author' => $i->author->full_name ?? '',
            'score_at_time' => $i->score_at_time, 'level_at_time' => $i->level_at_time,
            'created_at' => $i->created_at->toIso8601String(),
        ];
    }
}
