<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * S-01 / S-02: ولي الأمر لا يصل إلا لبيانات أبنائه المرتبطين به.
 */
class ParentAccessTest extends TestCase
{
    use MakesAcademicData;

    private array $parentA;
    private array $parentB;
    private array $childA;   // ابن ولي الأمر A
    private array $childB;   // ابن ولي الأمر B

    protected function setUp(): void
    {
        parent::setUp();

        $this->parentA = $this->makeParent();
        $this->parentB = $this->makeParent();
        $this->childA  = $this->makeStudent();
        $this->childB  = $this->makeStudent();

        $this->linkParent($this->parentA['user'], $this->childA['user']);
        $this->linkParent($this->parentB['user'], $this->childB['user']);
    }

    public function test_parent_can_read_own_child_performance(): void
    {
        $this->actAs($this->parentA['user'])
            ->getJson("/api/parent/performance/{$this->childA['student_id']}")
            ->assertOk();
    }

    public function test_parent_cannot_read_other_childs_performance(): void
    {
        $this->actAs($this->parentA['user'])
            ->getJson("/api/parent/performance/{$this->childB['student_id']}")
            ->assertForbidden();
    }

    public function test_parent_cannot_read_other_childs_assignments(): void
    {
        $this->actAs($this->parentA['user'])
            ->getJson("/api/parent/student/{$this->childB['student_id']}/assignments")
            ->assertForbidden();
    }

    public function test_parent_cannot_read_other_childs_permissions(): void
    {
        $this->actAs($this->parentA['user'])
            ->getJson("/api/parent/student/{$this->childB['student_id']}/permissions")
            ->assertForbidden();
    }

    public function test_parent_can_answer_own_childs_permission_request(): void
    {
        $requestId = $this->makeAbsenceRequest($this->childA['student_id']);

        $this->actAs($this->parentA['user'])
            ->postJson("/api/parent/permissions/$requestId/respond", ['status' => 'rejected'])
            ->assertOk();

        $this->assertSame('rejected', DB::table('absence_requests')->where('request_id', $requestId)->value('status'));
    }

    public function test_parent_cannot_answer_other_childs_permission_request(): void
    {
        $requestId = $this->makeAbsenceRequest($this->childB['student_id']);
        $before    = DB::table('absence_requests')->where('request_id', $requestId)->value('status');

        $this->actAs($this->parentA['user'])
            ->postJson("/api/parent/permissions/$requestId/respond", ['status' => 'approved'])
            ->assertForbidden();

        $this->assertSame($before, DB::table('absence_requests')->where('request_id', $requestId)->value('status'));
    }

    public function test_parent_cannot_answer_other_childs_leave_request(): void
    {
        $leaveId = DB::table('leave_requests')->insertGetId([
            'student_id' => $this->childB['user']->user_id,
            'type'       => 'full_day',
            'date'       => now()->toDateString(),
            'reason'     => 'x',
            'status'     => 'pending_parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actAs($this->parentA['user'])
            ->postJson("/api/parent/leave-requests/$leaveId/respond", ['status' => 'approved'])
            ->assertForbidden();

        $this->assertSame('pending_parent', DB::table('leave_requests')->where('id', $leaveId)->value('status'));
    }

    public function test_student_info_is_limited_to_own_children(): void
    {
        $this->actAs($this->parentA['user'])
            ->getJson("/api/student/info/{$this->childA['student_id']}")
            ->assertOk();

        $this->actAs($this->parentA['user'])
            ->getJson("/api/student/info/{$this->childB['student_id']}")
            ->assertForbidden();
    }

    private function makeAbsenceRequest(int $studentId): int
    {
        return DB::table('absence_requests')->insertGetId([
            'student_id' => $studentId,
            'date'       => now()->toDateString(),
            'reason'     => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
