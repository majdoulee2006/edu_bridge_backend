<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * N-10 / N-11: رئيس القسم (ويب) لا يدير إلا حسابات ومقررات وطلبات قسمه،
 * ولا يمس أبداً الأدمن أو الشؤون أو رؤساء الأقسام الآخرين.
 */
class HeadWebAccessTest extends TestCase
{
    use MakesAcademicData;

    private int $deptA;
    private int $deptB;
    private string $deptAName;
    private string $deptBName;
    private array $headA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptAName = 'Dept A ' . $this->nextSeq();
        $this->deptBName = 'Dept B ' . $this->nextSeq();
        $this->deptA = $this->makeDepartment($this->deptAName);
        $this->deptB = $this->makeDepartment($this->deptBName);
        $this->headA = $this->makeHead($this->deptA, ['department' => $this->deptAName]);
    }

    private function asHead()
    {
        return $this->actingAs($this->headA['user']);
    }

    // ── الحسابات (N-10) ──────────────────────────────────────────

    public function test_head_can_delete_teacher_in_own_department(): void
    {
        $teacher = $this->makeTeacher(['department' => $this->deptAName]);

        $this->asHead()->post("/hod/accounts/delete/{$teacher['user']->user_id}")->assertRedirect();

        $this->assertDatabaseMissing('users', ['user_id' => $teacher['user']->user_id]);
    }

    public function test_head_cannot_delete_admin(): void
    {
        $admin = $this->makeUser('admin', ['department' => $this->deptAName]);

        $this->asHead()->post("/hod/accounts/delete/{$admin->user_id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['user_id' => $admin->user_id]);
    }

    public function test_head_cannot_delete_other_head_or_affairs(): void
    {
        $otherHead = $this->makeHead($this->deptB, ['department' => $this->deptBName]);
        $affairs   = $this->makeUser('affairs');

        $this->asHead()->post("/hod/accounts/delete/{$otherHead['user']->user_id}")->assertForbidden();
        $this->asHead()->post("/hod/accounts/delete/{$affairs->user_id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['user_id' => $otherHead['user']->user_id]);
        $this->assertDatabaseHas('users', ['user_id' => $affairs->user_id]);
    }

    public function test_head_cannot_delete_teacher_of_other_department(): void
    {
        $teacher = $this->makeTeacher(['department' => $this->deptBName]);

        $this->asHead()->post("/hod/accounts/delete/{$teacher['user']->user_id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['user_id' => $teacher['user']->user_id]);
    }

    public function test_head_cannot_take_over_admin_account_via_update(): void
    {
        $admin = $this->makeUser('admin');
        $oldHash = DB::table('users')->where('user_id', $admin->user_id)->value('password');

        $this->asHead()->post("/hod/accounts/update/{$admin->user_id}", [
            'full_name'             => 'Hacked',
            'email'                 => 'hacked@example.test',
            'password'              => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
        ])->assertForbidden();

        $row = DB::table('users')->where('user_id', $admin->user_id)->first();
        $this->assertSame($oldHash, $row->password);
        $this->assertNotSame('hacked@example.test', $row->email);
    }

    public function test_head_can_update_student_in_own_department(): void
    {
        $student = $this->makeStudent(['department' => $this->deptAName]);

        $this->asHead()->post("/hod/accounts/update/{$student['user']->user_id}", [
            'full_name' => 'Renamed Student',
            'email'     => 'renamed_' . $this->nextSeq() . '@example.test',
        ])->assertRedirect();

        $this->assertSame('Renamed Student', DB::table('users')->where('user_id', $student['user']->user_id)->value('full_name'));
    }

    // ── الجداول والامتحانات والتثقيل (N-11) ─────────────────────

    public function test_head_cannot_delete_schedule_exam_or_reweight_foreign_course(): void
    {
        $programB = $this->makeProgram($this->deptB);
        $courseB  = $this->makeCourse($programB);

        $scheduleId = DB::table('schedules')->insertGetId([
            'course_id'  => $courseB,
            'teacher_id' => $this->makeTeacher()['user']->user_id,
            'day'        => 'Sunday',
            'room'       => 'R1',
            'start_time' => '08:00:00',
            'end_time'   => '09:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $examId = DB::table('exams')->insertGetId([
            'course_id' => $courseB, 'exam_name' => 'Final', 'exam_date' => now()->addWeek(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->asHead();
        $this->post("/hod/organization/schedule/delete/$scheduleId")->assertForbidden();
        $this->post("/hod/organization/exam/delete/$examId")->assertForbidden();
        $this->post("/hod/organization/course/$courseB/weight", ['weight' => 9])->assertForbidden();

        $this->assertDatabaseHas('schedules', ['schedule_id' => $scheduleId]);
        $this->assertDatabaseHas('exams', ['exam_id' => $examId]);
    }

    // ── طلبات الإجازة وآلة الحالات (N-08/N-09) ──────────────────

    private function leaveFor(array $student, string $status): int
    {
        return DB::table('leave_requests')->insertGetId([
            'student_id' => $student['user']->user_id,
            'type'       => 'full_day',
            'date'       => now()->toDateString(),
            'reason'     => 'x',
            'status'     => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_head_cannot_approve_leave_before_parent_approval(): void
    {
        $student = $this->makeStudent(['department' => $this->deptAName]);
        $leaveId = $this->leaveFor($student, 'pending_parent');

        $this->asHead()->post("/hod/leaves/$leaveId/status", ['status' => 'approved']);

        $this->assertSame('pending_parent', DB::table('leave_requests')->where('id', $leaveId)->value('status'));
    }

    public function test_head_moves_leave_to_affairs_after_parent_approval(): void
    {
        $student = $this->makeStudent(['department' => $this->deptAName]);
        $leaveId = $this->leaveFor($student, 'pending_hod');

        $this->asHead()->post("/hod/leaves/$leaveId/status", ['status' => 'approved']);

        $this->assertSame('pending_affairs', DB::table('leave_requests')->where('id', $leaveId)->value('status'));
    }

    public function test_head_cannot_touch_leave_of_other_department(): void
    {
        $student = $this->makeStudent(['department' => $this->deptBName]);
        $leaveId = $this->leaveFor($student, 'pending_hod');

        $this->asHead()->post("/hod/leaves/$leaveId/status", ['status' => 'approved'])->assertForbidden();

        $this->assertSame('pending_hod', DB::table('leave_requests')->where('id', $leaveId)->value('status'));
    }
}
