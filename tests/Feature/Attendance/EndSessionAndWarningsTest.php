<?php

namespace Tests\Feature\Attendance;

use App\Services\AbsenceWarningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * B-08: إنهاء الجلسة لا يغيّر المتأخر إلى غائب ويراعي الإجازات المعتمدة.
 * B-07 (جزئياً): الغياب المعذور لا يدخل في حدود الإنذارات.
 */
class EndSessionAndWarningsTest extends TestCase
{
    use MakesAcademicData;

    private array $teacher;
    private int $courseId;
    private int $lessonId;
    private int $sessionId;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();

        $this->teacher  = $this->makeTeacher();
        $this->courseId = $this->makeCourse();
        $this->assignTeacher($this->courseId, $this->teacher['teacher_id']);

        $this->lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $this->courseId, 'teacher_id' => $this->teacher['teacher_id'],
            'title' => 'S', 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $this->lessonId, 'qr_token' => 'END' . $this->nextSeq(),
            'expires_at' => now()->addMinutes(5), 'session_expires_at' => now()->addMinutes(10),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function enrolled(): array
    {
        $s = $this->makeStudent();
        $this->enroll($s['student_id'], $this->courseId);

        return $s;
    }

    private function record(array $student, string $status, string $excuse = 'none'): void
    {
        DB::table('attendance')->insert([
            'student_id' => $student['student_id'], 'lesson_id' => $this->lessonId, 'status' => $status,
            'excuse_status' => $excuse, 'attendance_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function end(): void
    {
        $this->actAs($this->teacher['user'])
            ->postJson("/api/teacher/attendance/session/{$this->sessionId}/end")
            ->assertOk();
    }

    public function test_late_student_is_not_turned_into_absent(): void
    {
        $late = $this->enrolled();
        $this->record($late, 'late');

        $this->end();

        $this->assertSame('late', DB::table('attendance')->where('student_id', $late['student_id'])->value('status'));
    }

    public function test_missing_student_becomes_absent_without_excuse(): void
    {
        $missing = $this->enrolled();

        $this->end();

        $row = DB::table('attendance')->where('student_id', $missing['student_id'])->first();
        $this->assertSame('absent', $row->status);
        $this->assertSame('none', $row->excuse_status);
    }

    public function test_student_with_approved_leave_is_absent_but_excused(): void
    {
        $onLeave = $this->enrolled();
        DB::table('leave_requests')->insert([
            'student_id' => $onLeave['user']->user_id, 'type' => 'full_day', 'date' => now()->toDateString(),
            'reason' => 'x', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->end();

        $this->assertSame('approved', DB::table('attendance')->where('student_id', $onLeave['student_id'])->value('excuse_status'));
    }

    public function test_excused_absences_do_not_trigger_warnings(): void
    {
        $student = $this->makeStudent();

        // 7 أيام غياب كلها معذورة ← لا إنذار
        foreach (range(1, 7) as $i) {
            $lesson = DB::table('lessons')->insertGetId([
                'course_id' => $this->courseId, 'title' => "L$i", 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('attendance')->insert([
                'student_id' => $student['student_id'], 'lesson_id' => $lesson, 'status' => 'absent',
                'excuse_status' => 'approved', 'attendance_date' => now()->subDays($i)->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        app(AbsenceWarningService::class)->checkAndWarn($student['student_id']);
        $this->assertSame(0, DB::table('student_warnings')->where('student_id', $student['student_id'])->count());

        // 7 أيام غياب بلا عذر ← إنذار أول
        $other = $this->makeStudent();
        foreach (range(1, 7) as $i) {
            $lesson = DB::table('lessons')->insertGetId([
                'course_id' => $this->courseId, 'title' => "M$i", 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('attendance')->insert([
                'student_id' => $other['student_id'], 'lesson_id' => $lesson, 'status' => 'absent',
                'excuse_status' => 'none', 'attendance_date' => now()->subDays($i)->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        app(AbsenceWarningService::class)->checkAndWarn($other['student_id']);
        $this->assertSame('first', DB::table('student_warnings')->where('student_id', $other['student_id'])->value('warning_level'));
    }
}
