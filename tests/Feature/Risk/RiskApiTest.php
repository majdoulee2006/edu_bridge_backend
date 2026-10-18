<?php

namespace Tests\Feature\Risk;

use App\Models\RiskIntervention;
use App\Models\StudentRiskHistory;
use App\Models\StudentRiskScore;
use App\Services\Risk\RiskScoreService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * واجهات الإنذار المبكر: النطاق (مرشد/رئيس قسم/أدمن)، القائمة والتفاصيل، سجل المتابعة، والأمر الليلي والتنبيه.
 */
class RiskApiTest extends TestCase
{
    use MakesAcademicData;

    private array $a;      // قسم A: برنامج + مرشد + رئيس قسم
    private array $b;      // قسم B
    private array $nonAdvisor;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Carbon::setTestNow('2026-10-08 12:00:00');

        $this->a = $this->dept('A');
        $this->b = $this->dept('B');
        $this->nonAdvisor = $this->makeTeacher();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function dept(string $name): array
    {
        $dept = $this->makeDepartment("Dept $name");
        $programId = $this->makeProgram($dept);
        $programName = DB::table('programs')->where('id', $programId)->value('name');
        $advisor = $this->makeTeacher();
        DB::table('teachers')->where('teacher_id', $advisor['teacher_id'])->update(['advisor_branch' => $programName, 'advisor_year' => 'السنة الأولى']);
        $course = $this->makeCourse($programId, ['title' => "Course $name"]);

        return ['dept' => $dept, 'program' => $programId, 'advisor' => $advisor, 'head' => $this->makeHead($dept), 'course' => $course];
    }

    private function student(array $dep, string $name, string $year = 'السنة الأولى', string $status = 'active'): array
    {
        $s = $this->makeStudent(['full_name' => $name, 'academic_year' => $year, 'status' => $status], $dep['program']);
        $this->enroll($s['student_id'], $dep['course']);

        return $s;
    }

    /** يملأ بيانات حضور تجعل الطالب "عالي الخطر": غياب متواصل بعد حضور جيد. */
    private function makeAtRisk(array $dep, array $s): void
    {
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $dep['course'], 'teacher_id' => $dep['advisor']['teacher_id'], 'title' => 'L',
            'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (range(0, 5) as $i) {
            $this->att($s, $lesson, 'absent', Carbon::parse('2026-09-22')->addDays($i)->toDateString());
            $this->att($s, $lesson, 'present', Carbon::parse('2026-09-01')->addDays($i)->toDateString());
        }
    }

    private function makeHealthy(array $dep, array $s): void
    {
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $dep['course'], 'teacher_id' => $dep['advisor']['teacher_id'], 'title' => 'L',
            'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (range(0, 5) as $i) {
            $this->att($s, $lesson, 'present', Carbon::parse('2026-09-22')->addDays($i)->toDateString());
        }
    }

    private function att(array $s, int $lesson, string $status, string $date): void
    {
        DB::table('attendance')->insert([
            'student_id' => $s['student_id'], 'lesson_id' => $lesson, 'status' => $status, 'attendance_date' => $date,
            'excuse_status' => 'none', 'created_at' => $date . ' 09:00:00', 'updated_at' => now(),
        ]);
    }

    private function compute(): void
    {
        $this->artisan('risk:compute', ['--no-notify' => true])->assertExitCode(0);
    }

    // ------------------------------------------------------------- الصلاحيات

    public function test_advisor_sees_only_the_students_of_their_branch_and_year(): void
    {
        $mine  = $this->student($this->a, 'طالبي');
        $other = $this->student($this->a, 'سنة أخرى', 'السنة الثانية');
        $far   = $this->student($this->b, 'قسم آخر');
        foreach ([$mine, $other, $far] as $s) {
            $this->makeAtRisk($s === $far ? $this->b : $this->a, $s);
        }
        $this->compute();

        $this->actAs($this->a['advisor']['user']);
        $res = $this->getJson('/api/teacher/risk')->assertOk();

        $names = array_column(array_column($res->json('data'), 'student'), 'name');
        $this->assertSame(['طالبي'], $names);
        $this->assertSame(1, $res->json('summary.total'));
    }

    public function test_head_sees_the_whole_department_and_not_other_departments(): void
    {
        $this->makeAtRisk($this->a, $this->student($this->a, 'سنة أولى'));
        $this->makeAtRisk($this->a, $this->student($this->a, 'سنة ثانية', 'السنة الثانية'));
        $this->makeAtRisk($this->b, $this->student($this->b, 'قسم B'));
        $this->compute();

        $this->actAs($this->a['head']['user']);
        $names = collect($this->getJson('/api/department-head/risk')->assertOk()->json('data'))->pluck('student.name')->sort()->values()->all();
        $this->assertSame(['سنة أولى', 'سنة ثانية'], $names);

        $this->actAs($this->b['head']['user']);
        $this->assertSame(['قسم B'], collect($this->getJson('/api/department-head/risk')->json('data'))->pluck('student.name')->all());
    }

    public function test_admin_sees_everyone(): void
    {
        $this->makeAtRisk($this->a, $this->student($this->a, 'أ'));
        $this->makeAtRisk($this->b, $this->student($this->b, 'ب'));
        $this->compute();

        $this->actAs($this->makeUser('admin'));

        $this->assertSame(2, $this->getJson('/api/admin/risk')->assertOk()->json('summary.total'));
    }

    public function test_everyone_else_is_refused(): void
    {
        $s = $this->student($this->a, 'ط');
        $this->compute();

        // معلم غير مرشد
        $this->actAs($this->nonAdvisor['user']);
        $this->getJson('/api/teacher/risk')->assertForbidden();
        $this->getJson("/api/teacher/risk/students/{$s['student_id']}")->assertForbidden();
        $this->postJson("/api/teacher/risk/students/{$s['student_id']}/interventions", ['type' => 'note'])->assertForbidden();

        // الطالب نفسه وولي الأمر لا يرون درجته أبدًا
        foreach ([$s['user'], $this->makeParent()['user']] as $user) {
            $this->actAs($user);
            $this->getJson('/api/teacher/risk')->assertForbidden();
            $this->getJson('/api/department-head/risk')->assertForbidden();
            $this->getJson('/api/admin/risk')->assertForbidden();
        }
    }

    public function test_inactive_students_are_not_listed(): void
    {
        $this->makeAtRisk($this->a, $this->student($this->a, 'نشط'));
        $this->makeAtRisk($this->a, $this->student($this->a, 'معطّل', 'السنة الأولى', 'inactive'));
        $this->compute();

        $this->actAs($this->a['advisor']['user']);

        $this->assertSame(['نشط'], collect($this->getJson('/api/teacher/risk')->json('data'))->pluck('student.name')->all());
    }

    // --------------------------------------------------------------- القائمة

    public function test_list_filters_search_sort_and_stable_summary(): void
    {
        $bad = $this->student($this->a, 'متعثر');
        $ok  = $this->student($this->a, 'متفوق');
        $new = $this->student($this->a, 'جديد');       // بلا بيانات → unknown
        $this->makeAtRisk($this->a, $bad);
        $this->makeHealthy($this->a, $ok);
        $this->compute();
        $this->actAs($this->a['advisor']['user']);

        $all = $this->getJson('/api/teacher/risk')->assertOk();
        $this->assertSame(['متعثر', 'متفوق', 'جديد'], $all->json('data.*.student.name'), 'الأعلى خطرًا أولًا والبلا درجة آخرًا');
        $this->assertSame(['high' => 1, 'medium' => 0, 'low' => 1, 'unknown' => 1, 'total' => 3, 'overdue' => 0], $all->json('summary'));

        $high = $this->getJson('/api/teacher/risk?level=high');
        $this->assertSame(['متعثر'], $high->json('data.*.student.name'));
        $this->assertSame(3, $high->json('summary.total'), 'الملخص لا يتغير بفلتر المستوى');

        $this->assertSame(['متعثر'], $this->getJson('/api/teacher/risk?level=at_risk')->json('data.*.student.name'));
        $this->assertSame(['جديد'], $this->getJson('/api/teacher/risk?level=unknown')->json('data.*.student.name'));
        $this->assertSame(['متفوق'], $this->getJson('/api/teacher/risk?search=متفو')->json('data.*.student.name'));
        $this->getJson('/api/teacher/risk?level=nonsense')->assertStatus(422);
    }

    public function test_list_row_carries_reasons_change_and_intervention_state(): void
    {
        $bad = $this->student($this->a, 'متعثر');
        $this->makeAtRisk($this->a, $bad);
        $this->compute();
        DB::table('student_risk_scores')->where('student_id', $bad['student_id'])->update(['previous_score' => 40.0]);
        RiskIntervention::create(['student_id' => $bad['student_id'], 'author_user_id' => $this->a['advisor']['user']->user_id, 'type' => 'call', 'follow_up_on' => '2026-10-01']);

        $this->actAs($this->a['advisor']['user']);
        $row = $this->getJson('/api/teacher/risk')->json('data.0');

        $this->assertSame('high', $row['level']);
        $this->assertSame('عالٍ', $row['level_label']);
        $this->assertNotEmpty($row['reasons']);
        $this->assertGreaterThan(0, $row['change']);
        $this->assertSame('call', $row['last_intervention']['type']);
        $this->assertTrue($row['follow_up_due'], 'موعد المتابعة مضى');
    }

    private function followUp(array $student, ?string $on, $createdAt = null): void
    {
        DB::table('risk_interventions')->insert([
            'student_id' => $student['student_id'], 'author_user_id' => $this->a['advisor']['user']->user_id, 'type' => 'call',
            'follow_up_on' => $on, 'score_at_time' => 80, 'level_at_time' => 'high',
            'created_at' => $createdAt ?? now(), 'updated_at' => now(),
        ]);
    }

    public function test_overdue_followup_filter_and_summary(): void
    {
        $late = $this->student($this->a, 'متأخر');
        $future = $this->student($this->a, 'موعده قادم');
        $superseded = $this->student($this->a, 'تجاوز الموعد ثم تابعناه');
        foreach ([$late, $future, $superseded] as $s) {
            $this->makeAtRisk($this->a, $s);
        }
        $this->compute();

        $this->followUp($late, today()->subDay()->toDateString());
        $this->followUp($future, today()->addDays(3)->toDateString());
        $this->followUp($superseded, today()->subDays(5)->toDateString(), now()->subDays(6));
        $this->followUp($superseded, today()->addDays(7)->toDateString()); // أحدث إجراء موعده لم يحن: لم يعد متأخرًا

        $this->actAs($this->a['advisor']['user']);

        $res = $this->getJson('/api/teacher/risk?followup=overdue')->assertOk();
        $this->assertSame(['متأخر'], $res->json('data.*.student.name'));
        $this->assertSame(1, $res->json('summary.overdue'));
        $this->assertSame(3, $res->json('summary.total'), 'الملخص لا يتأثر بالفلتر');

        $this->getJson('/api/teacher/risk')->assertOk()->assertJsonPath('summary.overdue', 1);
        $this->getJson('/api/teacher/risk?followup=nope')->assertStatus(422);
    }

    public function test_overdue_is_scoped_to_the_users_own_students(): void
    {
        $mine = $this->student($this->a, 'طالبي');
        $other = $this->student($this->b, 'طالب قسم آخر');
        $this->makeAtRisk($this->a, $mine);
        $this->makeAtRisk($this->b, $other);
        $this->compute();
        $this->followUp($mine, today()->subDay()->toDateString());
        $this->followUp($other, today()->subDay()->toDateString());

        $this->actAs($this->a['head']['user']);
        $res = $this->getJson('/api/department-head/risk?followup=overdue')->assertOk();

        $this->assertSame(['طالبي'], $res->json('data.*.student.name'));
        $this->assertSame(1, $res->json('summary.overdue'));
    }

    public function test_sort_by_change_puts_the_biggest_risers_first(): void
    {
        $a = $this->student($this->a, 'ثابت');
        $b = $this->student($this->a, 'صاعد');
        $this->makeAtRisk($this->a, $a);
        $this->makeAtRisk($this->a, $b);
        $this->compute();
        DB::table('student_risk_scores')->where('student_id', $a['student_id'])->update(['previous_score' => DB::raw('score')]);
        DB::table('student_risk_scores')->where('student_id', $b['student_id'])->update(['previous_score' => 10.0]);

        $this->actAs($this->a['advisor']['user']);

        $this->assertSame(['صاعد', 'ثابت'], $this->getJson('/api/teacher/risk?sort=change')->json('data.*.student.name'));
    }

    // -------------------------------------------------------------- التفاصيل

    public function test_detail_has_factors_history_and_interventions(): void
    {
        $s = $this->student($this->a, 'متعثر');
        $this->makeAtRisk($this->a, $s);
        $this->artisan('risk:compute', ['--no-notify' => true, '--backfill-weeks' => 3])->assertExitCode(0);
        RiskIntervention::create(['student_id' => $s['student_id'], 'author_user_id' => $this->a['advisor']['user']->user_id, 'type' => 'meeting', 'note' => 'التقيته', 'score_at_time' => 50.0, 'level_at_time' => 'medium']);

        $this->actAs($this->a['advisor']['user']);
        $d = $this->getJson("/api/teacher/risk/students/{$s['student_id']}")->assertOk()->json('data');

        $this->assertSame('متعثر', $d['student']['name']);
        $this->assertSame('high', $d['risk']['level']);
        $this->assertCount(5, $d['risk']['factors']);
        $this->assertGreaterThanOrEqual(2, count($d['history']));
        $this->assertSame('meeting', $d['interventions'][0]['type']);
        $this->assertSame('التقيته', $d['interventions'][0]['note']);
        $this->assertEquals(50.0, $d['since_last_intervention']['from']);
        $this->assertEquals($d['risk']['score'] - 50.0, $d['since_last_intervention']['delta']);
        $this->assertArrayHasKey('call', $d['intervention_types']);
    }

    public function test_detail_of_a_student_outside_the_scope_looks_missing(): void
    {
        $far = $this->student($this->b, 'بعيد');
        $this->compute();

        $this->actAs($this->a['advisor']['user']);

        $this->getJson("/api/teacher/risk/students/{$far['student_id']}")->assertNotFound();
        $this->actAs($this->a['head']['user']);
        $this->getJson("/api/department-head/risk/students/{$far['student_id']}")->assertNotFound();
    }

    // ---------------------------------------------------------- سجل المتابعة

    public function test_advisor_records_an_intervention_with_the_score_at_that_time(): void
    {
        $s = $this->student($this->a, 'متعثر');
        $this->makeAtRisk($this->a, $s);
        $this->compute();
        $this->actAs($this->a['advisor']['user']);

        $res = $this->postJson("/api/teacher/risk/students/{$s['student_id']}/interventions", [
            'type' => 'parent_contact', 'note' => 'اتصلت بالأب', 'follow_up_on' => '2026-10-15',
        ])->assertCreated();

        $res->assertJsonPath('data.type_label', 'تواصل مع ولي الأمر')->assertJsonPath('data.follow_up_on', '2026-10-15');
        $iv = RiskIntervention::first();
        $this->assertSame($this->a['advisor']['user']->user_id, (int) $iv->author_user_id);
        $this->assertSame('high', $iv->level_at_time);
        $this->assertGreaterThan(0, $iv->score_at_time);
    }

    public function test_intervention_validation_and_scope(): void
    {
        $mine = $this->student($this->a, 'طالبي');
        $far  = $this->student($this->b, 'بعيد');
        $this->compute();
        $this->actAs($this->a['advisor']['user']);

        $this->postJson("/api/teacher/risk/students/{$mine['student_id']}/interventions", ['type' => 'bogus'])->assertStatus(422);
        $this->postJson("/api/teacher/risk/students/{$mine['student_id']}/interventions", ['type' => 'note', 'follow_up_on' => '2026-01-01'])->assertStatus(422);
        $this->postJson("/api/teacher/risk/students/{$mine['student_id']}/interventions", ['type' => 'note', 'note' => str_repeat('x', 1001)])->assertStatus(422);
        $this->postJson("/api/teacher/risk/students/{$far['student_id']}/interventions", ['type' => 'note'])->assertNotFound();

        $this->assertSame(0, RiskIntervention::count());
    }

    public function test_head_can_record_for_department_students_too(): void
    {
        $s = $this->student($this->a, 'قسمي', 'السنة الثانية');
        $this->compute();
        $this->actAs($this->a['head']['user']);

        $this->postJson("/api/department-head/risk/students/{$s['student_id']}/interventions", ['type' => 'referral', 'note' => 'للإدارة'])->assertCreated();
    }

    // ------------------------------------------------------------ الأمر الليلي

    public function test_command_computes_only_active_students_and_one_student_on_demand(): void
    {
        $active = $this->student($this->a, 'نشط');
        $this->student($this->a, 'معطّل', 'السنة الأولى', 'inactive');

        $this->artisan('risk:compute', ['--no-notify' => true])->assertExitCode(0);
        $this->assertSame(1, StudentRiskScore::count());

        StudentRiskScore::query()->delete();
        $this->artisan('risk:compute', ['--student' => $active['student_id'], '--no-notify' => true])->assertExitCode(0);
        $this->assertSame([$active['student_id']], StudentRiskScore::pluck('student_id')->all());
    }

    public function test_command_can_be_switched_off_and_prunes_old_history(): void
    {
        $s = $this->student($this->a, 'ط');
        StudentRiskHistory::create(['student_id' => $s['student_id'], 'computed_on' => '2024-01-01', 'score' => 10, 'level' => 'low', 'reasons' => []]);

        config(['risk.enabled' => false]);
        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame(0, StudentRiskScore::count());

        config(['risk.enabled' => true]);
        $this->artisan('risk:compute', ['--no-notify' => true])->assertExitCode(0);
        $this->assertSame(0, StudentRiskHistory::where('computed_on', '2024-01-01')->count(), 'لقطة أقدم من مدة الاحتفاظ حُذفت');
    }

    public function test_backfill_builds_weekly_history_and_never_notifies(): void
    {
        $s = $this->student($this->a, 'متعثر');
        $this->makeAtRisk($this->a, $s);

        $this->artisan('risk:compute', ['--backfill-weeks' => 4])->assertExitCode(0);

        $this->assertSame(5, StudentRiskHistory::where('student_id', $s['student_id'])->count());   // 4 أسابيع + اليوم
        $this->assertSame(0, DB::table('notifications')->where('type', 'risk_alert')->count());
    }

    // ------------------------------------------------------------------ التنبيه

    private function alerts(array $dep): int
    {
        return DB::table('notifications')->where('user_id', $dep['advisor']['user']->user_id)->where('type', 'risk_alert')->count();
    }

    public function test_advisor_gets_one_grouped_alert_and_not_again_while_the_level_stays(): void
    {
        foreach (['س١', 'س٢', 'س٣', 'س٤'] as $name) {
            $this->makeAtRisk($this->a, $this->student($this->a, $name));
        }

        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame(1, $this->alerts($this->a), 'إشعار واحد مجمَّع لا واحد لكل طالب');
        $n = DB::table('notifications')->where('type', 'risk_alert')->first();
        $this->assertStringContainsString('4 طلاب', $n->message);
        $this->assertStringContainsString('و1 آخرين', $n->message);

        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame(1, $this->alerts($this->a), 'لا تكرار ما دام المستوى كما هو');
    }

    public function test_alert_fires_again_after_the_level_dropped_and_rose_again(): void
    {
        $s = $this->student($this->a, 'متذبذب');
        $this->makeAtRisk($this->a, $s);
        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame(1, $this->alerts($this->a));

        // تحسّن: يُعاد ضبط ما أُرسل عنه
        DB::table('attendance')->where('student_id', $s['student_id'])->update(['status' => 'present']);
        Carbon::setTestNow('2026-10-09 12:00:00');
        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertNotSame('high', StudentRiskScore::where('student_id', $s['student_id'])->value('level'));
        $this->assertSame(1, $this->alerts($this->a));

        // ثم تدهور من جديد
        DB::table('attendance')->where('student_id', $s['student_id'])->update(['status' => 'absent', 'attendance_date' => '2026-10-02']);
        Carbon::setTestNow('2026-10-10 12:00:00');
        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame('high', StudentRiskScore::where('student_id', $s['student_id'])->value('level'));
        $this->assertSame(2, $this->alerts($this->a));
    }

    public function test_alert_goes_to_the_head_when_there_is_no_advisor_and_no_notify_flag_silences_it(): void
    {
        DB::table('teachers')->where('teacher_id', $this->a['advisor']['teacher_id'])->update(['advisor_branch' => null, 'advisor_year' => null]);
        $this->makeAtRisk($this->a, $this->student($this->a, 'بلا مرشد'));

        $this->artisan('risk:compute', ['--no-notify' => true])->assertExitCode(0);
        $this->assertSame(0, DB::table('notifications')->where('type', 'risk_alert')->count());

        StudentRiskScore::query()->update(['notified_level' => null]);
        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame(1, DB::table('notifications')->where('user_id', $this->a['head']['user']->user_id)->where('type', 'risk_alert')->count());
    }

    public function test_medium_level_is_not_alerted_by_default_but_can_be_configured(): void
    {
        config(['risk.levels' => ['high' => 101, 'medium' => 20]]);   // لا درجة تبلغ 101: أعلى مستوى ممكن هو المتوسط
        $this->makeAtRisk($this->a, $this->student($this->a, 'متوسط'));

        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame(0, $this->alerts($this->a));

        config(['risk.notify_levels' => ['medium', 'high']]);
        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame(1, $this->alerts($this->a));
    }

    public function test_alerts_are_isolated_between_departments(): void
    {
        $this->makeAtRisk($this->b, $this->student($this->b, 'قسم B'));

        $this->artisan('risk:compute')->assertExitCode(0);

        $this->assertSame(0, $this->alerts($this->a));
        $this->assertSame(1, $this->alerts($this->b));
    }

    public function test_no_notify_does_not_consume_the_alert(): void
    {
        $this->makeAtRisk($this->a, $this->student($this->a, 'متعثر'));

        $this->artisan('risk:compute', ['--no-notify' => true])->assertExitCode(0);
        $this->assertSame(0, $this->alerts($this->a));
        $this->assertNull(StudentRiskScore::first()->notified_level, 'لم يُرسل شيء فلا يُسجَّل أنه أُرسل');

        $this->artisan('risk:compute')->assertExitCode(0);
        $this->assertSame(1, $this->alerts($this->a), 'التنبيه يصل في أول تشغيل عادي بعد ذلك');
    }
}
