<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleDraft;
use App\Services\Schedule\ScheduleDraftService;
use App\Services\Schedule\ScheduleProblem;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Http\Request;

/**
 * استوديو الجدول لرئيس القسم عبر API (تطبيق Flutter). المنطق كله في ScheduleDraftService نفسه الذي تستعمله صفحات الويب،
 * والقاعات وتوفّر المعلمين تُدار من لوحة الويب.
 */
class ScheduleStudioController extends Controller
{
    public function __construct(private ScheduleDraftService $svc)
    {
    }

    public function index(Request $request)
    {
        return $this->guard(function () use ($request) {
            $dept = $this->svc->departmentOf($request->user());

            return response()->json(['success' => true, 'data' => [
                'capacity' => ScheduleProblem::forDepartment($dept, [])->capacityReport(),
                'periods' => config('schedule.periods'), 'default_periods' => config('schedule.default_periods'), 'max_periods' => config('schedule.max_periods'),
                'drafts' => ScheduleDraft::where('department_id', $dept)->orderByDesc('id')->limit(20)->get()->map(fn ($d) => $this->brief($d))->values(),
            ]]);
        });
    }

    public function generate(Request $request)
    {
        $data = $request->validate(['name' => 'nullable|string|max:120', 'periods' => 'nullable|integer|between:1,' . config('schedule.max_periods')]);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full($this->svc->generate($request->user(), array_filter($data)))], 201));
    }

    public function show(Request $request, $id)
    {
        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full($this->draft($request, $id))]));
    }

    public function move(Request $request, $id)
    {
        $d = $request->validate(['entry_id' => 'required|integer', 'day' => 'required|string', 'period' => 'required|integer', 'room' => 'nullable|string|max:60']);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full(
            $this->svc->move($this->draft($request, $id), (int) $d['entry_id'], $d['day'], (int) $d['period'], $d['room'] ?? null)
        )]));
    }

    public function swap(Request $request, $id)
    {
        $d = $request->validate(['a' => 'required|integer|different:b', 'b' => 'required|integer']);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full($this->svc->swap($this->draft($request, $id), (int) $d['a'], (int) $d['b']))]));
    }

    public function lock(Request $request, $id)
    {
        $d = $request->validate(['entry_id' => 'required|integer', 'locked' => 'required|boolean']);

        return $this->guard(function () use ($request, $id, $d) {
            $draft = $this->draft($request, $id);
            $this->svc->setLocked($draft, (int) $d['entry_id'], (bool) $d['locked']);

            return response()->json(['success' => true, 'data' => $this->full($draft->fresh())]);
        });
    }

    public function regenerate(Request $request, $id)
    {
        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full($this->svc->regenerate($this->draft($request, $id), random_int(1, 99999)))]));
    }

    public function approve(Request $request, $id)
    {
        return $this->guard(fn () => response()->json(['success' => true, 'message' => 'اعتُمد الجدول وأُشعر المعلمون والطلاب', 'data' => $this->full(
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
        return $this->guard(fn () => response()->json(['success' => true, 'message' => 'عاد الجدول كما كان قبل الاعتماد', 'data' => $this->full(
            $this->svc->revert($this->draft($request, $id), $request->user())
        )]));
    }

    // ------------------------------------------------------------------ تمثيل

    private function draft(Request $request, $id): ScheduleDraft
    {
        return ScheduleDraft::where('department_id', $this->svc->departmentOf($request->user()))->findOrFail($id);
    }

    private function brief(ScheduleDraft $d): array
    {
        $s = $d->stats ?? [];

        return [
            'id' => $d->id, 'name' => $d->name, 'status' => $d->status, 'reverted' => $d->reverted_at !== null,
            'placed' => $s['placed'] ?? 0, 'required' => $s['required'] ?? 0, 'unscheduled' => count($s['unscheduled'] ?? []),
            'violations' => count($s['violations'] ?? []), 'created_at' => $d->created_at?->toIso8601String(),
        ];
    }

    private function full(ScheduleDraft $d): array
    {
        $d = $d->fresh();
        $problem = $this->svc->problemFor($d);
        $titles = [];
        foreach ($problem->groups as $g) {
            foreach ($g['courses'] as $c) {
                $titles[$c['course_id']] = $c['title'];
            }
        }

        $groups = [];
        foreach ($problem->groups as $key => $g) {
            $groups[$key] = ['key' => $key, 'label' => $g['label'], 'entries' => []];
        }
        foreach ($d->entries()->orderBy('id')->get() as $e) {
            $groups[$e->program_id . '-' . $e->year]['entries'][] = [
                'id' => $e->id, 'course' => $titles[$e->course_id] ?? ('#' . $e->course_id), 'teacher' => $problem->teacherName((int) $e->teacher_user_id),
                'day' => $e->day, 'period' => (int) $e->period, 'room' => $e->room, 'locked' => (bool) $e->locked, 'manual' => $e->source === 'manual',
            ];
        }
        $s = $d->stats ?? [];

        return $this->brief($d) + [
            'days' => $d->settings['days'], 'day_labels' => config('schedule.day_labels'),
            'period_count' => (int) $d->settings['periods'], 'periods' => config('schedule.periods'),
            'groups' => array_values($groups),
            'violation_messages' => array_column($s['violations'] ?? [], 'message'),
            'unscheduled_items' => array_map(fn ($u) => ['group' => $u['group_label'], 'title' => $u['title'], 'missing' => $u['missing'], 'of' => $u['of'], 'message' => $u['message']], $s['unscheduled'] ?? []),
            'quality' => ['gaps' => $s['quality']['gaps'] ?? 0, 'avoid' => $s['quality']['avoid'] ?? 0],
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
