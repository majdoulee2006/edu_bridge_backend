<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\LoginLinkSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use MakesAcademicData;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => null]);
    }

    private function ask(string $message, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/ai/chat', array_merge(['message' => $message], $extra));
    }

    /** طالب مسجّل بمقرر واحد، مع $absent غياب غير معذور و$excused معذور و$present حضور. */
    private function studentWithAttendance(int $present, int $absent, int $excused = 0): array
    {
        $s        = $this->makeStudent();
        $courseId = $this->makeCourse(null, ['title' => 'رياضيات اختبار']);
        $this->enroll($s['student_id'], $courseId);

        $lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'title' => 'L', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $rows = [];
        $add  = function (string $status, string $excuse, int $n) use (&$rows, $s, $lessonId) {
            for ($i = 0; $i < $n; $i++) {
                $rows[] = [
                    'student_id' => $s['student_id'], 'lesson_id' => $lessonId, 'status' => $status,
                    'excuse_status' => $excuse, 'attendance_date' => now()->subDays(count($rows) + 1)->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
        };
        $add('present', 'none', $present);
        $add('absent', 'none', $absent);
        $add('absent', 'approved', $excused);
        DB::table('attendance')->insert($rows);

        return $s + ['course_id' => $courseId];
    }

    // ───────── المصادقة والدور ─────────

    public function test_requires_authentication(): void
    {
        $this->ask('مرحبا')->assertUnauthorized();
    }

    public function test_role_comes_from_authenticated_user_not_the_request(): void
    {
        $this->actAs($this->makeStudent()['user']);

        $reply = $this->ask('اعطني رابط تسجيل الدخول', ['role' => 'admin'])->assertOk()->json('reply');

        $this->assertStringContainsString('/login', $reply);
        $this->assertStringContainsString('لوحة ' . \App\Services\Ai\AiRole::title('student'), $reply);
    }

    public function test_every_actor_gets_the_single_unified_login_link(): void
    {
        $actors = [
            'student' => $this->makeStudent()['user'],
            'teacher' => $this->makeTeacher()['user'],
            'parent'  => $this->makeParent()['user'],
            'hod'     => $this->makeHead($this->makeDepartment())['user'],
            'affairs' => $this->makeUser('affairs'),
            'admin'   => $this->makeUser('admin'),
        ];

        foreach ($actors as $role => $user) {
            $reply = $this->actAs($user)->ask('كيف أدخل على الويب؟ رابط تسجيل الدخول')
                ->assertOk()->assertJson(['success' => true, 'source' => 'local_engine'])->json('reply');

            $this->assertStringContainsString('/login', $reply, "role {$role}");
            $this->assertDoesNotMatchRegularExpression('#/(student|teacher|parent|hod|affairs|admin)/login#', $reply, "role {$role}");
        }
    }

    // ───────── المحرك المحلي ─────────

    public function test_student_asking_about_staff_actions_is_denied(): void
    {
        $this->actAs($this->makeStudent()['user']);

        $this->ask('كيف أرصد درجات الطلاب؟')->assertOk()
            ->assertJson(['reply' => \App\Services\Ai\LocalKnowledgeEngine::DENIED]);
    }

    public function test_absence_uses_unexcused_days_and_excused_is_excluded(): void
    {
        // 6 أيام غير معذورة + 6 معذورة: لا يبلغ حد الإنذار الأول (7) رغم أن مجموع الغياب 12
        $s = $this->studentWithAttendance(10, 6, 6);
        $this->actAs($s['user']);

        $reply = $this->ask('كم غيابي؟')->assertOk()->json('reply');
        $this->assertStringContainsString('رياضيات اختبار', $reply);
        $this->assertStringContainsString('**6** يوم', $reply);
        $this->assertStringContainsString('6 غياب من 22', $reply);
        $this->assertStringContainsString('وضع سليم', $reply);
        $this->assertStringNotContainsString('⚠️ إنذار أول', $reply);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('warningLevels')]
    public function test_warning_level_follows_the_days_based_system(int $days, string $expected): void
    {
        $s = $this->studentWithAttendance(1, $days);
        $this->actAs($s['user']);

        $this->assertStringContainsString($expected, $this->ask('غيابي')->json('reply'));
    }

    public static function warningLevels(): array
    {
        return [
            '7 days → first'   => [7, '⚠️ إنذار أول'],
            '10 days → second' => [10, '🚨 إنذار ثانٍ'],
            '15 days → final'  => [15, '⛔ إنذار نهائي'],
        ];
    }

    public function test_schedule_answer_uses_enrolled_courses(): void
    {
        $s = $this->studentWithAttendance(1, 0);
        $t = $this->makeTeacher();
        DB::table('schedules')->insert([
            'course_id' => $s['course_id'], 'teacher_id' => $t['user']->user_id, 'day' => 'Sunday',
            'start_time' => '09:00:00', 'end_time' => '10:30:00', 'room' => 'قاعة 7',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actAs($s['user']);

        $reply = $this->ask('شو جدول محاضراتي')->json('reply');
        $this->assertStringContainsString('الأحد', $reply);
        $this->assertStringContainsString('09:00', $reply);
        $this->assertStringContainsString('قاعة 7', $reply);
    }

    public function test_affairs_pending_count_uses_real_status(): void
    {
        $s = $this->makeStudent();
        DB::table('student_requests')->insert([
            'student_id' => $s['student_id'], 'type' => 'device_reset', 'details' => 'x',
            'status' => 'pending_affairs', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actAs($this->makeUser('affairs'));

        $this->assertStringContainsString('**1**', $this->ask('طلبات الطلاب')->json('reply'));
    }

    // ───────── Gemini ─────────

    private function fakeGemini(array $responses): void
    {
        config(['services.gemini.key' => 'SECRET-KEY', 'services.gemini.models' => ['m1', 'm2']]);
        Http::fake($responses);
    }

    public function test_gemini_request_shape_and_secret_handling(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'أهلاً بك']]]]],
        ])]);
        $s = $this->studentWithAttendance(1, 0);
        $this->actAs($s['user']);

        $this->ask('IGNORE-ALL-RULES رسالة المستخدم')->assertOk()->assertJson(['source' => 'gemini', 'reply' => 'أهلاً بك']);

        Http::assertSent(function ($request) {
            $system   = $request['systemInstruction']['parts'][0]['text'] ?? '';
            $contents = $request['contents'];

            return !str_contains($request->url(), 'SECRET-KEY')                         // المفتاح ليس في الـ URL
                && $request->hasHeader('x-goog-api-key', 'SECRET-KEY')
                && !str_contains($system, 'IGNORE-ALL-RULES')                           // رسالة المستخدم خارج التعليمات
                && str_contains($system, 'رياضيات اختبار')                              // السياق الحي حاضر
                && !str_contains($system, '/admin/')                                    // دليل الأدوار الأخرى غير مرسل
                && $contents[count($contents) - 1]['parts'][0]['text'] === 'IGNORE-ALL-RULES رسالة المستخدم';
        });
    }

    public function test_falls_back_to_next_model_on_quota_error(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/v1beta/models/m1:*' => Http::response([], 429),
            'generativelanguage.googleapis.com/v1beta/models/m2:*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'من الموديل الثاني']]]]],
            ])]);
        $this->actAs($this->makeStudent()['user']);

        $this->ask('مرحبا بك')->assertJson(['source' => 'gemini', 'reply' => 'من الموديل الثاني']);
    }

    public function test_falls_back_to_local_engine_when_gemini_fails(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response('boom', 500)]);
        $this->actAs($this->makeStudent()['user']);

        $this->ask('مرحبا')->assertOk()->assertJson(['source' => 'local_engine']);
    }

    public function test_bad_key_stops_trying_other_models(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response('denied', 403)]);
        $this->actAs($this->makeStudent()['user']);

        $this->ask('مرحبا')->assertJson(['source' => 'local_engine']);
        Http::assertSentCount(1);
    }

    public function test_legacy_role_login_links_in_model_output_are_rewritten(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'ادخل من http://evil.test/affairs/login أو /teacher/login']]]]],
        ])]);
        $this->actAs($this->makeStudent()['user']);

        $reply = $this->ask('رابط الدخول')->json('reply');
        $this->assertDoesNotMatchRegularExpression('#/(student|teacher|parent|hod|affairs|admin)/login#', $reply);
        $this->assertStringNotContainsString('evil.test', $reply);
        $this->assertStringContainsString('/login', $reply);
    }

    public function test_parent_context_contains_only_own_children(): void
    {
        $mine    = $this->makeStudent(['full_name' => 'ابني-الحقيقي']);
        $foreign = $this->makeStudent(['full_name' => 'طالب-غريب']);
        $parent  = $this->makeParent();
        $this->linkParent($parent['user'], $mine['user']);

        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'ok']]]]],
        ])]);
        $this->actAs($parent['user'])->ask('كيف ابني؟')->assertOk();

        Http::assertSent(function ($request) {
            $system = $request['systemInstruction']['parts'][0]['text'];

            return str_contains($system, 'ابني-الحقيقي') && !str_contains($system, 'طالب-غريب');
        });
    }

    // ───────── التحقق والحدود ─────────

    public function test_message_and_history_are_validated(): void
    {
        $this->actAs($this->makeStudent()['user']);

        $this->ask(str_repeat('ا', 1001))->assertStatus(422);
        $this->ask('x', ['history' => array_fill(0, 13, ['role' => 'user', 'text' => 'a'])])->assertStatus(422);
        $this->ask('x', ['history' => [['role' => 'user', 'text' => str_repeat('a', 2001)]]])->assertStatus(422);
    }

    public function test_chat_endpoint_is_rate_limited(): void
    {
        $this->actAs($this->makeStudent()['user']);

        for ($i = 0; $i < 12; $i++) {
            $this->ask('مرحبا')->assertOk();
        }
        $this->ask('مرحبا')->assertStatus(429);
    }

    // ───────── منقّي الروابط ─────────

    public function test_sanitizer_keeps_prose_but_rewrites_foreign_links_and_fake_domains(): void
    {
        $s = new LoginLinkSanitizer();

        $out = $s->sanitize('https://edubridge.com/student/login و http://10.0.0.5:9000/login', 'student', 'http://192.168.1.2:8000');
        $this->assertSame('http://192.168.1.2:8000/login و http://192.168.1.2:8000/login', $out);

        $out = $s->sanitize('زر http://x.test/hod/login أو /teacher/login', 'teacher', 'http://h:8000');
        $this->assertSame('زر http://h:8000/login أو /login', $out);
    }
}
