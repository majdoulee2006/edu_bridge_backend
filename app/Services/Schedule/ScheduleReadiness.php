<?php

namespace App\Services\Schedule;

use App\Models\Room;
use Illuminate\Support\Facades\DB;

/**
 * "جاهزية البيانات" قبل توليد جدول أسبوعي: ما الذي سيمنع النجاح أو يضعف الجودة، قبل أن يضغط رئيس القسم الزر.
 * كل بند: مستوى (ok / info / warn / error)، عنوان، تفصيل، وإجراء إصلاح اختياري (مفتاح يفسّره العميل: ويب أو تطبيق).
 *
 * error = لن يُجدول جزء من الحصص مهما حاول المولّد. warn = سيعمل لكن النتيجة أضعف أو بلا فحص. info = معلومة.
 */
class ScheduleReadiness
{
    public const ACTION_AUTO_ROOMS = 'auto_rooms';
    public const ACTION_OPEN_ROOMS = 'open_rooms';
    public const ACTION_OPEN_AVAILABILITY = 'open_availability';

    public function __construct(private RoomService $rooms)
    {
    }

    /**
     * @return array{ready: bool, errors: int, warnings: int, items: array<int,array{key:string,level:string,title:string,detail:string,action:?string}>}
     */
    public function check(int $deptId, int $semesterId): array
    {
        $problem = ScheduleProblem::forDepartment($deptId, ['semester_id' => $semesterId]);
        $items = [
            $this->courses($problem),
            $this->teachers($problem),
            $this->capacity($problem),
            ...$this->rooms($deptId, $problem),
            $this->availability($problem),
        ];

        $count = fn (string $lvl) => count(array_filter($items, fn ($i) => $i['level'] === $lvl));

        return ['ready' => $count('error') === 0, 'errors' => $count('error'), 'warnings' => $count('warn'), 'items' => array_values($items)];
    }

    private function item(string $key, string $level, string $title, string $detail = '', ?string $action = null): array
    {
        return ['key' => $key, 'level' => $level, 'title' => $title, 'detail' => $detail, 'action' => $action];
    }

    private function courses(ScheduleProblem $p): array
    {
        $courses = array_sum(array_map(fn ($g) => count($g['courses']), $p->groups));
        if ($courses === 0) {
            return $this->item('courses', 'error', "لا مقررات في «{$p->semesterName}» لقسمك", 'اختر فصلاً آخر، أو اطلب من الإدارة إضافة مقررات لهذا الفصل.');
        }

        return $this->item('courses', 'ok', "{$courses} مقرراً في " . count($p->groups) . ' شعبة', "فصل «{$p->semesterName}»");
    }

    private function teachers(ScheduleProblem $p): array
    {
        $missing = [];
        foreach ($p->groups as $g) {
            foreach ($g['courses'] as $c) {
                if (!$c['teacher']) {
                    $missing[$c['course_id']] = $c['title'];
                }
            }
        }
        if (!$missing) {
            return $this->item('teachers', 'ok', 'كل مقرر له معلم');
        }

        return $this->item('teachers', 'warn', count($missing) . ' مقرر بلا معلم مرتبط: لن تُجدول حصصه', implode('، ', array_slice($missing, 0, 6)) . (count($missing) > 6 ? ' ...' : '') . '. اطلب من الإدارة ربط معلم بكل منها.');
    }

    private function capacity(ScheduleProblem $p): array
    {
        $over = [];
        $tight = [];
        foreach ($p->capacityReport() as $r) {
            if ($r['overflow'] > 0) {
                $over[] = "{$r['label']} تحتاج {$r['needed']} حصة والمتاح {$r['available']}";
            } elseif ($r['available'] > 0 && $r['needed'] / $r['available'] >= 0.9) {
                $tight[] = "{$r['label']} ({$r['needed']}/{$r['available']})";
            }
        }
        if ($over) {
            return $this->item('capacity', 'error', 'شعب لا يتسع لها الأسبوع', implode('، ', $over) . '. زد عدد الفترات اليومية.');
        }
        if ($tight) {
            return $this->item('capacity', 'warn', 'شعب شبه ممتلئة', implode('، ', $tight) . '. قد يصعب إيجاد توزيع بلا فجوات.');
        }

        $max = 0;
        foreach ($p->capacityReport() as $r) {
            $max = max($max, $r['needed']);
        }

        return $this->item('capacity', 'ok', 'كل الشعب تتسع', "أعلى حمل {$max} حصة من " . $p->slotCount() . ' متاحة');
    }

    /** @return array<int,array> */
    private function rooms(int $deptId, ScheduleProblem $p): array
    {
        $groups = $this->rooms->groups($deptId);
        $all = Room::where(fn ($q) => $q->where('department_id', $deptId)->orWhereNull('department_id'))->where('is_active', true)->get();

        if ($all->isEmpty()) {
            return [$this->item('rooms', 'warn', 'لا قاعات مسجلة', 'سيُستعمل اسم قاعة افتراضي لكل شعبة بلا فحص للسعة أو التعارض.', self::ACTION_AUTO_ROOMS)];
        }

        $items = [];
        $homeKeys = [];
        foreach ($all as $r) {
            if ($r->groupKey()) {
                $homeKeys[$r->groupKey()][] = $r;
            }
        }

        $without = array_values(array_filter($groups, fn ($g) => !isset($homeKeys[$g['key']])));
        $shared = $all->filter(fn ($r) => !$r->groupKey())->count();
        if ($without) {
            $labels = implode('، ', array_map(fn ($g) => $g['label'], array_slice($without, 0, 5)));
            $items[] = $this->item(
                'rooms_missing', $shared > 0 ? 'info' : 'warn',
                count($without) . ' شعبة بلا قاعة خاصة', $labels . ($shared > 0 ? '. ستستعمل القاعات المشتركة.' : '. بلا قاعة مشتركة فلن تُجدول حصصها.'),
                self::ACTION_AUTO_ROOMS
            );
        }

        $small = [];
        foreach ($groups as $g) {
            foreach ($homeKeys[$g['key']] ?? [] as $r) {
                if ($g['size'] > 0 && $r->capacity !== null && $g['size'] > $r->capacity) {
                    $small[] = "{$r->name} تتسع {$r->capacity} وطلاب {$g['label']} {$g['size']}";
                }
            }
        }
        if ($small) {
            $items[] = $this->item('rooms_small', 'warn', 'قاعات أصغر من عدد طلاب شعبتها', implode('، ', $small), self::ACTION_OPEN_ROOMS);
        }

        if (!$without && !$small) {
            $items[] = $this->item('rooms', 'ok', 'لكل شعبة قاعتها الخاصة وتتسع لطلابها');
        }

        return $items;
    }

    private function availability(ScheduleProblem $p): array
    {
        $teachers = [];
        foreach ($p->groups as $g) {
            foreach ($g['courses'] as $c) {
                if ($c['teacher']) {
                    $teachers[$c['teacher']] = true;
                }
            }
        }
        $any = $teachers && DB::table('teacher_availability')->whereIn('teacher_user_id', array_keys($teachers))->exists();

        return $any
            ? $this->item('availability', 'ok', 'قيود توفّر المعلمين مسجّلة')
            : $this->item('availability', 'info', 'لا قيود توفّر للمعلمين (اختياري)', 'يمكنك تحديد أوقات لا يتاح فيها معلم، أو يفضّل تجنبها.', self::ACTION_OPEN_AVAILABILITY);
    }
}
