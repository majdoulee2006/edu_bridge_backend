<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * N-15: تسلسل الأدوار. الشؤون لا تعدّل الأدمن ولا موظفي الشؤون الآخرين،
 * والأدمن وحده يدير الجميع.
 */
class AccountHierarchyTest extends TestCase
{
    use MakesAcademicData;

    private $affairs;
    private $otherAffairs;
    private $admin;
    private array $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->affairs      = $this->makeUser('affairs');
        $this->otherAffairs = $this->makeUser('affairs');
        $this->admin        = $this->makeUser('admin');
        $this->student      = $this->makeStudent();
    }

    // ── API ──────────────────────────────────────────────────────

    public function test_api_affairs_cannot_modify_admin_or_other_affairs(): void
    {
        $this->actAs($this->affairs);

        foreach ([$this->admin, $this->otherAffairs] as $target) {
            $id = $target->user_id;
            $hash = DB::table('users')->where('user_id', $id)->value('password');

            $this->postJson("/api/affairs/accounts/$id/update", [
                'full_name' => 'Hacked', 'email' => "h{$id}@example.test", 'password' => 'NewPassw0rd!',
            ])->assertForbidden();
            $this->postJson("/api/affairs/accounts/$id/toggle")->assertForbidden();
            $this->deleteJson("/api/affairs/accounts/$id")->assertForbidden();

            $row = DB::table('users')->where('user_id', $id)->first();
            $this->assertNotNull($row);
            $this->assertSame($hash, $row->password);
            $this->assertSame('active', $row->status);
        }
    }

    public function test_api_affairs_can_manage_student_account(): void
    {
        $id = $this->student['user']->user_id;

        $this->actAs($this->affairs)->postJson("/api/affairs/accounts/$id/toggle")->assertOk();
        $this->assertSame('inactive', DB::table('users')->where('user_id', $id)->value('status'));
    }

    public function test_api_admin_can_manage_affairs_account(): void
    {
        $this->actAs($this->admin)
            ->postJson("/api/affairs/accounts/{$this->otherAffairs->user_id}/toggle")
            ->assertOk();
    }

    // ── الويب ────────────────────────────────────────────────────

    public function test_web_affairs_cannot_modify_admin(): void
    {
        $id = $this->admin->user_id;
        $this->actingAs($this->affairs);

        $this->post("/affairs/accounts/$id/toggle")->assertForbidden();
        $this->post("/affairs/accounts/$id/delete")->assertForbidden();
        $this->post("/affairs/accounts/update/$id", [
            'full_name' => 'Hacked', 'email' => 'hacked@example.test',
        ])->assertForbidden();

        $this->assertDatabaseHas('users', ['user_id' => $id, 'status' => 'active']);
    }

    public function test_web_affairs_cannot_delete_other_affairs(): void
    {
        $this->actingAs($this->affairs)
            ->post("/affairs/accounts/{$this->otherAffairs->user_id}/delete")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['user_id' => $this->otherAffairs->user_id]);
    }

    public function test_web_affairs_can_toggle_student(): void
    {
        $id = $this->student['user']->user_id;

        $this->actingAs($this->affairs)->post("/affairs/accounts/$id/toggle")->assertRedirect();

        $this->assertSame('inactive', DB::table('users')->where('user_id', $id)->value('status'));
    }
}
