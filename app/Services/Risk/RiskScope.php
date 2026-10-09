<?php

namespace App\Services\Risk;

use App\Models\User;
use App\Support\Access;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * من يحق له رؤية درجات الخطر ولمن. درجة الخطر معلومة حساسة داخلية:
 *   - أدمن: كل الطلاب.
 *   - رئيس قسم: طلاب قسمه فقط.
 *   - مرشد (معلم له advisor_branch/advisor_year): طلاب فرعه وسنته فقط.
 *   - الطالب وولي الأمر وبقية المعلمين والشؤون: لا يرون شيئًا.
 */
final class RiskScope
{
    public const ADMIN = 'admin';
    public const HEAD = 'head';
    public const ADVISOR = 'advisor';

    public static function kind(User $user): ?string
    {
        return match ((int) $user->role_id) {
            1 => self::ADMIN,
            5 => self::HEAD,
            2 => self::advisorOf($user) ? self::ADVISOR : null,
            default => null,
        };
    }

    /** @return object|null صف المعلم إذا كان مرشدًا. */
    private static function advisorOf(User $user): ?object
    {
        $t = DB::table('teachers')->where('user_id', $user->user_id)->first();

        return ($t && !empty($t->advisor_branch)) ? $t : null;
    }

    /**
     * طلاب النطاق نشطو الحساب: students ⨝ users ⨝ programs. يرجع null إذا لا صلاحية.
     */
    public static function students(User $user): ?Builder
    {
        $kind = self::kind($user);
        if ($kind === null) {
            return null;
        }

        $q = DB::table('students')
            ->join('users', 'users.user_id', '=', 'students.user_id')
            ->leftJoin('programs', 'programs.id', '=', 'students.program_id')
            ->where('users.status', 'active');

        if ($kind === self::HEAD) {
            $deptId = Access::headDepartment($user)['id'];

            return $deptId ? $q->where('programs.department_id', $deptId) : $q->whereRaw('1 = 0');
        }

        if ($kind === self::ADVISOR) {
            $t = self::advisorOf($user);

            return $q->where('programs.name', $t->advisor_branch)->where('users.academic_year', $t->advisor_year);
        }

        return $q;
    }

    public static function allows(User $user, int $studentId): bool
    {
        $q = self::students($user);

        return $q !== null && $q->where('students.student_id', $studentId)->exists();
    }

    /**
     * مستلمو تنبيه الخطر لطالب: مرشدوه، وإن لم يوجد مرشد فرئيس قسمه.
     *
     * @return int[] user_id
     */
    public static function recipientsFor(int $studentId): array
    {
        $s = DB::table('students')
            ->join('users', 'users.user_id', '=', 'students.user_id')
            ->leftJoin('programs', 'programs.id', '=', 'students.program_id')
            ->where('students.student_id', $studentId)
            ->first(['programs.name as program', 'programs.department_id', 'users.academic_year']);
        if (!$s) {
            return [];
        }

        $advisors = DB::table('teachers')
            ->join('users', 'users.user_id', '=', 'teachers.user_id')
            ->where('users.status', 'active')
            ->where('teachers.advisor_branch', $s->program)
            ->where('teachers.advisor_year', $s->academic_year)
            ->pluck('teachers.user_id')->map(fn ($i) => (int) $i)->all();
        if ($advisors) {
            return $advisors;
        }

        return $s->department_id
            ? DB::table('heads')->join('users', 'users.user_id', '=', 'heads.user_id')
                ->where('users.status', 'active')->where('heads.department_id', $s->department_id)
                ->pluck('heads.user_id')->map(fn ($i) => (int) $i)->all()
            : [];
    }
}
