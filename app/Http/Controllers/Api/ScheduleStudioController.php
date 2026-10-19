<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\ScheduleDraft;
use App\Models\Semester;
use App\Models\TeacherAvailability;
use App\Services\Schedule\RoomService;
use App\Services\Schedule\ScheduleDraftService;
use App\Services\Schedule\ScheduleReadiness;
use App\Services\Schedule\ScheduleProblem;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * استوديو الجدول لرئيس القسم عبر API (تطبيق Flutter). المنطق كله في ScheduleDraftService نفسه الذي تستعمله صفحات الويب،
 * والقاعات وتوفّر المعلمين تُدار من لوحة الويب.
 */
class ScheduleStudioController extends Controller
{
    public function __construct(private ScheduleDraftService $svc, private RoomService $roomSvc, private ScheduleReadiness $readiness)
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
                'capacity' => $selected ? ScheduleProblem::forDepartment($dept, ['semester_id' => $selected->semester_id])->capacityReport() : [],
                'readiness' => $selected ? $this->readiness->check($dept, $selected->semester_id) : null,
                'periods' => config('schedule.periods'), 'default_periods' => config('schedule.default_periods'), 'max_periods' => config('schedule.max_periods'),
                'drafts' => ScheduleDraft::where('department_id', $dept)->orderByDesc('id')->limit(20)->get()->map(fn ($d) => $this->brief($d))->values(),
            ]]);
        });
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:120', 'periods' => 'nullable|integer|between:1,' . config('schedule.max_periods'),
            'semester_id' => 'nullable|integer|exists:semesters,semester_id',
        ]);

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

    // ------------------------------------------------------------------ القاعات وتوفّر المعلمين

    public function settings(Request $request)
    {
        return $this->guard(function () use ($request) {
            $dept = $this->svc->departmentOf($request->user());
            $teachers = $this->teachers($dept);

            return response()->json(['success' => true, 'data' => [
                'rooms' => Room::with('program')->where(fn ($q) => $q->where('department_id', $dept)->orWhereNull('department_id'))->orderBy('name')->get()
                    ->map(fn ($r) => [
                        'id' => $r->id, 'name' => $r->name, 'capacity' => $r->capacity, 'shared' => $r->department_id === null,
                        'type' => $r->type, 'type_label' => Room::TYPES[$r->type] ?? $r->type,
                        'program_id' => $r->program_id, 'program' => $r->program?->name, 'year' => $r->year,
                    ])->values(),
                'room_types' => collect(Room::TYPES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
                'programs' => DB::table('programs')->where('department_id', $dept)->orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
                'groups' => $this->roomSvc->groups($dept),
                'teachers' => $teachers->map(fn ($t) => ['user_id' => $t->user_id, 'name' => $t->full_name])->values(),
                'availability' => TeacherAvailability::whereIn('teacher_user_id', $teachers->pluck('user_id'))->orderBy('teacher_user_id')->get()
                    ->map(fn ($a) => ['id' => $a->id, 'teacher_user_id' => $a->teacher_user_id, 'day' => $a->day, 'period' => $a->period, 'kind' => $a->kind])->values(),
                'days' => config('schedule.days'), 'day_labels' => config('schedule.day_labels'), 'periods' => config('schedule.periods'), 'max_periods' => config('schedule.max_periods'),
            ]]);
        });
    }

    public function storeRoom(Request $request)
    {
        $dept = null;

        return $this->guard(function () use ($request, &$dept) {
            $dept = $this->svc->departmentOf($request->user());
            $d = $this->roomRules($request, $dept);
            $room = Room::updateOrCreate(['name' => $d['name'], 'department_id' => $dept], $d + ['is_active' => true]);

            return response()->json(['success' => true, 'data' => ['id' => $room->id]], 201);
        });
    }

    public function updateRoom(Request $request, $id)
    {
        return $this->guard(function () use ($request, $id) {
            $dept = $this->svc->departmentOf($request->user());
            $room = Room::where('department_id', $dept)->findOrFail($id);
            $room->update($this->roomRules($request, $dept));

            return response()->json(['success' => true, 'data' => ['id' => $room->id]]);
        });
    }

    public function deleteRoom(Request $request, $id)
    {
        return $this->guard(function () use ($request, $id) {
            Room::where('department_id', $this->svc->departmentOf($request->user()))->where('id', $id)->delete();

            return response()->json(['success' => true]);
        });
    }

    /** قاعة خاصة لكل شعبة بلا قاعة. */
    public function autoRooms(Request $request)
    {
        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->roomSvc->createGroupRooms($this->svc->departmentOf($request->user()))]));
    }

    /** استيراد قاعات من نص (سطر لكل قاعة). يعيد الأخطاء برقم السطر دون إيقاف الباقي. */
    public function importRooms(Request $request)
    {
        $d = $request->validate(['lines' => 'required|string|max:20000']);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->roomSvc->import($this->svc->departmentOf($request->user()), $d['lines'])]));
    }

    private function roomRules(Request $request, int $dept): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:60', 'capacity' => 'required|integer|between:1,2000',
            'type' => 'nullable|in:' . implode(',', array_keys(Room::TYPES)),
            'program_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('programs', 'id')->where('department_id', $dept)],
            'year' => 'nullable|integer|between:1,5|required_with:program_id',
        ]);

        return [
            'name' => $data['name'], 'capacity' => (int) $data['capacity'], 'type' => $data['type'] ?? 'classroom',
            'program_id' => $data['program_id'] ?? null, 'year' => !empty($data['program_id']) ? (int) $data['year'] : null,
        ];
    }

    public function storeAvailability(Request $request)
    {
        $d = $request->validate([
            'teacher_user_id' => 'required|integer', 'day' => 'required|in:' . implode(',', config('schedule.days')),
            'period' => 'required|integer|between:1,' . config('schedule.max_periods'), 'kind' => 'required|in:unavailable,avoid',
        ]);

        return $this->guard(function () use ($request, $d) {
            if (!$this->teachers($this->svc->departmentOf($request->user()))->contains('user_id', (int) $d['teacher_user_id'])) {
                return response()->json(['success' => false, 'message' => 'هذا المعلم ليس من معلمي قسمك'], 403);
            }
            $a = TeacherAvailability::updateOrCreate(\Illuminate\Support\Arr::only($d, ['teacher_user_id', 'day', 'period']), ['kind' => $d['kind']]);

            return response()->json(['success' => true, 'data' => ['id' => $a->id]], 201);
        });
    }

    public function deleteAvailability(Request $request, $id)
    {
        return $this->guard(function () use ($request, $id) {
            $ids = $this->teachers($this->svc->departmentOf($request->user()))->pluck('user_id');
            TeacherAvailability::whereIn('teacher_user_id', $ids)->where('id', $id)->delete();

            return response()->json(['success' => true]);
        });
    }

    private function teachers(int $deptId)
    {
        return DB::table('course_teachers')
            ->join('teachers', 'teachers.teacher_id', '=', 'course_teachers.teacher_id')
            ->join('users', 'users.user_id', '=', 'teachers.user_id')
            ->join('course_program', 'course_program.course_id', '=', 'course_teachers.course_id')
            ->join('programs', 'programs.id', '=', 'course_program.program_id')
            ->where('programs.department_id', $deptId)
            ->select('users.user_id', 'users.full_name')->distinct()->orderBy('users.full_name')->get();
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
            'semester_id' => $d->semester_id, 'semester_name' => $d->semester?->name, 'type' => $d->type, 'saved_for_next' => $d->isSavedForNext(),
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
