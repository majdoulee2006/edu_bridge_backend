<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TelegramBotHandler;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * "Crawler": for each role, builds a database with realistic data (courses, lessons, attendance, assignments,
 * leaves, requests, photo requests, meetings...), presses every button of the role's real keyboard and then every
 * inline button that appears in the replies (lists -> details -> approve/reject...), until nothing new shows up.
 * Any handler that throws (wrong column, null access...) fails the test, and every press must answer.
 */
class TelegramServicesCrawlerTest extends TestCase
{
    use MakesAcademicData;

    private int $chat = 9300;

    /** @var array<string, mixed> */
    private array $world = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->buildWorld();
    }

    // ───────────────────────── world ─────────────────────────

    private function buildWorld(): void
    {
        $deptName = 'قسم الحاسوب';
        $dept     = $this->makeDepartment($deptName);
        $program  = $this->makeProgram($dept);
        $course   = $this->makeCourse($program, ['title' => 'Course One']);

        $teacher  = $this->makeTeacher(['department' => $deptName, 'full_name' => 'Teacher One']);
        $this->assignTeacher($course, $teacher['teacher_id']);

        $student  = $this->makeStudent(['department' => $deptName, 'full_name' => 'Student One', 'university_id' => 'U1'], $program);
        $student2 = $this->makeStudent(['department' => $deptName, 'full_name' => 'Student Two', 'university_id' => 'U2'], $program);
        $this->enroll($student['student_id'], $course);
        $this->enroll($student2['student_id'], $course);

        $parent = $this->makeParent(['full_name' => 'Parent One']);
        $this->linkParent($parent['user'], $student['user']);

        $head    = $this->makeHead($dept, ['department' => $deptName, 'full_name' => 'Head One']);
        $affairs = $this->makeUser('affairs', ['full_name' => 'Affairs One']);
        $admin   = $this->makeUser('admin', ['full_name' => 'Admin One']);

        $lesson = DB::table('lessons')->insertGetId(['course_id' => $course, 'title' => 'Lesson One', 'content_url' => 'https://example.test/v', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('resources')->insert(['course_id' => $course, 'resource_name' => 'Res One', 'file_path' => 'resources/none.pdf', 'created_at' => now(), 'updated_at' => now()]);

        $att = fn (int $studentId, string $status, array $extra = []) => DB::table('attendance')->insertGetId(array_merge([
            'student_id' => $studentId, 'lesson_id' => $lesson, 'status' => $status,
            'attendance_date' => now()->subDays(rand(1, 5))->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ], $extra));
        $att($student['student_id'], 'present');
        $att($student['student_id'], 'absent');
        $att($student['student_id'], 'absent', ['excuse_text' => 'sick', 'excuse_status' => 'pending']);
        $att($student2['student_id'], 'absent', ['excuse_text' => 'travel', 'excuse_status' => 'pending']);

        DB::table('attendance_sessions')->insert(['lesson_id' => $lesson, 'qr_token' => bin2hex(random_bytes(8)), 'expires_at' => now()->addHour(), 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('schedules')->insert(['course_id' => $course, 'teacher_id' => $teacher['user']->user_id, 'day' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '10:30:00', 'room' => 'R1', 'created_at' => now(), 'updated_at' => now()]);

        $assignment = DB::table('assignments')->insertGetId(['course_id' => $course, 'teacher_id' => $teacher['teacher_id'], 'title' => 'Homework One', 'due_date' => now()->addDays(3), 'max_points' => 20, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('assignment_submissions')->insert(['assignment_id' => $assignment, 'student_id' => $student['student_id'], 'file_path' => 'x.pdf', 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $exam = DB::table('exams')->insertGetId(['course_id' => $course, 'exam_name' => 'Midterm', 'exam_date' => now()->addWeek(), 'max_score' => 100, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('grades')->insert(['student_id' => $student['student_id'], 'exam_id' => $exam, 'score' => 80, 'created_at' => now(), 'updated_at' => now()]);

        foreach (['pending_hod', 'pending_affairs', 'pending_parent'] as $status) {
            DB::table('leave_requests')->insert(['student_id' => $student['user']->user_id, 'type' => 'full_day', 'date' => now()->toDateString(), 'reason' => 'r', 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['pending_affairs', 'pending_hod', 'pending_admin'] as $status) {
            DB::table('student_requests')->insert(['student_id' => $student['student_id'], 'type' => 'certificate', 'details' => 'd', 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('photo_change_requests')->insert(['user_id' => $student['user']->user_id, 'old_photo' => null, 'new_photo' => 'photo_requests/x.jpg', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('parent_meeting_requests')->insert(['parent_user_id' => $parent['user']->user_id, 'student_id' => $student['student_id'], 'subject' => 'Meeting', 'reason' => 'r', 'preferred_date' => now()->addDay()->toDateString(), 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        $this->world = compact('teacher', 'student', 'student2', 'parent', 'head', 'affairs', 'admin');
    }

    private function actor(string $role): User
    {
        $user = match ($role) {
            'student' => $this->world['student']['user'],
            'parent'  => $this->world['parent']['user'],
            'teacher' => $this->world['teacher']['user'],
            'head'    => $this->world['head']['user'],
            'affairs' => $this->world['affairs'],
            'admin'   => $this->world['admin'],
        };
        $user->forceFill(['telegram_chat_id' => (string) $this->chat])->save();

        return $user;
    }

    // ───────────────────────── bot plumbing ─────────────────────────

    private function fake(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)]);
    }

    private function say(string $text): void
    {
        (new TelegramBotHandler())->handleUpdate(['message' => ['chat' => ['id' => $this->chat], 'text' => $text]]);
    }

    private function press(string $data): void
    {
        (new TelegramBotHandler())->handleUpdate([
            'callback_query' => ['id' => 'q', 'data' => $data, 'message' => ['chat' => ['id' => $this->chat]]],
        ]);
    }

    private function telegramRequests()
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.telegram.org'));
    }

    private function markup($payload): array
    {
        $markup = $payload['reply_markup'] ?? null;

        return is_string($markup) ? (json_decode($markup, true) ?: []) : (is_array($markup) ? $markup : []);
    }

    /** @return string[] */
    private function menuLabels(): array
    {
        $this->fake();
        $this->say('/start');
        foreach ($this->telegramRequests()->reverse() as $pair) {
            $kb = $this->markup($pair[0]->data())['keyboard'] ?? null;
            if ($kb) {
                return array_values(array_map(fn ($b) => $b['text'], array_merge(...$kb)));
            }
        }

        return [];
    }

    /** @return string[] callback_data values of the inline buttons in the replies sent since the last fake() */
    private function inlineCallbacks(): array
    {
        $found = [];
        foreach ($this->telegramRequests() as $pair) {
            foreach ($this->markup($pair[0]->data())['inline_keyboard'] ?? [] as $row) {
                foreach ($row as $btn) {
                    if (!empty($btn['callback_data'])) {
                        $found[] = $btn['callback_data'];
                    }
                }
            }
        }

        return $found;
    }

    // ───────────────────────── the crawl ─────────────────────────

    public static function roles(): array
    {
        return [['student'], ['parent'], ['teacher'], ['head'], ['affairs'], ['admin']];
    }

    #[DataProvider('roles')]
    public function test_every_service_and_every_inline_button_works_with_real_data(string $role): void
    {
        $this->actor($role);
        $queue = [];

        foreach ($this->menuLabels() as $label) {
            if (str_contains($label, 'تسجيل خروج')) {
                continue;
            }
            $this->fake();
            $this->say($label);
            $this->assertGreaterThan(0, $this->telegramRequests()->count(), "[$role] button «{$label}» sent nothing");
            array_push($queue, ...$this->inlineCallbacks());
        }

        $seen = [];
        while ($queue && count($seen) < 250) {
            $data = array_shift($queue);
            if (isset($seen[$data])) {
                continue;
            }
            $seen[$data] = true;

            $this->fake();
            $this->press($data);
            $this->assertGreaterThan(0, $this->telegramRequests()->count(), "[$role] inline button «{$data}» sent nothing");
            array_push($queue, ...$this->inlineCallbacks());
        }

        $this->assertGreaterThanOrEqual(3, count($seen), "[$role] the crawl reached too few inline buttons: " . implode(', ', array_keys($seen)));
    }
}
