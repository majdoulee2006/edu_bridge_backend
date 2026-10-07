<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * The Flutter API (student -> parent -> head -> affairs) runs on the shared LeaveWorkflow:
 * every step only in its own stage, notices go to the head of the student's department only.
 */
class LeaveApiFlowTest extends TestCase
{
    use MakesAcademicData;

    private string $dept = 'قسم الحاسوب';
    private array $student;
    private array $parent;
    private User $head;
    private User $otherHead;
    private User $affairs;

    protected function setUp(): void
    {
        parent::setUp();
        $deptA = $this->makeDepartment($this->dept);
        $deptB = $this->makeDepartment('قسم الميكانيك');
        $this->student   = $this->makeStudent(['department' => $this->dept]);
        $this->parent    = $this->makeParent();
        $this->linkParent($this->parent['user'], $this->student['user']);
        $this->head      = $this->makeHead($deptA, ['department' => $this->dept])['user'];
        $this->otherHead = $this->makeHead($deptB, ['department' => 'قسم الميكانيك'])['user'];
        $this->affairs   = $this->makeUser('affairs');
    }

    private function notices(User $u): int
    {
        return DB::table('notifications')->where('user_id', $u->user_id)->where('type', 'leave_request')->count();
    }

    private function leaveStatus(int $id): string
    {
        return (string) DB::table('leave_requests')->where('id', $id)->value('status');
    }

    private function submitAsStudent(array $student = null): int
    {
        $student ??= $this->student;
        $this->actAs($student['user'])->postJson('/api/student/leave-requests', [
            'type' => 'full_day', 'date' => now()->addDay()->toDateString(), 'reason' => 'medical visit',
        ])->assertOk();

        return (int) DB::table('leave_requests')->where('student_id', $student['user']->user_id)->orderByDesc('id')->value('id');
    }

    public function test_full_chain_through_the_api(): void
    {
        $id = $this->submitAsStudent();
        $this->assertSame('pending_parent', $this->leaveStatus($id));
        $this->assertSame(1, $this->notices($this->parent['user']));

        $this->actAs($this->parent['user'])->postJson("/api/parent/leave-requests/$id/respond", ['status' => 'approved'])->assertOk();
        $this->assertSame('pending_hod', $this->leaveStatus($id));
        $this->assertSame(1, $this->notices($this->head));
        $this->assertSame(0, $this->notices($this->otherHead), 'another department head was notified');

        $this->actAs($this->head)->putJson("/api/department-head/leave-requests/$id/respond", ['status' => 'approved'])->assertOk();
        $this->assertSame('pending_affairs', $this->leaveStatus($id));

        $this->actAs($this->affairs)->postJson("/api/affairs/leaves/$id/status", ['status' => 'approved'])->assertOk();
        $this->assertSame('approved', $this->leaveStatus($id));
        $this->assertSame(0, $this->notices($this->otherHead));
    }

    public function test_student_without_a_parent_reaches_only_the_department_head(): void
    {
        $lonely = $this->makeStudent(['department' => $this->dept]);

        $id = $this->submitAsStudent($lonely);

        $this->assertSame('pending_hod', $this->leaveStatus($id));
        $this->assertSame(1, $this->notices($this->head));
        $this->assertSame(0, $this->notices($this->otherHead));
    }

    public function test_parent_cannot_answer_twice_or_for_another_family(): void
    {
        $id = $this->submitAsStudent();

        $stranger = $this->makeParent()['user'];
        $this->actAs($stranger)->postJson("/api/parent/leave-requests/$id/respond", ['status' => 'approved'])->assertForbidden();
        $this->assertSame('pending_parent', $this->leaveStatus($id));

        $this->actAs($this->parent['user'])->postJson("/api/parent/leave-requests/$id/respond", ['status' => 'approved'])->assertOk();
        $this->actAs($this->parent['user'])->postJson("/api/parent/leave-requests/$id/respond", ['status' => 'rejected'])->assertStatus(422);
        $this->assertSame('pending_hod', $this->leaveStatus($id));
    }

    public function test_affairs_decides_only_in_its_stage_and_cannot_flip_the_decision(): void
    {
        $id = $this->submitAsStudent();

        // pending_parent: affairs cannot decide yet
        $this->actAs($this->affairs)->postJson("/api/affairs/leaves/$id/status", ['status' => 'approved'])->assertStatus(422);
        $this->assertSame('pending_parent', $this->leaveStatus($id));

        DB::table('leave_requests')->where('id', $id)->update(['status' => 'pending_affairs']);
        $this->actAs($this->affairs)->postJson("/api/affairs/leaves/$id/status", ['status' => 'approved'])->assertOk();
        $this->actAs($this->affairs)->postJson("/api/affairs/leaves/$id/status", ['status' => 'rejected'])->assertStatus(422);
        $this->assertSame('approved', $this->leaveStatus($id));

        $this->actAs($this->affairs)->postJson('/api/affairs/leaves/999999/status', ['status' => 'approved'])->assertNotFound();
    }

    public function test_final_decision_also_reaches_the_students_advisor(): void
    {
        $advisor = $this->makeTeacher();
        DB::table('students')->where('student_id', $this->student['student_id'])->update(['level' => 'السنة الأولى']);
        DB::table('teachers')->where('teacher_id', $advisor['teacher_id'])->update(['advisor_branch' => $this->dept, 'advisor_year' => 'السنة الأولى']);
        $id = $this->submitAsStudent();
        DB::table('leave_requests')->where('id', $id)->update(['status' => 'pending_affairs']);

        $this->actAs($this->affairs)->postJson("/api/affairs/leaves/$id/status", ['status' => 'approved'])->assertOk();

        $this->assertSame(1, $this->notices($advisor['user']));
    }
}
