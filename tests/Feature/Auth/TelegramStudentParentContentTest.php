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
 * Student and parent services show the right data: today's timetable, grades, absences, children.
 */
class TelegramStudentParentContentTest extends TestCase
{
    use MakesAcademicData;

    private int $chat = 9600;
    private array $student;
    private int $course;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)]);

        $program = $this->makeProgram($this->makeDepartment('قسم الحاسوب'));
        $this->course  = $this->makeCourse($program, ['title' => 'Content Course']);
        $this->student = $this->makeStudent(['full_name' => 'Content Student'], $program);
        $this->enroll($this->student['student_id'], $this->course);
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

    private function sent(): string
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.telegram.org'))
            ->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))
            ->implode("\n");
    }

    private function loginStudent(): void
    {
        $this->student['user']->forceFill(['telegram_chat_id' => (string) $this->chat])->save();
    }

    private function addExamGrade(): void
    {
        $exam = DB::table('exams')->insertGetId([
            'course_id' => $this->course, 'exam_name' => 'Midterm X', 'exam_date' => now()->subDay(), 'max_score' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('grades')->insert(['student_id' => $this->student['student_id'], 'exam_id' => $exam, 'score' => 83, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_student_sees_todays_timetable(): void
    {
        $this->loginStudent();
        $teacher = $this->makeTeacher();
        DB::table('schedules')->insert([
            'course_id' => $this->course, 'teacher_id' => $teacher['user']->user_id, 'day' => now()->format('l'),
            'start_time' => '09:00:00', 'end_time' => '10:30:00', 'room' => 'Room 9', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->say('جدولي');

        $this->assertStringContainsString('Content Course', $this->sent());
        $this->assertStringContainsString('Room 9', $this->sent());
    }

    public function test_student_sees_his_grades(): void
    {
        $this->loginStudent();
        $this->addExamGrade();

        $this->say('علاماتي');

        $this->assertStringContainsString('Midterm X', $this->sent());
        $this->assertStringContainsString('83', $this->sent());
    }

    public function test_student_absences_list_the_absent_lessons_with_an_excuse_button(): void
    {
        $this->loginStudent();
        $lesson = DB::table('lessons')->insertGetId(['course_id' => $this->course, 'title' => 'L', 'created_at' => now(), 'updated_at' => now()]);
        $att = DB::table('attendance')->insertGetId([
            'student_id' => $this->student['student_id'], 'lesson_id' => $lesson, 'status' => 'absent',
            'attendance_date' => now()->subDay()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->say('غياباتي');

        $this->assertStringContainsString("excuse_start_{$att}", $this->sent());
        $this->assertStringContainsString('Content Course', $this->sent());
    }

    public function test_student_materials_list_shows_the_lessons_of_enrolled_courses(): void
    {
        $this->loginStudent();
        DB::table('lessons')->insert(['course_id' => $this->course, 'title' => 'Lesson Alpha', 'created_at' => now(), 'updated_at' => now()]);

        $this->say('محاضراتي');
        $this->assertStringContainsString("course_lectures_{$this->course}", $this->sent());

        $this->press("course_lectures_{$this->course}");
        $this->assertStringContainsString('Lesson Alpha', $this->sent());
    }

    public function test_parent_sees_his_children_and_their_grades(): void
    {
        $parent = $this->makeParent(['telegram_chat_id' => (string) $this->chat]);
        $this->linkParent($parent['user'], $this->student['user']);
        $this->addExamGrade();

        $this->say('أبنائي');
        $this->assertStringContainsString('Content Student', $this->sent());

        $this->say('علامات أبنائي');
        $this->assertStringContainsString("parent_student_grades_{$this->student['student_id']}", $this->sent());

        $this->press("parent_student_grades_{$this->student['student_id']}");
        $this->assertStringContainsString('Midterm X', $this->sent());
    }

    public function test_parent_without_children_gets_a_clear_message(): void
    {
        $this->makeParent(['telegram_chat_id' => (string) $this->chat]);

        $this->say('أبنائي');

        $this->assertStringContainsString('لا يوجد أبناء', $this->sent());
    }
}
