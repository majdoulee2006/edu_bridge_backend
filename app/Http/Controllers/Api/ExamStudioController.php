<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleDraft;
use App\Models\Semester;
use App\Services\Schedule\ExamDraftService;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Http\Request;

/**
 * مولّد الجدول الامتحاني لرئيس القسم عبر API (تطبيق Flutter). المنطق كله في ExamDraftService نفسه الذي تستعمله صفحات الويب.
 */
class ExamStudioController extends Controller
{
    public function __construct(private ExamDraftService $svc)
    {
    }

    public function index(Request $request)
    {
        $request->validate(['semester_id' => 'nullable|integer|exists:semesters,semester_id']);

        return $this->guard(function () use ($request) {
            $dept = $this->svc->departmentOf($request->user());
            $selected = $request->filled('semester_id') ? Semester::find((int) $request->semester_id) : Semester::current();

            return response()->json(['success' => true, 'data' => [
                'semesters' => Semester::orderByDesc('start_date')->get()->map(fn ($s) => ['id' => $s->semester_id, 'name' => $s->name, 'current' => (bool) $s->is_active])->values(),
                'current_semester_id' => Semester::currentId(),
                'selected_semester_id' => $selected?->semester_id,
                'readiness' => $selected ? $this->svc->readiness($dept, $selected->semester_id) : null,
                'defaults' => config('exam.default_duration'), 'max_slots_per_day' => (int) config('exam.max_slots_per_day'),
                'drafts' => ScheduleDraft::with('semester')->where('department_id', $dept)->where('type', ScheduleDraft::TYPE_EXAM)->orderByDesc('id')->limit(20)->get()->map(fn ($d) => $this->brief($d))->values(),
            ]]);
        });
    }

    public function generate(Request $request)
    {
        $data = $request->validate(['semester_id' => 'nullable|integer|exists:semesters,semester_id', 'name' => 'nullable|string|max:120', 'kinds' => 'required|array']);

        return $this->guard(function () use ($request, $data) {
            $draft = $this->svc->generate($request->user(), array_filter([
                'semester_id' => $data['semester_id'] ?? null, 'name' => $data['name'] ?? null, 'kinds' => ExamDraftService::kindsFromInput($data['kinds']),
            ], fn ($v) => $v !== null && $v !== ''));

            return response()->json(['success' => true, 'data' => $this->full($draft)], 201);
        });
    }

    public function show(Request $request, $id)
    {
        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full($this->draft($request, $id))]));
    }

    public function move(Request $request, $id)
    {
        $d = $request->validate(['entry_id' => 'required|integer', 'date' => 'required|date_format:Y-m-d', 'slot' => 'required|integer|min:0']);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full(
            $this->svc->move($this->draft($request, $id), (int) $d['entry_id'], $d['date'], (int) $d['slot'])
        )]));
    }

    public function lock(Request $request, $id)
    {
        $d = $request->validate(['entry_id' => 'required|integer', 'locked' => 'required|boolean']);

        return $this->guard(function () use ($request, $id, $d) {
            $draft = $this->draft($request, $id);
            $this->svc->setLocked($draft, (int) $d['entry_id'], (bool) $d['locked']);

            return response()->json(['success' => true, 'data' => $this->full($draft)]);
        });
    }

    public function moveStudent(Request $request, $id)
    {
        $d = $request->validate(['entry_id' => 'required|integer', 'student_id' => 'required|integer', 'room' => 'required|string|max:60']);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full(
            $this->svc->moveStudent($this->draft($request, $id), (int) $d['entry_id'], (int) $d['student_id'], $d['room'])
        )]));
    }

    /** يعيّن مراقب قاعة (user_id أو null للإزالة). */
    public function proctor(Request $request, $id)
    {
        $d = $request->validate(['entry_id' => 'required|integer', 'room' => 'required|string|max:60', 'user_id' => 'nullable|integer']);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full(
            $this->svc->setProctor($this->draft($request, $id), (int) $d['entry_id'], $d['room'], isset($d['user_id']) ? (int) $d['user_id'] : null)
        )]));
    }

    public function regenerate(Request $request, $id)
    {
        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full($this->svc->regenerate($this->draft($request, $id)))]));
    }

    public function approve(Request $request, $id)
    {
        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full(
            $this->svc->approve($this->draft($request, $id), $request->user(), $request->boolean('acknowledge'))
        )]));
    }

    public function discard(Request $request, $id)
    {
        return $this->guard(function () use ($request, $id) {
            $this->svc->discard($this->draft($request, $id));

            return response()->json(['success' => true]);
        });
    }

    public function revert(Request $request, $id)
    {
        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full($this->svc->revert($this->draft($request, $id), $request->user()))]));
    }

    // ------------------------------------------------------------------ تمثيل

    private function draft(Request $request, $id): ScheduleDraft
    {
        return ScheduleDraft::with('semester')->where('department_id', $this->svc->departmentOf($request->user()))->where('type', ScheduleDraft::TYPE_EXAM)->findOrFail($id);
    }

    private function brief(ScheduleDraft $d): array
    {
        $s = $d->stats ?? [];

        return [
            'id' => $d->id, 'name' => $d->name, 'status' => $d->status, 'reverted' => $d->reverted_at !== null,
            'semester_id' => $d->semester_id, 'semester_name' => $d->semester?->name, 'saved_for_next' => $d->isSavedForNext(),
            'placed' => $s['placed'] ?? 0, 'required' => $s['required'] ?? 0, 'unscheduled' => count($s['unscheduled'] ?? []),
            'violations' => count($s['violations'] ?? []), 'rooms_without_proctor' => $s['rooms_without_proctor'] ?? 0,
            'created_at' => $d->created_at?->toIso8601String(),
        ];
    }

    private function full(ScheduleDraft $d): array
    {
        $d = $d->fresh(['semester']);
        $v = $this->svc->describe($d);
        $s = $d->stats ?? [];

        return $this->brief($d) + [
            'entries' => $v['entries'], 'teachers' => $v['teachers'], 'slots' => $v['slots'], 'dates' => $v['dates'], 'kinds' => $v['kinds'],
            'approved_at' => $v['approved_at'],
            'violation_messages' => array_column($s['violations'] ?? [], 'message'),
            'unscheduled_items' => array_map(fn ($u) => ['group' => $u['group_label'], 'title' => $u['title'], 'kind' => $u['kind_label'], 'message' => $u['message']], $s['unscheduled'] ?? []),
            'impact' => $d->status === 'draft' ? $this->svc->impact($d) : null,
        ];
    }

    private function guard(callable $fn)
    {
        try {
            return $fn();
        } catch (ScheduleRuleException $e) {
            return response()->json(['success' => false, 'message' => implode(' — ', $e->messages), 'errors' => $e->messages], 422);
        }
    }
}
