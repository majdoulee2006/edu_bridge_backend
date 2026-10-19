<?php

namespace Tests\Feature\Schedule;

use App\Models\Exam;
use App\Models\ScheduleDraft;
use App\Models\User;
use App\Services\Schedule\ExamDraftService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** ما يراه كل طرف بعد اعتماد الجدول الامتحاني (طالب، معلم المقرر، مراقب)، وواجهة API لرئيس القسم. */
class ExamExposureTest extends ExamScenario
{
    protected User $head;
    protected User $proctor;
    protected ExamDraftService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->room('قاعة أ1', 4, $this->programA, 1);
        $this->room('قاعة أ1 ب', 4, $this->programA, 1);
        $this->room('قاعة ب1', 4, $this->programA, 2);
        $this->room('قاعة ت1', 3, $this->programB, 1);
        $this->head = $this->makeHead($this->dept)['user'];
        $this->proctor = $this->makeTeacher(['full_name' => 'المراقب', 'department' => DB::table('departments')->where('department_id', $this->dept)->value('name')])['user'];
        $this->svc = app(ExamDraftService::class);
    }

    /** يولّد ويعيّن المراقب في كل القاعات ويعتمد. */
    protected function approved(): ScheduleDraft
    {
        $d = $this->svc->generate($this->head, ['kinds' => $this->kinds(), 'seed' => 5]);
        foreach ($d->examEntries()->get() as $e) {
            foreach ($e->rooms as $r) {
                // مراقب واحد لا يصح في قاعتين بنفس الوقت: نعيّنه في قاعة واحدة فقط من أول تقييم
            }
        }
        $first = $d->examEntries()->first();
        $d = $this->svc->setProctor($d, $first->id, $first->rooms[0]['name'], $this->proctor->user_id);

        return $this->svc->approve($d, $this->head, true);
    }

    protected function studentUser(string $groupKey, int $i = 0): User
    {
        return User::find(DB::table('students')->where('student_id', $this->students[$groupKey][$i])->value('user_id'));
    }

    public function test_nothing_is_visible_before_approval(): void
    {
        $this->svc->generate($this->head, ['kinds' => $this->kinds(), 'seed' => 5]);

        $u = $this->studentUser($this->programA . '-1');
        $this->actAs($u)->getJson('/api/student/my-exams')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_student_api_lists_assessments_with_own_room_seat_type_and_duration(): void
    {
        $this->approved();
        $u = $this->studentUser($this->programA . '-1');

        $rows = $this->actAs($u)->getJson('/api/student/my-exams')->assertOk()->json('data');

        $this->assertCount(6, $rows);                                                  // 3 مقررات × (مذاكرة + امتحان)
        $this->assertEqualsCanonicalizing(['مذاكرة', 'امتحان'], array_values(array_unique(array_column($rows, 'type_label'))));
        foreach ($rows as $r) {
            $this->assertSame('exam_schedule', $r['source']);
            $this->assertMatchesRegularExpression('/مقعد \d+/', $r['room']);
            $this->assertMatchesRegularExpression('/^\d\d:\d\d - \d\d:\d\d$/', $r['time']);
        }
        $this->assertContains('60 دقيقة', array_column($rows, 'duration'));
        $this->assertContains('120 دقيقة', array_column($rows, 'duration'));
    }

    public function test_a_student_sees_only_his_own_group(): void
    {
        $this->approved();
        $b = $this->studentUser($this->programB . '-1');

        $rows = $this->actAs($b)->getJson('/api/student/my-exams')->assertOk()->json('data');
        $this->assertCount(2, $rows);                                                  // مقرر واحد × نوعان
    }

    public function test_a_late_enrolled_student_without_a_seat_does_not_see_the_seated_exam_on_the_web(): void
    {
        $this->approved();
        $late = $this->makeStudent(['full_name' => 'متأخر', 'academic_year' => ScheduleProblemYear::label(1), 'status' => 'active'], $this->programB);
        $this->enroll($late['student_id'], $this->courses[$this->programB . '-1'][0]);

        $q = DB::table('exams')->join('courses', 'courses.course_id', '=', 'exams.course_id')->whereIn('exams.course_id', $this->courses[$this->programB . '-1']);
        $this->assertCount(0, \App\Services\Schedule\ExamScheduleReader::restrictToStudent(clone $q, $late['student_id'])->get());
    }

    public function test_student_web_page_shows_room_type_and_real_duration(): void
    {
        $this->approved();
        $html = $this->actingAs($this->studentUser($this->programA . '-1'))->get('/student/schedule')->assertOk()->getContent();

        $this->assertStringContainsString('مذاكرة', $html);
        $this->assertStringContainsString('قاعة أ1', $html);
        $this->assertStringContainsString('مقعد', $html);
        $this->assertStringContainsString('60 ', $html);
    }

    public function test_proctor_sees_only_assigned_room_with_its_students_on_web_and_api(): void
    {
        $d = $this->approved();
        $first = $d->examEntries()->orderBy('id')->first();
        $room = $first->rooms[0];

        $api = $this->actAs($this->proctor)->getJson('/api/teacher/invigilations')->assertOk()->json('data');
        $this->assertCount(1, $api);
        $this->assertSame($room['name'], $api[0]['rooms'][0]['name']);
        $this->assertCount(count($room['students']), $api[0]['rooms'][0]['students']);

        $html = $this->actingAs($this->proctor)->get('/teacher/schedule')->assertOk()->getContent();
        $this->assertStringContainsString('مراقباتي', $html);
        $this->assertStringContainsString($room['name'], $html);
    }

    public function test_course_teacher_sees_all_rooms_and_proctors_of_his_courses(): void
    {
        $this->approved();
        $teacher = $this->makeTeacher();
        $course = $this->courses[$this->programA . '-1'][0];
        $this->assignTeacher($course, $teacher['teacher_id']);

        $rows = $this->actAs($teacher['user'])->getJson('/api/teacher/exam-rooms')->assertOk()->json('data');

        $this->assertCount(2, $rows);                                                  // مذاكرة + امتحان لمقرر واحد
        $this->assertGreaterThanOrEqual(2, count($rows[0]['rooms']));                  // 6 طلاب على قاعتين سعة 4
        $this->assertSame(6, array_sum(array_column($rows[0]['rooms'], 'count')));
    }

    public function test_hod_api_full_cycle_and_role_guard(): void
    {
        $payload = ['kinds' => [
            'midterm' => ['from' => '2026-11-01', 'to' => '2026-11-12', 'duration' => 60, 'slots' => [['start' => '09:00', 'end' => '10:30'], ['start' => '11:00', 'end' => '12:30']]],
            'final' => ['from' => '2026-12-06', 'to' => '2026-12-17', 'duration' => 120, 'excluded' => ['2026-12-08'], 'slots' => [['start' => '09:00', 'end' => '11:30'], ['start' => '12:00', 'end' => '14:30']]],
        ]];

        $this->actAs($this->head);
        $this->getJson('/api/department-head/exam-studio')->assertOk()->assertJsonPath('data.readiness.ready', true);
        $d = $this->postJson('/api/department-head/exam-studio/generate', $payload)->assertCreated()->json('data');
        $this->assertSame(12, $d['placed']);
        $this->assertSame([], $d['violation_messages']);

        $e = $d['entries'][0];
        $after = $this->postJson("/api/department-head/exam-studio/{$d['id']}/proctor", ['entry_id' => $e['id'], 'room' => $e['rooms'][0]['name'], 'user_id' => $this->proctor->user_id])->assertOk()->json('data');
        $this->assertSame('المراقب', collect($after['entries'])->firstWhere('id', $e['id'])['rooms'][0]['proctor']);

        $this->postJson("/api/department-head/exam-studio/{$d['id']}/approve")->assertStatus(422);
        $this->assertSame(0, Exam::count());
        $this->postJson("/api/department-head/exam-studio/{$d['id']}/approve", ['acknowledge' => true])->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertSame(12, Exam::count());
        $this->postJson("/api/department-head/exam-studio/{$d['id']}/revert")->assertOk();
        $this->assertSame(0, Exam::count());

        $this->actAs($this->proctor)->getJson('/api/department-head/exam-studio')->assertStatus(403);
    }

    public function test_bad_dates_return_a_readable_422(): void
    {
        $r = $this->actAs($this->head)->postJson('/api/department-head/exam-studio/generate', ['kinds' => ['final' => ['from' => 'x', 'to' => '2026-12-17', 'duration' => 120, 'slots' => [['start' => '09:00', 'end' => '11:30']]]]]);
        $r->assertStatus(422);
        $this->assertStringContainsString('تاريخ', $r->json('message'));
    }
}
