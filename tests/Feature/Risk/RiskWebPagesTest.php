<?php

namespace Tests\Feature\Risk;

use App\Models\RiskIntervention;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * صفحات الويب للإنذار المبكر (المرشد ورئيس القسم): العرض، الفلاتر، تسجيل المتابعة، والصلاحيات.
 */
class RiskWebPagesTest extends TestCase
{
    use MakesAcademicData;

    private array $a;
    private array $b;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Carbon::setTestNow('2026-10-08 12:00:00');

        $this->a = $this->dept('A');
        $this->b = $this->dept('B');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function dept(string $name): array
    {
        $dept = $this->makeDepartment("Dept $name");
        $programId = $this->makeProgram($dept);
        $programName = DB::table('programs')->where('id', $programId)->value('name');
        $advisor = $this->makeTeacher();
        DB::table('teachers')->where('teacher_id', $advisor['teacher_id'])->update(['advisor_branch' => $programName, 'advisor_year' => 'السنة الأولى']);

        return ['program' => $programId, 'advisor' => $advisor, 'head' => $this->makeHead($dept), 'course' => $this->makeCourse($programId, ['title' => "Course $name"])];
    }

    private function atRisk(array $dep, string $name): array
    {
        $s = $this->makeStudent(['full_name' => $name, 'academic_year' => 'السنة الأولى'], $dep['program']);
        $this->enroll($s['student_id'], $dep['course']);
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $dep['course'], 'teacher_id' => $dep['advisor']['teacher_id'], 'title' => 'L', 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (range(0, 5) as $i) {
            foreach ([['absent', '2026-09-22'], ['present', '2026-09-01']] as [$st, $from]) {
                DB::table('attendance')->insert([
                    'student_id' => $s['student_id'], 'lesson_id' => $lesson, 'status' => $st, 'attendance_date' => Carbon::parse($from)->addDays($i)->toDateString(),
                    'excuse_status' => 'none', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        return $s;
    }

    private function compute(): void
    {
        $this->artisan('risk:compute', ['--no-notify' => true, '--backfill-weeks' => 3])->assertExitCode(0);
    }

    // ------------------------------------------------------------------ القائمة

    public function test_summary_boxes_are_links_to_their_own_filtered_list(): void
    {
        $this->actingAs($this->a['advisor']['user']);

        $html = $this->get('/teacher/risk?search=x&program_id=' . $this->a['program'])->assertOk()->getContent();

        foreach (['level=high', 'level=medium', 'level=low', 'level=unknown', 'followup=overdue'] as $needle) {
            $this->assertMatchesRegularExpression('/<a class="rk-stat[^"]*"[^>]*href="[^"]*' . preg_quote($needle, '/') . '[^"]*"/', $html, "المربع $needle ليس رابطًا");
        }
        $this->assertStringContainsString('search=x', $html, 'الروابط تحتفظ بالبحث');
        $this->assertDoesNotMatchRegularExpression('/<div class="rk-stat[ "]/', $html, 'لم يبقَ مربع غير تفاعلي');
    }

    public function test_the_active_box_follows_the_current_filter(): void
    {
        $this->actingAs($this->a['advisor']['user']);

        $this->assertMatchesRegularExpression('/class="rk-stat active"[^>]*href="[^"]*level=high/', $this->get('/teacher/risk?level=high')->getContent());
        $this->assertMatchesRegularExpression('/class="rk-stat active"[^>]*href="[^"]*followup=overdue/', $this->get('/teacher/risk?followup=overdue')->getContent());
    }

    public function test_advisor_page_lists_own_cohort_with_reasons_and_hides_others(): void
    {
        $this->atRisk($this->a, 'سارة المتعثرة');
        $this->atRisk($this->b, 'طالب قسم آخر');
        $this->compute();
        $this->actingAs($this->a['advisor']['user']);

        $this->get('/teacher/risk')->assertOk()
            ->assertSee('سارة المتعثرة')
            ->assertSee('عالٍ')
            ->assertSee('حضوره')
            ->assertDontSee('طالب قسم آخر');
    }

    public function test_head_page_uses_the_head_layout_and_shows_the_department(): void
    {
        $this->atRisk($this->a, 'طالب القسم');
        $this->compute();
        $this->actingAs($this->a['head']['user']);

        $this->get('/hod/risk')->assertOk()->assertSee('طالب القسم')->assertSee('تحليل الحضور'); // رابط قائمة رئيس القسم الجانبية
    }

    public function test_filters_and_empty_state(): void
    {
        $this->atRisk($this->a, 'متعثر');
        $fresh = $this->makeStudent(['full_name' => 'جديد بلا بيانات', 'academic_year' => 'السنة الأولى'], $this->a['program']);
        $this->compute();
        $this->actingAs($this->a['advisor']['user']);

        $this->get('/teacher/risk?level=unknown')->assertOk()->assertSee('جديد بلا بيانات')->assertDontSee('متعثر');
        $this->get('/teacher/risk?level=high')->assertOk()->assertSee('متعثر')->assertDontSee('جديد بلا بيانات');
        $this->get('/teacher/risk?search=zzzz')->assertOk()->assertSee('لا طلاب في هذه القائمة');
        $this->get('/teacher/risk?level=bogus')->assertSessionHasErrors('level');
    }

    // ------------------------------------------------------------------ التفاصيل

    public function test_detail_page_explains_the_score(): void
    {
        $s = $this->atRisk($this->a, 'متعثرة');
        $this->compute();
        $this->actingAs($this->a['advisor']['user']);

        $this->get("/teacher/risk/students/{$s['student_id']}")->assertOk()
            ->assertSee('متعثرة')
            ->assertSee('كيف حُسبت الدرجة')
            ->assertSee('الحضور')
            ->assertSee('لا بيانات كافية')          // عوامل غير متوفرة تُعرض صراحةً
            ->assertSee('سجل المتابعة')
            ->assertSee('أداة لترتيب الأولويات');
    }

    public function test_detail_of_a_student_outside_the_scope_is_404(): void
    {
        $far = $this->atRisk($this->b, 'بعيد');
        $this->compute();

        $this->actingAs($this->a['advisor']['user']);
        $this->get("/teacher/risk/students/{$far['student_id']}")->assertNotFound();

        $this->actingAs($this->a['head']['user']);
        $this->get("/hod/risk/students/{$far['student_id']}")->assertNotFound();
    }

    // ---------------------------------------------------------------- المتابعة

    public function test_advisor_records_an_intervention_from_the_page(): void
    {
        $s = $this->atRisk($this->a, 'متعثر');
        $this->compute();
        $this->actingAs($this->a['advisor']['user']);

        $this->post("/teacher/risk/students/{$s['student_id']}/interventions", ['type' => 'meeting', 'note' => 'التقينا اليوم', 'follow_up_on' => '2026-10-20'])
            ->assertRedirect()->assertSessionHas('success');

        $iv = RiskIntervention::first();
        $this->assertSame('meeting', $iv->type);
        $this->assertSame('high', $iv->level_at_time);

        $this->get("/teacher/risk/students/{$s['student_id']}")->assertSee('التقينا اليوم')->assertSee('لقاء مع الطالب');
    }

    public function test_intervention_input_is_validated_and_scoped(): void
    {
        $mine = $this->atRisk($this->a, 'طالبي');
        $far = $this->atRisk($this->b, 'بعيد');
        $this->compute();
        $this->actingAs($this->a['advisor']['user']);

        $this->post("/teacher/risk/students/{$mine['student_id']}/interventions", ['type' => 'bogus'])->assertSessionHasErrors('type');
        $this->post("/teacher/risk/students/{$far['student_id']}/interventions", ['type' => 'note'])->assertNotFound();
        $this->assertSame(0, RiskIntervention::count());
    }

    // ------------------------------------------------------------- الصلاحيات

    public function test_a_teacher_who_is_not_an_advisor_is_sent_back_and_sees_no_menu_link(): void
    {
        $plain = $this->makeTeacher();
        $this->actingAs($plain['user']);

        $this->get('/teacher/risk')->assertRedirect('/teacher/dashboard')->assertSessionHas('error');
        $this->get('/teacher/dashboard')->assertDontSee('المعرّضون للتعثر');

        $this->actingAs($this->a['advisor']['user']);
        $this->get('/teacher/dashboard')->assertSee('المعرّضون للتعثر');
    }

    public function test_students_and_parents_cannot_open_the_pages(): void
    {
        foreach ([$this->makeStudent()['user'], $this->makeParent()['user']] as $user) {
            $this->actingAs($user);
            $this->get('/teacher/risk')->assertRedirect();
            $this->get('/hod/risk')->assertRedirect();
        }
    }

    public function test_risk_alert_notification_opens_the_risk_list_for_both_roles(): void
    {
        foreach ([['advisor', 'teacher'], ['head', 'hod']] as [$who, $prefix]) {
            $user = $this->a[$who]['user'];
            DB::table('notifications')->insert([
                'user_id' => $user->user_id, 'title' => 'طلاب ارتفع خطر تعثرهم', 'message' => 'ارتفع مستوى الخطر الأكاديمي لـ 1 طالب: س (عالٍ)',
                'type' => 'risk_alert', 'category' => 'administrative', 'is_read' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->actingAs($user);

            $this->get("/{$prefix}/notifications")->assertOk()->assertSee("handleNotifClick(event, '/{$prefix}/risk'", false);
        }
    }
}
