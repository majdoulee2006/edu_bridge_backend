<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LeaveWorkflow;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * The single student-leave workflow shared by the Flutter API, the web panel and the Telegram bot:
 * student -> parent -> head of department -> student affairs -> approved, each step only in its own stage,
 * next-stage notices go to the head of the STUDENT's department, never to every head.
 */
class LeaveWorkflowTest extends TestCase
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
        $this->student   = $this->makeStudent(['department' => $this->dept, 'full_name' => 'Student One']);
        $this->parent    = $this->makeParent(['full_name' => 'Parent One']);
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

    public function test_full_chain_from_student_to_final_approval(): void
    {
        $leave = LeaveWorkflow::submit($this->student['user'], 'full_day', now()->addDay()->toDateString(), 'medical visit');
        $this->assertSame('pending_parent', $this->leaveStatus($leave->id));
        $this->assertSame(1, $this->notices($this->parent['user']));
        $this->assertSame(0, $this->notices($this->head), 'the head must not hear about it before the parent answers');

        $this->assertTrue(LeaveWorkflow::parentRespond($this->parent['user'], $leave->id, 'approved')['ok']);
        $this->assertSame('pending_hod', $this->leaveStatus($leave->id));
        $this->assertSame(1, $this->notices($this->head));
        $this->assertSame(0, $this->notices($this->otherHead), 'another department head was notified');

        $this->assertTrue(LeaveWorkflow::hodRespond($this->head, $leave->id, 'approved')['ok']);
        $this->assertSame('pending_affairs', $this->leaveStatus($leave->id));
        $this->assertSame(1, $this->notices($this->affairs));

        $this->assertTrue(LeaveWorkflow::affairsRespond($this->affairs, $leave->id, 'approved')['ok']);
        $this->assertSame('approved', $this->leaveStatus($leave->id));
        $this->assertSame(1, $this->notices($this->student['user']), 'the student gets the final decision');
        $this->assertSame(2, $this->notices($this->parent['user']), 'the parent is told the final decision');
        $this->assertSame(2, $this->notices($this->head), 'the department head is told the final decision');
        $this->assertSame(0, $this->notices($this->otherHead));
    }

    public function test_student_without_a_parent_goes_straight_to_the_department_head(): void
    {
        $lonely = $this->makeStudent(['department' => $this->dept]);

        $leave = LeaveWorkflow::submit($lonely['user'], 'hourly', now()->toDateString(), 'appointment');

        $this->assertSame('pending_hod', $this->leaveStatus($leave->id));
        $this->assertSame(1, $this->notices($this->head));
        $this->assertSame(0, $this->notices($this->otherHead));
    }

    public function test_no_head_for_the_department_alerts_the_admin_instead_of_losing_the_request(): void
    {
        $admin = $this->makeUser('admin');
        $orphan = $this->makeStudent(['department' => 'قسم بلا رئيس']);

        LeaveWorkflow::submit($orphan['user'], 'full_day', now()->toDateString(), 'reason here');

        $this->assertSame(1, $this->notices($admin));
        $this->assertSame(0, $this->notices($this->head));
    }

    public function test_parent_rejection_ends_the_request_and_tells_the_student(): void
    {
        $leave = LeaveWorkflow::submit($this->student['user'], 'full_day', now()->toDateString(), 'reason here');

        $this->assertTrue(LeaveWorkflow::parentRespond($this->parent['user'], $leave->id, 'rejected')['ok']);

        $this->assertSame('rejected', $this->leaveStatus($leave->id));
        $this->assertSame(1, $this->notices($this->student['user']));
        $this->assertSame(0, $this->notices($this->head));
    }

    public function test_every_step_is_refused_outside_its_stage(): void
    {
        $leave = LeaveWorkflow::submit($this->student['user'], 'full_day', now()->toDateString(), 'reason here');
        $id = $leave->id;

        // pending_parent: nobody but the parent decides
        $this->assertSame('stage', LeaveWorkflow::hodRespond($this->head, $id, 'approved')['error']);
        $this->assertSame('stage', LeaveWorkflow::affairsRespond($this->affairs, $id, 'approved')['error']);
        $this->assertSame('pending_parent', $this->leaveStatus($id));

        LeaveWorkflow::parentRespond($this->parent['user'], $id, 'approved');
        // pending_hod: the parent cannot answer again and affairs cannot jump ahead
        $this->assertSame('stage', LeaveWorkflow::parentRespond($this->parent['user'], $id, 'rejected')['error']);
        $this->assertSame('stage', LeaveWorkflow::affairsRespond($this->affairs, $id, 'approved')['error']);
        $this->assertSame('pending_hod', $this->leaveStatus($id));

        LeaveWorkflow::hodRespond($this->head, $id, 'approved');
        LeaveWorkflow::affairsRespond($this->affairs, $id, 'rejected');
        // decided: no re-decision
        $this->assertSame('stage', LeaveWorkflow::affairsRespond($this->affairs, $id, 'approved')['error']);
        $this->assertSame('stage', LeaveWorkflow::hodRespond($this->head, $id, 'rejected')['error']);
        $this->assertSame('rejected', $this->leaveStatus($id));
    }

    public function test_only_the_students_own_parent_and_own_department_head_may_decide(): void
    {
        $leave = LeaveWorkflow::submit($this->student['user'], 'full_day', now()->toDateString(), 'reason here');
        $strangerParent = $this->makeParent()['user'];

        $this->assertSame('forbidden', LeaveWorkflow::parentRespond($strangerParent, $leave->id, 'approved')['error']);
        $this->assertSame('pending_parent', $this->leaveStatus($leave->id));

        LeaveWorkflow::parentRespond($this->parent['user'], $leave->id, 'approved');
        $this->assertSame('forbidden', LeaveWorkflow::hodRespond($this->otherHead, $leave->id, 'approved')['error']);
        $this->assertSame('pending_hod', $this->leaveStatus($leave->id));
    }

    public function test_unknown_request_is_reported(): void
    {
        $this->assertSame('not_found', LeaveWorkflow::parentRespond($this->parent['user'], 999999, 'approved')['error']);
        $this->assertSame('not_found', LeaveWorkflow::hodRespond($this->head, 999999, 'approved')['error']);
        $this->assertSame('not_found', LeaveWorkflow::affairsRespond($this->affairs, 999999, 'approved')['error']);
    }
}
