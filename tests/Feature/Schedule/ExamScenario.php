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

/** بيانات مشتركة لاختبارات الجدول الامتحاني: قسم بثلاث شعب ومقرراتها وطلابها. */
abstract class ExamScenario extends TestCase
{
    use MakesAcademicData;

    protected int $dept;
    protected int $programA;
    protected int $programB;
    protected int $semester;
    /** @var array<string,int[]> group key => course ids */
    protected array $courses = [];
    /** @var array<string,int[]> group key => student ids */
    protected array $students = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->semester = Semester::currentOrCreate()->semester_id;
        $this->dept = $this->makeDepartment('قسم');
        $this->programA = DB::table('programs')->insertGetId(['name' => 'معلوماتية', 'department_id' => $this->dept]);
        $this->programB = DB::table('programs')->insertGetId(['name' => 'اتصالات', 'department_id' => $this->dept]);

        // معلوماتية سنة 1: 3 مقررات و6 طلاب. معلوماتية سنة 2: مقرران و4 طلاب. اتصالات سنة 1: مقرر واحد و3 طلاب.
        $this->group($this->programA, 1, ['أ1', 'أ2', 'أ3'], 6);
        $this->group($this->programA, 2, ['ب1', 'ب2'], 4);
        $this->group($this->programB, 1, ['ت1'], 3);
    }

    protected function group(int $program, int $year, array $titles, int $students): void
    {
        $key = $program . '-' . $year;
        foreach ($titles as $t) {
            $this->courses[$key][] = $this->makeCourse($program, ['title' => $t, 'hours' => 3, 'year' => $year]);
        }
        foreach (range(1, $students) as $i) {
            $s = $this->makeStudent(['full_name' => "طالب $key $i", 'academic_year' => ScheduleProblemYear::label($year), 'status' => 'active'], $program);
            $this->students[$key][] = $s['student_id'];
            foreach ($this->courses[$key] as $c) {
                $this->enroll($s['student_id'], $c);
            }
        }
    }

    protected function room(string $name, int $cap, ?int $program = null, ?int $year = null): void
    {
        Room::create(['name' => $name, 'capacity' => $cap, 'department_id' => $this->dept, 'is_active' => true, 'program_id' => $program, 'year' => $year, 'type' => 'classroom']);
    }

    /** فترات رئيس القسم: مذاكرات في أول أسبوعين، وامتحانات بعدها بشهر. */
    protected function kinds(array $override = []): array
    {
        return array_replace_recursive([
            'midterm' => ['from' => '2026-11-01', 'to' => '2026-11-12', 'slots' => [['09:00', '10:30'], ['11:00', '12:30']], 'duration' => 60],
            'final' => ['from' => '2026-12-06', 'to' => '2026-12-17', 'slots' => [['09:00', '11:30'], ['12:00', '14:30']], 'duration' => 120],
        ], $override);
    }

    protected function problem(array $opts = []): ExamProblem
    {
        return ExamProblem::forDepartment($this->dept, $opts + ['kinds' => $this->kinds(), 'seed' => 5]);
    }

    protected function solve(array $opts = []): array
    {
        $p = $this->problem($opts);

        return [$p, (new ExamSolver())->solve($p)];
    }
}

/** تحويل رقم السنة إلى قيمة users.academic_year */
final class ScheduleProblemYear
{
    public static function label(int $year): string
    {
        return \App\Services\Schedule\ScheduleProblem::academicYear($year);
    }
}
