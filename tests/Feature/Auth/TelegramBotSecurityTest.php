<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TelegramBotHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * أمان التسجيل وتسجيل الدخول عبر بوت تيليغرام:
 *  - لا يُنشأ حساب طالب إلا برقم جامعي أصدرته شؤون الطلاب وغير مستخدم
 *  - ولي الأمر يلزمه اسم عائلة مطابق لاسم عائلة الطالب
 *  - كلمة السر 8 خانات على الأقل
 *  - دخول البوت يخضع لنفس قفل المحاولات (5 فاشلة = قفل)
 */
class TelegramBotSecurityTest extends TestCase
{
    use MakesAcademicData;

    private int $chat = 555001;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)]);
        Cache::flush();
    }

    private function say(string $text): void
    {
        (new TelegramBotHandler())->handleUpdate([
            'message' => ['chat' => ['id' => $this->chat], 'text' => $text],
        ]);
    }

    private function state(): ?string
    {
        return Cache::get("telegram_state_{$this->chat}");
    }

    private function issueUniversityId(string $uid, bool $used = false): void
    {
        DB::table('university_ids')->insert([
            'university_id' => $uid, 'full_name' => 'Test', 'role' => 'student',
            'is_used' => $used, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_student_registration_rejects_an_invented_university_id(): void
    {
        Cache::put("telegram_state_{$this->chat}", 'reg_student_uid', 1800);

        $this->say('9999999');

        $this->assertSame('reg_student_uid', $this->state());
        $this->assertNull(Cache::get("reg_std_uid_{$this->chat}"));
    }

    public function test_student_registration_rejects_an_already_used_university_id(): void
    {
        $this->issueUniversityId('2026111', used: true);
        Cache::put("telegram_state_{$this->chat}", 'reg_student_uid', 1800);

        $this->say('2026111');

        $this->assertSame('reg_student_uid', $this->state());
    }

    public function test_student_registration_accepts_an_issued_unused_university_id(): void
    {
        $this->issueUniversityId('2026222');
        Cache::put("telegram_state_{$this->chat}", 'reg_student_uid', 1800);

        $this->say('2026222');

        $this->assertSame('reg_student_program', $this->state());
        $this->assertSame('2026222', Cache::get("reg_std_uid_{$this->chat}"));
    }

    public function test_registration_passwords_must_have_at_least_8_characters(): void
    {
        Cache::put("telegram_state_{$this->chat}", 'reg_student_password', 1800);
        $before = User::count();

        $this->say('1234567'); // 7 خانات

        $this->assertSame('reg_student_password', $this->state());
        $this->assertSame($before, User::count());
    }

    public function test_parent_registration_requires_matching_family_name(): void
    {
        $this->makeUser('student', ['university_id' => 'S100', 'last_name' => 'العلي', 'full_name' => 'سامر العلي']);
        Cache::put("telegram_state_{$this->chat}", 'reg_parent_child_uid', 1800);
        Cache::put("reg_par_name_{$this->chat}", 'محمد الخطيب', 1800);

        $this->say('S100'); // طالب من عائلة أخرى

        $this->assertSame('reg_parent_child_uid', $this->state());
        $this->assertNull(Cache::get("reg_par_child_user_id_{$this->chat}"));
    }

    public function test_parent_registration_accepts_matching_family_name(): void
    {
        $child = $this->makeUser('student', ['university_id' => 'S200', 'last_name' => 'الخطيب', 'full_name' => 'سامر الخطيب']);
        Cache::put("telegram_state_{$this->chat}", 'reg_parent_child_uid', 1800);
        Cache::put("reg_par_name_{$this->chat}", 'محمد الخطيب', 1800);

        $this->say('S200');

        $this->assertSame('reg_parent_phone', $this->state());
        $this->assertSame($child->user_id, Cache::get("reg_par_child_user_id_{$this->chat}"));
    }

    public function test_bot_login_locks_the_account_after_5_wrong_passwords(): void
    {
        $user = $this->makeUser('student');

        for ($i = 0; $i < 5; $i++) {
            Cache::put("telegram_state_{$this->chat}", 'awaiting_password', 3600);
            Cache::put("telegram_auth_{$this->chat}_user_id", $user->user_id, 3600);
            $this->say('wrong-password');
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue(\Carbon\Carbon::parse($user->locked_until)->isFuture());

        // حتى كلمة السر الصحيحة ما تُقبل أثناء القفل، ولا يُربط تيليغرام
        Cache::put("telegram_state_{$this->chat}", 'awaiting_password', 3600);
        Cache::put("telegram_auth_{$this->chat}_user_id", $user->user_id, 3600);
        $this->say('Password123!');

        $this->assertNull($user->fresh()->telegram_chat_id);
        $this->assertNull($this->state());
    }

    public function test_bot_login_with_correct_password_links_the_chat_and_resets_failures(): void
    {
        $user = $this->makeUser('student', ['failed_login_attempts' => 3]);
        Cache::put("telegram_state_{$this->chat}", 'awaiting_password', 3600);
        Cache::put("telegram_auth_{$this->chat}_user_id", $user->user_id, 3600);

        $this->say('Password123!');

        $user->refresh();
        $this->assertSame((string) $this->chat, (string) $user->telegram_chat_id);
        $this->assertSame(0, (int) $user->failed_login_attempts);
    }
}
