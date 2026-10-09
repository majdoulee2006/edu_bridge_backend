<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\StudyMaterial;
use App\Services\Study\StudyException;
use App\Services\Study\StudyMaterialGenerator;
use App\Services\Study\StudyMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * رفيق المذاكرة: صفحات المعلم (توليد مسودة، مراجعة، اعتماد، تصدير إلى كويز) وصفحات الطالب (قراءة وتدريب).
 * المنطق كله في StudyMaterialService؛ هنا العرض وتحويل الرفض إلى رسائل.
 */
class StudyWebController extends Controller
{
    public function __construct(private StudyMaterialService $svc, private StudyMaterialGenerator $generator)
    {
    }

    // ------------------------------------------------------------------ المعلم

    public function teacherIndex()
    {
        return view('study.teacher_index', [
            'rows' => $this->svc->overview(Auth::user()),
            'aiReady' => app(\App\Services\Ai\GeminiClient::class)->isConfigured() && config('study.enabled'),
        ]);
    }

    public function teacherGenerate($lessonId)
    {
        return $this->guard(function () use ($lessonId) {
            $m = $this->svc->request(Auth::user(), (int) $lessonId);

            return redirect()->route('teacher.study.show', $m->id)->with('success', 'بدأ التوليد في الخلفية. حدّث الصفحة بعد لحظات.');
        }, redirect()->route('teacher.study'));
    }

    public function teacherShow($id)
    {
        $m = $this->svc->ownedBy(Auth::user(), (int) $id)->load(['lesson', 'flashcards', 'questions']);

        return view('study.teacher_show', ['m' => $m]);
    }

    public function teacherSave(Request $request, $id)
    {
        $m = $this->svc->ownedBy(Auth::user(), (int) $id);
        $data = $request->validate([
            'summary' => 'required|string|max:4000',
            'flashcards' => 'nullable|array|max:40', 'flashcards.*.front' => 'nullable|string|max:500', 'flashcards.*.back' => 'nullable|string|max:2000',
            'questions' => 'nullable|array|max:40', 'questions.*.question' => 'nullable|string|max:1000',
            'questions.*.options' => 'nullable|array', 'questions.*.options.*' => 'nullable|string|max:300',
            'questions.*.correct_index' => 'nullable|integer|between:0,3', 'questions.*.explanation' => 'nullable|string|max:1000',
        ]);

        return $this->guard(function () use ($m, $data) {
            $this->svc->update($m, $data);

            return back()->with('success', 'حُفظت التعديلات.');
        }, back());
    }

    public function teacherApprove($id)
    {
        $m = $this->svc->ownedBy(Auth::user(), (int) $id);

        return $this->guard(function () use ($m) {
            $this->svc->approve($m, Auth::user());

            return back()->with('success', 'اعتُمدت المادة وصارت ظاهرة لطلاب المقرر، وأُشعروا بها.');
        }, back());
    }

    public function teacherUnpublish($id)
    {
        $m = $this->svc->ownedBy(Auth::user(), (int) $id);

        return $this->guard(function () use ($m) {
            $this->svc->unpublish($m);

            return back()->with('success', 'أُلغي النشر: عادت المادة مسودة لا يراها الطلاب.');
        }, back());
    }

    public function teacherExport($id)
    {
        $m = $this->svc->ownedBy(Auth::user(), (int) $id);

        return $this->guard(function () use ($m) {
            $quiz = $this->svc->exportToQuiz($m);

            return back()->with('success', "صُدّرت الأسئلة إلى كويز مسودة «{$quiz->title}» غير منشور. اضبط مواعيده من صفحة الاختبارات ثم انشره.");
        }, back());
    }

    // ------------------------------------------------------------------ الطالب

    public function studentIndex()
    {
        return view('study.student_index', ['materials' => $this->svc->listForStudent(Auth::user())]);
    }

    public function studentShow($id)
    {
        $m = $this->svc->showForStudent(Auth::user(), (int) $id);
        $last = DB::table('study_attempts')->where('material_id', $m->id)->where('student_user_id', Auth::id())->orderByDesc('id')->first();

        return view('study.student_show', ['m' => $m, 'last' => $last, 'result' => session('study_result')]);
    }

    public function studentAttempt(Request $request, $id)
    {
        $data = $request->validate(['answers' => 'nullable|array', 'answers.*' => 'nullable|integer|between:0,3']);
        $result = $this->svc->submitAttempt(Auth::user(), (int) $id, $data['answers'] ?? []);

        return redirect()->route('student.study.show', $id)->with('study_result', $result)->withFragment('practice');
    }

    // ------------------------------------------------------------------ مساعدة

    private function guard(callable $fn, $onError)
    {
        try {
            return $fn();
        } catch (StudyException $e) {
            return $onError->with('error', $e->getMessage());
        }
    }
}
