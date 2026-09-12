<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateAcademicSchedules extends Command
{
    protected $signature = 'schedules:generate {--fresh : حذف الجداول الدراسية والامتحانية الحالية قبل التوليد}';

    protected $description = 'يولّد جدولاً دراسياً أسبوعياً وجدولاً امتحانياً لكل برنامج/سنة بمواده الحالية، بدون أي تعارض بالمدرّس أو القاعة أو الشعبة';

    private const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

    private const PERIODS = [
        1 => ['08:00:00', '09:30:00'],
        2 => ['09:30:00', '11:00:00'],
        3 => ['11:00:00', '12:30:00'],
        4 => ['12:30:00', '14:00:00'],
        5 => ['14:00:00', '15:30:00'],
    ];

    private const YEAR_LABELS = [1 => 'سنة أولى', 2 => 'سنة ثانية'];

    private const EXAM_TIMES = ['09:00:00', '11:30:00', '14:00:00'];

    public function handle()
    {
        if ($this->option('fresh')) {
            DB::table('schedules')->delete();
            DB::table('exams')->delete();
            $this->info('تم حذف الجداول القديمة.');
        }

        $semester = DB::table('semesters')->where('is_active', 1)->first()
            ?? DB::table('semesters')->orderByDesc('start_date')->first();

        if (!$semester) {
            $this->error('لا يوجد فصل دراسي معرّف.');
            return Command::FAILURE;
        }

        $classGroups = $this->buildClassGroups();
        if (empty($classGroups)) {
            $this->error('لا توجد مواد مرتبطة ببرامج لتوليد جدول لها.');
            return Command::FAILURE;
        }

        $courseTeachers = $this->buildCourseTeachers();
        $teacherForCourseInGroup = $this->assignTeachersToGroups($classGroups, $courseTeachers);

        [$scheduleRows, $examRows] = $this->buildScheduleAndExams($classGroups, $teacherForCourseInGroup, $semester);

        DB::table('schedules')->insert($scheduleRows);
        DB::table('exams')->insert($examRows);

        $this->info('تم توليد ' . count($scheduleRows) . ' حصة دراسية و' . count($examRows) . ' امتحان لـ ' . count($classGroups) . ' شعبة، بدون أي تعارض بالمدرّس أو القاعة أو الشعبة.');

        return Command::SUCCESS;
    }

    /**
     * كل مفتاح "program_id-year" يمثّل شعبة (دورة+سنة) بمواردها ومواده.
     */
    private function buildClassGroups(): array
    {
        $rows = DB::table('course_program')
            ->join('courses', 'course_program.course_id', '=', 'courses.course_id')
            ->join('programs', 'course_program.program_id', '=', 'programs.id')
            ->select('programs.id as program_id', 'programs.name as program_name', 'courses.course_id', 'courses.title', 'courses.hours', 'courses.year')
            ->orderBy('programs.id')->orderBy('courses.year')->orderBy('courses.course_id')
            ->get();

        $classGroups = [];
        foreach ($rows as $r) {
            $key = $r->program_id . '-' . $r->year;
            if (!isset($classGroups[$key])) {
                $yearLabel = self::YEAR_LABELS[$r->year] ?? ('سنة ' . $r->year);
                $classGroup = $r->program_name . ' - ' . $yearLabel;
                $classGroups[$key] = [
                    'class_group' => $classGroup,
                    'room' => 'قاعة ' . $classGroup,
                    'courses' => [],
                ];
            }
            $classGroups[$key]['courses'][] = [
                'course_id' => $r->course_id,
                'title' => $r->title,
                'hours' => $r->hours,
            ];
        }

        ksort($classGroups);
        return $classGroups;
    }

    /**
     * user_id (وليس teacher_id) لكل معلم مرتبط بمادة، لأن schedules.teacher_id مرتبط بـ users.user_id.
     */
    private function buildCourseTeachers(): array
    {
        $teacherRows = DB::table('course_teachers')
            ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->select('course_teachers.course_id', 'teachers.teacher_id', 'teachers.user_id')
            ->orderBy('course_teachers.course_id')->orderBy('teachers.teacher_id')
            ->get();

        $courseTeachers = [];
        foreach ($teacherRows as $t) {
            $courseTeachers[$t->course_id][] = $t->user_id;
        }

        return $courseTeachers;
    }

    /**
     * مادة مشتركة بين أكثر من دورة (مثل "شبكات") قد يكون لها أكثر من معلم؛
     * نوزّع المعلمين على الشعب المحتاجة للمادة بالتناوب بشكل حتمي (نفس النتيجة عند إعادة التوليد).
     */
    private function assignTeachersToGroups(array $classGroups, array $courseTeachers): array
    {
        $courseGroupOrder = [];
        foreach ($classGroups as $groupKey => $cg) {
            foreach ($cg['courses'] as $c) {
                $courseGroupOrder[$c['course_id']][] = $groupKey;
            }
        }

        $assignment = [];
        foreach ($courseGroupOrder as $courseId => $groupKeys) {
            $teacherList = $courseTeachers[$courseId] ?? [];
            foreach ($groupKeys as $idx => $groupKey) {
                $assignment[$courseId][$groupKey] = empty($teacherList) ? null : $teacherList[$idx % count($teacherList)];
            }
        }

        return $assignment;
    }

    private function buildScheduleAndExams(array $classGroups, array $teacherForCourseInGroup, object $semester): array
    {
        $classGroupBusy = [];
        $teacherBusy = [];
        $courseUsedDays = [];

        $scheduleRows = [];
        $examRows = [];

        $examDate = Carbon::parse($semester->end_date)->subDays(21);
        while ($examDate->isFriday() || $examDate->isSaturday()) {
            $examDate->addDay();
        }
        $examSlotIndex = 0;

        foreach ($classGroups as $groupKey => $cg) {
            foreach ($cg['courses'] as $course) {
                $courseId = $course['course_id'];
                $teacherUserId = $teacherForCourseInGroup[$courseId][$groupKey] ?? null;
                $sessions = max(1, (int) round(($course['hours'] ?: 3) / 1.5));
                $usedDaysKey = $courseId . '|' . $groupKey;

                for ($s = 0; $s < $sessions; $s++) {
                    $slot = $this->findSlot($groupKey, $teacherUserId, $classGroupBusy, $teacherBusy, $courseUsedDays[$usedDaysKey] ?? []);
                    if (!$slot) {
                        $this->warn("تعذر إيجاد وقت متاح للمادة \"{$course['title']}\" في \"{$cg['class_group']}\" بدون تعارض — تم تخطيها.");
                        continue;
                    }
                    [$day, $period] = $slot;
                    $classGroupBusy["{$groupKey}|{$day}|{$period}"] = true;
                    if ($teacherUserId) {
                        $teacherBusy["{$teacherUserId}|{$day}|{$period}"] = true;
                    }
                    $courseUsedDays[$usedDaysKey][] = $day;

                    $scheduleRows[] = [
                        'course_id' => $courseId,
                        'teacher_id' => $teacherUserId,
                        'day' => $day,
                        'start_time' => self::PERIODS[$period][0],
                        'end_time' => self::PERIODS[$period][1],
                        'room' => $cg['room'],
                        'class_group' => $cg['class_group'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // امتحان نهائي واحد لكل مادة/شعبة، بقاعة مخصصة للشعبة ووقت متسلسل لا يتعارض مع باقي امتحانات نفس الشعبة
                $examRows[] = [
                    'course_id' => $courseId,
                    'exam_name' => 'الامتحان النهائي - ' . $course['title'],
                    'exam_date' => $examDate->copy()->setTimeFromTimeString(self::EXAM_TIMES[$examSlotIndex % count(self::EXAM_TIMES)])->format('Y-m-d H:i:s'),
                    'room' => $cg['room'],
                    'class_group' => $cg['class_group'],
                    'max_score' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $examSlotIndex++;
                if ($examSlotIndex % count(self::EXAM_TIMES) === 0) {
                    do {
                        $examDate->addDay();
                    } while ($examDate->isFriday() || $examDate->isSaturday());
                }
            }
        }

        return [$scheduleRows, $examRows];
    }

    private function findSlot(string $groupKey, ?int $teacherUserId, array &$classGroupBusy, array &$teacherBusy, array $usedDaysForCourse): ?array
    {
        // أولاً: نفضّل يوماً لم تُجدول فيه هذه المادة بعد (لتوزيع حصصها على أيام مختلفة)
        foreach ([true, false] as $preferNewDay) {
            foreach (self::DAYS as $day) {
                if ($preferNewDay && in_array($day, $usedDaysForCourse, true)) {
                    continue;
                }
                foreach (array_keys(self::PERIODS) as $period) {
                    if (isset($classGroupBusy["{$groupKey}|{$day}|{$period}"])) {
                        continue;
                    }
                    if ($teacherUserId && isset($teacherBusy["{$teacherUserId}|{$day}|{$period}"])) {
                        continue;
                    }
                    return [$day, $period];
                }
            }
        }

        return null;
    }
}
