<?php

namespace App\Services\TelegramBot;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Access;
use Illuminate\Support\Facades\DB;

/**
 * Telegram bot: ownership / scope checks for buttons that carry a record id.
 *
 * The role guard in handleCallbackQuery only proves "this is a teacher / parent / ...". The handlers then
 * load whatever id is inside the button (Course::find($id), Student::find($id) ...) without checking that
 * the record belongs to the caller. This trait is the single place that answers "may this user act on
 * THIS record?", so a forged or stale button cannot read or change someone else's data (IDOR).
 *
 * Unknown / public buttons return true (their role is already checked by requiredRoleForCallback).
 */
trait CallbackAuthorization
{
    private function callbackRecordId(string $data, string $prefix): int
    {
        return (int) substr($data, strlen($prefix));
    }

    private function userMayUseCallback(User $user, string $data): bool
    {
        // ---- ولي الأمر: أبناؤه فقط ----
        foreach (['parent_student_grades_', 'parent_student_attendance_'] as $prefix) {
            if (str_starts_with($data, $prefix)) {
                $childIds = $this->getParentChildren($user)->pluck('student_id')->map(fn ($id) => (int) $id)->all();

                return in_array($this->callbackRecordId($data, $prefix), $childIds, true);
            }
        }

        foreach (['parent_leave_approve_', 'parent_leave_reject_'] as $prefix) {
            if (str_starts_with($data, $prefix)) {
                $leave = DB::table('leave_requests')->where('id', $this->callbackRecordId($data, $prefix))->first();

                return $leave && Access::parentOwnsStudent($user, $leave->student_id, 'user');
            }
        }

        // ---- الطالب: عذره على غيابه هو، ومحتوى المواد المسجّل فيها ----
        if (str_starts_with($data, 'excuse_start_')) {
            $attendance = Attendance::with('student')->find($this->callbackRecordId($data, 'excuse_start_'));

            return $attendance && $attendance->student
                && (int) $attendance->student->user_id === (int) $user->user_id;
        }

        if (str_starts_with($data, 'course_lectures_')) {
            return $this->studentIsEnrolled($user, $this->callbackRecordId($data, 'course_lectures_'));
        }
        if (str_starts_with($data, 'lesson_details_')) {
            $lesson = Lesson::find($this->callbackRecordId($data, 'lesson_details_'));

            return $lesson && $this->studentIsEnrolled($user, (int) $lesson->course_id);
        }
        if (str_starts_with($data, 'resource_details_')) {
            $resource = Resource::find($this->callbackRecordId($data, 'resource_details_'));

            return $resource && $this->studentIsEnrolled($user, (int) $resource->course_id);
        }

        // ---- المعلم: مقرراته وما يتبعها فقط ----
        foreach ([
            'teacher_course_detail_', 'teacher_course_students_', 'teacher_course_start_attendance_',
            'teacher_course_assignments_', 'teacher_course_exams_', 'teacher_course_excuses_',
            'teacher_create_assign_course_', 'teacher_upload_lesson_start_',
            'teacher_assign_due_preset_', 'teacher_assign_points_preset_',
        ] as $prefix) {
            if (str_starts_with($data, $prefix)) {
                // أزرار الـ preset شكلها {courseId}_{value}، والباقي {courseId} فقط
                $courseId = (int) explode('_', substr($data, strlen($prefix)))[0];

                return $this->teacherOwnsCourse($user, $courseId);
            }
        }

        foreach (['teacher_session_stats_', 'teacher_end_session_'] as $prefix) {
            if (str_starts_with($data, $prefix)) {
                $courseId = (int) DB::table('attendance_sessions')
                    ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
                    ->where('attendance_sessions.id', $this->callbackRecordId($data, $prefix))
                    ->value('lessons.course_id');

                return $this->teacherOwnsCourse($user, $courseId);
            }
        }

        if (str_starts_with($data, 'teacher_assignment_submissions_')) {
            $assignment = Assignment::find($this->callbackRecordId($data, 'teacher_assignment_submissions_'));

            return $assignment && $this->teacherOwnsCourse($user, (int) $assignment->course_id);
        }
        if (str_starts_with($data, 'teacher_grade_sub_')) {
            $submission = AssignmentSubmission::with('assignment')->find($this->callbackRecordId($data, 'teacher_grade_sub_'));

            return $submission && $submission->assignment
                && $this->teacherOwnsCourse($user, (int) $submission->assignment->course_id);
        }
        if (str_starts_with($data, 'teacher_exam_grades_')) {
            $exam = Exam::find($this->callbackRecordId($data, 'teacher_exam_grades_'));

            return $exam && $this->teacherOwnsCourse($user, (int) $exam->course_id);
        }
        if (str_starts_with($data, 'teacher_excuse_detail_')) {
            $attendance = Attendance::with('lesson')->find($this->callbackRecordId($data, 'teacher_excuse_detail_'));

            return $attendance && $attendance->lesson
                && $this->teacherOwnsCourse($user, (int) $attendance->lesson->course_id);
        }

        // ---- رئيس القسم: قسمه فقط (نفس مرجع الويب: App\Support\Access) ----
        foreach (['hod_approve_req_', 'hod_reject_req_', 'hod_notes_req_'] as $prefix) {
            if (str_starts_with($data, $prefix)) {
                return $this->hodStudentRequestsQuery($user)
                    ->where('student_requests.id', $this->callbackRecordId($data, $prefix))
                    ->exists();
            }
        }
        if (str_starts_with($data, 'hod_teacher_detail_')) {
            $teacher = Teacher::find($this->callbackRecordId($data, 'hod_teacher_detail_'));

            return $teacher && Access::headManagesUser($user, $teacher->user_id);
        }
        if (str_starts_with($data, 'hod_course_detail_')) {
            return Access::headManagesCourse($user, $this->callbackRecordId($data, 'hod_course_detail_'));
        }
        foreach (['hod_approve_leave_', 'hod_reject_leave_'] as $prefix) {
            if (str_starts_with($data, $prefix)) {
                $leave = DB::table('leave_requests')->where('id', $this->callbackRecordId($data, $prefix))->first();
                $applicantId = $leave
                    ? ($leave->student_id ?: DB::table('teachers')->where('teacher_id', $leave->teacher_id)->value('user_id'))
                    : null;

                return $applicantId && Access::headManagesUser($user, $applicantId);
            }
        }

        return true;
    }

    private function studentIsEnrolled(User $user, int $courseId): bool
    {
        $student = Student::where('user_id', $user->user_id)->first();
        if (!$student || $courseId <= 0) {
            return false;
        }

        return $student->courses()->where('courses.course_id', $courseId)->exists();
    }

    private function teacherOwnsCourse(User $user, int $courseId): bool
    {
        if ($courseId <= 0) {
            return false;
        }

        return $this->getTeacherCourses($user)->pluck('course_id')->map(fn ($id) => (int) $id)->contains($courseId);
    }
}
