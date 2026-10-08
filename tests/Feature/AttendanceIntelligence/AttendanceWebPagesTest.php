<?php

namespace Tests\Feature\AttendanceIntelligence;

use App\Models\AttendanceFlag;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * صفحات الويب: طابور مراجعة المعلّم، وتحليل الحضور لرئيس القسم.
 */
class AttendanceWebPagesTest extends TestCase
{
    use MakesAcademicData;

    private array $a;
    private array $b;

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

    private function department(string $name): array
    {
        $dept = $this->makeDepartment("Dept $name");
        $program = $this->makeProgram($dept);
        $teacher = $this->makeTeacher();
        $course = $this->makeCourse($program, ['title' => "Course $name", 'year' => 1]);
        $this->assignTeacher($course, $teacher['teacher_id']);

        return ['dept' => $dept, 'program' => $program, 'teacher' => $teacher, 'course' => $course, 'head' => $this->makeHead($dept)];
    }

    private function record(array $dep, string $status, string $date): int
    {
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $dep['course'], 'teacher_id' => $dep['teacher']['teacher_id'], 'title' => 'L' . $this->nextSeq(),
            'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('attendance')->insertGetId([
            'student_id' => $this->makeStudent([], $dep['program'])['student_id'], 'lesson_id' => $lesson,
            'status' => $status, 'attendance_date' => $date, 'excuse_status' => 'none',
            'created_at' => $date . ' 08:10:00', 'updated_at' => now(),
        ]);
    }

    private function flag(array $dep, array $attrs = []): AttendanceFlag
    {
        $att = $this->record($dep, 'present', '2026-10-08');

        return AttendanceFlag::create(array_merge([
            'attendance_id' => $att,
            'student_id' => DB::table('attendance')->where('attendance_id', $att)->value('student_id'),
            'course_id' => $dep['course'], 'rule' => 'shared_device', 'severity' => 'high', 'status' => 'open',
            'details' => ['group_size' => 2, 'device_tail' => 'XYZ789'],
        ], $attrs));
    }

    // -------------------------------------------------------------- المعلّم

    public function test_teacher_page_lists_only_own_flags_with_explanations(): void
    {
        $this->flag($this->a);
        $this->flag($this->b, ['details' => ['group_size' => 2, 'device_tail' => 'OTHER1']]);
        $this->actingAs($this->a['teacher']['user']);

        $this->get('/teacher/attendance-flags')->assertOk()
            ->assertSee('جهاز مشترك')
            ->assertSee('XYZ789')
            ->assertDontSee('OTHER1');
    }

    public function test_teacher_can_dismiss_and_confirm_from_the_page(): void
    {
        $dismiss = $this->flag($this->a);
        $confirm = $this->flag($this->a);
        $this->actingAs($this->a['teacher']['user']);

        $this->post("/teacher/attendance-flags/{$dismiss->id}/review", ['decision' => 'dismissed', 'note' => 'طبيعي'])
            ->assertRedirect()->assertSessionHas('success');
        $this->post("/teacher/attendance-flags/{$confirm->id}/review", ['decision' => 'confirmed', 'mark_absent' => 1])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('dismissed', $dismiss->fresh()->status);
        $this->assertSame('confirmed', $confirm->fresh()->status);
        $this->assertSame('present', DB::table('attendance')->where('attendance_id', $dismiss->attendance_id)->value('status'));
        $this->assertSame('absent', DB::table('attendance')->where('attendance_id', $confirm->attendance_id)->value('status'));
    }

    public function test_teacher_cannot_review_another_teachers_flag_or_twice(): void
    {
        $theirs = $this->flag($this->b);
        $mine = $this->flag($this->a);
        $this->actingAs($this->a['teacher']['user']);

        $this->post("/teacher/attendance-flags/{$theirs->id}/review", ['decision' => 'dismissed'])->assertNotFound();
        $this->assertSame('open', $theirs->fresh()->status);

        $this->post("/teacher/attendance-flags/{$mine->id}/review", ['decision' => 'dismissed']);
        $this->post("/teacher/attendance-flags/{$mine->id}/review", ['decision' => 'confirmed', 'mark_absent' => 1])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame('dismissed', $mine->fresh()->status);
    }

    public function test_review_input_is_validated(): void
    {
        $f = $this->flag($this->a);
        $this->actingAs($this->a['teacher']['user']);

        $this->post("/teacher/attendance-flags/{$f->id}/review", ['decision' => 'whatever'])->assertSessionHasErrors('decision');
    }

    // ------------------------------------------------------------ رئيس القسم

    public function test_head_page_shows_the_heatmap_of_own_department_only(): void
    {
        foreach (range(1, 4) as $_) {
            $this->record($this->a, 'present', '2026-10-05');
        }
        foreach (range(1, 6) as $_) {
            $this->record($this->b, 'absent', '2026-10-05');
        }
        $this->actingAs($this->a['head']['user']);

        $this->get('/hod/attendance-insights')->assertOk()
            ->assertSee('خريطة الحضور')
            ->assertSee('100%')
            ->assertSee('Course A')
            ->assertDontSee('Course B');
    }

    public function test_head_page_works_with_no_data_and_with_filters(): void
    {
        $this->actingAs($this->a['head']['user']);

        $this->get('/hod/attendance-insights')->assertOk()->assertSee('لا توجد بيانات حضور');
        $this->get('/hod/attendance-insights?program_id=' . $this->a['program'] . '&from=2026-09-01&to=2026-10-08')->assertOk();
    }

    public function test_head_cannot_widen_scope_with_another_departments_program(): void
    {
        $this->record($this->b, 'present', '2026-10-05');
        $this->actingAs($this->a['head']['user']);

        $this->get('/hod/attendance-insights?program_id=' . $this->b['program'])->assertOk()
            ->assertSee('لا توجد بيانات حضور')->assertDontSee('Course B');
    }

    public function test_pages_are_closed_to_other_roles(): void
    {
        $this->actingAs($this->makeStudent()['user']);

        $this->get('/teacher/attendance-flags')->assertRedirect();
        $this->get('/hod/attendance-insights')->assertRedirect();
    }

    // ------------------------------------------------ مسار الويب للحضور (جلسة ومسح)

    public function test_ending_a_session_from_the_web_runs_the_analysis(): void
    {
        $dep = $this->a;
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $dep['course'], 'teacher_id' => $dep['teacher']['teacher_id'], 'title' => 'W',
            'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $lesson, 'qr_token' => 'WEB' . $this->nextSeq(),
            'expires_at' => now()->addSeconds(30), 'session_expires_at' => now()->addMinutes(10),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (range(1, 2) as $_) {
            DB::table('attendance')->insert([
                'student_id' => $this->makeStudent([], $dep['program'])['student_id'], 'lesson_id' => $lesson,
                'session_id' => $sessionId, 'status' => 'present', 'attendance_date' => '2026-10-08', 'excuse_status' => 'none',
                'device_id' => 'WEB-SHARED-DEVICE-1', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->actingAs($dep['teacher']['user'])->post("/teacher/attendance/end/{$sessionId}")->assertRedirect();

        $this->assertNotNull(DB::table('attendance_sessions')->where('id', $sessionId)->value('analyzed_at'));
        $this->assertSame(2, AttendanceFlag::where('session_id', $sessionId)->where('rule', 'shared_device')->count());
    }

    public function test_a_scan_made_through_the_web_keeps_the_connection_fingerprint(): void
    {
        $dep = $this->a;
        $student = $this->makeStudent(['academic_year' => 'السنة الأولى'], $dep['program']);
        DB::table('students')->where('student_id', $student['student_id'])->update(['level' => 'السنة الأولى']);
        $this->enroll($student['student_id'], $dep['course']);
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $dep['course'], 'teacher_id' => $dep['teacher']['teacher_id'], 'title' => 'W',
            'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $lesson, 'qr_token' => 'WEBSCAN-TOKEN-0123456789ABCDEFGH',
            'expires_at' => now()->addSeconds(22), 'session_expires_at' => now()->addMinutes(10),
            'is_active' => true, 'created_at' => now()->subSeconds(20), 'updated_at' => now(),
        ]);

        $this->actingAs($student['user'])
            ->withHeaders(['User-Agent' => 'WebBrowserTest/2.0'])
            ->postJson('/student/attendance/scan', ['qr_token' => 'WEBSCAN-TOKEN-0123456789ABCDEFGH'])
            ->assertOk();

        $row = DB::table('attendance')->where('student_id', $student['student_id'])->first();
        $this->assertSame($sessionId, (int) $row->session_id);
        $this->assertSame('WebBrowserTest/2.0', $row->user_agent);
        $this->assertNotEmpty($row->ip_address);
        $this->assertSame(8, (int) $row->qr_age_seconds);
    }
}
