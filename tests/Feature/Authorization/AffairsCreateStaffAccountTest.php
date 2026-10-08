<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/** إنشاء حساب معلم / رئيس قسم من الشؤون (شاشتا التطبيق). */
class AffairsCreateStaffAccountTest extends TestCase
{
    use MakesAcademicData;

    private function deptId(): int
    {
        return (int) DB::table('departments')->insertGetId([
            'name' => 'Dept ' . uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_affairs_creates_teacher_with_gender_and_birth_date(): void
    {
        $dept = $this->deptId();
        $this->actAs($this->makeUser('affairs'))->postJson('/api/affairs/accounts/create', [
            'full_name' => 'Teacher One', 'email' => 't1@example.test', 'role_id' => 2,
            'password' => 'Secret123', 'phone' => '0911111111', 'department_id' => $dept,
            'specialization' => 'Math', 'gender' => 'أنثى', 'birth_date' => '1990-05-01',
        ])->assertOk()->assertJson(['success' => true]);

        $u = DB::table('users')->where('email', 't1@example.test')->first();
        $this->assertSame(2, (int) $u->role_id);
        $this->assertSame('أنثى', $u->gender);
        $this->assertTrue(DB::table('teachers')->where('user_id', $u->user_id)->exists());
    }

    public function test_affairs_creates_head_but_not_a_second_one_for_same_department(): void
    {
        $dept = $this->deptId();
        $this->actAs($this->makeUser('affairs'));
        $payload = fn ($mail) => [
            'full_name' => 'Head', 'email' => $mail, 'role_id' => 5,
            'password' => 'Secret123', 'department_id' => $dept,
        ];

        $this->postJson('/api/affairs/accounts/create', $payload('h1@example.test'))->assertOk();
        $this->assertSame(1, DB::table('heads')->where('department_id', $dept)->count());

        $this->postJson('/api/affairs/accounts/create', $payload('h2@example.test'))->assertStatus(422);
        $this->assertSame(1, DB::table('heads')->where('department_id', $dept)->count());
        $this->assertFalse(DB::table('users')->where('email', 'h2@example.test')->exists());
    }

    public function test_future_birth_date_is_rejected(): void
    {
        $this->actAs($this->makeUser('affairs'))->postJson('/api/affairs/accounts/create', [
            'full_name' => 'X', 'email' => 'x@example.test', 'role_id' => 2, 'password' => 'Secret123',
            'department_id' => $this->deptId(), 'specialization' => 'S', 'birth_date' => now()->addDay()->toDateString(),
        ])->assertStatus(422);
    }
}
