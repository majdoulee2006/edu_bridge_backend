<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * The migration that moves the legacy absence_requests rows into leave_requests (the unified table).
 */
class LegacyLeaveMigrationTest extends TestCase
{
    use MakesAcademicData;

    private function migrate(): void
    {
        $migration = require base_path('database/migrations/2026_10_08_100000_copy_absence_requests_into_leave_requests.php');
        $migration->up();
    }

    private function legacy(array $student, array $attrs): int
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

        $this->migrate();

        $rows = DB::table('leave_requests')->where('student_id', $student['user']->user_id)->orderBy('date')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(['full_day', 'pending_hod', 'docs/a.pdf'], [$rows[0]->type, $rows[0]->status, $rows[0]->attachment]);
        $this->assertSame(['hourly', 'approved'], [$rows[1]->type, $rows[1]->status]);
        $this->assertSame('2026-09-18 10:00:00', (string) $rows[0]->created_at);
    }

    public function test_running_it_twice_does_not_duplicate_and_old_rows_are_kept(): void
    {
        $student = $this->makeStudent();
        $this->legacy($student, []);

        $this->migrate();
        $this->migrate();

        $this->assertSame(1, DB::table('leave_requests')->count());
        $this->assertSame(1, DB::table('absence_requests')->count(), 'the legacy table must not be touched');
    }

    public function test_rows_already_mirrored_by_the_old_parent_form_are_skipped(): void
    {
        $student = $this->makeStudent();
        $this->legacy($student, ['reason' => 'family matter', 'status' => 'pending_hod']);
        DB::table('leave_requests')->insert([
            'student_id' => $student['user']->user_id, 'type' => 'full_day', 'date' => '2026-09-20', 'reason' => 'family matter',
            'status' => 'pending_hod', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->migrate();

        $this->assertSame(1, DB::table('leave_requests')->count());
    }

    public function test_unknown_statuses_do_not_break_the_copy(): void
    {
        $student = $this->makeStudent();
        $this->legacy($student, ['reason' => 'a', 'status' => 'recorded']);
        $this->legacy($student, ['reason' => 'b', 'status' => 'weird_value', 'date' => '2026-09-22']);

        $this->migrate();

        $this->assertSame('approved', DB::table('leave_requests')->where('reason', 'a')->value('status'));
        $this->assertSame('pending_affairs', DB::table('leave_requests')->where('reason', 'b')->value('status'));
    }
}
