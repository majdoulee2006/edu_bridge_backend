<?php

namespace Tests\Feature\Auth;

use App\Services\TelegramBotHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * أزرار الإجراءات الإدارية في بوت تيليغرام (admin_ / affairs_ ...) لا تُنفَّذ إلا من الدور المناسب.
 * معالجات الإجراءات نفسها لا تفحص الدور، فالحارس المركزي في handleCallbackQuery هو خط الدفاع.
 */
class TelegramCallbackRoleGuardTest extends TestCase
{
    use MakesAcademicData;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)]);
    }

    private function press(int $chatId, string $data): void
    {
        (new TelegramBotHandler())->handleUpdate([
            'callback_query' => ['id' => 'q1', 'data' => $data, 'message' => ['chat' => ['id' => $chatId]]],
        ]);
    }

    private function victimStatus(int $userId): string
    {
        return (string) DB::table('users')->where('user_id', $userId)->value('status');
    }

    public function test_non_admin_roles_cannot_trigger_admin_actions(): void
    {
        $victim = $this->makeUser('student');

        foreach (['student', 'teacher', 'parent', 'head', 'affairs'] as $i => $role) {
            $chat = 9000 + $i;
            $this->makeUser($role, ['telegram_chat_id' => (string) $chat]);

            $this->press($chat, "admin_toggle_status_{$victim->user_id}");

            $this->assertSame('active', $this->victimStatus($victim->user_id), "role $role changed an account status");
        }
    }

    public function test_student_cannot_trigger_affairs_or_head_actions(): void
    {
        $this->makeUser('student', ['telegram_chat_id' => '9100']);
        $requests = DB::table('student_requests')->count();

        $this->press(9100, 'affairs_approve_req_1');
        $this->press(9100, 'hod_approve_req_1');
        $this->press(9100, 'teacher_end_session_1');

        $this->assertSame($requests, DB::table('student_requests')->count());
    }

    public function test_admin_can_still_use_admin_actions(): void
    {
        $victim = $this->makeUser('student');
        $this->makeUser('admin', ['telegram_chat_id' => '9200']);

        $this->press(9200, "admin_toggle_status_{$victim->user_id}");

        $this->assertSame('inactive', $this->victimStatus($victim->user_id));
    }
}
