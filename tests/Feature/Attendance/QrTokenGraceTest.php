<?php

namespace Tests\Feature\Attendance;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * الـ QR يتجدد كل 30 ثانية، وخطوة الوجه عند الطالب (رمشة + استقامة) تأخذ وقتًا بعد قراءته.
 * الرمز المستبدَل يبقى صالحًا مهلة قصيرة (attendance.qr_grace_seconds) ثم يُرفض.
 */
class QrTokenGraceTest extends TestCase
{
    use MakesAcademicData;

    private array $teacher;
    private array $student;
    private int $sessionId;
    private string $firstToken;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Carbon::setTestNow('2026-10-08 09:00:00');
        config(['attendance.require_face' => false, 'attendance.qr_grace_seconds' => 90]);

        $program = $this->makeProgram($this->makeDepartment());
        $this->teacher = $this->makeTeacher();
        $this->student = $this->makeStudent(['academic_year' => 'السنة الأولى'], $program);
        DB::table('students')->where('student_id', $this->student['student_id'])->update(['level' => 'السنة الأولى']);

        $course = $this->makeCourse($program, ['year' => 1]);
        $this->assignTeacher($course, $this->teacher['teacher_id']);
        $this->enroll($this->student['student_id'], $course);

        // الجلسة تُفتح عبر API المعلّم كما في الواقع
        $res = $this->actAs($this->teacher['user'])->postJson('/api/teacher/attendance/generate-qr', ['course_id' => $course])->assertOk();
        $this->sessionId = $res->json('data.session_id');
        $this->firstToken = $res->json('data.qr_token');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function refresh(): string
    {
        return $this->actAs($this->teacher['user'])
            ->postJson("/api/teacher/attendance/session/{$this->sessionId}/refresh-qr")->assertOk()->json('data.qr_token');
    }

    private function scan(string $token)
    {
        return $this->actAs($this->student['user'])->postJson('/api/student/attendance/scan', ['qr_token' => $token]);
    }

    public function test_the_current_token_is_accepted(): void
    {
        $this->scan($this->firstToken)->assertOk()->assertJson(['success' => true]);
    }

    public function test_a_just_replaced_token_is_accepted_within_the_grace_period(): void
    {
        Carbon::setTestNow(now()->addSeconds(25));
        $this->refresh();                                  // الرمز الأول استُبدل الآن
        Carbon::setTestNow(now()->addSeconds(30));
        $this->refresh();                                  // صفحة المعلّم تواصل التجديد كل 30 ثانية
        Carbon::setTestNow(now()->addSeconds(10));         // الطالب أنهى خطوة الوجه، والرمز الأول استُبدل قبل 40 ثانية

        $this->scan($this->firstToken)->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendance', ['student_id' => $this->student['student_id'], 'status' => 'present']);
    }

    public function test_a_token_replaced_long_ago_is_rejected(): void
    {
        Carbon::setTestNow(now()->addSeconds(25));
        $this->refresh();
        foreach ([30, 30, 30] as $step) {                  // صفحة المعلّم تجدّد بانتظام
            Carbon::setTestNow(now()->addSeconds($step));
            $this->refresh();
        }
        Carbon::setTestNow(now()->addSeconds(10));         // الرمز الأول استُبدل قبل 100 ثانية: بعد مهلة السماح (90ث)

        $this->scan($this->firstToken)->assertStatus(400)->assertJson(['reject_reason' => 'expired_qr']);

        $this->assertSame(0, DB::table('attendance')->count());
    }

    public function test_grace_can_be_disabled(): void
    {
        config(['attendance.qr_grace_seconds' => 0]);
        Carbon::setTestNow(now()->addSeconds(25));
        $this->refresh();
        Carbon::setTestNow(now()->addSeconds(5));

        $this->scan($this->firstToken)->assertStatus(400)->assertJson(['reject_reason' => 'expired_qr']);
    }

    public function test_an_older_token_survives_several_rotations_inside_the_grace_period(): void
    {
        $tokens = [$this->firstToken];
        foreach (range(1, 3) as $_) {                       // ثلاثة تدويرات خلال 75 ثانية
            Carbon::setTestNow(now()->addSeconds(25));
            $tokens[] = $this->refresh();
        }

        // الرمز الأول استُبدل قبل 75 ثانية فقط، فما زال ضمن السماح
        $this->scan($tokens[0])->assertOk();
    }

    public function test_unknown_and_malformed_tokens_are_rejected(): void
    {
        $this->scan(str_repeat('A', 32))->assertStatus(400)->assertJson(['reject_reason' => 'expired_qr']);
        $this->scan("x%' OR '1'='1")->assertStatus(400);
        $this->scan('short')->assertStatus(400);
    }

    public function test_history_is_kept_per_session_and_capped(): void
    {
        foreach (range(1, 9) as $_) {
            Carbon::setTestNow(now()->addSeconds(10));
            $this->refresh();
        }

        $history = json_decode(DB::table('attendance_sessions')->where('id', $this->sessionId)->value('previous_tokens'), true);

        $this->assertCount(6, $history);
        $this->assertArrayHasKey('replaced_at', $history[0]);
    }

    public function test_web_refresh_also_keeps_the_previous_token(): void
    {
        Carbon::setTestNow(now()->addSeconds(25));
        $this->actingAs($this->teacher['user'])->getJson("/teacher/attendance/refresh/{$this->sessionId}")->assertOk();
        Carbon::setTestNow(now()->addSeconds(30));

        $this->scan($this->firstToken)->assertOk();
    }

    public function test_a_token_of_another_session_does_not_leak_into_this_one(): void
    {
        $other = $this->actAs($this->teacher['user'])->postJson('/api/teacher/attendance/generate-qr', [
            'course_id' => DB::table('course_teachers')->where('teacher_id', $this->teacher['teacher_id'])->value('course_id'),
        ])->json('data');

        $this->scan($other['qr_token'])->assertOk();

        $this->assertSame($other['session_id'], (int) DB::table('attendance')->where('student_id', $this->student['student_id'])->value('session_id'));
    }
}
