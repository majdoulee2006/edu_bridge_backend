<?php

namespace Tests\Feature\Auth;

use App\Services\TelegramService;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * استرجاع كلمة السر عبر OTP تيليغرام:
 *  - الرمز يذهب فقط إلى تيليغرام المربوط مسبقاً بالحساب، ولا يُقبل معرّف تيليغرام من الطلب
 *    (وإلا يستطيع أي شخص يعرف الرقم الجامعي أن يربط حسابه ويستولي على الحساب).
 *  - الرمز لا يظهر أبداً في جواب الـ API.
 */
class PasswordResetOtpTest extends TestCase
{
    use MakesAcademicData;

    private const URL = '/password/forgot/send-otp';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_unlinked_account_cannot_be_hijacked_with_an_attacker_telegram_id(): void
    {
        $victim = $this->makeUser('student', ['university_id' => '2026777']);

        $this->mock(TelegramService::class, function ($m) {
            $m->shouldNotReceive('sendOtpSync');
            $m->shouldNotReceive('findChatIdByUsername');
        });

        $response = $this->postJson(self::URL, [
            'identifier'          => '2026777',
            'telegram_identifier' => '999000111',   // معرّف المهاجم
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertNull(DB::table('users')->where('user_id', $victim->user_id)->value('telegram_chat_id'));
        $this->assertNull(session('pwd_reset_otp'));
        $this->assertDoesNotMatchRegularExpression('/\b\d{6}\b/', $response->getContent());
    }

    public function test_otp_goes_only_to_the_linked_chat_and_is_never_returned(): void
    {
        $user = $this->makeUser('student', ['university_id' => '2026888', 'telegram_chat_id' => '4242']);

        $sentOtp = null;
        $this->mock(TelegramService::class, function ($m) use (&$sentOtp) {
            $m->shouldReceive('sendOtpSync')->once()
                ->withArgs(function (int $chatId, string $otp) use (&$sentOtp) {
                    $sentOtp = $otp;
                    return $chatId === 4242;
                })->andReturn(true);
        });

        $response = $this->postJson(self::URL, ['identifier' => '2026888', 'telegram_identifier' => '999000111']);

        $response->assertOk()->assertJson(['success' => true])->assertJsonMissingPath('chat_id');
        $this->assertNotNull($sentOtp);
        $this->assertStringNotContainsString($sentOtp, $response->getContent());
        $this->assertSame($sentOtp, session('pwd_reset_otp'));
        $this->assertSame('4242', (string) DB::table('users')->where('user_id', $user->user_id)->value('telegram_chat_id'));
    }

    public function test_failed_telegram_delivery_does_not_leak_the_otp_or_open_a_reset_session(): void
    {
        $this->makeUser('student', ['university_id' => '2026999', 'telegram_chat_id' => '4343']);

        $this->mock(TelegramService::class, fn ($m) => $m->shouldReceive('sendOtpSync')->once()->andReturn(false));

        $response = $this->postJson(self::URL, ['identifier' => '2026999']);

        $response->assertStatus(503)->assertJson(['success' => false]);
        $this->assertNull(session('pwd_reset_otp'));
        $this->assertDoesNotMatchRegularExpression('/\b\d{6}\b/', $response->getContent());
    }
}
