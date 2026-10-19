<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\ScheduleDraft;
use App\Models\Semester;
use App\Models\TeacherAvailability;
use App\Services\Schedule\ScheduleDraftService;
use App\Services\Schedule\ScheduleProblem;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * استوديو الجدول لرئيس القسم (/hod/schedule-studio): يختار إنشاءً يدويًا أو مسودة يقترحها النظام يراجعها ويعدّلها ويعتمدها.
 * المنطق كله في ScheduleDraftService؛ هنا العرض وتحويل الرفض إلى رسائل.
 */
class ScheduleStudioWebController extends Controller
{
    public function __construct(private ScheduleDraftService $svc)
    {
    }

    public function index(Request $request)
    {
        $deptId = $this->dept();
        $request->validate(['semester_id' => 'nullable|integer|exists:semesters,semester_id']);

        $semesters = Semester::orderByDesc('start_date')->get();
        $selected = $request->filled('semester_id') ? $semesters->firstWhere('semester_id', (int) $request->semester_id) : Semester::current();
        $problem = $selected ? ScheduleProblem::forDepartment($deptId, ['semester_id' => $selected->semester_id]) : null;

        return view('schedule_studio.index', [
            'semesters' => $semesters, 'selected' => $selected, 'currentId' => Semester::currentId(),
            'capacity' => $problem ? $problem->capacityReport() : [],
            'drafts'   => ScheduleDraft::with('semester')->where('department_id', $deptId)->orderByDesc('id')->limit(20)->get(),
            'rooms'    => Room::where(fn ($q) => $q->where('department_id', $deptId)->orWhereNull('department_id'))->orderBy('name')->get(),
            'teachers' => $this->teachers($deptId),
            'avail'    => TeacherAvailability::whereIn('teacher_user_id', $this->teachers($deptId)->pluck('user_id'))->orderBy('teacher_user_id')->get(),
            'days'     => config('schedule.days'),
            'dayLabels' => config('schedule.day_labels'),
            'periods'  => config('schedule.periods'),
            'maxPeriods' => (int) config('schedule.max_periods'),
            'defaultPeriods' => (int) config('schedule.default_periods'),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:120',
            'periods' => 'nullable|integer|between:1,' . config('schedule.max_periods'),
            'seed' => 'nullable|integer|min:1|max:99999',
            'semester_id' => 'nullable|integer|exists:semesters,semester_id',
        ]);

        return $this->run(function () use ($data) {
            $draft = $this->svc->generate(Auth::user(), array_filter($data, fn ($v) => $v !== null && $v !== ''));

            return redirect()->route('hod.studio.show', $draft->id)->with('success', $draft->isSavedForNext()
                ? 'حُفظت المسودة للفصل القادم «' . $draft->semester->name . '». راجعها وعدّلها، وتعتمدها عندما يصبح فصلها فعّالاً.'
                : 'أُنشئت مسودة مقترحة. راجعها قبل الاعتماد؛ لن يتغيّر الجدول الحالي حتى تعتمدها.');
        });
    }

    public function show($id)
    {
        $draft = $this->draft($id);
        $problem = $this->svc->problemFor($draft);
        $entries = $draft->entries()->orderBy('id')->get();
        $stats = $draft->stats ?? [];

        $titles = [];   // لا flatMap: يعيد ترقيم مفاتيح course_id
        foreach ($problem->groups as $g) {
            foreach ($g['courses'] as $c) {
                $titles[$c['course_id']] = $c['title'];
            }
        }

        // شبكة لكل شعبة: [label => [day][period] => entry]
        $grids = [];
        foreach ($problem->groups as $key => $g) {
            $grids[$key] = ['label' => $g['label'], 'cells' => []];
        }
        foreach ($entries as $e) {
            $grids[$e->program_id . '-' . $e->year]['cells'][$e->day][$e->period] = $e;
        }

        return view('schedule_studio.show', [
            'draft' => $draft, 'stats' => $stats, 'grids' => $grids, 'entries' => $entries,
            'courseTitles' => $titles,
            'teacherNames' => $problem->teacherNames,
            'days' => $draft->settings['days'], 'dayLabels' => config('schedule.day_labels'),
            'periodCount' => (int) $draft->settings['periods'], 'periods' => config('schedule.periods'),
            'rooms' => Room::where(fn ($q) => $q->where('department_id', $draft->department_id)->orWhereNull('department_id'))->where('is_active', true)->orderBy('name')->pluck('name'),
            'latestApprovedId' => ScheduleDraft::where('department_id', $draft->department_id)->where('status', 'approved')->whereNull('reverted_at')->orderByDesc('approved_at')->value('id'),
        ]);
    }

    public function move(Request $request, $id)
    {
        $data = $request->validate(['entry_id' => 'required|integer', 'day' => 'required|string', 'period' => 'required|integer', 'room' => 'nullable|string|max:60']);

        return $this->act($id, fn ($d) => $this->svc->move($d, (int) $data['entry_id'], $data['day'], (int) $data['period'], $data['room'] ?? null), 'تم نقل الحصة.');
    }

    public function swap(Request $request, $id)
    {
        $data = $request->validate(['a' => 'required|integer|different:b', 'b' => 'required|integer']);

        return $this->act($id, fn ($d) => $this->svc->swap($d, (int) $data['a'], (int) $data['b']), 'تم تبديل الحصتين.');
    }

    public function lock(Request $request, $id)
    {
        $data = $request->validate(['entry_id' => 'required|integer', 'locked' => 'required|boolean']);

        return $this->act($id, function ($d) use ($data) {
            $this->svc->setLocked($d, (int) $data['entry_id'], (bool) $data['locked']);
        }, $data['locked'] ? 'ثُبّتت الحصة: لن تتغير عند إعادة التوليد.' : 'أُلغي التثبيت.');
    }

    public function regenerate(Request $request, $id)
    {
        return $this->act($id, fn ($d) => $this->svc->regenerate($d, random_int(1, 99999)), 'أُعيد توزيع الحصص غير المثبّتة.');
    }

    public function approve(Request $request, $id)
    {
        return $this->act($id, fn ($d) => $this->svc->approve($d, Auth::user(), $request->boolean('acknowledge')), 'اعتُمد الجدول وصار هو الجدول الدراسي الفعلي، وأُشعر المعلمون والطلاب.');
    }

    public function discard($id)
    {
        $this->act($id, fn ($d) => $this->svc->discard($d), 'أُهملت المسودة.');

        return redirect()->route('hod.studio');
    }

    public function revert($id)
    {
        $draft = $this->draft($id);

        return $this->run(function () use ($draft) {
            $this->svc->revert($draft, Auth::user());

            return redirect()->route('hod.studio.show', $draft->id)->with('success', 'تم التراجع: عاد الجدول كما كان قبل الاعتماد.');
        }, $draft->id);
    }

    // ------------------------------------------------------------- قاعات وتوفّر

    public function storeRoom(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60', 'capacity' => 'required|integer|between:1,2000']);
        $dept = $this->dept();

        Room::updateOrCreate(['name' => $data['name'], 'department_id' => $dept], ['capacity' => $data['capacity'], 'is_active' => true]);

        return back()->with('success', 'حُفظت القاعة.');
    }

    public function deleteRoom($id)
    {
        Room::where('department_id', $this->dept())->where('id', $id)->delete();

        return back()->with('success', 'حُذفت القاعة.');
    }

    public function storeAvailability(Request $request)
    {
        $data = $request->validate([
            'teacher_user_id' => 'required|integer', 'day' => 'required|in:' . implode(',', config('schedule.days')),
            'period' => 'required|integer|between:1,' . config('schedule.max_periods'), 'kind' => 'required|in:unavailable,avoid',
        ]);
        abort_unless($this->teachers($this->dept())->contains('user_id', (int) $data['teacher_user_id']), 403, 'هذا المعلم ليس من معلمي قسمك.');

        TeacherAvailability::updateOrCreate(\Illuminate\Support\Arr::only($data, ['teacher_user_id', 'day', 'period']), ['kind' => $data['kind']]);

        return back()->with('success', 'حُفظ قيد المعلم.');
    }

    public function deleteAvailability($id)
    {
        TeacherAvailability::whereIn('teacher_user_id', $this->teachers($this->dept())->pluck('user_id'))->where('id', $id)->delete();

        return back()->with('success', 'حُذف القيد.');
    }

    // ------------------------------------------------------------- مساعدة

    private function dept(): int
    {
        return $this->svc->departmentOf(Auth::user());
    }

    private function draft($id): ScheduleDraft
    {
        return ScheduleDraft::where('department_id', $this->dept())->findOrFail($id);
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

    /** ينفّذ إجراءً على مسودة ويعيد إلى صفحتها، محوّلًا رفض القواعد إلى رسائل خطأ. */
    private function act($id, callable $fn, string $ok)
    {
        $draft = $this->draft($id);

        return $this->run(function () use ($fn, $draft, $ok) {
            $fn($draft);

            return redirect()->route('hod.studio.show', $draft->id)->with('success', $ok);
        }, $draft->id);
    }

    private function run(callable $fn, ?int $draftId = null)
    {
        try {
            return $fn();
        } catch (ScheduleRuleException $e) {
            $to = $draftId ? redirect()->route('hod.studio.show', $draftId) : redirect()->route('hod.studio');

            return $to->with('error', implode(' — ', $e->messages));
        }
    }
}
