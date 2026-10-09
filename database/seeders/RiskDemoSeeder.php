<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * بيانات عرض للإنذار المبكر فوق طلاب AttendanceIntelligenceDemoSeeder (ومعهم ليان وعمر):
 * واجبات وعلامات ونشاط دخول بسيناريوهات مختلفة، وتاريخ 8 أسابيع، ومعلّم العرض (سامر) يصير مرشدًا
 * لفرع "معلوماتية" السنة الأولى فيرى كل هؤلاء الطلاب.
 *
 * التشغيل:  php artisan db:seed --class=RiskDemoSeeder
 * الحذف:    RISK_DEMO_CLEAN=1 php artisan db:seed --class=RiskDemoSeeder   (يحذف ما أضافه هنا فقط)
 */
class RiskDemoSeeder extends Seeder
{
    private const TEACHER_USERNAME = 'samerkhatib-trainer@edu-bridge.com';
    private const WEB_COURSE = 'برمجة الويب (عرض التحليل)';
    private const NEW_UIDS = ['2026113', '2026114'];

    /** خطة الواجبات التسعة (الأقدم أولًا): o في موعده، l متأخر، x فائت. المفتاح: الرقم الجامعي. */
    private const ASSIGNMENTS = [
        '2026101' => 'ooooooooo', // سارة: منتظمة
        '2026102' => 'ooooooooo', // محمد: واجباته جيدة لكن علاماته تتراجع
        '2026103' => 'oooollxxx', // رنا: تتراجع في الأسابيع الأخيرة
        '2026104' => 'ooooooooo', // ياسر: منقطع عن المنصة فقط
        '2026105' => 'ooooooolo',
        '2026106' => 'ooooxxxxx', // خالد: توقف عن التسليم
        '2026107' => 'oooooooll',
        '2026108' => 'ooooooooo',
        '2026109' => 'ooooooooo', // دانة: غيابها هو المشكلة
        '2026110' => 'ooolollol',
        '2026111' => 'ooooooooo',
        '2026112' => 'ooooollxo',
        '2026017' => 'ooooooooo', // ليان (عائلة الحداد)
        '2026018' => 'oooxxxxxx', // عمر: يتدهور
    ];

    /** نسب العلامات الست (الأقدم أولًا). */
    private const GRADES = [
        '2026101' => [90, 88, 92, 87, 90, 91], '2026102' => [82, 80, 78, 60, 48, 42], '2026103' => [70, 68, 72, 65, 60, 62],
        '2026104' => [75, 78, 74, 76, 72, 75], '2026105' => [88, 90, 85, 89, 92, 90], '2026106' => [65, 60, 58, 50, 45, 40],
        '2026107' => [72, 70, 75, 73, 71, 74], '2026108' => [80, 82, 79, 81, 78, 80], '2026109' => [85, 88, 90, 86, 84, 83],
        '2026110' => [60, 62, 55, 58, 52, 57], '2026111' => [95, 92, 94, 96, 93, 95], '2026112' => [68, 65, 70, 60, 66, 62],
        '2026017' => [85, 88, 90, 86, 89, 91], '2026018' => [78, 75, 70, 62, 55, 45],
    ];

    /** منذ كم يومًا آخر دخول (الافتراضي 1). */
    private const LAST_LOGIN_DAYS = ['2026104' => 36, '2026106' => 9, '2026112' => 12, '2026103' => 4, '2026102' => 3];

    public function run(): void
    {
        $this->clean();
        if (env('RISK_DEMO_CLEAN')) {
            $this->command?->info('تم حذف بيانات الإنذار المبكر التجريبية.');

            return;
        }

        $teacherUserId = DB::table('users')->where('username', self::TEACHER_USERNAME)->value('user_id');
        $courseId = DB::table('courses')->where('title', self::WEB_COURSE)->value('course_id');
        if (!$teacherUserId || !$courseId) {
            $this->call(AttendanceIntelligenceDemoSeeder::class);
            $teacherUserId = DB::table('users')->where('username', self::TEACHER_USERNAME)->value('user_id');
            $courseId = DB::table('courses')->where('title', self::WEB_COURSE)->value('course_id');
        }
        $teacherId = DB::table('teachers')->where('user_id', $teacherUserId)->value('teacher_id');

        // معلّم العرض مرشد فرع "معلوماتية" السنة الأولى
        DB::table('teachers')->where('teacher_id', $teacherId)->update(['advisor_branch' => 'معلوماتية', 'advisor_year' => 'السنة الأولى']);

        $students = $this->studentsByUid();
        $this->newStudents();

        $this->assignments($courseId, $teacherId, $students);
        $this->grades($courseId, $teacherId, $students);
        $this->logins($students);

        // 8 أسابيع من التاريخ بلا تنبيهات، ثم حساب اليوم مع التنبيه (يصل سامر إشعار مجمَّع)
        Artisan::call('risk:compute', ['--backfill-weeks' => 8, '--no-notify' => true]);
        Artisan::call('risk:compute');

        $this->command?->info('تم إنشاء بيانات الإنذار المبكر. ' . trim(Artisan::output()));
        $this->command?->table(['الدور', 'اسم الدخول', 'كلمة السر', 'ملاحظة'], [
            ['مرشد', self::TEACHER_USERNAME, 'pass123', 'القائمة: المعرّضون للتعثر'],
            ['رئيس قسم', 'waseemalmady-head@edu-bridge.com', 'pass123', 'نفس القائمة لكل القسم'],
        ]);
    }

    // ------------------------------------------------------------------ بناء البيانات

    /** @return array<string,array{student_id:int,user_id:int}> بحسب الرقم الجامعي */
    private function studentsByUid(): array
    {
        return DB::table('students')->join('users', 'users.user_id', '=', 'students.user_id')
            ->whereIn('users.username', array_keys(self::ASSIGNMENTS))
            ->get(['users.username', 'students.student_id', 'students.user_id'])
            ->mapWithKeys(fn ($r) => [$r->username => ['student_id' => (int) $r->student_id, 'user_id' => (int) $r->user_id]])
            ->all();
    }

    /** طالبان مستجدان بلا أي مواد/حضور/علامات: يظهران "بيانات غير كافية" (لا "آمنان"). */
    private function newStudents(): void
    {
        $programId = DB::table('programs')->where('name', 'معلوماتية')->value('id');
        $dept = DB::table('programs')->join('departments', 'departments.department_id', '=', 'programs.department_id')->where('programs.id', $programId)->value('departments.name');

        foreach (self::NEW_UIDS as $i => $uid) {
            $userId = DB::table('users')->insertGetId([
                'role_id' => 3, 'full_name' => ['مستجد أول', 'مستجدة ثانية'][$i], 'username' => $uid, 'university_id' => $uid,
                'email' => "demo{$uid}@example.test", 'password' => Hash::make('pass123'), 'status' => 'active',
                'department' => $dept, 'academic_year' => 'السنة الأولى', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('students')->insert([
                'user_id' => $userId, 'student_code' => $uid, 'level' => 'السنة الأولى', 'program_id' => $programId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function assignments(int $courseId, int $teacherId, array $students): void
    {
        // تسعة واجبات: آخر خميس ثم أسبوعيًا إلى الوراء (الأقدم أولًا)
        $lastThursday = now()->startOfDay()->copy();
        while ($lastThursday->dayOfWeek !== Carbon::THURSDAY || $lastThursday->isFuture()) {
            $lastThursday->subDay();
        }

        $ids = [];
        foreach (range(0, 8) as $k) {
            $due = $lastThursday->copy()->subWeeks(8 - $k)->setTime(23, 59);
            $ids[$k] = [DB::table('assignments')->insertGetId([
                'course_id' => $courseId, 'teacher_id' => $teacherId, 'title' => 'الواجب ' . ($k + 1),
                'due_date' => $due, 'max_points' => 100, 'created_at' => $due->copy()->subDays(6), 'updated_at' => now(),
            ]), $due];
        }

        foreach (self::ASSIGNMENTS as $uid => $plan) {
            $st = $students[$uid] ?? null;
            if (!$st) {
                continue;
            }
            foreach (str_split($plan) as $k => $mark) {
                if ($mark === 'x') {
                    continue;
                }
                [$aid, $due] = $ids[$k];
                $at = $mark === 'o' ? $due->copy()->subDay()->setTime(14, 0) : $due->copy()->addDays(2)->setTime(10, 0);
                DB::table('assignment_submissions')->insert([
                    'assignment_id' => $aid, 'student_id' => $st['student_id'], 'file_path' => 'demo', 'submitted_at' => $at,
                    'created_at' => $at, 'updated_at' => $at,
                ]);
            }
        }
    }

    private function grades(int $courseId, int $teacherId, array $students): void
    {
        $offsets = [80, 62, 45, 30, 16, 4];   // أيام قبل اليوم، الأقدم أولًا

        foreach ($offsets as $i => $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();
            $event = DB::table('grade_events')->insertGetId([
                'teacher_id' => $teacherId, 'course_id' => $courseId, 'type' => $i % 2 ? 'exam' : 'quiz',
                'title' => 'تقييم ' . ($i + 1), 'max_score' => 100, 'date' => $date, 'created_at' => $date, 'updated_at' => $date,
            ]);
            foreach (self::GRADES as $uid => $percents) {
                if (isset($students[$uid])) {
                    DB::table('grade_entries')->insert([
                        'grade_event_id' => $event, 'student_id' => $students[$uid]['student_id'], 'score' => $percents[$i],
                        'created_at' => $date, 'updated_at' => $date,
                    ]);
                }
            }
        }
    }

    private function logins(array $students): void
    {
        foreach ($students as $uid => $st) {
            $lastDays = self::LAST_LOGIN_DAYS[$uid] ?? 1;
            // دخول كل 3 أيام من قبل 70 يومًا حتى آخر دخول
            for ($d = 70; $d >= $lastDays; $d -= 3) {
                DB::table('user_activities')->insert([
                    'user_id' => $st['user_id'], 'role_name' => 'طالب', 'action' => 'تسجيل دخول (تطبيق)',
                    'created_at' => now()->subDays($d)->setTime(9, 0), 'updated_at' => now(),
                ]);
            }
            DB::table('users')->where('user_id', $st['user_id'])->update(['last_login' => now()->subDays($lastDays)->setTime(9, 0)]);
        }
    }

    // ------------------------------------------------------------------ التنظيف

    private function clean(): void
    {
        $teacherUserId = DB::table('users')->where('username', self::TEACHER_USERNAME)->value('user_id');
        $courseId = DB::table('courses')->where('title', self::WEB_COURSE)->value('course_id');
        $uids = array_merge(array_keys(self::ASSIGNMENTS), self::NEW_UIDS);
        $userIds = DB::table('users')->whereIn('username', $uids)->pluck('user_id');
        $studentIds = DB::table('students')->whereIn('user_id', $userIds)->pluck('student_id');

        if ($courseId) {
            DB::table('assignments')->where('course_id', $courseId)->delete();
            DB::table('grade_events')->where('course_id', $courseId)->delete();
        }
        DB::table('student_risk_scores')->whereIn('student_id', $studentIds)->delete();
        DB::table('student_risk_history')->whereIn('student_id', $studentIds)->delete();
        DB::table('risk_interventions')->whereIn('student_id', $studentIds)->delete();
        DB::table('user_activities')->whereIn('user_id', $userIds)->where('action', 'like', 'تسجيل دخول%')->delete();
        if ($teacherUserId) {
            DB::table('notifications')->where('user_id', $teacherUserId)->where('type', 'risk_alert')->delete();
        }

        // الطالبان المستجدان من هذا الـ seeder فقط
        $newUserIds = DB::table('users')->whereIn('username', self::NEW_UIDS)->pluck('user_id');
        DB::table('students')->whereIn('user_id', $newUserIds)->delete();
        DB::table('users')->whereIn('user_id', $newUserIds)->delete();
    }
}
