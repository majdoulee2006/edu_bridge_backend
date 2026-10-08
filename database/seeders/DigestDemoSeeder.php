<?php

namespace Database\Seeders;

use App\Services\Digest\DigestBuilder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * عائلة تجريبية لتجربة الملخص الأسبوعي، بحسابات بأسماء حقيقية على نمط ملف accounts.xlsx:
 *   ولي الأمر: ابو عمر الحداد   (aboomaralhaddad@edu-bridge.com / pass123)
 *   ليان الحداد (2026017): أسبوع جيد.
 *   عمر الحداد  (2026018): غياب متكرر وواجبات فائتة وعلامة منخفضة (يحتاج متابعة).
 *
 * الحسابات تُنشأ أو تُحدَّث (آمن لإعادة التشغيل)، ونشاط الأسبوع الحالي يُعاد توليده في كل تشغيل
 * داخل مادتين خاصتين بهما فقط، فلا تظهر بياناتهما لأي طالب آخر.
 *
 * التشغيل:   php artisan db:seed --class=DigestDemoSeeder
 * الحذف الكامل (الحسابات والنشاط):  DIGEST_DEMO_CLEAN=1 php artisan db:seed --class=DigestDemoSeeder
 */
class DigestDemoSeeder extends Seeder
{
    public const PASSWORD = 'pass123';

    private const PARENT = [
        'name' => 'ابو عمر الحداد', 'username' => 'aboomaralhaddad@edu-bridge.com',
        'email' => 'aboomaralhaddad@gmail.com', 'phone' => '0944123456',
    ];

    private const KIDS = [
        'layan' => ['name' => 'ليان الحداد', 'uid' => '2026017', 'gender' => 'أنثى', 'email' => 'layanalhaddad@gmail.com', 'phone' => '0944123457', 'birth' => '2007-03-14'],
        'omar'  => ['name' => 'عمر الحداد',  'uid' => '2026018', 'gender' => 'ذكر',  'email' => 'omaralhaddad@gmail.com',  'phone' => '0944123458', 'birth' => '2006-11-02'],
    ];

    private const DEPARTMENT = 'نظم المعلومات الحاسوبية';
    private const YEAR       = 'السنة الأولى';

    /** مادتا النشاط الخاصتان بالعرض؛ تُحذفان وتُنشآن من جديد في كل تشغيل. */
    private const COURSES = ['layan' => 'شبكات الحاسوب (حساب تجريبي)', 'omar' => 'قواعد البيانات (حساب تجريبي)'];

    public function run(): void
    {
        $this->cleanActivity();
        $this->deleteUsers(DB::table('users')->where('username', 'like', 'demo.digest.%')->pluck('user_id')); // بقايا النسخة الأولى

        if (env('DIGEST_DEMO_CLEAN')) {
            $this->cleanAccounts();
            $this->command?->info('تم حذف حسابات ونشاط الملخص التجريبي.');

            return;
        }

        $teacherId = DB::table('teachers')->orderBy('teacher_id')->value('teacher_id');
        if (!$teacherId) {
            $this->command?->error('يلزم وجود معلم واحد على الأقل في القاعدة.');

            return;
        }

        // برنامج "معلوماتية" كما في ملف الحسابات (وإلا أول برنامج متاح)
        $programId = DB::table('programs')->where('name', 'معلوماتية')->value('id')
            ?? DB::table('programs')->orderBy('id')->value('id');

        // --- الحسابات ---
        $parentUserId = $this->account(4, self::PARENT['name'], self::PARENT['username'], self::PARENT['email'], self::PARENT['phone']);
        if (!DB::table('parents')->where('user_id', $parentUserId)->exists()) {
            DB::table('parents')->insert(['user_id' => $parentUserId, 'created_at' => now(), 'updated_at' => now()]);
        }

        $kids = [];
        foreach (self::KIDS as $key => $k) {
            $userId = $this->account(3, $k['name'], $k['uid'], $k['email'], $k['phone'], [
                'university_id' => $k['uid'], 'department' => self::DEPARTMENT, 'academic_year' => self::YEAR,
                'gender' => $k['gender'], 'birth_date' => $k['birth'],
            ]);
            $studentId = DB::table('students')->where('user_id', $userId)->value('student_id')
                ?? DB::table('students')->insertGetId([
                    'user_id' => $userId, 'student_code' => $k['uid'], 'level' => self::YEAR,
                    'program_id' => $programId, 'birth_date' => $k['birth'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);

            DB::table('parent_students')->updateOrInsert(
                ['parent_id' => $parentUserId, 'student_id' => $userId],
                ['relationship' => 'father', 'created_at' => now(), 'updated_at' => now()]
            );

            $kids[$key] = ['user_id' => $userId, 'student_id' => $studentId];
        }

        // --- نشاط الأسبوع الحالي ---
        $week = DigestBuilder::weekStartFor(now());          // الأحد
        $day  = fn (int $n) => $week->copy()->addDays($n);   // 0=الأحد ... 4=الخميس

        $course = [];
        foreach (self::COURSES as $key => $title) {
            $course[$key] = DB::table('courses')->insertGetId([
                'title' => $title, 'level' => '1', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('enrollments')->insert([
                'student_id' => $kids[$key]['student_id'], 'course_id' => $course[$key],
                'enrollment_date' => now()->toDateString(), 'status' => 'active',
            ]);
        }

        // ليان: أسبوع جيد
        $L = $kids['layan']['student_id'];
        foreach (range(0, 4) as $d) {
            $this->attend($L, $course['layan'], 'present', $day($d));
        }
        foreach ([[0, 'present'], [1, 'present'], [2, 'present'], [3, 'present'], [4, 'absent']] as [$d, $st]) {
            $this->attend($L, $course['layan'], $st, $day($d)->subDays(7));
        }
        foreach ([[1, 'الواجب الأول: تمديد الشبكات'], [3, 'الواجب الثاني: عناوين IP']] as [$d, $title]) {
            $a = $this->assignment($course['layan'], $teacherId, $title, $day($d)->setTime(23, 59));
            $this->submit($a, $L, $day($d)->setTime(10, 0));
        }
        $this->assignment($course['layan'], $teacherId, 'مشروع تصميم شبكة', now()->addDays(4)->setTime(12, 0));
        $this->grade($course['layan'], $teacherId, $L, 'اختبار قصير', 20, 18, $day(2));

        // عمر: يحتاج متابعة
        $O = $kids['omar']['student_id'];
        foreach ([[0, 'present'], [1, 'absent'], [2, 'absent'], [3, 'absent'], [4, 'late']] as [$d, $st]) {
            $this->attend($O, $course['omar'], $st, $day($d));
        }
        foreach (range(0, 4) as $d) {
            $this->attend($O, $course['omar'], 'present', $day($d)->subDays(7));
        }
        foreach ([[0, 'الواجب الأول: النمذجة'], [1, 'الواجب الثاني: SQL'], [2, 'الواجب الثالث: الفهارس']] as [$d, $title]) {
            $this->assignment($course['omar'], $teacherId, $title, $day($d)->setTime(23, 59));
        }
        $this->grade($course['omar'], $teacherId, $O, 'اختبار قصير', 20, 9, $day(2));
        $this->grade($course['omar'], $teacherId, $O, 'اختبار سابق', 20, 15, $day(2)->subDays(7));

        // توليد ملخصات الأسبوع لولي الأمر فورًا، فتظهر في التطبيق والويب بعد الـ seed مباشرة
        Artisan::call('digest:send', ['--parent' => $parentUserId, '--now' => true]);

        $this->command?->info('تم إنشاء حسابات العائلة ونشاط الأسبوع الحالي وملخصاتها.');
        $this->command?->table(['الحساب', 'اسم الدخول', 'كلمة السر'], [
            ['ولي الأمر - ' . self::PARENT['name'], self::PARENT['username'], self::PASSWORD],
            ['الابنة - ' . self::KIDS['layan']['name'], self::KIDS['layan']['uid'], self::PASSWORD],
            ['الابن - ' . self::KIDS['omar']['name'], self::KIDS['omar']['uid'], self::PASSWORD],
        ]);
    }

    // ------------------------------------------------------------------ حسابات

    private function familyUserIds()
    {
        $usernames = [self::PARENT['username'], self::KIDS['layan']['uid'], self::KIDS['omar']['uid']];

        return DB::table('users')->whereIn('username', $usernames)->pluck('user_id');
    }

    /** ينشئ المستخدم أو يحدّثه حسب اسم الدخول، ويرجع user_id. */
    private function account(int $roleId, string $name, string $username, string $email, string $phone, array $extra = []): int
    {
        $existing = DB::table('users')->where('username', $username)->value('user_id');

        $data = array_merge([
            'role_id' => $roleId, 'full_name' => $name, 'email' => $email, 'phone' => $phone,
            'password' => Hash::make(self::PASSWORD), 'status' => 'active', 'updated_at' => now(),
        ], $extra);

        if ($existing) {
            DB::table('users')->where('user_id', $existing)->update($data);

            return $existing;
        }

        return DB::table('users')->insertGetId($data + ['username' => $username, 'created_at' => now()]);
    }

    // ------------------------------------------------------------------ تنظيف

    /** يحذف نشاط العرض فقط (مادتا العرض، ملخصات وإشعارات العائلة) ويُبقي الحسابات. */
    private function cleanActivity(): void
    {
        $userIds = $this->familyUserIds();

        DB::table('notifications')->whereIn('user_id', $userIds)->delete();
        DB::table('parent_digests')->whereIn('parent_user_id', $userIds)->delete();

        // حذف المادة يحذف واجباتها ودروسها وفعالياتها وتسجيلاتها بالتتالي (cascade)
        DB::table('courses')
            ->whereIn('title', array_values(self::COURSES))
            ->orWhere('title', 'like', 'DEMO %') // بقايا النسخة الأولى
            ->delete();

        $studentIds = DB::table('students')->whereIn('user_id', $userIds)->pluck('student_id');
        DB::table('attendance')->whereIn('student_id', $studentIds)->delete();
        DB::table('assignment_submissions')->whereIn('student_id', $studentIds)->delete();
        DB::table('grade_entries')->whereIn('student_id', $studentIds)->delete();
    }

    private function cleanAccounts(): void
    {
        $this->deleteUsers($this->familyUserIds());
    }

    /** يحذف المستخدمين مع ملفاتهم (طالب/ولي أمر) وروابطهم وتسجيلاتهم. */
    private function deleteUsers($userIds): void
    {
        if (count($userIds) === 0) {
            return;
        }

        $studentIds = DB::table('students')->whereIn('user_id', $userIds)->pluck('student_id');

        DB::table('notifications')->whereIn('user_id', $userIds)->delete();
        DB::table('parent_digests')->whereIn('parent_user_id', $userIds)->delete();
        DB::table('attendance')->whereIn('student_id', $studentIds)->delete();
        DB::table('assignment_submissions')->whereIn('student_id', $studentIds)->delete();
        DB::table('grade_entries')->whereIn('student_id', $studentIds)->delete();
        DB::table('enrollments')->whereIn('student_id', $studentIds)->delete();
        DB::table('parent_students')->whereIn('student_id', $userIds)->orWhereIn('parent_id', $userIds)->delete();
        DB::table('students')->whereIn('user_id', $userIds)->delete();
        DB::table('parents')->whereIn('user_id', $userIds)->delete();
        DB::table('users')->whereIn('user_id', $userIds)->delete();
    }

    // ------------------------------------------------------------------ نشاط

    private function attend(int $studentId, int $courseId, string $status, Carbon $date): void
    {
        $lesson = DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'title' => 'محاضرة ' . $date->toDateString(),
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
