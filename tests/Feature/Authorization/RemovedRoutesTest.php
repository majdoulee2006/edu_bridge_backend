<?php

namespace Tests\Feature\Authorization;

use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * المسارات الخطرة التي حُذفت يجب ألا تعود، والمسارات الحساسة تتطلب مصادقة.
 */
class RemovedRoutesTest extends TestCase
{
    use MakesAcademicData;

    public function test_dev_reset_schedules_route_is_gone(): void
    {
        $user = $this->makeUser('student');

        $this->actAs($user)->getJson('/api/dev/reset-schedules')->assertNotFound();
    }

    public function test_create_student_web_route_is_gone(): void
    {
        $this->get('/create-student')->assertNotFound();
    }

    public function test_user_profile_by_id_route_is_gone(): void
    {
        $user = $this->makeUser('student');

        $this->actAs($user)->getJson('/api/user/profile/1')->assertNotFound();
    }

    public function test_public_parent_info_route_requires_authentication(): void
    {
        $this->getJson('/api/parent/info/1')->assertUnauthorized();
    }

    public function test_ai_chat_requires_authentication(): void
    {
        $this->postJson('/api/ai/chat', ['message' => 'hi'])->assertUnauthorized();
    }
}
