<?php

namespace Tests\Feature\FollowUp;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * لوحة المتابعة: نطاق رئيس القسم والمرشد، ترتيب الدورات بدرجة الاهتمام وأسبابها، الفلاتر، والمؤشرات العلوية.
 */
class FollowUpDashboardTest extends TestCase
{
    use MakesAcademicData;

    private array $a;        // قسم A: برنامج + رئيس + مرشد + دورات
    private array $b;        // قسم B (للعزل)
    private array $s;        // طلاب قسم A بحسب الاسم
    private array $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Carbon::setTestNow('2026-10-14 12:00:00');

        $this->teacher = $this->makeTeacher();
        $this->a = $this->dept('A', true);
        $this->b = $this->dept('B', false);

        // C1: حضور 50% وفيها طالب عالٍ ومتوسط  → تحتاج اهتمامًا
        // C2: حضور 100% وطلابها منخفضو الخطر   → وضعها جيد
        // C3: بلا حضور ولا درجات خطر            → بيانات غير كافية
        $this->s = [];
        foreach (['high', 'medium', 'low1', 'low2', 'unknown', 'fresh'] as $n) {
            $this->s[$n] = $this->student($this->a, $n);
        }
        foreach (['high', 'medium', 'low1'] as $n) {
            $this->enroll($this->s[$n]['student_id'], $this->a['c1']);
        }
        foreach (['low1', 'low2', 'unknown'] as $n) {
            $this->enroll($this->s[$n]['student_id'], $this->a['c2']);
        }
        $this->enroll($this->s['fresh']['student_id'], $this->a['c3']);

        $this->risk($this->s['high'], 'high', 82);
        $this->risk($this->s['medium'], 'medium', 45);
        $this->risk($this->s['low1'], 'low', 10);
        $this->risk($this->s['low2'], 'low', 5);

        $this->attendance($this->a['c1'], [$this->s['high'], $this->s['medium'], $this->s['low1']], present: 3, absent: 3);
        $this->attendance($this->a['c2'], [$this->s['low1'], $this->s['low2'], $this->s['unknown']], present: 6, absent: 0);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ───────────────────────────── بناء البيانات ─────────────────────────────

    private function dept(string $n, bool $withAdvisor): array
    {
        $dept = $this->makeDepartment("Dept $n");
        $programId = $this->makeProgram($dept);
        $name = DB::table('programs')->where('id', $programId)->value('name');
        $advisor = $this->makeTeacher();
        if ($withAdvisor) {
            DB::table('teachers')->where('teacher_id', $advisor['teacher_id'])->update(['advisor_branch' => $name, 'advisor_year' => 'السنة الأولى']);
        }

        return [
            'dept' => $dept, 'program' => $programId, 'program_name' => $name, 'advisor' => $advisor, 'head' => $this->makeHead($dept),
            'c1' => $this->makeCourse($programId, ['title' => "C1 $n", 'year' => 1]),
            'c2' => $this->makeCourse($programId, ['title' => "C2 $n", 'year' => 1]),
            'c3' => $this->makeCourse($programId, ['title' => "C3 $n", 'year' => 2]),
        ];
    }

    private function student(array $dep, string $name): array
    {
        return $this->makeStudent(['full_name' => $name, 'academic_year' => 'السنة الأولى', 'status' => 'active'], $dep['program']);
    }

    private function risk(array $student, string $level, float $score, array $extra = []): void
    {
        DB::table('student_risk_scores')->insert(array_merge([
            'student_id' => $student['student_id'], 'score' => $score, 'level' => $level, 'confidence' => 1.0,
            'factors' => '[]', 'reasons' => json_encode(['سبب ' . $level], JSON_UNESCAPED_UNICODE),
            'computed_on' => today()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ], $extra));
    }

    /** يسجّل $present حضورًا و$absent غيابًا لكل طالب، في دروس منفصلة ضمن آخر 10 أيام. */
    private function attendance(int $courseId, array $students, int $present, int $absent): void
    {
        $n = $present + $absent;
        for ($i = 0; $i < $n; $i++) {
            $lesson = DB::table('lessons')->insertGetId([
                'course_id' => $courseId, 'teacher_id' => $this->teacher['teacher_id'], 'title' => 'L' . $i,
                'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($students as $st) {
                DB::table('attendance')->insert([
                    'student_id' => $st['student_id'], 'lesson_id' => $lesson, 'status' => $i < $present ? 'present' : 'absent',
                    'excuse_status' => 'none', 'attendance_date' => today()->subDays($i + 1)->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    private function intervention(array $student, int $authorUserId, ?string $followUp, string $when = 'now'): void
    {
        DB::table('risk_interventions')->insert([
            'student_id' => $student['student_id'], 'author_user_id' => $authorUserId, 'type' => 'call', 'note' => 'x',
            'follow_up_on' => $followUp, 'score_at_time' => 80, 'level_at_time' => 'high',
            'created_at' => $when === 'now' ? now() : $when, 'updated_at' => now(),
        ]);
    }

    private function api(string $role, array $dep, array $query = [])
    {
        $user = $role === 'head' ? $dep['head']['user'] : $dep['advisor']['user'];
        $prefix = $role === 'head' ? 'department-head' : 'teacher';

        return $this->actAs($user)->getJson("/api/$prefix/follow-up" . ($query ? '?' . http_build_query($query) : ''));
    }

    /** كل المقررات من كل البرامج مفهرسة بالعنوان. */
    private function courses(array $json): array
    {
        return collect($json['programs'])->pluck('courses')->flatten(1)->keyBy('title')->all();
    }

    /** عناوين المقررات بترتيب ظهورها (برنامج ثم مقرراته). */
    private function titles(array $json): array
    {
        return collect($json['programs'])->pluck('courses')->flatten(1)->pluck('title')->all();
    }

    // ───────────────────────────── النطاق والصلاحيات ─────────────────────────────

    public function test_head_sees_only_his_department(): void
    {
        $other = $this->student($this->b, 'other');
        $this->enroll($other['student_id'], $this->b['c1']);
        $this->risk($other, 'high', 90);

        $json = $this->api('head', $this->a)->assertOk()->json();

        $titles = array_keys($this->courses($json));
        sort($titles);
        $this->assertSame(['C1 A', 'C2 A', 'C3 A'], $titles);
        $this->assertSame(6, $json['kpis']['students']);
        $this->assertSame(1, $json['kpis']['high'], 'طالب القسم B لا يُحتسب');
    }

    public function test_advisor_sees_only_his_cohort_and_no_advisor_table(): void
    {
        $y2 = $this->makeStudent(['full_name' => 'y2', 'academic_year' => 'السنة الثانية', 'status' => 'active'], $this->a['program']);
        $this->enroll($y2['student_id'], $this->a['c1']);
        $this->risk($y2, 'high', 90);

        $json = $this->api('advisor', $this->a)->assertOk()->json();

        $this->assertSame(6, $json['kpis']['students']);
        $this->assertSame(1, $json['kpis']['high'], 'طالب السنة الثانية خارج نطاق مرشد السنة الأولى');
        $this->assertSame([], $json['advisors']);
        $this->assertSame(3, $this->courses($json)['C1 A']['enrolled'], 'الدورة تُحسب على طلابه فقط');
    }

    public function test_everyone_else_is_refused(): void
    {
        $plain = $this->makeTeacher(); // معلم ليس مرشدًا
        $parent = $this->makeParent();

        foreach ([$this->s['high']['user'], $parent['user'], $plain['user']] as $u) {
            $this->actAs($u)->getJson('/api/teacher/follow-up')->assertStatus(403);
        }
        $this->actAs($this->s['high']['user'])->getJson('/api/department-head/follow-up')->assertStatus(403);
    }

    public function test_web_pages_render_for_both_roles_and_redirect_others(): void
    {
        $this->actingAs($this->a['head']['user'])->get('/hod/follow-up')->assertOk()
            ->assertSee('لوحة المتابعة')->assertSee('C1 A')->assertSee('تحتاج اهتمامًا');

        $this->actingAs($this->a['advisor']['user'])->get('/teacher/follow-up')->assertOk()->assertSee('C1 A');

        $this->actingAs($this->s['high']['user'])->get('/teacher/follow-up')->assertRedirect();
        $this->actingAs($this->makeTeacher()['user'])->get('/teacher/follow-up')->assertRedirect('/teacher/dashboard');
    }

    // ───────────────────────────── الدورات ─────────────────────────────

    public function test_courses_are_ranked_by_attention_with_reasons(): void
    {
        $json = $this->api('head', $this->a)->assertOk()->json();
        $c = $this->courses($json);

        $this->assertSame(['C1 A', 'C2 A', 'C3 A'], $this->titles($json), 'الأحوج أولاً، ومن لا بيانات له آخرًا');

        $this->assertSame('attention', $c['C1 A']['status']);
        $this->assertEqualsWithDelta(50.0, $c['C1 A']['attendance_rate'], 0.1);
        $this->assertSame(1, $c['C1 A']['high']);
        $this->assertSame(1, $c['C1 A']['medium']);
        $this->assertContains('الحضور 50% خلال آخر 30 يومًا', $c['C1 A']['reasons']);
        $this->assertContains('1 طالب بخطر عالٍ', $c['C1 A']['reasons']);

        $this->assertSame('ok', $c['C2 A']['status']);
        $this->assertEqualsWithDelta(100.0, $c['C2 A']['attendance_rate'], 0.1);

        $this->assertSame('nodata', $c['C3 A']['status']);
        $this->assertNull($c['C3 A']['attendance_rate']);
    }

    public function test_excused_absences_do_not_lower_the_course_rate(): void
    {
        DB::table('attendance')->where('status', 'absent')->update(['excuse_status' => 'approved']);

        $c = $this->courses($this->api('head', $this->a)->json());

        $this->assertEqualsWithDelta(100.0, $c['C1 A']['attendance_rate'], 0.1);
    }

    public function test_few_records_mean_no_attendance_rate_and_never_a_top_priority(): void
    {
        $c4 = $this->makeCourse($this->a['program'], ['title' => 'C4 A', 'year' => 1]);
        $this->enroll($this->s['high']['student_id'], $c4);
        $this->attendance($c4, [$this->s['high']], present: 0, absent: 2); // أقل من الحد الأدنى للسجلات

        $c = $this->courses($this->api('head', $this->a)->json())['C4 A'];

        $this->assertNull($c['attendance_rate']);
        $this->assertNotSame('attention', $c['status'], 'الخطر وحده بلا حضور كافٍ يكفي لـ "راقب" فقط');
    }

    public function test_low_risk_students_without_attendance_records_are_not_called_good(): void
    {
        $c5 = $this->makeCourse($this->a['program'], ['title' => 'C5 A', 'year' => 1]);
        $this->enroll($this->s['low2']['student_id'], $c5); // خطره منخفض لكن لا سجلات حضور للمقرر

        $c = $this->courses($this->api('head', $this->a)->json())['C5 A'];

        $this->assertNull($c['attendance_rate']);
        $this->assertSame('nodata', $c['status'], 'غياب الأدلة ليس دليلاً على أن الوضع جيد');
    }

    public function test_sort_status_year_and_search_filters(): void
    {
        $titles = fn (array $q) => $this->titles($this->api('head', $this->a, $q)->assertOk()->json());

        $this->assertSame(['C1 A'], $titles(['status' => 'attention']));
        $this->assertSame(['C3 A'], $titles(['status' => 'nodata']));
        $this->assertSame(['C3 A'], $titles(['year' => 2]));
        $this->assertSame(['C2 A'], $titles(['search' => 'C2']));

        $this->assertSame('C1 A', $titles(['sort' => 'attendance'])[0], 'الحضور الأقل أولاً');
        $this->assertSame('C1 A', $titles(['sort' => 'risk'])[0], 'الأكثر معرَّضين أولاً');
        $this->assertSame(['C1 A', 'C2 A', 'C3 A'], $titles(['sort' => 'name']));
    }

    public function test_program_filter_narrows_the_students(): void
    {
        $p2 = $this->makeProgram($this->a['dept']);
        $extra = $this->makeStudent(['full_name' => 'p2', 'academic_year' => 'السنة الأولى', 'status' => 'active'], $p2);
        $this->enroll($extra['student_id'], $this->a['c1']);

        $all = $this->api('head', $this->a)->json();
        $this->assertSame(7, $all['kpis']['students']);

        $only = $this->api('head', $this->a, ['program_id' => $p2])->json();
        $this->assertSame(1, $only['kpis']['students']);
    }

    public function test_pending_filter_keeps_only_courses_with_sessions_awaiting_closure(): void
    {
        $now = now();
        $lesson = DB::table('lessons')->insertGetId(['course_id' => $this->a['c1'], 'teacher_id' => $this->teacher['teacher_id'], 'title' => 'P', 'type' => 'session', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('attendance_sessions')->insert(['lesson_id' => $lesson, 'qr_token' => 'PENDFILT012345678901234567890123', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now]);

        $json = $this->api('head', $this->a, ['pending' => 1])->assertOk()->json();

        $this->assertSame(['C1 A'], $this->titles($json));
        $this->assertSame(['C1 A', 'C2 A', 'C3 A'], $this->titles($this->api('head', $this->a)->json()), 'بدونه تظهر كل المقررات');
    }

    public function test_every_summary_box_on_the_page_is_a_link(): void
    {
        $html = $this->actingAs($this->a['head']['user'])->get('/hod/follow-up')->assertOk()->getContent();

        preg_match_all('/<(a|div) class="fu-kpi[ "]/', $html, $m);
        $this->assertNotEmpty($m[1]);
        $this->assertSame(array_fill(0, count($m[1]), 'a'), $m[1], 'لا مربع غير تفاعلي');

        foreach (['level=high', 'level=medium', 'followup=overdue', '#clock-alerts', 'pending=1'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('id="clock-alerts"', $html, 'وجهة رابط التلاعب موجودة بالصفحة');
        $this->assertStringContainsString('id="programs"', $html);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->api('head', $this->a, ['sort' => 'hack'])->assertStatus(422);
        $this->api('head', $this->a, ['status' => 'x'])->assertStatus(422);
        $this->api('head', $this->a, ['days' => 1])->assertStatus(422);
    }

    // ───────────────────────────── المؤشرات و"عليك اليوم" ─────────────────────────────

    public function test_kpis_and_today_lists(): void
    {
        DB::table('student_risk_scores')->where('student_id', $this->s['high']['student_id'])->update([
            'previous_level' => 'low', 'previous_score' => 40, 'level_changed_at' => now()->subDays(2),
        ]);
        $this->intervention($this->s['high'], $this->a['advisor']['user']->user_id, today()->subDays(3)->toDateString());

        $json = $this->api('head', $this->a)->assertOk()->json();
        $k = $json['kpis'];

        $this->assertSame([1, 1, 2, 2], [$k['high'], $k['medium'], $k['low'], $k['unknown']]);
        $this->assertSame(1, $k['rising_week']);
        $this->assertSame(1, $k['overdue_followups']);

        $this->assertSame('high', $json['today']['top_risk'][0]['level']);
        $this->assertEqualsWithDelta(42.0, $json['today']['top_risk'][0]['change'], 0.01);
        $this->assertSame(3, $json['today']['overdue'][0]['days_late']);
    }

    public function test_only_the_latest_followup_per_student_counts(): void
    {
        $uid = $this->a['advisor']['user']->user_id;
        $this->intervention($this->s['high'], $uid, today()->subDays(5)->toDateString(), now()->subDays(6));
        $this->intervention($this->s['high'], $uid, today()->addDays(5)->toDateString()); // أحدث: موعده لم يحن

        $this->assertSame(0, $this->api('head', $this->a)->json('kpis.overdue_followups'));
    }

    public function test_escalated_clock_alerts_and_pending_sessions_are_counted_in_scope_only(): void
    {
        $now = now();
        DB::table('attendance_clock_alerts')->insert(['student_id' => $this->s['high']['student_id'], 'kind' => 'clock_skew', 'session_id' => 1, 'created_at' => $now, 'updated_at' => $now, 'escalated_at' => $now]);
        DB::table('attendance_clock_alerts')->insert(['student_id' => $this->s['low1']['student_id'], 'kind' => 'clock_skew', 'session_id' => 2, 'created_at' => $now, 'updated_at' => $now, 'acknowledged_at' => $now]);
        $other = $this->student($this->b, 'x');
        DB::table('attendance_clock_alerts')->insert(['student_id' => $other['student_id'], 'kind' => 'clock_skew', 'session_id' => 3, 'created_at' => $now, 'updated_at' => $now, 'escalated_at' => $now]);

        $lesson = DB::table('lessons')->insertGetId(['course_id' => $this->a['c1'], 'teacher_id' => $this->teacher['teacher_id'], 'title' => 'P', 'type' => 'session', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('attendance_sessions')->insert(['lesson_id' => $lesson, 'qr_token' => 'PENDTOKEN012345678901234567890123', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('attendance_sessions')->insert(['lesson_id' => $lesson, 'qr_token' => 'DONETOKEN012345678901234567890123', 'is_active' => false, 'finalized_at' => $now, 'created_at' => $now, 'updated_at' => $now]);

        $json = $this->api('head', $this->a)->json();

        $this->assertSame(1, $json['kpis']['clock_alerts_open'], 'المؤكَّد عليه لا يُحتسب، وطالب القسم الآخر لا يظهر');
        $this->assertSame(1, $json['kpis']['clock_alerts_escalated']);
        $this->assertSame(['high'], array_column($json['today']['clock_alerts'], 'name'), 'يظهر في قائمة اليوم طالب القسم المُصعَّد فقط');
        $this->assertSame(1, $json['kpis']['pending_sessions']);
        $this->assertSame(1, $this->courses($json)['C1 A']['pending_sessions']);
    }

    public function test_attendance_window_follows_the_days_filter(): void
    {
        DB::table('attendance')->update(['attendance_date' => today()->subDays(45)->toDateString()]);

        $c30 = $this->courses($this->api('head', $this->a)->json());
        $this->assertNull($c30['C1 A']['attendance_rate'], 'السجلات خارج 30 يومًا');

        $c60 = $this->courses($this->api('head', $this->a, ['days' => 60])->json());
        $this->assertNotNull($c60['C1 A']['attendance_rate']);
    }


    // ───────────────────────────── البرنامج (الدورة) ثم مقرراته ─────────────────────────────

    /** برنامج ثانٍ في القسم A له طالبان: واحد عالي الخطر يدرس مقررًا مشتركًا مع البرنامج الأول. */
    private function secondProgram(): array
    {
        $p2 = $this->makeProgram($this->a['dept']);
        DB::table('programs')->where('id', $p2)->update(['name' => 'برنامج ثانٍ']);
        $h = $this->makeStudent(['full_name' => 'p2high', 'academic_year' => 'السنة الأولى', 'status' => 'active'], $p2);
        $l = $this->makeStudent(['full_name' => 'p2low', 'academic_year' => 'السنة الأولى', 'status' => 'active'], $p2);
        $own = $this->makeCourse($p2, ['title' => 'P2 only', 'year' => 1]);
        foreach ([$h, $l] as $st) {
            $this->enroll($st['student_id'], $own);
            $this->enroll($st['student_id'], $this->a['c1']); // مقرر مشترك مع البرنامج الأول
        }
        $this->risk($h, 'high', 90);
        $this->risk($l, 'low', 5);

        return ['id' => $p2, 'high' => $h, 'low' => $l, 'course' => $own];
    }

    public function test_courses_are_grouped_under_their_program(): void
    {
        $p2 = $this->secondProgram();
        $this->attendance($p2['course'], [$p2['high'], $p2['low']], present: 2, absent: 4); // حضور ضعيف يرفعها فوق C1 في هذا البرنامج

        $programs = collect($this->api('head', $this->a)->assertOk()->json('programs'))->keyBy('name');

        $this->assertEqualsCanonicalizing([$this->a['program_name'], 'برنامج ثانٍ'], $programs->keys()->all());
        $this->assertSame(['C1 A', 'C2 A', 'C3 A'], array_column($programs[$this->a['program_name']]['courses'], 'title'));
        $this->assertSame(['P2 only', 'C1 A'], array_column($programs['برنامج ثانٍ']['courses'], 'title'), 'الأحوج أولاً داخل البرنامج');
        $this->assertSame('Dept A', $programs['برنامج ثانٍ']['department']);
    }

    public function test_a_shared_course_is_counted_per_program_cohort(): void
    {
        $this->secondProgram();

        $programs = collect($this->api('head', $this->a)->json('programs'))->keyBy('name');
        $inFirst  = collect($programs[$this->a['program_name']]['courses'])->firstWhere('title', 'C1 A');
        $inSecond = collect($programs['برنامج ثانٍ']['courses'])->firstWhere('title', 'C1 A');

        $this->assertSame(3, $inFirst['enrolled']);
        $this->assertSame([1, 1], [$inFirst['high'], $inFirst['medium']]);
        $this->assertSame(2, $inSecond['enrolled'], 'طالبا البرنامج الثاني فقط');
        $this->assertSame([1, 0], [$inSecond['high'], $inSecond['medium']]);
    }

    public function test_program_summary_and_order(): void
    {
        $this->secondProgram();

        $json = $this->api('head', $this->a)->json();
        $programs = collect($json['programs'])->keyBy('name');

        $first = $programs[$this->a['program_name']];
        $this->assertSame(6, $first['students']);
        $this->assertSame([1, 1], [$first['high'], $first['medium']]);
        $this->assertEqualsWithDelta(75.0, $first['attendance_rate'], 0.1, 'حضور كل مقررات البرنامج: 9 من 12');
        $this->assertSame(2, $programs['برنامج ثانٍ']['students']);

        $rank = ['attention' => 0, 'watch' => 1, 'ok' => 2, 'nodata' => 3];
        $keys = array_map(fn ($p) => [$rank[$p['status']], -($p['attention_score'] ?? 0)], $json['programs']);
        $sorted = $keys;
        usort($sorted, fn ($a, $b) => $a <=> $b);
        $this->assertSame($sorted, $keys, 'الحالة أولاً (تحتاج اهتمامًا قبل راقبها) ثم الدرجة');
    }

    public function test_status_filter_hides_programs_without_matching_courses(): void
    {
        $this->secondProgram();

        $names = array_column($this->api('head', $this->a, ['status' => 'nodata'])->json('programs'), 'name');

        $this->assertSame([$this->a['program_name']], $names, 'C3 وحده بلا بيانات، وبرنامج ثانٍ كله فيه بيانات');
    }

    public function test_advisor_sees_a_single_program(): void
    {
        $this->secondProgram();

        $programs = $this->api('advisor', $this->a)->json('programs');

        $this->assertCount(1, $programs);
        $this->assertSame($this->a['program_name'], $programs[0]['name']);
    }

    // ───────────────────────────── المرشدون ─────────────────────────────

    public function test_advisor_table_flags_an_advisor_who_ignores_at_risk_students(): void
    {
        $adv = collect($this->api('head', $this->a)->json('advisors'))->first();

        $this->assertSame($this->a['program_name'], $adv['branch']);
        $this->assertSame(6, $adv['students']);
        $this->assertSame([1, 1], [$adv['high'], $adv['medium']]);
        $this->assertSame(0, $adv['actions_recent']);
        $this->assertTrue($adv['needs_nudge']);

        $this->intervention($this->s['high'], $this->a['advisor']['user']->user_id, null);
        $adv = collect($this->api('head', $this->a)->json('advisors'))->first();
        $this->assertSame(1, $adv['actions_recent']);
        $this->assertFalse($adv['needs_nudge']);
    }

    public function test_head_never_sees_advisors_of_another_department(): void
    {
        $nameB = $this->b['program_name'];
        DB::table('teachers')->where('teacher_id', $this->b['advisor']['teacher_id'])->update(['advisor_branch' => $nameB, 'advisor_year' => 'السنة الأولى']);

        $advisors = $this->api('head', $this->a)->json('advisors');

        $this->assertCount(1, $advisors);
        $this->assertSame($this->a['program_name'], $advisors[0]['branch']);
    }

    // ───────────────────────────── حالات حدّية ─────────────────────────────

    public function test_inactive_students_are_excluded(): void
    {
        DB::table('users')->where('user_id', $this->s['high']['user']->user_id)->update(['status' => 'inactive']);

        $json = $this->api('head', $this->a)->json();

        $this->assertSame(5, $json['kpis']['students']);
        $this->assertSame(0, $json['kpis']['high']);
    }

    public function test_empty_department_renders_without_errors(): void
    {
        $dept = $this->makeDepartment('Empty');
        $head = $this->makeHead($dept);

        $this->actAs($head['user'])->getJson('/api/department-head/follow-up')->assertOk()
            ->assertJson(['kpis' => ['students' => 0, 'high' => 0], 'programs' => []]);

        $this->actingAs($head['user'])->get('/hod/follow-up')->assertOk()->assertSee('لا توجد دورات');
    }

    public function test_admin_sees_everything(): void
    {
        $admin = $this->makeUser('admin');

        $json = $this->actAs($admin)->getJson('/api/admin/follow-up')->assertOk()->json();

        $this->assertGreaterThanOrEqual(6, $json['kpis']['students']);
        $this->assertContains('C1 A', $this->titles($json));
    }
}
