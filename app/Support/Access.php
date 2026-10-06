<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * نقطة واحدة لفحوص "هل يحق لهذا المستخدم الوصول لهذا المورد؟".
 *
 * المبدأ: الوصول مرفوض ما لم تثبت الملكية/الدور. أي Controller يستقبل معرّفاً
 * من الطلب يجب أن يمرّ عبر إحدى هذه الدوال قبل القراءة أو التعديل.
 *
 * ملاحظة عن المعرّفات: بعض الجداول القديمة تخزّن users.user_id وأخرى المعرّف الداخلي
 * (students.student_id / parents.parent_id). الدوال هنا تحدد بوضوح أي نوع تتوقعه.
 */
class Access
{
    /**
     * هل الطالب مرتبط بولي الأمر؟
     *
     * @param string $idType 'student' = students.student_id ، 'user' = users.user_id
     */
    public static function parentOwnsStudent(User $parentUser, $id, string $idType = 'student'): bool
    {
        $parent = DB::table('parents')->where('user_id', $parentUser->user_id)->first();
        if (!$parent) {
            return false;
        }

        $student = DB::table('students')
            ->where($idType === 'user' ? 'user_id' : 'student_id', $id)
            ->first();
        if (!$student) {
            return false;
        }

        return DB::table('parent_students')
            ->whereIn('parent_id', [$parent->user_id, $parent->parent_id])
            ->whereIn('student_id', [$student->student_id, $student->user_id])
            ->exists();
    }

    // ───────────────────────── النظام ─────────────────────────

    /**
     * معرّف مستخدم "المرسل الآلي" للإشعارات والاستدعاءات التلقائية: أول حساب أدمن فعلي.
     * (كان الكود يفترض أن المستخدم رقم 1 موجود دائماً، وينهار بقيد المفتاح الأجنبي إن لم يكن.)
     */
    public static function systemSenderId(): ?int
    {
        $id = DB::table('users')->where('role_id', 1)->orderBy('user_id')->value('user_id');

        return $id ? (int) $id : null;
    }

    // ───────────────────────── رئيس القسم ─────────────────────────

    /**
     * قسم رئيس القسم: من جدول heads، وإلا من users.department (الاسم).
     *
     * @return array{id: ?int, name: ?string}
     */
    public static function headDepartment(User $head): array
    {
        $row = DB::table('heads')->where('user_id', $head->user_id)->first();
        $id  = $row->department_id ?? null;
        $name = $id ? DB::table('departments')->where('department_id', $id)->value('name') : null;

        if (!$name && !empty($head->department)) {
            $name = $head->department;
            $id   = $id ?: DB::table('departments')->where('name', $name)->value('department_id');
        }

        return ['id' => $id ? (int) $id : null, 'name' => $name];
    }

    /** هل المقرر ضمن برنامج تابع لقسم رئيس القسم؟ */
    public static function headManagesCourse(User $head, $courseId): bool
    {
        $dept = self::headDepartment($head);
        if (!$dept['id']) {
            return false;
        }

        return DB::table('course_program')
            ->join('programs', 'course_program.program_id', '=', 'programs.id')
            ->where('programs.department_id', $dept['id'])
            ->where('course_program.course_id', $courseId)
            ->exists();
    }

    /**
     * هل المستخدم (users.user_id) طالب أو معلّم أو ولي أمر ضمن قسم الرئيس؟
     * الأدوار الأعلى (أدمن، رئيس قسم، شؤون) لا يديرها رئيس القسم أبداً.
     */
    public static function headManagesUser(User $head, $targetUserId): bool
    {
        $target = DB::table('users')->where('user_id', $targetUserId)->first();
        if (!$target || (int) $target->user_id === (int) $head->user_id) {
            return false;
        }

        $dept = self::headDepartment($head);
        if (!$dept['name'] && !$dept['id']) {
            return false;
        }

        switch ((int) $target->role_id) {
            case 2: // معلّم
            case 3: // طالب
                return $dept['name'] !== null && $target->department === $dept['name'];

            case 4: // ولي أمر: له ابن واحد على الأقل في القسم
                $parent = DB::table('parents')->where('user_id', $target->user_id)->first();
                if (!$parent) {
                    return false;
                }
                $childUserIds = DB::table('parent_students')
                    ->whereIn('parent_id', [$parent->user_id, $parent->parent_id])
                    ->pluck('student_id');

                return DB::table('students')
                    ->join('users', 'students.user_id', '=', 'users.user_id')
                    ->where(function ($q) use ($childUserIds) {
                        $q->whereIn('students.user_id', $childUserIds)
                          ->orWhereIn('students.student_id', $childUserIds);
                    })
                    ->where('users.department', $dept['name'])
                    ->exists();

            default:
                return false;
        }
    }

    /** هل الطالب (students.student_id) ضمن قسم رئيس القسم؟ */
    public static function headManagesStudent(User $head, $studentId): bool
    {
        $userId = DB::table('students')->where('student_id', $studentId)->value('user_id');

        return $userId ? self::headManagesUser($head, $userId) : false;
    }

    // ───────────────────────── إدارة الحسابات (تسلسل الأدوار) ─────────────────────────

    /**
     * هل يحق لهذا المستخدم تعديل/حذف/تعطيل حساب المستخدم الهدف؟
     *
     *  - الأدمن (1): أي حساب.
     *  - الشؤون (6): المعلّمون (2) والطلاب (3) وأولياء الأمور (4) ورؤساء الأقسام (5) فقط؛
     *    لا الأدمن ولا موظفو الشؤون الآخرون.
     *  - رئيس القسم (5): حسابات قسمه فقط (انظر headManagesUser).
     *  - أي دور آخر: لا.
     */
    public static function canManageAccount(User $actor, $targetUserId): bool
    {
        $target = DB::table('users')->where('user_id', $targetUserId)->first();
        if (!$target) {
            return false;
        }

        switch ((int) $actor->role_id) {
            case 1:
                return true;
            case 6:
                return in_array((int) $target->role_id, [2, 3, 4, 5], true);
            case 5:
                return self::headManagesUser($actor, $targetUserId);
            default:
                return false;
        }
    }

    // ───────────────────────── المعلّم ─────────────────────────

    /** هل المعلّم يدرّس هذا المقرر؟ */
    public static function teacherTeachesCourse(int $teacherId, $courseId): bool
    {
        return DB::table('course_teachers')
            ->where('teacher_id', $teacherId)
            ->where('course_id', $courseId)
            ->exists();
    }

    /** هل الطالب (students.student_id) مسجَّل في مقرر يدرّسه المعلّم؟ */
    public static function teacherTeachesStudent(int $teacherId, $studentId): bool
    {
        return DB::table('enrollments')
            ->join('course_teachers', 'course_teachers.course_id', '=', 'enrollments.course_id')
            ->where('course_teachers.teacher_id', $teacherId)
            ->where('enrollments.student_id', $studentId)
            ->exists();
    }

    /**
     * هل يحق للمعلّم استدعاء ولي أمر هذا الطالب؟
     * نعم إذا كان يدرّسه، أو كان مربّي دفعته (نفس البرنامج ونفس السنة).
     */
    public static function teacherCanSummonForStudent(int $teacherId, $studentId): bool
    {
        if (self::teacherTeachesStudent($teacherId, $studentId)) {
            return true;
        }

        $teacher = DB::table('teachers')->where('teacher_id', $teacherId)->first();
        if (!$teacher || empty($teacher->advisor_branch) || empty($teacher->advisor_year)) {
            return false;
        }

        return DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->where('students.student_id', $studentId)
            ->where('programs.name', $teacher->advisor_branch)
            ->where('users.academic_year', $teacher->advisor_year)
            ->exists();
    }

    /**
     * هل لدى الطالب إجازة أو إذن غياب معتمد في هذا التاريخ؟
     * leave_requests.student_id = users.user_id ، و absence_requests.student_id = students.student_id
     */
    public static function studentHasApprovedLeave($studentId, $studentUserId, string $date): bool
    {
        return DB::table('leave_requests')
                ->where('student_id', $studentUserId)->where('date', $date)->where('status', 'approved')->exists()
            || DB::table('absence_requests')
                ->where('student_id', $studentId)->where('date', $date)->where('status', 'approved')->exists();
    }

    /** هل الطالب (students.student_id) مسجَّل في هذا المقرر؟ */
    public static function studentEnrolledInCourse($studentId, $courseId): bool
    {
        return DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->exists();
    }
}
