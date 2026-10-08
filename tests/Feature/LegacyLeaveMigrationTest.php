<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * The two migrations that retire the legacy absence_requests table:
 *  1) 2026_10_08_100000 copies its rows into leave_requests (idempotent, non-destructive)
 *  2) 2026_10_08_110000 drops it, but refuses to if some row has not been copied
 * A fresh database no longer has the table, so each test recreates it (and removes it afterwards).
 */
class LegacyLeaveMigrationTest extends TestCase
{
    use MakesAcademicData;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('absence_requests');
        Schema::create('absence_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('student_id');
            $table->date('date');
            $table->text('reason');
            $table->string('document')->nullable();
            $table->string('status', 50)->default('pending_parent');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('absence_requests');
        parent::tearDown();
    }

    private function copy(): void
    {
        (require base_path('database/migrations/2026_10_08_100000_copy_absence_requests_into_leave_requests.php'))->up();
    }

    private function drop(): void
    {
        (require base_path('database/migrations/2026_10_08_110000_drop_absence_requests_table.php'))->up();
    }

    private function legacy(array $student, array $attrs = []): int
    {
        return DB::table('absence_requests')->insertGetId(array_merge([
            'student_id' => $student['student_id'], 'date' => '2026-09-20', 'reason' => '[إذن يومي] سبب',
            'status' => 'approved', 'created_at' => '2026-09-18 10:00:00', 'updated_at' => '2026-09-19 10:00:00',
        ], $attrs));
    }

    public function test_legacy_rows_are_copied_with_the_right_mapping_and_dates(): void
    {
        $student = $this->makeStudent();
        $this->legacy($student, ['reason' => '[إذن يومي] زيارة طبية', 'status' => 'pending_hod', 'document' => 'docs/a.pdf']);
        $this->legacy($student, ['reason' => '[إذن ساعي - الفترة: 10:00 - 12:00] موعد', 'date' => '2026-09-21']);

        $this->copy();

        $rows = DB::table('leave_requests')->where('student_id', $student['user']->user_id)->orderBy('date')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(['full_day', 'pending_hod', 'docs/a.pdf'], [$rows[0]->type, $rows[0]->status, $rows[0]->attachment]);
        $this->assertSame(['hourly', 'approved'], [$rows[1]->type, $rows[1]->status]);
        $this->assertSame('2026-09-18 10:00:00', (string) $rows[0]->created_at);
    }

    public function test_running_the_copy_twice_does_not_duplicate_and_keeps_the_old_rows(): void
    {
        $student = $this->makeStudent();
        $this->legacy($student);

        $this->copy();
        $this->copy();

        $this->assertSame(1, DB::table('leave_requests')->count());
        $this->assertSame(1, DB::table('absence_requests')->count());
    }

    public function test_rows_already_mirrored_by_the_old_parent_form_are_skipped(): void
    {
        $student = $this->makeStudent();
        $this->legacy($student, ['reason' => 'family matter', 'status' => 'pending_hod']);
        DB::table('leave_requests')->insert([
            'student_id' => $student['user']->user_id, 'type' => 'full_day', 'date' => '2026-09-20', 'reason' => 'family matter',
            'status' => 'pending_hod', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->copy();

        $this->assertSame(1, DB::table('leave_requests')->count());
    }

    public function test_unknown_statuses_do_not_break_the_copy(): void
    {
        $student = $this->makeStudent();
        $this->legacy($student, ['reason' => 'a', 'status' => 'recorded']);
        $this->legacy($student, ['reason' => 'b', 'status' => 'weird_value', 'date' => '2026-09-22']);

        $this->copy();

        $this->assertSame('approved', DB::table('leave_requests')->where('reason', 'a')->value('status'));
        $this->assertSame('pending_affairs', DB::table('leave_requests')->where('reason', 'b')->value('status'));
    }

    public function test_the_table_is_dropped_once_everything_was_copied(): void
    {
        $this->legacy($this->makeStudent());
        $this->copy();

        $this->drop();

        $this->assertFalse(Schema::hasTable('absence_requests'));
        $this->assertSame(1, DB::table('leave_requests')->count(), 'the copied row must survive the drop');
    }

    public function test_the_drop_refuses_to_lose_a_row_that_was_never_copied(): void
    {
        $student = $this->makeStudent();
        $this->legacy($student, ['reason' => 'never copied']);

        try {
            $this->drop();
            $this->fail('the drop must stop when a row has not been copied');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('not copied', $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable('absence_requests'));
        $this->assertSame(1, DB::table('absence_requests')->count());
    }

    public function test_the_drop_is_harmless_when_the_table_is_already_gone(): void
    {
        Schema::drop('absence_requests');

        $this->drop();

        $this->assertFalse(Schema::hasTable('absence_requests'));
    }
}
