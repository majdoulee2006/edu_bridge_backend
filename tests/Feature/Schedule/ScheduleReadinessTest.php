<?php

namespace Tests\Feature\Schedule;

use App\Models\Room;
use App\Models\Semester;
use App\Services\Schedule\RoomService;
use App\Services\Schedule\ScheduleProblem;
use App\Services\Schedule\ScheduleReadiness;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * جاهزية البيانات قبل التوليد، وإدارة القاعات (إنشاء لكل شعبة، واستيراد بالجملة).
 */
class ScheduleReadinessTest extends TestCase
{
    use MakesAcademicData;

    private int $dept;
    private int $program;
    private int $semester;
    private RoomService $rooms;
    private ScheduleReadiness $ready;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rooms = app(RoomService::class);
        $this->ready = app(ScheduleReadiness::class);

        $this->semester = Semester::currentOrCreate()->semester_id;
        $this->dept = $this->makeDepartment('قسم');
        $this->program = DB::table('programs')->insertGetId(['name' => 'معلوماتية', 'department_id' => $this->dept]);
    }

    private function course(string $title, int $year = 1, bool $withTeacher = true, ?int $semester = null): int
    {
        $c = $this->makeCourse($this->program, ['title' => $title, 'hours' => 3, 'year' => $year, 'semester_id' => $semester ?? $this->semester]);
        if ($withTeacher) {
            $this->assignTeacher($c, DB::table('teachers')->where('user_id', $this->makeTeacher()['user']->user_id)->value('teacher_id'));
        }

        return $c;
    }

    private function students(int $year, int $n): void
    {
        foreach (range(1, $n) as $i) {
            $this->makeStudent(['academic_year' => ScheduleProblem::academicYear($year), 'status' => 'active'], $this->program);
        }
    }

    private function item(array $report, string $key): ?array
    {
        return collect($report['items'])->firstWhere('key', $key);
    }

    private function check(): array
    {
        return $this->ready->check($this->dept, $this->semester);
    }

    // ───────────────────────────── الجاهزية ─────────────────────────────

    public function test_no_courses_in_the_semester_is_an_error(): void
    {
        $r = $this->check();

        $this->assertFalse($r['ready']);
        $this->assertSame('error', $this->item($r, 'courses')['level']);
        $this->assertStringContainsString('لا مقررات', $this->item($r, 'courses')['title']);
    }

    public function test_courses_of_another_semester_do_not_count(): void
    {
        $other = DB::table('semesters')->insertGetId(['name' => 'الثاني', 'start_date' => '2027-02-01', 'end_date' => '2027-06-30', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);
        $this->course('مقرر الفصل الثاني', 1, true, $other);

        $this->assertSame('error', $this->item($this->check(), 'courses')['level']);
        $this->assertSame('ok', $this->item($this->ready->check($this->dept, $other), 'courses')['level']);
    }

    public function test_a_healthy_setup_is_ready(): void
    {
        $this->course('أ');
        $this->course('ب');
        $this->students(1, 5);
        $this->rooms->createGroupRooms($this->dept);

        $r = $this->check();

        $this->assertTrue($r['ready']);
        $this->assertSame(0, $r['errors']);
        $this->assertSame('ok', $this->item($r, 'teachers')['level']);
        $this->assertSame('ok', $this->item($r, 'capacity')['level']);
        $this->assertSame('ok', $this->item($r, 'rooms')['level']);
    }

    public function test_courses_without_a_teacher_are_listed(): void
    {
        $this->course('بلا معلم', 1, false);
        $this->course('بمعلم');

        $t = $this->item($this->check(), 'teachers');

        $this->assertSame('warn', $t['level']);
        $this->assertStringContainsString('بلا معلم', $t['detail']);
        $this->assertStringNotContainsString('بمعلم،', $t['detail']);
    }

    public function test_overflow_is_an_error_and_a_nearly_full_week_is_a_warning(): void
    {
        foreach (range(1, 14) as $i) {      // 14 مقرراً × حصتان = 28 > 25
            $this->course("م$i");
        }
        $r = $this->check();
        $this->assertFalse($r['ready']);
        $this->assertSame('error', $this->item($r, 'capacity')['level']);
        $this->assertStringContainsString('28', $this->item($r, 'capacity')['detail']);
    }

    public function test_a_week_at_ninety_percent_warns_but_does_not_block(): void
    {
        foreach (range(1, 12) as $i) {      // 24 من 25
            $this->course("م$i");
        }
        $r = $this->check();

        $this->assertTrue($r['ready']);
        $this->assertSame('warn', $this->item($r, 'capacity')['level']);
    }

    public function test_no_rooms_warns_and_offers_auto_creation(): void
    {
        $this->course('أ');

        $room = $this->item($this->check(), 'rooms');

        $this->assertSame('warn', $room['level']);
        $this->assertSame(ScheduleReadiness::ACTION_AUTO_ROOMS, $room['action']);
    }

    public function test_groups_without_a_room_are_listed_and_shared_rooms_soften_it(): void
    {
        $this->course('س1', 1);
        $this->course('س2', 2);
        Room::create(['name' => 'قاعة 1', 'capacity' => 40, 'department_id' => $this->dept, 'is_active' => true, 'program_id' => $this->program, 'year' => 1]);

        $missing = $this->item($this->check(), 'rooms_missing');
        $this->assertSame('warn', $missing['level'], 'لا مشتركة فتبقى السنة الثانية بلا قاعة');
        $this->assertStringContainsString('سنة ثانية', $missing['detail']);

        Room::create(['name' => 'المخبر', 'capacity' => 40, 'department_id' => $this->dept, 'is_active' => true, 'type' => 'lab']);
        $this->assertSame('info', $this->item($this->check(), 'rooms_missing')['level']);
    }

    public function test_a_room_smaller_than_its_groups_students_warns(): void
    {
        $this->course('أ');
        $this->students(1, 12);
        Room::create(['name' => 'صغيرة', 'capacity' => 5, 'department_id' => $this->dept, 'is_active' => true, 'program_id' => $this->program, 'year' => 1]);

        $small = $this->item($this->check(), 'rooms_small');

        $this->assertSame('warn', $small['level']);
        $this->assertStringContainsString('صغيرة', $small['detail']);
        $this->assertSame(ScheduleReadiness::ACTION_OPEN_ROOMS, $small['action']);
    }

    public function test_teacher_availability_is_informational(): void
    {
        $c = $this->course('أ');
        $this->assertSame('info', $this->item($this->check(), 'availability')['level']);

        $teacher = DB::table('course_teachers')->where('course_id', $c)->value('teacher_id');
        $userId = DB::table('teachers')->where('teacher_id', $teacher)->value('user_id');
        DB::table('teacher_availability')->insert(['teacher_user_id' => $userId, 'day' => 'Sunday', 'period' => 1, 'kind' => 'avoid', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame('ok', $this->item($this->check(), 'availability')['level']);
    }

    // ───────────────────────────── إنشاء قاعة لكل شعبة ─────────────────────────────

    public function test_one_room_per_group_is_created_with_a_sensible_capacity(): void
    {
        $this->course('س1', 1);
        $this->course('س2', 2);
        $this->students(1, 33);

        $report = $this->rooms->createGroupRooms($this->dept);

        $this->assertEqualsCanonicalizing(['قاعة معلوماتية - سنة أولى', 'قاعة معلوماتية - سنة ثانية'], $report['created']);
        $first = Room::where('name', 'قاعة معلوماتية - سنة أولى')->first();
        $this->assertSame([$this->program, 1, 40], [(int) $first->program_id, $first->year, $first->capacity], '33 طالباً + هامش = 40');
        $this->assertSame(30, Room::where('name', 'قاعة معلوماتية - سنة ثانية')->value('capacity'), 'الحد الأدنى 30');
    }

    public function test_creation_is_idempotent_and_links_an_existing_room_by_name(): void
    {
        $this->course('س1', 1);
        $this->course('س2', 2);
        $legacy = Room::create(['name' => 'قاعة معلوماتية - سنة ثانية', 'capacity' => 55, 'department_id' => $this->dept, 'is_active' => true]);

        $first = $this->rooms->createGroupRooms($this->dept);
        $second = $this->rooms->createGroupRooms($this->dept);

        $this->assertSame(['قاعة معلوماتية - سنة أولى'], $first['created']);
        $this->assertSame(['قاعة معلوماتية - سنة ثانية'], $first['linked']);
        $this->assertSame([], $second['created'] + $second['linked']);
        $this->assertCount(2, $second['skipped']);
        $this->assertSame(2, Room::where('department_id', $this->dept)->count(), 'لا تكرار');
        $this->assertSame([$this->program, 2, 55], [(int) $legacy->fresh()->program_id, $legacy->fresh()->year, $legacy->fresh()->capacity], 'سعتها لم تتغير');
    }

    public function test_room_creation_never_touches_other_departments(): void
    {
        $this->course('س1', 1);
        $otherDept = $this->makeDepartment('آخر');
        $otherProgram = DB::table('programs')->insertGetId(['name' => 'برنامج آخر', 'department_id' => $otherDept]);
        $this->makeCourse($otherProgram, ['year' => 1]);

        $this->rooms->createGroupRooms($this->dept);

        $this->assertSame(0, Room::where('department_id', $otherDept)->count());
        $this->assertSame(1, Room::where('program_id', $this->program)->count());
    }

    // ───────────────────────────── استيراد بالجملة ─────────────────────────────

    public function test_import_creates_and_updates_rooms_from_pasted_lines(): void
    {
        $text = "قاعة 101، 40\nمخبر الحاسوب، 25، مخبر\nقاعة معلوماتية - سنة أولى، 35، قاعة شعبة، معلوماتية، أولى\nقاعة س2\t30\tclassroom\tمعلوماتية\t2";

        $r = $this->rooms->import($this->dept, $text);

        $this->assertSame([4, 0, []], [$r['created'], $r['updated'], $r['errors']]);
        $this->assertSame('lab', Room::where('name', 'مخبر الحاسوب')->value('type'));
        $this->assertNull(Room::where('name', 'قاعة 101')->value('program_id'), 'بلا شعبة = مشتركة');
        $own = Room::where('name', 'قاعة معلوماتية - سنة أولى')->first();
        $this->assertSame([$this->program, 1, 35], [(int) $own->program_id, $own->year, $own->capacity]);
        $this->assertSame(2, Room::where('name', 'قاعة س2')->value('year'));

        $again = $this->rooms->import($this->dept, "قاعة 101، 60");
        $this->assertSame([0, 1], [$again['created'], $again['updated']]);
        $this->assertSame(60, Room::where('name', 'قاعة 101')->where('department_id', $this->dept)->value('capacity'));
    }

    public function test_import_reports_each_bad_line_without_stopping(): void
    {
        $text = "قاعة جيدة، 30\n،50\nقاعة، abc\nقاعة، 5000\nقاعة، 30، نوع غريب\nقاعة، 30، قاعة شعبة، دورة ليست لي، 1\nقاعة، 30، قاعة شعبة، معلوماتية\nقاعة أخرى جيدة، 20";

        $r = $this->rooms->import($this->dept, $text);

        $this->assertSame(2, $r['created']);
        $this->assertEqualsCanonicalizing([2, 3, 4, 5, 6, 7], array_keys($r['errors']));
        $this->assertStringContainsString('اسم', $r['errors'][2]);
        $this->assertStringContainsString('السعة', $r['errors'][3]);
        $this->assertStringContainsString('النوع', $r['errors'][5]);
        $this->assertStringContainsString('ليست من دورات قسمك', $r['errors'][6]);
        $this->assertStringContainsString('سنة', $r['errors'][7]);
    }

    public function test_import_cannot_attach_a_room_to_another_departments_program(): void
    {
        $otherProgram = DB::table('programs')->insertGetId(['name' => 'برنامج الغير', 'department_id' => $this->makeDepartment('آخر')]);

        $r = $this->rooms->import($this->dept, "قاعة، 30، قاعة شعبة، برنامج الغير، 1");

        $this->assertSame(0, $r['created']);
        $this->assertArrayHasKey(1, $r['errors']);
        $this->assertSame(0, Room::where('program_id', $otherProgram)->count());
    }
}
