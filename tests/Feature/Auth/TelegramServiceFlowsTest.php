<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TelegramBotHandler;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * Behaviour of the Telegram bot services that take typed input or make decisions: student leave / excuse,
 * teacher assignment / grading / sessions / leave, affairs and admin decisions, searches and broadcasts.
 * Decision tests also pin the workflow stage rules (a decision is allowed only in its own stage).
 */
class TelegramServiceFlowsTest extends TestCase
{
    use MakesAcademicData;

    private int $chat = 9500;
    private string $dept = 'قسم الحاسوب';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
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

    private function sent(): string
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.telegram.org'))
            ->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))
            ->implode("\n");
    }

    private function actingChat(User $user): User
    {
        $user->forceFill(['telegram_chat_id' => (string) $this->chat])->save();

        return $user;
    }

    private function notifications(int $userId, ?string $type = null): int
    {
        $q = DB::table('notifications')->where('user_id', $userId);

        return $type ? $q->where('type', $type)->count() : $q->count();
    }

    private function courseWithTeacher(array $teacher): int
    {
        $course = $this->makeCourse($this->makeProgram($this->makeDepartment($this->dept . ' ' . $this->nextSeq())));
        $this->assignTeacher($course, $teacher['teacher_id']);

        return $course;
    }

    // ═════════════════════════ الطالب ═════════════════════════

    public function test_student_leave_request_flow_creates_a_request_and_notifies_the_parent(): void
    {
        $student = $this->makeStudent(['full_name' => 'Student One']);
        $parent  = $this->makeParent();
        $this->linkParent($parent['user'], $student['user']);
        $this->actingChat($student['user']);

        $this->press('leave_type_full_day');
        $this->say('2026-12-01');
        $this->say('مراجعة طبية');

        $row = DB::table('absence_requests')->where('student_id', $student['student_id'])->first();
        $this->assertNotNull($row, 'no leave request was stored');
        $this->assertSame('pending_parent', $row->status);
        $this->assertSame('2026-12-01', (string) $row->date);
        $this->assertStringContainsString('مراجعة طبية', $row->reason);
        $this->assertSame(1, $this->notifications($parent['user']->user_id, 'leave_request'));
        $this->assertNull(Cache::get("telegram_state_{$this->chat}"));
    }

    public function test_student_hourly_leave_keeps_the_chosen_hours(): void
    {
        $student = $this->makeStudent();
        $this->actingChat($student['user']);

        $this->press('leave_type_hourly');
        $this->say('2026-12-02');
        $this->press('leave_hours_10:00 - 12:00');
        $this->say('موعد رسمي');

        $reason = DB::table('absence_requests')->where('student_id', $student['student_id'])->value('reason');
        $this->assertStringContainsString('10:00 - 12:00', (string) $reason);
    }

    public function test_student_leave_rejects_an_invalid_date(): void
    {
        $student = $this->makeStudent();
        $this->actingChat($student['user']);

        $this->press('leave_type_full_day');
        $this->say('not a date');

        $this->assertSame('awaiting_leave_date', Cache::get("telegram_state_{$this->chat}"));
        $this->assertSame(0, DB::table('absence_requests')->count());
    }

    public function test_student_excuse_flow_marks_the_absence_pending_and_notifies_affairs(): void
    {
        $student = $this->makeStudent(['full_name' => 'Student One']);
        $affairs = $this->makeUser('affairs');
        $this->actingChat($student['user']);
        $lesson = DB::table('lessons')->insertGetId(['course_id' => $this->makeCourse(), 'title' => 'L', 'created_at' => now(), 'updated_at' => now()]);
        $att = DB::table('attendance')->insertGetId([
            'student_id' => $student['student_id'], 'lesson_id' => $lesson, 'status' => 'absent',
            'attendance_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->press("excuse_start_{$att}");
        $this->say('كنت مريضاً');
        $this->say('/skip_photo');

        $row = DB::table('attendance')->where('attendance_id', $att)->first();
        $this->assertSame('pending', $row->excuse_status);
        $this->assertSame('كنت مريضاً', $row->excuse_text);
        $this->assertGreaterThanOrEqual(1, $this->notifications($affairs->user_id));
    }

    // ═════════════════════════ المعلم ═════════════════════════

    public function test_teacher_creates_an_assignment_and_students_are_notified(): void
    {
        $teacher = $this->makeTeacher();
        $this->actingChat($teacher['user']);
        $course  = $this->courseWithTeacher($teacher);
        $student = $this->makeStudent();
        $this->enroll($student['student_id'], $course);

        $this->press("teacher_create_assign_course_{$course}");
        $this->say('Homework Two');
        $this->press("teacher_assign_due_preset_{$course}_7");
        $this->press("teacher_assign_points_preset_{$course}_20");

        $assignment = DB::table('assignments')->where('title', 'Homework Two')->first();
        $this->assertNotNull($assignment, 'assignment was not created');
        $this->assertSame($course, (int) $assignment->course_id);
        $this->assertSame($teacher['teacher_id'], (int) $assignment->teacher_id);
        $this->assertSame(20, (int) $assignment->max_points);
        $this->assertSame(1, $this->notifications($student['user']->user_id));
    }

    public function test_teacher_grades_a_submission_within_the_maximum(): void
    {
        $teacher = $this->makeTeacher();
        $this->actingChat($teacher['user']);
        $course  = $this->courseWithTeacher($teacher);
        $student = $this->makeStudent();
        $assignment = DB::table('assignments')->insertGetId(['course_id' => $course, 'teacher_id' => $teacher['teacher_id'], 'title' => 'HW', 'due_date' => now()->addDay(), 'max_points' => 20, 'created_at' => now(), 'updated_at' => now()]);
        $sub = DB::table('assignment_submissions')->insertGetId(['assignment_id' => $assignment, 'student_id' => $student['student_id'], 'file_path' => 'x.pdf', 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        $this->press("teacher_grade_sub_{$sub}");
        $this->say('99');   // أكبر من العلامة العظمى 20
        $this->assertNull(DB::table('assignment_submissions')->where('submission_id', $sub)->value('grade'));

        $this->say('15');
        $this->assertSame(15.0, (float) DB::table('assignment_submissions')->where('submission_id', $sub)->value('grade'));
    }

    public function test_teacher_starts_and_ends_an_attendance_session(): void
    {
        $teacher = $this->makeTeacher();
        $this->actingChat($teacher['user']);
        $course = $this->courseWithTeacher($teacher);

        $before = DB::table('attendance_sessions')->count();
        $this->press("teacher_course_start_attendance_{$course}");
        $this->assertSame($before + 1, DB::table('attendance_sessions')->count());

        $sessionId = (int) DB::table('attendance_sessions')->orderByDesc('id')->value('id');
        $this->assertSame(1, (int) DB::table('attendance_sessions')->where('id', $sessionId)->value('is_active'));

        $this->press("teacher_end_session_{$sessionId}");
        $this->assertSame(0, (int) DB::table('attendance_sessions')->where('id', $sessionId)->value('is_active'));
    }

    public function test_teacher_leave_request_notifies_only_the_head_of_the_same_department(): void
    {
        $short = $this->makeDepartment('علوم');
        $long  = $this->makeDepartment('علوم الحاسوب');
        $headShort = $this->makeHead($short, ['department' => 'علوم'])['user'];
        $headLong  = $this->makeHead($long, ['department' => 'علوم الحاسوب'])['user'];
        $teacher = $this->makeTeacher(['department' => 'علوم']);
        $this->actingChat($teacher['user']);

        $this->press('teacher_leave_type_full_day');
        $this->say('2026-12-05');
        $this->say('ظرف طارئ');

        $this->assertSame(1, $this->notifications($headShort->user_id, 'teacher_leave'));
        $this->assertSame(0, $this->notifications($headLong->user_id, 'teacher_leave'), 'another department\'s head was notified (substring match)');
    }

    // ═════════════════════════ شؤون الطلاب ═════════════════════════

    public function test_affairs_leave_decision_is_allowed_only_at_its_own_stage(): void
    {
        $this->actingChat($this->makeUser('affairs'));
        $student = $this->makeStudent();
        $mk = fn (string $status) => DB::table('leave_requests')->insertGetId([
            'student_id' => $student['user']->user_id, 'type' => 'full_day', 'date' => now()->toDateString(),
            'reason' => 'r', 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $mine = $mk('pending_affairs');
        $atHod = $mk('pending_hod');
        $done = $mk('rejected');

        $this->press("affairs_approve_leave_leave_requests_{$atHod}");
        $this->press("affairs_approve_leave_leave_requests_{$done}");
        $this->assertSame('pending_hod', DB::table('leave_requests')->where('id', $atHod)->value('status'));
        $this->assertSame('rejected', DB::table('leave_requests')->where('id', $done)->value('status'));

        $this->press("affairs_approve_leave_leave_requests_{$mine}");
        $this->assertSame('approved', DB::table('leave_requests')->where('id', $mine)->value('status'));
        $this->assertSame(1, $this->notifications($student['user']->user_id));

        $this->press("affairs_reject_leave_leave_requests_{$mine}"); // لا يمكن قلب القرار
        $this->assertSame('approved', DB::table('leave_requests')->where('id', $mine)->value('status'));
    }

    public function test_affairs_can_decide_absence_requests_through_the_bot(): void
    {
        // الزر يحمل اسم الجدول (absence_requests) ثم الرقم؛ كان الفصل بـ explode('_') يعطي مصدراً خاطئاً ورقماً صفر
        $this->actingChat($this->makeUser('affairs'));
        $student = $this->makeStudent();
        $mk = fn () => DB::table('absence_requests')->insertGetId([
            'student_id' => $student['student_id'], 'date' => now()->toDateString(), 'reason' => 'r',
            'status' => 'pending_affairs', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $toApprove = $mk();
        $toReject  = $mk();

        $this->press("affairs_approve_leave_absence_requests_{$toApprove}");
        $this->press("affairs_reject_leave_absence_requests_{$toReject}");

        $this->assertSame('approved', DB::table('absence_requests')->where('request_id', $toApprove)->value('status'));
        $this->assertSame('rejected', DB::table('absence_requests')->where('request_id', $toReject)->value('status'));
    }

    public function test_affairs_student_request_decisions_follow_the_workflow(): void
    {
        $this->actingChat($this->makeUser('affairs'));
        $student = $this->makeStudent();
        $mk = fn (string $status, string $type = 'certificate') => DB::table('student_requests')->insertGetId([
            'student_id' => $student['student_id'], 'type' => $type, 'details' => 'd', 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $normal = $mk('pending_affairs');
        $atAdmin = $mk('pending_admin');

        $this->press("affairs_approve_req_{$atAdmin}");
        $this->press("affairs_reject_req_{$atAdmin}");
        $this->assertSame('pending_admin', DB::table('student_requests')->where('id', $atAdmin)->value('status'));

        $this->press("affairs_approve_req_{$normal}");
        $this->assertSame('pending_hod', DB::table('student_requests')->where('id', $normal)->value('status'));
        $this->assertSame('approved', DB::table('student_requests')->where('id', $normal)->value('affairs_decision'));
    }

    public function test_affairs_device_reset_request_unlocks_the_device(): void
    {
        $this->actingChat($this->makeUser('affairs'));
        $student = $this->makeStudent();
        DB::table('students')->where('student_id', $student['student_id'])->update(['device_id' => 'abc', 'is_device_locked' => 1]);
        $req = DB::table('student_requests')->insertGetId([
            'student_id' => $student['student_id'], 'type' => 'device_reset', 'details' => 'd', 'status' => 'pending_affairs',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->press("affairs_approve_req_{$req}");

        $this->assertSame('approved', DB::table('student_requests')->where('id', $req)->value('status'));
        $this->assertSame(0, (int) DB::table('students')->where('student_id', $student['student_id'])->value('is_device_locked'));
        $this->assertNull(DB::table('students')->where('student_id', $student['student_id'])->value('device_id'));
    }

    public function test_affairs_photo_request_approval_updates_the_avatar_once(): void
    {
        $this->actingChat($this->makeUser('affairs'));
        $student = $this->makeStudent();
        $req = DB::table('photo_change_requests')->insertGetId([
            'user_id' => $student['user']->user_id, 'new_photo' => 'photo_requests/new.jpg', 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->press("affairs_approve_photo_{$req}");
        $this->assertSame('approved', DB::table('photo_change_requests')->where('id', $req)->value('status'));
        $this->assertSame('photo_requests/new.jpg', DB::table('users')->where('user_id', $student['user']->user_id)->value('avatar'));

        // قرار ثانٍ على نفس الطلب لا يغيّر شيئاً
        $this->press("affairs_reject_photo_{$req}");
        $this->assertSame('approved', DB::table('photo_change_requests')->where('id', $req)->value('status'));
    }

    public function test_affairs_student_search_finds_by_university_id(): void
    {
        $this->actingChat($this->makeUser('affairs'));
        $this->makeStudent(['full_name' => 'Searchable Student', 'university_id' => 'UNI777']);

        $this->press('affairs_action_search');
        $this->say('UNI777');

        $this->assertStringContainsString('Searchable Student', $this->sent());
    }

    public function test_affairs_broadcast_reaches_the_chosen_audience_only(): void
    {
        $this->actingChat($this->makeUser('affairs'));
        $student = $this->makeStudent();
        $teacher = $this->makeTeacher();

        $this->say('نشر إعلان وتعميم');
        $this->say('Exam schedule');
        $this->say('The exams start on Sunday');
        $this->press('affairs_ann_target_students');

        $this->assertSame(1, DB::table('announcements')->where('title', 'Exam schedule')->count());
        $this->assertSame(1, $this->notifications($student['user']->user_id, 'announcement'));
        $this->assertSame(0, $this->notifications($teacher['user']->user_id, 'announcement'));
    }

    // ═════════════════════════ الإدارة ═════════════════════════

    public function test_admin_can_approve_a_pending_account_but_not_touch_an_active_one(): void
    {
        $this->actingChat($this->makeUser('admin'));
        $pending = $this->makeUser('student', ['status' => 'inactive']);
        $active  = $this->makeUser('student');

        $this->press("admin_approve_user_{$active->user_id}");   // لا شيء يتغير
        $this->assertSame('active', DB::table('users')->where('user_id', $active->user_id)->value('status'));

        $this->press("admin_approve_user_{$pending->user_id}");
        $this->assertSame('active', DB::table('users')->where('user_id', $pending->user_id)->value('status'));
    }

    public function test_admin_reject_deletes_only_a_pending_account(): void
    {
        $admin  = $this->actingChat($this->makeUser('admin'));
        $other  = $this->makeUser('admin');
        $active = $this->makeUser('student');
        $pending = $this->makeUser('student', ['status' => 'inactive']);

        $this->press("admin_reject_user_{$active->user_id}");   // حساب فعال: يجب ألا يُحذف
        $this->press("admin_reject_user_{$other->user_id}");    // مدير: يجب ألا يُحذف
        $this->assertDatabaseHas('users', ['user_id' => $active->user_id]);
        $this->assertDatabaseHas('users', ['user_id' => $other->user_id]);

        $this->press("admin_reject_user_{$pending->user_id}");
        $this->assertDatabaseMissing('users', ['user_id' => $pending->user_id]);
    }

    public function test_admin_student_request_decision_only_at_the_admin_stage(): void
    {
        $this->actingChat($this->makeUser('admin'));
        $student = $this->makeStudent();
        $mk = fn (string $status) => DB::table('student_requests')->insertGetId([
            'student_id' => $student['student_id'], 'type' => 'certificate', 'details' => 'd', 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $early = $mk('pending_affairs');
        $mine  = $mk('pending_admin');

        $this->press("admin_approve_req_{$early}");
        $this->assertSame('pending_affairs', DB::table('student_requests')->where('id', $early)->value('status'));

        $this->press("admin_approve_req_{$mine}");
        $this->assertSame('completed', DB::table('student_requests')->where('id', $mine)->value('status'));

        $this->press("admin_reject_req_{$mine}");   // لا يمكن إعادة البتّ
        $this->assertSame('approved', DB::table('student_requests')->where('id', $mine)->value('admin_decision'));
    }

    public function test_admin_toggle_status_and_unlink_work_for_normal_accounts_only(): void
    {
        $this->actingChat($this->makeUser('admin'));
        $other = $this->makeUser('admin');
        $student = $this->makeStudent();
        DB::table('students')->where('student_id', $student['student_id'])->update(['device_id' => 'abc', 'is_device_locked' => 1]);

        $this->press("admin_toggle_status_{$other->user_id}");
        $this->assertSame('active', DB::table('users')->where('user_id', $other->user_id)->value('status'));

        $this->press("admin_toggle_status_{$student['user']->user_id}");
        $this->assertSame('inactive', DB::table('users')->where('user_id', $student['user']->user_id)->value('status'));

        $this->press("admin_unlink_user_{$student['user']->user_id}");
        $this->assertSame(0, (int) DB::table('students')->where('student_id', $student['student_id'])->value('is_device_locked'));
    }

    public function test_admin_user_search_finds_users(): void
    {
        $this->actingChat($this->makeUser('admin'));
        $this->makeUser('teacher', ['full_name' => 'Findable Teacher', 'email' => 'findable@example.test']);

        $this->press('admin_action_search');
        $this->say('findable@example.test');

        $this->assertStringContainsString('Findable Teacher', $this->sent());
    }

    public function test_admin_broadcast_reaches_the_chosen_audience(): void
    {
        $this->actingChat($this->makeUser('admin'));
        $student = $this->makeStudent();
        $teacher = $this->makeTeacher();

        $this->say('بث وتعميم إداري');
        $this->say('Holiday');
        $this->say('The university is closed tomorrow');
        $this->press('admin_ann_target_teachers');

        $this->assertSame(1, $this->notifications($teacher['user']->user_id, 'announcement'));
        $this->assertSame(0, $this->notifications($student['user']->user_id, 'announcement'));
    }
}
