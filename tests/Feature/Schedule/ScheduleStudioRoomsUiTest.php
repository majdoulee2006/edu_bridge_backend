<?php

namespace Tests\Feature\Schedule;

use App\Models\Room;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * واجهة الاستوديو الجديدة: زر البناء الواضح، لوحة الجاهزية، التبويبات، وإدارة القاعات (ويب + API).
 */
class ScheduleStudioRoomsUiTest extends TestCase
{
    use MakesAcademicData;

    private int $dept;
    private array $head;
    private int $program;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        $this->dept = $this->makeDepartment('قسم');
        $this->head = $this->makeHead($this->dept);
        $this->program = DB::table('programs')->insertGetId(['name' => 'معلوماتية', 'department_id' => $this->dept]);
        foreach ([1, 2] as $year) {
            $c = $this->makeCourse($this->program, ['title' => "م$year", 'hours' => 3, 'year' => $year]);
            $this->assignTeacher($c, DB::table('teachers')->where('user_id', $this->makeTeacher()['user']->user_id)->value('teacher_id'));
        }
        $this->actingAs($this->head['user']);
    }

    private function page(string $q = ''): string
    {
        return $this->get('/hod/schedule-studio' . $q)->assertOk()->getContent();
    }

    // ───────────────────────────── الصفحة الرئيسية ─────────────────────────────

    public function test_the_main_action_is_obvious_and_the_dialog_posts_the_semester(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('ابنِ لي الجدول تلقائياً', $html);
        $this->assertStringContainsString('ابدأ البناء', $html);
        $this->assertStringContainsString('id="gen-dialog"', $html);
        $this->assertStringContainsString('name="semester_id" value="' . Semester::currentId() . '"', $html);
        $this->assertStringContainsString('أنشئه بنفسي', $html);
    }

    public function test_the_page_shows_a_readiness_panel_with_a_fix_button_for_missing_rooms(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('جاهزية البيانات', $html);
        $this->assertStringContainsString('لا قاعات مسجلة', $html);
        $this->assertStringContainsString(route('hod.studio.rooms.auto'), $html);
        $this->assertStringContainsString('أنشئ قاعة لكل شعبة', $html);
    }

    public function test_a_ready_department_says_so(): void
    {
        $this->post(route('hod.studio.rooms.auto'))->assertSessionHas('success');

        $html = $this->page();

        $this->assertStringContainsString('✓ البيانات جاهزة', $html);
        $this->assertStringContainsString('لكل شعبة قاعتها الخاصة', $html);
    }

    public function test_readiness_errors_are_called_out_on_the_main_card(): void
    {
        DB::table('courses')->update(['semester_id' => DB::table('semesters')->insertGetId([
            'name' => 'آخر', 'start_date' => '2027-02-01', 'end_date' => '2027-06-30', 'is_active' => false, 'created_at' => now(), 'updated_at' => now(),
        ])]);

        $html = $this->page();

        $this->assertStringContainsString('لا مقررات في', $html);
        $this->assertStringContainsString('يمنع جزءاً من الحصص', $html);
    }

    public function test_the_tabs_switch_the_content_and_keep_the_semester(): void
    {
        $sem = Semester::currentId();

        $drafts = $this->page();
        $this->assertStringContainsString('لا مسودات بعد', $drafts);
        $this->assertStringNotContainsString('استيراد قائمة قاعات', $drafts);

        $rooms = $this->page('?tab=rooms');
        $this->assertStringContainsString('استيراد قائمة قاعات', $rooms);
        $this->assertStringNotContainsString('لا مسودات بعد', $rooms);

        $avail = $this->page('?tab=availability');
        $this->assertStringContainsString('غير متاح', $avail);

        $this->assertStringContainsString('tab=rooms', $drafts);
        $this->assertStringContainsString('semester_id=' . $sem, $drafts, 'الرابط يحتفظ بالفصل المختار');
        $this->get('/hod/schedule-studio?tab=zzz')->assertSessionHasErrors('tab');
    }

    // ───────────────────────────── القاعات ─────────────────────────────

    public function test_auto_rooms_creates_one_per_group_and_is_idempotent(): void
    {
        $this->post(route('hod.studio.rooms.auto'))->assertSessionHas('success');
        $this->assertSame(2, Room::where('department_id', $this->dept)->whereNotNull('program_id')->count());

        $this->post(route('hod.studio.rooms.auto'))->assertSessionHas('success', 'لكل شعبة قاعتها الخاصة أصلاً.');
        $this->assertSame(2, Room::where('department_id', $this->dept)->count());

        $html = $this->page('?tab=rooms');
        $this->assertStringContainsString('قاعة معلوماتية - سنة أولى', $html);
        $this->assertStringContainsString('قاعة معلوماتية - سنة ثانية', $html);
    }

    public function test_adding_a_room_linked_to_a_group_and_a_shared_one(): void
    {
        $this->post(route('hod.studio.rooms.store'), ['name' => 'خاصة', 'capacity' => 40, 'type' => 'classroom', 'program_id' => $this->program, 'year' => 2])->assertSessionHas('success');
        $this->post(route('hod.studio.rooms.store'), ['name' => 'مخبر', 'capacity' => 25, 'type' => 'lab'])->assertSessionHas('success');

        $own = Room::where('name', 'خاصة')->first();
        $this->assertSame([$this->program, 2, 'classroom'], [(int) $own->program_id, $own->year, $own->type]);
        $lab = Room::where('name', 'مخبر')->first();
        $this->assertNull($lab->program_id);
        $this->assertNull($lab->year);
        $this->assertSame('lab', $lab->type);
    }

    public function test_a_room_cannot_be_linked_to_another_departments_program_or_without_a_year(): void
    {
        $foreign = DB::table('programs')->insertGetId(['name' => 'غريب', 'department_id' => $this->makeDepartment('آخر')]);

        $this->post(route('hod.studio.rooms.store'), ['name' => 'x', 'capacity' => 10, 'program_id' => $foreign, 'year' => 1])->assertSessionHasErrors('program_id');
        $this->post(route('hod.studio.rooms.store'), ['name' => 'y', 'capacity' => 10, 'program_id' => $this->program])->assertSessionHasErrors('year');
        $this->post(route('hod.studio.rooms.store'), ['name' => 'z', 'capacity' => 10, 'type' => 'castle'])->assertSessionHasErrors('type');
        $this->assertSame(0, Room::whereIn('name', ['x', 'y', 'z'])->count());
    }

    public function test_updating_a_room_changes_its_link_and_never_touches_another_departments_room(): void
    {
        $mine = Room::create(['name' => 'قاعة', 'capacity' => 30, 'department_id' => $this->dept, 'is_active' => true]);
        $other = Room::create(['name' => 'ملك غيري', 'capacity' => 30, 'department_id' => $this->makeDepartment('آخر'), 'is_active' => true]);

        $this->post(route('hod.studio.rooms.update', $mine->id), ['name' => 'قاعة جديدة', 'capacity' => 55, 'type' => 'hall', 'program_id' => $this->program, 'year' => 1])->assertSessionHas('success');
        $mine->refresh();
        $this->assertSame(['قاعة جديدة', 55, 'hall', 1], [$mine->name, $mine->capacity, $mine->type, $mine->year]);

        $this->post(route('hod.studio.rooms.update', $other->id), ['name' => 'سرقة', 'capacity' => 1])->assertNotFound();
        $this->assertSame('ملك غيري', $other->fresh()->name);
    }

    public function test_pasted_rooms_are_imported_and_bad_lines_are_reported_on_the_page(): void
    {
        $res = $this->post(route('hod.studio.rooms.import'), ['lines' => "قاعة 1، 30\nمخبر، 25، مخبر\nسطر سيئ، abc"]);

        $res->assertSessionHas('success')->assertSessionHas('import_errors');
        $this->assertSame(2, Room::where('department_id', $this->dept)->count());

        $html = $this->withSession(['import_errors' => [3 => 'السعة «abc» غير صالحة']])->get('/hod/schedule-studio?tab=rooms')->getContent();
        $this->assertStringContainsString('سطر 3', $html);
    }

    public function test_the_rooms_table_flags_a_room_smaller_than_its_group(): void
    {
        foreach (range(1, 12) as $i) {
            $this->makeStudent(['academic_year' => 'السنة الأولى', 'status' => 'active'], $this->program);
        }
        Room::create(['name' => 'ضيقة', 'capacity' => 5, 'department_id' => $this->dept, 'is_active' => true, 'program_id' => $this->program, 'year' => 1]);

        $html = $this->page('?tab=rooms');

        $this->assertStringContainsString('5 مقعد ⚠', $html);
        $this->assertStringContainsString('12', $html);
    }

    // ───────────────────────────── API ─────────────────────────────

    public function test_api_index_carries_the_readiness_report(): void
    {
        $this->actAs($this->head['user']);

        $d = $this->getJson('/api/department-head/schedule-studio')->assertOk()->json('data');

        $this->assertTrue($d['readiness']['ready']);
        $this->assertContains('rooms', array_column($d['readiness']['items'], 'key'));
        $this->assertSame('auto_rooms', collect($d['readiness']['items'])->firstWhere('key', 'rooms')['action']);
    }

    public function test_api_room_endpoints_mirror_the_web(): void
    {
        $this->actAs($this->head['user']);

        $this->postJson('/api/department-head/schedule-studio/rooms/auto')->assertOk()->assertJsonCount(2, 'data.created');
        $this->postJson('/api/department-head/schedule-studio/rooms/import', ['lines' => "قاعة API، 30\nمعطوب، x"])
            ->assertOk()->assertJsonPath('data.created', 1)->assertJsonStructure(['data' => ['errors' => [2]]]);

        $settings = $this->getJson('/api/department-head/schedule-studio/settings')->assertOk()->json('data');
        $this->assertSame([$this->program], array_values(array_unique(array_filter(array_column($settings["rooms"], "program_id")))));
        $this->assertSame(['classroom', 'lab', 'hall'], array_column($settings['room_types'], 'key'));
        $this->assertContains('معلوماتية', array_column($settings['programs'], 'name'));

        $id = collect($settings['rooms'])->firstWhere('name', 'قاعة API')['id'];
        $this->putJson("/api/department-head/schedule-studio/rooms/$id", ['name' => 'قاعة API', 'capacity' => 99, 'type' => 'hall'])->assertOk();
        $this->assertSame(99, Room::find($id)->capacity);

        $foreign = DB::table('programs')->insertGetId(['name' => 'غريب', 'department_id' => $this->makeDepartment('آخر')]);
        $this->postJson('/api/department-head/schedule-studio/rooms', ['name' => 'k', 'capacity' => 10, 'program_id' => $foreign, 'year' => 1])->assertStatus(422);
    }

    public function test_only_department_heads_reach_the_room_endpoints(): void
    {
        $this->actAs($this->makeStudent()['user']);

        $this->postJson('/api/department-head/schedule-studio/rooms/auto')->assertStatus(403);
        $this->postJson('/api/department-head/schedule-studio/rooms/import', ['lines' => 'a، 5'])->assertStatus(403);
    }
}
