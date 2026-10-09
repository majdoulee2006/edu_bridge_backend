<?php

namespace Tests\Feature\Study;

use App\Jobs\GenerateStudyMaterialJob;
use App\Models\StudyMaterial;
use App\Services\Study\StudyException;
use App\Services\Study\StudyMaterialGenerator;
use App\Services\Study\StudyMaterialService;
use App\Services\Study\TextExtractor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;
use ZipArchive;

/** رفيق المذاكرة: استخراج النص، فحص ناتج النموذج، الاعتماد، وتدريب الطالب. */
class StudyCompanionTest extends TestCase
{
    use MakesAcademicData;

    private array $teacher;
    private int $teacherId;
    private int $course;
    private int $lesson;
    private StudyMaterialService $svc;

    /** نص محاضرة عربي أطول من الحد الأدنى. */
    private const LECTURE = 'الشبكة الحاسوبية هي مجموعة أجهزة مترابطة تتبادل البيانات. بروتوكول TCP يضمن وصول الحزم بالترتيب الصحيح ويعيد إرسال المفقود منها. '
        . 'أما بروتوكول UDP فهو أسرع لكنه لا يضمن التسليم ولذلك يستعمل في البث المباشر. عنوان IP يحدد موقع الجهاز في الشبكة، '
        . 'والموجّه يختار أفضل مسار للحزمة بين الشبكات. جدار الحماية يفحص الحزم الداخلة والخارجة ويطبق قواعد الأمان. '
        . 'نظام أسماء النطاقات DNS يترجم أسماء المواقع إلى عناوين IP. طبقات نموذج OSI سبع طبقات تبدأ بالفيزيائية وتنتهي بالتطبيقات.';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Storage::fake('public');
        config(['services.gemini.key' => 'test-key', 'services.gemini.models' => ['gemini-test']]);

        $this->svc = app(StudyMaterialService::class);
        $this->teacher = $this->makeTeacher();
        $this->teacherId = (int) DB::table('teachers')->where('user_id', $this->teacher['user']->user_id)->value('teacher_id');
        $dept = $this->makeDepartment();
        $program = $this->makeProgram($dept);
        $this->course = $this->makeCourse($program, ['title' => 'شبكات']);
        $this->assignTeacher($this->course, $this->teacherId);
        $this->lesson = $this->makeLesson('lectures/net.docx', $this->docx(self::LECTURE));
    }

    // ---------------------------------------------------------------- أدوات

    private function makeLesson(?string $path, ?string $bytes = null, string $title = 'الشبكات 1'): int
    {
        if ($path && $bytes !== null) {
            Storage::disk('public')->put($path, $bytes);
        }

        return DB::table('lessons')->insertGetId([
            'course_id' => $this->course, 'teacher_id' => $this->teacherId, 'title' => $title, 'type' => 'pdf',
            'content_url' => $path, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function docx(string $text): string
    {
        return $this->zip(['word/document.xml' => '<w:document><w:body><w:p><w:r><w:t>' . htmlspecialchars($text) . '</w:t></w:r></w:p></w:body></w:document>']);
    }

    private function pptx(array $slides): string
    {
        $files = [];
        foreach ($slides as $i => $t) {
            $files['ppt/slides/slide' . ($i + 1) . '.xml'] = '<p:sld><a:p><a:r><a:t>' . htmlspecialchars($t) . '</a:t></a:r></a:p></p:sld>';
        }

        return $this->zip($files);
    }

    private function zip(array $files): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zip');
        $z = new ZipArchive();
        $z->open($tmp, ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $z->addFromString($name, $content);
        }
        $z->close();
        $bytes = file_get_contents($tmp);
        unlink($tmp);

        return $bytes;
    }

    /** PDF أدنى بنص لاتيني (يكفي لاختبار مسار smalot). */
    private function pdf(string $text): string
    {
        $stream = "BT /F1 12 Tf 50 700 Td ({$text}) Tj ET";
        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $out = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $o) {
            $offsets[] = strlen($out);
            $out .= ($i + 1) . " 0 obj\n{$o}\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $out .= sprintf("%010d 00000 n \n", $off);
        }

        return $out . 'trailer << /Size ' . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function aiPayload(array $over = []): array
    {
        return array_replace([
            'summary' => 'ملخص عن الشبكات وبروتوكولاتها.',
            'key_terms' => [
                ['term' => 'TCP', 'definition' => 'بروتوكول يضمن التسليم'],
                ['term' => 'بلوكتشين', 'definition' => 'مصطلح ليس في المحاضرة'],
            ],
            'flashcards' => [
                ['front' => 'ما وظيفة TCP؟', 'back' => 'ضمان وصول الحزم بالترتيب'],
                ['front' => 'ما وظيفة DNS؟', 'back' => 'ترجمة الأسماء إلى عناوين IP'],
                ['front' => 'كم طبقة في OSI؟', 'back' => 'سبع'],
            ],
            'questions' => [
                ['question' => 'أي بروتوكول يضمن التسليم؟', 'options' => ['TCP', 'UDP', 'DNS', 'ARP'], 'correct_index' => 0, 'explanation' => 'TCP يعيد إرسال المفقود'],
                ['question' => 'كم طبقة في OSI؟', 'options' => ['خمس', 'ست', 'سبع', 'ثماني'], 'correct_index' => 2],
                ['question' => 'ماذا يفعل DNS؟', 'options' => ['يشفّر', 'يترجم الأسماء', 'يوجّه', 'يضغط'], 'correct_index' => 1],
            ],
        ], $over);
    }

    private function fakeGemini(array $payload): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($payload, JSON_UNESCAPED_UNICODE)]]]]],
        ])]);
    }

    private function runJob(StudyMaterial $m): StudyMaterial
    {
        (new GenerateStudyMaterialJob($m->id))->handle(app(StudyMaterialGenerator::class));

        return $m->fresh();
    }

    /** مادة معتمدة جاهزة وطالب مسجّل. */
    private function approvedMaterial(): array
    {
        Queue::fake();
        $this->fakeGemini($this->aiPayload());
        $m = $this->runJob($this->svc->request($this->teacher['user'], $this->lesson));
        $student = $this->makeStudent();
        $this->enroll($student['student']->student_id ?? DB::table('students')->where('user_id', $student['user']->user_id)->value('student_id'), $this->course);
        $m = $this->svc->approve($m, $this->teacher['user']);

        return [$m, $student];
    }

    // ---------------------------------------------------------------- استخراج النص

    public function test_extracts_text_from_docx_and_pptx_in_slide_order(): void
    {
        $x = new TextExtractor();
        Storage::disk('public')->put('a.docx', $this->docx(self::LECTURE));
        $this->assertStringContainsString('بروتوكول TCP', $x->extract(Storage::disk('public')->path('a.docx')));

        $slides = [str_repeat('الشريحة الأولى ', 30), str_repeat('الشريحة الثانية ', 30)];
        Storage::disk('public')->put('b.pptx', $this->pptx($slides));
        $text = $x->extract(Storage::disk('public')->path('b.pptx'));
        $this->assertLessThan(mb_strpos($text, 'الثانية'), mb_strpos($text, 'الأولى'));
    }

    public function test_extracts_text_from_pdf(): void
    {
        Storage::disk('public')->put('a.pdf', $this->pdf(str_repeat('Networks carry data between computers. ', 20)));

        $this->assertStringContainsString('Networks carry data', (new TextExtractor())->extract(Storage::disk('public')->path('a.pdf')));
    }

    public function test_scanned_corrupt_and_unsupported_files_are_refused_with_a_reason(): void
    {
        $x = new TextExtractor();
        Storage::disk('public')->put('scan.pdf', $this->pdf('x'));   // نص شبه معدوم = ممسوح
        Storage::disk('public')->put('bad.pdf', 'not a pdf at all');
        Storage::disk('public')->put('a.txt', 'hello');

        foreach (['scan.pdf' => 'نص كافٍ', 'bad.pdf' => 'تعذّرت قراءة', 'a.txt' => 'غير مدعوم'] as $file => $needle) {
            try {
                $x->extract(Storage::disk('public')->path($file));
                $this->fail("$file كان يجب أن يُرفض");
            } catch (StudyException $e) {
                $this->assertStringContainsString($needle, $e->getMessage());
            }
        }
    }

    // ---------------------------------------------------------------- فحص ناتج النموذج

    public function test_output_is_validated_ungrounded_terms_and_bad_questions_are_dropped(): void
    {
        $bad = $this->aiPayload();
        $bad['questions'][] = ['question' => 'ثلاثة خيارات', 'options' => ['أ', 'ب', 'ج'], 'correct_index' => 0];
        $bad['questions'][] = ['question' => 'خيارات مكررة', 'options' => ['أ', 'أ', 'ب', 'ج'], 'correct_index' => 0];
        $bad['questions'][] = ['question' => 'فهرس خارج المدى', 'options' => ['أ', 'ب', 'ج', 'د'], 'correct_index' => 4];

        $out = app(StudyMaterialGenerator::class)->parse(json_encode($bad, JSON_UNESCAPED_UNICODE), self::LECTURE);

        $this->assertCount(3, $out['questions'], 'الثلاثة غير الصالحة أُسقطت');
        $this->assertSame(['TCP'], array_column($out['key_terms'], 'term'), 'مصطلح بلوكتشين ليس في المحاضرة فسقط');
    }

    public function test_shuffling_options_keeps_the_correct_answer_pointing_at_the_same_text(): void
    {
        $payload = $this->aiPayload();
        $out = app(StudyMaterialGenerator::class)->parse(json_encode($payload, JSON_UNESCAPED_UNICODE), self::LECTURE);

        foreach ($out['questions'] as $q) {
            $orig = collect($payload['questions'])->firstWhere('question', $q['question']);
            $this->assertSame($orig['options'][$orig['correct_index']], $q['options'][$q['correct_index']]);
            $this->assertEqualsCanonicalizing($orig['options'], $q['options']);
        }
    }

    public function test_garbage_or_too_thin_output_is_rejected(): void
    {
        $g = app(StudyMaterialGenerator::class);

        $this->expectException(StudyException::class);
        $g->parse('هذا ليس JSON', self::LECTURE);
    }

    public function test_output_with_almost_nothing_usable_is_rejected(): void
    {
        $this->expectException(StudyException::class);
        app(StudyMaterialGenerator::class)->parse(json_encode(['summary' => 'x', 'flashcards' => [], 'questions' => []]), self::LECTURE);
    }

    // ---------------------------------------------------------------- التوليد

    public function test_request_queues_a_job_and_the_job_stores_a_draft_students_cannot_see(): void
    {
        Queue::fake();
        $m = $this->svc->request($this->teacher['user'], $this->lesson);
        Queue::assertPushed(GenerateStudyMaterialJob::class);
        $this->assertSame('pending', $m->status);

        $this->fakeGemini($this->aiPayload());
        $m = $this->runJob($m);

        $this->assertSame('draft', $m->status);
        $this->assertSame(3, $m->flashcards()->count());
        $this->assertSame(3, $m->questions()->count());
        $this->assertNotNull($m->source_hash);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'gemini-test') && $r['generationConfig']['responseMimeType'] === 'application/json'
            && str_contains($r['contents'][0]['parts'][0]['text'], 'بروتوكول TCP'));

        $student = $this->makeStudent();
        $this->enroll(DB::table('students')->where('user_id', $student['user']->user_id)->value('student_id'), $this->course);
        $this->assertCount(0, $this->svc->listForStudent($student['user']), 'المسودة لا تظهر للطالب');
    }

    public function test_ai_failure_is_recorded_on_the_material_with_a_readable_reason(): void
    {
        Queue::fake();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('boom', 500)]);

        $m = $this->runJob($this->svc->request($this->teacher['user'], $this->lesson));

        $this->assertSame('failed', $m->status);
        $this->assertStringContainsString('تعذّر الاتصال', $m->failure_reason);
    }

    public function test_request_refuses_links_foreign_lessons_and_unsupported_types(): void
    {
        Queue::fake();
        $link = $this->makeLesson('https://youtu.be/x', null, 'فيديو');
        $txt = $this->makeLesson('lectures/a.txt', 'hi', 'نصي');

        foreach ([[$link, 'رابط فيديو'], [$txt, 'غير مدعوم']] as [$id, $needle]) {
            try {
                $this->svc->request($this->teacher['user'], $id);
                $this->fail('كان يجب الرفض');
            } catch (StudyException $e) {
                $this->assertStringContainsString($needle, $e->getMessage());
            }
        }

        $other = $this->makeTeacher();
        $this->expectException(StudyException::class);
        $this->svc->request($other['user'], $this->lesson);
    }

    public function test_generation_is_rate_limited_per_teacher(): void
    {
        Queue::fake();
        config(['study.generations_per_hour' => 2]);
        RateLimiter::clear('study-gen:' . $this->teacher['user']->user_id);
        $l2 = $this->makeLesson('lectures/b.docx', $this->docx(self::LECTURE), 'ثانية');
        $l3 = $this->makeLesson('lectures/c.docx', $this->docx(self::LECTURE), 'ثالثة');

        $this->svc->request($this->teacher['user'], $this->lesson);
        $this->svc->request($this->teacher['user'], $l2);

        try {
            $this->svc->request($this->teacher['user'], $l3);
            $this->fail('كان يجب بلوغ الحد');
        } catch (StudyException $e) {
            $this->assertStringContainsString('حد التوليد', $e->getMessage());
        }
    }

    // ---------------------------------------------------------------- مراجعة المعلم واعتماده

    public function test_teacher_edits_are_validated_and_replace_the_content(): void
    {
        Queue::fake();
        $this->fakeGemini($this->aiPayload());
        $m = $this->runJob($this->svc->request($this->teacher['user'], $this->lesson));

        $m = $this->svc->update($m, [
            'summary' => 'ملخصي المعدّل',
            'flashcards' => [['front' => 'س', 'back' => 'ج']],
            'questions' => [['question' => 'سؤال المعلم', 'options' => ['1', '2', '3', '4'], 'correct_index' => 3]],
        ]);

        $this->assertSame('ملخصي المعدّل', $m->summary);
        $this->assertSame(1, $m->flashcards()->count());
        $this->assertSame(3, $m->questions()->first()->correct_index);

        $this->expectException(StudyException::class);
        $this->svc->update($m, ['summary' => 'x', 'questions' => [['question' => 'ناقص', 'options' => ['1', '2'], 'correct_index' => 0]]]);
    }

    public function test_only_the_owning_teacher_can_open_a_material(): void
    {
        Queue::fake();
        $this->fakeGemini($this->aiPayload());
        $m = $this->runJob($this->svc->request($this->teacher['user'], $this->lesson));

        $this->assertSame($m->id, $this->svc->ownedBy($this->teacher['user'], $m->id)->id);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->svc->ownedBy($this->makeTeacher()['user'], $m->id);
    }

    public function test_approval_publishes_to_enrolled_students_only_and_notifies_them(): void
    {
        [$m, $student] = $this->approvedMaterial();
        $outsider = $this->makeStudent();

        $this->assertSame('approved', $m->status);
        $this->assertCount(1, $this->svc->listForStudent($student['user']));
        $this->assertCount(0, $this->svc->listForStudent($outsider['user']));
        $this->assertSame([$student['user']->user_id], DB::table('notifications')->where('type', 'study_material')->pluck('user_id')->all());

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->svc->showForStudent($outsider['user'], $m->id);
    }

    public function test_an_approved_material_cannot_be_regenerated_or_edited_until_unpublished(): void
    {
        [$m] = $this->approvedMaterial();

        try {
            $this->svc->request($this->teacher['user'], $this->lesson);
            $this->fail('لا إعادة توليد لمنشور');
        } catch (StudyException $e) {
            $this->assertStringContainsString('ألغِ نشرها', $e->getMessage());
        }
        try {
            $this->svc->update($m, ['summary' => 'x']);
            $this->fail('لا تعديل لمنشور');
        } catch (StudyException) {
        }

        $this->assertSame('draft', $this->svc->unpublish($m)->status);
    }

    // ---------------------------------------------------------------- تدريب الطالب

    public function test_attempt_is_graded_on_the_server_and_never_touches_marks(): void
    {
        [$m, $student] = $this->approvedMaterial();
        $qs = $m->questions()->get();
        $answers = [];
        foreach ($qs as $i => $q) {
            $answers[$q->id] = $i === 0 ? $q->correct_index : ($q->correct_index + 1) % 4;   // الأول صحيح والباقي خطأ
        }
        $marksBefore = DB::table('grades')->count();

        $result = $this->svc->submitAttempt($student['user'], $m->id, $answers);

        $this->assertSame([1, 3], [$result['score'], $result['total']]);
        $this->assertTrue($result['review'][0]['is_correct']);
        $this->assertFalse($result['review'][1]['is_correct']);
        $this->assertSame(1, DB::table('study_attempts')->where('student_user_id', $student['user']->user_id)->count());
        $this->assertSame($marksBefore, DB::table('grades')->count(), 'التدريب لا يلمس العلامات (grades)');
    }

    public function test_unanswered_questions_count_as_wrong(): void
    {
        [$m, $student] = $this->approvedMaterial();

        $this->assertSame(0, $this->svc->submitAttempt($student['user'], $m->id, [])['score']);
    }

    // ---------------------------------------------------------------- تصدير إلى كويز

    public function test_export_creates_an_unpublished_quiz_with_the_right_answers(): void
    {
        [$m] = $this->approvedMaterial();

        $quiz = $this->svc->exportToQuiz($m);

        $this->assertFalse((bool) $quiz->is_published);
        $this->assertCount(3, $quiz->questions);
        foreach ($m->questions()->get() as $i => $q) {
            $correct = $quiz->questions[$i]->options->where('is_correct', true);
            $this->assertCount(1, $correct);
            $this->assertSame($q->options[$q->correct_index], $correct->first()->option_text);
        }
    }
}
