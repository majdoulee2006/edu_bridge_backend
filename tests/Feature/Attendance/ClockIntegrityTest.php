<?php

namespace Tests\Feature\Attendance;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * مقارنة ساعة الهاتف بساعة السيرفر: تصحيح وقت المسح، تنبيه الطالب، وتصعيد رئيس القسم عند التكرار دون رد.
 */
class ClockIntegrityTest extends TestCase
{
    use MakesAcademicData;

    private array $student;
    private array $head;
    private int $dept;
    private int $program;
    private int $courseId;
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        \Illuminate\Support\Facades\Storage::fake('local');
        config(['attendance.require_face' => false]);
        Carbon::setTestNow('2026-10-14 10:00:00');

        $this->dept    = $this->makeDepartment();
        $this->program = $this->makeProgram($this->dept);
        $this->head    = $this->makeHead($this->dept);
        $teacher       = $this->makeTeacher();

        $this->student = $this->makeStudent(['academic_year' => 'السنة الأولى'], $this->program);
        DB::table('students')->where('student_id', $this->student['student_id'])->update(['level' => 'السنة الأولى']);

        $this->courseId = $this->makeCourse($this->program, ['year' => 1]);
        $this->assignTeacher($this->courseId, $teacher['teacher_id']);
        $this->enroll($this->student['student_id'], $this->courseId);
        $this->teacherId = $teacher['teacher_id'];
    }

    private int $teacherId;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** جلسة جديدة بدرس جديد (حتى لا يمنع already_marked المسح الثاني) ورمز مميز. */
    private function makeSession(string $createdAt = '2026-10-14 09:59:55'): array
    {
        $this->seq++;
        $token = sprintf('CLK%02dTOKEN0123456789012345678901', $this->seq);
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $this->courseId, 'teacher_id' => $this->teacherId, 'title' => 'S' . $this->seq,
            'type' => 'session', 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
        $id = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $lesson, 'qr_token' => $token, 'expires_at' => now()->addMinutes(5),
            'session_expires_at' => now()->addMinutes(30), 'is_active' => true,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);

        return ['id' => $id, 'token' => $token, 'lesson' => $lesson];
    }

    private function scan(array $session, array $extra = [], ?array $student = null)
    {
        return $this->actAs(($student ?? $this->student)['user'])
            ->postJson('/api/student/attendance/scan', array_merge(['qr_token' => $session['token']], $extra));
    }

    private function att(int $lessonId): ?object
    {
        return DB::table('attendance')->where('student_id', $this->student['student_id'])->where('lesson_id', $lessonId)->first();
    }

    private function alerts()
    {
        return DB::table('attendance_clock_alerts')->where('student_id', $this->student['student_id']);
    }

    public function test_honest_online_scan_has_no_alert_and_is_high_confidence(): void
    {
        $s = $this->makeSession();

        $this->scan($s, ['scanned_at' => now()->toIso8601String(), 'device_time' => now()->toIso8601String()])->assertOk();

        $row = $this->att($s['lesson']);
        $this->assertSame('present', $row->status);
        $this->assertSame('online', $row->source);
        $this->assertSame('high', $row->time_confidence);
        $this->assertSame(0, $this->alerts()->count());
    }

    public function test_honest_offline_scan_synced_later_is_not_flagged(): void
    {
        $s = $this->makeSession('2026-10-14 09:40:00');
        DB::table('attendance_sessions')->where('id', $s['id'])->update(['session_expires_at' => '2026-10-14 10:30:00']);

        // مسح قبل 10 دقائق بلا إنترنت، والهاتف يرسله الآن بساعة سليمة
        $this->scan($s, ['scanned_at' => now()->subMinutes(10)->toIso8601String(), 'device_time' => now()->toIso8601String()])->assertOk();

        $row = $this->att($s['lesson']);
        $this->assertSame('offline', $row->source);
        $this->assertSame('high', $row->time_confidence);
        $this->assertSame(0, $this->alerts()->count());
    }

    public function test_phone_clock_set_ahead_is_corrected_flagged_and_the_student_is_alerted(): void
    {
        $s = $this->makeSession();

        // الهاتف متقدّم 20 دقيقة، ويرسل وقتًا متقدمًا كأنه مسح الآن
        $phone = now()->addMinutes(20)->toIso8601String();
        $this->scan($s, ['scanned_at' => $phone, 'device_time' => $phone])->assertOk();

        $row = $this->att($s['lesson']);
        $this->assertSame('low', $row->time_confidence);
        $this->assertSame('2026-10-14', substr($row->attendance_date, 0, 10));

        $alert = $this->alerts()->first();
        $this->assertSame('clock_skew', $alert->kind);
        $this->assertEqualsWithDelta(1200, $alert->skew_seconds, 2);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->student['user']->user_id, 'type' => 'clock_alert', 'related_id' => $alert->id,
        ]);
    }

    public function test_a_set_back_clock_can_no_longer_replay_an_old_qr_window(): void
    {
        // رمز قديم صالح فقط في نافذة سابقة: الجلسة بدأت قبل ساعة وتجدد رمزها، والرمز القديم استُبدل منذ زمن
        $s = $this->makeSession('2026-10-14 08:00:00');
        DB::table('attendance_sessions')->where('id', $s['id'])->update(['session_expires_at' => '2026-10-14 10:30:00']);
        $old = 'OLDTOKEN0123456789012345678901234';
        DB::table('attendance_session_tokens')->insert([
            ['session_id' => $s['id'], 'token' => $old, 'issued_at' => '2026-10-14 08:00:00', 'replaced_at' => '2026-10-14 08:00:30', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // الطالب أرجع ساعة هاتفه إلى 08:00:10 ليبدو أنه مسح الرمز القديم في وقته
        $fake = Carbon::parse('2026-10-14 08:00:10')->toIso8601String();
        $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', ['qr_token' => $old, 'scanned_at' => $fake, 'device_time' => $fake])
            ->assertStatus(400)
            ->assertJson(['reject_reason' => 'expired_qr']);

        $this->assertSame(0, DB::table('attendance')->count());
        // ... ورُصد الانحراف (الجلسة معروفة) فنُبّه الطالب
        $this->assertSame(1, $this->alerts()->count());
    }

    public function test_scan_time_after_the_send_time_is_flagged_and_replaced_by_server_time(): void
    {
        $s = $this->makeSession();

        // الهاتف نفسه يقول إن المسح بعد لحظة الإرسال بـ 5 دقائق: مستحيل. نقبل بوقت السيرفر مع وسم وتنبيه.
        $this->scan($s, ['scanned_at' => now()->addMinutes(5)->toIso8601String(), 'device_time' => now()->toIso8601String()])->assertOk();

        $this->assertSame('scan_after_send', $this->alerts()->where('session_id', $s['id'])->value('kind'));
        $this->assertSame('low', $this->att($s['lesson'])->time_confidence);

        // فرق صغير ضمن التسامح لا يُنبَّه عليه
        $s2 = $this->makeSession();
        $this->scan($s2, ['scanned_at' => now()->addSeconds(60)->toIso8601String(), 'device_time' => now()->toIso8601String()])->assertOk();
        $this->assertSame(0, $this->alerts()->where('session_id', $s2['id'])->count());
    }

    public function test_garbage_tokens_never_create_alerts(): void
    {
        $phone = now()->addMinutes(20)->toIso8601String();

        $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', ['qr_token' => 'RANDOMGARBAGE0123456789', 'scanned_at' => $phone, 'device_time' => $phone])
            ->assertStatus(400);

        $this->assertSame(0, $this->alerts()->count());
    }

    public function test_old_apps_without_device_time_still_work_and_never_claim_high_for_deferred_scans(): void
    {
        $s = $this->makeSession('2026-10-14 09:40:00');
        DB::table('attendance_sessions')->where('id', $s['id'])->update(['session_expires_at' => '2026-10-14 10:30:00']);

        $this->scan($s, ['scanned_at' => now()->subMinutes(10)->toIso8601String()])->assertOk();

        $this->assertSame('medium', $this->att($s['lesson'])->time_confidence);
        $this->assertSame(0, $this->alerts()->count());
    }

    public function test_client_cannot_claim_its_own_time_confidence(): void
    {
        $s = $this->makeSession();
        $phone = now()->addMinutes(20)->toIso8601String();

        $this->scan($s, ['scanned_at' => $phone, 'device_time' => $phone, 'time_confidence' => 'high'])->assertOk();

        $this->assertSame('low', $this->att($s['lesson'])->time_confidence);
    }

    public function test_one_alert_per_session_even_if_the_scan_is_retried(): void
    {
        $s = $this->makeSession();
        $phone = now()->addMinutes(20)->toIso8601String();

        $this->scan($s, ['scanned_at' => $phone, 'device_time' => $phone, 'client_scan_id' => 'abc'])->assertOk();
        $this->scan($s, ['scanned_at' => $phone, 'device_time' => $phone])->assertStatus(409);

        $this->assertSame(1, $this->alerts()->count());
        $this->assertSame(1, DB::table('notifications')->where('type', 'clock_alert')->count());
    }

    // ───────────────────────── التصعيد لرئيس القسم ─────────────────────────

    private function tamperOn(array $session): void
    {
        $phone = now()->addMinutes(20)->toIso8601String();
        $this->scan($session, ['scanned_at' => $phone, 'device_time' => $phone])->assertOk();
    }

    private function headAlerts(): int
    {
        return DB::table('notifications')->where('user_id', $this->head['user']->user_id)->where('type', 'clock_alert_escalation')->count();
    }

    public function test_second_unanswered_alert_escalates_to_the_head_once(): void
    {
        $this->tamperOn($this->makeSession());
        $this->assertSame(0, $this->headAlerts(), 'تنبيه واحد لا يكفي');

        $this->tamperOn($this->makeSession());
        $this->assertSame(1, $this->headAlerts());
        $this->assertSame(2, $this->alerts()->whereNotNull('escalated_at')->count());

        $this->tamperOn($this->makeSession()); // ثالث: تنبيه جديد وحده، فلا يصعَّد حتى يتكرر
        $this->assertSame(1, $this->headAlerts());
    }

    public function test_acknowledged_alerts_do_not_escalate(): void
    {
        $this->tamperOn($this->makeSession());
        $first = $this->alerts()->first();

        $this->actAs($this->student['user'])
            ->postJson("/api/student/clock-alerts/{$first->id}/acknowledge", ['note' => 'صحّحت الساعة'])
            ->assertOk();

        $this->tamperOn($this->makeSession());

        $this->assertSame(0, $this->headAlerts(), 'الأول أُجيب عليه فلا يُحتسب');
        $this->assertNotNull(DB::table('attendance_clock_alerts')->where('id', $first->id)->value('acknowledged_at'));
    }

    public function test_old_alerts_outside_the_window_do_not_count(): void
    {
        $this->tamperOn($this->makeSession());

        Carbon::setTestNow('2026-11-10 10:00:00'); // بعد أكثر من 14 يومًا
        $s = $this->makeSession('2026-11-10 09:59:55');
        $this->tamperOn($s);

        $this->assertSame(0, $this->headAlerts());
    }

    public function test_escalation_is_not_lost_when_the_department_has_no_head_yet(): void
    {
        DB::table('heads')->delete();

        $this->tamperOn($this->makeSession());
        $this->tamperOn($this->makeSession());

        $this->assertSame(0, $this->alerts()->whereNotNull('escalated_at')->count(), 'لا تُعلَّم مُصعَّدة ما دام لا مستلم');
    }

    public function test_head_of_another_department_is_not_notified(): void
    {
        $otherHead = $this->makeHead($this->makeDepartment());

        $this->tamperOn($this->makeSession());
        $this->tamperOn($this->makeSession());

        $this->assertSame(1, $this->headAlerts());
        $this->assertSame(0, DB::table('notifications')->where('user_id', $otherHead['user']->user_id)->count());
    }

    public function test_student_can_list_and_only_acknowledge_their_own_alerts(): void
    {
        $this->tamperOn($this->makeSession());
        $alert = $this->alerts()->first();

        $list = $this->actAs($this->student['user'])->getJson('/api/student/clock-alerts')->assertOk()->json('data');
        $this->assertCount(1, $list);
        $this->assertNull($list[0]['acknowledged_at']);

        $other = $this->makeStudent(['academic_year' => 'السنة الأولى'], $this->program);
        $this->actAs($other['user'])->postJson("/api/student/clock-alerts/{$alert->id}/acknowledge")->assertNotFound();
        $this->assertNull(DB::table('attendance_clock_alerts')->where('id', $alert->id)->value('acknowledged_at'));
    }
}
