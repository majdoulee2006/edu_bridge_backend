<?php

namespace Tests\Feature\Attendance;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * S-14: ماسح تيليغرام لا يعتمد على chat_id يرسله العميل، بل على رابط موقّع ورمز يصدرهما السيرفر.
 */
class TelegramScannerTest extends TestCase
{
    use MakesAcademicData;

    private array $studentA;
    private array $studentB;
    private int $lessonId;
    private string $token = 'TGQRTOKEN0123456789012345678901234';

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('local');   // لا نكتب صور اختبار في التخزين الحقيقي
        Http::fake();   // لا اتصال حقيقي بتيليغرام

        $dept    = $this->makeDepartment();
        $program = $this->makeProgram($dept);
        $teacher = $this->makeTeacher();

        $this->studentA = $this->makeStudent(['academic_year' => 'السنة الأولى', 'telegram_chat_id' => '1001'], $program);
        $this->studentB = $this->makeStudent(['academic_year' => 'السنة الأولى', 'telegram_chat_id' => '1002'], $program);
        foreach ([$this->studentA, $this->studentB] as $s) {
            DB::table('students')->where('student_id', $s['student_id'])->update(['level' => 'السنة الأولى']);
        }

        $courseId = $this->makeCourse($program, ['year' => 1]);
        $this->assignTeacher($courseId, $teacher['teacher_id']);
        $this->enroll($this->studentA['student_id'], $courseId);
        $this->enroll($this->studentB['student_id'], $courseId);

        $this->lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'teacher_id' => $teacher['teacher_id'],
            'title' => 'S', 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('attendance_sessions')->insert([
            'lesson_id' => $this->lessonId, 'qr_token' => $this->token,
            'expires_at' => now()->addMinutes(5), 'session_expires_at' => now()->addMinutes(10),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function tokenFor(string $chatId, int $ttlMinutes = 15): string
    {
        return Crypt::encryptString(json_encode([
            'chat_id' => $chatId, 'exp' => now()->addMinutes($ttlMinutes)->timestamp,
        ]));
    }

    public function test_scanner_page_requires_a_signed_link(): void
    {
        $this->get('/telegram/scanner?chat_id=1001')->assertForbidden();
    }

    public function test_scanner_page_opens_with_signed_link_and_embeds_a_token(): void
    {
        $path = URL::temporarySignedRoute('telegram.scanner', now()->addMinutes(15), ['chat_id' => '1001'], false);

        $this->get($path)->assertOk()->assertSee('scannerToken', false);
    }

    public function test_expired_signed_link_is_rejected(): void
    {
        $path = URL::temporarySignedRoute('telegram.scanner', now()->subMinute(), ['chat_id' => '1001'], false);

        $this->get($path)->assertForbidden();
    }

    public function test_recording_without_a_valid_token_is_rejected(): void
    {
        // كان يكفي إرسال chat_id لتسجيل حضور أي طالب
        $this->postJson('/telegram/record-attendance', ['chat_id' => '1001', 'qr_token' => $this->token])
            ->assertStatus(422);

        $this->postJson('/telegram/record-attendance', ['scanner_token' => 'garbage', 'qr_token' => $this->token])
            ->assertForbidden();

        $this->postJson('/telegram/record-attendance', ['scanner_token' => $this->tokenFor('1001', -1), 'qr_token' => $this->token])
            ->assertForbidden();

        $this->assertSame(0, DB::table('attendance')->count());
    }

    public function test_identity_comes_from_the_token_not_from_the_request(): void
    {
        $this->postJson('/telegram/record-attendance', [
            'scanner_token' => $this->tokenFor('1001'),   // الطالب A
            'chat_id'       => '1002',                     // محاولة انتحال الطالب B
            'qr_token'      => $this->token,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendance', ['student_id' => $this->studentA['student_id'], 'lesson_id' => $this->lessonId]);
        $this->assertDatabaseMissing('attendance', ['student_id' => $this->studentB['student_id']]);
    }

    public function test_scan_without_face_is_flagged_not_verified(): void
    {
        $this->postJson('/telegram/record-attendance', [
            'scanner_token' => $this->tokenFor('1001'), 'qr_token' => $this->token,
        ])->assertOk();

        $this->assertSame('suspicious', DB::table('attendance')
            ->where('student_id', $this->studentA['student_id'])->value('face_status'));
    }
}
