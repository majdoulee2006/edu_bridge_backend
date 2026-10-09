<?php

namespace Tests\Feature\Schedule;

use App\Models\ScheduleDraft;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/** واجهة رئيس القسم لاستوديو الجدول + إغلاق ثغرة الإضافة اليدوية. */
class ScheduleStudioWebTest extends TestCase
{
    use MakesAcademicData;

    private int $dept;
    private array $head;
    private int $program;
    private int $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        $this->dept = $this->makeDepartment('قسم');
        $this->head = $this->makeHead($this->dept);
        $this->program = DB::table('programs')->insertGetId(['name' => 'برنامج', 'department_id' => $this->dept]);
        $t = $this->makeTeacher();
        $this->teacherUser = $t['user']->user_id;
        foreach (['شبكات', 'برمجة'] as $title) {
            $c = $this->makeCourse($this->program, ['title' => $title, 'hours' => 3, 'year' => 1]);
            $this->assignTeacher($c, DB::table('teachers')->where('user_id', $this->teacherUser)->value('teacher_id'));
        }
        $this->actingAs($this->head['user']);
    }

    private function generate(): ScheduleDraft
    {
        $this->post('/hod/schedule-studio/generate', ['name' => 'تجريبية'])->assertRedirect();

        return ScheduleDraft::firstOrFail();
    }

    public function test_index_shows_both_choices_and_capacity(): void
    {
        $this->get('/hod/schedule-studio')->assertOk()
            ->assertSee('إنشاء يدوي')->assertSee('اقتراح من النظام')->assertSee('برنامج - سنة أولى')->assertSee('يتسع')
            ->assertSee('مولّد الجدول');   // رابط القائمة الجانبية
    }

    public function test_generate_then_view_the_draft_without_touching_the_real_schedule(): void
    {
        $draft = $this->generate();

        $this->assertSame(0, DB::table('schedules')->count());
        $this->get("/hod/schedule-studio/{$draft->id}")->assertOk()
            ->assertSee('تجريبية')->assertSee('شبكات')->assertSee('برمجة')->assertDontSee('<b>#', false)->assertSee('اعتماد الجدول')->assertSee('إعادة توزيع');
    }

    public function test_approve_through_the_web_replaces_the_schedule_and_revert_restores_it(): void
    {
        $draft = $this->generate();

        $this->post("/hod/schedule-studio/{$draft->id}/approve")->assertSessionHas('success');
        $this->assertSame(4, DB::table('schedules')->count());
        $this->get("/hod/schedule-studio/{$draft->id}")->assertSee('تراجع عن الاعتماد');

        $this->post("/hod/schedule-studio/{$draft->id}/revert")->assertSessionHas('success');
        $this->assertSame(0, DB::table('schedules')->count());
    }

    public function test_a_rule_breaking_move_shows_an_error_and_changes_nothing(): void
    {
        $draft = $this->generate();
        [$a, $b] = $draft->entries()->orderBy('id')->take(2)->get()->all();

        $this->post("/hod/schedule-studio/{$draft->id}/move", ['entry_id' => $a->id, 'day' => $b->day, 'period' => $b->period])
            ->assertSessionHas('error');

        $this->assertSame($a->day . $a->period, $a->fresh()->day . $a->fresh()->period);
    }

    public function test_a_head_cannot_open_or_change_another_departments_draft(): void
    {
        $draft = $this->generate();
        $other = $this->makeHead($this->makeDepartment('قسم آخر'));
        $this->actingAs($other['user']);

        $this->get("/hod/schedule-studio/{$draft->id}")->assertNotFound();
        $this->post("/hod/schedule-studio/{$draft->id}/approve")->assertNotFound();
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_rooms_and_availability_are_scoped_to_the_department(): void
    {
        $this->post('/hod/schedule-studio/rooms', ['name' => 'قاعة 1', 'capacity' => 30])->assertSessionHas('success');
        $this->assertSame(1, DB::table('rooms')->where('department_id', $this->dept)->count());

        $this->post('/hod/schedule-studio/availability', ['teacher_user_id' => $this->teacherUser, 'day' => 'Sunday', 'period' => 1, 'kind' => 'unavailable'])
            ->assertSessionHas('success');
        $this->assertSame(1, DB::table('teacher_availability')->count());

        $stranger = $this->makeTeacher()['user']->user_id;
        $this->post('/hod/schedule-studio/availability', ['teacher_user_id' => $stranger, 'day' => 'Sunday', 'period' => 2, 'kind' => 'avoid'])
            ->assertForbidden();
    }

    // ---- ثغرة الإضافة اليدوية القديمة

    private function manual(int $courseId, int $period = 1, string $room = 'ق1'): \Illuminate\Testing\TestResponse
    {
        return $this->post('/hod/organization/schedule', [
            'course_id' => $courseId, 'day' => 'Sunday', 'period' => $period, 'department' => 'برنامج', 'year' => 'سنة أولى', 'room' => $room,
        ]);
    }

    public function test_manual_add_refuses_a_course_of_another_department(): void
    {
        $foreign = $this->makeCourse(DB::table('programs')->insertGetId(['name' => 'غريب', 'department_id' => $this->makeDepartment('آخر')]), ['title' => 'غريبة']);

        $this->manual($foreign)->assertForbidden();
        $this->assertSame(0, DB::table('schedules')->count());
    }

    public function test_manual_add_refuses_group_and_room_clashes(): void
    {
        $courses = DB::table('courses')->pluck('course_id');
        $other = $this->makeTeacher()['user']->user_id;
        $c3 = $this->makeCourse($this->program, ['title' => 'ثالثة']);
        $this->assignTeacher($c3, DB::table('teachers')->where('user_id', $other)->value('teacher_id'));

        $this->manual($courses[0])->assertSessionHas('success');

        $this->manual($c3, 1, 'ق2')->assertSessionHas('error');   // نفس الشعبة بنفس الوقت
        $this->assertSame(1, DB::table('schedules')->count());
    }
}
