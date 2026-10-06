<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

/**
 * مساعدات لبناء بيانات أكاديمية صغيرة داخل الاختبارات.
 * تستعمل الجداول مباشرة (DB::table) لتبقى مستقلة عن الـ fillable والـ factories.
 */
trait MakesAcademicData
{
    public const ROLE_IDS = [
        'admin' => 1, 'teacher' => 2, 'student' => 3,
        'parent' => 4, 'head' => 5, 'affairs' => 6,
    ];

    private int $seq = 0;

    protected function nextSeq(): int
    {
        return ++$this->seq + random_int(1000, 9999) * 100;
    }

    /** ينشئ مستخدماً بدور معيّن (بدون صف في جدول الدور). */
    protected function makeUser(string $role, array $attrs = []): User
    {
        $n = $this->nextSeq();
        $id = DB::table('users')->insertGetId(array_merge([
            'role_id'    => self::ROLE_IDS[$role],
            'full_name'  => ucfirst($role) . " $n",
            'username'   => "{$role}_$n",
            'email'      => "{$role}_$n@example.test",
            'password'   => Hash::make('Password123!'),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));

        return User::findOrFail($id);
    }

    protected function makeDepartment(string $name = null): int
    {
        return DB::table('departments')->insertGetId([
            'name' => $name ?? 'Dept ' . $this->nextSeq(),
        ]);
    }

    protected function makeProgram(int $departmentId): int
    {
        return DB::table('programs')->insertGetId([
            'name'          => 'Program ' . $this->nextSeq(),
            'department_id' => $departmentId,
        ]);
    }

    /** @return array{user: User, student_id: int} */
    protected function makeStudent(array $userAttrs = [], ?int $programId = null): array
    {
        $user = $this->makeUser('student', $userAttrs);
        $studentId = DB::table('students')->insertGetId([
            'user_id'      => $user->user_id,
            'student_code' => 'S' . $this->nextSeq(),
            'program_id'   => $programId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return ['user' => $user, 'student_id' => $studentId];
    }

    /** @return array{user: User, teacher_id: int} */
    protected function makeTeacher(array $userAttrs = []): array
    {
        $user = $this->makeUser('teacher', $userAttrs);
        $teacherId = DB::table('teachers')->insertGetId([
            'user_id'        => $user->user_id,
            'specialization' => 'General',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return ['user' => $user, 'teacher_id' => $teacherId];
    }

    /** @return array{user: User, parent_id: int} */
    protected function makeParent(array $userAttrs = []): array
    {
        $user = $this->makeUser('parent', $userAttrs);
        $parentId = DB::table('parents')->insertGetId([
            'user_id'    => $user->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['user' => $user, 'parent_id' => $parentId];
    }

    /** @return array{user: User, head_id: int} */
    protected function makeHead(int $departmentId, array $userAttrs = []): array
    {
        $user = $this->makeUser('head', $userAttrs);
        $headId = DB::table('heads')->insertGetId([
            'user_id'       => $user->user_id,
            'department_id' => $departmentId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return ['user' => $user, 'head_id' => $headId];
    }

    protected function makeCourse(?int $programId = null, array $attrs = []): int
    {
        $courseId = DB::table('courses')->insertGetId(array_merge([
            'title'      => 'Course ' . $this->nextSeq(),
            'level'      => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));

        if ($programId) {
            DB::table('course_program')->insert(['course_id' => $courseId, 'program_id' => $programId]);
        }

        return $courseId;
    }

    protected function assignTeacher(int $courseId, int $teacherId, string $role = 'teacher'): void
    {
        DB::table('course_teachers')->insert([
            'course_id'  => $courseId,
            'teacher_id' => $teacherId,
            'role'       => $role,
        ]);
    }

    protected function enroll(int $studentId, int $courseId): void
    {
        DB::table('enrollments')->insert([
            'student_id'      => $studentId,
            'course_id'       => $courseId,
            'enrollment_date' => now()->toDateString(),
            'status'          => 'active',
        ]);
    }

    /** يربط ولي أمر بطالب بالطريقة المعتمدة (user_id لكليهما). */
    protected function linkParent(User $parentUser, User $studentUser): void
    {
        DB::table('parent_students')->insert([
            'parent_id'  => $parentUser->user_id,
            'student_id' => $studentUser->user_id,
        ]);
    }

    /** يسجّل الدخول عبر Sanctum لطلبات الـ API. */
    protected function actAs(User $user): static
    {
        Sanctum::actingAs($user);

        return $this;
    }
}
