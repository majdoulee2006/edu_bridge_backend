<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TelegramBotHandler;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * Presses every button of every role's real keyboard (taken from the menu the bot actually sends on /start)
 * and checks that it reaches its own service: it answers, does not crash, and does not fall back to
 * "re-send the main menu". Routing is done by substring matching in handleMessage, so one broad keyword
 * («قسم») once sent two head-of-department buttons to the dashboard instead of their service.
 */
class TelegramMenuRoutingTest extends TestCase
{
    use MakesAcademicData;

    private int $chat = 8900;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function say(string $text): void
    {
        (new TelegramBotHandler())->handleUpdate(['message' => ['chat' => ['id' => $this->chat], 'text' => $text]]);
    }

    /** @return array<int, array> payloads of messages sent to Telegram since the last fake() */
    private function payloads(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'sendMessage'))
            ->map(fn ($pair) => $pair[0]->data())
            ->values()
            ->all();
    }

    private function freshFake(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)]);
    }

    /** أزرار لوحة المفاتيح الفعلية التي يرسلها البوت عند /start لهذا المستخدم. */
    private function menuLabels(): array
    {
        $this->freshFake();
        $this->say('/start');

        foreach (array_reverse($this->payloads()) as $payload) {
            $markup = $payload['reply_markup'] ?? null;
            if (is_string($markup)) {
                $markup = json_decode($markup, true);
            }
            if (!empty($markup['keyboard'])) {
                return array_values(array_filter(array_map(
                    fn ($btn) => $btn['text'] ?? null,
                    array_merge(...$markup['keyboard'])
                )));
            }
        }

        return [];
    }

    private function hasReplyKeyboard(array $payload): bool
    {
        $markup = $payload['reply_markup'] ?? null;
        if (is_string($markup)) {
            $markup = json_decode($markup, true);
        }

        return !empty($markup['keyboard']);
    }

    private function linkedUser(string $role): User
    {
        $attrs = ['telegram_chat_id' => (string) $this->chat];

        return match ($role) {
            'student' => $this->makeStudent($attrs)['user'],
            'parent'  => $this->makeParent($attrs)['user'],
            'teacher' => $this->makeTeacher($attrs)['user'],
            'head'    => $this->makeHead($this->makeDepartment('قسم الحاسوب'), $attrs + ['department' => 'قسم الحاسوب'])['user'],
            default   => $this->makeUser($role, $attrs),
        };
    }

    public static function roles(): array
    {
        return [['student', 7], ['parent', 4], ['teacher', 10], ['head', 8], ['affairs', 7], ['admin', 7]];
    }

    #[DataProvider('roles')]
    public function test_every_menu_button_reaches_its_own_service(string $role, int $expectedButtons): void
    {
        $this->linkedUser($role);
        $labels = $this->menuLabels();

        $this->assertCount($expectedButtons, $labels, "unexpected number of buttons for $role: " . implode(' | ', $labels));

        foreach ($labels as $label) {
            if (str_contains($label, 'تسجيل خروج')) {
                continue; // يفصل الحساب؛ يُختبر على حدة
            }

            $this->freshFake();
            $this->say($label);
            $payloads = $this->payloads();

            $this->assertNotEmpty($payloads, "[$role] button «{$label}» produced no reply");
            foreach ($payloads as $payload) {
                $this->assertFalse(
                    $this->hasReplyKeyboard($payload),
                    "[$role] button «{$label}» fell back to the main menu instead of reaching its service"
                );
            }
        }
    }

    #[DataProvider('roles')]
    public function test_logout_button_unlinks_the_account(string $role, int $expectedButtons): void
    {
        $user = $this->linkedUser($role);
        $label = collect($this->menuLabels())->first(fn ($l) => str_contains($l, 'تسجيل خروج'));
        $this->assertNotNull($label, "[$role] has no logout button");

        $this->freshFake();
        $this->say($label);

        $this->assertNull($user->fresh()->telegram_chat_id);
    }

    public function test_head_buttons_reach_the_expected_services(): void
    {
        $this->linkedUser('head');
        $expected = [
            'لوحة القسم والإحصائيات'  => 'لوحة معلومات القسم الأكاديمي',
            'كادر'                    => 'لا يوجد معلمون',
            'مقررات وشعب القسم'       => 'لا توجد مقررات',
            'إجازات'                  => 'لا توجد أي طلبات إجازة',
            'الطلبات والخدمات'        => 'لا توجد طلبات طلابية',
            'مواعيد'                  => 'لا توجد أي مواعيد',
            'نشر إعلان للقسم'         => 'عنوان الإعلان',
        ];

        foreach ($this->menuLabels() as $label) {
            foreach ($expected as $needle => $marker) {
                if (!str_contains($label, $needle)) {
                    continue;
                }
                $this->freshFake();
                $this->say($label);
                $this->assertStringContainsString($marker, json_encode($this->payloads(), JSON_UNESCAPED_UNICODE), "head button «{$label}»");
            }
        }
    }
}
