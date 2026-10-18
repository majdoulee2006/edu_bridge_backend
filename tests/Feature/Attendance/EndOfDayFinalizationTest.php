<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * إغلاق الحضور عند 12 ليلاً: إنهاء الجلسة لا يسجّل غيابًا فورًا، من لم يمسح "قيد المزامنة" حتى منتصف الليل،
 * والإنهاء النهائي المجدول هو من يسجّل الغياب (حتى لو نسي المعلم)، وبعده زر الاستثناء بسبب مكتوب.
 */
class EndOfDayFinalizationTest extends TestCase
{
    use MakesAcademicData;

    private array $teacher;
    private int $program;
    private int $courseId;
    private int $lessonId;
    private int $sessionId;
    private string $token = 'EODTOKEN0123456789012345678901234';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        \Illuminate\Support\Facades\Storage::fake('local');
        config(['attendance.require_face' => false]);
        Carbon::setTestNow('2026-10-14 10:00:00');

        $dept          = $this->makeDepartment();
        $this->program = $this->makeProgram($dept);
        $this->teacher = $this->makeTeacher();
        $this->courseId = $this->makeCourse($this->program, ['year' => 1]);
        $this->assignTeacher($this->courseId, $this->teacher['teacher_id']);

        $this->lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $this->courseId, 'teacher_id' => $this->teacher['teacher_id'],
            'title' => 'S', 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $this->lessonId, 'qr_token' => $this->token,
            'expires_at' => now()->addMinutes(5), 'session_expires_at' => now()->addMinutes(10),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function student(): array
    {
        $s = $this->makeStudent(['academic_year' => 'السنة الأولى'], $this->program);
        DB::table('students')->where('student_id', $s['student_id'])->update(['level' => 'السنة الأولى']);
        $this->enroll($s['student_id'], $this->courseId);

        return $s;
    }

    private function scan(array $student)
    {
        return $this->actAs($student['user'])->postJson('/api/student/attendance/scan', ['qr_token' => $this->token]);
    }

    private function endByTeacher(array $extra = [])
    {
        return $this->actAs($this->teacher['user'])
            ->postJson("/api/teacher/attendance/session/{$this->sessionId}/end", $extra);
    }

    private function runFinalizeAt(string $when): void
    {
        Carbon::setTestNow($when);
        Artisan::call('attendance:finalize-sessions');
    }

    private function attStatus(array $student): ?string
    {
        return DB::table('attendance')->where('student_id', $student['student_id'])->value('status');
    }

    public function test_ending_the_session_does_not_record_absence_immediately(): void
    {
        $present = $this->student();
        $missing = $this->student();
        $this->scan($present)->assertOk();

        $res = $this->endByTeacher()->assertOk()->assertJson(['status' => 'pending_sync']);

        $this->assertSame('present', $this->attStatus($present));
        $this->assertNull($this->attStatus($missing), 'من لم يمسح لا يُسجَّل غائبًا قبل منتصف الليل');
        $this->assertStringStartsWith('2026-10-14T23:59:59', $res->json('sync_deadline_at'));
        $this->assertNull(AttendanceSession::find($this->sessionId)->finalized_at);
    }

    public function test_midnight_job_records_absence_and_keeps_present(): void
    {
        $present = $this->student();
        $missing = $this->student();
        $this->scan($present)->assertOk();
        $this->endByTeacher()->assertOk();

        $this->runFinalizeAt('2026-10-14 23:30:00');
        $this->assertNull($this->attStatus($missing), 'قبل 12 ليلاً لا شيء يتغير');

        $this->runFinalizeAt('2026-10-15 00:05:00');

        $this->assertSame('absent', $this->attStatus($missing));
        $this->assertSame('present', $this->attStatus($present));
        $this->assertNotNull(AttendanceSession::find($this->sessionId)->finalized_at);
    }

    public function test_a_session_the_teacher_forgot_to_end_is_still_finalized(): void
    {
        $missing = $this->student();

        $this->runFinalizeAt('2026-10-15 00:05:00'); // المعلم لم يستدعِ "إنهاء" أبدًا

        $this->assertSame('absent', $this->attStatus($missing));
        $this->assertNotNull(AttendanceSession::find($this->sessionId)->finalized_at);
    }

    public function test_finalizing_twice_does_not_duplicate_absences(): void
    {
        $missing = $this->student();
        $this->endByTeacher()->assertOk();

        $this->runFinalizeAt('2026-10-15 00:05:00');
        $this->runFinalizeAt('2026-10-15 00:15:00');

        $this->assertSame(1, DB::table('attendance')->where('student_id', $missing['student_id'])->count());
    }

    public function test_late_scan_before_midnight_is_accepted_after_the_teacher_ended(): void
    {
        $late = $this->student();
        $this->endByTeacher()->assertOk();

        // الطالب عاد له الإنترنت المساء وزامن مسحه (الرمز الحالي ما زال هو رمز الجلسة المحفوظ)
        Carbon::setTestNow('2026-10-14 22:00:00');
        DB::table('attendance_sessions')->where('id', $this->sessionId)->update(['session_expires_at' => '2026-10-14 22:30:00']);

        $this->scan($late)->assertOk()->assertJson(['success' => true]);
        $this->assertSame('present', $this->attStatus($late));

        $this->runFinalizeAt('2026-10-15 00:05:00');
        $this->assertSame('present', $this->attStatus($late), 'الإنهاء النهائي لا يقلب الحضور غيابًا');
    }

    public function test_scan_after_midnight_is_rejected_with_a_clear_reason(): void
    {
        $late = $this->student();
        $this->endByTeacher()->assertOk();

        Carbon::setTestNow('2026-10-15 00:30:00');
        DB::table('attendance_sessions')->where('id', $this->sessionId)->update(['session_expires_at' => '2026-10-15 09:00:00']);

        $this->scan($late)->assertStatus(400)->assertJson(['reject_reason' => 'sync_window_closed']);
        $this->assertNull($this->attStatus($late));
    }

    public function test_a_finalized_session_rejects_scans_even_before_its_deadline(): void
    {
        $late = $this->student();
        $this->endByTeacher(['immediate' => true])->assertOk()->assertJson(['status' => 'finalized']);

        $this->assertSame('absent', $this->attStatus($late));
        $this->scan($late)->assertStatus(400)->assertJson(['reject_reason' => 'sync_window_closed']);
    }

    public function test_ending_twice_does_not_extend_the_deadline(): void
    {
        $this->endByTeacher()->assertOk();
        $first = AttendanceSession::find($this->sessionId)->sync_deadline_at;

        Carbon::setTestNow('2026-10-14 20:00:00');
        $this->endByTeacher()->assertOk();

        $this->assertTrue($first->equalTo(AttendanceSession::find($this->sessionId)->sync_deadline_at));
    }

    public function test_session_list_shows_absent_while_active_then_pending_then_absent(): void
    {
        $missing = $this->student();

        $list = fn () => collect($this->actAs($this->teacher['user'])
            ->getJson("/api/teacher/attendance/session/{$this->sessionId}/list")
            ->assertOk()->json('data.students'))->firstWhere('student_id', $missing['student_id'])['status'];

        $this->assertSame('absent', $list(), 'أثناء الجلسة النشطة: لم يحضر بعد (سلوك التطبيق الحالي)');

        $this->endByTeacher()->assertOk();
        $this->assertSame('pending', $list(), 'بعد الإغلاق وقبل 12 ليلاً: قيد المزامنة');

        $this->runFinalizeAt('2026-10-15 00:05:00');
        $this->assertSame('absent', $list());
    }

    public function test_approved_leave_is_excused_when_finalized(): void
    {
        $onLeave = $this->student();
        DB::table('leave_requests')->insert([
            'student_id' => $onLeave['user']->user_id, 'type' => 'full_day', 'date' => '2026-10-14',
            'reason' => 'x', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->runFinalizeAt('2026-10-15 00:05:00');

        $this->assertSame('approved', DB::table('attendance')->where('student_id', $onLeave['student_id'])->value('excuse_status'));
    }

    public function test_finalizing_triggers_the_absence_warning_check_for_each_absent_student(): void
    {
        // Attendance::create (وليس insert) كي يعمل الـ Observer فتُفحص إنذارات الغياب. نسخة الويب القديمة كانت تتجاوزه.
        $a = $this->student();
        $b = $this->student();
        $present = $this->student();
        $this->scan($present)->assertOk();

        $checked = [];
        $this->mock(\App\Services\AbsenceWarningService::class, function ($m) use (&$checked) {
            $m->shouldReceive('checkAndWarn')->andReturnUsing(function ($id) use (&$checked) {
                $checked[] = (int) $id;
            });
        });

        $this->runFinalizeAt('2026-10-15 00:05:00');

        sort($checked);
        $expected = [$a['student_id'], $b['student_id']];
        sort($expected);
        $this->assertSame($expected, $checked);
    }

    public function test_web_end_uses_the_same_deferred_flow(): void
    {
        $missing = $this->student();

        $this->actingAs($this->teacher['user'])->post("/teacher/attendance/end/{$this->sessionId}")->assertRedirect();
        $this->assertNull($this->attStatus($missing), 'الويب أيضًا لا يسجّل الغياب قبل 12 ليلاً');

        $this->runFinalizeAt('2026-10-15 00:05:00');
        $this->assertSame('absent', $this->attStatus($missing));
    }

    // ───────────────────────── زر الاستثناء ─────────────────────────

    private function exception(array $payload, ?array $as = null, ?int $sessionId = null)
    {
        return $this->actAs(($as ?? $this->teacher)['user'])
            ->postJson('/api/teacher/attendance/session/' . ($sessionId ?? $this->sessionId) . '/exception', $payload);
    }

    public function test_teacher_exception_turns_a_finalized_absence_into_present_with_reason(): void
    {
        $dead = $this->student();
        $this->runFinalizeAt('2026-10-15 00:05:00');
        $this->assertSame('absent', $this->attStatus($dead));

        $this->exception(['student_id' => $dead['student_id'], 'reason' => 'هاتفه تعطل وأكدت حضوره'])
            ->assertOk()->assertJson(['success' => true, 'data' => ['status' => 'present']]);

        $row = DB::table('attendance')->where('student_id', $dead['student_id'])->first();
        $this->assertSame('present', $row->status);
        $this->assertSame('manual_exception', $row->source);
        $this->assertSame('هاتفه تعطل وأكدت حضوره', $row->exception_reason);
        $this->assertSame($this->teacher['user']->user_id, (int) $row->exception_by);
        $this->assertSame(1, DB::table('attendance')->where('student_id', $dead['student_id'])->count(), 'لا سجل مكرر');
        $this->assertDatabaseHas('notifications', ['user_id' => $dead['user']->user_id, 'type' => 'attendance_exception']);
    }

    public function test_exception_works_before_finalization_too(): void
    {
        $s = $this->student();

        $this->exception(['student_id' => $s['student_id'], 'reason' => 'مسح ورقياً مع المعلم'])->assertOk();

        $this->runFinalizeAt('2026-10-15 00:05:00');
        $this->assertSame('present', $this->attStatus($s), 'الإنهاء النهائي لا يلغي الاستثناء');
    }

    public function test_exception_requires_a_written_reason(): void
    {
        $s = $this->student();

        $this->exception(['student_id' => $s['student_id']])->assertStatus(422);
        $this->exception(['student_id' => $s['student_id'], 'reason' => 'ab'])->assertStatus(422);
        $this->assertNull($this->attStatus($s));
    }

    public function test_exception_is_limited_to_the_owner_teacher_and_enrolled_students(): void
    {
        $s = $this->student();
        $other = $this->makeTeacher();
        $outsider = $this->makeStudent(['academic_year' => 'السنة الأولى'], $this->program); // غير مسجّل بالمقرر

        $this->exception(['student_id' => $s['student_id'], 'reason' => 'سبب مكتوب'], $other)->assertNotFound();
        $this->exception(['student_id' => $outsider['student_id'], 'reason' => 'سبب مكتوب'])->assertStatus(422);
        $this->assertSame(0, DB::table('attendance')->count());
    }

    public function test_exception_expires_after_the_configured_days(): void
    {
        $s = $this->student();
        config(['attendance.exception_days' => 7]);

        Carbon::setTestNow('2026-10-22 10:00:00'); // اليوم الثامن
        $this->exception(['student_id' => $s['student_id'], 'reason' => 'سبب مكتوب'])->assertStatus(422);

        Carbon::setTestNow('2026-10-21 10:00:00'); // اليوم السابع: ما زال مسموحًا
        $this->exception(['student_id' => $s['student_id'], 'reason' => 'سبب مكتوب'])->assertOk();
    }

    public function test_students_and_parents_cannot_use_the_exception_button(): void
    {
        $s = $this->student();

        $this->actAs($s['user'])
            ->postJson("/api/teacher/attendance/session/{$this->sessionId}/exception", ['student_id' => $s['student_id'], 'reason' => 'أريد حضورًا'])
            ->assertStatus(403);
    }
}
