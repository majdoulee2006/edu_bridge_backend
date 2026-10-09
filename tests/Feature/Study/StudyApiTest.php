<?php

namespace Tests\Feature\Study;

use App\Jobs\GenerateStudyMaterialJob;
use App\Models\StudyMaterial;
use App\Services\Study\StudyMaterialGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;
use ZipArchive;

/** واجهة API لرفيق المذاكرة (تطبيق Flutter): صلاحيات، وعدم تسريب الجواب الصحيح قبل الإجابة. */
class StudyApiTest extends TestCase
{
    use MakesAcademicData;

    private array $teacher;
    private array $student;
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
        $course = $this->makeCourse($this->makeProgram($this->makeDepartment()), ['title' => 'شبكات']);
        $this->assignTeacher($course, $tid);
        $this->student = $this->makeStudent();
        $this->enroll((int) DB::table('students')->where('user_id', $this->student['user']->user_id)->value('student_id'), $course);

        $tmp = tempnam(sys_get_temp_dir(), 'z');
        $z = new ZipArchive();
        $z->open($tmp, ZipArchive::OVERWRITE);
        $z->addFromString('word/document.xml', '<w:p><w:t>' . str_repeat('بروتوكول TCP يضمن وصول الحزم بالترتيب. ', 20) . '</w:t></w:p>');
        $z->close();
        Storage::disk('public')->put('lectures/n.docx', file_get_contents($tmp));
        unlink($tmp);

        $this->lesson = DB::table('lessons')->insertGetId([
            'course_id' => $course, 'teacher_id' => $tid, 'title' => 'محاضرة', 'type' => 'pdf',
            'content_url' => 'lectures/n.docx', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function draft(): StudyMaterial
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
            'summary' => 'ملخص', 'key_terms' => [['term' => 'TCP', 'definition' => 'ب']],
            'flashcards' => [['front' => 'أ', 'back' => 'ب'], ['front' => 'ج', 'back' => 'د'], ['front' => 'هـ', 'back' => 'و']],
            'questions' => [['question' => 'أيها يضمن؟', 'options' => ['TCP', 'UDP', 'DNS', 'ARP'], 'correct_index' => 0, 'explanation' => 'شرح سري']],
        ], JSON_UNESCAPED_UNICODE)]]]]]])]);

        Sanctum::actingAs($this->teacher['user']);
        $this->postJson("/api/teacher/study/generate/{$this->lesson}")->assertStatus(202);
        $m = StudyMaterial::firstOrFail();
        (new GenerateStudyMaterialJob($m->id))->handle(app(StudyMaterialGenerator::class));

        return $m->fresh();
    }

    public function test_teacher_generates_reviews_and_approves(): void
    {
        $m = $this->draft();

        $this->getJson('/api/teacher/study')->assertOk()->assertJsonPath('data.0.status', 'draft')->assertJsonPath('data.0.can_generate', true);
        $show = $this->getJson("/api/teacher/study/{$m->id}")->assertOk();
        $this->assertIsInt($show->json('data.questions.0.correct_index'), 'المعلم يرى الجواب الصحيح');
        $this->assertSame('شرح سري', $show->json('data.questions.0.explanation'));

        $this->postJson("/api/teacher/study/{$m->id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/teacher/study/{$m->id}/approve")->assertStatus(422);   // مرتان = رفض مفهوم
    }

    public function test_student_never_receives_the_answers_before_attempting(): void
    {
        $m = $this->draft();
        $this->postJson("/api/teacher/study/{$m->id}/approve")->assertOk();

        Sanctum::actingAs($this->student['user']);
        $res = $this->getJson("/api/student/study/{$m->id}")->assertOk();
        $this->assertStringNotContainsString('correct_index', $res->getContent());
        $this->assertStringNotContainsString('شرح سري', $res->getContent());
        $res->assertJsonPath('data.ai_generated', true)->assertJsonPath('data.teacher_reviewed', true);

        $qid = $res->json('data.questions.0.id');
        $options = $res->json('data.questions.0.options');
        $right = array_search('TCP', $options, true);

        $this->postJson("/api/student/study/{$m->id}/attempt", ['answers' => [$qid => $right]])
            ->assertOk()->assertJsonPath('data.score', 1)->assertJsonPath('data.review.0.explanation', 'شرح سري');
    }

    public function test_drafts_and_other_roles_are_blocked(): void
    {
        $m = $this->draft();

        Sanctum::actingAs($this->student['user']);
        $this->getJson("/api/student/study/{$m->id}")->assertNotFound();
        $this->getJson('/api/teacher/study')->assertForbidden();

        Sanctum::actingAs($this->makeTeacher()['user']);
        $this->getJson("/api/teacher/study/{$m->id}")->assertNotFound();
        $this->postJson("/api/teacher/study/{$m->id}/approve")->assertNotFound();
    }
}
