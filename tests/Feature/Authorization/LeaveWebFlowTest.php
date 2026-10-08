<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * Web panel (student / parent / head / affairs) runs the same LeaveWorkflow as the Flutter app and the bot:
 * new requests go to leave_requests (one record, not two tables), each step only in its own stage,
 * next-stage notices to the student's department head only.
 */
class LeaveWebFlowTest extends TestCase
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

    private function leave(string $status): int
    {
        return DB::table('leave_requests')->insertGetId([
            'student_id' => $this->student['user']->user_id, 'type' => 'full_day', 'date' => now()->toDateString(),
            'reason' => 'r', 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_student_web_form_creates_one_request_in_the_unified_table(): void
    {
        $this->actingAs($this->student['user'])->post('/student/leave-requests', [
            'type' => 'full_day', 'date' => now()->addDay()->toDateString(), 'reason' => 'medical visit', 'leave_time' => '09:00',
        ])->assertRedirect();

        $row = DB::table('leave_requests')->where('student_id', $this->student['user']->user_id)->first();
        $this->assertNotNull($row);
        $this->assertSame('pending_parent', $row->status);
        $this->assertSame(1, $this->notices($this->parent['user']));
    }

    public function test_parent_web_submission_creates_a_single_record_for_the_department_head_only(): void
    {
        $this->actingAs($this->parent['user'])->post('/parent/permissions/submit', [
            'student_id' => $this->student['student_id'], 'type' => 'full_day',
            'date' => now()->addDay()->toDateString(), 'reason' => 'family matter',
        ])->assertRedirect();

        $this->assertSame(1, DB::table('leave_requests')->where('student_id', $this->student['user']->user_id)->count());
        $this->assertSame(1, DB::table('leave_requests')->count(), 'it used to be inserted in both tables');
        $this->assertSame('pending_hod', DB::table('leave_requests')->value('status'));
        $this->assertSame(1, $this->notices($this->head));
        $this->assertSame(0, $this->notices($this->otherHead));
    }

    public function test_parent_web_answer_moves_the_request_and_only_once(): void
    {
        $id = $this->leave('pending_parent');

        $this->actingAs($this->parent['user'])->post("/parent/permissions/$id/respond", ['status' => 'approved', 'source_table' => 'leave_requests'])->assertRedirect();
        $this->assertSame('pending_hod', $this->leaveStatus($id));
        $this->assertSame(1, $this->notices($this->head));
        $this->assertSame(0, $this->notices($this->otherHead));

        $this->actingAs($this->parent['user'])->post("/parent/permissions/$id/respond", ['status' => 'rejected', 'source_table' => 'leave_requests'])->assertRedirect();
        $this->assertSame('pending_hod', $this->leaveStatus($id), 'a parent must not flip a request that already moved on');
    }

    public function test_parent_web_cannot_answer_another_familys_request(): void
    {
        $id = $this->leave('pending_parent');
        $stranger = $this->makeParent()['user'];

        $this->actingAs($stranger)->post("/parent/permissions/$id/respond", ['status' => 'approved', 'source_table' => 'leave_requests'])->assertRedirect();

        $this->assertSame('pending_parent', $this->leaveStatus($id));
    }

    public function test_head_web_decision_uses_the_stage_and_the_department(): void
    {
        $atHod = $this->leave('pending_hod');
        $atParent = $this->leave('pending_parent');

        $this->actingAs($this->otherHead)->post("/hod/leaves/$atHod/status", ['status' => 'approved', 'source_table' => 'leave_requests'])->assertForbidden();
        $this->actingAs($this->head)->post("/hod/leaves/$atParent/status", ['status' => 'approved', 'source_table' => 'leave_requests'])->assertRedirect()->assertSessionHas('error');
        $this->assertSame('pending_parent', $this->leaveStatus($atParent));

        $this->actingAs($this->head)->post("/hod/leaves/$atHod/status", ['status' => 'approved', 'source_table' => 'leave_requests'])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('pending_affairs', $this->leaveStatus($atHod));
        $this->assertSame(1, $this->notices($this->affairs));
    }

    public function test_affairs_web_final_decision_only_in_its_stage_and_notifies_everyone_concerned(): void
    {
        $early = $this->leave('pending_hod');
        $mine = $this->leave('pending_affairs');

        $this->actingAs($this->affairs)->post("/affairs/leaves/$early/status", ['status' => 'approved', 'source_table' => 'leave_requests'])->assertRedirect()->assertSessionHas('error');
        $this->assertSame('pending_hod', $this->leaveStatus($early));

        $this->actingAs($this->affairs)->post("/affairs/leaves/$mine/status", ['status' => 'approved', 'source_table' => 'leave_requests'])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('approved', $this->leaveStatus($mine));
        $this->assertSame(1, $this->notices($this->student['user']));
        $this->assertSame(1, $this->notices($this->parent['user']));
        $this->assertSame(1, $this->notices($this->head));
        $this->assertSame(0, $this->notices($this->otherHead));

        $this->actingAs($this->affairs)->post("/affairs/leaves/$mine/status", ['status' => 'rejected', 'source_table' => 'leave_requests'])->assertRedirect()->assertSessionHas('error');
        $this->assertSame('approved', $this->leaveStatus($mine));
    }

    public function test_web_pages_list_the_requests_of_the_unified_table(): void
    {
        $this->leave('pending_parent');

        $this->assertCount(1, $this->actingAs($this->student['user'])->get('/student/leave-requests')->assertOk()->viewData('requests'));
        $this->assertCount(1, $this->actingAs($this->parent['user'])->get('/parent/permissions')->assertOk()->viewData('requests'));
        $this->assertCount(0, $this->actingAs($this->head)->get('/hod/leaves')->assertOk()->viewData('allLeaves'), 'only pending_parent exists in the new table, which the head must not see yet');
    }
}
