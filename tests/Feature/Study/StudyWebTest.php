<?php

namespace Tests\Feature\Study;

use App\Jobs\GenerateStudyMaterialJob;
use App\Models\StudyMaterial;
use App\Services\Study\StudyMaterialGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;
use ZipArchive;

/** صفحات رفيق المذاكرة للمعلم والطالب، وحدود الرؤية. */
class StudyWebTest extends TestCase
{
    use MakesAcademicData;

    private array $teacher;
    private array $student;
    private int $course;
    private int $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Storage::fake('public');
        Queue::fake();
        config(['services.gemini.key' => 'k', 'services.gemini.models' => ['m']]);

        $this->teacher = $this->makeTeacher();
        $tid = (int) DB::table('teachers')->where('user_id', $this->teacher['user']->user_id)->value('teacher_id');
        $this->course = $this->makeCourse($this->makeProgram($this->makeDepartment()), ['title' => 'شبكات']);
        $this->assignTeacher($this->course, $tid);

        $this->student = $this->makeStudent();
        $this->enroll((int) DB::table('students')->where('user_id', $this->student['user']->user_id)->value('student_id'), $this->course);

        $lecture = str_repeat('بروتوكول TCP يضمن وصول الحزم بالترتيب الصحيح. ', 20);
        $tmp = tempnam(sys_get_temp_dir(), 'z');
        $z = new ZipArchive();
        $z->open($tmp, ZipArchive::OVERWRITE);
        $z->addFromString('word/document.xml', "<w:p><w:t>{$lecture}</w:t></w:p>");
        $z->close();
        Storage::disk('public')->put('lectures/n.docx', file_get_contents($tmp));
        unlink($tmp);

        $this->lesson = DB::table('lessons')->insertGetId([
            'course_id' => $this->course, 'teacher_id' => $tid, 'title' => 'محاضرة الشبكات', 'type' => 'pdf',
            'content_url' => 'lectures/n.docx', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function payload(): array
    {
        return [
            'summary' => 'ملخص الشبكات', 'key_terms' => [['term' => 'TCP', 'definition' => 'بروتوكول']],
            'flashcards' => [['front' => 'ما TCP؟', 'back' => 'بروتوكول تسليم'], ['front' => 'ب', 'back' => 'ج'], ['front' => 'د', 'back' => 'هـ']],
            'questions' => [['question' => 'أيها يضمن التسليم؟', 'options' => ['TCP', 'UDP', 'DNS', 'ARP'], 'correct_index' => 0, 'explanation' => 'لأنه يعيد الإرسال']],
        ];
    }

    private function generateDraft(): StudyMaterial
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($this->payload(), JSON_UNESCAPED_UNICODE)]]]]],
        ])]);
        $this->actingAs($this->teacher['user']);
        $this->post("/teacher/study/generate/{$this->lesson}")->assertRedirect();
        $m = StudyMaterial::firstOrFail();
        (new GenerateStudyMaterialJob($m->id))->handle(app(StudyMaterialGenerator::class));

        return $m->fresh();
    }

    public function test_teacher_flow_generate_review_approve(): void
    {
        $this->actingAs($this->teacher['user']);
        $this->get('/teacher/study')->assertOk()->assertSee('محاضرة الشبكات')->assertSee('توليد مسودة')->assertSee('رفيق المذاكرة');

        $m = $this->generateDraft();
        Queue::assertPushed(GenerateStudyMaterialJob::class);

        $this->get("/teacher/study/{$m->id}")->assertOk()->assertSee('ملخص الشبكات')->assertSee('مُولَّد بالذكاء الاصطناعي')->assertSee('اعتماد ونشر للطلاب');

        $this->post("/teacher/study/{$m->id}/approve")->assertSessionHas('success');
        $this->assertSame('approved', $m->fresh()->status);
    }

    public function test_teacher_save_validates_and_replaces_content(): void
    {
        $m = $this->generateDraft();

        $this->post("/teacher/study/{$m->id}/save", [
            'summary' => 'ملخصي', 'flashcards' => [['front' => 'س', 'back' => 'ج']],
            'questions' => [['question' => 'سؤال', 'options' => ['a', 'b', 'c', 'd'], 'correct_index' => 2]],
        ])->assertSessionHas('success');
        $this->assertSame('ملخصي', $m->fresh()->summary);

        $this->post("/teacher/study/{$m->id}/save", ['summary' => 'x', 'questions' => [['question' => 'ناقص', 'options' => ['a'], 'correct_index' => 0]]])
            ->assertSessionHas('error');
        $this->assertSame('ملخصي', $m->fresh()->summary, 'الرفض لا يغيّر شيئًا');
    }

    public function test_another_teacher_cannot_open_or_approve_it(): void
    {
        $m = $this->generateDraft();
        $this->actingAs($this->makeTeacher()['user']);

        $this->get("/teacher/study/{$m->id}")->assertNotFound();
        $this->post("/teacher/study/{$m->id}/approve")->assertNotFound();
        $this->assertSame('draft', $m->fresh()->status);
    }

    public function test_student_sees_only_approved_material_and_practices_it(): void
    {
        $m = $this->generateDraft();

        $this->actingAs($this->student['user']);
        $this->get('/student/study')->assertOk()->assertDontSee('محاضرة الشبكات');
        $this->get("/student/study/{$m->id}")->assertNotFound();

        $this->actingAs($this->teacher['user']);
        $this->post("/teacher/study/{$m->id}/approve");

        $this->actingAs($this->student['user']);
        $this->get('/student/study')->assertSee('محاضرة الشبكات');
        $page = $this->get("/student/study/{$m->id}")->assertOk()->assertSee('ملخص الشبكات')->assertSee('راجعه المعلم');
        $page->assertDontSee('✓ الصحيحة');   // الجواب لا يظهر قبل المحاولة

        $q = $m->questions()->first();
        $this->post("/student/study/{$m->id}/attempt", ['answers' => [$q->id => $q->correct_index]])->assertRedirect();
        $this->get("/student/study/{$m->id}")->assertSee('نتيجتك: 1 من 1')->assertSee('✓ الصحيحة');
    }

    public function test_a_student_outside_the_course_gets_nothing(): void
    {
        $m = $this->generateDraft();
        $this->actingAs($this->teacher['user']);
        $this->post("/teacher/study/{$m->id}/approve");

        $this->actingAs($this->makeStudent()['user']);
        $this->get("/student/study/{$m->id}")->assertNotFound();
        $this->post("/student/study/{$m->id}/attempt", ['answers' => []])->assertNotFound();
    }
}
