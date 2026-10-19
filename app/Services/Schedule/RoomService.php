<?php

namespace App\Services\Schedule;

use App\Models\Room;
use Illuminate\Support\Facades\DB;

/**
 * إدارة قاعات القسم: إنشاء قاعة لكل شعبة تلقائياً، واستيراد قائمة قاعات دفعة واحدة (لصق أو ملف).
 * القاعة الخاصة بشعبة = program_id + year. القاعة المشتركة (مخبر، قاعة كبرى) بلا شعبة.
 */
class RoomService
{
    private const TYPE_WORDS = [
        'classroom' => 'classroom', 'قاعة شعبة' => 'classroom', 'قاعة' => 'classroom', 'صف' => 'classroom',
        'lab' => 'lab', 'مخبر' => 'lab', 'معمل' => 'lab',
        'hall' => 'hall', 'قاعة كبرى' => 'hall', 'مدرج' => 'hall', 'كبرى' => 'hall',
    ];

    private const YEAR_WORDS = [
        'أولى' => 1, 'اولى' => 1, 'الأولى' => 1, 'الاولى' => 1, 'ثانية' => 2, 'الثانية' => 2, 'ثالثة' => 3, 'الثالثة' => 3,
        'رابعة' => 4, 'الرابعة' => 4, 'خامسة' => 5, 'الخامسة' => 5,
    ];

    /** كل (برنامج، سنة) في القسم، من مقرراته (أي فصل)، بعدد طلابه النشطين. */
    public function groups(int $deptId): array
    {
        $rows = DB::table('course_program')
            ->join('courses', 'courses.course_id', '=', 'course_program.course_id')
            ->join('programs', 'programs.id', '=', 'course_program.program_id')
            ->where('programs.department_id', $deptId)
            ->select('programs.id as program_id', 'programs.name as program', 'courses.year')
            ->distinct()->orderBy('programs.id')->orderBy('courses.year')->get();

        $out = [];
        foreach ($rows as $r) {
            $year = (int) ($r->year ?: 1);
            $key = $r->program_id . '-' . $year;
            $out[$key] ??= [
                'key' => $key, 'program_id' => (int) $r->program_id, 'program' => $r->program, 'year' => $year,
                'label' => ScheduleProblem::groupLabel($r->program, $year),
                'size' => (int) DB::table('students')->join('users', 'users.user_id', '=', 'students.user_id')
                    ->where('students.program_id', $r->program_id)->where('users.academic_year', ScheduleProblem::academicYear($year))
                    ->where('users.status', 'active')->count(),
            ];
        }

        return array_values($out);
    }

    /**
     * ينشئ لكل شعبة بلا قاعة خاصة قاعة باسمها («قاعة معلوماتية - سنة أولى»)، أو يربط قاعة موجودة بهذا الاسم.
     * السعة المقترحة = عدد طلاب الشعبة + هامش، بحد أدنى 30 (يعدّلها رئيس القسم).
     *
     * @return array{created: string[], linked: string[], skipped: string[]}
     */
    public function createGroupRooms(int $deptId): array
    {
        $report = ['created' => [], 'linked' => [], 'skipped' => []];

        foreach ($this->groups($deptId) as $g) {
            $has = Room::where('department_id', $deptId)->where('program_id', $g['program_id'])->where('year', $g['year'])->where('is_active', true)->exists();
            if ($has) {
                $report['skipped'][] = $g['label'];
                continue;
            }

            $name = 'قاعة ' . $g['label'];
            $existing = Room::where('department_id', $deptId)->where('name', $name)->first();
            if ($existing) {
                $existing->update(['program_id' => $g['program_id'], 'year' => $g['year'], 'is_active' => true]);
                $report['linked'][] = $name;
                continue;
            }

            Room::create([
                'name' => $name, 'department_id' => $deptId, 'program_id' => $g['program_id'], 'year' => $g['year'],
                'capacity' => max(30, (int) (ceil(($g['size'] + 5) / 5) * 5)), 'type' => 'classroom', 'is_active' => true,
            ]);
            $report['created'][] = $name;
        }

        return $report;
    }

    /**
     * استيراد قاعات من نص: سطر لكل قاعة: «الاسم، السعة، النوع، الدورة، السنة» (الثلاثة الأخيرة اختيارية).
     * الفاصل: فاصلة (، أو ,) أو Tab أو ; أو |. القاعة الموجودة بنفس الاسم تُحدَّث.
     *
     * @return array{created: int, updated: int, errors: array<int,string>}
     */
    public function import(int $deptId, string $text): array
    {
        $programs = DB::table('programs')->where('department_id', $deptId)->pluck('id', 'name')->all();
        $out = ['created' => 0, 'updated' => 0, 'errors' => []];

        foreach (preg_split('/\R/u', $text) as $i => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $n = $i + 1;
            $c = array_map('trim', preg_split('/[,،\t;|]/u', $line));

            $name = $c[0] ?? '';
            if ($name === '' || mb_strlen($name) > 60) {
                $out['errors'][$n] = 'اسم القاعة مطلوب (حتى 60 حرفاً)';
                continue;
            }

            $capacity = $c[1] ?? '';
            if (!ctype_digit($capacity) || (int) $capacity < 1 || (int) $capacity > 2000) {
                $out['errors'][$n] = "السعة «{$capacity}» غير صالحة (رقم من 1 إلى 2000)";
                continue;
            }

            $type = 'classroom';
            if (($c[2] ?? '') !== '') {
                $type = self::TYPE_WORDS[$c[2]] ?? null;
                if ($type === null) {
                    $out['errors'][$n] = "النوع «{$c[2]}» غير معروف (قاعة شعبة، مخبر، قاعة كبرى)";
                    continue;
                }
            }

            $programId = null;
            $year = null;
            if (($c[3] ?? '') !== '') {
                $programId = $programs[$c[3]] ?? null;
                if ($programId === null) {
                    $out['errors'][$n] = "الدورة «{$c[3]}» ليست من دورات قسمك";
                    continue;
                }
                $year = $this->parseYear($c[4] ?? '');
                if ($year === null) {
                    $out['errors'][$n] = 'حدّد سنة القاعة مع الدورة (1 أو أولى، 2 أو ثانية...)';
                    continue;
                }
            }

            $room = Room::where('department_id', $deptId)->where('name', $name)->first();
            $data = ['capacity' => (int) $capacity, 'type' => $type, 'program_id' => $programId, 'year' => $year, 'is_active' => true];
            if ($room) {
                $room->update($data);
                $out['updated']++;
            } else {
                Room::create($data + ['name' => $name, 'department_id' => $deptId]);
                $out['created']++;
            }
        }

        return $out;
    }

    private function parseYear(string $v): ?int
    {
        $v = trim(str_replace(['سنة', 'السنة'], '', $v));
        if (ctype_digit($v) && (int) $v >= 1 && (int) $v <= 5) {
            return (int) $v;
        }

        return self::YEAR_WORDS[$v] ?? null;
    }
}
