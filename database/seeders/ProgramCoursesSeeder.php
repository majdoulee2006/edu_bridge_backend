<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProgramCoursesSeeder extends Seeder
{
    private array $teacherCache = [];

    public function run(): void
    {
        $plans = [
            'ai' => [
                'program_like' => ['ذكاء', 'Artificial'],
                'plan' => [
                    1 => [
                        1 => [
                            ['فيزياء', ['عوض حلاوة']],
                            ['دارات', ['عوض حلاوة']],
                            ['جبر خطي', ['عوض حلاوة']],
                            ['بايثون', ['خالد اسماعيل']],
                            ['مهارات الحاسوب ICDL', ['رنا باكير', 'خالد اسماعيل']],
                            ['شبكات', ['إبراهيم جبارة']],
                            ['مقدمة في الذكاء الاصطناعي', ['نور']],
                            ['اللغة الإنكليزية', ['بلال']],
                            ['اللغة العربية', ['رنا حلاوة']],
                            ['أخلاقيات وثقافة', ['رنا حلاوة']],
                        ],
                        2 => [
                            ['Microcontroller', ['عوض حلاوة']],
                            ['Digital Logic', ['عوض حلاوة']],
                            ['إحصاء', ['نور']],
                            ['قواعد بيانات', ['نور']],
                            ['C#', ['خالد اسماعيل', 'عوض حلاوة']],
                            ['Operating System', ['رنا باكير', 'عوض حلاوة']],
                            ['اللغة العربية', ['رنا حلاوة']],
                            ['اللغة الإنكليزية', ['بلال']],
                        ],
                    ],
                    2 => [
                        1 => [
                            ['معالجة لغات طبيعية', ['نور']],
                            ['أنظمة خبيرة', ['خالد اسماعيل']],
                            ['رؤية حاسوبية', ['خالد اسماعيل']],
                            ['خوارزميات بحث', ['نور']],
                            ['تطبيقات موبايل', ['احمد نصلة']],
                            ['اللغة الإنكليزية', ['بلال']],
                            ['أمن المعلومات', ['حذيفة محمد']],
                            ['روبوتيك 1', ['عوض حلاوة']],
                        ],
                        2 => [
                            ['روبوتيك 2', ['عوض حلاوة']],
                            ['حساسات', ['عوض حلاوة']],
                            ['مهارات تواصل', ['بلال']],
                            ['شبكات عصبية', ['نور']],
                            ['واقع افتراضي', ['خالد اسماعيل']],
                            ['تعليم الآلة', ['نور']],
                            ['ريادية', ['خالد اسماعيل']],
                            ['مشروع تخرج', []],
                        ],
                    ],
                ],
            ],
            'comm' => [
                'program_like' => ['اتصالات'],
                'plan' => [
                    1 => [
                        1 => [
                            ['أسس كهرباء', ['فيصل بلاوني']],
                            ['الثقافة القومية', ['رنا حلاوة']],
                            ['الرياضيات', ['عوض حلاوة']],
                            ['اللغة الإنكليزية', ['بلال']],
                            ['اللغة العربية', ['رنا حلاوة']],
                            ['شبكات', ['حذيفة محمد']],
                            ['القياسات الكهربائية والإلكترونية', ['فيصل بلاوني']],
                            ['مهارات الحاسوب ICDL', ['هزار']],
                            ['ورشة هندسية تأسيسية', ['فيصل بلاوني']],
                        ],
                        2 => [
                            ['أسس الاتصالات', ['عبدالله يوسف']],
                            ['برمجة 1', ['رنا باكير']],
                            ['الثقافة القومية 2', ['رنا حلاوة']],
                            ['اللغة الإنكليزية 2', ['بلال']],
                            ['اللغة العربية 2', ['رنا حلاوة']],
                            ['دارات إلكترونية', ['فيصل بلاوني']],
                            ['نظم منطقية', ['فيصل بلاوني']],
                            ['نظم تشغيل', ['رنا باكير']],
                            ['شبكات حاسوب 2', ['حذيفة محمد']],
                        ],
                    ],
                    2 => [
                        1 => [
                            ['اتصالات رقمية', ['عبدالله يوسف']],
                            ['الاتصالات المتنقلة', ['عبدالله يوسف']],
                            ['برمجة 2', ['عبدالله يوسف']],
                            ['اللغة الإنكليزية 3', ['بلال']],
                            ['أنظمة الأمان', ['عبدالله يوسف']],
                            ['شبكات حاسوب 3', ['حذيفة محمد']],
                            ['نظم مضمنة', ['فيصل بلاوني']],
                            ['ورشة صيانة', ['عبدالله يوسف']],
                        ],
                        2 => [
                            ['اتصالات متقدمة', ['عبدالله يوسف']],
                            ['اتصالات مايكروية', ['عبدالله يوسف']],
                            ['أساسيات الهاتف والمقاسم الهاتفية', ['حذيفة محمد']],
                                                        ['مهارات ريادية', ['رنا باكير']],
                            ['هوائيات وانتشار الأمواج', ['عبدالله يوسف']],
                            ['شبكات حاسوب 4', ['حذيفة محمد']],
                            ['مهارات التواصل (اللغة الإنكليزية 4)', ['بلال']],
                            ['ورشة اتصالات', ['حذيفة محمد']],
                        ],
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($plans) {
            $semesters = [1 => $this->resolveSemester(1), 2 => $this->resolveSemester(2)];

            foreach ($plans as $key => $cfg) {
                $programId = $this->resolveProgram($cfg['program_like']);
                if (!$programId) {
                    $this->command->warn("⚠️ لم أجد دورة مطابقة لـ [$key] — تم التخطي");
                    continue;
                }

                $created = 0;
                foreach ($cfg['plan'] as $year => $terms) {
                    foreach ($terms as $term => $subjects) {
                        foreach ($subjects as [$title, $teachers]) {
                            $courseId = $this->upsertCourse($title, $year, $semesters[$term], $programId);
                            $created++;
                            foreach ($teachers as $i => $name) {
                                $this->attachTeacher($courseId, $this->resolveTeacher($name), $i === 0 ? 'primary' : 'secondary');
                            }
                        }
                    }
                }
                $this->command->info("✅ [$key] تمت معالجة $created مادة");
            }
        });
    }

    private function resolveSemester(int $term): int
    {
        $needles = $term === 1 ? ['الأول', 'الاول'] : ['الثاني', 'التاني'];
        foreach ($needles as $needle) {
            $id = DB::table('semesters')->where('name', 'like', "%$needle%")->orderByDesc('start_date')->value('semester_id');
            if ($id) {
                return $id;
            }
        }

        return DB::table('semesters')->insertGetId([
            'name' => $term === 1 ? 'الفصل الأول' : 'الفصل الثاني',
            'start_date' => $term === 1 ? '2026-09-01' : '2027-02-01',
            'end_date' => $term === 1 ? '2027-01-15' : '2027-06-30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolveProgram(array $needles): ?int
    {
        foreach ($needles as $needle) {
            $id = DB::table('programs')->where('name', 'like', "%$needle%")->value('id');
            if ($id) {
                return $id;
            }
        }
        return null;
    }

    private function upsertCourse(string $title, int $year, int $semesterId, int $programId): int
    {
        $existing = DB::table('courses')
            ->join('course_program', 'course_program.course_id', '=', 'courses.course_id')
            ->where('course_program.program_id', $programId)
            ->where('courses.title', $title)
            ->where('courses.year', $year)
            ->where('courses.semester_id', $semesterId)
            ->value('courses.course_id');

        if ($existing) {
            return $existing;
        }

        $courseId = DB::table('courses')->insertGetId([
            'title' => $title,
            'level' => 'عام',
            'year' => $year,
            'semester_id' => $semesterId,
            'hours' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('course_program')->insert([
            'course_id' => $courseId,
            'program_id' => $programId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $courseId;
    }

    private function resolveTeacher(string $name): int
    {
        if (isset($this->teacherCache[$name])) {
            return $this->teacherCache[$name];
        }

        $teacherId = DB::table('teachers')
            ->join('users', 'users.user_id', '=', 'teachers.user_id')
            ->where('users.full_name', $name)
            ->value('teachers.teacher_id');

        if (!$teacherId) {
            // نفس تسمية الحسابات اليدوية: <الاسم>-trainer@edu-bridge.com + <الاسم>@gmail.com
            $slug = $this->latinSlug($name);
            $base = $slug;
            for ($i = 2; DB::table('users')->where('username', "$slug-trainer@edu-bridge.com")->orWhere('email', "$slug@gmail.com")->exists(); $i++) {
                $slug = $base . $i;
            }
            $userId = DB::table('users')->insertGetId([
                'full_name' => $name,
                'username' => "$slug-trainer@edu-bridge.com",
                'email' => "$slug@gmail.com",
                'password' => Hash::make('pass123'),
                'role_id' => 2,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $teacherId = DB::table('teachers')->insertGetId([
                'user_id' => $userId,
                'specialization' => 'غير محدد',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->teacherCache[$name] = $teacherId;
    }

    /** تحويل بسيط للاسم العربي إلى أحرف لاتينية صغيرة بدون فراغات (للمراجعة اليدوية عند الحاجة). */
    private function latinSlug(string $name): string
    {
        $map = [
            'ا' => 'a', 'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j',
            'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'dh', 'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'sh',
            'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'q',
            'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'ه' => 'h', 'ة' => 'a', 'و' => 'w', 'ي' => 'y',
            'ى' => 'a', 'ئ' => 'e', 'ؤ' => 'o', 'ء' => '',
        ];
        $latin = strtolower(preg_replace('/[^a-z0-9]/i', '', strtr($name, $map)));

        return $latin !== '' ? $latin : 'teacher' . substr(md5($name), 0, 4);
    }

    private function attachTeacher(int $courseId, int $teacherId, string $role): void
    {
        $exists = DB::table('course_teachers')
            ->where('course_id', $courseId)
            ->where('teacher_id', $teacherId)
            ->exists();

        if (!$exists) {
            DB::table('course_teachers')->insert([
                'course_id' => $courseId,
                'teacher_id' => $teacherId,
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
