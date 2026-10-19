<?php

namespace Tests\Feature\Schedule;

use App\Models\ScheduleDraft;
use App\Models\Semester;
use App\Services\Schedule\ScheduleDraftService;
use App\Services\Schedule\ScheduleProblem;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * المولّد الأسبوعي واعٍ بالفصل الدراسي: مقررات فصل واحد فقط، خيار فصل آخر، ومسودة غير الفعّال تُحفظ للفصل القادم ولا تُعتمد.
 *
 * السياق الواقعي الذي كشف الخلل: شعبة لها 10 مقررات في الفصل الأول و11 في الثاني كانت تطلب 42 حصة من 25 متاحة،
 * مع أن كل فصل وحده يتسع (20 و22 حصة).
 */
class ScheduleSemesterTest extends TestCase
{
    use MakesAcademicData;

    private int $dept;
    private array $head;
    private int $program;
    private int $s1;   // الفصل الفعّال
    private int $s2;   // الفصل القادم (غير فعّال)
    private ScheduleDraftService $svc;
    /** @var int[] */
    private array $c1 = [];
    /** @var int[] */
    private array $c2 = [];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->svc = app(ScheduleDraftService::class);

        $this->s1 = Semester::currentOrCreate()->semester_id;
        DB::table('semesters')->where('semester_id', $this->s1)->update(['name' => 'الفصل الأول']);
        $this->s2 = DB::table('semesters')->insertGetId([
            'name' => 'الفصل الثاني', 'start_date' => '2027-02-01', 'end_date' => '2027-06-30', 'is_active' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->dept = $this->makeDepartment('قسم');
        $this->head = $this->makeHead($this->dept);
        $this->program = DB::table('programs')->insertGetId(['name' => 'برنامج', 'department_id' => $this->dept]);

        $teachers = [];
        foreach (range(1, 4) as $i) {
            $teachers[] = DB::table('teachers')->where('user_id', $this->makeTeacher()['user']->user_id)->value('teacher_id');
        }

        // الفصل الأول: 10 مقررات (20 حصة). الثاني: 11 مقرراً (22 حصة). نفس البرنامج والسنة.
        foreach (range(1, 10) as $i) {
            $c = $this->makeCourse($this->program, ['title' => "أول $i", 'hours' => 3, 'year' => 1, 'semester_id' => $this->s1]);
            $this->assignTeacher($c, $teachers[$i % 4]);
            $this->c1[] = $c;
        }
        foreach (range(1, 11) as $i) {
            $c = $this->makeCourse($this->program, ['title' => "ثانٍ $i", 'hours' => 3, 'year' => 1, 'semester_id' => $this->s2]);
            $this->assignTeacher($c, $teachers[$i % 4]);
            $this->c2[] = $c;
        }
    }

    private function generate(array $opts = []): ScheduleDraft
    {
        return $this->svc->generate($this->head['user'], $opts + ['seed' => 3]);
    }

    private function entryCourses(ScheduleDraft $d): array
    {
        return $d->entries()->pluck('course_id')->unique()->map(fn ($i) => (int) $i)->sort()->values()->all();
    }

    // ───────────────────────────── الاقتصار على فصل واحد ─────────────────────────────

    public function test_default_is_the_active_semester_and_only_its_courses(): void
    {
        $d = $this->generate();

        $this->assertSame($this->s1, (int) $d->semester_id);
        $this->assertSame('weekly', $d->type);
        $this->assertSame('جدول أسبوعي: الفصل الأول', $d->name);
        $this->assertEqualsCanonicalizing($this->c1, $this->entryCourses($d));
        $this->assertSame(20, $d->stats['required'], 'لا تُحسب مقررات الفصل الثاني');
        $this->assertSame(20, $d->stats['placed']);
        $this->assertSame([], $d->stats['unscheduled']);
    }

    public function test_capacity_report_counts_one_semester_at_a_time(): void
    {
        $one = ScheduleProblem::forDepartment($this->dept, ['semester_id' => $this->s1])->capacityReport()[0];
        $two = ScheduleProblem::forDepartment($this->dept, ['semester_id' => $this->s2])->capacityReport()[0];

        $this->assertSame([20, 0], [$one['needed'], $one['overflow']]);
        $this->assertSame([22, 0], [$two['needed'], $two['overflow']]);
        // لو اختلطا (الخلل القديم) لكان 42 من 25
        $this->assertSame(42, $one['needed'] + $two['needed']);
        $this->assertGreaterThan(25, $one['needed'] + $two['needed']);
    }

    public function test_every_group_fits_within_a_semester_even_when_both_together_do_not(): void
    {
        foreach ([$this->s1, $this->s2] as $sem) {
            foreach (ScheduleProblem::forDepartment($this->dept, ['semester_id' => $sem])->capacityReport() as $row) {
                $this->assertLessThanOrEqual($row['available'], $row['needed']);
                $this->assertSame(0, $row['overflow']);
            }
        }
    }

    public function test_another_semester_can_be_chosen_and_uses_only_its_courses(): void
    {
        $d = $this->generate(['semester_id' => $this->s2]);

        $this->assertSame($this->s2, (int) $d->semester_id);
        $this->assertSame('جدول أسبوعي: الفصل الثاني', $d->name);
        $this->assertEqualsCanonicalizing($this->c2, $this->entryCourses($d));
        $this->assertSame(22, $d->stats['placed']);
        $this->assertSame([], $d->stats['violations']);
    }

    public function test_a_draft_stays_on_its_semester_through_regenerate_and_move(): void
    {
        $d = $this->generate(['semester_id' => $this->s2]);

        $d = $this->svc->regenerate($d, 99);
        $this->assertEqualsCanonicalizing($this->c2, $this->entryCourses($d));
        $this->assertSame($this->s2, (int) $d->semester_id);

        $entry = $d->entries()->first();
        $free = $this->freeSlot($d);
        $d = $this->svc->move($d, $entry->id, $free[0], $free[1]);
        $this->assertEqualsCanonicalizing($this->c2, $this->entryCourses($d), 'النقل والفحص يجريان على مقررات فصل المسودة');
    }

    /** أول (يوم، فترة) فارغ في شبكة المسودة، بحيث لا ينكسر قيد عند نقل حصة إليه. */
    private function freeSlot(ScheduleDraft $d): array
    {
        $taken = $d->entries()->get()->map(fn ($e) => $e->day . '|' . $e->period)->all();
        $entry = $d->entries()->first();
        foreach ($d->settings['days'] as $day) {
            for ($p = 1; $p <= $d->settings['periods']; $p++) {
                // نفس المقرر لا يتكرر في اليوم نفسه
                $sameDay = $d->entries()->where('course_id', $entry->course_id)->where('day', $day)->where('id', '!=', $entry->id)->exists();
                if (!in_array("$day|$p", $taken, true) && !$sameDay) {
                    return [$day, $p];
                }
            }
        }
        $this->fail('لا خانة فارغة');
    }

    // ───────────────────────────── اعتماد الفصل غير الفعّال ─────────────────────────────

    public function test_a_draft_for_an_inactive_semester_is_saved_for_next_and_cannot_be_approved(): void
    {
        $d = $this->generate(['semester_id' => $this->s2]);

        $this->assertTrue($d->isSavedForNext());
        $this->assertFalse($this->generate()->isSavedForNext(), 'مسودة الفصل الفعّال ليست "للقادم"');

        try {
            $this->svc->approve($d, $this->head['user']);
            $this->fail('كان يجب أن يُرفض الاعتماد');
        } catch (ScheduleRuleException $e) {
            $this->assertStringContainsString('الفصل الثاني', $e->messages[0]);
            $this->assertStringContainsString('محفوظة للفصل القادم', $e->messages[0]);
        }

        $this->assertSame(0, DB::table('schedules')->count(), 'لم يُمسّ الجدول الجاري');
        $this->assertSame('draft', $d->fresh()->status);
        $this->assertGreaterThan(0, $d->entries()->count(), 'المسودة بقيت كما هي قابلة للتعديل');
    }

    public function test_a_saved_draft_can_still_be_reviewed_and_edited(): void
    {
        $d = $this->generate(['semester_id' => $this->s2]);
        $entry = $d->entries()->first();

        $this->svc->setLocked($d, $entry->id, true);
        $this->assertTrue((bool) $entry->fresh()->locked);
        $this->svc->discard($d);
        $this->assertSame('discarded', $d->fresh()->status);
    }

    public function test_it_can_be_approved_once_its_semester_becomes_active(): void
    {
        $d = $this->generate(['semester_id' => $this->s2]);

        // يتفعّل الفصل الثاني ويُغلق الأول (كما يفعل academic:check-semesters)
        DB::table('semesters')->where('semester_id', $this->s1)->update(['is_active' => false]);
        DB::table('semesters')->where('semester_id', $this->s2)->update(['is_active' => true]);

        $this->assertFalse($d->fresh()->isSavedForNext());
        $approved = $this->svc->approve($d->fresh(), $this->head['user']);

        $this->assertSame('approved', $approved->status);
        $this->assertEqualsCanonicalizing($this->c2, DB::table('schedules')->pluck('course_id')->unique()->map(fn ($i) => (int) $i)->sort()->values()->all());
        $this->assertSame(22, DB::table('schedules')->count());
    }

    public function test_approving_the_active_semester_still_works_and_ignores_the_saved_one(): void
    {
        $saved = $this->generate(['semester_id' => $this->s2]);
        $now = $this->generate();

        $this->svc->approve($now, $this->head['user']);

        $this->assertSame(20, DB::table('schedules')->count());
        $this->assertSame(0, DB::table('schedules')->whereIn('course_id', $this->c2)->count());
        $this->assertSame('draft', $saved->fresh()->status, 'مسودة الفصل القادم لم تتأثر');
    }

    // ───────────────────────────── أخطاء واضحة ─────────────────────────────

    public function test_unknown_semester_is_refused(): void
    {
        $this->expectException(ScheduleRuleException::class);
        $this->generate(['semester_id' => 99999]);
    }

    public function test_no_active_semester_and_none_chosen_is_refused_with_a_clear_message(): void
    {
        DB::table('semesters')->update(['is_active' => false]);

        try {
            $this->generate();
            $this->fail();
        } catch (ScheduleRuleException $e) {
            $this->assertStringContainsString('فصل', $e->messages[0]);
        }

        // لكن اختيار فصل صراحة يعمل (تخطيط مسبق)
        $d = $this->generate(['semester_id' => $this->s2]);
        $this->assertSame($this->s2, (int) $d->semester_id);
    }

    // ───────────────────────────── التنظيف لكل فصل ─────────────────────────────

    public function test_pruning_is_per_semester_so_next_semester_drafts_survive(): void
    {
        config(['schedule.max_drafts_per_department' => 2]);

        $keep = $this->generate(['semester_id' => $this->s2]);
        foreach (range(1, 5) as $i) {
            $this->generate();
        }

        $this->assertNotNull(ScheduleDraft::find($keep->id), 'مسودة الفصل القادم لا تُحذف بسبب مسودات الفصل الجاري');
        $this->assertSame(2, ScheduleDraft::where('semester_id', $this->s1)->count());
    }

    // ───────────────────────────── API ─────────────────────────────

    public function test_api_index_lists_semesters_and_scopes_capacity(): void
    {
        $this->actAs($this->head['user']);

        $res = $this->getJson('/api/department-head/schedule-studio')->assertOk();
        $this->assertSame($this->s1, $res->json('data.current_semester_id'));
        $this->assertSame($this->s1, $res->json('data.selected_semester_id'));
        $this->assertSame(20, $res->json('data.capacity.0.needed'));
        $this->assertEqualsCanonicalizing([$this->s1, $this->s2], array_column($res->json('data.semesters'), 'id'));

        $other = $this->getJson('/api/department-head/schedule-studio?semester_id=' . $this->s2)->assertOk();
        $this->assertSame(22, $other->json('data.capacity.0.needed'));
        $this->assertSame($this->s2, $other->json('data.selected_semester_id'));

        $this->getJson('/api/department-head/schedule-studio?semester_id=99999')->assertStatus(422);
    }

    public function test_api_generate_for_next_semester_then_approve_is_a_clear_422(): void
    {
        $this->actAs($this->head['user']);

        $d = $this->postJson('/api/department-head/schedule-studio/generate', ['semester_id' => $this->s2])->assertCreated()->json('data');
        $this->assertTrue($d['saved_for_next']);
        $this->assertSame($this->s2, $d['semester_id']);
        $this->assertSame('الفصل الثاني', $d['semester_name']);

        $res = $this->postJson("/api/department-head/schedule-studio/{$d['id']}/approve")->assertStatus(422);
        $this->assertStringContainsString('محفوظة للفصل القادم', $res->json('message'));

        $list = $this->getJson('/api/department-head/schedule-studio')->json('data.drafts');
        $this->assertTrue($list[0]['saved_for_next']);
    }

    public function test_api_generate_without_active_semester_is_422(): void
    {
        DB::table('semesters')->update(['is_active' => false]);
        $this->actAs($this->head['user']);

        $this->postJson('/api/department-head/schedule-studio/generate')->assertStatus(422);
        $this->postJson('/api/department-head/schedule-studio/generate', ['semester_id' => 99999])->assertStatus(422);
    }

    // ───────────────────────────── الويب ─────────────────────────────

    public function test_web_index_shows_the_semester_picker_and_the_next_semester_notice(): void
    {
        $this->actingAs($this->head['user']);

        $html = $this->get('/hod/schedule-studio')->assertOk()->getContent();
        $this->assertStringContainsString('الفصل الأول (الحالي)', $html);
        $this->assertStringNotContainsString('تُحفظ للفصل القادم، ولا تُعتمد', $html);

        $next = $this->get('/hod/schedule-studio?semester_id=' . $this->s2)->assertOk()->getContent();
        $this->assertStringContainsString('تُحفظ للفصل القادم', $next);
        $this->assertStringContainsString('name="semester_id" value="' . $this->s2 . '"', $next, 'نموذج التوليد يحمل الفصل المختار');
    }

    public function test_web_generate_for_next_semester_redirects_with_the_saved_message_and_blocks_approval(): void
    {
        $this->actingAs($this->head['user']);

        $this->post(route('hod.studio.generate'), ['semester_id' => $this->s2])->assertRedirect();
        $draft = ScheduleDraft::latest('id')->first();
        $this->assertSame($this->s2, (int) $draft->semester_id);

        $page = $this->get(route('hod.studio.show', $draft->id))->assertOk()->getContent();
        $this->assertStringContainsString('محفوظة للفصل القادم', $page);
        $this->assertMatchesRegularExpression('/<button class="st-btn"[^>]*disabled[^>]*>اعتماد الجدول<\/button>/', $page);

        $this->post(route('hod.studio.approve', $draft->id))->assertSessionHas('error');
        $this->assertSame(0, DB::table('schedules')->count());
    }

    public function test_web_drafts_table_labels_a_saved_draft(): void
    {
        $this->generate(['semester_id' => $this->s2]);
        $this->actingAs($this->head['user']);

        $this->get('/hod/schedule-studio')->assertOk()->assertSee('محفوظة للفصل القادم')->assertSee('الفصل الثاني');
    }
}
