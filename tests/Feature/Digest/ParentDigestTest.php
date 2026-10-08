<?php

namespace Tests\Feature\Digest;

use App\Models\ParentDigest;
use App\Services\Digest\DigestBuilder;
use App\Services\Digest\DigestComposer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

class ParentDigestTest extends TestCase
{
    use MakesAcademicData;

    /** الأحد 2026-10-04 بداية الأسبوع؛ "الآن" = الخميس 2026-10-08 مساءً. */
    private Carbon $weekStart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weekStart = Carbon::parse('2026-10-04')->startOfDay();
        Carbon::setTestNow('2026-10-08 18:00:00');
        config(['digest.enabled' => true, 'digest.use_ai' => false]);
        // أي اتصال خارجي غير مُحاكى (Gemini/FCM/Telegram) يفشل الاختبار بدل أن يخرج للشبكة
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    /** @return array{student: array, parent: array, course_id: int} */
    private function family(array $studentAttrs = []): array
    {
        $dept    = $this->makeDepartment();
        $program = $this->makeProgram($dept);
        $student = $this->makeStudent($studentAttrs + ['full_name' => 'ليان الأحمد'], $program);
        $parent  = $this->makeParent();
        $this->linkParent($parent['user'], $student['user']);
        $course = $this->makeCourse($program, ['title' => 'الشبكات']);
        $this->enroll($student['student_id'], $course);

        return ['student' => $student, 'parent' => $parent, 'course_id' => $course];
    }

    private function lesson(int $courseId): int
    {
        return DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'title' => 'محاضرة ' . $this->nextSeq(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function attend(int $studentId, int $courseId, string $status, string $date, string $excuse = 'none'): void
    {
        DB::table('attendance')->insert([
            'student_id' => $studentId, 'lesson_id' => $this->lesson($courseId),
            'status' => $status, 'attendance_date' => $date, 'excuse_status' => $excuse,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function assignment(int $courseId, string $due): int
    {
        return DB::table('assignments')->insertGetId([
            'course_id' => $courseId, 'title' => 'واجب ' . $this->nextSeq(),
            'due_date' => $due, 'max_points' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function submit(int $assignmentId, int $studentId, string $at): void
    {
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $assignmentId, 'student_id' => $studentId,
            'file_path' => '', 'submitted_at' => $at,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function facts(array $family): array
    {
        return (new DigestBuilder())->build($family['student']['student_id'], $this->weekStart, now());
    }

    // ---------------------------------------------------------------- builder

    public function test_builder_counts_attendance_assignments_and_grades(): void
    {
        $f = $this->family();
        $sid = $f['student']['student_id'];

        $this->attend($sid, $f['course_id'], 'present', '2026-10-04');
        $this->attend($sid, $f['course_id'], 'present', '2026-10-05');
        $this->attend($sid, $f['course_id'], 'late', '2026-10-06');
        $this->attend($sid, $f['course_id'], 'absent', '2026-10-07');
        $this->attend($sid, $f['course_id'], 'absent', '2026-10-08', 'approved');

        $done = $this->assignment($f['course_id'], '2026-10-06 12:00:00');
        $this->submit($done, $sid, '2026-10-05 10:00:00');
        $lateOne = $this->assignment($f['course_id'], '2026-10-06 12:00:00');
        $this->submit($lateOne, $sid, '2026-10-07 10:00:00');
        $this->assignment($f['course_id'], '2026-10-07 12:00:00'); // فائت
        $this->assignment($f['course_id'], '2026-10-12 12:00:00'); // قادم

        $exam = DB::table('exams')->insertGetId([
            'course_id' => $f['course_id'], 'exam_name' => 'نصفي', 'exam_date' => '2026-10-05 09:00:00',
            'max_score' => 50, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('grades')->insert([
            'student_id' => $sid, 'exam_id' => $exam, 'score' => 40,
            'created_at' => '2026-10-06 10:00:00', 'updated_at' => '2026-10-06 10:00:00',
        ]);

        $facts = $this->facts($f);

        $this->assertFalse($facts['empty']);
        $this->assertSame(5, $facts['attendance']['total']);
        $this->assertSame(2, $facts['attendance']['present']);
        $this->assertSame(1, $facts['attendance']['late']);
        $this->assertSame(2, $facts['attendance']['absent']);
        $this->assertSame(1, $facts['attendance']['excused_absent']);
        $this->assertSame(1, $facts['attendance']['unexcused_absent']);
        $this->assertSame(60, $facts['attendance']['rate']);

        $this->assertSame(3, $facts['assignments']['due_this_week']);
        $this->assertSame(2, $facts['assignments']['submitted']);
        $this->assertSame(1, $facts['assignments']['late']);
        $this->assertSame(1, $facts['assignments']['missing']);
        $this->assertSame(1, $facts['assignments']['upcoming']);

        $this->assertSame(1, $facts['grades']['count']);
        $this->assertSame(80.0, $facts['grades']['avg_percent']);
    }

    public function test_builder_reads_grades_from_grade_events_too(): void
    {
        $f = $this->family();
        $teacher = $this->makeTeacher();
        $event = DB::table('grade_events')->insertGetId([
            'teacher_id' => $teacher['teacher_id'], 'course_id' => $f['course_id'],
            'type' => 'quiz', 'title' => 'مذاكرة 1', 'max_score' => 20, 'date' => '2026-10-06',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('grade_entries')->insert([
            'grade_event_id' => $event, 'student_id' => $f['student']['student_id'], 'score' => 15,
            'created_at' => '2026-10-06 10:00:00', 'updated_at' => '2026-10-06 10:00:00',
        ]);

        $facts = $this->facts($f);

        $this->assertSame(1, $facts['grades']['count']);
        $this->assertSame(75.0, $facts['grades']['avg_percent']);
    }

    public function test_tone_is_good_attention_or_concern_by_rules(): void
    {
        $good = $this->family();
        $this->attend($good['student']['student_id'], $good['course_id'], 'present', '2026-10-05');
        $this->assertSame('good', $this->facts($good)['tone']);
        $this->assertSame('celebrate', $this->facts($good)['suggestion']);

        $attention = $this->family();
        $this->attend($attention['student']['student_id'], $attention['course_id'], 'present', '2026-10-05');
        $this->attend($attention['student']['student_id'], $attention['course_id'], 'absent', '2026-10-06');
        $this->assertSame('attention', $this->facts($attention)['tone']);

        $concern = $this->family();
        foreach (['2026-10-04', '2026-10-05', '2026-10-06'] as $d) {
            $this->attend($concern['student']['student_id'], $concern['course_id'], 'absent', $d);
        }
        $facts = $this->facts($concern);
        $this->assertSame('concern', $facts['tone']);
        $this->assertSame('talk_absence', $facts['suggestion']);
    }

    public function test_student_without_any_activity_is_empty(): void
    {
        $this->assertTrue($this->facts($this->family())['empty']);
    }

    // --------------------------------------------------------------- composer

    public function test_template_body_contains_the_numbers_and_the_student_name(): void
    {
        $f = $this->family();
        $this->attend($f['student']['student_id'], $f['course_id'], 'present', '2026-10-05');
        $this->attend($f['student']['student_id'], $f['course_id'], 'absent', '2026-10-06');

        $out = app(DigestComposer::class)->compose($this->facts($f), 'ليان الأحمد');

        $this->assertSame('template', $out['source']);
        $this->assertStringContainsString('ليان', $out['title']);
        $this->assertStringContainsString('1 من 2', $out['body']);
        $this->assertStringContainsString('غياب بدون عذر: 1', $out['body']);
        $this->assertStringNotContainsString('{NAME}', $out['body']);
    }

    private function enableAi(string $reply): void
    {
        config([
            'digest.use_ai' => true,
            'services.gemini.key' => 'K',
            'services.gemini.models' => ['m1'],
        ]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => $reply]]]]],
        ])]);
    }

    private function familyWithOneAbsence(): array
    {
        $f = $this->family();
        $this->attend($f['student']['student_id'], $f['course_id'], 'present', '2026-10-05');
        $this->attend($f['student']['student_id'], $f['course_id'], 'absent', '2026-10-06');

        return $f;
    }

    public function test_faithful_ai_text_is_used_and_never_receives_the_student_name(): void
    {
        $f = $this->familyWithOneAbsence();
        $this->enableAi('هذا الأسبوع حضر {NAME} محاضرة واحدة من أصل 2، وسُجّل غياب واحد. نقترح الحديث معه بهدوء حول الأسباب.');

        $out = app(DigestComposer::class)->compose($this->facts($f), 'ليان الأحمد');

        $this->assertSame('ai', $out['source']);
        $this->assertStringContainsString('ليان', $out['body']);
        $this->assertStringNotContainsString('{NAME}', $out['body']);

        Http::assertSent(function ($request) {
            $payload = json_encode($request->data(), JSON_UNESCAPED_UNICODE);
            return str_contains($request->url(), 'generativelanguage')
                && !str_contains($payload, 'ليان')
                && !str_contains($payload, 'الأحمد');
        });
    }

    public function test_ai_text_with_an_invented_number_falls_back_to_the_template(): void
    {
        $f = $this->familyWithOneAbsence();
        $this->enableAi('حضر {NAME} 7 محاضرات من أصل 9 هذا الأسبوع، ونتمنى له التوفيق في بقية الأسابيع القادمة.');

        $out = app(DigestComposer::class)->compose($this->facts($f), 'ليان الأحمد');

        $this->assertSame('template', $out['source']);
        $this->assertStringContainsString('1 من 2', $out['body']);
    }

    public function test_ai_text_with_links_or_when_gemini_fails_falls_back(): void
    {
        $f = $this->familyWithOneAbsence();

        $this->enableAi('تابعوا التفاصيل على https://evil.example.com بخصوص {NAME} وغيابه الأسبوعي القصير جدا.');
        $this->assertSame('template', app(DigestComposer::class)->compose($this->facts($f), 'ليان')['source']);

        config(['services.gemini.models' => ['m1']]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('boom', 500)]);
        $this->assertSame('template', app(DigestComposer::class)->compose($this->facts($f), 'ليان')['source']);
    }

    // ---------------------------------------------------------------- command

    public function test_command_stores_notifies_and_is_idempotent(): void
    {
        $f = $this->familyWithOneAbsence();

        $this->artisan('digest:send', ['--week' => '2026-10-08', '--now' => true])->assertExitCode(0);

        $digest = ParentDigest::where('parent_user_id', $f['parent']['user']->user_id)->first();
        $this->assertNotNull($digest);
        $this->assertSame($f['student']['student_id'], $digest->student_id);
        $this->assertSame('2026-10-04', $digest->week_start->toDateString());
        $this->assertNotNull($digest->sent_at);
        $this->assertSame('attention', $digest->tone);
        $this->assertSame(1, DB::table('notifications')
            ->where('user_id', $f['parent']['user']->user_id)->where('type', 'weekly_digest')->count());

        // إعادة التشغيل لا تكرّر الملخص ولا الإشعار
        $this->artisan('digest:send', ['--week' => '2026-10-08', '--now' => true])->assertExitCode(0);
        $this->assertSame(1, ParentDigest::count());
        $this->assertSame(1, DB::table('notifications')->where('type', 'weekly_digest')->count());
    }

    public function test_command_works_through_the_queue_by_default(): void
    {
        $f = $this->familyWithOneAbsence();

        $this->artisan('digest:send', ['--week' => '2026-10-08'])->assertExitCode(0);

        $this->assertSame(1, ParentDigest::where('parent_user_id', $f['parent']['user']->user_id)->count());
    }

    public function test_dry_run_saves_and_sends_nothing(): void
    {
        $this->familyWithOneAbsence();

        $this->artisan('digest:send', ['--week' => '2026-10-08', '--dry-run' => true])->assertExitCode(0);

        $this->assertSame(0, ParentDigest::count());
        $this->assertSame(0, DB::table('notifications')->where('type', 'weekly_digest')->count());
    }

    public function test_no_digest_for_quiet_week_or_opted_out_parent_or_disabled_feature(): void
    {
        $quiet = $this->family();
        $optedOut = $this->familyWithOneAbsence();
        DB::table('parents')->where('user_id', $optedOut['parent']['user']->user_id)->update(['digest_enabled' => false]);

        $this->artisan('digest:send', ['--week' => '2026-10-08', '--now' => true])->assertExitCode(0);
        $this->assertSame(0, ParentDigest::count());

        config(['digest.enabled' => false]);
        $active = $this->familyWithOneAbsence();
        $this->artisan('digest:send', ['--week' => '2026-10-08', '--now' => true])->assertExitCode(0);
        $this->assertSame(0, ParentDigest::where('parent_user_id', $active['parent']['user']->user_id)->count());
    }

    public function test_inactive_student_is_skipped(): void
    {
        $f = $this->familyWithOneAbsence();
        DB::table('users')->where('user_id', $f['student']['user']->user_id)->update(['status' => 'inactive']);

        $this->artisan('digest:send', ['--week' => '2026-10-08', '--now' => true])->assertExitCode(0);

        $this->assertSame(0, ParentDigest::count());
    }

    // -------------------------------------------------------------------- API

    private function sentDigestFor(array $family): ParentDigest
    {
        $this->artisan('digest:send', ['--week' => '2026-10-08', '--now' => true]);

        return ParentDigest::where('parent_user_id', $family['parent']['user']->user_id)->firstOrFail();
    }

    public function test_parent_lists_reads_and_marks_own_digest(): void
    {
        $f = $this->familyWithOneAbsence();
        $digest = $this->sentDigestFor($f);
        $this->actAs($f['parent']['user']);

        $this->getJson('/api/parent/digests')->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.id', $digest->id)
            ->assertJsonPath('data.0.is_read', false)
            ->assertJsonPath('data.0.tone', 'attention');

        $this->getJson("/api/parent/digests/{$digest->id}")->assertOk()
            ->assertJsonPath('data.facts.attendance.total', 2);

        $this->putJson("/api/parent/digests/{$digest->id}/read")->assertOk();
        $this->getJson('/api/parent/digests')->assertJsonPath('unread_count', 0)
            ->assertJsonPath('data.0.is_read', true);
    }

    public function test_parent_cannot_see_or_mark_another_parents_digest(): void
    {
        $mine  = $this->familyWithOneAbsence();
        $other = $this->familyWithOneAbsence();
        $otherDigest = $this->sentDigestFor($other);

        $this->actAs($mine['parent']['user']);

        // (الأمر يولّد ملخصًا لكل ولي أمر، فلكل منهما ملخصه الخاص فقط)
        $list = $this->getJson('/api/parent/digests')->assertOk()->assertJsonCount(1, 'data');
        $this->assertNotSame($otherDigest->id, $list->json('data.0.id'));
        $this->getJson("/api/parent/digests/{$otherDigest->id}")->assertNotFound();
        $this->putJson("/api/parent/digests/{$otherDigest->id}/read")->assertNotFound();
        $this->assertNull($otherDigest->fresh()->read_at);
    }

    public function test_only_parents_can_use_the_digest_routes(): void
    {
        $this->actAs($this->makeStudent()['user']);

        $this->getJson('/api/parent/digests')->assertForbidden();
        $this->getJson('/api/parent/digest-settings')->assertForbidden();
    }

    public function test_parent_can_turn_the_digest_off_and_on(): void
    {
        $f = $this->family();
        $this->actAs($f['parent']['user']);

        $this->getJson('/api/parent/digest-settings')->assertOk()->assertJsonPath('data.digest_enabled', true);
        $this->putJson('/api/parent/digest-settings', ['digest_enabled' => false])->assertOk()
            ->assertJsonPath('data.digest_enabled', false);
        $this->assertSame(0, (int) DB::table('parents')->where('user_id', $f['parent']['user']->user_id)->value('digest_enabled'));
        $this->putJson('/api/parent/digest-settings', ['digest_enabled' => 'maybe'])->assertStatus(422);
    }

    // -------------------------------------------------------------------- PDF

    public function test_parent_downloads_own_digest_as_a_real_pdf(): void
    {
        $f = $this->familyWithOneAbsence();
        $digest = $this->sentDigestFor($f);
        $this->actAs($f['parent']['user']);

        $res = $this->get("/api/parent/digests/{$digest->id}/pdf");

        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('weekly_digest_2026-10-04_', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertGreaterThan(2000, strlen($res->getContent()));
    }

    public function test_parent_cannot_download_another_parents_pdf(): void
    {
        $mine  = $this->familyWithOneAbsence();
        $other = $this->familyWithOneAbsence();
        $otherDigest = $this->sentDigestFor($other);

        $this->actAs($mine['parent']['user']);
        $this->getJson("/api/parent/digests/{$otherDigest->id}/pdf")->assertNotFound();
    }

    // -------------------------------------------------------------------- Web

    public function test_web_parent_sees_list_detail_and_pdf_and_reading_marks_it_read(): void
    {
        $f = $this->familyWithOneAbsence();
        $digest = $this->sentDigestFor($f);
        $this->actingAs($f['parent']['user']);

        $this->get('/parent/digests')->assertOk()->assertSee($digest->title);

        $this->assertNull($digest->fresh()->read_at);
        $this->get("/parent/digests/{$digest->id}")->assertOk()->assertSee('تنزيل PDF');
        $this->assertNotNull($digest->fresh()->read_at);

        $pdf = $this->get("/parent/digests/{$digest->id}/pdf");
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_web_parent_cannot_open_another_parents_digest(): void
    {
        $mine  = $this->familyWithOneAbsence();
        $other = $this->familyWithOneAbsence();
        $otherDigest = $this->sentDigestFor($other);

        $this->actingAs($mine['parent']['user']);
        $this->get("/parent/digests/{$otherDigest->id}")->assertNotFound();
        $this->get("/parent/digests/{$otherDigest->id}/pdf")->assertNotFound();
    }

    public function test_web_parent_can_switch_the_digest_off(): void
    {
        $f = $this->family();
        $this->actingAs($f['parent']['user']);

        $this->post('/parent/digest-settings', ['digest_enabled' => 0])->assertRedirect();

        $this->assertSame(0, (int) DB::table('parents')->where('user_id', $f['parent']['user']->user_id)->value('digest_enabled'));
    }

    public function test_web_notifications_page_links_a_digest_notification_to_its_page(): void
    {
        $f = $this->familyWithOneAbsence();
        $digest = $this->sentDigestFor($f);
        $this->actingAs($f['parent']['user']);

        $this->get('/parent/notifications')->assertOk()->assertSee("/parent/digests/{$digest->id}", false);
    }
}
