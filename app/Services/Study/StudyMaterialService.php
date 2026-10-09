<?php

namespace App\Services\Study;

use App\Jobs\GenerateStudyMaterialJob;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\StudyAttempt;
use App\Models\StudyMaterial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * دورة حياة مادة المذاكرة: طلب توليد ← مسودة ← مراجعة وتعديل المعلم ← اعتماد ← تدريب الطلاب.
 * القاعدة الثابتة: الطالب لا يرى إلا المعتمَد، والتدريب لا يدخل في العلامات.
 */
class StudyMaterialService
{
    public function __construct(private StudyMaterialGenerator $generator)
    {
    }

    /** يطلب توليد مسودة لمحاضرة يملكها المعلم. @throws StudyException */
    public function request(User $teacherUser, int $lessonId): StudyMaterial
    {
        $teacher = $teacherUser->teacher ?? throw new StudyException('هذه الميزة للمعلمين فقط.');
        $lesson = Lesson::where('lesson_id', $lessonId)->first() ?? throw new StudyException('المحاضرة غير موجودة.');

        $teaches = DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->where('course_id', $lesson->course_id)->exists();
        if (!$teaches && (int) $lesson->teacher_id !== (int) $teacher->teacher_id) {
            throw new StudyException('هذه المحاضرة ليست من مقرراتك.');
        }
        if ($why = $this->generator->whyNot($lesson->content_url)) {
            throw new StudyException($why);
        }

        $existing = StudyMaterial::where('lesson_id', $lesson->lesson_id)->first();
        if ($existing?->status === StudyMaterial::APPROVED) {
            throw new StudyException('المادة معتمدة ومنشورة للطلاب. ألغِ نشرها أولًا إن أردت إعادة توليدها.');
        }
        if ($existing?->status === StudyMaterial::PENDING) {
            throw new StudyException('التوليد جارٍ بالفعل لهذه المحاضرة.');
        }

        $key = 'study-gen:' . $teacherUser->user_id;
        $limit = (int) config('study.generations_per_hour');
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw new StudyException("بلغت حد التوليد ({$limit} في الساعة). حاول لاحقًا.");
        }
        RateLimiter::hit($key, 3600);

        $material = StudyMaterial::updateOrCreate(
            ['lesson_id' => $lesson->lesson_id],
            ['course_id' => $lesson->course_id, 'teacher_id' => $teacher->teacher_id, 'status' => StudyMaterial::PENDING, 'failure_reason' => null],
        );
        GenerateStudyMaterialJob::dispatch($material->id);

        return $material;
    }

    /** محاضرات المعلم مع حالة مادة المذاكرة وسبب عدم قابليتها للتوليد (إن وُجد). */
    public function overview(User $teacherUser)
    {
        $courseIds = DB::table('course_teachers')->where('teacher_id', $teacherUser->teacher->teacher_id)->pluck('course_id');
        $lessons = Lesson::with('course:course_id,title')->whereIn('course_id', $courseIds)->orderByDesc('lesson_id')->limit(100)->get();
        $materials = StudyMaterial::whereIn('lesson_id', $lessons->pluck('lesson_id'))->get()->keyBy('lesson_id');

        return $lessons->map(fn ($l) => ['lesson' => $l, 'material' => $materials->get($l->lesson_id), 'whyNot' => $this->generator->whyNot($l->content_url)]);
    }

    /** مادة يملكها المعلم (يدرّس مقررها). */
    public function ownedBy(User $teacherUser, int $materialId): StudyMaterial
    {
        $m = StudyMaterial::findOrFail($materialId);
        $teacher = $teacherUser->teacher;
        $ok = $teacher && DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->where('course_id', $m->course_id)->exists();
        abort_unless($ok || ($teacher && (int) $m->teacher_id === (int) $teacher->teacher_id), 404);

        return $m;
    }

    /**
     * تعديل المعلم الكامل للمسودة: يستبدل الملخص والبطاقات والأسئلة بما أرسله بعد فحصه.
     *
     * @param array{summary:string,key_terms?:array,flashcards?:array,questions?:array} $data
     * @throws StudyException
     */
    public function update(StudyMaterial $m, array $data): StudyMaterial
    {
        if (!in_array($m->status, [StudyMaterial::DRAFT, StudyMaterial::FAILED], true)) {
            throw new StudyException('لا يمكن تعديل المادة الآن (منشورة أو قيد التوليد).');
        }
        $summary = trim((string) ($data['summary'] ?? ''));
        if ($summary === '') {
            throw new StudyException('الملخص مطلوب.');
        }

        $cards = [];
        foreach ((array) ($data['flashcards'] ?? []) as $c) {
            $f = trim((string) ($c['front'] ?? ''));
            $b = trim((string) ($c['back'] ?? ''));
            if ($f !== '' && $b !== '') {
                $cards[] = ['front' => mb_substr($f, 0, 500), 'back' => $b];
            }
        }

        $questions = [];
        foreach ((array) ($data['questions'] ?? []) as $q) {
            $text = trim((string) ($q['question'] ?? ''));
            $opts = array_values(array_map(fn ($o) => trim((string) $o), (array) ($q['options'] ?? [])));
            $idx = (int) ($q['correct_index'] ?? -1);
            if ($text === '') {
                continue;
            }
            if (count($opts) !== 4 || in_array('', $opts, true) || count(array_unique($opts)) !== 4 || $idx < 0 || $idx > 3) {
                throw new StudyException('السؤال «' . mb_substr($text, 0, 40) . '» يحتاج أربعة خيارات مختلفة وجوابًا صحيحًا واحدًا.');
            }
            $questions[] = ['question' => $text, 'options' => $opts, 'correct_index' => $idx, 'explanation' => trim((string) ($q['explanation'] ?? '')) ?: null];
        }

        DB::transaction(function () use ($m, $summary, $cards, $questions, $data) {
            $m->flashcards()->delete();
            $m->questions()->delete();
            foreach ($cards as $i => $c) {
                $m->flashcards()->create($c + ['position' => $i]);
            }
            foreach ($questions as $i => $q) {
                $m->questions()->create($q + ['position' => $i]);
            }
            $m->update(['summary' => mb_substr($summary, 0, 4000), 'key_terms' => $data['key_terms'] ?? $m->key_terms, 'status' => StudyMaterial::DRAFT, 'failure_reason' => null]);
        });

        return $m->fresh();
    }

    /** اعتماد المعلم: من هنا فقط يراها الطلاب. @throws StudyException */
    public function approve(StudyMaterial $m, User $by): StudyMaterial
    {
        if ($m->status !== StudyMaterial::DRAFT) {
            throw new StudyException('الاعتماد للمسودات فقط.');
        }
        if (!$m->summary || ($m->flashcards()->count() === 0 && $m->questions()->count() === 0)) {
            throw new StudyException('لا يمكن اعتماد مادة فارغة.');
        }

        $m->update(['status' => StudyMaterial::APPROVED, 'approved_at' => now(), 'approved_by' => $by->user_id]);
        $this->notifyStudents($m, $by);

        return $m->fresh();
    }

    public function unpublish(StudyMaterial $m): StudyMaterial
    {
        if ($m->status !== StudyMaterial::APPROVED) {
            throw new StudyException('المادة غير منشورة.');
        }
        $m->update(['status' => StudyMaterial::DRAFT, 'approved_at' => null, 'approved_by' => null]);

        return $m->fresh();
    }

    /** يصدّر الأسئلة إلى كويز رسمي مسودة (غير منشور)؛ المعلم يضبط مواعيده ثم ينشره بنفسه. */
    public function exportToQuiz(StudyMaterial $m): Quiz
    {
        if ($m->questions()->count() === 0) {
            throw new StudyException('لا أسئلة لتصديرها.');
        }

        return DB::transaction(function () use ($m) {
            $quiz = Quiz::create([
                'teacher_id' => $m->teacher_id, 'course_id' => $m->course_id, 'title' => 'اختبار: ' . ($m->lesson?->title ?? 'محاضرة'),
                'description' => 'مسودة مصدّرة من مواد المذاكرة. راجعها وحدّد موعدها قبل النشر.',
                'duration_minutes' => 20, 'total_marks' => $m->questions()->count(), 'is_published' => false,
            ]);
            foreach ($m->questions as $i => $q) {
                $qq = $quiz->questions()->create(['question_text' => $q->question, 'type' => 'mcq', 'marks' => 1, 'order_num' => $i]);
                foreach ($q->options as $oi => $text) {
                    $qq->options()->create(['option_text' => $text, 'is_correct' => $oi === $q->correct_index]);
                }
            }

            return $quiz;
        });
    }

    // ------------------------------------------------------------------ الطالب

    /** المواد المعتمدة لمقررات الطالب المسجّل فيها. */
    public function listForStudent(User $student)
    {
        $courseIds = DB::table('enrollments')->join('students', 'students.student_id', '=', 'enrollments.student_id')
            ->where('students.user_id', $student->user_id)->pluck('enrollments.course_id');

        return StudyMaterial::with('lesson:lesson_id,title,course_id')->withCount(['flashcards', 'questions'])
            ->where('status', StudyMaterial::APPROVED)->whereIn('course_id', $courseIds)->orderByDesc('approved_at')->get();
    }

    public function showForStudent(User $student, int $materialId): StudyMaterial
    {
        $m = $this->listForStudent($student)->firstWhere('id', $materialId);
        abort_unless($m, 404);

        return $m->load(['flashcards', 'questions']);
    }

    /**
     * يصحّح الإجابات في الخادم (الجواب الصحيح لا يُرسل للطالب قبل أن يجيب) ويحفظ المحاولة. لا علامات.
     *
     * @param array<int,int> $answers question_id => chosen_index
     */
    public function submitAttempt(User $student, int $materialId, array $answers): array
    {
        $m = $this->showForStudent($student, $materialId);
        $score = 0;
        $review = [];
        foreach ($m->questions as $q) {
            $chosen = isset($answers[$q->id]) ? (int) $answers[$q->id] : null;
            $ok = $chosen === $q->correct_index;
            $score += $ok ? 1 : 0;
            $review[] = ['question_id' => $q->id, 'chosen' => $chosen, 'correct_index' => $q->correct_index, 'is_correct' => $ok, 'explanation' => $q->explanation];
        }
        StudyAttempt::create(['material_id' => $m->id, 'student_user_id' => $student->user_id, 'score' => $score, 'total' => $m->questions->count(), 'answers' => $answers]);

        return ['score' => $score, 'total' => $m->questions->count(), 'review' => $review];
    }

    private function notifyStudents(StudyMaterial $m, User $by): void
    {
        $userIds = DB::table('enrollments')->join('students', 'students.student_id', '=', 'enrollments.student_id')
            ->join('users', 'users.user_id', '=', 'students.user_id')->where('enrollments.course_id', $m->course_id)
            ->where('users.status', 'active')->pluck('users.user_id');
        $now = now();
        $title = '📚 مادة مذاكرة جديدة — ' . ($m->lesson?->title ?? 'محاضرة');
        $rows = $userIds->map(fn ($uid) => [
            'user_id' => $uid, 'sender_id' => $by->user_id, 'title' => $title,
            'message' => 'أعدّ معلمك ملخصًا وبطاقات وأسئلة تدريب لهذه المحاضرة. التدريب لا يُحتسب بالعلامات. (محتوى مُولَّد بمساعدة الذكاء الاصطناعي وراجعه المعلم)',
            'type' => 'study_material', 'category' => 'academic', 'related_id' => $m->id, 'is_read' => false, 'created_at' => $now, 'updated_at' => $now,
        ])->values()->all();
        foreach (array_chunk($rows, 300) as $chunk) {
            Notification::insert($chunk);
        }
    }
}
