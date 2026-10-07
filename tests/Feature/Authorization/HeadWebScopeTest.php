<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * Head of department (web): dashboard numbers, notifications, announcements and the appointments page are
 * limited to the head's own department (they used to reach / show the whole university).
 */
class HeadWebScopeTest extends TestCase
{
    use MakesAcademicData;

    private int $deptA;
    private int $deptB;
    private User $head;
    private string $nameA = 'قسم الحاسوب';
    private string $nameB = 'قسم الميكانيك';

    protected function setUp(): void
    {
        parent::setUp();
        $this->deptA = $this->makeDepartment($this->nameA);
        $this->deptB = $this->makeDepartment($this->nameB);
        $this->head  = $this->makeHead($this->deptA, ['department' => $this->nameA])['user'];
    }

    private function asHead()
    {
        return $this->actingAs($this->head);
    }

    private function notified(User $user, ?string $title = null): int
    {
        $q = DB::table('notifications')->where('user_id', $user->user_id);

        return $title ? $q->where('title', $title)->count() : $q->count();
    }

    // ── لوحة المعلومات ───────────────────────────────────────────────────────

    public function test_dashboard_numbers_are_the_departments_not_the_universitys(): void
    {
        foreach (range(1, 2) as $i) { $this->makeTeacher(['department' => $this->nameA]); }
        foreach (range(1, 5) as $i) { $this->makeTeacher(['department' => $this->nameB]); }
        foreach (range(1, 3) as $i) { $this->makeStudent(['department' => $this->nameA]); }
        foreach (range(1, 4) as $i) { $this->makeStudent(['department' => $this->nameB]); }
        $this->makeCourse($this->makeProgram($this->deptA));
        $this->makeCourse($this->makeProgram($this->deptB));
        $this->makeCourse($this->makeProgram($this->deptB));

        $this->asHead()->get('/hod/dashboard')->assertOk()
            ->assertViewHas('teachersCount', 2)
            ->assertViewHas('studentsCount', 3)
            ->assertViewHas('coursesCount', 1);
    }

    // ── الإشعارات ────────────────────────────────────────────────────────────

    private function audience(): array
    {
        $parent = $this->makeParent();
        $childInA = $this->makeStudent(['department' => $this->nameA]);
        $this->linkParent($parent['user'], $childInA['user']);

        return [
            'studentA'  => $this->makeStudent(['department' => $this->nameA])['user'],
            'teacherA'  => $this->makeTeacher(['department' => $this->nameA])['user'],
            'parentA'   => $parent['user'],
            'studentB'  => $this->makeStudent(['department' => $this->nameB])['user'],
            'teacherB'  => $this->makeTeacher(['department' => $this->nameB])['user'],
            'admin'     => $this->makeUser('admin'),
            'affairs'   => $this->makeUser('affairs'),
            'otherHead' => $this->makeHead($this->deptB, ['department' => $this->nameB])['user'],
        ];
    }

    public function test_notification_to_students_reaches_only_students_of_the_department(): void
    {
        $u = $this->audience();

        $this->asHead()->post('/hod/notifications/send', ['title' => 'T1', 'message' => 'm', 'target' => 'students'])->assertRedirect();

        $this->assertSame(1, $this->notified($u['studentA'], 'T1'));
        foreach (['teacherA', 'parentA', 'studentB', 'teacherB', 'admin', 'affairs', 'otherHead'] as $k) {
            $this->assertSame(0, $this->notified($u[$k], 'T1'), "$k was notified");
        }
    }

    public function test_notification_to_students_and_teachers_stays_inside_the_department(): void
    {
        $u = $this->audience();

        $this->asHead()->post('/hod/notifications/send', ['title' => 'T2', 'message' => 'm', 'target' => 'students_teachers'])->assertRedirect();

        $this->assertSame(1, $this->notified($u['studentA'], 'T2'));
        $this->assertSame(1, $this->notified($u['teacherA'], 'T2'));
        foreach (['parentA', 'studentB', 'teacherB', 'admin', 'affairs', 'otherHead'] as $k) {
            $this->assertSame(0, $this->notified($u[$k], 'T2'), "$k was notified");
        }
    }

    public function test_notification_to_all_means_the_department_including_its_parents_only(): void
    {
        $u = $this->audience();

        $this->asHead()->post('/hod/notifications/send', ['title' => 'T3', 'message' => 'm', 'target' => 'all'])->assertRedirect();

        foreach (['studentA', 'teacherA', 'parentA'] as $k) {
            $this->assertSame(1, $this->notified($u[$k], 'T3'), "$k was not notified");
        }
        foreach (['studentB', 'teacherB', 'admin', 'affairs', 'otherHead'] as $k) {
            $this->assertSame(0, $this->notified($u[$k], 'T3'), "$k was notified");
        }
    }

    public function test_head_without_a_department_notifies_nobody(): void
    {
        $orphan = $this->makeUser('head');
        $student = $this->makeStudent(['department' => $this->nameA])['user'];

        $this->actingAs($orphan)->post('/hod/notifications/send', ['title' => 'T4', 'message' => 'm', 'target' => 'all'])->assertRedirect();

        $this->assertSame(0, $this->notified($student, 'T4'));
    }

    // ── الإعلانات ────────────────────────────────────────────────────────────

    public function test_announcement_notifies_only_the_departments_students_and_teachers(): void
    {
        $u = $this->audience();

        $this->asHead()->post('/hod/announcements', [
            'title' => 'Ann', 'content' => 'body', 'type' => 'general', 'target_audience' => 'all',
        ])->assertRedirect();

        $this->assertSame(1, DB::table('announcements')->where('title', 'Ann')->where('department_id', $this->deptA)->count());
        foreach (['studentA', 'teacherA'] as $k) {
            $this->assertSame(1, $this->notified($u[$k], 'إعلان جديد من رئيس القسم'), "$k was not notified");
        }
        foreach (['studentB', 'teacherB', 'admin', 'affairs', 'otherHead', 'parentA'] as $k) {
            $this->assertSame(0, $this->notified($u[$k], 'إعلان جديد من رئيس القسم'), "$k was notified");
        }
    }

    public function test_course_announcement_requires_a_course_of_the_department(): void
    {
        $own     = $this->makeCourse($this->makeProgram($this->deptA));
        $foreign = $this->makeCourse($this->makeProgram($this->deptB));

        $this->asHead()->post('/hod/announcements', [
            'title' => 'ForeignCourse', 'content' => 'body', 'type' => 'course_specific', 'course_id' => $foreign,
        ])->assertForbidden();
        $this->assertSame(0, DB::table('announcements')->where('title', 'ForeignCourse')->count());

        $this->asHead()->post('/hod/announcements', [
            'title' => 'OwnCourse', 'content' => 'body', 'type' => 'course_specific', 'course_id' => $own,
        ])->assertRedirect();
        $this->assertSame(1, DB::table('announcements')->where('title', 'OwnCourse')->count());
    }

    // ── المواعيد ─────────────────────────────────────────────────────────────

    private function meeting(array $student, string $subject): void
    {
        $parent = $this->makeParent();
        DB::table('parent_meeting_requests')->insert([
            'parent_user_id' => $parent['user']->user_id, 'student_id' => $student['student_id'], 'subject' => $subject,
            'reason' => 'r', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_appointments_page_matches_the_department_exactly_not_by_substring(): void
    {
        $short = $this->makeDepartment('علوم');
        $this->makeDepartment('علوم الحاسوب');
        $headShort = $this->makeHead($short, ['department' => 'علوم'])['user'];
        $this->makeProgram($short);

        $this->meeting($this->makeStudent(['department' => 'علوم']), 'ShortDept');
        $this->meeting($this->makeStudent(['department' => 'علوم الحاسوب']), 'LongDept');

        $response = $this->actingAs($headShort)->get('/hod/appointments')->assertOk();

        $subjects = $response->viewData('meetings')->pluck('subject')->all();
        $this->assertSame(['ShortDept'], $subjects);
    }

    public function test_appointments_page_for_a_head_without_a_department_is_empty(): void
    {
        $orphan = $this->makeUser('head');
        $this->meeting($this->makeStudent(['department' => $this->nameA]), 'Anything');
        $this->makeProgram($this->deptA);

        $response = $this->actingAs($orphan)->get('/hod/appointments')->assertOk();

        $this->assertCount(0, $response->viewData('meetings'));
        $this->assertCount(0, $response->viewData('students'));
        $this->assertCount(0, $response->viewData('programs'));
    }
}
