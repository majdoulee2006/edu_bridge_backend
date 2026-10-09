<?php

namespace Tests\Feature\Schedule;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/** واجهة API لاستوديو الجدول (تطبيق رئيس القسم). */
class ScheduleStudioApiTest extends TestCase
{
    use MakesAcademicData;

    private int $dept;
    private array $head;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        $this->dept = $this->makeDepartment('قسم');
        $this->head = $this->makeHead($this->dept);
        $program = DB::table('programs')->insertGetId(['name' => 'برنامج', 'department_id' => $this->dept]);
        $teacher = DB::table('teachers')->where('user_id', $this->makeTeacher()['user']->user_id)->value('teacher_id');
        foreach (['شبكات', 'برمجة'] as $title) {
            $this->assignTeacher($this->makeCourse($program, ['title' => $title, 'hours' => 3, 'year' => 1]), $teacher);
        }
        Sanctum::actingAs($this->head['user']);
    }

    private function generate(): array
    {
        return $this->postJson('/api/department-head/schedule-studio/generate', ['name' => 'مسودة'])->assertCreated()->json('data');
    }

    public function test_index_generate_and_show(): void
    {
        $this->getJson('/api/department-head/schedule-studio')->assertOk()->assertJsonPath('data.capacity.0.label', 'برنامج - سنة أولى')->assertJsonPath('data.drafts', []);

        $d = $this->generate();
        $this->assertSame(4, $d['placed']);
        $this->assertSame([], $d['violation_messages']);
        $this->assertEqualsCanonicalizing(['شبكات', 'برمجة'], collect($d['groups'][0]['entries'])->pluck('course')->unique()->values()->all());
        $this->assertSame(0, DB::table('schedules')->count(), 'التوليد لا يمسّ الجدول الفعلي');

        $this->getJson("/api/department-head/schedule-studio/{$d['id']}")->assertOk()->assertJsonPath('data.name', 'مسودة');
    }

    public function test_rule_breaking_move_is_422_with_the_reason(): void
    {
        $d = $this->generate();
        [$a, $b] = array_slice($d['groups'][0]['entries'], 0, 2);

        $this->postJson("/api/department-head/schedule-studio/{$d['id']}/move", ['entry_id' => $a['id'], 'day' => $b['day'], 'period' => $b['period']])
            ->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_approve_and_revert_via_api(): void
    {
        $d = $this->generate();

        $this->postJson("/api/department-head/schedule-studio/{$d['id']}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertSame(4, DB::table('schedules')->count());

        $this->postJson("/api/department-head/schedule-studio/{$d['id']}/revert")->assertOk();
        $this->assertSame(0, DB::table('schedules')->count());
    }

    public function test_lock_and_regenerate_keep_the_locked_slot(): void
    {
        $d = $this->generate();
        $e = $d['groups'][0]['entries'][0];

        $this->postJson("/api/department-head/schedule-studio/{$d['id']}/lock", ['entry_id' => $e['id'], 'locked' => true])->assertOk();
        $after = $this->postJson("/api/department-head/schedule-studio/{$d['id']}/regenerate")->assertOk()->json('data');

        $kept = collect($after['groups'][0]['entries'])->firstWhere('id', $e['id']);
        $this->assertSame([$e['day'], $e['period'], true], [$kept['day'], $kept['period'], $kept['locked']]);
    }

    public function test_other_departments_and_roles_are_blocked(): void
    {
        $d = $this->generate();

        Sanctum::actingAs($this->makeHead($this->makeDepartment('آخر'))['user']);
        $this->getJson("/api/department-head/schedule-studio/{$d['id']}")->assertNotFound();

        Sanctum::actingAs($this->makeTeacher()['user']);
        $this->getJson('/api/department-head/schedule-studio')->assertForbidden();
    }

    public function test_rooms_and_availability_crud_scoped_to_the_department(): void
    {
        $base = '/api/department-head/schedule-studio';
        $id = $this->postJson("$base/rooms", ['name' => 'قاعة 1', 'capacity' => 30])->assertCreated()->json('data.id');
        $set = $this->getJson("$base/settings")->assertOk();
        $set->assertJsonPath('data.rooms.0.name', 'قاعة 1');
        $teacher = $set->json('data.teachers.0.user_id');

        $aid = $this->postJson("$base/availability", ['teacher_user_id' => $teacher, 'day' => 'Sunday', 'period' => 1, 'kind' => 'unavailable'])->assertCreated()->json('data.id');
        $this->getJson("$base/settings")->assertJsonPath('data.availability.0.kind', 'unavailable');

        $stranger = $this->makeTeacher()['user']->user_id;
        $this->postJson("$base/availability", ['teacher_user_id' => $stranger, 'day' => 'Sunday', 'period' => 2, 'kind' => 'avoid'])->assertForbidden();

        $this->deleteJson("$base/availability/$aid")->assertOk();
        $this->deleteJson("$base/rooms/$id")->assertOk();
        $this->getJson("$base/settings")->assertJsonPath('data.rooms', [])->assertJsonPath('data.availability', []);

        // قسم آخر لا يحذف قاعاتك
        $keep = $this->postJson("$base/rooms", ['name' => 'قاعة 2', 'capacity' => 20])->json('data.id');
        Sanctum::actingAs($this->makeHead($this->makeDepartment('آخر'))['user']);
        $this->deleteJson("$base/rooms/$keep")->assertOk();
        $this->assertSame(1, DB::table('rooms')->where('id', $keep)->count());
    }
}
