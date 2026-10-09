<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiskIntervention;
use App\Models\StudentRiskHistory;
use App\Models\StudentRiskScore;
use App\Services\Risk\RiskScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * الطلاب المعرَّضون للتعثر: للمرشد (فرعه وسنته)، ولرئيس القسم (قسمه)، وللأدمن (الكل).
 * الطالب وولي الأمر وبقية المعلمين لا يصلون إلى هذه البيانات إطلاقًا.
 */
class RiskController extends Controller
{
    public const LEVEL_LABELS = ['high' => 'عالٍ', 'medium' => 'متوسط', 'low' => 'منخفض', 'unknown' => 'بيانات غير كافية'];

    public function index(Request $request)
    {
        $base = RiskScope::students($request->user());
        if ($base === null) {
            return $this->forbidden();
        }

        $request->validate([
            'level'      => 'nullable|in:high,medium,low,unknown,at_risk',
            'search'     => 'nullable|string|max:100',
            'program_id' => 'nullable|integer',
            'sort'       => 'nullable|in:score,change',
        ]);

        $scoped = $base
            ->leftJoin('student_risk_scores as r', 'r.student_id', '=', 'students.student_id')
            ->when($request->filled('program_id'), fn ($q) => $q->where('students.program_id', (int) $request->program_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn ($w) => $w->where('users.full_name', 'like', $term)->orWhere('students.student_code', 'like', $term));
            });

        // ملخص المستويات ضمن النطاق (قبل فلتر المستوى، كي تبقى الأعداد ثابتة عند التبديل بين التبويبات)
        $counts = (clone $scoped)->selectRaw("COALESCE(r.level, 'unknown') AS lvl, COUNT(*) AS n")->groupBy('lvl')->pluck('n', 'lvl');

        $q = (clone $scoped)
            ->when($request->level === 'at_risk', fn ($x) => $x->whereIn('r.level', ['high', 'medium']))
            ->when($request->filled('level') && $request->level !== 'at_risk', function ($x) use ($request) {
                $request->level === 'unknown'
                    ? $x->where(fn ($w) => $w->whereNull('r.level')->orWhere('r.level', 'unknown'))
                    : $x->where('r.level', $request->level);
            });

        $q->select(
            'students.student_id', 'students.student_code', 'users.full_name', 'users.academic_year', 'programs.name as program',
            'r.score', 'r.level', 'r.confidence', 'r.reasons', 'r.previous_score', 'r.previous_level', 'r.computed_on', 'r.level_changed_at'
        );

        $request->query('sort') === 'change'
            ? $q->orderByRaw('(r.score - COALESCE(r.previous_score, r.score)) DESC')->orderByDesc('r.score')
            : $q->orderByRaw('r.score IS NULL')->orderByDesc('r.score');

        $page = $q->paginate(min((int) $request->query('per_page', 20), 50));
        $ids  = collect($page->items())->pluck('student_id');

        $last = RiskIntervention::whereIn('student_id', $ids)->orderByDesc('id')->get()->unique('student_id')->keyBy('student_id');

        return response()->json([
            'success' => true,
            'summary' => [
                'high' => (int) ($counts['high'] ?? 0), 'medium' => (int) ($counts['medium'] ?? 0),
                'low' => (int) ($counts['low'] ?? 0), 'unknown' => (int) ($counts['unknown'] ?? 0),
                'total' => (int) $counts->sum(),
            ],
            'data' => collect($page->items())->map(fn ($r) => $this->row($r, $last->get($r->student_id)))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, $studentId)
    {
        $student = $this->scopedStudent($request, (int) $studentId);
        if ($student instanceof \Illuminate\Http\JsonResponse) {
            return $student;
        }

        $score = StudentRiskScore::where('student_id', $student->student_id)->first();
        $history = StudentRiskHistory::where('student_id', $student->student_id)
            ->where('computed_on', '>=', now()->subWeeks(12)->toDateString())
            ->orderBy('computed_on')->get(['computed_on', 'score', 'level']);

        $interventions = RiskIntervention::with('author:user_id,full_name')
            ->where('student_id', $student->student_id)->orderByDesc('id')->limit(30)->get();

        $lastIv = $interventions->first();

        return response()->json([
            'success' => true,
            'data' => [
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
                'history' => $history->map(fn ($h) => ['date' => $h->computed_on->toDateString(), 'score' => $h->score, 'level' => $h->level])->values(),
                'interventions' => $interventions->map(fn ($i) => $this->intervention($i))->values(),
                // أثر المتابعة: كيف تغيّرت الدرجة منذ آخر إجراء
                'since_last_intervention' => ($lastIv && $lastIv->score_at_time !== null && $score)
                    ? ['date' => $lastIv->created_at->toDateString(), 'from' => $lastIv->score_at_time, 'to' => $score->score, 'delta' => round($score->score - $lastIv->score_at_time, 1)]
                    : null,
                'intervention_types' => RiskIntervention::TYPES,
            ],
        ]);
    }

    public function storeIntervention(Request $request, $studentId)
    {
        $student = $this->scopedStudent($request, (int) $studentId);
        if ($student instanceof \Illuminate\Http\JsonResponse) {
            return $student;
        }

        $data = $request->validate([
            'type'         => 'required|in:' . implode(',', array_keys(RiskIntervention::TYPES)),
            'note'         => 'nullable|string|max:1000',
            'follow_up_on' => 'nullable|date|after_or_equal:today',
        ]);

        $score = StudentRiskScore::where('student_id', $student->student_id)->first();

        $iv = RiskIntervention::create([
            'student_id'     => $student->student_id,
            'author_user_id' => $request->user()->user_id,
            'type'           => $data['type'],
            'note'           => $data['note'] ?? null,
            'follow_up_on'   => $data['follow_up_on'] ?? null,
            'score_at_time'  => $score?->score,
            'level_at_time'  => $score?->level,
        ])->load('author:user_id,full_name');

        return response()->json(['success' => true, 'message' => 'تم تسجيل الإجراء', 'data' => $this->intervention($iv)], 201);
    }

    // ----------------------------------------------------------------- مساعدة

    /** الطالب إن كان ضمن نطاق المستخدم، وإلا استجابة 403/404 جاهزة. */
    private function scopedStudent(Request $request, int $studentId)
    {
        $base = RiskScope::students($request->user());
        if ($base === null) {
            return $this->forbidden();
        }

        $student = $base->where('students.student_id', $studentId)
            ->first(['students.student_id', 'students.student_code', 'users.full_name', 'users.academic_year', 'programs.name as program']);

        // طالب خارج النطاق يبدو غير موجود (لا نكشف وجوده)
        return $student ?: response()->json(['success' => false, 'message' => 'الطالب غير موجود'], 404);
    }

    private function forbidden()
    {
        return response()->json(['success' => false, 'message' => 'هذه الخدمة للمرشدين ورؤساء الأقسام فقط'], 403);
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
