<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * S-05 + N-01..N-05: المعلّم يتحكم بمقرراته وطلابه وجلساته فقط.
 */
class TeacherAccessTest extends TestCase
{
    use MakesAcademicData;

    private array $teacherA;
    private array $teacherB;
    private array $student;   // مسجّل في مقرر المعلّم A
    private int $courseA;
    private int $courseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacherA = $this->makeTeacher();
        $this->teacherB = $this->makeTeacher();
        $this->student  = $this->makeStudent();

        $this->courseA = $this->makeCourse();
        $this->courseB = $this->makeCourse();
        $this->assignTeacher($this->courseA, $this->teacherA['teacher_id']);
        $this->assignTeacher($this->courseB, $this->teacherB['teacher_id']);
        $this->enroll($this->student['student_id'], $this->courseA);
    }

    // ── جلسات الحضور (S-05) ─────────────────────────────────────────

    private function openSession(array $teacher, int $courseId): int
    {
        return $this->actAs($teacher['user'])
            ->postJson('/api/teacher/attendance/generate-qr', ['course_id' => $courseId])
            ->assertOk()
            ->json('data.session_id');
    }

    public function test_teacher_cannot_open_session_for_foreign_course(): void
    {
        $this->actAs($this->teacherA['user'])
            ->postJson('/api/teacher/attendance/generate-qr', ['course_id' => $this->courseB])
            ->assertForbidden();
    }

    public function test_owner_can_end_own_session(): void
    {
        $sessionId = $this->openSession($this->teacherA, $this->courseA);

        $this->actAs($this->teacherA['user'])
            ->postJson("/api/teacher/attendance/session/$sessionId/end")
            ->assertOk();
    }

    public function test_other_teacher_cannot_end_or_read_or_refresh_session(): void
    {
        $sessionId = $this->openSession($this->teacherA, $this->courseA);

        $this->actAs($this->teacherB['user']);
        $this->postJson("/api/teacher/attendance/session/$sessionId/end")->assertNotFound();
        $this->getJson("/api/teacher/attendance/session/$sessionId/list")->assertNotFound();
        $this->postJson("/api/teacher/attendance/session/$sessionId/refresh-qr")->assertNotFound();

        // الجلسة لم تُنهَ ولم يُسجَّل أي غياب
        $this->assertSame(1, (int) DB::table('attendance_sessions')->where('id', $sessionId)->value('is_active'));
        $this->assertSame(0, DB::table('attendance')->where('status', 'absent')->count());
    }

    public function test_teacher_can_reset_face_only_for_own_students(): void
    {
        $outsider = $this->makeStudent();

        $this->actAs($this->teacherA['user'])
            ->postJson("/api/teacher/students/{$this->student['student_id']}/reset-face")
            ->assertOk();

        $this->actAs($this->teacherA['user'])
            ->postJson("/api/teacher/students/{$outsider['student_id']}/reset-face")
            ->assertForbidden();
    }

    // ── تصحيح الواجبات (N-01) ───────────────────────────────────────

    private function submission(int $courseId, int $studentId): int
    {
        $assignmentId = DB::table('assignments')->insertGetId([
            'course_id'  => $courseId,
            'title'      => 'HW',
            'due_date'   => now()->addDay(),
            'max_points' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('assignment_submissions')->insertGetId([
            'assignment_id' => $assignmentId,
            'student_id'    => $studentId,
            'submitted_at'  => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function test_teacher_can_grade_submission_of_own_course(): void
    {
        $submissionId = $this->submission($this->courseA, $this->student['student_id']);

        $this->actAs($this->teacherA['user'])
            ->postJson("/api/teacher/assignments/$submissionId/grade", ['grade' => 80])
            ->assertOk();
    }

    public function test_teacher_cannot_grade_submission_of_foreign_course(): void
    {
        $submissionId = $this->submission($this->courseA, $this->student['student_id']);

        $this->actAs($this->teacherB['user'])
            ->postJson("/api/teacher/assignments/$submissionId/grade", ['grade' => 100])
            ->assertStatus(403);

        $this->assertNull(DB::table('assignment_submissions')->where('submission_id', $submissionId)->value('grade'));
    }

    // ── طلبات الغياب (N-02) ─────────────────────────────────────────

    public function test_teacher_cannot_answer_absence_request_of_foreign_student(): void
    {
        $requestId = DB::table('absence_requests')->insertGetId([
            'student_id' => $this->student['student_id'],   // طالب مقرر A
            'date'       => now()->toDateString(),
            'reason'     => 'x',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $before = DB::table('absence_requests')->where('request_id', $requestId)->value('status');

        $this->actAs($this->teacherB['user'])
            ->putJson("/api/teacher/absence-requests/$requestId/respond", ['status' => 'approved'])
            ->assertStatus(403);

        $this->assertSame($before, DB::table('absence_requests')->where('request_id', $requestId)->value('status'));
    }

    // ── تسجيل الحضور اليدوي (N-04) ──────────────────────────────────

    public function test_manual_attendance_rejects_lesson_of_another_course(): void
    {
        $foreignLesson = DB::table('lessons')->insertGetId([
            'course_id' => $this->courseB, 'title' => 'L', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actAs($this->teacherA['user'])
            ->postJson('/api/teacher/attendance', [
                'course_id'  => $this->courseA,
                'lesson_id'  => $foreignLesson,
                'date'       => now()->toDateString(),
                'attendance' => [['student_id' => $this->student['student_id'], 'status' => 'absent']],
            ])
            ->assertStatus(422);

        $this->assertSame(0, DB::table('attendance')->count());
    }

    public function test_manual_attendance_rejects_student_not_enrolled(): void
    {
        $lesson   = DB::table('lessons')->insertGetId([
            'course_id' => $this->courseA, 'title' => 'L', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $outsider = $this->makeStudent();

        $this->actAs($this->teacherA['user'])
            ->postJson('/api/teacher/attendance', [
                'course_id'  => $this->courseA,
                'lesson_id'  => $lesson,
                'date'       => now()->toDateString(),
                'attendance' => [['student_id' => $outsider['student_id'], 'status' => 'absent']],
            ])
            ->assertStatus(422);

        $this->assertSame(0, DB::table('attendance')->count());
    }

    // ── طلبات التقارير (N-03) ───────────────────────────────────────

    private function reportRequestFor(array $teacher): int
    {
        $head = $this->makeUser('head');

        return DB::table('report_requests')->insertGetId([
            'head_id'     => $head->user_id,
            'teacher_id'  => $teacher['teacher_id'],
            'student_id'  => $this->student['student_id'],
            'report_type' => 'behavioral',
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function test_teacher_cannot_read_or_submit_report_request_of_another_teacher(): void
    {
        $id = $this->reportRequestFor($this->teacherA);

        $this->actAs($this->teacherB['user']);
        $this->getJson("/api/teacher/report-requests/$id/stats")->assertForbidden();
        $this->postJson("/api/teacher/report-requests/$id/submit", ['notes' => 'forged evaluation'])->assertForbidden();

        $this->assertNull(DB::table('report_requests')->where('id', $id)->value('notes'));
    }

    public function test_teacher_can_submit_own_report_request(): void
    {
        $id = $this->reportRequestFor($this->teacherA);

        $this->actAs($this->teacherA['user'])
            ->postJson("/api/teacher/report-requests/$id/submit", ['notes' => 'good student'])
            ->assertOk();
    }

    // ── استدعاء أولياء الأمور (N-05) ────────────────────────────────

    public function test_teacher_cannot_summon_parent_of_unrelated_student(): void
    {
        $this->actAs($this->teacherB['user'])   // لا يدرّس هذا الطالب
            ->postJson('/api/teacher/parent-summons/send', [
                'student_id'   => $this->student['student_id'],
                'reason_title' => 'x',
                'details'      => 'x',
            ])
            ->assertForbidden();

        $this->assertSame(0, DB::table('parent_summons')->count());
    }

    public function test_summon_request_alias_route_works_for_own_student(): void
    {
        $this->actAs($this->teacherA['user'])
            ->postJson('/api/teacher/parent-summons/request', [
                'student_id'   => $this->student['student_id'],
                'reason_title' => 'Behavior',
                'details'      => 'Details',
            ])
            ->assertSuccessful();
    }

    // ── مسارات كانت تنهار بسبب عمود students.department_id المحذوف ────

    public function test_educator_students_and_summons_history_do_not_crash(): void
    {
        $this->actAs($this->teacherA['user'])
            ->getJson('/api/teacher/educator-students')
            ->assertOk();

        $this->actAs($this->teacherA['user'])
            ->getJson('/api/teacher/parent-summons-history')
            ->assertOk();
    }

    public function test_head_summons_list_does_not_crash(): void
    {
        $dept = $this->makeDepartment();
        $head = $this->makeHead($dept);

        $this->actAs($head['user'])
            ->getJson('/api/department-head/appointments/summons')
            ->assertOk();
    }
}
