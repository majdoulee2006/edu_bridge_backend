<?php

namespace Tests\Feature\Attendance;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * ما يراه المعلم والطالب ورئيس القسم في الويب هو نفسه في التطبيق: نفس قائمة الجلسة (present/absent/pending)،
 * نفس زر الاستثناء وقواعده، نفس تأكيد تنبيه الساعة، ونفس روابط الإشعارات.
 */
class WebAppParityTest extends TestCase
{
    use MakesAcademicData;

    private array $teacher;
    private array $student;
    private array $head;
    private int $program;
    private int $courseId;
    private int $sessionId;
    private int $lessonId;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Carbon::setTestNow('2026-10-14 10:00:00');

        $dept          = $this->makeDepartment();
        $this->program = $this->makeProgram($dept);
        $this->head    = $this->makeHead($dept);
        $this->teacher = $this->makeTeacher();
        $this->courseId = $this->makeCourse($this->program, ['year' => 1]);
        $this->assignTeacher($this->courseId, $this->teacher['teacher_id']);

        $this->student = $this->makeStudent(['academic_year' => 'السنة الأولى', 'status' => 'active'], $this->program);
        DB::table('students')->where('student_id', $this->student['student_id'])->update(['level' => 'السنة الأولى']);
        $this->enroll($this->student['student_id'], $this->courseId);

        $this->lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $this->courseId, 'teacher_id' => $this->teacher['teacher_id'], 'title' => 'S',
            'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $this->lessonId, 'qr_token' => 'PARITYTOKEN0123456789012345678901', 'is_active' => true,
            'expires_at' => now()->addMinutes(5), 'session_expires_at' => now()->addMinutes(10),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function webList(): array
    {
        return collect($this->actingAs($this->teacher['user'])->getJson("/teacher/attendance/absentees/{$this->sessionId}")->assertOk()->json())
            ->pluck('status', 'student_id')->all();
    }

    private function apiList(): array
    {
        return collect($this->actAs($this->teacher['user'])->getJson("/api/teacher/attendance/session/{$this->sessionId}/list")->assertOk()->json('data.students'))
            ->pluck('status', 'student_id')->all();
    }

    private function endSession(): void
    {
        $this->actAs($this->teacher['user'])->postJson("/api/teacher/attendance/session/{$this->sessionId}/end")->assertOk();
    }

    public function test_web_and_app_lists_agree_in_every_phase_of_the_session(): void
    {
        $sid = $this->student['student_id'];

        $this->assertSame('absent', $this->webList()[$sid], 'أثناء الجلسة: لم يمسح بعد');
        $this->assertSame($this->webList(), $this->apiList());

        $this->endSession();
        $this->assertSame('pending', $this->webList()[$sid], 'بعد الإغلاق وقبل 12 ليلاً');
        $this->assertSame($this->webList(), $this->apiList());

        Carbon::setTestNow('2026-10-15 00:05:00');
        \Illuminate\Support\Facades\Artisan::call('attendance:finalize-sessions');
        $this->assertSame('absent', $this->webList()[$sid]);
        $this->assertSame($this->webList(), $this->apiList());
    }

    public function test_web_exception_matches_the_app_rules(): void
    {
        $sid = $this->student['student_id'];
        $post = fn (array $d, $as = null) => $this->actingAs($as ?? $this->teacher['user'])->postJson("/teacher/attendance/exception/{$this->sessionId}", $d);

        $post(['student_id' => $sid])->assertStatus(422);                                   // بلا سبب
        $post(['student_id' => $sid, 'reason' => 'ab'])->assertStatus(422);                 // سبب قصير
        $post(['student_id' => $sid, 'reason' => 'سبب كافٍ'], $this->makeTeacher()['user'])->assertNotFound(); // معلم آخر

        $outsider = $this->makeStudent(['academic_year' => 'السنة الأولى'], $this->program);
        $post(['student_id' => $outsider['student_id'], 'reason' => 'سبب كافٍ'])->assertStatus(422);

        $post(['student_id' => $sid, 'reason' => 'هاتفه تعطل وأكدت حضوره'])->assertOk()->assertJson(['success' => true]);

        $row = DB::table('attendance')->where('student_id', $sid)->first();
        $this->assertSame('present', $row->status);
        $this->assertSame('manual_exception', $row->source);
        $this->assertSame('present', $this->webList()[$sid]);
        $this->assertSame('present', $this->apiList()[$sid], 'ويظهر حاضرًا في التطبيق أيضًا');
    }

    public function test_web_exception_records_the_actor_and_notifies_the_student(): void
    {
        $this->actingAs($this->teacher['user'])
            ->postJson("/teacher/attendance/exception/{$this->sessionId}", ['student_id' => $this->student['student_id'], 'reason' => 'سبب كافٍ'])
            ->assertOk();

        $this->assertSame($this->teacher['user']->user_id, (int) DB::table('attendance')->value('exception_by'));
        $this->assertDatabaseHas('notifications', ['user_id' => $this->student['user']->user_id, 'type' => 'attendance_exception']);
    }

    public function test_the_teacher_page_ships_the_exception_button_and_pending_state(): void
    {
        $html = $this->actingAs($this->teacher['user'])->get('/teacher/attendance')->assertOk()->getContent();

        $this->assertStringContainsString('recordException', $html);
        $this->assertStringContainsString('قيد المزامنة', $html);
    }

    // ───────────────────────────── تأكيد تنبيه الساعة على الويب ─────────────────────────────

    private function alert(?array $student = null): int
    {
        $student ??= $this->student;
        $id = DB::table('attendance_clock_alerts')->insertGetId([
            'student_id' => $student['student_id'], 'session_id' => $this->sessionId, 'kind' => 'clock_skew', 'skew_seconds' => 1200,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('notifications')->insert([
            'user_id' => $student['user']->user_id, 'title' => '⏰ تنبيه: ساعة هاتفك غير مضبوطة', 'message' => 'نص', 'type' => 'clock_alert',
            'category' => 'academic', 'related_id' => $id, 'is_read' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_student_sees_a_confirm_button_then_the_confirmed_state(): void
    {
        $id = $this->alert();

        $this->actingAs($this->student['user'])->get('/student/notifications')->assertOk()
            ->assertSee('تأكيد: صحّحت ساعة الهاتف')
            ->assertSee(route('student.clock_alerts.ack', $id), false);

        $this->actingAs($this->student['user'])->post(route('student.clock_alerts.ack', $id))->assertRedirect();

        $this->assertNotNull(DB::table('attendance_clock_alerts')->where('id', $id)->value('acknowledged_at'));
        $this->assertSame(1, (int) DB::table('notifications')->where('related_id', $id)->value('is_read'));

        $this->actingAs($this->student['user'])->get('/student/notifications')->assertOk()
            ->assertSee('تم تأكيدك')->assertDontSee('تأكيد: صحّحت ساعة الهاتف');
    }

    public function test_web_confirmation_stops_the_escalation_like_the_app(): void
    {
        $first = $this->alert();
        $this->actingAs($this->student['user'])->post(route('student.clock_alerts.ack', $first))->assertRedirect();

        // تنبيه ثانٍ بعد التأكيد على الأول: لا يكفي لتصعيد رئيس القسم (الأول مُجاب عليه)
        $session2 = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $this->lessonId, 'qr_token' => 'PARITYTOKEN2345678901234567890123', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = \App\Models\Student::find($this->student['student_id']);
        \App\Services\ClockAlertService::record($student, \App\Models\AttendanceSession::find($session2), ['kind' => 'clock_skew', 'skew' => 900]);

        $this->assertSame(0, DB::table('notifications')->where('user_id', $this->head['user']->user_id)->where('type', 'clock_alert_escalation')->count());
    }

    public function test_a_student_cannot_confirm_someone_elses_alert(): void
    {
        $id = $this->alert();
        $other = $this->makeStudent(['academic_year' => 'السنة الأولى'], $this->program);

        $this->actingAs($other['user'])->post(route('student.clock_alerts.ack', $id))->assertNotFound();
        $this->assertNull(DB::table('attendance_clock_alerts')->where('id', $id)->value('acknowledged_at'));
    }

    public function test_head_notification_for_an_escalation_links_to_the_dashboard(): void
    {
        DB::table('notifications')->insert([
            'user_id' => $this->head['user']->user_id, 'title' => 'طالب يتكرر منه التلاعب', 'message' => 'نص', 'type' => 'clock_alert_escalation',
            'category' => 'administrative', 'related_id' => $this->student['student_id'], 'is_read' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->head['user'])->get('/hod/notifications')->assertOk()->assertSee('/hod/follow-up', false);
    }
}
