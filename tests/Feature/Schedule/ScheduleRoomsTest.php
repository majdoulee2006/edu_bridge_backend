<?php

namespace Tests\Feature\Schedule;

use App\Models\Room;
use App\Services\Schedule\ScheduleDraftService;
use App\Services\Schedule\ScheduleProblem;
use App\Services\Schedule\ScheduleRuleException;
use App\Services\Schedule\ScheduleSolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * قاعة خاصة لكل دورة وسنة: حصص الشعبة تذهب إلى قاعتها، ولا تستعمل شعبة قاعة شعبة أخرى، والمشتركة متاحة للجميع.
 */
class ScheduleRoomsTest extends TestCase
{
    use MakesAcademicData;

    private int $dept;
    private array $head;
    private int $program;
    private ScheduleDraftService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->svc = app(ScheduleDraftService::class);

        $this->dept = $this->makeDepartment('قسم');
        $this->head = $this->makeHead($this->dept);
        $this->program = DB::table('programs')->insertGetId(['name' => 'معلوماتية', 'department_id' => $this->dept]);

        $teachers = [];
        foreach (range(1, 3) as $i) {
            $teachers[] = DB::table('teachers')->where('user_id', $this->makeTeacher()['user']->user_id)->value('teacher_id');
        }
        foreach ([1, 2] as $year) {
            foreach (range(1, 3) as $i) {
                $c = $this->makeCourse($this->program, ['title' => "س{$year} م{$i}", 'hours' => 3, 'year' => $year]);
                $this->assignTeacher($c, $teachers[$i - 1]);
            }
        }
    }

    private function room(string $name, int $capacity, ?int $year = null, string $type = 'classroom'): Room
    {
        return Room::create([
            'name' => $name, 'capacity' => $capacity, 'department_id' => $this->dept, 'is_active' => true,
            'program_id' => $year ? $this->program : null, 'year' => $year, 'type' => $type,
        ]);
    }

    private function students(int $year, int $n): void
    {
        foreach (range(1, $n) as $i) {
            $this->makeStudent(['academic_year' => ProblemYear::label($year), 'status' => 'active'], $this->program);
        }
    }

    private function solve(): array
    {
        $p = ScheduleProblem::forDepartment($this->dept, ['seed' => 4]);

        return [$p, (new ScheduleSolver())->solve($p)];
    }

    private function roomsByGroup(array $entries): array
    {
        $out = [];
        foreach ($entries as $e) {
            $out[$e['group']][$e['room']] = true;
        }

        return array_map('array_keys', $out);
    }

    // ───────────────────────────── كل شعبة في قاعتها ─────────────────────────────

    public function test_each_group_uses_its_own_room(): void
    {
        $this->room('قاعة معلوماتية - سنة أولى', 40, 1);
        $this->room('قاعة معلوماتية - سنة ثانية', 40, 2);
        $this->students(1, 5);
        $this->students(2, 5);

        [$p, $r] = $this->solve();
        $by = $this->roomsByGroup($r['entries']);

        $this->assertSame(12, $r['placed']);
        $this->assertSame(['قاعة معلوماتية - سنة أولى'], $by[$this->program . '-1']);
        $this->assertSame(['قاعة معلوماتية - سنة ثانية'], $by[$this->program . '-2']);
        $this->assertSame([], \App\Services\Schedule\ScheduleValidator::validate($p, $r['entries']));
    }

    public function test_a_room_owned_by_another_group_is_never_used(): void
    {
        $this->room('قاعة معلوماتية - سنة أولى', 40, 1);   // السنة الثانية بلا قاعة وبلا قاعات مشتركة
        $this->students(1, 3);
        $this->students(2, 3);

        [, $r] = $this->solve();
        $by = $this->roomsByGroup($r['entries']);

        $this->assertSame(['قاعة معلوماتية - سنة أولى'], $by[$this->program . '-1']);
        $this->assertArrayNotHasKey($this->program . '-2', $by, 'لا تُوضع حصصها في قاعة السنة الأولى');
        $this->assertSame(6, array_sum(array_column($r['unscheduled'], 'missing')));
        $this->assertSame('no_room', $r['unscheduled'][0]['reason']);
    }

    public function test_groups_without_a_room_share_the_shared_rooms(): void
    {
        $this->room('قاعة معلوماتية - سنة أولى', 40, 1);
        $this->room('المخبر', 40, null, 'lab');           // مشتركة
        $this->students(1, 3);
        $this->students(2, 3);

        [, $r] = $this->solve();
        $by = $this->roomsByGroup($r['entries']);

        $this->assertSame(12, $r['placed']);
        $this->assertSame(['المخبر'], $by[$this->program . '-2'], 'السنة الثانية بلا قاعة خاصة فتستعمل المشتركة');
        $this->assertSame(['قاعة معلوماتية - سنة أولى'], $by[$this->program . '-1'], 'السنة الأولى تبقى في قاعتها الخاصة ولا تزاحم المشتركة');
    }

    public function test_rooms_without_an_owner_behave_as_before(): void
    {
        $this->room('قاعة 1', 40);
        $this->room('قاعة 2', 40);
        $this->students(1, 3);
        $this->students(2, 3);

        [$p, $r] = $this->solve();

        $this->assertSame(12, $r['placed']);
        $this->assertSame([], \App\Services\Schedule\ScheduleValidator::validate($p, $r['entries']));
    }

    // ───────────────────────────── السعة ─────────────────────────────

    public function test_a_too_small_home_room_falls_back_to_a_shared_room_that_fits(): void
    {
        $this->room('قاعة معلوماتية - سنة أولى', 2, 1);    // أصغر من عدد الطلاب
        $this->room('القاعة الكبرى', 60, null, 'hall');
        $this->students(1, 8);

        [, $r] = $this->solve();
        $by = $this->roomsByGroup($r['entries']);

        $this->assertSame(['القاعة الكبرى'], $by[$this->program . '-1']);
    }

    public function test_a_too_small_home_room_with_no_alternative_is_reported_not_forced(): void
    {
        $this->room('قاعة معلوماتية - سنة أولى', 2, 1);
        $this->students(1, 8);

        [, $r] = $this->solve();

        $this->assertArrayNotHasKey($this->program . '-1', $this->roomsByGroup($r['entries']));
        $this->assertSame('no_room', $r['unscheduled'][0]['reason']);
    }

    // ───────────────────────────── التعديل اليدوي ─────────────────────────────

    public function test_moving_a_session_into_another_groups_room_is_refused(): void
    {
        $this->room('قاعة معلوماتية - سنة أولى', 40, 1);
        $this->room('قاعة معلوماتية - سنة ثانية', 40, 2);
        $this->students(1, 3);
        $this->students(2, 3);

        $draft = $this->svc->generate($this->head['user'], ['seed' => 4]);
        $entry = $draft->entries()->where('program_id', $this->program)->where('year', 2)->first();

        try {
            $this->svc->move($draft, $entry->id, $entry->day, $entry->period, 'قاعة معلوماتية - سنة أولى');
            $this->fail('كان يجب رفض النقل');
        } catch (ScheduleRuleException $e) {
            $this->assertStringContainsString('مخصصة لشعبة', implode(' ', $e->messages));
        }

        $this->assertSame('قاعة معلوماتية - سنة ثانية', $entry->fresh()->room);
    }

    // ───────────────────────────── النموذج ─────────────────────────────

    public function test_room_group_key_and_type_labels(): void
    {
        $own = $this->room('خاصة', 30, 1);
        $shared = $this->room('مشتركة', 30, null, 'lab');

        $this->assertSame($this->program . '-1', $own->groupKey());
        $this->assertNull($shared->groupKey());
        $this->assertSame('مخبر', Room::TYPES[$shared->type]);
    }
}

/** تحويل رقم السنة إلى قيمة users.academic_year */
final class ProblemYear
{
    public static function label(int $year): string
    {
        return ScheduleProblem::academicYear($year);
    }
}
