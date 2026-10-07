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
 * Head-of-department services in the Telegram bot: every list, detail and decision is limited to the
 * head's own department, decisions respect the workflow stage, and nothing falls back to "show everything".
 * Reference rules: App\Support\Access and HODWebController (same behaviour as the web panel).
 */
class TelegramHodServicesTest extends TestCase
{
    use MakesAcademicData;

    private const CHAT = 8800;

    private int $deptA;
    private int $deptB;
    private string $nameA = 'قسم الحاسوب';
    private string $nameB = 'قسم الميكانيك';
    private User $head;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)]);
        Cache::flush();

        $this->deptA = $this->makeDepartment($this->nameA);
        $this->deptB = $this->makeDepartment($this->nameB);
        $this->head  = $this->makeHead($this->deptA, ['telegram_chat_id' => (string) self::CHAT, 'department' => $this->nameA])['user'];
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function say(string $text): void
    {
        (new TelegramBotHandler())->handleUpdate(['message' => ['chat' => ['id' => self::CHAT], 'text' => $text]]);
    }

    private function press(string $data): void
    {
        (new TelegramBotHandler())->handleUpdate([
            'callback_query' => ['id' => 'q', 'data' => $data, 'message' => ['chat' => ['id' => self::CHAT]]],
        ]);
    }

    private function sent(): string
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'api.telegram.org'))
            ->map(fn ($pair) => json_encode($pair[0]->data(), JSON_UNESCAPED_UNICODE))
            ->implode("\n");
    }

    private function teacherIn(string $dept, string $name): array
    {
        return $this->makeTeacher(['department' => $dept, 'full_name' => $name]);
    }

    private function studentIn(string $dept, string $name, ?int $programId = null): array
    {
        return $this->makeStudent(['department' => $dept, 'full_name' => $name], $programId);
    }

    private function leave(array $student, string $status = 'pending_hod'): int
    {
        return DB::table('leave_requests')->insertGetId([
            'student_id' => $student['user']->user_id, 'type' => 'full_day', 'date' => now()->toDateString(),
            'reason' => 'reason', 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function request(array $student, string $status = 'pending_hod'): int
    {
        return DB::table('student_requests')->insertGetId([
            'student_id' => $student['student_id'], 'type' => 'certificate', 'details' => 'details',
            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ── لوحة المعلومات ───────────────────────────────────────────────────────

    public function test_overview_counts_only_the_own_department_and_never_falls_back_to_global_numbers(): void
    {
        $this->teacherIn($this->nameA, 'TA1');
        $this->teacherIn($this->nameA, 'TA2');
        foreach (range(1, 5) as $i) { $this->teacherIn($this->nameB, "TB$i"); }
        foreach (range(1, 3) as $i) { $this->studentIn($this->nameA, "SA$i"); }
        foreach (range(1, 4) as $i) { $this->studentIn($this->nameB, "SB$i"); }
        $progA = $this->makeProgram($this->deptA);
        $progB = $this->makeProgram($this->deptB);
        $this->makeCourse($progA);
        $this->makeCourse($progB);
        $this->makeCourse($progB);

        $this->say('لوحة المعلومات');
        $out = $this->sent();

        $this->assertStringContainsString('`2` مدرّب', $out);
        $this->assertStringContainsString('`3` طالب', $out);
        $this->assertStringContainsString('`1` مقرر', $out);
    }

    public function test_overview_of_a_department_without_data_shows_zeros_not_other_departments(): void
    {
        $this->teacherIn($this->nameB, 'TB1');
        $this->studentIn($this->nameB, 'SB1');
        $this->makeCourse($this->makeProgram($this->deptB));

        $this->say('لوحة المعلومات');

        $this->assertStringContainsString('`0` مدرّب', $this->sent());
        $this->assertStringContainsString('`0` طالب', $this->sent());
        $this->assertStringContainsString('`0` مقرر', $this->sent());
    }

    public function test_head_without_a_department_sees_nothing_and_is_told_why(): void
    {
        $orphan = $this->makeUser('head', ['telegram_chat_id' => '8801']); // لا سجل heads ولا department
        $this->teacherIn($this->nameA, 'SomeTeacher');

        (new TelegramBotHandler())->handleUpdate(['message' => ['chat' => ['id' => 8801], 'text' => 'كادر المعلمين']]);

        $out = $this->sent();
        $this->assertStringContainsString('غير مرتبط بقسم', $out);
        $this->assertStringNotContainsString('SomeTeacher', $out);
    }

    // ── الكادر والمقررات ─────────────────────────────────────────────────────

    public function test_teachers_list_and_detail_are_limited_to_the_department(): void
    {
        $mine  = $this->teacherIn($this->nameA, 'TeacherMine');
        $other = $this->teacherIn($this->nameB, 'TeacherOther');

        $this->say('كادر المعلمين');
        $this->assertStringContainsString('TeacherMine', $this->sent());
        $this->assertStringNotContainsString('TeacherOther', $this->sent());

        $this->press("hod_teacher_detail_{$other['teacher_id']}");
        $this->assertStringNotContainsString('TeacherOther', $this->sent());

        $this->press("hod_teacher_detail_{$mine['teacher_id']}");
        $this->assertStringContainsString('بطاقة المعلم', $this->sent());
    }

    public function test_department_names_are_matched_exactly_not_by_substring(): void
    {
        $short = $this->makeDepartment('علوم');
        $long  = $this->makeDepartment('علوم الحاسوب');
        $head  = $this->makeHead($short, ['telegram_chat_id' => '8802', 'department' => 'علوم'])['user'];
        $this->teacherIn('علوم الحاسوب', 'LongNameTeacher');

        (new TelegramBotHandler())->handleUpdate(['message' => ['chat' => ['id' => 8802], 'text' => 'كادر المعلمين']]);

        $this->assertStringNotContainsString('LongNameTeacher', $this->sent());
    }

    public function test_courses_list_and_detail_are_limited_to_the_department(): void
    {
        $mine  = $this->makeCourse($this->makeProgram($this->deptA), ['title' => 'CourseMine']);
        $other = $this->makeCourse($this->makeProgram($this->deptB), ['title' => 'CourseOther']);

        $this->say('مقررات القسم');
        $this->assertStringContainsString('CourseMine', $this->sent());
        $this->assertStringNotContainsString('CourseOther', $this->sent());

        $this->press("hod_course_detail_{$other}");
        $this->assertStringNotContainsString('CourseOther', $this->sent());

        $this->press("hod_course_detail_{$mine}");
        $this->assertStringContainsString('بطاقة المقرر', $this->sent());
    }

    public function test_a_department_with_no_courses_does_not_show_other_departments_courses(): void
    {
        $this->makeCourse($this->makeProgram($this->deptB), ['title' => 'CourseOther']);

        $this->say('مقررات القسم');

        $this->assertStringNotContainsString('CourseOther', $this->sent());
    }

    // ── إجازات الطلاب ────────────────────────────────────────────────────────

    public function test_leave_list_shows_only_pending_hod_leaves_of_own_department(): void
    {
        $mine    = $this->studentIn($this->nameA, 'StudentMine');
        $other   = $this->studentIn($this->nameB, 'StudentOther');
        $done    = $this->studentIn($this->nameA, 'StudentDone');
        $this->leave($mine);
        $this->leave($other);
        $this->leave($done, 'approved');

        $this->say('إجازات الطلاب');
        $out = $this->sent();

        $this->assertStringContainsString('StudentMine', $out);
        $this->assertStringNotContainsString('StudentOther', $out);
        $this->assertStringNotContainsString('StudentDone', $out);
    }

    public function test_approving_a_leave_forwards_it_to_affairs_instead_of_approving_it(): void
    {
        $student = $this->studentIn($this->nameA, 'StudentMine');
        $affairs = $this->makeUser('affairs');
        $id = $this->leave($student);

        $this->press("hod_approve_leave_{$id}");

        $this->assertSame('pending_affairs', DB::table('leave_requests')->where('id', $id)->value('status'));
        $this->assertSame(1, DB::table('notifications')->where('user_id', $affairs->user_id)->where('type', 'leave_request')->count());
    }

    public function test_rejecting_a_leave_notifies_the_student(): void
    {
        $student = $this->studentIn($this->nameA, 'StudentMine');
        $id = $this->leave($student);

        $this->press("hod_reject_leave_{$id}");

        $this->assertSame('rejected', DB::table('leave_requests')->where('id', $id)->value('status'));
        $this->assertSame(1, DB::table('notifications')->where('user_id', $student['user']->user_id)->where('type', 'leave_request')->count());
    }

    public function test_leave_decisions_are_refused_for_other_departments_and_wrong_stages(): void
    {
        $foreign = $this->leave($this->studentIn($this->nameB, 'StudentOther'));
        $parentStage = $this->leave($this->studentIn($this->nameA, 'StudentWaitingParent'), 'pending_parent');
        $alreadyForwarded = $this->leave($this->studentIn($this->nameA, 'StudentForwarded'), 'pending_affairs');

        foreach ([$foreign => 'pending_hod', $parentStage => 'pending_parent', $alreadyForwarded => 'pending_affairs'] as $id => $expected) {
            $this->press("hod_approve_leave_{$id}");
            $this->press("hod_reject_leave_{$id}");
            $this->assertSame($expected, DB::table('leave_requests')->where('id', $id)->value('status'), "leave $id changed");
        }
    }

    public function test_a_leave_cannot_be_decided_twice(): void
    {
        $id = $this->leave($this->studentIn($this->nameA, 'StudentMine'));

        $this->press("hod_approve_leave_{$id}");
        $this->press("hod_reject_leave_{$id}");

        $this->assertSame('pending_affairs', DB::table('leave_requests')->where('id', $id)->value('status'));
    }

    // ── طلبات خدمات الطلاب ───────────────────────────────────────────────────

    public function test_student_requests_list_is_limited_to_department_and_pending_hod(): void
    {
        $this->request($this->studentIn($this->nameA, 'ReqStudentMine'));
        $this->request($this->studentIn($this->nameB, 'ReqStudentOther'));
        $this->request($this->studentIn($this->nameA, 'ReqStudentProcessed'), 'pending_admin');

        $this->say('طلبات الطلاب');
        $out = $this->sent();

        $this->assertStringContainsString('ReqStudentMine', $out);
        $this->assertStringNotContainsString('ReqStudentOther', $out);
        $this->assertStringNotContainsString('ReqStudentProcessed', $out);
    }

    public function test_student_request_approval_and_rejection_move_it_to_admin_once(): void
    {
        $a = $this->request($this->studentIn($this->nameA, 'S1'));
        $b = $this->request($this->studentIn($this->nameA, 'S2'));

        $this->press("hod_approve_req_{$a}");
        $this->press("hod_reject_req_{$b}");

        $this->assertSame(['approved', 'pending_admin'], [DB::table('student_requests')->where('id', $a)->value('hod_decision'), DB::table('student_requests')->where('id', $a)->value('status')]);
        $this->assertSame(['rejected', 'pending_admin'], [DB::table('student_requests')->where('id', $b)->value('hod_decision'), DB::table('student_requests')->where('id', $b)->value('status')]);

        // لا يمكن تغيير القرار بعد تحويل الطلب للإدارة
        $this->press("hod_reject_req_{$a}");
        $this->press("hod_approve_req_{$b}");
        $this->assertSame('approved', DB::table('student_requests')->where('id', $a)->value('hod_decision'));
        $this->assertSame('rejected', DB::table('student_requests')->where('id', $b)->value('hod_decision'));
    }

    public function test_student_request_decisions_are_refused_for_other_departments_and_other_stages(): void
    {
        $foreign   = $this->request($this->studentIn($this->nameB, 'Other'));
        $atAffairs = $this->request($this->studentIn($this->nameA, 'AtAffairs'), 'pending_affairs');
        $completed = $this->request($this->studentIn($this->nameA, 'Completed'), 'completed');

        foreach ([$foreign => 'pending_hod', $atAffairs => 'pending_affairs', $completed => 'completed'] as $id => $status) {
            $this->press("hod_approve_req_{$id}");
            $this->press("hod_reject_req_{$id}");
            $this->press("hod_notes_req_{$id}");
            $this->assertSame($status, DB::table('student_requests')->where('id', $id)->value('status'), "request $id changed");
            $this->assertNull(DB::table('student_requests')->where('id', $id)->value('hod_decision'));
        }
        $this->assertNull(Cache::get('telegram_state_' . self::CHAT));
    }

    public function test_head_can_add_notes_to_a_pending_request_only(): void
    {
        $id = $this->request($this->studentIn($this->nameA, 'S1'));

        $this->press("hod_notes_req_{$id}");
        $this->assertSame("awaiting_hod_req_notes_{$id}", Cache::get('telegram_state_' . self::CHAT));

        $this->say('ملاحظات رئيس القسم');
        $this->assertSame('ملاحظات رئيس القسم', DB::table('student_requests')->where('id', $id)->value('hod_notes'));

        // بعد البتّ لا يمكن تعديل الملاحظات عبر حالة قديمة بقيت بالذاكرة
        $this->press("hod_approve_req_{$id}");
        Cache::put('telegram_state_' . self::CHAT, "awaiting_hod_req_notes_{$id}", 600);
        $this->say('تعديل متأخر');
        $this->assertSame('ملاحظات رئيس القسم', DB::table('student_requests')->where('id', $id)->value('hod_notes'));
    }

    // ── مواعيد أولياء الأمور ─────────────────────────────────────────────────

    public function test_parent_appointments_are_listed_for_own_department_only(): void
    {
        $mine  = $this->studentIn($this->nameA, 'ApptStudentMine');
        $other = $this->studentIn($this->nameB, 'ApptStudentOther');
        $mk = function (array $student, string $subject) {
            $parent = $this->makeParent(['full_name' => 'Parent of ' . $subject]);
            DB::table('parent_meeting_requests')->insert([
                'parent_user_id' => $parent['user']->user_id, 'student_id' => $student['student_id'],
                'subject' => $subject, 'reason' => 'r', 'preferred_date' => now()->addDay()->toDateString(),
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
        };
        $mk($mine, 'SubjectMine');
        $mk($other, 'SubjectOther');

        $this->say('مواعيد أولياء الأمور');
        $out = $this->sent();

        $this->assertStringContainsString('SubjectMine', $out);
        $this->assertStringContainsString('ApptStudentMine', $out);
        $this->assertStringNotContainsString('SubjectOther', $out);
    }

    // ── الإعلانات ────────────────────────────────────────────────────────────

    private function draftAnnouncement(string $title = 'Notice', string $content = 'Body'): void
    {
        Cache::put('telegram_hod_ann_title_' . self::CHAT, $title, 600);
        Cache::put('telegram_hod_ann_content_' . self::CHAT, $content, 600);
        Cache::put('telegram_state_' . self::CHAT, 'awaiting_hod_ann_target', 600);
    }

    public function test_announcement_reaches_only_the_departments_audience(): void
    {
        $mine   = $this->studentIn($this->nameA, 'S-mine');
        $teach  = $this->teacherIn($this->nameA, 'T-mine');
        $other  = $this->studentIn($this->nameB, 'S-other');
        $this->draftAnnouncement();

        $this->press('hod_ann_target_students');

        $count = fn (User $u) => DB::table('notifications')->where('user_id', $u->user_id)->where('type', 'announcement')->count();
        $this->assertSame(1, $count($mine['user']));
        $this->assertSame(0, $count($teach['user']));
        $this->assertSame(0, $count($other['user']));
        $this->assertSame($this->deptA, (int) DB::table('announcements')->value('department_id'));
    }

    public function test_announcement_never_falls_back_to_the_whole_university(): void
    {
        $outsider = $this->studentIn($this->nameB, 'S-other');
        $this->draftAnnouncement(); // لا طلاب ولا معلمين بقسمه

        $this->press('hod_ann_target_all');

        $this->assertSame(0, DB::table('notifications')->where('user_id', $outsider['user']->user_id)->count());
    }

    public function test_announcement_audience_and_content_are_validated(): void
    {
        $student = $this->studentIn($this->nameA, 'S-mine');

        $this->draftAnnouncement();
        $this->press('hod_ann_target_everyone');            // جمهور غير معرّف
        $this->draftAnnouncement('', '');
        $this->press('hod_ann_target_students');            // نص فارغ
        Cache::flush();
        $this->press('hod_ann_target_students');            // بلا مسودة أصلاً

        $this->assertSame(0, DB::table('announcements')->count());
        $this->assertSame(0, DB::table('notifications')->where('user_id', $student['user']->user_id)->count());
    }

    // ── القائمة النصية ───────────────────────────────────────────────────────

    public function test_broadcast_menu_item_starts_the_announcement_flow(): void
    {
        $this->say('نشر إعلان');

        $this->assertSame('awaiting_hod_ann_title', Cache::get('telegram_state_' . self::CHAT));
    }

    public function test_every_head_menu_item_answers_without_crashing(): void
    {
        $this->studentIn($this->nameA, 'S');
        $this->teacherIn($this->nameA, 'T');
        $this->makeCourse($this->makeProgram($this->deptA));

        foreach (['لوحة المعلومات', 'كادر المعلمين', 'مقررات القسم', 'إجازات الطلاب', 'طلبات الطلاب', 'مواعيد أولياء الأمور', 'نشر إعلان', 'قائمة'] as $item) {
            Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200)]);
            $this->say($item);
            $this->assertNotSame('', $this->sent(), "menu item [$item] sent nothing");
        }
    }
}
