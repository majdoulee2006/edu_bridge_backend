<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyMaterial;
use App\Services\Study\StudyException;
use App\Services\Study\StudyMaterialService;
use Illuminate\Http\Request;

/**
 * رفيق المذاكرة عبر API: المعلم (توليد/مراجعة/اعتماد) والطالب (قراءة وتدريب).
 * المنطق كله في StudyMaterialService نفسه الذي تستعمله صفحات الويب.
 */
class StudyController extends Controller
{
    public function __construct(private StudyMaterialService $svc)
    {
    }

    // ------------------------------------------------------------------ المعلم

    public function teacherIndex(Request $request)
    {
        $rows = $this->svc->overview($request->user())->map(fn ($r) => [
            'lesson_id' => $r['lesson']->lesson_id, 'title' => $r['lesson']->title, 'course' => $r['lesson']->course?->title,
            'can_generate' => $r['whyNot'] === null, 'why_not' => $r['whyNot'],
            'material_id' => $r['material']?->id, 'status' => $r['material']?->status, 'failure_reason' => $r['material']?->failure_reason,
        ])->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function generate(Request $request, $lessonId)
    {
        return $this->guard(function () use ($request, $lessonId) {
            $m = $this->svc->request($request->user(), (int) $lessonId);

            return response()->json(['success' => true, 'message' => 'بدأ التوليد في الخلفية', 'data' => ['material_id' => $m->id, 'status' => $m->status]], 202);
        });
    }

    public function teacherShow(Request $request, $id)
    {
        $m = $this->svc->ownedBy($request->user(), (int) $id)->load(['lesson', 'flashcards', 'questions']);

        return response()->json(['success' => true, 'data' => $this->full($m)]);
    }

    public function update(Request $request, $id)
    {
        $m = $this->svc->ownedBy($request->user(), (int) $id);
        $data = $request->validate([
            'summary' => 'required|string|max:4000',
            'flashcards' => 'nullable|array|max:40', 'flashcards.*.front' => 'nullable|string|max:500', 'flashcards.*.back' => 'nullable|string|max:2000',
            'questions' => 'nullable|array|max:40', 'questions.*.question' => 'nullable|string|max:1000',
            'questions.*.options' => 'nullable|array', 'questions.*.options.*' => 'nullable|string|max:300',
            'questions.*.correct_index' => 'nullable|integer|between:0,3', 'questions.*.explanation' => 'nullable|string|max:1000',
        ]);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => $this->full($this->svc->update($m, $data)->load(['flashcards', 'questions']))]));
    }

    public function approve(Request $request, $id)
    {
        $m = $this->svc->ownedBy($request->user(), (int) $id);

        return $this->guard(fn () => response()->json(['success' => true, 'message' => 'اعتُمدت المادة وصارت ظاهرة للطلاب', 'data' => ['status' => $this->svc->approve($m, $request->user())->status]]));
    }

    public function unpublish(Request $request, $id)
    {
        $m = $this->svc->ownedBy($request->user(), (int) $id);

        return $this->guard(fn () => response()->json(['success' => true, 'data' => ['status' => $this->svc->unpublish($m)->status]]));
    }

    public function export(Request $request, $id)
    {
        $m = $this->svc->ownedBy($request->user(), (int) $id);

        return $this->guard(fn () => response()->json(['success' => true, 'message' => 'صُدّرت الأسئلة إلى كويز مسودة غير منشور', 'data' => ['quiz_id' => $this->svc->exportToQuiz($m)->id]], 201));
    }

    // ------------------------------------------------------------------ الطالب

    public function studentIndex(Request $request)
    {
        $rows = $this->svc->listForStudent($request->user())->map(fn ($m) => [
            'id' => $m->id, 'title' => $m->lesson?->title, 'flashcards' => $m->flashcards_count, 'questions' => $m->questions_count,
            'approved_at' => $m->approved_at?->toIso8601String(),
        ])->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /** الأسئلة بلا الجواب الصحيح ولا الشرح: يصلان بعد الإجابة فقط. */
    public function studentShow(Request $request, $id)
    {
        $m = $this->svc->showForStudent($request->user(), (int) $id);

        return response()->json(['success' => true, 'data' => [
            'id' => $m->id, 'title' => $m->lesson?->title, 'summary' => $m->summary, 'key_terms' => $m->key_terms ?? [],
            'ai_generated' => true, 'teacher_reviewed' => true,
            'flashcards' => $m->flashcards->map(fn ($c) => ['front' => $c->front, 'back' => $c->back])->values(),
            'questions' => $m->questions->map(fn ($q) => ['id' => $q->id, 'question' => $q->question, 'options' => $q->options])->values(),
        ]]);
    }

    public function attempt(Request $request, $id)
    {
        $data = $request->validate(['answers' => 'nullable|array', 'answers.*' => 'nullable|integer|between:0,3']);

        return response()->json(['success' => true, 'data' => $this->svc->submitAttempt($request->user(), (int) $id, $data['answers'] ?? [])]);
    }

    // ------------------------------------------------------------------ مساعدة

    private function full(StudyMaterial $m): array
    {
        return [
            'id' => $m->id, 'status' => $m->status, 'failure_reason' => $m->failure_reason, 'lesson' => $m->lesson?->title,
            'summary' => $m->summary, 'key_terms' => $m->key_terms ?? [],
            'flashcards' => $m->flashcards->map(fn ($c) => ['front' => $c->front, 'back' => $c->back])->values(),
            'questions' => $m->questions->map(fn ($q) => ['question' => $q->question, 'options' => $q->options, 'correct_index' => $q->correct_index, 'explanation' => $q->explanation])->values(),
        ];
    }

    private function guard(callable $fn)
    {
        try {
            return $fn();
        } catch (StudyException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
