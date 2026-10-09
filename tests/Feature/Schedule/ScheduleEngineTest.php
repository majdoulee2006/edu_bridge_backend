<?php

namespace Tests\Feature\Schedule;

use App\Services\Schedule\ScheduleProblem;
use App\Services\Schedule\ScheduleQuality;
use App\Services\Schedule\ScheduleSolver;
use App\Services\Schedule\ScheduleValidator;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * محرّك الجدولة: المولّد والمدقق والجودة. كل حالة بأرقام صغيرة يمكن التحقق منها يدويًا.
 * الشبكة الافتراضية: 5 أيام × 5 فترات = 25 فترة أسبوعيًا للشعبة. حصة المادة = ساعاتها ÷ 1.5.
 */
class ScheduleEngineTest extends TestCase
{
    use MakesAcademicData;

    private int $dept;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dept = $this->makeDepartment('قسم الاختبار');
    }

    // ---------------------------------------------------------------- helpers

    /** برنامج بمواده: [[عنوان، ساعات، معلم(user_id)|null، سنة]] */
    private function program(string $name, array $courses, ?int $dept = null): int
    {
        $program = DB::table('programs')->insertGetId(['name' => $name, 'department_id' => $dept ?? $this->dept]);
        foreach ($courses as [$title, $hours, $teacherUser, $year]) {
            $course = $this->makeCourse($program, ['title' => $title, 'hours' => $hours, 'year' => $year ?? 1]);
            if ($teacherUser) {
                $this->assignTeacher($course, DB::table('teachers')->where('user_id', $teacherUser)->value('teacher_id'));
            }
        }

        return $program;
    }

    private function teacher(): int
    {
        return $this->makeTeacher()['user']->user_id;
    }

    private function solve(array $options = [], array $locked = []): array
    {
        $problem = ScheduleProblem::forDepartment($this->dept, $options + ['seed' => 3], $locked);
        $result = (new ScheduleSolver())->solve($problem);

        return [$problem, $result];
    }

    private function assertClean(ScheduleProblem $p, array $result): void
    {
        $this->assertSame([], ScheduleValidator::validate($p, $result['entries']), 'الناتج لا يجوز أن يخالف أي قيد صلب');
    }

    // ------------------------------------------------------------- الحالة السليمة

    public function test_a_feasible_department_is_scheduled_completely_without_conflicts(): void
    {
        $t1 = $this->teacher();
        $t2 = $this->teacher();
        $this->program('برنامج أ', [['شبكات', 3, $t1, 1], ['برمجة', 4.5, $t2, 1], ['رياضيات', 3, $t1, 1], ['فيزياء', 3, $t2, 2]]);

        [$p, $r] = $this->solve();

        $this->assertSame([], $r['unscheduled']);
        $this->assertSame(2 + 3 + 2 + 2, $r['placed']);       // 3س=2 حصة، 4.5س=3 حصص
        $this->assertSame($r['required'], $r['placed']);
        $this->assertClean($p, $r);
    }

    public function test_sessions_per_course_follow_the_hours_and_default_for_zero(): void
    {
        $t = $this->teacher();
        $this->program('أ', [['ثلاث ساعات', 3, $t, 1], ['ست ساعات', 6, $t, 1], ['بلا ساعات', 0, $t, 1]]);

        [, $r] = $this->solve();

        $count = collect($r['entries'])->countBy('course_id');
        $titles = DB::table('courses')->pluck('course_id', 'title');
        $this->assertSame(2, $count[$titles['ثلاث ساعات']]);
        $this->assertSame(4, $count[$titles['ست ساعات']]);
        $this->assertSame(2, $count[$titles['بلا ساعات']], 'ساعات غير معرّفة = الافتراضي 3 ساعات = حصتان');
    }

    public function test_the_same_seed_gives_the_same_schedule_and_other_seeds_stay_valid(): void
    {
        $t1 = $this->teacher();
        $t2 = $this->teacher();
        $this->program('أ', [['م1', 3, $t1, 1], ['م2', 3, $t2, 1], ['م3', 3, $t1, 1], ['م4', 3, $t2, 1]]);
        $this->program('ب', [['م5', 3, $t1, 1], ['م6', 3, $t2, 1]]);

        [$p, $a] = $this->solve(['seed' => 11]);
        [, $b] = $this->solve(['seed' => 11]);
        [$p2, $c] = $this->solve(['seed' => 99]);

        $this->assertSame($a['entries'], $b['entries']);
        $this->assertClean($p, $a);
        $this->assertClean($p2, $c);
        $this->assertSame([], $c['unscheduled']);
    }

    // --------------------------------------------------------------- القيود الصلبة

    public function test_a_teacher_shared_between_groups_is_never_double_booked(): void
    {
        $shared = $this->teacher();
        // معلم واحد يدرّس 4 مواد (8 حصص) في برنامجين: يجب ألا يتعارض أبدًا
        $this->program('أ', [['أ1', 3, $shared, 1], ['أ2', 3, $shared, 1]]);
        $this->program('ب', [['ب1', 3, $shared, 1], ['ب2', 3, $shared, 1]]);

        [$p, $r] = $this->solve();

        $this->assertSame(8, $r['placed']);
        $slots = collect($r['entries'])->map(fn ($e) => $e['day'] . $e['period']);
        $this->assertSame($slots->count(), $slots->unique()->count(), 'لا حصتان للمعلم في الفترة نفسها');
        $this->assertClean($p, $r);
    }

    public function test_a_course_is_never_twice_in_the_same_day_and_extra_sessions_are_explained(): void
    {
        $t = $this->teacher();
        $this->program('أ', [['مادة كثيفة', 9, $t, 1]]);   // 6 حصص مطلوبة والأسبوع 5 أيام

        [$p, $r] = $this->solve();

        $this->assertSame(5, $r['placed'], 'حصة واحدة يوميًا كحد أقصى');
        $days = collect($r['entries'])->pluck('day');
        $this->assertSame($days->count(), $days->unique()->count());
        $this->assertSame('course_days_exhausted', $r['unscheduled'][0]['reason']);
        $this->assertSame(1, $r['unscheduled'][0]['missing']);
        $this->assertStringContainsString('حصة واحدة يوميًا', $r['unscheduled'][0]['message']);
        $this->assertClean($p, $r);
    }

    public function test_an_overfull_group_places_what_fits_and_reports_the_rest_honestly(): void
    {
        $teachers = array_map(fn () => $this->teacher(), range(1, 8));
        $courses = [];
        foreach (range(1, 8) as $i) {
            $courses[] = ["م{$i}", 6, $teachers[$i - 1], 1];   // 4 حصص × 8 مواد = 32 حصة مقابل 25 فترة
        }
        $this->program('مكتظ', $courses);

        [$p, $r] = $this->solve();

        $this->assertSame(25, $r['placed']);
        $this->assertSame(7, array_sum(array_column($r['unscheduled'], 'missing')));
        $this->assertSame(['group_full'], array_values(array_unique(array_column($r['unscheduled'], 'reason'))));
        $this->assertStringContainsString('32', $r['unscheduled'][0]['message']);
        $this->assertClean($p, $r);
        $this->assertSame(7, $p->capacityReport()[0]['overflow']);
    }

    public function test_a_course_without_a_teacher_is_reported_not_scheduled(): void
    {
        $t = $this->teacher();
        $this->program('أ', [['بمعلم', 3, $t, 1], ['بلا معلم', 3, null, 1]]);

        [, $r] = $this->solve();

        $this->assertSame(2, $r['placed']);
        $this->assertSame('no_teacher', $r['unscheduled'][0]['reason']);
        $this->assertSame('بلا معلم', $r['unscheduled'][0]['title']);
        $this->assertSame(2, $r['unscheduled'][0]['missing']);
    }

    public function test_teacher_unavailability_is_a_hard_rule(): void
    {
        $t = $this->teacher();
        $this->program('أ', [['م', 3, $t, 1]]);
        foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday'] as $d) {
            foreach (range(1, 5) as $per) {
                DB::table('teacher_availability')->insert(['teacher_user_id' => $t, 'day' => $d, 'period' => $per, 'kind' => 'unavailable', 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        [$p, $r] = $this->solve();

        // لا يتاح إلا الخميس، وحصة واحدة يوميًا للمادة: توضع حصة وتبقى حصة، بسبب المعلم/قاعدة اليوم
        $this->assertSame(['Thursday'], array_values(array_unique(array_column($r['entries'], 'day'))));
        $this->assertSame(1, $r['placed']);
        $this->assertSame(1, $r['unscheduled'][0]['missing']);
        $this->assertClean($p, $r);
    }

    public function test_slots_a_teacher_prefers_to_avoid_are_not_used_when_there_is_room(): void
    {
        $t = $this->teacher();
        $this->program('أ', [['م', 4.5, $t, 1]]);   // 3 حصص: تكفيها الأيام الثلاثة غير المتجنَّبة
        foreach (['Sunday', 'Monday'] as $d) {
            foreach (range(1, 5) as $per) {
                DB::table('teacher_availability')->insert(['teacher_user_id' => $t, 'day' => $d, 'period' => $per, 'kind' => 'avoid', 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        [$p, $r] = $this->solve();

        $this->assertSame(3, $r['placed']);
        $this->assertSame(0, $r['quality']['avoid']);
        $this->assertNotContains('Sunday', array_column($r['entries'], 'day'));
        $this->assertClean($p, $r);
    }

    public function test_other_departments_bookings_of_the_same_teacher_and_rooms_are_respected(): void
    {
        $t = $this->teacher();
        $this->program('أ', [['م', 3, $t, 1]]);

        $otherDept = $this->makeDepartment('قسم آخر');
        $otherProgram = DB::table('programs')->insertGetId(['name' => 'برنامج آخر', 'department_id' => $otherDept]);
        $otherCourse = $this->makeCourse($otherProgram, ['title' => 'مادة قسم آخر']);
        // المعلم نفسه محجوز الأحد والإثنين في كل الفترات الخمس من قسم آخر
        foreach (['Sunday', 'Monday'] as $d) {
            foreach (config('schedule.periods') as $n => [$start, $end]) {
                if ($n > 5) {
                    continue;
                }
                DB::table('schedules')->insert(['course_id' => $otherCourse, 'teacher_id' => $t, 'day' => $d, 'start_time' => "$start:00", 'end_time' => "$end:00", 'room' => 'R', 'class_group' => 'x', 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        [$p, $r] = $this->solve();

        $this->assertSame(2, $r['placed']);
        $this->assertEmpty(array_intersect(['Sunday', 'Monday'], array_column($r['entries'], 'day')));
        $this->assertClean($p, $r);
    }

    // ------------------------------------------------------------------ القاعات

    public function test_with_a_room_pool_two_groups_never_share_a_room_at_once(): void
    {
        $t1 = $this->teacher();
        $t2 = $this->teacher();
        $this->program('أ', [['أ1', 3, $t1, 1], ['أ2', 3, $t1, 1]]);
        $this->program('ب', [['ب1', 3, $t2, 1], ['ب2', 3, $t2, 1]]);
        foreach (['قاعة 1', 'قاعة 2'] as $n) {
            DB::table('rooms')->insert(['name' => $n, 'capacity' => 50, 'department_id' => $this->dept, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        [$p, $r] = $this->solve();

        $this->assertSame(8, $r['placed']);
        $keys = collect($r['entries'])->map(fn ($e) => $e['room'] . '|' . $e['day'] . '|' . $e['period']);
        $this->assertSame($keys->count(), $keys->unique()->count());
        $this->assertEmpty(array_diff(array_column($r['entries'], 'room'), ['قاعة 1', 'قاعة 2']));
        $this->assertClean($p, $r);
    }

    public function test_one_room_for_two_groups_serializes_them_and_reports_when_it_runs_out(): void
    {
        $t1 = $this->teacher();
        $t2 = $this->teacher();
        // كل شعبة تحتاج 16 حصة (8 مواد × 2)؛ قاعة واحدة فقط = 25 فترة للشعبتين معًا (32 مطلوبة)
        $a = [];
        $b = [];
        foreach (range(1, 8) as $i) {
            $a[] = ["أ{$i}", 3, $t1, 1];
            $b[] = ["ب{$i}", 3, $t2, 1];
        }
        $this->program('أ', $a);
        $this->program('ب', $b);
        DB::table('rooms')->insert(['name' => 'الوحيدة', 'capacity' => null, 'department_id' => $this->dept, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        [$p, $r] = $this->solve();

        $this->assertLessThanOrEqual(25, $r['placed']);
        $this->assertGreaterThan(0, count($r['unscheduled']));
        $this->assertContains($r['unscheduled'][0]['reason'], ['no_room', 'teacher_conflict', 'no_slot']);
        $this->assertClean($p, $r);
    }

    public function test_room_capacity_is_a_hard_rule(): void
    {
        $t = $this->teacher();
        $program = $this->program('أ', [['م', 3, $t, 1]]);
        foreach (range(1, 5) as $i) {
            $s = $this->makeStudent(['academic_year' => 'السنة الأولى'], $program);
        }
        DB::table('rooms')->insert(['name' => 'صغيرة', 'capacity' => 3, 'department_id' => $this->dept, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        [$p, $r] = $this->solve();

        $this->assertSame(0, $r['placed']);
        $this->assertSame('no_room', $r['unscheduled'][0]['reason']);
        $this->assertSame(5, $p->groups[array_key_first($p->groups)]['size']);
    }

    // -------------------------------------------------------------- مثبّتة وجودة

    public function test_locked_entries_are_kept_exactly_and_counted_as_placed(): void
    {
        $t = $this->teacher();
        $program = $this->program('أ', [['م', 3, $t, 1], ['ن', 3, $t, 1]]);
        $course = DB::table('courses')->where('title', 'م')->value('course_id');
        $locked = [['group' => "$program-1", 'course_id' => $course, 'teacher' => $t, 'day' => 'Wednesday', 'period' => 4, 'room' => 'قاعة', 'locked' => true]];

        [$p, $r] = $this->solve([], $locked);

        $this->assertSame(4, $r['placed']);
        $kept = collect($r['entries'])->firstWhere(fn ($e) => $e['day'] === 'Wednesday' && $e['period'] === 4 && $e['course_id'] === $course);
        $this->assertNotNull($kept);
        $this->assertTrue($kept['locked']);
        $this->assertSame(2, collect($r['entries'])->where('course_id', $course)->count(), 'حصة مثبّتة + حصة جديدة فقط، لا ثلاث');
        $this->assertClean($p, $r);
    }

    public function test_local_search_never_makes_the_schedule_worse_and_removes_gaps(): void
    {
        $t = [$this->teacher(), $this->teacher(), $this->teacher()];
        $this->program('أ', [['م1', 6, $t[0], 1], ['م2', 6, $t[1], 1], ['م3', 3, $t[2], 1]]);

        config(['schedule.search.iterations' => 0, 'schedule.search.restarts' => 1]);
        [, $without] = $this->solve();
        config(['schedule.search.iterations' => 1500]);
        [$p, $with] = $this->solve();

        $this->assertLessThanOrEqual($without['quality']['total'], $with['quality']['total']);
        $this->assertSame(0, $with['quality']['gaps'], 'جدول مريح بلا فترات فارغة بين المحاضرات');
        $this->assertClean($p, $with);
    }

    // -------------------------------------------------------------------- المدقق

    public function test_validator_detects_every_hard_violation_type(): void
    {
        $t = $this->teacher();
        $program = $this->program('أ', [['م', 3, $t, 1], ['ن', 3, $t, 1]]);
        $c1 = DB::table('courses')->where('title', 'م')->value('course_id');
        $c2 = DB::table('courses')->where('title', 'ن')->value('course_id');
        $g = "$program-1";
        $p = ScheduleProblem::forDepartment($this->dept, ['seed' => 1]);
        $p->unavailable["$t|Sunday|3"] = true;
        $p->busyTeacher["$t|Monday|1"] = true;

        $e = fn ($course, $day, $period, $room = 'ق') => ['group' => $g, 'course_id' => $course, 'teacher' => $t, 'day' => $day, 'period' => $period, 'room' => $room];

        $types = fn (array $entries) => array_unique(array_column(ScheduleValidator::validate($p, $entries), 'type'));

        $this->assertContains('group_clash', $types([$e($c1, 'Tuesday', 2), $e($c2, 'Tuesday', 2)]));
        $this->assertContains('teacher_clash', $types([$e($c1, 'Tuesday', 2), $e($c2, 'Tuesday', 2)]));
        $this->assertContains('same_course_day', $types([$e($c1, 'Tuesday', 2), $e($c1, 'Tuesday', 3)]));
        $this->assertContains('teacher_unavailable', $types([$e($c1, 'Sunday', 3)]));
        $this->assertContains('external_teacher_clash', $types([$e($c1, 'Monday', 1)]));
        $this->assertContains('out_of_range', $types([$e($c1, 'Friday', 1)]));
        $this->assertContains('out_of_range', $types([$e($c1, 'Sunday', 9)]));
        $this->assertContains('unknown_course', $types([$e(999999, 'Sunday', 1)]));
        $this->assertSame([], ScheduleValidator::validate($p, [$e($c1, 'Tuesday', 2), $e($c2, 'Wednesday', 3)]));
    }

    public function test_quality_measures_gaps_imbalance_adjacent_days_and_late_periods(): void
    {
        $t = $this->teacher();
        $program = $this->program('أ', [['م', 3, $t, 1]]);
        $c = DB::table('courses')->value('course_id');
        $p = ScheduleProblem::forDepartment($this->dept, ['seed' => 1, 'periods' => 7]);
        $g = "$program-1";
        $e = fn ($day, $period) => ['group' => $g, 'course_id' => $c, 'teacher' => $t, 'day' => $day, 'period' => $period, 'room' => 'ق'];

        $good = ScheduleQuality::groupCost($p, [$e('Sunday', 1), $e('Tuesday', 1)]);
        $this->assertSame(0, $good['gaps']);
        $this->assertSame(0, $good['adjacent']);

        $bad = ScheduleQuality::groupCost($p, [$e('Sunday', 1), $e('Monday', 7)]);
        $this->assertSame(1, $bad['adjacent']);
        $this->assertSame(1, $bad['late']);
        $this->assertGreaterThan($good['total'], $bad['total']);

        $gap = ScheduleQuality::groupCost($p, [$e('Sunday', 1), $e('Sunday', 4)]);
        $this->assertSame(2, $gap['gaps']);
    }

    public function test_capacity_report_counts_needed_available_overflow_and_missing_teachers(): void
    {
        $t = $this->teacher();
        $this->program('أ', [['أ', 6, $t, 1], ['ب', 3, null, 1]]);

        $r = ScheduleProblem::forDepartment($this->dept)->capacityReport()[0];

        $this->assertSame(4, $r['needed']);
        $this->assertSame(25, $r['available']);
        $this->assertSame(0, $r['overflow']);
        $this->assertSame(2, $r['no_teacher']);
    }
}
