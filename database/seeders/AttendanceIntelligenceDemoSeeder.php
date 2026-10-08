<?php

namespace Database\Seeders;

use App\Models\AttendanceSession;
use App\Services\AttendanceIntelligence\SessionAnalyzer;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * بيانات عرض لتحليل الحضور: 6 أسابيع من الحضور الواقعي (تفاوت بين الأيام والحصص) لـ 12 طالبًا،
 * مع سيناريوهات اشتباه في آخر جلسة تُنتج حالات للمراجعة عند المعلّم، وطالب هبط حضوره فجأة.
 *
 *   معلّم العرض:  samerkhatib-trainer@edu-bridge.com / pass123   (يرى الحالات ويراجعها)
 *   رئيس القسم:   waseemalmady-head@edu-bridge.com  (يرى الخريطة، فالمقرران ضمن برنامج "معلوماتية")
 *
 * التشغيل:  php artisan db:seed --class=AttendanceIntelligenceDemoSeeder
 * الحذف:    ATTENDANCE_DEMO_CLEAN=1 php artisan db:seed --class=AttendanceIntelligenceDemoSeeder
 *
 * يعمل بـ DB::table مباشرة (بلا Observers) فلا يرسل إشعارات غياب ولا يمسّ بيانات حقيقية.
 * قابل لإعادة التشغيل: يحذف بيانات العرض السابقة ويعيد توليدها.
 */
class AttendanceIntelligenceDemoSeeder extends Seeder
{
    private const PASSWORD = 'pass123';
    private const TEACHER_USERNAME = 'samerkhatib-trainer@edu-bridge.com';
    private const COURSES = ['web' => 'برمجة الويب (عرض التحليل)', 'net' => 'شبكات متقدمة (عرض التحليل)'];
    private const CAMPUS_IP = '10.20.1.15';

    private const STUDENTS = [
        'سارة العلي', 'محمد الخطيب', 'رنا الحموي', 'ياسر النجار', 'لمى سليمان', 'خالد العبدالله',
        'هبة الشامي', 'أنس درويش', 'دانة حداد', 'طارق المصري', 'ميس القاسم', 'بلال الأحمد',
    ];

    /** [اليوم PHP (0=الأحد)، المقرر، وقت الحصة، احتمال الحضور الأساسي] */
    private const TIMETABLE = [
        [0, 'web', '08:00', 0.62], [1, 'net', '09:30', 0.88], [2, 'web', '11:00', 0.93],
        [3, 'net', '12:30', 0.85], [4, 'web', '08:00', 0.70], [4, 'net', '11:00', 0.78],
    ];

    public function run(): void
    {
        $this->clean();

        if (env('ATTENDANCE_DEMO_CLEAN')) {
            $this->command?->info('تم حذف بيانات عرض تحليل الحضور.');

            return;
        }

        $programId = DB::table('programs')->where('name', 'معلوماتية')->value('id')
            ?? DB::table('programs')->orderBy('id')->value('id');
        if (!$programId) {
            $this->command?->error('يلزم وجود برنامج واحد على الأقل.');

            return;
        }
        $deptName = DB::table('programs')->join('departments', 'departments.department_id', '=', 'programs.department_id')
            ->where('programs.id', $programId)->value('departments.name');

        mt_srand(42); // نتائج ثابتة بين التشغيلات

        // --- المعلّم والمقرران والجدول ---
        $teacherUserId = DB::table('users')->insertGetId([
            'role_id' => 2, 'full_name' => 'سامر الخطيب', 'username' => self::TEACHER_USERNAME,
            'email' => 'samerkhatib@gmail.com', 'phone' => '0933111222', 'password' => Hash::make(self::PASSWORD),
            'status' => 'active', 'department' => $deptName, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $teacherId = DB::table('teachers')->insertGetId([
            'user_id' => $teacherUserId, 'specialization' => 'برمجة الويب والشبكات',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $course = [];
        foreach (self::COURSES as $key => $title) {
            $course[$key] = DB::table('courses')->insertGetId([
                'title' => $title, 'level' => '1', 'year' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('course_program')->insert(['course_id' => $course[$key], 'program_id' => $programId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('course_teachers')->insert(['course_id' => $course[$key], 'teacher_id' => $teacherId, 'role' => 'teacher', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (self::TIMETABLE as [$dow, $key, $time]) {
            DB::table('schedules')->insert([
                'course_id' => $course[$key], 'teacher_id' => $teacherUserId, 'day' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDays($dow)->format('l'),
                'start_time' => $time . ':00', 'end_time' => Carbon::parse($time)->addMinutes(90)->format('H:i:s'),
                'room' => 'قاعة ' . (1 + $dow), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // --- الطلاب ---
        $students = [];
        foreach (self::STUDENTS as $i => $name) {
            $uid = (string) (2026101 + $i);
            $userId = DB::table('users')->insertGetId([
                'role_id' => 3, 'full_name' => $name, 'username' => $uid, 'university_id' => $uid,
                'email' => "demo{$uid}@example.test", 'password' => Hash::make(self::PASSWORD), 'status' => 'active',
                'department' => $deptName, 'academic_year' => 'السنة الأولى', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $studentId = DB::table('students')->insertGetId([
                'user_id' => $userId, 'student_code' => $uid, 'level' => 'السنة الأولى', 'program_id' => $programId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($course as $cid) {
                DB::table('enrollments')->insert(['student_id' => $studentId, 'course_id' => $cid, 'enrollment_date' => now()->toDateString(), 'status' => 'active']);
            }
            $students[$i] = ['student_id' => $studentId, 'factor' => [0.97, 0.9, 1.0, 0.8, 0.95, 0.85][$i % 6]];
        }

        // --- الجلسات من الأقدم إلى الأحدث ---
        $sessions = []; // [courseKey => [session rows...]]
        $today = now()->startOfDay();
        for ($daysAgo = 41; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            foreach (self::TIMETABLE as [$dow, $key, $time, $base]) {
                if ($date->dayOfWeek !== $dow) {
                    continue;
                }
                $sessions[$key][] = $this->makeSession($date, $time, $base, $key, $course[$key], $teacherId, $students, $daysAgo);
            }
        }

        $this->injectScenarios($sessions['web'] ?? [], $students);

        // --- التحليل (يُنتج الاشتباهات من السيناريوهات) ---
        $analyzer = app(SessionAnalyzer::class);
        $flags = 0;
        foreach ($sessions as $rows) {
            foreach ($rows as $row) {
                $flags += $analyzer->analyze(AttendanceSession::findOrFail($row['session_id']));
            }
        }

        $this->command?->info('تم إنشاء بيانات عرض تحليل الحضور.');
        $this->command?->table(['الدور', 'اسم الدخول', 'كلمة السر', 'ملاحظة'], [
            ['معلّم', self::TEACHER_USERNAME, self::PASSWORD, "يرى {$flags} اشتباهًا للمراجعة"],
            ['رئيس قسم', 'waseemalmady-head@edu-bridge.com', 'pass123', 'يرى الخريطة والترتيب'],
        ]);
    }

    /** يُنشئ درسًا وجلسة وسجل حضور لكل الطلاب. @return array{session_id:int, lesson_id:int, daysAgo:int, ...} */
    private function makeSession(Carbon $date, string $time, float $base, string $key, int $courseId, int $teacherId, array $students, int $daysAgo): array
    {
        $start = Carbon::parse($date->toDateString() . ' ' . $time . ':00');

        $lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'teacher_id' => $teacherId, 'title' => 'محاضرة ' . $date->toDateString(),
            'type' => 'session', 'created_at' => $start, 'updated_at' => $start,
        ]);
        $sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $lessonId, 'qr_token' => 'DEMO-' . $key . '-' . $date->format('Ymd') . '-' . $time,
            'expires_at' => $start->copy()->addSeconds(30), 'session_expires_at' => $start->copy()->addMinutes(10),
            'is_active' => false, 'closed_at' => $start->copy()->addMinutes(10),
            'created_at' => $start, 'updated_at' => $start,
        ]);

        $rows = [];
        foreach ($students as $i => $st) {
            // الطالب رقم 8: حضور كامل ثم غياب تام في آخر أسبوعين (إشارة "تغيّر السلوك")
            $present = $i === 8 ? $daysAgo > 14 : (mt_rand(0, 9999) / 10000) < min(0.99, $base * $st['factor']);
            $scanAt = $start->copy()->addSeconds(mt_rand(15, 280));

            $rows[$i] = [
                'student_id' => $st['student_id'], 'lesson_id' => $lessonId, 'session_id' => $sessionId,
                'status' => $present ? (mt_rand(0, 19) === 0 ? 'late' : 'present') : 'absent',
                'attendance_date' => $date->toDateString(), 'excuse_status' => 'none',
                'device_id' => $present ? 'DEMO-DEV-' . $st['student_id'] : null,
                'latitude' => $present ? 33.5100000 + mt_rand(0, 90000) / 10000000 : null,
                'longitude' => $present ? 36.2900000 + mt_rand(0, 90000) / 10000000 : null,
                'ip_address' => $present ? self::CAMPUS_IP : null,
                'user_agent' => $present ? 'EduBridge/1.0 (Android 13)' : null,
                'qr_age_seconds' => $present ? mt_rand(2, 20) : null,
                'face_score' => $present ? round(74 + mt_rand(0, 2200) / 100, 2) : null,
                'face_status' => $present ? 'verified' : null,
                'created_at' => $present ? $scanAt : $start->copy()->addMinutes(10), 'updated_at' => $scanAt,
            ];
        }

        // الإدراج يحتفظ بمعرّفات السجلات ليعدّلها السيناريو لاحقًا
        foreach ($rows as $i => $row) {
            $rows[$i]['attendance_id'] = DB::table('attendance')->insertGetId($row);
        }

        return ['session_id' => $sessionId, 'lesson_id' => $lessonId, 'rows' => $rows];
    }

    /**
     * سيناريوهات الاشتباه في آخر جلسة لمقرر الويب (وتاريخ سابق لبعضها)، كل واحد يُطلق قاعدة محددة:
     *   0+1 جهاز مشترك | 2+3+4 إحداثيات متطابقة | 5 درجة وجه 100 متكررة | 6 شبكة مختلفة | 7 نمط آخر ثانية
     */
    private function injectScenarios(array $web, array $students): void
    {
        $n = count($web);
        if ($n < 3) {
            return;
        }
        $last = $web[$n - 1];
        $prev = [$web[$n - 2], $web[$n - 3]];

        $force = function (array $session, int $i, array $attrs) {
            $id = $session['rows'][$i]['attendance_id'];
            DB::table('attendance')->where('attendance_id', $id)->update($attrs + ['status' => 'present']);
        };

        // 0+1: الجهاز نفسه
        foreach ([0, 1] as $i) {
            $force($last, $i, ['device_id' => 'DEMO-SHARED-DEVICE-7731']);
        }
        // 2,3,4: إحداثيات متطابقة حتى 7 خانات
        foreach ([2, 3, 4] as $i) {
            $force($last, $i, ['latitude' => 33.5138421, 'longitude' => 36.2765389]);
        }
        // 5: درجة 100 الآن ومرتين قبلها
        foreach ([$last, ...$prev] as $s) {
            $force($s, 5, ['face_score' => 100.00, 'face_status' => 'verified']);
        }
        // 6: IP مختلف
        $force($last, 6, ['ip_address' => '197.63.20.88']);
        // 7: يسجّل دائمًا في آخر ثواني الرمز
        foreach ([$last, ...$prev] as $k => $s) {
            $force($s, 7, ['qr_age_seconds' => [28, 27, 29][$k]]);
        }
    }

    private function clean(): void
    {
        $uids = array_map(fn ($i) => (string) (2026101 + $i), array_keys(self::STUDENTS));
        $userIds = DB::table('users')->whereIn('username', array_merge($uids, [self::TEACHER_USERNAME]))->pluck('user_id');

        // حذف المقررين يحذف دروسهما وجلساتهما وسجلات حضورهما واشتباهاتها وجدولهما وتسجيلاتهما (cascade)
        DB::table('courses')->whereIn('title', array_values(self::COURSES))->delete();

        DB::table('notifications')->whereIn('user_id', $userIds)->delete();
        DB::table('students')->whereIn('user_id', $userIds)->delete();
        DB::table('teachers')->whereIn('user_id', $userIds)->delete();
        DB::table('users')->whereIn('user_id', $userIds)->delete();
    }
}
