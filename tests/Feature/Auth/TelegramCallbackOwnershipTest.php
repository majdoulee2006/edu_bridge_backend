<?php

namespace Tests\Feature\Auth;

use App\Services\TelegramBotHandler;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * Telegram bot: a button carries a record id, and the handlers used to load it with Course::find($id),
 * Student::find($id)... without checking that it belongs to the caller (IDOR).
 * CallbackAuthorization is the single check; these tests pin each rule.
 */
class TelegramCallbackOwnershipTest extends TestCase
{
    use MakesAcademicData;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)]);
        Cache::flush();
    }

    private function press(int $chatId, string $data): void
    {
        (new TelegramBotHandler())->handleUpdate([
            'callback_query' => ['id' => 'q1', 'data' => $data, 'message' => ['chat' => ['id' => $chatId]]],
        ]);
    }

    /** كل ما أرسله البوت لتيليغرام كنص واحد (للبحث عن اسم داخله). */
    private function sentToTelegram(): string
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.telegram.org'))
            ->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))
            ->implode("\n");
    }

    private function lessonFor(int $courseId, string $title = 'Lesson'): int
    {
        return DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'title' => $title, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ───────────── ولي الأمر ─────────────

    public function test_parent_sees_only_his_own_children(): void
    {
        $mine  = $this->makeStudent(['full_name' => 'ChildAlpha']);
        $other = $this->makeStudent(['full_name' => 'ChildBeta']);
        $parent = $this->makeParent(['telegram_chat_id' => '7001']);
        $this->linkParent($parent['user'], $mine['user']);

        $this->press(7001, "parent_student_grades_{$other['student_id']}");
        $this->press(7001, "parent_student_attendance_{$other['student_id']}");
        $this->assertStringNotContainsString('ChildBeta', $this->sentToTelegram());

        $this->press(7001, "parent_student_grades_{$mine['student_id']}");
        $this->assertStringContainsString('ChildAlpha', $this->sentToTelegram());
    }

    // ───────────── الطالب ─────────────

    public function test_student_can_only_start_an_excuse_for_his_own_absence(): void
    {
        $me    = $this->makeStudent(['telegram_chat_id' => '7101']);
        $other = $this->makeStudent();
        $lesson = $this->lessonFor($this->makeCourse());

        $mk = fn (int $studentId) => DB::table('attendance')->insertGetId([
            'student_id' => $studentId, 'lesson_id' => $lesson, 'status' => 'absent',
            'attendance_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $others = $mk($other['student_id']);
        $mine   = $mk($me['student_id']);

        $this->press(7101, "excuse_start_{$others}");
        $this->assertNull(Cache::get('telegram_state_7101'));

        $this->press(7101, "excuse_start_{$mine}");
        $this->assertSame("awaiting_excuse_text_{$mine}", Cache::get('telegram_state_7101'));
    }

    public function test_student_only_gets_materials_of_courses_he_is_enrolled_in(): void
    {
        $me = $this->makeStudent(['telegram_chat_id' => '7102']);
        $enrolled = $this->makeCourse();
        $foreign  = $this->makeCourse();
        $this->enroll($me['student_id'], $enrolled);
        $this->lessonFor($enrolled, 'LessonMine');
        $foreignLesson = $this->lessonFor($foreign, 'LessonForeign');

        $this->press(7102, "course_lectures_{$foreign}");
        $this->press(7102, "lesson_details_{$foreignLesson}");
        $this->assertStringNotContainsString('LessonForeign', $this->sentToTelegram());

        $this->press(7102, "course_lectures_{$enrolled}");
        $this->assertStringContainsString('LessonMine', $this->sentToTelegram());
    }

    // ───────────── المعلم ─────────────

    public function test_teacher_cannot_touch_another_teachers_course_students_or_sessions(): void
    {
        $me    = $this->makeTeacher(['telegram_chat_id' => '7201']);
        $other = $this->makeTeacher();
        $mine  = $this->makeCourse();
        $theirs = $this->makeCourse();
        $this->assignTeacher($mine, $me['teacher_id']);
        $this->assignTeacher($theirs, $other['teacher_id']);

        $student = $this->makeStudent(['full_name' => 'SecretStudent']);
        $this->enroll($student['student_id'], $theirs);

        $theirLesson = $this->lessonFor($theirs);
        $myLesson    = $this->lessonFor($mine);
        $session = fn (int $lesson) => DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $lesson, 'qr_token' => bin2hex(random_bytes(8)), 'expires_at' => now()->addHour(),
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $theirSession = $session($theirLesson);
        $mySession    = $session($myLesson);

        $this->press(7201, "teacher_course_students_{$theirs}");
        $this->assertStringNotContainsString('SecretStudent', $this->sentToTelegram());

        $this->press(7201, "teacher_end_session_{$theirSession}");
        $this->assertSame(1, (int) DB::table('attendance_sessions')->where('id', $theirSession)->value('is_active'));

        $this->press(7201, "teacher_end_session_{$mySession}");
        $this->assertSame(0, (int) DB::table('attendance_sessions')->where('id', $mySession)->value('is_active'));
    }

    public function test_teacher_cannot_grade_a_submission_of_another_teachers_assignment(): void
    {
        $me    = $this->makeTeacher(['telegram_chat_id' => '7202']);
        $other = $this->makeTeacher();
        $theirs = $this->makeCourse();
        $this->assignTeacher($theirs, $other['teacher_id']);

        $assignment = DB::table('assignments')->insertGetId([
            'course_id' => $theirs, 'teacher_id' => $other['teacher_id'], 'title' => 'HW', 'due_date' => now()->addDay(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = $this->makeStudent(['full_name' => 'SubmitterName']);
        $submission = DB::table('assignment_submissions')->insertGetId([
            'assignment_id' => $assignment, 'student_id' => $student['student_id'], 'file_path' => 'x.pdf',
            'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->press(7202, "teacher_grade_sub_{$submission}");
        $this->press(7202, "teacher_assignment_submissions_{$assignment}");

        $this->assertStringNotContainsString('SubmitterName', $this->sentToTelegram());
    }

    // ───────────── رئيس القسم ─────────────

    public function test_head_can_only_decide_on_requests_of_his_own_department(): void
    {
        $dept = $this->makeDepartment();
        $head = $this->makeHead($dept, ['telegram_chat_id' => '7301', 'department' => 'قسم الحاسوب']);

        $inDept  = $this->makeStudent(['department' => 'قسم الحاسوب']);
        $outDept = $this->makeStudent(['department' => 'قسم الميكانيك']);
        $mk = fn (int $studentId) => DB::table('student_requests')->insertGetId([
            'student_id' => $studentId, 'type' => 'certificate', 'details' => 'x', 'status' => 'pending_hod',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $foreign = $mk($outDept['student_id']);
        $own     = $mk($inDept['student_id']);

        $this->press(7301, "hod_approve_req_{$foreign}");
        $this->assertNull(DB::table('student_requests')->where('id', $foreign)->value('hod_decision'));

        $this->press(7301, "hod_approve_req_{$own}");
        $this->assertSame('approved', DB::table('student_requests')->where('id', $own)->value('hod_decision'));
    }
}
