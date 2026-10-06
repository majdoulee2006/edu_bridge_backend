<?php

namespace Tests\Feature\Attendance;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * سلامة تسجيل الحضور بالـ QR: وقت المسح، الجهاز، الوجه، والتكرار.
 */
class AttendanceScanTest extends TestCase
{
    use MakesAcademicData;

    private array $student;
    private array $teacher;
    private int $courseId;
    private int $lessonId;
    private string $token = 'TESTQRTOKEN0123456789012345678901';

    protected function setUp(): void
    {
        parent::setUp();

        $dept    = $this->makeDepartment();
        $program = $this->makeProgram($dept);

        $this->teacher  = $this->makeTeacher();
        $this->student  = $this->makeStudent(['academic_year' => 'السنة الأولى'], $program);
        DB::table('students')->where('student_id', $this->student['student_id'])->update(['level' => 'السنة الأولى']);

        $this->courseId = $this->makeCourse($program, ['year' => 1]);
        $this->assignTeacher($this->courseId, $this->teacher['teacher_id']);
        $this->enroll($this->student['student_id'], $this->courseId);

        $this->lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $this->courseId, 'teacher_id' => $this->teacher['teacher_id'],
            'title' => 'Session', 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('attendance_sessions')->insert([
            'lesson_id' => $this->lessonId, 'qr_token' => $this->token,
            'expires_at' => now()->addMinutes(5), 'session_expires_at' => now()->addMinutes(10),
            'is_active' => true, 'created_at' => now()->subSeconds(5), 'updated_at' => now(),
        ]);
    }

    private function scan(array $extra = [])
    {
        return $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', array_merge(['qr_token' => $this->token], $extra));
    }

    private function embedding(float $seed = 0.1): array
    {
        return array_map(fn ($i) => sin($i * $seed) + ($i % 7) * 0.05, range(1, 192));
    }

    public function test_valid_scan_without_face_is_accepted_by_default(): void
    {
        config(['attendance.require_face' => false]);

        $this->scan()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendance', [
            'student_id' => $this->student['student_id'], 'lesson_id' => $this->lessonId, 'status' => 'present',
        ]);
    }

    public function test_scan_without_face_is_rejected_when_face_is_required(): void
    {
        config(['attendance.require_face' => true]);

        $this->scan()->assertForbidden()->assertJson(['reject_reason' => 'face_mismatch']);

        $this->assertSame(0, DB::table('attendance')->where('status', 'present')->count());
    }

    public function test_scanned_time_in_the_future_is_rejected(): void
    {
        $this->scan(['scanned_at' => now()->addMinutes(30)->toDateTimeString()])
            ->assertStatus(400)
            ->assertJson(['reject_reason' => 'expired_qr']);

        $this->assertSame(0, DB::table('attendance')->count());
    }

    public function test_first_face_scan_is_marked_first_time_not_verified(): void
    {
        $this->scan(['face_embedding' => $this->embedding()])
            ->assertOk()
            ->assertJson(['face_status' => 'first_time', 'face_score' => null]);
    }

    public function test_matching_face_is_verified_and_different_face_is_rejected(): void
    {
        DB::table('students')->where('student_id', $this->student['student_id'])
            ->update(['face_embedding' => json_encode($this->embedding(0.1))]);

        // وجه مختلف تماماً ← رفض
        $other = array_map(fn ($v) => -$v, $this->embedding(0.1));
        $this->scan(['face_embedding' => $other])
            ->assertForbidden()
            ->assertJson(['reject_reason' => 'face_mismatch']);
        $this->assertSame(0, DB::table('attendance')->where('status', 'present')->count());

        // نفس الوجه ← تحقق
        $this->scan(['face_embedding' => $this->embedding(0.1)])
            ->assertOk()
            ->assertJson(['face_status' => 'verified']);
    }

    public function test_face_image_without_reference_is_flagged_for_review(): void
    {
        $png = base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $this->scan(['face_image' => $png])
            ->assertOk()
            ->assertJson(['face_status' => 'suspicious', 'face_score' => null]);
    }

    public function test_device_bound_to_another_phone_is_rejected(): void
    {
        DB::table('students')->where('student_id', $this->student['student_id'])
            ->update(['device_id' => 'DEVICE-A', 'is_device_locked' => 1]);

        $this->scan(['device_id' => 'DEVICE-B'])
            ->assertForbidden()
            ->assertJson(['reject_reason' => 'device_mismatch']);
    }

    public function test_second_scan_for_same_lesson_is_refused(): void
    {
        $this->scan()->assertOk();
        $this->scan()->assertStatus(409)->assertJson(['reject_reason' => 'already_marked']);
    }

    public function test_unknown_qr_token_is_rejected(): void
    {
        $this->actAs($this->student['user'])
            ->postJson('/api/student/attendance/scan', ['qr_token' => 'nope'])
            ->assertStatus(400)
            ->assertJson(['reject_reason' => 'expired_qr']);
    }
}
