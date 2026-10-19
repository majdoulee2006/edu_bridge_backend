<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ScheduleDraft;
use App\Models\Semester;
use App\Services\Schedule\ExamDraftService;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * مولّد الجدول الامتحاني لرئيس القسم (/hod/exam-studio): مذاكرات وامتحانات، توزيع الطلاب على القاعات، مراقب لكل قاعة، ثم اعتماد.
 * المنطق كله في ExamDraftService؛ هنا العرض وتحويل الرفض إلى رسائل.
 */
class ExamStudioWebController extends Controller
{
    public function __construct(private ExamDraftService $svc)
    {
    }

    public function index(Request $request)
    {
        $deptId = $this->dept();
        $request->validate(['semester_id' => 'nullable|integer|exists:semesters,semester_id']);

        $semesters = Semester::orderByDesc('start_date')->get();
        $selected = $request->filled('semester_id') ? $semesters->firstWhere('semester_id', (int) $request->semester_id) : Semester::current();

        return view('exam_studio.index', [
            'semesters' => $semesters, 'selected' => $selected, 'currentId' => Semester::currentId(),
            'readiness' => $selected ? $this->svc->readiness($deptId, $selected->semester_id) : null,
            'drafts' => ScheduleDraft::with('semester')->where('department_id', $deptId)->where('type', ScheduleDraft::TYPE_EXAM)->orderByDesc('id')->limit(20)->get(),
            'defaults' => config('exam.default_duration'),
            'maxSlots' => (int) config('exam.max_slots_per_day'),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'semester_id' => 'nullable|integer|exists:semesters,semester_id', 'name' => 'nullable|string|max:120',
            'kinds' => 'required|array',
        ]);

        return $this->run(function () use ($data) {
            $draft = $this->svc->generate(Auth::user(), array_filter([
                'semester_id' => $data['semester_id'] ?? null, 'name' => $data['name'] ?? null,
                'kinds' => ExamDraftService::kindsFromInput($data['kinds']),
            ], fn ($v) => $v !== null && $v !== ''));

            return redirect()->route('hod.exams.show', $draft->id)->with('success', $draft->isSavedForNext()
                ? 'حُفظ الجدول للفصل القادم «' . $draft->semester->name . '». راجعه وعدّله، وتعتمده عندما يصبح فصله فعّالاً.'
                : 'أُنشئت مسودة. اختر مراقباً لكل قاعة وراجعها قبل الاعتماد؛ لا يراها أحد غيرك حتى تعتمدها.');
        });
    }

    public function show($id)
    {
        $draft = $this->draft($id);
        $view = $this->svc->describe($draft);
        $byDate = [];
        foreach ($view['entries'] as $e) {
            $byDate[$e['date']][] = $e;
        }
        ksort($byDate);

        return view('exam_studio.show', [
            'draft' => $draft, 'd' => $view, 'byDate' => $byDate, 'impact' => $draft->status === 'draft' ? $this->svc->impact($draft) : null,
            'latestApprovedId' => ScheduleDraft::where('department_id', $draft->department_id)->where('type', ScheduleDraft::TYPE_EXAM)->where('status', 'approved')->whereNull('reverted_at')->orderByDesc('approved_at')->value('id'),
        ]);
    }

    public function move(Request $request, $id)
    {
        $data = $request->validate(['entry_id' => 'required|integer', 'date' => 'required|date_format:Y-m-d', 'slot' => 'required|integer|min:0']);

        return $this->act($id, fn ($d) => $this->svc->move($d, (int) $data['entry_id'], $data['date'], (int) $data['slot']), 'نُقل التقييم وأُعيد توزيع طلابه على القاعات.');
    }

    public function lock(Request $request, $id)
    {
        $data = $request->validate(['entry_id' => 'required|integer', 'locked' => 'required|boolean']);

        return $this->act($id, fn ($d) => $this->svc->setLocked($d, (int) $data['entry_id'], (bool) $data['locked']), $data['locked'] ? 'ثُبّت التقييم: لن يتغير عند إعادة التوليد.' : 'أُلغي التثبيت.');
    }

    public function moveStudent(Request $request, $id)
    {
        $data = $request->validate(['entry_id' => 'required|integer', 'student_id' => 'required|integer', 'room' => 'required|string|max:60']);

        return $this->act($id, fn ($d) => $this->svc->moveStudent($d, (int) $data['entry_id'], (int) $data['student_id'], $data['room']), 'نُقل الطالب إلى القاعة.');
    }

    /** يحفظ مراقبي قاعات تقييم واحد دفعة واحدة (proctors[اسم القاعة] = user_id أو فارغ). */
    public function proctors(Request $request, $id)
    {
        $data = $request->validate(['entry_id' => 'required|integer', 'proctors' => 'required|array', 'proctors.*' => 'nullable|integer']);

        return $this->act($id, function ($d) use ($data) {
            foreach ($data['proctors'] as $room => $userId) {
                $this->svc->setProctor($d, (int) $data['entry_id'], (string) $room, $userId ? (int) $userId : null);
            }
        }, 'حُفظ المراقبون.');
    }

    public function regenerate($id)
    {
        return $this->act($id, fn ($d) => $this->svc->regenerate($d), 'أُعيد توزيع التقييمات غير المثبّتة.');
    }

    public function approve(Request $request, $id)
    {
        return $this->act($id, fn ($d) => $this->svc->approve($d, Auth::user(), $request->boolean('acknowledge')), 'اعتُمد الجدول الامتحاني وصار ظاهراً للطلاب والمعلمين والمراقبين، وأُشعروا به.');
    }

    public function discard($id)
    {
        $this->act($id, fn ($d) => $this->svc->discard($d), 'أُهملت المسودة.');

        return redirect()->route('hod.exams');
    }

    public function revert($id)
    {
        $draft = $this->draft($id);

        return $this->run(function () use ($draft) {
            $this->svc->revert($draft, Auth::user());

            return redirect()->route('hod.exams.show', $draft->id)->with('success', 'تم التراجع عن الاعتماد.');
        }, $draft->id);
    }

    // ------------------------------------------------------------- مساعدة

    private function dept(): int
    {
        return $this->svc->departmentOf(Auth::user());
    }

    private function draft($id): ScheduleDraft
    {
        return ScheduleDraft::with('semester')->where('department_id', $this->dept())->where('type', ScheduleDraft::TYPE_EXAM)->findOrFail($id);
    }

    private function act($id, callable $fn, string $ok)
    {
        $draft = $this->draft($id);

        return $this->run(function () use ($fn, $draft, $ok) {
            $fn($draft);

            return redirect()->route('hod.exams.show', $draft->id)->with('success', $ok);
        }, $draft->id);
    }

    private function run(callable $fn, ?int $draftId = null)
    {
        try {
            return $fn();
        } catch (ScheduleRuleException $e) {
            $to = $draftId ? redirect()->route('hod.exams.show', $draftId) : redirect()->route('hod.exams');

            return $to->with('error', implode(' — ', $e->messages))->withInput();
        }
    }
}
