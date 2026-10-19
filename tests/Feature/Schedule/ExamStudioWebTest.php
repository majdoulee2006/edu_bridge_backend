<?php

namespace Tests\Feature\Schedule;

use App\Models\Exam;
use App\Models\ScheduleDraft;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** واجهة الويب لمولّد الامتحانات: نموذج الإدخال، المسودة، المراقبون، الاعتماد، وحصرها برئيس القسم. */
class ExamStudioWebTest extends ExamScenario
{
    protected $head;
    protected int $teacherId;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->room('قاعة أ1', 4, $this->programA, 1);
        $this->room('قاعة أ1 ب', 4, $this->programA, 1);
        $this->room('قاعة ب1', 4, $this->programA, 2);
        $this->room('قاعة ت1', 3, $this->programB, 1);
        $this->head = $this->makeHead($this->dept)['user'];
        $deptName = DB::table('departments')->where('department_id', $this->dept)->value('name');
        $this->teacherId = $this->makeTeacher(['full_name' => 'مراقب', 'department' => $deptName])['user']->user_id;
    }

    protected function payload(array $override = []): array
    {
        $k = $this->kinds();
        $form = fn (array $c) => [
            'enabled' => '1', 'from' => $c['from'], 'to' => $c['to'], 'duration' => $c['duration'], 'excluded' => '',
            'slots' => array_map(fn ($s) => ['start' => $s[0], 'end' => $s[1]], $c['slots']),
        ];

        return array_replace_recursive(['kinds' => ['midterm' => $form($k['midterm']), 'final' => $form($k['final'])]], $override);
    }

    public function test_page_shows_the_form_with_both_periods_and_readiness(): void
    {
        $html = $this->actingAs($this->head)->get('/hod/exam-studio')->assertOk()->getContent();

        $this->assertStringContainsString('ابنِ لي جدول المذاكرات والامتحانات', $html);
        $this->assertStringContainsString('name="kinds[midterm][from]"', $html);
        $this->assertStringContainsString('name="kinds[final][duration]"', $html);
        $this->assertStringContainsString('جاهزية البيانات', $html);
    }

    public function test_generating_redirects_to_a_draft_page_with_rooms_and_proctor_dropdowns(): void
    {
        $r = $this->actingAs($this->head)->post('/hod/exam-studio/generate', $this->payload());
        $draft = ScheduleDraft::where('type', 'exam')->firstOrFail();
        $r->assertRedirect(route('hod.exams.show', $draft->id));
        $this->assertSame(0, Exam::count());

        $html = $this->get('/hod/exam-studio/' . $draft->id)->assertOk()->getContent();
        $this->assertStringContainsString('name="proctors[', $html);
        $this->assertStringContainsString('مراقب', $html);
        $this->assertStringContainsString('اعتماد الجدول', $html);
    }

    public function test_invalid_dates_are_rejected_with_a_clear_message_and_nothing_is_saved(): void
    {
        $bad = $this->payload(['kinds' => ['final' => ['from' => 'x']]]);
        $this->actingAs($this->head)->post('/hod/exam-studio/generate', $bad)->assertSessionHas('error');
        $this->assertSame(0, ScheduleDraft::where('type', 'exam')->count());
    }

    public function test_an_unchecked_kind_is_not_generated(): void
    {
        $p = $this->payload(['kinds' => ['midterm' => ['enabled' => '0']]]);
        $this->actingAs($this->head)->post('/hod/exam-studio/generate', $p);
        $draft = ScheduleDraft::where('type', 'exam')->firstOrFail();

        $this->assertSame(['final'], array_keys($draft->settings['kinds']));
        $this->assertSame(6, $draft->examEntries()->count());
    }

    public function test_proctors_are_saved_and_approval_publishes(): void
    {
        $this->actingAs($this->head)->post('/hod/exam-studio/generate', $this->payload());
        $draft = ScheduleDraft::where('type', 'exam')->firstOrFail();
        $e = $draft->examEntries()->first();

        $this->post("/hod/exam-studio/{$draft->id}/proctors", ['entry_id' => $e->id, 'proctors' => [$e->rooms[0]['name'] => $this->teacherId]])->assertSessionHas('success');
        $this->assertSame($this->teacherId, $e->fresh()->rooms[0]['proctor']);

        $this->post("/hod/exam-studio/{$draft->id}/approve")->assertSessionHas('error');   // قاعات بلا مراقب وبلا إقرار
        $this->assertSame(0, Exam::count());

        $this->post("/hod/exam-studio/{$draft->id}/approve", ['acknowledge' => 1])->assertSessionHas('success');
        $this->assertSame(12, Exam::count());
    }

    public function test_only_the_department_head_can_open_it(): void
    {
        $teacher = $this->makeTeacher()['user'];
        $this->actingAs($teacher)->get('/hod/exam-studio')->assertRedirect();
    }

    public function test_another_departments_draft_is_not_reachable(): void
    {
        $this->actingAs($this->head)->post('/hod/exam-studio/generate', $this->payload());
        $draft = ScheduleDraft::where('type', 'exam')->firstOrFail();

        $otherHead = $this->makeHead($this->makeDepartment('قسم آخر'))['user'];
        $this->actingAs($otherHead)->get('/hod/exam-studio/' . $draft->id)->assertNotFound();
        $this->post("/hod/exam-studio/{$draft->id}/approve", ['acknowledge' => 1])->assertNotFound();
    }

    public function test_the_weekly_studio_never_opens_an_exam_draft(): void
    {
        $this->actingAs($this->head)->post('/hod/exam-studio/generate', $this->payload());
        $draft = ScheduleDraft::where('type', 'exam')->firstOrFail();

        $this->get('/hod/schedule-studio/' . $draft->id)->assertNotFound();
    }
}
