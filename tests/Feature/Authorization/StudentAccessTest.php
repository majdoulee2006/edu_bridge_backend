<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * N-06 / N-07 / N-13: الطالب لا يصل إلا لطلباته ومقرراته، ولا يسجّل نفسه في أي مقرر.
 */
class StudentAccessTest extends TestCase
{
    use MakesAcademicData;

    private array $studentA;
    private array $studentB;
    private int $courseA;     // مسجَّل فيه الطالب A
    private int $foreignCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $dept       = $this->makeDepartment();
        $programA   = $this->makeProgram($dept);
        $programOther = $this->makeProgram($dept);

        $this->studentA = $this->makeStudent([], $programA);
        $this->studentB = $this->makeStudent([], $programA);

        $this->courseA       = $this->makeCourse($programA);
        $this->foreignCourse = $this->makeCourse($programOther);   // برنامج مختلف
        $this->enroll($this->studentA['student_id'], $this->courseA);
    }

    // ── طلبات الإجازة (N-06) ────────────────────────────────────

    private function leave(array $student): int
    {
        return DB::table('leave_requests')->insertGetId([
            'student_id' => $student['user']->user_id, 'type' => 'full_day', 'date' => now()->toDateString(),
            'reason' => 'private reason', 'status' => 'pending_parent', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_student_can_read_own_leave_details(): void
    {
        $id = $this->leave($this->studentA);

        $this->actAs($this->studentA['user'])->getJson("/api/student/leave-requests/$id")->assertOk();
    }

    public function test_student_cannot_read_other_students_leave_details(): void
    {
        $id = $this->leave($this->studentB);

        $this->actAs($this->studentA['user'])->getJson("/api/student/leave-requests/$id")->assertNotFound();
    }

    // ── تسليم الواجبات (N-07) ───────────────────────────────────

    private function assignment(int $courseId): int
    {
        return DB::table('assignments')->insertGetId([
            'course_id' => $courseId, 'title' => 'HW', 'due_date' => now()->addDay(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_student_can_submit_assignment_of_enrolled_course(): void
    {
        $id = $this->assignment($this->courseA);

        $this->actAs($this->studentA['user'])
            ->postJson("/api/student/assignments/$id/submit", ['solution_text' => 'my answer'])
            ->assertOk();

        $this->assertDatabaseHas('assignment_submissions', ['assignment_id' => $id, 'student_id' => $this->studentA['student_id']]);
    }

    public function test_student_cannot_submit_assignment_of_foreign_course(): void
    {
        $id = $this->assignment($this->foreignCourse);

        $this->actAs($this->studentA['user'])
            ->postJson("/api/student/assignments/$id/submit", ['solution_text' => 'x'])
            ->assertForbidden();

        $this->assertDatabaseMissing('assignment_submissions', ['assignment_id' => $id]);
    }

    // ── مواد المقررات على الويب (N-13) ──────────────────────────

    public function test_web_materials_do_not_auto_enroll_into_ineligible_course(): void
    {
        $this->actingAs($this->studentA['user'])
            ->get("/student/courses/{$this->foreignCourse}/materials")
            ->assertForbidden();

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->studentA['student_id'],
            'course_id'  => $this->foreignCourse,
        ]);
    }

    public function test_web_lesson_download_requires_enrollment(): void
    {
        $lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $this->foreignCourse, 'title' => 'Secret lecture',
            'content_url' => 'lectures/none.pdf', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->studentA['user'])
            ->get("/student/lessons/$lessonId/download")
            ->assertForbidden();
    }
}
