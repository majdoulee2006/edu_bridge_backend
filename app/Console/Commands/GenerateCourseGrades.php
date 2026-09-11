<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateCourseGrades extends Command
{
    protected $signature = 'grades:generate {--fresh : حذف كل الدرجات الحالية قبل التوليد}';

    protected $description = 'يولّد مذاكرة وشفهياً وامتحاناً نهائياً لكل شعبة (مادة+دورة) ويضع علامات لكل طالب مسجَّل، مع رسوب متعمّد لبعض الطلاب بالامتحان';

    private const QUIZ_MAX = 25;
    private const ORAL_MAX = 25;
    private const EXAM_MAX = 50;

    public function handle()
    {
        if ($this->option('fresh')) {
            DB::table('grade_entries')->delete();
            DB::table('grade_events')->delete();
            $this->info('تم حذف الدرجات القديمة.');
        }

        $groups = $this->buildGroups();
        if (empty($groups)) {
            $this->error('لا توجد تسجيلات (enrollments) نشطة لتوليد درجات لها.');
            return Command::FAILURE;
        }

        $teacherForGroup = $this->assignTeachers($groups);

        $quizEvents = 0;
        $oralEvents = 0;
        $examEvents = 0;
        $entries = 0;
        $failCount = 0;
        $index = 0;

        foreach ($groups as $key => $group) {
            $teacherId = $teacherForGroup[$key] ?? null;
            if (!$teacherId) {
                $this->warn("تعذر إيجاد معلم لمادة course_id={$group['course_id']} — تم تخطيها.");
                continue;
            }

            $programName = DB::table('programs')->where('id', $group['program_id'])->value('name');
            $yearLabel = $group['year'] == 1 ? 'سنة أولى' : 'سنة ثانية';
            $classGroup = $programName . ' - ' . $yearLabel;
            $courseTitle = DB::table('courses')->where('course_id', $group['course_id'])->value('title');

            // نستخدم نفس تاريخ الامتحان النهائي المولّد مسبقاً بجدول exams لهذه الشعبة/المادة لضمان التطابق
            $examRow = DB::table('exams')->where('course_id', $group['course_id'])->where('class_group', $classGroup)->first();
            $examDate = $examRow ? Carbon::parse($examRow->exam_date) : now()->addWeeks(3);
            $quizDate = $examDate->copy()->subWeeks(3);

            $quizEventId = DB::table('grade_events')->insertGetId([
                'teacher_id' => $teacherId,
                'course_id' => $group['course_id'],
                'program_id' => $group['program_id'],
                'year_level' => $group['year'],
                'type' => 'quiz',
                'title' => 'مذاكرة الفصل - ' . $courseTitle,
                'max_score' => self::QUIZ_MAX,
                'date' => $quizDate->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $quizEvents++;

            $oralEventId = DB::table('grade_events')->insertGetId([
                'teacher_id' => $teacherId,
                'course_id' => $group['course_id'],
                'program_id' => $group['program_id'],
                'year_level' => $group['year'],
                'type' => 'oral',
                'title' => 'الشفهي (وظائف، تسميع، تقييم، سبر) - ' . $courseTitle,
                'max_score' => self::ORAL_MAX,
                'date' => $quizDate->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $oralEvents++;

            $examEventId = DB::table('grade_events')->insertGetId([
                'teacher_id' => $teacherId,
                'course_id' => $group['course_id'],
                'program_id' => $group['program_id'],
                'year_level' => $group['year'],
                'type' => 'exam',
                'title' => 'الامتحان النهائي - ' . $courseTitle,
                'max_score' => self::EXAM_MAX,
                'date' => $examDate->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $examEvents++;

            foreach ($group['students'] as $studentId) {
                // كل رابع (طالب، مادة) نجعله يرسب بالامتحان تحديداً؛ النطاقات محسوبة بحيث يضمن
                // الرسوب مجموعاً < 50 دوماً، والنجاح مجموعاً >= 50 دوماً بغض النظر عن العشوائية
                $shouldFailExam = ($index % 4 === 3);
                $index++;

                if ($shouldFailExam) {
                    $quizScore = $this->pseudoScore($studentId, $group['course_id'], 10, 18);
                    $oralScore = $this->pseudoScore($studentId, $group['course_id'] + 500, 8, 15);
                    $examScore = $this->pseudoScore($studentId, $group['course_id'] + 1000, 5, 15);
                    $failCount++;
                } else {
                    $quizScore = $this->pseudoScore($studentId, $group['course_id'], 18, self::QUIZ_MAX);
                    $oralScore = $this->pseudoScore($studentId, $group['course_id'] + 500, 15, self::ORAL_MAX);
                    $examScore = $this->pseudoScore($studentId, $group['course_id'] + 1000, 20, self::EXAM_MAX);
                }

                DB::table('grade_entries')->insert([
                    'grade_event_id' => $quizEventId,
                    'student_id' => $studentId,
                    'score' => $quizScore,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('grade_entries')->insert([
                    'grade_event_id' => $oralEventId,
                    'student_id' => $studentId,
                    'score' => $oralScore,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('grade_entries')->insert([
                    'grade_event_id' => $examEventId,
                    'student_id' => $studentId,
                    'score' => $examScore,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $entries += 3;
            }
        }

        $this->info("تم توليد {$quizEvents} مذاكرة و{$oralEvents} شفهي و{$examEvents} امتحان و{$entries} علامة لـ " . count($groups) . " شعبة، منها {$failCount} حالة رسوب متعمّدة بالامتحان.");

        return Command::SUCCESS;
    }

    /**
     * كل شعبة = (مادة + دورة الطالب الفعلية) مع قائمة الطلاب المسجّلين بها فعلياً.
     */
    private function buildGroups(): array
    {
        $rows = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.status', 'active')
            ->select('enrollments.student_id', 'enrollments.course_id', 'students.program_id', 'courses.year')
            ->orderBy('students.program_id')->orderBy('enrollments.course_id')->orderBy('enrollments.student_id')
            ->get();

        $groups = [];
        foreach ($rows as $r) {
            $key = $r->course_id . '-' . $r->program_id;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'course_id' => $r->course_id,
                    'program_id' => $r->program_id,
                    'year' => $r->year,
                    'students' => [],
                ];
            }
            $groups[$key]['students'][] = $r->student_id;
        }

        return $groups;
    }

    /**
     * نفس منطق توزيع المعلمين على الشُعب المستخدم بأمر توليد الجدول الدراسي (schedules:generate)
     * حتى يكون معلم كل شعبة متّسقاً بين الجدول والدرجات.
     */
    private function assignTeachers(array $groups): array
    {
        $teacherRows = DB::table('course_teachers')
            ->orderBy('course_id')->orderBy('teacher_id')
            ->get(['course_id', 'teacher_id']);

        $courseTeachers = [];
        foreach ($teacherRows as $t) {
            $courseTeachers[$t->course_id][] = $t->teacher_id;
        }

        $courseGroupOrder = [];
        foreach ($groups as $key => $g) {
            $courseGroupOrder[$g['course_id']][] = $key;
        }

        $assignment = [];
        foreach ($courseGroupOrder as $courseId => $groupKeys) {
            $teacherList = $courseTeachers[$courseId] ?? [];
            foreach ($groupKeys as $idx => $groupKey) {
                $assignment[$groupKey] = empty($teacherList) ? null : $teacherList[$idx % count($teacherList)];
            }
        }

        return $assignment;
    }

    /**
     * علامة عشوائية حتمية (نفس المدخلات = نفس النتيجة دوماً) بمجال [$min, $max].
     */
    private function pseudoScore(int $studentId, int $salt, int $min, int $max): int
    {
        $seed = crc32($studentId . '-' . $salt);
        mt_srand($seed);
        return mt_rand($min, $max);
    }
}
