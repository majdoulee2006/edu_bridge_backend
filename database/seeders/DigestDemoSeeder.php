<?php

namespace Database\Seeders;

use App\Services\Digest\DigestBuilder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * بيانات تجريبية لتجربة الملخص الأسبوعي: ولي أمر واحد وطفلان في الأسبوع الحالي.
 *   - ليان: أسبوع جيد.
 *   - عمر: غياب متكرر وواجبات فائتة وعلامة منخفضة (نبرة "يحتاج متابعة").
 *
 * التشغيل:   php artisan db:seed --class=DigestDemoSeeder
 * الحذف:     DIGEST_DEMO_CLEAN=1 php artisan db:seed --class=DigestDemoSeeder
 *
 * كل ما يُنشأ يحمل البادئة demo.digest أو DEMO في الاسم ليسهل حذفه، ولا يلمس أي بيانات حقيقية.
 */
class DigestDemoSeeder extends Seeder
{
    public const PASSWORD = 'Demo@12345';
    private const USER_PREFIX = 'demo.digest.';

    public function run(): void
    {
        $this->clean();

        if (env('DIGEST_DEMO_CLEAN')) {
            $this->command?->info('تم حذف بيانات الملخص التجريبية.');

            return;
        }

        $teacherId = DB::table('teachers')->orderBy('teacher_id')->value('teacher_id');
        $programId = DB::table('programs')->orderBy('id')->value('id');
        if (!$teacherId) {
            $this->command?->error('يلزم وجود معلم واحد على الأقل في القاعدة.');

            return;
        }

        // مادة تجريبية مستقلة: الواجبات مرتبطة بالمادة، فلا نستعمل مادة حقيقية كي لا تظهر واجبات DEMO لطلاب حقيقيين
        // مادة لكل طفل حتى لا تظهر واجبات أحدهما في ملخص الآخر
        $newCourse = fn (string $title) => DB::table('courses')->insertGetId([
            'title' => $title, 'level' => '1', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $courseL = $newCourse('DEMO شبكات الحاسوب');
        $courseO = $newCourse('DEMO قواعد البيانات');

        $week = DigestBuilder::weekStartFor(now());         // الأحد
        $day  = fn (int $n) => $week->copy()->addDays($n);  // 0=الأحد ... 4=الخميس

        // --- الحسابات ---
        $parent = $this->user(4, 'parent', 'ولي أمر تجريبي');
        DB::table('parents')->insert(['user_id' => $parent, 'created_at' => now(), 'updated_at' => now()]);

        $layan = $this->student('layan', 'ليان التجريبية', $programId);
        $omar  = $this->student('omar', 'عمر التجريبي', $programId);
        foreach ([[$layan, $courseL], [$omar, $courseO]] as [$s, $cid]) {
            DB::table('parent_students')->insert([
                'parent_id' => $parent, 'student_id' => $s['user_id'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('enrollments')->insert([
                'student_id' => $s['student_id'], 'course_id' => $cid,
                'enrollment_date' => now()->toDateString(), 'status' => 'active',
            ]);
        }

        // --- ليان: أسبوع جيد ---
        foreach (range(0, 4) as $d) {
            $this->attend($layan['student_id'], $courseL, 'present', $day($d));
        }
        foreach ([[0, 'present'], [1, 'present'], [2, 'present'], [3, 'present'], [4, 'absent']] as [$d, $st]) {
            $this->attend($layan['student_id'], $courseL, $st, $day($d)->subDays(7));
        }
        foreach ([1, 3] as $d) {
            $a = $this->assignment($courseL, $teacherId, "DEMO واجب ليان {$d}", $day($d)->setTime(23, 59));
            $this->submit($a, $layan['student_id'], $day($d)->setTime(10, 0));
        }
        $this->assignment($courseL, $teacherId, 'DEMO مشروع قادم', now()->addDays(4)->setTime(12, 0));
        $this->grade($courseL, $teacherId, $layan['student_id'], 'DEMO اختبار قصير', 20, 18, $day(2));

        // --- عمر: يحتاج متابعة ---
        foreach ([[0, 'present'], [1, 'absent'], [2, 'absent'], [3, 'absent'], [4, 'late']] as [$d, $st]) {
            $this->attend($omar['student_id'], $courseO, $st, $day($d));
        }
        foreach (range(0, 4) as $d) {
            $this->attend($omar['student_id'], $courseO, 'present', $day($d)->subDays(7));
        }
        foreach ([0, 1, 2] as $d) {
            $this->assignment($courseO, $teacherId, "DEMO واجب عمر {$d}", $day($d)->setTime(23, 59));
        }
        $this->grade($courseO, $teacherId, $omar['student_id'], 'DEMO اختبار قصير', 20, 9, $day(2));
        $this->grade($courseO, $teacherId, $omar['student_id'], 'DEMO اختبار سابق', 20, 15, $day(2)->subDays(7));

        $this->command?->info('تم إنشاء البيانات التجريبية.');
        $this->command?->table(['الحساب', 'اسم الدخول', 'كلمة المرور'], [
            ['ولي الأمر', self::USER_PREFIX . 'parent', self::PASSWORD],
        ]);
    }

    private function clean(): void
    {
        $userIds = DB::table('users')->where('username', 'like', self::USER_PREFIX . '%')->pluck('user_id');

        DB::table('notifications')->whereIn('user_id', $userIds)->delete();
        DB::table('parent_digests')->whereIn('parent_user_id', $userIds)->delete();

        $studentIds = DB::table('students')->whereIn('user_id', $userIds)->pluck('student_id');
        DB::table('attendance')->whereIn('student_id', $studentIds)->delete();
        DB::table('assignment_submissions')->whereIn('student_id', $studentIds)->delete();
        DB::table('grade_entries')->whereIn('student_id', $studentIds)->delete();
        DB::table('enrollments')->whereIn('student_id', $studentIds)->delete();
        DB::table('parent_students')->whereIn('student_id', $userIds)->orWhereIn('parent_id', $userIds)->delete();
        DB::table('students')->whereIn('user_id', $userIds)->delete();
        DB::table('parents')->whereIn('user_id', $userIds)->delete();
        DB::table('users')->whereIn('user_id', $userIds)->delete();

        // حذف المادة التجريبية يحذف واجباتها ودروسها وفعالياتها بالتتالي (cascade)
        DB::table('courses')->where('title', 'like', 'DEMO %')->delete();
        DB::table('assignments')->where('title', 'like', 'DEMO %')->delete();
        DB::table('grade_events')->where('title', 'like', 'DEMO %')->delete();
        DB::table('lessons')->where('title', 'like', 'DEMO %')->delete();
    }

    private function user(int $roleId, string $key, string $name): int
    {
        return DB::table('users')->insertGetId([
            'role_id'    => $roleId,
            'full_name'  => $name,
            'username'   => self::USER_PREFIX . $key,
            'email'      => self::USER_PREFIX . $key . '@example.test',
            'password'   => Hash::make(self::PASSWORD),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array{user_id:int, student_id:int} */
    private function student(string $key, string $name, ?int $programId): array
    {
        $userId = $this->user(3, "student.{$key}", $name);
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $userId, 'student_code' => 'DEMO-' . strtoupper($key),
            'program_id' => $programId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['user_id' => $userId, 'student_id' => $studentId];
    }

    private function attend(int $studentId, int $courseId, string $status, Carbon $date): void
    {
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'title' => 'DEMO محاضرة ' . $date->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('attendance')->insert([
            'student_id' => $studentId, 'lesson_id' => $lesson, 'status' => $status,
            'attendance_date' => $date->toDateString(), 'excuse_status' => 'none',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function assignment(int $courseId, int $teacherId, string $title, Carbon $due): int
    {
        return DB::table('assignments')->insertGetId([
            'course_id' => $courseId, 'teacher_id' => $teacherId, 'title' => $title,
            'due_date' => $due, 'max_points' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function submit(int $assignmentId, int $studentId, Carbon $at): void
    {
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $assignmentId, 'student_id' => $studentId,
            'file_path' => 'demo', 'submitted_at' => $at, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function grade(int $courseId, int $teacherId, int $studentId, string $title, int $max, int $score, Carbon $at): void
    {
        $event = DB::table('grade_events')->insertGetId([
            'teacher_id' => $teacherId, 'course_id' => $courseId, 'type' => 'quiz',
            'title' => $title, 'max_score' => $max, 'date' => $at->toDateString(),
            'created_at' => $at, 'updated_at' => $at,
        ]);
        DB::table('grade_entries')->insert([
            'grade_event_id' => $event, 'student_id' => $studentId, 'score' => $score,
            'created_at' => $at, 'updated_at' => $at,
        ]);
    }
}
