<?php

namespace Tests\Feature\Schedule;

use App\Models\Exam;
use App\Models\ExamInvigilator;
use App\Models\ExamSeating;
use App\Models\ScheduleDraft;
use App\Services\Schedule\ExamDraftService;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Support\Facades\DB;

/** دورة حياة المسودة الامتحانية: توليد ← تعديل ← اعتماد ← تراجع، دون المساس بالعلامات. */
class ExamDraftServiceTest extends ExamScenario
{
    protected $head;
    protected ExamDraftService $svc;
    protected array $teachers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->room('قاعة أ1', 4, $this->programA, 1);   // أصغر من الشعبة (6) فتتوزع على قاعتين
        $this->room('قاعة أ1 ب', 4, $this->programA, 1);
        $this->room('قاعة ب1', 4, $this->programA, 2);
        $this->room('قاعة ت1', 3, $this->programB, 1);
        $this->room('قاعة عامة', 10);
        $this->head = $this->makeHead($this->dept)['user'];
        $this->svc = app(ExamDraftService::class);
        $deptName = DB::table('departments')->where('department_id', $this->dept)->value('name');
        foreach (['م1', 'م2'] as $n) {
            $this->teachers[] = $this->makeTeacher(['full_name' => $n, 'department' => $deptName])['user']->user_id;
        }
    }

    protected function draft(): ScheduleDraft
    {
        return $this->svc->generate($this->head, ['kinds' => $this->kinds(), 'seed' => 5]);
    }

    public function test_generate_saves_a_clean_draft_and_touches_no_real_exam(): void
    {
        $d = $this->draft();

        $this->assertSame(ScheduleDraft::TYPE_EXAM, $d->type);
        $this->assertSame(12, $d->examEntries()->count());
        $this->assertSame([], $d->stats['violations']);
        $this->assertSame([], $d->stats['unscheduled']);
        $this->assertSame(0, Exam::count());
    }

    public function test_approve_creates_exams_seatings_and_invigilators_and_notifies(): void
    {
        $d = $this->draft();
        $e = $d->examEntries()->first();
        $d = $this->svc->setProctor($d, $e->id, $e->rooms[0]['name'], $this->teachers[0]);

        $d = $this->svc->approve($d, $this->head, true);

        $this->assertSame('approved', $d->status);
        $this->assertSame(12, Exam::count());
        $exam = Exam::where('course_id', $e->course_id)->where('exam_type', $e->kind)->first();
        $this->assertSame($e->duration_minutes, $exam->duration_minutes);
        $this->assertSame(array_sum(array_map(fn ($r) => count($r['students']), $e->rooms)), ExamSeating::where('exam_id', $exam->exam_id)->count());
        $this->assertSame(1, ExamInvigilator::where('exam_id', $exam->exam_id)->count());
        $this->assertGreaterThan(0, DB::table('notifications')->where('type', 'exam_schedule_approved')->count());
        $this->assertSame(1, DB::table('notifications')->where('type', 'exam_invigilation')->where('user_id', $this->teachers[0])->count());
    }

    public function test_unproctored_rooms_need_explicit_acknowledgement(): void
    {
        $d = $this->draft();
        $this->expectException(ScheduleRuleException::class);
        $this->svc->approve($d, $this->head);
    }

    public function test_approve_updates_in_place_and_never_deletes_graded_exams(): void
    {
        $d = $this->draft();
        $e = $d->examEntries()->where('kind', 'final')->first();
        $old = Exam::create(['course_id' => $e->course_id, 'exam_name' => 'قديم', 'exam_type' => 'final', 'class_group' => $e->class_group, 'exam_date' => '2026-01-01 08:00:00', 'max_score' => 100]);
        $student = $this->students[$e->program_id . '-' . $e->year][0];
        DB::table('grades')->insert(['student_id' => $student, 'exam_id' => $old->exam_id, 'score' => 80, 'created_at' => now(), 'updated_at' => now()]);

        $this->svc->approve($d, $this->head, true);

        $this->assertSame(1, Exam::where('exam_id', $old->exam_id)->count());
        $this->assertSame(1, DB::table('grades')->where('exam_id', $old->exam_id)->count());
        $this->assertStringStartsWith($e->exam_date->toDateString(), (string) Exam::find($old->exam_id)->getRawOriginal('exam_date'));
        $this->assertSame(12, Exam::count());
    }

    public function test_revert_restores_updated_rows_and_removes_inserted_ones(): void
    {
        $d = $this->draft();
        $e = $d->examEntries()->where('kind', 'final')->first();
        $old = Exam::create(['course_id' => $e->course_id, 'exam_name' => 'قديم', 'exam_type' => 'final', 'class_group' => $e->class_group, 'exam_date' => '2026-01-01 08:00:00', 'max_score' => 100]);

        $d = $this->svc->approve($d, $this->head, true);
        $this->svc->revert($d, $this->head);

        $this->assertSame(1, Exam::count());
        $this->assertSame('قديم', Exam::find($old->exam_id)->exam_name);
        $this->assertSame(0, ExamSeating::count());
    }

    public function test_move_rejects_a_second_assessment_for_the_same_group_on_one_day(): void
    {
        $d = $this->draft();
        $e = $d->examEntries()->where('kind', 'final')->first();
        $other = $d->examEntries()->where('kind', 'final')->where('id', '!=', $e->id)->where('program_id', $e->program_id)->where('year', $e->year)->first();

        $this->expectException(ScheduleRuleException::class);
        $this->svc->move($d, $e->id, $other->exam_date->toDateString(), 0);
    }

    public function test_move_to_a_free_day_updates_the_entry(): void
    {
        $d = $this->draft();
        $e = $d->examEntries()->where('kind', 'final')->where('program_id', $this->programB)->first();
        $day = '2026-12-17';

        $d = $this->svc->move($d, $e->id, $day, 1);

        $this->assertSame($day, $d->examEntries()->find($e->id)->exam_date->toDateString());
        $this->assertSame([], $d->stats['violations']);
    }

    public function test_move_student_between_rooms_respects_capacity(): void
    {
        $d = $this->draft();
        $e = $d->examEntries()->get()->first(fn ($x) => count($x->rooms) >= 2);
        $this->assertNotNull($e, 'الشعبة الأولى تتوزع على قاعتين');
        $sid = $e->rooms[0]['students'][0];

        $d = $this->svc->moveStudent($d, $e->id, $sid, $e->rooms[1]['name']);
        $row = $d->examEntries()->find($e->id);
        $this->assertContains($sid, $row->rooms[1]['students']);
        $this->assertNotContains($sid, $row->rooms[0]['students']);
    }

    public function test_only_department_teachers_can_proctor(): void
    {
        $d = $this->draft();
        $e = $d->examEntries()->first();
        $outsider = $this->makeTeacher(['full_name' => 'غريب', 'department' => 'قسم آخر']);
        $this->expectException(ScheduleRuleException::class);
        $this->svc->setProctor($d, $e->id, $e->rooms[0]['name'], $outsider['user']->user_id);
    }

    public function test_regenerate_keeps_locked_entries(): void
    {
        $d = $this->draft();
        $e = $d->examEntries()->first();
        $this->svc->setLocked($d, $e->id, true);
        $d = $this->svc->regenerate($d->fresh(), 99);
        $row = $d->examEntries()->where('course_id', $e->course_id)->where('kind', $e->kind)->where('program_id', $e->program_id)->first();

        $this->assertSame($e->exam_date->toDateString(), $row->exam_date->toDateString());
        $this->assertSame([], $d->stats['violations']);
        $this->assertSame(12, $d->examEntries()->count());
    }

    public function test_draft_for_a_non_active_semester_cannot_be_approved(): void
    {
        $d = $this->draft();
        $other = DB::table('semesters')->insertGetId(['name' => 'قادم', 'start_date' => '2027-02-01', 'end_date' => '2027-06-01', 'is_active' => false]);
        $d->update(['semester_id' => $other]);
        $this->expectException(ScheduleRuleException::class);
        $this->svc->approve($d->fresh(), $this->head, true);
    }
}
