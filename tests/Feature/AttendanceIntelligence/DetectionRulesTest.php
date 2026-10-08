<?php

namespace Tests\Feature\AttendanceIntelligence;

use App\Models\AttendanceFlag;
use App\Models\AttendanceSession;
use App\Services\AttendanceIntelligence\SessionAnalyzer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * قواعد كشف الأنماط المشبوهة في الحضور، والمحلّل الذي يحفظ نتائجها.
 * القواعد تنتج "اشتباهات للمراجعة" فقط ولا تغيّر أي سجل حضور.
 */
class DetectionRulesTest extends TestCase
{
    use MakesAcademicData;

    private array $teacher;
    private int $courseId;
    private int $lessonId;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        $program = $this->makeProgram($this->makeDepartment());
        $this->teacher  = $this->makeTeacher();
        $this->courseId = $this->makeCourse($program, ['year' => 1]);
        $this->assignTeacher($this->courseId, $this->teacher['teacher_id']);
        $this->lessonId = $this->newLesson();
    }

    // ---------------------------------------------------------------- helpers

    private function newLesson(): int
    {
        return DB::table('lessons')->insertGetId([
            'course_id' => $this->courseId, 'teacher_id' => $this->teacher['teacher_id'],
            'title' => 'L' . $this->nextSeq(), 'type' => 'session', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function newSession(?int $lessonId = null): AttendanceSession
    {
        $id = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $lessonId ?? $this->lessonId, 'qr_token' => 'T' . $this->nextSeq(),
            'expires_at' => now()->addSeconds(30), 'session_expires_at' => now()->addMinutes(10),
            'is_active' => false, 'closed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return AttendanceSession::findOrFail($id);
    }

    /** طالب جديد بسجل حضور في الجلسة المعطاة. @return int attendance_id */
    private function scan(AttendanceSession $s, array $attrs = [], ?int $studentId = null): int
    {
        $studentId ??= $this->makeStudent()['student_id'];

        return DB::table('attendance')->insertGetId(array_merge([
            'student_id' => $studentId, 'lesson_id' => $s->lesson_id, 'session_id' => $s->id,
            'status' => 'present', 'attendance_date' => now()->toDateString(), 'excuse_status' => 'none',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    private function analyze(AttendanceSession $s): int
    {
        return app(SessionAnalyzer::class)->analyze($s);
    }

    private function flagsFor(int $attendanceId): array
    {
        return AttendanceFlag::where('attendance_id', $attendanceId)->pluck('severity', 'rule')->all();
    }

    // ------------------------------------------------- identical_coordinates

    public function test_identical_precise_coordinates_across_students_are_flagged_high_from_three(): void
    {
        $s = $this->newSession();
        $ids = [];
        foreach (range(1, 3) as $_) {
            $ids[] = $this->scan($s, ['latitude' => 33.5138421, 'longitude' => 36.2765389]);
        }
        $other = $this->scan($s, ['latitude' => 33.5138911, 'longitude' => 36.2765102]);

        $this->analyze($s);

        foreach ($ids as $id) {
            $this->assertSame(['identical_coordinates' => 'high'], $this->flagsFor($id));
        }
        $this->assertSame([], $this->flagsFor($other));
    }

    public function test_two_identical_coordinates_are_medium(): void
    {
        $s = $this->newSession();
        $a = $this->scan($s, ['latitude' => 33.5138421, 'longitude' => 36.2765389]);
        $this->scan($s, ['latitude' => 33.5138421, 'longitude' => 36.2765389]);

        $this->analyze($s);

        $this->assertSame(['identical_coordinates' => 'medium'], $this->flagsFor($a));
    }

    public function test_coarse_network_coordinates_are_ignored(): void
    {
        $s = $this->newSession();
        $a = $this->scan($s, ['latitude' => 33.51300, 'longitude' => 36.27600]);
        $this->scan($s, ['latitude' => 33.51300, 'longitude' => 36.27600]);

        $this->analyze($s);

        $this->assertSame([], $this->flagsFor($a));
    }

    // ---------------------------------------------------------- shared_device

    public function test_one_device_for_two_students_is_flagged_high_for_both(): void
    {
        $s = $this->newSession();
        $a = $this->scan($s, ['device_id' => 'DEV-SHARED-123456']);
        $b = $this->scan($s, ['device_id' => 'DEV-SHARED-123456']);
        $c = $this->scan($s, ['device_id' => 'DEV-OWN-000001']);

        $this->analyze($s);

        $this->assertSame(['shared_device' => 'high'], $this->flagsFor($a));
        $this->assertSame(['shared_device' => 'high'], $this->flagsFor($b));
        $this->assertSame([], $this->flagsFor($c));
        // لا يُخزَّن معرّف الجهاز كاملًا
        $this->assertSame('123456', AttendanceFlag::where('attendance_id', $a)->first()->details['device_tail']);
    }

    // -------------------------------------------------------------- ip_outlier

    public function test_ip_outlier_is_low_and_only_when_a_dominant_network_exists(): void
    {
        $s = $this->newSession();
        foreach (range(1, 5) as $_) {
            $this->scan($s, ['ip_address' => '10.0.0.5']);
        }
        $odd = $this->scan($s, ['ip_address' => '197.50.1.9']);
        $v6  = $this->scan($s, ['ip_address' => '2001:db8::1']); // عائلة مختلفة: لا تُقارن

        $this->analyze($s);

        $this->assertSame(['ip_outlier' => 'low'], $this->flagsFor($odd));
        $this->assertSame([], $this->flagsFor($v6));
    }

    public function test_ip_rule_needs_enough_scans_and_a_clear_majority(): void
    {
        $few = $this->newSession();
        $this->scan($few, ['ip_address' => '10.0.0.5']);
        $x = $this->scan($few, ['ip_address' => '1.1.1.1']);
        $this->analyze($few);
        $this->assertSame([], $this->flagsFor($x));

        $split = $this->newSession();
        foreach (['10.0.0.5', '10.0.0.5', '10.0.0.5', '1.1.1.1', '1.1.1.1', '1.1.1.1'] as $ip) {
            $id = $this->scan($split, ['ip_address' => $ip]);
        }
        $this->analyze($split);
        $this->assertSame(0, AttendanceFlag::where('session_id', $split->id)->count());
    }

    // ---------------------------------------------------- replayed_face_score

    public function test_perfect_face_score_is_flagged_medium(): void
    {
        $s = $this->newSession();
        $a = $this->scan($s, ['face_score' => 100.0]);
        $b = $this->scan($s, ['face_score' => 87.35]);

        $this->analyze($s);

        $this->assertSame(['replayed_face_score' => 'medium'], $this->flagsFor($a));
        $this->assertSame([], $this->flagsFor($b));
    }

    public function test_same_exact_score_repeated_across_sessions_is_flagged(): void
    {
        $student = $this->makeStudent()['student_id'];
        foreach (range(1, 2) as $_) {
            $this->scan($this->newSession($this->newLesson()), ['face_score' => 88.12], $student);
        }
        $current = $this->newSession();
        $id = $this->scan($current, ['face_score' => 88.12], $student);

        $this->analyze($current);

        $this->assertSame(['replayed_face_score' => 'medium'], $this->flagsFor($id));
    }

    public function test_perfect_and_repeated_together_are_high(): void
    {
        $student = $this->makeStudent()['student_id'];
        foreach (range(1, 2) as $_) {
            $this->scan($this->newSession($this->newLesson()), ['face_score' => 100.0], $student);
        }
        $current = $this->newSession();
        $id = $this->scan($current, ['face_score' => 100.0], $student);

        $this->analyze($current);

        $this->assertSame(['replayed_face_score' => 'high'], $this->flagsFor($id));
    }

    // ------------------------------------------------------ last_second_pattern

    public function test_last_second_scans_are_flagged_only_as_a_repeated_pattern(): void
    {
        $student = $this->makeStudent()['student_id'];
        $lone = $this->makeStudent()['student_id'];

        foreach (range(1, 2) as $_) {
            $this->scan($this->newSession($this->newLesson()), ['qr_age_seconds' => 27], $student);
        }
        $current = $this->newSession();
        $pattern = $this->scan($current, ['qr_age_seconds' => 28], $student);
        $single  = $this->scan($current, ['qr_age_seconds' => 28], $lone);

        $this->analyze($current);

        $this->assertSame(['last_second_pattern' => 'low'], $this->flagsFor($pattern));
        $this->assertSame([], $this->flagsFor($single));
    }

    // ----------------------------------------------------------------- analyzer

    public function test_analysis_is_idempotent_and_marks_the_session_analyzed(): void
    {
        $s = $this->newSession();
        $this->scan($s, ['device_id' => 'SAME-DEVICE-AAAAAA']);
        $this->scan($s, ['device_id' => 'SAME-DEVICE-AAAAAA']);

        $this->assertNull($s->analyzed_at);
        $this->assertSame(2, $this->analyze($s));
        $this->assertSame(0, $this->analyze($s->fresh()));

        $this->assertSame(2, AttendanceFlag::count());
        $this->assertNotNull($s->fresh()->analyzed_at);
    }

    public function test_reviewed_flags_are_never_reopened_or_changed(): void
    {
        $s = $this->newSession();
        $a = $this->scan($s, ['device_id' => 'SAME-DEVICE-BBBBBB']);
        $this->scan($s, ['device_id' => 'SAME-DEVICE-BBBBBB']);
        $this->analyze($s);

        AttendanceFlag::where('attendance_id', $a)->update(['status' => 'dismissed', 'review_note' => 'زملاء في نفس المقعد']);
        $this->analyze($s->fresh());

        $flag = AttendanceFlag::where('attendance_id', $a)->first();
        $this->assertSame('dismissed', $flag->status);
        $this->assertSame('زملاء في نفس المقعد', $flag->review_note);
    }

    public function test_open_flags_that_no_longer_apply_are_dropped(): void
    {
        $s = $this->newSession();
        $a = $this->scan($s, ['device_id' => 'SAME-DEVICE-CCCCCC']);
        $b = $this->scan($s, ['device_id' => 'SAME-DEVICE-CCCCCC']);
        $this->analyze($s);
        $this->assertSame(2, AttendanceFlag::count());

        DB::table('attendance')->where('attendance_id', $b)->update(['device_id' => 'ANOTHER-DEVICE-1']);
        $this->analyze($s->fresh());

        $this->assertSame(0, AttendanceFlag::count());
    }

    public function test_absent_rows_and_rows_of_other_sessions_are_ignored(): void
    {
        $s = $this->newSession();
        $other = $this->newSession($this->newLesson());
        $this->scan($s, ['device_id' => 'SAME-DEVICE-DDDDDD']);
        $this->scan($s, ['device_id' => 'SAME-DEVICE-DDDDDD', 'status' => 'absent']);
        $this->scan($other, ['device_id' => 'SAME-DEVICE-DDDDDD']);

        $this->analyze($s);

        $this->assertSame(0, AttendanceFlag::count());
    }

    public function test_teacher_is_notified_once_when_new_flags_appear(): void
    {
        $s = $this->newSession();
        $this->scan($s, ['device_id' => 'SAME-DEVICE-EEEEEE']);
        $this->scan($s, ['device_id' => 'SAME-DEVICE-EEEEEE']);

        $this->analyze($s);
        $this->analyze($s->fresh());

        $this->assertSame(1, DB::table('notifications')
            ->where('user_id', $this->teacher['user']->user_id)->where('type', 'attendance_flags')->count());
    }

    public function test_analysis_never_changes_attendance_records(): void
    {
        $s = $this->newSession();
        $a = $this->scan($s, ['device_id' => 'SAME-DEVICE-FFFFFF']);
        $this->scan($s, ['device_id' => 'SAME-DEVICE-FFFFFF']);
        $before = (array) DB::table('attendance')->where('attendance_id', $a)->first();

        $this->analyze($s);

        $this->assertEquals($before, (array) DB::table('attendance')->where('attendance_id', $a)->first());
    }

    public function test_history_rules_only_look_at_earlier_sessions(): void
    {
        $student = $this->makeStudent()['student_id'];
        $old = $this->newSession();
        $oldScan = $this->scan($old, ['face_score' => 100.0], $student);
        foreach (range(1, 2) as $_) { // جلسات لاحقة بنفس الدرجة لا تؤثر على تحليل الأقدم
            $this->scan($this->newSession($this->newLesson()), ['face_score' => 100.0], $student);
        }

        $this->analyze($old);

        $this->assertSame(['replayed_face_score' => 'medium'], $this->flagsFor($oldScan)); // مثالية فقط، بلا تكرار
    }
}
