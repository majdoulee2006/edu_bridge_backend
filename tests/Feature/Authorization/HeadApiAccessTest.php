<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * N-08: رئيس القسم (API) لا يتحكم إلا بطلبات وتقارير ومقررات قسمه، وبالمرحلة الصحيحة فقط.
 */
class HeadApiAccessTest extends TestCase
{
    use MakesAcademicData;

    private int $deptA;
    private int $deptB;
    private string $nameA;
    private string $nameB;
    private array $headA;
    private array $studentA;   // في قسم A
    private array $studentB;   // في قسم B

    protected function setUp(): void
    {
        parent::setUp();

        $this->nameA = 'API Dept A ' . $this->nextSeq();
        $this->nameB = 'API Dept B ' . $this->nextSeq();
        $this->deptA = $this->makeDepartment($this->nameA);
        $this->deptB = $this->makeDepartment($this->nameB);
        $this->headA = $this->makeHead($this->deptA, ['department' => $this->nameA]);

        $this->studentA = $this->makeStudent(['department' => $this->nameA]);
        $this->studentB = $this->makeStudent(['department' => $this->nameB]);

        $this->actAs($this->headA['user']);
    }

    private function leave(array $student, string $status): int
    {
        return DB::table('leave_requests')->insertGetId([
            'student_id' => $student['user']->user_id, 'type' => 'full_day', 'date' => now()->toDateString(),
            'reason' => 'x', 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function reportRequest(array $student, array $extra = []): int
    {
        return DB::table('report_requests')->insertGetId(array_merge([
            'head_id'     => $this->headA['user']->user_id,
            'student_id'  => $student['student_id'],
            'report_type' => 'behavioral',
            'status'      => 'completed',
            'created_at'  => now(),
            'updated_at'  => now(),
        ], $extra));
    }

    private function serviceRequest(array $student, string $status): int
    {
        return DB::table('student_requests')->insertGetId([
            'student_id' => $student['student_id'], 'type' => 'appeal', 'details' => 'x',
            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ── الإجازات ─────────────────────────────────────────────────

    public function test_leave_of_other_department_is_forbidden(): void
    {
        $id = $this->leave($this->studentB, 'pending_hod');

        $this->putJson("/api/department-head/leave-requests/$id/respond", ['status' => 'approved'])->assertForbidden();

        $this->assertSame('pending_hod', DB::table('leave_requests')->where('id', $id)->value('status'));
    }

    public function test_leave_cannot_skip_parent_approval(): void
    {
        $id = $this->leave($this->studentA, 'pending_parent');

        $this->putJson("/api/department-head/leave-requests/$id/respond", ['status' => 'approved'])->assertStatus(422);

        $this->assertSame('pending_parent', DB::table('leave_requests')->where('id', $id)->value('status'));
    }

    public function test_leave_in_head_stage_moves_to_affairs(): void
    {
        $id = $this->leave($this->studentA, 'pending_hod');

        $this->putJson("/api/department-head/leave-requests/$id/respond", ['status' => 'approved'])->assertOk();

        $this->assertSame('pending_affairs', DB::table('leave_requests')->where('id', $id)->value('status'));
    }

    // ── التقارير ─────────────────────────────────────────────────

    public function test_head_cannot_delete_or_annotate_foreign_report(): void
    {
        $id = $this->reportRequest($this->studentB);

        $this->postJson("/api/department-head/report-requests/$id/hod-notes", ['hod_notes' => 'x'])->assertForbidden();
        $this->deleteJson("/api/department-head/report-requests/$id")->assertForbidden();
        $this->postJson("/api/department-head/report-requests/$id/send-to-parent")->assertForbidden();

        $this->assertDatabaseHas('report_requests', ['id' => $id]);
    }

    public function test_head_can_delete_own_department_report(): void
    {
        $id = $this->reportRequest($this->studentA);

        $this->deleteJson("/api/department-head/report-requests/$id")->assertOk();

        $this->assertDatabaseMissing('report_requests', ['id' => $id]);
    }

    // ── خدمات الطلاب ─────────────────────────────────────────────

    public function test_service_request_of_other_department_is_forbidden(): void
    {
        $id = $this->serviceRequest($this->studentB, 'pending_hod');

        $this->putJson("/api/department-head/student-service-requests/$id/respond", ['status' => 'approved'])->assertForbidden();

        $this->assertSame('pending_hod', DB::table('student_requests')->where('id', $id)->value('status'));
    }

    public function test_service_request_must_be_in_head_stage(): void
    {
        $id = $this->serviceRequest($this->studentA, 'pending_affairs');

        $this->putJson("/api/department-head/student-service-requests/$id/respond", ['status' => 'approved'])->assertStatus(422);

        $this->assertSame('pending_affairs', DB::table('student_requests')->where('id', $id)->value('status'));
    }

    public function test_service_request_in_head_stage_moves_to_admin(): void
    {
        $id = $this->serviceRequest($this->studentA, 'pending_hod');

        $this->putJson("/api/department-head/student-service-requests/$id/respond", ['status' => 'approved'])->assertOk();

        $this->assertSame('pending_admin', DB::table('student_requests')->where('id', $id)->value('status'));
    }

    // ── درجات المقررات ───────────────────────────────────────────

    public function test_head_cannot_read_grades_of_foreign_course(): void
    {
        $courseB = $this->makeCourse($this->makeProgram($this->deptB));
        $courseA = $this->makeCourse($this->makeProgram($this->deptA));

        $this->getJson("/api/department-head/grade-report-requests/$courseB/entries")->assertForbidden();
        $this->getJson("/api/department-head/grade-report-requests/$courseA/entries")->assertOk();
    }

    // ── المواعيد والاستدعاءات ────────────────────────────────────

    private function summon(array $student, string $status = 'pending_hod'): int
    {
        return DB::table('parent_summons')->insertGetId([
            'sender_user_id' => $this->makeTeacher()['user']->user_id,
            'student_id'     => $student['student_id'],
            'reason_title'   => 'T', 'details' => 'D', 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function meeting(array $student): int
    {
        return DB::table('parent_meeting_requests')->insertGetId([
            'parent_user_id' => $this->makeParent()['user']->user_id,
            'student_id'     => $student['student_id'],
            'subject' => 'S', 'reason' => 'R',
            'target_role'    => 'head',
            'status'         => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_head_sees_only_own_department_summons_and_meetings(): void
    {
        $mine    = $this->summon($this->studentA);
        $foreign = $this->summon($this->studentB);
        $myMeet  = $this->meeting($this->studentA);
        $foMeet  = $this->meeting($this->studentB);

        $summonIds  = collect($this->getJson('/api/department-head/appointments/summons')->assertOk()->json('data'))->pluck('id')->all();
        $meetingIds = collect($this->getJson('/api/department-head/appointments/meetings')->assertOk()->json('data'))->pluck('id')->all();

        $this->assertContains($mine, $summonIds);
        $this->assertNotContains($foreign, $summonIds);
        $this->assertContains($myMeet, $meetingIds);
        $this->assertNotContains($foMeet, $meetingIds);
    }

    public function test_head_cannot_forward_or_answer_foreign_department_items(): void
    {
        $foreign = $this->summon($this->studentB);
        $foMeet  = $this->meeting($this->studentB);

        $this->postJson("/api/department-head/appointments/summons/$foreign/forward")->assertForbidden();
        $this->putJson("/api/department-head/appointments/meetings/$foMeet/respond", ['status' => 'approved'])->assertForbidden();
        $this->putJson("/api/department-head/parent-meetings/$foMeet/respond", ['status' => 'approved'])->assertForbidden();

        $this->assertSame('pending_hod', DB::table('parent_summons')->where('id', $foreign)->value('status'));
        $this->assertSame('pending', DB::table('parent_meeting_requests')->where('id', $foMeet)->value('status'));
    }

    public function test_head_cannot_summon_parent_of_foreign_student(): void
    {
        $this->makeParentLink($this->studentB);

        $this->postJson('/api/department-head/parent-summons', [
            'student_id' => $this->studentB['student_id'], 'reason_title' => 'x', 'details' => 'y',
        ])->assertForbidden();
    }

    private function makeParentLink(array $student): void
    {
        $this->linkParent($this->makeParent()['user'], $student['user']);
    }
}
