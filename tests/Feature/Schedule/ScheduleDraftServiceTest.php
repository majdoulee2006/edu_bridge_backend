<?php

namespace Tests\Feature\Schedule;

use App\Models\ScheduleDraft;
use App\Services\Schedule\ScheduleDraftService;
use App\Services\Schedule\ScheduleRuleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * دورة حياة المسودة: لا شيء يمسّ جدول schedules الحقيقي قبل الاعتماد، والاعتماد معاملة قابلة للتراجع.
 */
class ScheduleDraftServiceTest extends TestCase
{
    use MakesAcademicData;

    private int $dept;
    private array $head;
    private int $program;
    private int $t1;
    private int $t2;
    private ScheduleDraftService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->svc = app(ScheduleDraftService::class);

        $this->dept = $this->makeDepartment('قسم');
        $this->head = $this->makeHead($this->dept);
        $this->program = DB::table('programs')->insertGetId(['name' => 'برنامج', 'department_id' => $this->dept]);
        $this->t1 = $this->makeTeacher()['user']->user_id;
        $this->t2 = $this->makeTeacher()['user']->user_id;

        foreach ([['شبكات', $this->t1], ['برمجة', $this->t2], ['رياضيات', $this->t1]] as [$title, $teacher]) {
            $c = $this->makeCourse($this->program, ['title' => $title, 'hours' => 3, 'year' => 1]);
            $this->assignTeacher($c, DB::table('teachers')->where('user_id', $teacher)->value('teacher_id'));
        }
    }

    private function draft(array $opts = []): ScheduleDraft
    {
        return $this->svc->generate($this->head['user'], $opts + ['seed' => 5]);
    }

    private function entryIds(ScheduleDraft $d): array
    {
        return $d->entries()->orderBy('id')->pluck('id')->all();
    }

    private function realSchedules(): int
    {
        return DB::table('schedules')->count();
    }

    // ---------------------------------------------------------------- توليد

    public function test_generate_stores_a_draft_and_never_touches_the_real_schedule(): void
    {
        $before = $this->realSchedules();

        $d = $this->draft(['name' => 'مسودة الفصل الأول']);

        $this->assertSame('draft', $d->status);
        $this->assertSame('مسودة الفصل الأول', $d->name);
        $this->assertSame($this->dept, (int) $d->department_id);
        $this->assertSame(6, $d->entries()->count());            // 3 مواد × حصتان
        $this->assertSame([], $d->stats['violations']);
        $this->assertSame([], $d->stats['unscheduled']);
        $this->assertSame(6, $d->stats['placed']);
        $this->assertSame($before, $this->realSchedules(), 'التوليد لا يكتب في schedules');
        $this->assertSame('برنامج - سنة أولى', $d->entries()->first()->class_group);
    }

    public function test_only_a_head_with_a_department_can_generate(): void
    {
        $this->expectException(ScheduleRuleException::class);

        $this->svc->generate($this->makeUser('head'), []);
    }

    public function test_old_unapproved_drafts_are_pruned_but_approved_ones_never(): void
    {
        config(['schedule.max_drafts_per_department' => 2]);
        $approved = $this->draft();
        $this->svc->approve($approved, $this->head['user']);

        foreach (range(1, 4) as $i) {
            $this->draft(['seed' => $i]);
        }

        $this->assertSame(2, ScheduleDraft::where('status', 'draft')->count());
        $this->assertNotNull(ScheduleDraft::find($approved->id), 'المعتمدة محفوظة دائمًا');
    }

    // ---------------------------------------------------------------- تعديل

    public function test_a_valid_move_is_saved_and_marked_manual(): void
    {
        $d = $this->draft();
        $e = $d->entries()->first();
        $target = $this->freeSlot($d, $e->id);

        $d = $this->svc->move($d, $e->id, $target['day'], $target['period']);

        $moved = $d->entries()->find($e->id);
        $this->assertSame([$target['day'], $target['period'], 'manual'], [$moved->day, $moved->period, $moved->source]);
        $this->assertSame([], $d->stats['violations']);
    }

    /** فترة فارغة للشعبة لا يشغلها نفس المعلم، وفي يوم لا تُدرَّس فيه المادة. */
    private function freeSlot(ScheduleDraft $d, int $entryId): array
    {
        $e = $d->entries()->find($entryId);
        $others = $d->entries()->where('id', '!=', $entryId)->get();
        $courseDays = $others->where('course_id', $e->course_id)->pluck('day')->all();

        foreach (config('schedule.days') as $day) {
            foreach (range(1, 5) as $p) {
                $busy = $others->contains(fn ($o) => $o->day === $day && $o->period === $p);
                if (!$busy && !in_array($day, $courseDays, true)) {
                    return ['day' => $day, 'period' => $p];
                }
            }
        }
        $this->fail('لا فترة فارغة');
    }

    public function test_a_move_that_breaks_a_hard_rule_is_refused_and_changes_nothing(): void
    {
        $d = $this->draft();
        [$a, $b] = $d->entries()->orderBy('id')->take(2)->get()->all();
        // ضع الحصة الأولى فوق الثانية: تعارض شعبة (وتعارض معلم إن اشتركا)
        $before = $a->only(['day', 'period']);

        try {
            $this->svc->move($d, $a->id, $b->day, $b->period);
            $this->fail('كان يجب رفض النقل');
        } catch (ScheduleRuleException $e) {
            $this->assertNotEmpty($e->messages);
            $this->assertStringContainsString('تعارض', implode(' ', $e->messages));
        }

        $this->assertSame($before, $a->fresh()->only(['day', 'period']));
    }

    public function test_moving_onto_a_teachers_unavailable_time_is_refused(): void
    {
        $d = $this->draft();
        $e = $d->entries()->first();
        $target = $this->freeSlot($d, $e->id);
        DB::table('teacher_availability')->insert(['teacher_user_id' => $e->teacher_user_id, 'day' => $target['day'], 'period' => $target['period'], 'kind' => 'unavailable', 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(ScheduleRuleException::class);
        $this->svc->move($d, $e->id, $target['day'], $target['period']);
    }

    public function test_moving_outside_the_grid_is_refused(): void
    {
        $d = $this->draft();

        $this->expectException(ScheduleRuleException::class);
        $this->svc->move($d, $d->entries()->first()->id, 'Friday', 1);
    }

    public function test_swap_exchanges_two_slots_when_both_remain_valid(): void
    {
        $d = $this->draft();
        $all = $d->entries()->orderBy('id')->get();
        $swapped = null;

        // أول زوج يصح تبديله (بعض الأزواج يخالف قاعدة "حصة واحدة للمادة يوميًا")
        foreach ($all as $i => $a) {
            foreach ($all->slice($i + 1) as $b) {
                try {
                    $d = $this->svc->swap($d, $a->id, $b->id);
                    $swapped = [$a, $b];
                    break 2;
                } catch (ScheduleRuleException) {
                }
            }
        }

        $this->assertNotNull($swapped, 'يجب أن يوجد زوج قابل للتبديل');
        [$a, $b] = $swapped;
        $this->assertSame([$b->day, $b->period], [$a->fresh()->day, $a->fresh()->period]);
        $this->assertSame([$a->day, $a->period], [$b->fresh()->day, $b->fresh()->period]);
        $this->assertSame([], $d->stats['violations']);
    }

    public function test_lock_survives_regeneration_while_the_rest_is_rebuilt(): void
    {
        $d = $this->draft();
        $locked = $d->entries()->first();
        $this->svc->setLocked($d, $locked->id, true);

        $d = $this->svc->regenerate($d, 77);

        $kept = $d->entries()->find($locked->id);
        $this->assertNotNull($kept, 'المثبّتة لا تُحذف عند إعادة التوليد');
        $this->assertSame([$locked->day, $locked->period, $locked->room], [$kept->day, $kept->period, $kept->room]);
        $this->assertTrue($kept->locked);
        $this->assertSame(6, $d->entries()->count());
        $this->assertSame([], $d->stats['violations']);
        $this->assertSame(77, $d->settings['seed']);
    }

    public function test_closed_drafts_cannot_be_edited(): void
    {
        $d = $this->draft();
        $this->svc->discard($d);
        $d->refresh();

        $this->assertSame('discarded', $d->status);
        $this->expectException(ScheduleRuleException::class);
        $this->svc->move($d, $d->entries()->first()->id, 'Sunday', 1);
    }

    // ---------------------------------------------------------------- اعتماد

    private function seedExistingSchedule(): array
    {
        $course = DB::table('courses')->where('title', 'شبكات')->value('course_id');
        $other = DB::table('programs')->insertGetId(['name' => 'قسم آخر', 'department_id' => $this->makeDepartment('آخر')]);
        $otherCourse = $this->makeCourse($other, ['title' => 'مادة غريبة']);

        $mine = DB::table('schedules')->insertGetId(['course_id' => $course, 'teacher_id' => $this->t1, 'day' => 'Thursday', 'start_time' => '08:00:00', 'end_time' => '09:30:00', 'room' => 'قديمة', 'class_group' => 'برنامج - سنة أولى', 'created_at' => now(), 'updated_at' => now()]);
        $legacy = DB::table('schedules')->insertGetId(['course_id' => $course, 'teacher_id' => $this->t1, 'day' => 'Thursday', 'start_time' => '09:30:00', 'end_time' => '11:00:00', 'room' => 'قديمة بلا شعبة', 'class_group' => null, 'created_at' => now(), 'updated_at' => now()]);
        $foreign = DB::table('schedules')->insertGetId(['course_id' => $otherCourse, 'teacher_id' => $this->t2, 'day' => 'Sunday', 'start_time' => '14:00:00', 'end_time' => '15:30:00', 'room' => 'غريبة', 'class_group' => 'قسم آخر - سنة أولى', 'created_at' => now(), 'updated_at' => now()]);

        return compact('mine', 'legacy', 'foreign');
    }

    public function test_approve_replaces_only_this_departments_schedule_in_one_transaction(): void
    {
        $old = $this->seedExistingSchedule();
        $d = $this->draft();

        $d = $this->svc->approve($d, $this->head['user']);

        $this->assertSame('approved', $d->status);
        $this->assertNotNull($d->approved_at);
        $this->assertNull(DB::table('schedules')->where('schedule_id', $old['mine'])->first(), 'حصة القسم القديمة استُبدلت');
        $this->assertNull(DB::table('schedules')->where('schedule_id', $old['legacy'])->first(), 'وكذلك الحصة القديمة بلا شعبة لمادة القسم');
        $this->assertNotNull(DB::table('schedules')->where('schedule_id', $old['foreign'])->first(), 'قسم آخر لا يُمسّ');
        $this->assertSame(6 + 1, $this->realSchedules());

        $row = DB::table('schedules')->where('class_group', 'برنامج - سنة أولى')->first();
        $period = collect(config('schedule.periods'))->first(fn ($p) => $p[0] . ':00' === $row->start_time);
        $this->assertNotNull($period, 'أوقات الحصص من الشبكة المعتمدة');
        $this->assertCount(2, $d->backup, 'نسخة احتياطية لما استُبدل');
    }

    public function test_approve_is_refused_while_a_hard_conflict_remains(): void
    {
        $d = $this->draft();
        [$a, $b] = $d->entries()->orderBy('id')->take(2)->get()->all();
        $a->update(['day' => $b->day, 'period' => $b->period]);   // تعارض مفروض مباشرة في القاعدة

        try {
            $this->svc->approve($d, $this->head['user']);
            $this->fail('كان يجب رفض الاعتماد');
        } catch (ScheduleRuleException $e) {
            $this->assertStringContainsString('تعارضات', $e->getMessage());
        }

        $this->assertSame(0, $this->realSchedules());
        $this->assertSame('draft', $d->fresh()->status);
    }

    public function test_approve_with_unscheduled_needs_an_explicit_acknowledgement(): void
    {
        $c = $this->makeCourse($this->program, ['title' => 'بلا معلم', 'hours' => 3, 'year' => 1]);
        $d = $this->draft();
        $this->assertNotEmpty($d->stats['unscheduled']);

        try {
            $this->svc->approve($d, $this->head['user']);
            $this->fail('كان يجب طلب الإقرار');
        } catch (ScheduleRuleException $e) {
            $this->assertStringContainsString('أقرّ', $e->getMessage());
        }

        $approved = $this->svc->approve($d, $this->head['user'], true);
        $this->assertSame('approved', $approved->status);
        $this->assertSame(6, $this->realSchedules());
    }

    public function test_approve_notifies_the_departments_teachers_and_students_once(): void
    {
        $student = $this->makeStudent([], $this->program);
        $d = $this->draft();

        $this->svc->approve($d, $this->head['user']);

        $recipients = DB::table('notifications')->where('type', 'schedule_approved')->pluck('user_id')->all();
        $this->assertEqualsCanonicalizing([$this->t1, $this->t2, $student['user']->user_id], $recipients);
    }

    public function test_revert_restores_exactly_what_the_approval_replaced(): void
    {
        $old = $this->seedExistingSchedule();
        $d = $this->svc->approve($this->draft(), $this->head['user']);

        $this->svc->revert($d, $this->head['user']);

        $this->assertSame(3, $this->realSchedules());
        $this->assertSame('قديمة', DB::table('schedules')->where('class_group', 'برنامج - سنة أولى')->value('room'));
        $this->assertSame('قديمة بلا شعبة', DB::table('schedules')->whereNull('class_group')->value('room'));
        $this->assertNotNull($d->fresh()->reverted_at);

        $this->expectException(ScheduleRuleException::class);
        $this->svc->revert($d->fresh(), $this->head['user']);   // لا تراجع مرتين
    }

    public function test_only_the_latest_approval_can_be_reverted(): void
    {
        $first = $this->svc->approve($this->draft(), $this->head['user']);
        $this->travel(1)->minutes();
        $second = $this->svc->approve($this->draft(['seed' => 9]), $this->head['user']);

        try {
            $this->svc->revert($first->fresh(), $this->head['user']);
            $this->fail('الأقدم لا يُتراجَع عنه قبل الأحدث');
        } catch (ScheduleRuleException $e) {
            $this->assertStringContainsString('أحدث', $e->getMessage());
        }

        $this->svc->revert($second->fresh(), $this->head['user']);
        $this->svc->revert($first->fresh(), $this->head['user']);
        $this->assertSame(0, $this->realSchedules());
    }
}
