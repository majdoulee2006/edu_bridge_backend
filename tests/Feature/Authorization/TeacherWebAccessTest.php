<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * N-12: المعلّم (ويب) لا يصل إلا لجلساته وواجباته وطلبات تقاريره.
 */
class TeacherWebAccessTest extends TestCase
{
    use MakesAcademicData;

    private array $teacherA;
    private array $teacherB;
    private array $student;
    private int $courseA;
    private int $sessionA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacherA = $this->makeTeacher();
        $this->teacherB = $this->makeTeacher();
        $this->student  = $this->makeStudent();

        $this->courseA = $this->makeCourse();
        $this->assignTeacher($this->courseA, $this->teacherA['teacher_id']);
        $this->enroll($this->student['student_id'], $this->courseA);

        $lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $this->courseA, 'teacher_id' => $this->teacherA['teacher_id'],
            'title' => 'Session', 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->sessionA = DB::table('attendance_sessions')->insertGetId([
            'lesson_id'          => $lessonId,
            'qr_token'           => 'tok_' . $this->nextSeq(),
            'expires_at'         => now()->addSeconds(30),
            'session_expires_at' => now()->addMinutes(10),
            'is_active'          => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function test_owner_can_refresh_end_and_list_absentees(): void
    {
        $this->actingAs($this->teacherA['user']);

        $this->getJson("/teacher/attendance/refresh/{$this->sessionA}")->assertOk();
        $this->getJson("/teacher/attendance/absentees/{$this->sessionA}")->assertOk();
    }

    public function test_other_teacher_cannot_touch_the_session(): void
    {
        $this->actingAs($this->teacherB['user']);

        $this->getJson("/teacher/attendance/refresh/{$this->sessionA}")->assertNotFound();
        $this->getJson("/teacher/attendance/absentees/{$this->sessionA}")->assertOk()->assertExactJson([]);
        $this->get("/teacher/attendance/export/{$this->sessionA}")->assertNotFound();
        $this->post("/teacher/attendance/end/{$this->sessionA}");

        $this->assertSame(1, (int) DB::table('attendance_sessions')->where('id', $this->sessionA)->value('is_active'));
        $this->assertSame(0, DB::table('attendance')->where('status', 'absent')->count());
    }

    public function test_other_teacher_cannot_read_assignment_submissions(): void
    {
        $assignmentId = DB::table('assignments')->insertGetId([
            'course_id' => $this->courseA, 'title' => 'HW', 'due_date' => now()->addDay(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->teacherA['user'])->get("/teacher/assignments/$assignmentId/submissions")->assertOk();
        $this->actingAs($this->teacherB['user'])->get("/teacher/assignments/$assignmentId/submissions")->assertForbidden();
    }

    public function test_other_teacher_cannot_submit_report_request(): void
    {
        $head = $this->makeUser('head');
        $id = DB::table('report_requests')->insertGetId([
            'head_id' => $head->user_id, 'teacher_id' => $this->teacherA['teacher_id'],
            'student_id' => $this->student['student_id'], 'report_type' => 'behavioral', 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->teacherB['user'])
            ->post("/teacher/reports/$id/submit", ['behavioral_notes' => 'forged'])
            ->assertForbidden();

        $this->assertSame('pending', DB::table('report_requests')->where('id', $id)->value('status'));
    }
}
