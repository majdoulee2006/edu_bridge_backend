<?php

namespace Tests\Feature;

use App\Services\FcmService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * FcmService::sendToUserSync ينسخ الإشعار إلى تيليغرام لمن ربط حسابه بالبوت،
 * ويمكن إيقاف ذلك من الإعداد services.telegram.forward_notifications (خصوصية: علامات/إنذارات غياب).
 */
class TelegramNotificationForwardTest extends TestCase
{
    use MakesAcademicData;

    private function telegramCalls(): int
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.telegram.org'))->count();
    }

    public function test_notification_is_forwarded_to_telegram_by_default(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config(['services.telegram.bot_token' => 'TESTTOKEN', 'services.telegram.forward_notifications' => true]);
        $user = $this->makeUser('student', ['telegram_chat_id' => '777']);

        $this->assertTrue(FcmService::sendToUserSync($user->user_id, 'Title', 'Body'));
        $this->assertSame(1, $this->telegramCalls());
    }

    public function test_forwarding_can_be_switched_off(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config(['services.telegram.bot_token' => 'TESTTOKEN', 'services.telegram.forward_notifications' => false]);
        $user = $this->makeUser('student', ['telegram_chat_id' => '777']);

        $this->assertFalse(FcmService::sendToUserSync($user->user_id, 'Title', 'Body'));
        $this->assertSame(0, $this->telegramCalls());
    }

    public function test_muted_users_get_nothing_on_telegram_either(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config(['services.telegram.bot_token' => 'TESTTOKEN', 'services.telegram.forward_notifications' => true]);
        $user = $this->makeUser('student', ['telegram_chat_id' => '777', 'notifications_muted' => 1]);

        $this->assertFalse(FcmService::sendToUserSync($user->user_id, 'Title', 'Body'));
        $this->assertSame(0, $this->telegramCalls());
    }
}
