<?php

namespace Tests\Feature\Schedule;

use App\Models\Room;
use App\Models\Semester;
use App\Services\Schedule\ExamProblem;
use App\Services\Schedule\ExamSolver;
use App\Services\Schedule\ExamValidator;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * مولّد الجدول الامتحاني (مذاكرة + امتحان): قيود صلبة، توزيع الطلاب على القاعات، مدخلات رئيس القسم.
 */
class ExamSolverTest extends ExamScenario
{
    // ───────────────────────────── التوليد الأساسي ─────────────────────────────

    public function test_every_assessment_is_placed_without_any_violation(): void
    {
        $this->room('قاعة أ1', 6, $this->programA, 1);
        $this->room('قاعة ب1', 4, $this->programA, 2);
        $this->room('قاعة ت1', 3, $this->programB, 1);

        [$p, $r] = $this->solve();

        $this->assertSame(12, $r['required']);                 // (3+2+1) مقرراً × نوعان
        $this->assertSame(12, $r['placed']);
        $this->assertSame([], $r['unscheduled']);
        $this->assertSame([], ExamValidator::validate($p, $r['entries']));
    }

    public function test_each_kind_stays_inside_its_own_period_and_the_midterm_comes_first(): void
    {
        $this->room('قاعة أ1', 6, $this->programA, 1);
        [$p, $r] = $this->solve();

        foreach ($r['entries'] as $e) {
            $period = $this->kinds()[$e['kind']];
            $this->assertGreaterThanOrEqual($period['from'], $e['date']);
            $this->assertLessThanOrEqual($period['to'], $e['date']);
        }
        $byKey = array_column($r['entries'], null, 'key');
        foreach ($p->groups as $gk => $g) {
            foreach ($g['courses'] as $c) {
                $this->assertLessThan($byKey["$gk|{$c['course_id']}|final"]['date'], $byKey["$gk|{$c['course_id']}|midterm"]['date']);
            }
        }
    }

    public function test_one_assessment_per_group_per_day_across_both_kinds(): void
    {
        $this->room('قاعة', 20);
        [$p, $r] = $this->solve();

        $perDay = [];
        foreach ($r['entries'] as $e) {
            $perDay[$e['group'] . '|' . $e['date']] = ($perDay[$e['group'] . '|' . $e['date']] ?? 0) + 1;
        }
        $this->assertSame([], array_filter($perDay, fn ($n) => $n > 1), 'لا شعبة بتقييمين في يوم واحد');
    }

    public function test_the_same_seed_gives_the_same_schedule(): void
    {
        $this->room('قاعة', 20);

        [, $a] = $this->solve();
        [, $b] = $this->solve();

        $this->assertSame($a['entries'], $b['entries']);
    }

    // ───────────────────────────── توزيع الطلاب على القاعات ─────────────────────────────

    public function test_students_are_spread_over_several_rooms_and_each_is_seated_once(): void
    {
        $this->room('قاعة أ1', 4, $this->programA, 1);      // الشعبة 6 طلاب: لا تتسع في قاعتها وحدها
        $this->room('قاعة إضافية', 4);
        [$p, $r] = $this->solve(['kinds' => $this->kinds(['final' => ['slots' => [['09:00', '11:30']]]])]);

        $e = collect($r['entries'])->firstWhere('key', $this->programA . '-1|' . $this->courses[$this->programA . '-1'][0] . '|final');
        $this->assertNotNull($e);
        $this->assertCount(2, $e['rooms']);
        $this->assertSame('قاعة أ1', $e['rooms'][0]['name'], 'قاعة الشعبة الخاصة أولاً');
        $this->assertSame([3, 3], array_map(fn ($x) => count($x['students']), $e['rooms']), 'توزيع متوازن');

        $seated = array_merge(...array_column($e['rooms'], 'students'));
        $this->assertEqualsCanonicalizing($this->students[$this->programA . '-1'], $seated);
        $this->assertCount(6, array_unique($seated));
        foreach ($e['rooms'] as $room) {
            $this->assertLessThanOrEqual($room['capacity'], count($room['students']));
        }
    }

    public function test_distribution_is_proportional_to_capacity_and_never_exceeds_it(): void
    {
        $names = [1 => 'أ', 2 => 'ب', 3 => 'ج', 4 => 'د', 5 => 'هـ', 6 => 'و', 7 => 'ز', 8 => 'ح', 9 => 'ط'];
        $out = ExamSolver::distribute(array_keys($names), [['name' => 'كبيرة', 'capacity' => 6], ['name' => 'صغيرة', 'capacity' => 3]], $names);

        $this->assertSame([6, 3], array_map(fn ($r) => count($r['students']), $out));

        $tight = ExamSolver::distribute([1, 2, 3, 4, 5], [['name' => 'x', 'capacity' => 4], ['name' => 'y', 'capacity' => 4]], [1 => 'أ', 2 => 'ب', 3 => 'ج', 4 => 'د', 5 => 'هـ']);
        $this->assertSame([3, 2], array_map(fn ($r) => count($r['students']), $tight));
    }

    public function test_students_are_listed_in_name_order_inside_each_room(): void
    {
        $out = ExamSolver::distribute([3, 1, 2], [['name' => 'x', 'capacity' => 5]], [1 => 'سامي', 2 => 'أحمد', 3 => 'باسل']);

        $this->assertSame([2, 3, 1], $out[0]['students']);
    }

    // ───────────────────────────── ما يستحيل ─────────────────────────────

    public function test_too_few_days_leaves_the_rest_unscheduled_with_a_reason_and_never_a_clash(): void
    {
        $this->room('قاعة', 20);
        // مجموعة معلوماتية سنة 1 فيها 3 مقررات لكن الفترة يومان فقط
        [$p, $r] = $this->solve(['kinds' => $this->kinds(['final' => ['from' => '2026-12-06', 'to' => '2026-12-07']])]);

        $this->assertNotEmpty($r['unscheduled']);
        $row = collect($r['unscheduled'])->firstWhere('group', $this->programA . '-1');
        $this->assertSame('no_free_day', $row['reason']);
        $this->assertStringContainsString('مدّد الفترة', $row['message']);
        $this->assertSame([], ExamValidator::validate($p, $r['entries']), 'ما وُضع سليم');
    }

    public function test_rooms_that_cannot_hold_a_group_leave_it_unscheduled_as_no_room(): void
    {
        $this->room('صغيرة', 2);     // أصغر من أي شعبة

        [, $r] = $this->solve();

        $this->assertSame(0, $r['placed']);
        $this->assertSame('no_room', $r['unscheduled'][0]['reason']);
    }

    public function test_capacity_report_flags_a_period_with_too_few_days(): void
    {
        $this->room('قاعة', 20);
        $p = $this->problem(['kinds' => $this->kinds(['final' => ['from' => '2026-12-06', 'to' => '2026-12-08']])]);

        $row = collect($p->capacityReport())->first(fn ($r) => $r['group'] === $this->programA . '-1' && $r['kind'] === 'final');

        $this->assertSame([3, 3, 0], [$row['needed_days'], $row['available_days'], $row['overflow']]);
        $tight = collect($p->capacityReport())->first(fn ($r) => $r['group'] === $this->programA . '-1' && $r['kind'] === 'midterm');
        $this->assertSame(0, $tight['overflow']);

        $short = $this->problem(['kinds' => $this->kinds(['final' => ['from' => '2026-12-06', 'to' => '2026-12-07']])]);
        $row2 = collect($short->capacityReport())->first(fn ($r) => $r['group'] === $this->programA . '-1' && $r['kind'] === 'final');
        $this->assertSame(1, $row2['overflow']);
    }

    // ───────────────────────────── تعارض على مستوى الطالب ─────────────────────────────

    public function test_a_student_shared_between_groups_never_has_two_assessments_on_one_day(): void
    {
        $this->room('قاعة', 30);
        // طالب سنة أولى يعيد مقرراً من سنة ثانية: مسجّل بمقرر من الشعبة الأخرى (نفس البرنامج)
        $repeater = $this->students[$this->programA . '-1'][0];
        $this->enroll($repeater, $this->courses[$this->programA . '-2'][0]);

        [$p, $r] = $this->solve();

        $this->assertSame([], ExamValidator::validate($p, $r['entries']));
        $byDate = [];
        foreach ($r['entries'] as $e) {
            if (in_array($repeater, array_merge(...array_column($e['rooms'], 'students')), true)) {
                $byDate[$e['date']][] = $e['key'];
            }
        }
        $this->assertSame([], array_filter($byDate, fn ($k) => count($k) > 1));
    }

    public function test_a_shared_course_gets_its_own_assessment_for_each_program(): void
    {
        $this->room('قاعة', 30);
        $shared = $this->courses[$this->programA . '-1'][0];
        DB::table('course_program')->insert(['course_id' => $shared, 'program_id' => $this->programB]);
        $b = $this->makeStudent(['full_name' => 'طالب اتصالات إضافي', 'academic_year' => 'السنة الأولى', 'status' => 'active'], $this->programB);
        $this->enroll($b['student_id'], $shared);

        [$p, $r] = $this->solve();

        $keys = array_column($r['entries'], 'key');
        $this->assertContains($this->programA . "-1|$shared|final", $keys);
        $this->assertContains($this->programB . "-1|$shared|final", $keys, 'لكل دورة تقييمها الخاص');
        $inB = collect($r['entries'])->firstWhere('key', $this->programB . "-1|$shared|final");
        $this->assertSame([$b['student_id']], array_merge(...array_column($inB['rooms'], 'students')), 'طلاب اتصالات فقط');
    }

    // ───────────────────────────── من يدخل الجدول ─────────────────────────────

    public function test_dropped_and_inactive_students_are_not_seated_and_other_semesters_are_ignored(): void
    {
        $this->room('قاعة', 30);
        $g = $this->programA . '-1';
        $dropped = $this->students[$g][0];
        DB::table('enrollments')->where('student_id', $dropped)->update(['status' => 'dropped']);
        $inactive = $this->students[$g][1];
        DB::table('users')->where('user_id', DB::table('students')->where('student_id', $inactive)->value('user_id'))->update(['status' => 'inactive']);
        $other = DB::table('semesters')->insertGetId(['name' => 'آخر', 'start_date' => '2027-02-01', 'end_date' => '2027-06-30', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);
        $this->makeCourse($this->programA, ['title' => 'فصل آخر', 'hours' => 3, 'year' => 1, 'semester_id' => $other]);

        [$p, $r] = $this->solve();

        $e = collect($r['entries'])->firstWhere('key', $g . '|' . $this->courses[$g][0] . '|final');
        $seated = array_merge(...array_column($e['rooms'], 'students'));
        $this->assertNotContains($dropped, $seated);
        $this->assertNotContains($inactive, $seated);
        $this->assertCount(4, $seated);
        $this->assertNotContains('فصل آخر', array_column($p->tasks, 'title'));
    }

    public function test_only_the_requested_kinds_are_generated(): void
    {
        $this->room('قاعة', 30);

        $mid = $this->solve(['kinds' => ['midterm' => $this->kinds()['midterm']]]);
        $this->assertSame(['midterm'], array_values(array_unique(array_column($mid[1]['entries'], 'kind'))));
        $this->assertSame(6, $mid[1]['placed']);

        $fin = $this->solve(['kinds' => ['final' => $this->kinds()['final']]]);
        $this->assertSame(['final'], array_values(array_unique(array_column($fin[1]['entries'], 'kind'))));
    }

    // ───────────────────────────── مدخلات رئيس القسم ─────────────────────────────

    public function test_weekends_and_excluded_days_are_never_used(): void
    {
        $this->room('قاعة', 30);
        $kinds = $this->kinds(['midterm' => ['excluded' => ['2026-11-02']]]);

        [, $r] = $this->solve(['kinds' => $kinds]);

        foreach ($r['entries'] as $e) {
            $this->assertNotContains(date('l', strtotime($e['date'])), ['Friday', 'Saturday']);
            $this->assertNotSame('2026-11-02', $e['date']);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badInputs')]
    public function test_bad_inputs_are_refused_with_a_clear_arabic_message(array $kinds, string $needle): void
    {
        try {
            ExamProblem::normalizeKinds($kinds);
            $this->fail('كان يجب الرفض');
        } catch (ScheduleRuleException $e) {
            $this->assertStringContainsString($needle, implode(' ', $e->messages));
        }
    }

    public static function badInputs(): array
    {
        $ok = ['from' => '2026-11-01', 'to' => '2026-11-12', 'slots' => [['09:00', '10:30']], 'duration' => 60];

        return [
            'لا شيء' => [[], 'حدّد فترة'],
            'نهاية قبل البداية' => [['final' => ['from' => '2026-12-10', 'to' => '2026-12-01'] + $ok], 'النهاية قبل البداية'],
            'تاريخ غير صالح' => [['final' => ['from' => 'x', 'to' => 'y'] + $ok], 'تاريخ بداية ونهاية صالحين'],
            'تاريخ مستحيل' => [['final' => ['from' => '2026-02-30', 'to' => '2026-03-05'] + $ok], 'تاريخ بداية ونهاية صالحين'],
            'تاريخ بصيغة أخرى' => [['final' => ['from' => '12/06/2026', 'to' => '12/17/2026'] + $ok], 'تاريخ بداية ونهاية صالحين'],
            'مستثنى فاسد' => [['final' => ['excluded' => ['غداً']] + $ok], 'مستثنى غير صالح'],
            'بلا فترات يومية' => [['final' => ['slots' => []] + $ok], 'فترة يومية واحدة'],
            'فترة معكوسة' => [['final' => ['slots' => [['11:00', '09:00']]] + $ok], 'غير صالحة'],
            'فترتان متداخلتان' => [['final' => ['slots' => [['09:00', '11:00'], ['10:30', '12:00']]] + $ok], 'متداخلتان'],
            'مدة أقصر من 15' => [['final' => ['duration' => 5] + $ok], 'بين 15 و360'],
            'مدة أطول من الفترة' => [['final' => ['duration' => 200] + $ok], 'أطول من أطول فترة'],
            'امتحانات قبل مذاكرات' => [['midterm' => ['from' => '2026-12-01', 'to' => '2026-12-20'] + $ok, 'final' => ['from' => '2026-11-01', 'to' => '2026-11-12'] + $ok], 'بعد فترة المذاكرات'],
        ];
    }

    public function test_a_valid_input_is_normalised(): void
    {
        $k = ExamProblem::normalizeKinds(['final' => ['from' => '2026-12-06', 'to' => '2026-12-17', 'slots' => [['12:00', '14:00'], ['09:00', '11:00']], 'duration' => 90, 'excluded' => ['2026-12-08', '2026-12-08']]]);

        $this->assertSame([['09:00', '11:00'], ['12:00', '14:00']], $k['final']['slots'], 'تُرتَّب الفترات');
        $this->assertSame(['2026-12-08'], $k['final']['excluded']);
        $this->assertArrayNotHasKey('midterm', $k);
    }

    // ───────────────────────────── المُدقّق يرفض أي كسر ─────────────────────────────

    public function test_the_validator_detects_each_hard_rule_when_broken(): void
    {
        $this->room('قاعة أ1', 6, $this->programA, 1);
        $this->room('قاعة ب1', 4, $this->programA, 2);
        [$p, $r] = $this->solve();
        $good = $r['entries'];

        $types = fn (array $entries) => array_unique(array_column(ExamValidator::validate($p, $entries), 'type'));
        $find = fn (string $key) => array_search($key, array_column($good, 'key'), true);

        $g1 = $this->programA . '-1';
        [$c1, $c2] = [$this->courses[$g1][0], $this->courses[$g1][1]];

        // يوم واحد لطالبين مشتركين (نفس الشعبة): ننقل تقييماً إلى يوم تقييم آخر للشعبة
        $x = $good;
        $x[$find("$g1|$c2|final")]['date'] = $x[$find("$g1|$c1|final")]['date'];
        $this->assertContains('student_day_clash', $types($x));

        // خارج الفترة
        $x = $good;
        $x[$find("$g1|$c1|final")]['date'] = '2026-12-01';
        $this->assertContains('out_of_period', $types($x));

        // فترة غير معرّفة
        $x = $good;
        $x[$find("$g1|$c1|final")]['start'] = '03:00';
        $this->assertContains('bad_slot', $types($x));

        // تجاوز السعة
        $x = $good;
        $i = $find("$g1|$c1|final");
        $x[$i]['rooms'][0]['capacity'] = 99;
        $x[$i]['rooms'][0]['students'] = array_merge($x[$i]['rooms'][0]['students'], range(9000, 9010));
        $this->assertContains('room_capacity', $types($x));

        // طالب بلا قاعة
        $x = $good;
        $x[$i]['rooms'][0]['students'] = array_slice($x[$i]['rooms'][0]['students'], 1);
        $this->assertContains('unseated', $types($x));

        // قاعة غير مسجّلة
        $x = $good;
        $x[$i]['rooms'][0]['name'] = 'قاعة وهمية';
        $this->assertContains('unknown_room', $types($x));

    }

    public function test_the_order_rule_holds_even_when_the_two_periods_overlap(): void
    {
        $this->room('قاعة', 30);
        $kinds = $this->kinds([
            'midterm' => ['from' => '2026-11-01', 'to' => '2026-11-30'],
            'final' => ['from' => '2026-11-15', 'to' => '2026-12-17'],
        ]);

        [$p, $r] = $this->solve(['kinds' => $kinds]);

        $this->assertSame([], $r['unscheduled']);
        $this->assertSame([], ExamValidator::validate($p, $r['entries']));
        $byKey = array_column($r['entries'], null, 'key');
        foreach ($p->groups as $gk => $g) {
            foreach ($g['courses'] as $c) {
                $this->assertLessThan($byKey["$gk|{$c['course_id']}|final"]['date'], $byKey["$gk|{$c['course_id']}|midterm"]['date']);
            }
        }

        // ونخالفها يدوياً فيرفضها المُدقّق
        $g1 = $this->programA . '-1';
        $c1 = $this->courses[$g1][0];
        $x = $r['entries'];
        $m = array_search("$g1|$c1|midterm", array_column($x, 'key'), true);
        $f = array_search("$g1|$c1|final", array_column($x, 'key'), true);
        [$x[$m]['date'], $x[$f]['date']] = [$x[$f]['date'], $x[$m]['date']];
        $this->assertContains('order', array_column(ExamValidator::validate($p, $x), 'type'));
    }

    public function test_the_validator_catches_a_room_clash_and_a_proctor_in_two_rooms(): void
    {
        $this->room('قاعة', 30);
        [$p, $r] = $this->solve();
        $e = $r['entries'];

        // تقييمان في القاعة نفسها وبنفس الوقت
        $x = $e;
        $x[1]['date'] = $x[0]['date'];
        $x[1]['start'] = $x[0]['start'];
        $x[1]['end'] = $x[0]['end'];
        $x[1]['slot'] = $x[0]['slot'];
        $x[1]['kind'] = $x[0]['kind'];
        $x[1]['rooms'][0]['name'] = $x[0]['rooms'][0]['name'];
        $this->assertContains('room_clash', array_column(ExamValidator::validate($p, $x), 'type'));

        // مراقب واحد في قاعتين بنفس الوقت
        $y = $e;
        $y[0]['rooms'][0]['proctor'] = 77;
        $y[1] = $y[0];
        $y[1]['key'] = $e[1]['key'];
        $y[1]['rooms'][0]['name'] = 'أخرى';
        $this->assertContains('proctor_clash', array_column(ExamValidator::validate($p, $y), 'type'));
    }
}
