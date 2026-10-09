<?php

namespace Tests\Feature\Risk;

use App\Models\StudentRiskHistory;
use App\Models\StudentRiskScore;
use App\Services\Risk\Factors\AssignmentsFactor;
use App\Services\Risk\Factors\AttendanceFactor;
use App\Services\Risk\Factors\EngagementFactor;
use App\Services\Risk\Factors\GradesFactor;
use App\Services\Risk\Factors\PatternsFactor;
use App\Services\Risk\RiskContext;
use App\Services\Risk\RiskScoreService;
use App\Services\Risk\Scale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * محرّك درجة الخطر: كل معادلة مُتحقَّق منها برقم محسوب يدويًا.
 * "اليوم" ثابت = الخميس 2026-10-08. نافذة الحضور 21 يومًا: الحالية 09-18..10-08 والسابقة 08-28..09-17.
 */
class RiskScoreServiceTest extends TestCase
{
    use MakesAcademicData;

    private array $student;
    private int $courseId;
    private int $teacherId;
    private Carbon $asOf;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 12:00:00');
        $this->asOf = now();

        $program = $this->makeProgram($this->makeDepartment());
        $teacher = $this->makeTeacher();
        $this->teacherId = $teacher['teacher_id'];
        $this->courseId = $this->makeCourse($program, ['title' => 'مادة الاختبار']);
        $this->student = $this->makeStudent([], $program);
        $this->enroll($this->student['student_id'], $this->courseId);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function ctx(?array $student = null): RiskContext
    {
        $s = $student ?? $this->student;

        return new RiskContext($s['student_id'], $s['user']->user_id, $this->asOf);
    }

    private function lesson(?int $courseId = null): int
    {
        return DB::table('lessons')->insertGetId([
            'course_id' => $courseId ?? $this->courseId, 'teacher_id' => $this->teacherId,
            'title' => 'L' . $this->nextSeq(), 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function attend(string $status, string $date, array $attrs = [], ?int $courseId = null, ?array $student = null): void
    {
        DB::table('attendance')->insert(array_merge([
            'student_id' => ($student ?? $this->student)['student_id'], 'lesson_id' => $this->lesson($courseId),
            'status' => $status, 'attendance_date' => $date, 'excuse_status' => 'none',
            'created_at' => $date . ' 09:00:00', 'updated_at' => now(),
        ], $attrs));
    }

    /** @param string[] $statuses */
    private function attendOn(array $statuses, string $from): void
    {
        foreach ($statuses as $i => $st) {
            $this->attend($st, Carbon::parse($from)->addDays($i)->toDateString());
        }
    }

    private function assignment(string $due): int
    {
        return DB::table('assignments')->insertGetId([
            'course_id' => $this->courseId, 'title' => 'A' . $this->nextSeq(), 'due_date' => $due,
            'max_points' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function submit(int $assignmentId, string $at): void
    {
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $assignmentId, 'student_id' => $this->student['student_id'], 'file_path' => '',
            'submitted_at' => $at, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function grade(float $score, float $max, string $date): void
    {
        $event = DB::table('grade_events')->insertGetId([
            'teacher_id' => $this->teacherId, 'course_id' => $this->courseId, 'type' => 'quiz', 'title' => 'G' . $this->nextSeq(),
            'max_score' => $max, 'date' => $date, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('grade_entries')->insert([
            'grade_event_id' => $event, 'student_id' => $this->student['student_id'], 'score' => $score,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ------------------------------------------------------------------ Scale

    public function test_scale_linear_curve_and_pretty(): void
    {
        $this->assertSame(0.0, Scale::linear(95, 90, 30));
        $this->assertSame(100.0, Scale::linear(10, 90, 30));
        $this->assertEquals(50.0, Scale::linear(60, 90, 30));
        $this->assertEquals(50.0, Scale::linear(15, 0, 30));
        $this->assertSame(0.0, Scale::linear(5, 5, 5));

        $curve = [7 => 0, 14 => 40, 28 => 70, 45 => 100];
        $this->assertEquals(0.0, Scale::curve(3, $curve));
        $this->assertEquals(40.0, Scale::curve(14, $curve));
        $this->assertEquals(55.0, Scale::curve(21, $curve));
        $this->assertEquals(100.0, Scale::curve(90, $curve));

        $this->assertSame('90', Scale::pretty(90.0));
        $this->assertSame('87.5', Scale::pretty(87.5));
    }

    // ------------------------------------------------------------ الحضور

    public function test_attendance_combines_level_and_drop(): void
    {
        $this->attendOn(array_merge(array_fill(0, 4, 'present'), array_fill(0, 6, 'absent')), '2026-09-22'); // 40%
        $this->attendOn(array_fill(0, 10, 'present'), '2026-09-01');                                         // السابقة 100%

        $r = (new AttendanceFactor())->evaluate($this->ctx());

        // level = (40-90)/(30-90)*100 = 83.3 ، drop = 60 → trend 100 ، score = 0.6*83.3+0.4*100 = 90
        $this->assertEquals(90.0, $r['score']);
        $this->assertEquals(40.0, $r['facts']['recent_rate']);
        $this->assertEquals(100.0, $r['facts']['prior_rate']);
        $this->assertStringContainsString('حضوره 40%', $r['reason']);
        $this->assertStringContainsString('كان 100%', $r['reason']);
    }

    public function test_attendance_without_prior_uses_level_only(): void
    {
        $this->attendOn(array_merge(array_fill(0, 3, 'present'), array_fill(0, 3, 'absent')), '2026-09-22'); // 50%

        $r = (new AttendanceFactor())->evaluate($this->ctx());

        $this->assertEquals(66.7, $r['score']);        // (50-90)/(30-90)
        $this->assertNull($r['facts']['prior_rate']);
    }

    public function test_healthy_attendance_scores_zero_and_late_counts_as_attended(): void
    {
        $this->attendOn(['present', 'present', 'late', 'present', 'present'], '2026-09-22');

        $this->assertEquals(0.0, (new AttendanceFactor())->evaluate($this->ctx())['score']);
    }

    public function test_attendance_ignores_excused_absence_and_needs_enough_records(): void
    {
        $this->attendOn(['present', 'present'], '2026-09-22');
        $this->attend('absent', '2026-09-25', ['excuse_status' => 'approved']);
        $this->assertNull((new AttendanceFactor())->evaluate($this->ctx()), 'سجلان فعليان فقط (المعذور مُستبعد) أقل من الحد');

        $this->attend('present', '2026-09-26');
        $r = (new AttendanceFactor())->evaluate($this->ctx());
        $this->assertEquals(0.0, $r['score']);
        $this->assertSame(3, $r['facts']['recent_total']);
    }

    public function test_attendance_ignores_data_after_the_evaluation_day(): void
    {
        $this->attendOn(array_fill(0, 5, 'present'), '2026-09-22');
        $this->attendOn(array_fill(0, 5, 'absent'), '2026-10-09'); // بعد يوم التقييم

        $this->assertEquals(0.0, (new AttendanceFactor())->evaluate($this->ctx())['score']);
    }

    // ---------------------------------------------------------- الواجبات

    public function test_assignments_missing_and_late(): void
    {
        $onTime = $this->assignment('2026-10-01 23:59:00');
        $late   = $this->assignment('2026-10-02 23:59:00');
        $this->assignment('2026-10-03 23:59:00');   // فائت
        $this->assignment('2026-10-04 23:59:00');   // فائت
        $this->submit($onTime, '2026-10-01 10:00:00');
        $this->submit($late, '2026-10-04 10:00:00');
        $this->assignment('2026-10-20 23:59:00');   // لم يحل موعده بعد: لا يُحتسب
        $this->assignment('2026-08-01 23:59:00');   // خارج النافذة: لا يُحتسب

        $r = (new AssignmentsFactor())->evaluate($this->ctx());

        // (2 فائت + 0.5×1 متأخر) / 4 = 0.625 × 120 = 75
        $this->assertEquals(75.0, $r['score']);
        $this->assertSame(['due' => 4, 'missing' => 2, 'late' => 1], $r['facts']);
        $this->assertStringContainsString('2 لم تُسلَّم', $r['reason']);
        $this->assertStringContainsString('1 متأخرة', $r['reason']);
    }

    public function test_assignments_all_on_time_scores_zero_and_few_assignments_give_no_data(): void
    {
        $a = $this->assignment('2026-10-01 23:59:00');
        $this->assertNull((new AssignmentsFactor())->evaluate($this->ctx()), 'واجب واحد أقل من الحد');

        $b = $this->assignment('2026-10-02 23:59:00');
        $this->submit($a, '2026-09-30 10:00:00');
        $this->submit($b, '2026-10-02 10:00:00');

        $this->assertEquals(0.0, (new AssignmentsFactor())->evaluate($this->ctx())['score']);
    }

    public function test_assignments_ignore_submissions_made_after_the_evaluation_day_and_not_enrolled(): void
    {
        $a = $this->assignment('2026-10-01 23:59:00');
        $b = $this->assignment('2026-10-02 23:59:00');
        $this->submit($a, '2026-10-09 10:00:00'); // سُلِّم بعد يوم التقييم = كان فائتًا وقتها
        $this->submit($b, '2026-10-09 11:00:00');

        $this->assertEquals(100.0, (new AssignmentsFactor())->evaluate($this->ctx())['score']);

        $loner = $this->makeStudent();
        $this->assertNull((new AssignmentsFactor())->evaluate($this->ctx($loner)), 'غير مسجَّل في أي مادة');
    }

    // ----------------------------------------------------------- العلامات

    public function test_grades_level_and_drop_from_dated_entries(): void
    {
        $this->grade(8, 20, '2026-09-25');    // 40%
        $this->grade(10, 20, '2026-10-02');   // 50%  → المتوسط 45
        $this->grade(15, 20, '2026-08-20');   // السابقة 75%

        $r = (new GradesFactor())->evaluate($this->ctx());

        // level = (45-70)/(30-70) = 62.5 ، drop = 30 → trend 100 ، score = 0.6*62.5+0.4*100 = 77.5
        $this->assertEquals(77.5, $r['score']);
        $this->assertEquals(45.0, $r['facts']['recent_avg']);
        $this->assertSame(1, $r['facts']['fails']);   // 40% فقط تحت 50 (والـ 50% ليست تحتها)
        $this->assertStringContainsString('متوسط علاماته 45%', $r['reason']);
        $this->assertStringContainsString('كان 75%', $r['reason']);
        $this->assertStringContainsString('1 علامة تحت 50%', $r['reason']);
    }

    public function test_grades_read_the_legacy_exam_system_and_ignore_undated_future_or_empty(): void
    {
        $exam = DB::table('exams')->insertGetId([
            'course_id' => $this->courseId, 'exam_name' => 'نصفي', 'exam_date' => '2026-10-01 09:00:00',
            'max_score' => 50, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('grades')->insert(['student_id' => $this->student['student_id'], 'exam_id' => $exam, 'score' => 45, 'created_at' => now(), 'updated_at' => now()]);
        $this->grade(2, 20, '2026-10-15'); // تاريخ مستقبلي: يُهمل

        $r = (new GradesFactor())->evaluate($this->ctx());

        $this->assertSame(1, $r['facts']['recent_count']);
        $this->assertEquals(90.0, $r['facts']['recent_avg']);
        $this->assertEquals(0.0, $r['score']);

        $this->assertNull((new GradesFactor())->evaluate($this->ctx($this->makeStudent())), 'بلا علامات');
    }

    // ------------------------------------------------------------ الانقطاع

    public function test_engagement_curve_and_missing_data(): void
    {
        $this->assertNull((new EngagementFactor())->evaluate($this->ctx()), 'لا سجل دخول إطلاقًا = لا بيانات');

        DB::table('users')->where('user_id', $this->student['user']->user_id)->update(['last_login' => '2026-09-18 08:00:00']); // قبل 20 يومًا
        $r = (new EngagementFactor())->evaluate($this->ctx());

        $this->assertEquals(52.9, $r['score']);        // 40 + (20-14)/(28-14)*30
        $this->assertSame(20, $r['facts']['days_since_login']);
        $this->assertStringContainsString('منذ 20 يومًا', $r['reason']);
    }

    public function test_engagement_uses_the_activity_log_and_ignores_future_logins(): void
    {
        $uid = $this->student['user']->user_id;
        DB::table('users')->where('user_id', $uid)->update(['last_login' => '2026-10-20 08:00:00']); // بعد يوم التقييم
        DB::table('user_activities')->insert([
            'user_id' => $uid, 'action' => 'تسجيل دخول (تطبيق)', 'created_at' => '2026-10-07 10:00:00', 'updated_at' => now(),
        ]);

        $r = (new EngagementFactor())->evaluate($this->ctx());

        $this->assertEquals(0.0, $r['score']);
        $this->assertSame(1, $r['facts']['days_since_login']);
    }

    // ------------------------------------------------------------- الأنماط

    public function test_pattern_concentrated_in_one_course(): void
    {
        $other = $this->makeCourse(null, ['title' => 'مادة أخرى']);
        $this->enroll($this->student['student_id'], $other);

        foreach (['2026-09-20', '2026-09-27', '2026-10-04', '2026-10-05'] as $d) {
            $this->attend('absent', $d, [], $this->courseId);
        }
        $this->attend('present', '2026-09-21', [], $this->courseId);
        foreach (['2026-09-21', '2026-09-28', '2026-10-06'] as $d) {
            $this->attend('present', $d, [], $other);
        }

        $r = (new PatternsFactor())->evaluate($this->ctx());

        $this->assertEquals(70.0, $r['score']);
        $this->assertStringContainsString('مادة الاختبار', $r['reason']);
        $this->assertStringContainsString('4 من 5', $r['reason']);
    }

    public function test_pattern_same_weekday_repeatedly(): void
    {
        // أيام الأحد 09-20 و09-27 و10-04 غياب، وبقية الأيام حضور
        foreach (['2026-09-20', '2026-09-27', '2026-10-04'] as $sunday) {
            $this->attend('absent', $sunday);
        }
        foreach (['2026-09-21', '2026-09-22', '2026-09-28', '2026-09-29'] as $d) {
            $this->attend('present', $d);
        }

        $r = (new PatternsFactor())->evaluate($this->ctx());

        $this->assertEquals(60.0, $r['score']);
        $this->assertStringContainsString('الأحد', $r['reason']);
    }

    public function test_pattern_consecutive_absent_school_days_skip_the_weekend(): void
    {
        // الخميس 10-01 ثم الأحد 10-04 والإثنين 10-05: ثلاثة أيام دوام متتالية (الجمعة والسبت ليسا دوامًا)
        foreach (['2026-10-01', '2026-10-04', '2026-10-05'] as $d) {
            $this->attend('absent', $d);
        }
        $this->attend('present', '2026-09-20');

        $r = (new PatternsFactor())->evaluate($this->ctx());

        $this->assertEquals(100.0, $r['score']);
        $this->assertStringContainsString('3 أيام دوام متتالية', $r['reason']);
    }

    public function test_no_pattern_scores_zero_and_scarce_data_gives_null(): void
    {
        $this->attendOn(array_fill(0, 5, 'present'), '2026-09-22');
        $r = (new PatternsFactor())->evaluate($this->ctx());
        $this->assertEquals(0.0, $r['score']);
        $this->assertSame('لا أنماط غياب ملحوظة', $r['reason']);

        $this->assertNull((new PatternsFactor())->evaluate($this->ctx($this->makeStudent())));
    }

    // -------------------------------------------------------------- التجميع

    private function troubledStudent(): void
    {
        $this->attendOn(array_merge(array_fill(0, 4, 'present'), array_fill(0, 6, 'absent')), '2026-09-22');
        $this->attendOn(array_fill(0, 10, 'present'), '2026-09-01');
        $this->assignment('2026-10-03 23:59:00');
        $this->assignment('2026-10-04 23:59:00');
        $this->grade(8, 20, '2026-09-25');
        DB::table('users')->where('user_id', $this->student['user']->user_id)->update(['last_login' => '2026-09-01 08:00:00']);
    }

    public function test_aggregate_score_level_confidence_and_explanations(): void
    {
        $this->troubledStudent();

        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);

        $this->assertSame('high', $res['level']);
        $this->assertEquals(1.0, $res['confidence']);
        $this->assertGreaterThanOrEqual(60, $res['score']);
        $this->assertLessThanOrEqual(100, $res['score']);
        $this->assertCount(5, $res['factors']);
        $this->assertNotEmpty($res['reasons']);
        $this->assertLessThanOrEqual(4, count($res['reasons']));

        // الدرجة = مجموع المساهمات، وكل مساهمة قابلة لإعادة الحساب من النقاط والأوزان
        $this->assertEquals($res['score'], round(array_sum(array_column($res['factors'], 'contribution')), 1), '', 0.2);
        $att = collect($res['factors'])->firstWhere('key', 'attendance');
        $this->assertEquals(round(30 / 100 * $att['score'], 1), $att['contribution']);
    }

    public function test_missing_factors_are_not_punished_and_weights_are_renormalized(): void
    {
        // حضور سيئ فقط (بلا واجبات/علامات/دخول): العوامل المتوفرة هي الحضور والأنماط
        $this->attendOn(array_merge(array_fill(0, 2, 'present'), array_fill(0, 8, 'absent')), '2026-09-22');

        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);

        $this->assertEquals(0.4, $res['confidence']);   // (30+10)/100
        $unavailable = collect($res['factors'])->where('available', false)->pluck('key')->sort()->values()->all();
        $this->assertSame(['assignments', 'engagement', 'grades'], $unavailable);
        $this->assertNotSame('low', $res['level']);
        $this->assertSame('لا بيانات كافية', collect($res['factors'])->firstWhere('key', 'grades')['reason']);
    }

    public function test_too_little_data_is_unknown_never_low(): void
    {
        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);

        $this->assertSame('unknown', $res['level']);
        $this->assertEquals(0.0, $res['confidence']);
        $this->assertSame([], $res['reasons']);

        // الحضور وحده (30 من 100) = 0.3 يبلغ الحد الأدنى للثقة
        $this->attendOn(array_fill(0, 2, 'present'), '2026-09-22');
        $this->attend('present', '2026-09-25');
        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);
        $this->assertSame('low', $res['level']);
    }

    public function test_healthy_student_is_low_without_alarming_reasons(): void
    {
        $this->attendOn(array_fill(0, 8, 'present'), '2026-09-22');
        $a = $this->assignment('2026-10-01 23:59:00');
        $b = $this->assignment('2026-10-02 23:59:00');
        $this->submit($a, '2026-10-01 08:00:00');
        $this->submit($b, '2026-10-02 08:00:00');
        $this->grade(18, 20, '2026-10-01');

        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);

        $this->assertSame('low', $res['level']);
        $this->assertLessThan(10, $res['score']);
        $this->assertSame([], $res['reasons']);
    }

    public function test_level_boundaries_follow_the_config(): void
    {
        config(['risk.levels' => ['high' => 20, 'medium' => 10]]);
        $this->attendOn(array_merge(array_fill(0, 5, 'present'), array_fill(0, 5, 'absent')), '2026-09-22'); // 50%

        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);

        $this->assertSame('high', $res['level']);   // الحضور 50% ونمط بلا شيء: الدرجة فوق 20 بسهولة
    }

    // ----------------------------------------------------------- الحفظ والتاريخ

    public function test_refresh_stores_the_score_and_one_history_row_per_day(): void
    {
        $this->troubledStudent();
        $svc = app(RiskScoreService::class);
        $sid = $this->student['student_id'];
        $uid = $this->student['user']->user_id;

        $first = $svc->refresh($sid, $uid, $this->asOf);
        $svc->refresh($sid, $uid, $this->asOf);   // إعادة التشغيل في اليوم نفسه

        $this->assertSame(1, StudentRiskScore::where('student_id', $sid)->count());
        $this->assertSame(1, StudentRiskHistory::where('student_id', $sid)->count());
        $this->assertFalse($first['level_rose'], 'أول حساب ليس "ارتفاعًا"');
        $this->assertSame('high', $first['row']->level);
        $this->assertNull($first['row']->fresh()->previous_score);
    }

    public function test_refresh_tracks_previous_values_and_level_rise(): void
    {
        $svc = app(RiskScoreService::class);
        $sid = $this->student['student_id'];
        $uid = $this->student['user']->user_id;

        // يوم 1: طالب جيد
        $this->attendOn(array_fill(0, 6, 'present'), '2026-09-22');
        $svc->refresh($sid, $uid, Carbon::parse('2026-10-01'));
        $this->assertSame('low', StudentRiskScore::where('student_id', $sid)->value('level'));

        // يوم 2: غياب متواصل بعد ذلك
        $this->attendOn(array_fill(0, 6, 'absent'), '2026-10-02');
        $out = $svc->refresh($sid, $uid, $this->asOf);

        $this->assertTrue($out['level_rose']);
        $this->assertSame('low', $out['from_level']);
        $row = $out['row']->fresh();
        $this->assertSame('low', $row->previous_level);
        $this->assertNotNull($row->previous_score);
        $this->assertGreaterThan($row->previous_score, $row->score);
        $this->assertNotNull($row->level_changed_at);
        $this->assertSame(2, StudentRiskHistory::where('student_id', $sid)->count());
    }

    public function test_refreshing_an_older_date_only_adds_history(): void
    {
        $svc = app(RiskScoreService::class);
        $sid = $this->student['student_id'];
        $uid = $this->student['user']->user_id;
        $this->attendOn(array_fill(0, 6, 'present'), '2026-09-22');

        $svc->refresh($sid, $uid, $this->asOf);
        $current = StudentRiskScore::where('student_id', $sid)->first();
        $svc->refresh($sid, $uid, Carbon::parse('2026-09-30'));   // إعادة بناء الماضي

        $this->assertSame(2, StudentRiskHistory::where('student_id', $sid)->count());
        $this->assertSame($current->computed_on->toDateString(), StudentRiskScore::where('student_id', $sid)->first()->computed_on->toDateString());
    }

    // ------------------------------------------------------------ الحالة الحرجة

    private function perfectExceptAttendance(string $attendance): void
    {
        $this->attendOn(array_fill(0, 8, $attendance), '2026-09-22');
        $a = $this->assignment('2026-10-01 23:59:00');
        $b = $this->assignment('2026-10-02 23:59:00');
        $this->submit($a, '2026-10-01 08:00:00');
        $this->submit($b, '2026-10-02 08:00:00');
        $this->grade(19, 20, '2026-10-01');
        DB::table('users')->where('user_id', $this->student['user']->user_id)->update(['last_login' => '2026-10-07 09:00:00']);
    }

    public function test_a_collapsed_major_factor_is_not_diluted_by_the_healthy_ones(): void
    {
        $this->perfectExceptAttendance('absent');   // حضور 0% مع واجبات وعلامات ودخول ممتازة

        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);

        $this->assertSame(['attendance'], $res['critical']);
        $this->assertSame('high', $res['level']);
        $this->assertGreaterThanOrEqual(60.0, $res['score']);
        $this->assertTrue(collect($res['factors'])->firstWhere('key', 'attendance')['critical']);
        $this->assertStringContainsString('حضوره 0%', $res['reasons'][0]);   // يُذكر دائمًا في الأسباب
    }

    public function test_a_merely_bad_factor_is_not_critical_and_keeps_its_weighted_score(): void
    {
        $this->perfectExceptAttendance('present');
        // 5 غياب من 8 = 37.5% حضور: سيئ لكنه دون الانهيار الكامل
        DB::table('attendance')->where('student_id', $this->student['student_id'])->limit(5)->update(['status' => 'absent']);

        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);

        $this->assertSame([], $res['critical']);
        $this->assertLessThan(60, $res['score']);
    }

    public function test_minor_factors_can_never_be_critical_alone(): void
    {
        $this->perfectExceptAttendance('present');
        DB::table('users')->where('user_id', $this->student['user']->user_id)->update(['last_login' => '2026-06-01 09:00:00']); // انقطاع شديد (وزنه 10 فقط)

        $res = app(RiskScoreService::class)->compute($this->student['student_id'], $this->student['user']->user_id, $this->asOf);

        $this->assertEquals(100.0, collect($res['factors'])->firstWhere('key', 'engagement')['score']);
        $this->assertSame([], $res['critical']);
        $this->assertNotSame('high', $res['level']);
    }
}
