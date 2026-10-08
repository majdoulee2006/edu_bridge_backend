<?php

namespace Tests\Feature\AttendanceIntelligence;

use App\Models\AttendanceFlag;
use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * واجهات الاشتباهات (المعلم) والتحليلات (رئيس القسم/الأدمن): الصلاحيات، النطاق، والحساب.
 * "الآن" ثابت = الخميس 2026-10-08، فالأحد 2026-10-04 هو يوم 0 في الأسبوع.
 */
class AttendanceInsightsApiTest extends TestCase
{
    use MakesAcademicData;

    private array $a;   // قسم A: معلم + مقرر + رئيس قسم
    private array $b;   // قسم B

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Carbon::setTestNow('2026-10-08 12:00:00');
        config(['attendance_intelligence.analytics.min_records_for_ranking' => 3]);

        $this->a = $this->department('A');
        $this->b = $this->department('B');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function department(string $name): array
    {
        $dept = $this->makeDepartment("Dept $name");
        $program = $this->makeProgram($dept);
        $teacher = $this->makeTeacher();
        $course = $this->makeCourse($program, ['title' => "Course $name", 'year' => 1]);
        $this->assignTeacher($course, $teacher['teacher_id']);
        $head = $this->makeHead($dept);

        return compact('dept', 'program', 'teacher', 'course', 'head');
    }

    private function lesson(array $dep): int
    {
        return DB::table('lessons')->insertGetId([
            'course_id' => $dep['course'], 'teacher_id' => $dep['teacher']['teacher_id'],
            'title' => 'L' . $this->nextSeq(), 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function newSession(array $dep, array $attrs = []): AttendanceSession
    {
        $id = DB::table('attendance_sessions')->insertGetId(array_merge([
            'lesson_id' => $this->lesson($dep), 'qr_token' => 'T' . $this->nextSeq(),
            'expires_at' => now()->addSeconds(30), 'session_expires_at' => now()->addMinutes(10),
            'is_active' => false, 'closed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ], $attrs));

        return AttendanceSession::findOrFail($id);
    }

    private function record(array $dep, string $status, string $date, array $attrs = [], ?int $studentId = null, ?int $lessonId = null): int
    {
        $studentId ??= $this->makeStudent([], $dep['program'])['student_id'];

        return DB::table('attendance')->insertGetId(array_merge([
            'student_id' => $studentId, 'lesson_id' => $lessonId ?? $this->lesson($dep),
            'status' => $status, 'attendance_date' => $date, 'excuse_status' => 'none',
            'created_at' => $date . ' 08:10:00', 'updated_at' => now(),
        ], $attrs));
    }

    private function flag(array $dep, array $attrs = []): AttendanceFlag
    {
        $att = $this->record($dep, 'present', '2026-10-08');
        $studentId = DB::table('attendance')->where('attendance_id', $att)->value('student_id');

        return AttendanceFlag::create(array_merge([
            'attendance_id' => $att, 'student_id' => $studentId, 'course_id' => $dep['course'],
            'rule' => 'shared_device', 'severity' => 'high', 'status' => 'open',
            'details' => ['group_size' => 2, 'device_tail' => 'ABC123'],
        ], $attrs));
    }

    // ------------------------------------------------- مراجعة المعلم: الصلاحيات

    public function test_teacher_sees_only_flags_of_own_courses_with_explanations(): void
    {
        $mine = $this->flag($this->a);
        $this->flag($this->b);
        $this->actAs($this->a['teacher']['user']);

        $res = $this->getJson('/api/teacher/attendance-flags')->assertOk();

        $res->assertJsonPath('summary.open', 1)
            ->assertJsonPath('summary.by_severity.high', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.rule_label', 'جهاز مشترك')
            ->assertJsonPath('data.0.severity_label', 'عالية');
        $this->assertStringContainsString('ABC123', $res->json('data.0.explanation'));
    }

    public function test_flags_can_be_filtered_by_status_and_severity(): void
    {
        $this->flag($this->a, ['severity' => 'low', 'rule' => 'ip_outlier']);
        $this->flag($this->a, ['severity' => 'high', 'status' => 'dismissed']);
        $this->actAs($this->a['teacher']['user']);

        $this->getJson('/api/teacher/attendance-flags')->assertJsonCount(1, 'data');
        $this->getJson('/api/teacher/attendance-flags?status=all')->assertJsonCount(2, 'data');
        $this->getJson('/api/teacher/attendance-flags?status=dismissed')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.severity', 'high');
        $this->getJson('/api/teacher/attendance-flags?status=all&severity=low')->assertJsonCount(1, 'data');
    }

    public function test_teacher_cannot_review_a_flag_of_another_teachers_course(): void
    {
        $theirs = $this->flag($this->b);
        $this->actAs($this->a['teacher']['user']);

        $this->putJson("/api/teacher/attendance-flags/{$theirs->id}", ['status' => 'confirmed'])->assertNotFound();

        $this->assertSame('open', $theirs->fresh()->status);
    }

    public function test_only_teachers_can_use_the_review_routes(): void
    {
        $f = $this->flag($this->a);

        foreach ([$this->makeStudent()['user'], $this->makeParent()['user']] as $user) {
            $this->actAs($user);
            $this->getJson('/api/teacher/attendance-flags')->assertForbidden();
            $this->putJson("/api/teacher/attendance-flags/{$f->id}", ['status' => 'dismissed'])->assertForbidden();
        }
    }

    // ------------------------------------------------------------- المراجعة

    public function test_dismissing_records_the_decision_and_leaves_attendance_untouched(): void
    {
        $f = $this->flag($this->a);
        $this->actAs($this->a['teacher']['user']);

        $this->putJson("/api/teacher/attendance-flags/{$f->id}", ['status' => 'dismissed', 'note' => 'زميلان بجوار بعض'])
            ->assertOk()->assertJsonPath('data.status', 'dismissed')->assertJsonPath('data.review.note', 'زميلان بجوار بعض');

        $f->refresh();
        $this->assertSame('dismissed', $f->status);
        $this->assertSame($this->a['teacher']['user']->user_id, (int) $f->reviewed_by);
        $this->assertNotNull($f->reviewed_at);
        $this->assertSame('present', DB::table('attendance')->where('attendance_id', $f->attendance_id)->value('status'));
    }

    public function test_confirming_alone_does_not_change_attendance(): void
    {
        $f = $this->flag($this->a);
        $this->actAs($this->a['teacher']['user']);

        $this->putJson("/api/teacher/attendance-flags/{$f->id}", ['status' => 'confirmed'])->assertOk();

        $this->assertSame('present', DB::table('attendance')->where('attendance_id', $f->attendance_id)->value('status'));
    }

    public function test_confirming_with_mark_absent_makes_the_student_absent(): void
    {
        $f = $this->flag($this->a);
        $this->actAs($this->a['teacher']['user']);

        $this->putJson("/api/teacher/attendance-flags/{$f->id}", ['status' => 'confirmed', 'mark_absent' => true])
            ->assertOk()->assertJsonPath('data.review.marked_absent', true);

        $this->assertSame('absent', DB::table('attendance')->where('attendance_id', $f->attendance_id)->value('status'));
    }

    public function test_mark_absent_is_ignored_when_dismissing(): void
    {
        $f = $this->flag($this->a);
        $this->actAs($this->a['teacher']['user']);

        $this->putJson("/api/teacher/attendance-flags/{$f->id}", ['status' => 'dismissed', 'mark_absent' => true])->assertOk();

        $this->assertSame('present', DB::table('attendance')->where('attendance_id', $f->attendance_id)->value('status'));
    }

    public function test_a_reviewed_flag_cannot_be_reviewed_again_and_input_is_validated(): void
    {
        $f = $this->flag($this->a);
        $this->actAs($this->a['teacher']['user']);

        $this->putJson("/api/teacher/attendance-flags/{$f->id}", ['status' => 'maybe'])->assertStatus(422);
        $this->putJson("/api/teacher/attendance-flags/{$f->id}", ['status' => 'dismissed'])->assertOk();
        $this->putJson("/api/teacher/attendance-flags/{$f->id}", ['status' => 'confirmed', 'mark_absent' => true])->assertStatus(409);

        $this->assertSame('present', DB::table('attendance')->where('attendance_id', $f->attendance_id)->value('status'));
    }

    // ------------------------------------------------- بصمة المسح وإنهاء الجلسة

    public function test_scan_stores_the_fingerprint_for_later_analysis(): void
    {
        $dep = $this->a;
        $student = $this->makeStudent(['academic_year' => 'السنة الأولى'], $dep['program']);
        DB::table('students')->where('student_id', $student['student_id'])->update(['level' => 'السنة الأولى']);
        $this->enroll($student['student_id'], $dep['course']);

        // QR صدر قبل 8 ثوانٍ: expires_at = الآن + 22 ثانية
        $session = $this->newSession($dep, [
            'qr_token' => 'FINGERPRINT-TOKEN-0123456789ABCDEF', 'is_active' => true, 'closed_at' => null,
            'expires_at' => now()->addSeconds(22), 'created_at' => now()->subSeconds(20),
        ]);

        $this->actAs($student['user'])
            ->withHeaders(['User-Agent' => 'EduBridgeTest/1.0'])
            ->postJson('/api/student/attendance/scan', ['qr_token' => 'FINGERPRINT-TOKEN-0123456789ABCDEF'])
            ->assertOk();

        $row = DB::table('attendance')->where('student_id', $student['student_id'])->first();
        $this->assertSame($session->id, (int) $row->session_id);
        $this->assertSame('EduBridgeTest/1.0', $row->user_agent);
        $this->assertNotEmpty($row->ip_address);
        $this->assertSame(8, (int) $row->qr_age_seconds);
    }

    public function test_ending_a_session_closes_it_and_runs_the_analysis(): void
    {
        $dep = $this->a;
        $session = $this->newSession($dep, ['is_active' => true, 'closed_at' => null]);
        $lessonId = $session->lesson_id;
        foreach (range(1, 2) as $_) {
            $this->record($dep, 'present', '2026-10-08', ['session_id' => $session->id, 'device_id' => 'SHARED-DEV-998877'], null, $lessonId);
        }

        $this->actAs($dep['teacher']['user'])
            ->postJson("/api/teacher/attendance/session/{$session->id}/end")->assertOk();

        $session->refresh();
        $this->assertNotNull($session->closed_at);
        $this->assertNotNull($session->analyzed_at);
        $this->assertSame(2, AttendanceFlag::where('session_id', $session->id)->where('rule', 'shared_device')->count());
    }

    public function test_analyze_command_picks_up_finished_sessions_once(): void
    {
        $dep = $this->a;
        $expired = $this->newSession($dep, ['is_active' => true, 'closed_at' => null, 'session_expires_at' => now()->subMinutes(5)]);
        $running = $this->newSession($dep, ['is_active' => true, 'closed_at' => null, 'session_expires_at' => now()->addMinutes(5)]);
        foreach ([$expired, $running] as $s) {
            foreach (range(1, 2) as $_) {
                $this->record($dep, 'present', '2026-10-08', ['session_id' => $s->id, 'device_id' => 'SHARED-DEV-' . $s->id], null, $s->lesson_id);
            }
        }

        $this->artisan('attendance:analyze')->assertExitCode(0);

        $this->assertNotNull($expired->fresh()->analyzed_at);
        $this->assertNull($running->fresh()->analyzed_at, 'جلسة جارية لا تُحلَّل');
        $this->assertSame(2, AttendanceFlag::count());

        $this->artisan('attendance:analyze')->assertExitCode(0);
        $this->assertSame(2, AttendanceFlag::count());
    }

    public function test_analysis_can_be_switched_off(): void
    {
        config(['attendance_intelligence.enabled' => false]);
        $dep = $this->a;
        $s = $this->newSession($dep, ['session_expires_at' => now()->subMinutes(5)]);

        $this->artisan('attendance:analyze')->assertExitCode(0);

        $this->assertNull($s->fresh()->analyzed_at);
    }

    // ---------------------------------------------------------- الخريطة والتحليل

    public function test_heatmap_rates_use_schedule_slots_exclude_excused_and_count_late(): void
    {
        $dep = $this->a;
        DB::table('schedules')->insert([
            'course_id' => $dep['course'], 'teacher_id' => $dep['teacher']['user']->user_id, 'day' => 'Sunday', 'start_time' => '09:30:00', 'end_time' => '11:00:00', 'room' => 'R1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // الأحد 2026-10-04: 2 حاضر + 1 متأخر + 1 غائب = 3/4، ويُستبعد الغياب المعذور من المقام
        $lesson = $this->lesson($dep);
        foreach (['present', 'present', 'late', 'absent'] as $st) {
            $this->record($dep, $st, '2026-10-04', [], null, $lesson);
        }
        $this->record($dep, 'absent', '2026-10-04', ['excuse_status' => 'approved'], null, $lesson);

        $this->actAs($dep['head']['user']);
        $data = $this->getJson('/api/department-head/attendance/insights')->assertOk()->json('data');

        $this->assertSame(4, $data['overall']['total']);
        $this->assertEquals(75.0, $data['overall']['rate']);
        $this->assertSame([['key' => 0, 'label' => 'الأحد']], $data['heatmap']['days']);
        $this->assertSame(['09:30'], $data['heatmap']['slots']);
        $this->assertEquals(
            ['day' => 0, 'slot' => '09:30', 'total' => 4, 'attended' => 3, 'rate' => 75.0],
            $data['heatmap']['cells'][0]
        );
    }

    public function test_slot_falls_back_to_scan_hour_without_a_schedule(): void
    {
        $dep = $this->a;
        $this->record($dep, 'present', '2026-10-05', ['created_at' => '2026-10-05 10:20:00']);

        $this->actAs($dep['head']['user']);
        $cell = $this->getJson('/api/department-head/attendance/insights')->json('data.heatmap.cells.0');

        $this->assertSame(1, $cell['day']);          // الإثنين
        $this->assertSame('10:00', $cell['slot']);
    }

    public function test_course_ranking_orders_worst_first_and_ignores_tiny_samples(): void
    {
        $dep = $this->a;
        $second = $this->makeCourse($dep['program'], ['title' => 'Weak course', 'year' => 1]);
        $this->assignTeacher($second, $dep['teacher']['teacher_id']);
        $third = $this->makeCourse($dep['program'], ['title' => 'Tiny sample', 'year' => 1]);

        foreach (range(1, 4) as $_) {
            $this->record($dep, 'present', '2026-10-05');                                  // Course A: 100%
            $this->record(['course' => $second] + $dep, 'absent', '2026-10-05');           // Weak: 0%
        }
        $this->record(['course' => $third] + $dep, 'absent', '2026-10-05');               // عيّنة صغيرة

        $this->actAs($dep['head']['user']);
        $courses = $this->getJson('/api/department-head/attendance/insights')->json('data.courses');

        $this->assertSame(['Weak course', 'Course A'], array_column($courses, 'title'));
        $this->assertEquals(0.0, $courses[0]['rate']);
    }

    public function test_behavior_change_lists_a_good_student_who_dropped_recently(): void
    {
        $dep = $this->a;
        $dropped = $this->makeStudent(['full_name' => 'طالب هبط'], $dep['program'])['student_id'];
        $steady  = $this->makeStudent(['full_name' => 'طالب ثابت'], $dep['program'])['student_id'];

        // الأساس (قبل 14 يومًا): 10 حضور لكل منهما
        foreach (range(15, 24) as $daysAgo) {
            $d = now()->subDays($daysAgo)->toDateString();
            $this->record($dep, 'present', $d, [], $dropped);
            $this->record($dep, 'present', $d, [], $steady);
        }
        // آخر أسبوعين: الأول 4 غياب، الثاني 4 حضور
        foreach (range(1, 4) as $daysAgo) {
            $d = now()->subDays($daysAgo)->toDateString();
            $this->record($dep, 'absent', $d, [], $dropped);
            $this->record($dep, 'present', $d, [], $steady);
        }

        $this->actAs($dep['head']['user']);
        $changes = $this->getJson('/api/department-head/attendance/insights')->json('data.behavior_changes');

        $this->assertCount(1, $changes);
        $this->assertSame('طالب هبط', $changes[0]['name']);
        $this->assertEquals(100.0, $changes[0]['baseline_rate']);
        $this->assertEquals(0.0, $changes[0]['recent_rate']);
    }

    public function test_flag_summary_is_part_of_the_insights(): void
    {
        $this->flag($this->a, ['severity' => 'high']);
        $this->flag($this->a, ['severity' => 'low', 'rule' => 'ip_outlier', 'status' => 'confirmed']);
        $this->flag($this->b);

        $this->actAs($this->a['head']['user']);
        $flags = $this->getJson('/api/department-head/attendance/insights')->json('data.flags');

        $this->assertSame(2, $flags['total']);
        $this->assertSame(1, $flags['open']);
        $this->assertSame(1, $flags['confirmed']);
        $this->assertSame(['shared_device' => 1, 'ip_outlier' => 1], $flags['by_rule']);
    }

    // ------------------------------------------------------ نطاق رئيس القسم والأدمن

    public function test_head_sees_only_own_department_and_cannot_target_another_course(): void
    {
        foreach (range(1, 3) as $_) {
            $this->record($this->a, 'present', '2026-10-05');
        }
        foreach (range(1, 5) as $_) {
            $this->record($this->b, 'present', '2026-10-05');
        }

        $this->actAs($this->a['head']['user']);
        $this->assertSame(3, $this->getJson('/api/department-head/attendance/insights')->json('data.overall.total'));

        $this->getJson('/api/department-head/attendance/insights?course_id=' . $this->b['course'])->assertForbidden();
        // برنامج قسم آخر لا يوسّع النطاق
        $this->assertSame(0, $this->getJson('/api/department-head/attendance/insights?program_id=' . $this->b['program'])->json('data.overall.total'));
    }

    public function test_head_flag_list_is_read_only_and_scoped(): void
    {
        $mine = $this->flag($this->a);
        $this->flag($this->b);

        $this->actAs($this->a['head']['user']);
        $list = $this->getJson('/api/department-head/attendance/flags')->assertOk();

        $list->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
        $this->putJson("/api/department-head/attendance/flags/{$mine->id}", ['status' => 'dismissed'])->assertStatus(404);
    }

    public function test_admin_sees_everything_and_students_and_teachers_are_refused(): void
    {
        $this->record($this->a, 'present', '2026-10-05');
        $this->record($this->b, 'present', '2026-10-05');

        $this->actAs($this->makeUser('admin'));
        $this->assertSame(2, $this->getJson('/api/admin/attendance/insights')->json('data.overall.total'));

        foreach ([$this->makeStudent()['user'], $this->a['teacher']['user']] as $user) {
            $this->actAs($user);
            $this->getJson('/api/department-head/attendance/insights')->assertForbidden();
            $this->getJson('/api/admin/attendance/insights')->assertForbidden();
        }
    }

    public function test_head_without_a_department_sees_nothing(): void
    {
        $this->record($this->a, 'present', '2026-10-05');
        $orphan = $this->makeUser('head');

        $this->actAs($orphan);

        $this->getJson('/api/department-head/attendance/insights')->assertOk()
            ->assertJsonPath('data.overall.total', 0);
    }
}
