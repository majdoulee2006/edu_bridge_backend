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

    /** هل الطالب (students.student_id) مسجَّل في هذا المقرر؟ */
    public static function studentEnrolledInCourse($studentId, $courseId): bool
    {
        return DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->exists();
    }
}
