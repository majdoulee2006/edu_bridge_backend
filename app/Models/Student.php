<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_id';

    protected $fillable = [
        'user_id',
        'parent_id',
        'student_code',
        'level',
        'birth_date',
        'face_embedding',
        'requires_face_reset',
        'reference_photo',
        'device_id',
        'is_device_locked',
        'web_device_token',
        'program_id',
    ];

    protected $casts = [
        'face_embedding'      => 'array',
        'requires_face_reset' => 'boolean',
    ];

    // علاقة الطالب بالحساب الأساسي (User)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // علاقة الطالب بالبرنامج (Program)
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    // علاقة الطالب بالمواد اللي مسجل فيها (Many to Many عبر Enrollments)
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'enrollments', 'student_id', 'course_id')
                    ->withPivot('status', 'enrollment_date')
                    ->withTimestamps();
    }

    /**
     * التحقق الأمني من أحقية الطالب في حضور جلسة مقرر معين (السنة الدراسية، الاختصاص، والتسجيل)
     */
    public function checkCourseEligibility($courseId): array
    {
        $course = is_object($courseId) ? $courseId : \DB::table('courses')->where('course_id', $courseId)->first();
        if (!$course) {
            return [
                'eligible' => false,
                'reason'   => 'course_not_found',
                'message'  => 'المقرر الدراسي غير موجود أو تم إلغاؤه.',
            ];
        }

        $user = $this->user ?? \DB::table('users')->where('user_id', $this->user_id)->first();
        $studentLevel = trim($this->level ?? $user?->academic_year ?? 'السنة الأولى');

        $yearMapRev = [
            'السنة الأولى' => 1, 'أولى' => 1, '1' => 1,
            'السنة الثانية' => 2, 'ثانية' => 2, '2' => 2,
            'السنة الثالثة' => 3, 'ثالثة' => 3, '3' => 3,
            'السنة الرابعة' => 4, 'رابعة' => 4, '4' => 4,
            'السنة الخامسة' => 5, 'خامسة' => 5, '5' => 5,
        ];
        $studentYearNum = $yearMapRev[$studentLevel] ?? null;
        $courseYearNum = (int)$course->year;

        // 1. الأولوية القصوى: فحص مطابقة الاختصاص حصراً (نفس الاختصاص فقط)
        $coursePrograms = \DB::table('course_program')
            ->join('programs', 'course_program.program_id', '=', 'programs.id')
            ->where('course_program.course_id', $course->course_id)
            ->get();

        $studentProgramId = $this->program_id;
        if (!$studentProgramId && !empty($user?->branch)) {
            $studentProgramId = \DB::table('programs')->where('name', $user->branch)->value('id');
        }

        if ($coursePrograms->isNotEmpty()) {
            $allowedProgramIds = $coursePrograms->pluck('program_id')->toArray();
            $allowedProgramNames = $coursePrograms->pluck('name')->implode(' أو ');

            if (!$studentProgramId || !in_array($studentProgramId, $allowedProgramIds)) {
                $studentProgramName = $studentProgramId
                    ? (\DB::table('programs')->where('id', $studentProgramId)->value('name') ?? 'اختصاصك')
                    : ($user?->branch ?: 'اختصاصك الحالي');

                return [
                    'eligible' => false,
                    'reason'   => 'program_mismatch',
                    'message'  => "عذراً، هذه الجلسة غير مخصصة لك! هذه المحاضرة مخصصة حصراً لطلاب اختصاص ({$allowedProgramNames}) بينما أنت مسجل في اختصاص ({$studentProgramName}).",
                ];
            }
        }

        // 2. فحص مطابقة السنة الدراسية حصراً (السنة الأولى لا تحضر عند الثانية والعكس)
        if ($courseYearNum > 0 && $studentYearNum > 0 && $courseYearNum !== $studentYearNum) {
            $courseYearName = ($courseYearNum === 1 ? 'السنة الأولى' : ($courseYearNum === 2 ? 'السنة الثانية' : 'السنة ' . $courseYearNum));
            $studentYearName = ($studentYearNum === 1 ? 'السنة الأولى' : ($studentYearNum === 2 ? 'السنة الثانية' : 'السنة ' . $studentYearNum));
            return [
                'eligible' => false,
                'reason'   => 'year_mismatch',
                'message'  => "عذراً، هذه الجلسة غير مخصصة لك! هذه المحاضرة مخصصة حصراً لطلاب ({$courseYearName}) بينما أنت مقيد في ({$studentYearName}).",
            ];
        }

        // 3. فحص تسجيل الطالب في المقرر الدراسي (فقط من عنده هذه المادة فعلياً في خطته)
        $isEnrolled = \DB::table('enrollments')
            ->where('student_id', $this->student_id)
            ->where('course_id', $course->course_id)
            ->where('status', '!=', 'dropped')
            ->exists();

        if (!$isEnrolled) {
            return [
                'eligible' => false,
                'reason'   => 'not_enrolled',
                'message'  => "عذراً، هذه الجلسة غير مخصصة لك! أنت غير مسجل في مقرر ({$course->title}) ولا توجد هذه المادة ضمن خطتك الدراسية.",
            ];
        }

        return [
            'eligible' => true,
            'course'   => $course,
        ];
    }

    // علاقة الطالب بعلاماته
    public function grades()
    {
        return $this->hasMany(Grade::class, 'student_id', 'student_id');
    }

    // علاقة الطالب بسجلات الحضور
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id', 'student_id');
    }

    // parent_students.student_id/parent_id هما FK على users.user_id (وليس students.student_id/parents.parent_id)
    public function parentStudents() {
        return $this->hasMany(StudentParent::class, 'student_id', 'user_id');
    }

    public function parents() {
        return $this->belongsToMany(Parents::class, 'parent_students', 'student_id', 'parent_id', 'user_id', 'user_id');
    }

    public static function autoAssignAdvisor($studentId)
    {
        $student = \DB::table('students')->where('student_id', $studentId)->first();
        if (!$student) return;

        $level = $student->level ?? 'السنة الأولى';
        $academicYear = trim($level);
        if ($academicYear === 'أولى' || $academicYear === 'السنة الأولى' || $academicYear === '1') {
            $academicYear = 'السنة الأولى';
        } elseif ($academicYear === 'ثانية' || $academicYear === 'السنة الثانية' || $academicYear === '2') {
            $academicYear = 'السنة الثانية';
        } elseif ($academicYear === 'ثالثة' || $academicYear === 'السنة الثالثة' || $academicYear === '3') {
            $academicYear = 'السنة الثالثة';
        } elseif ($academicYear === 'رابعة' || $academicYear === 'السنة الرابعة' || $academicYear === '4') {
            $academicYear = 'السنة الرابعة';
        } elseif ($academicYear === 'خامسة' || $academicYear === 'السنة الخامسة' || $academicYear === '5') {
            $academicYear = 'السنة الخامسة';
        }

        $branch = null;
        if ($student->program_id) {
            $branch = \DB::table('programs')->where('id', $student->program_id)->value('name');
        }

        if (!$branch) {
            $user = \DB::table('users')->where('user_id', $student->user_id)->first();
            if ($user) {
                $branch = $user->department;
            }
        }

        if ($branch && $academicYear) {
            // Check if there is already an advisor teacher for this branch and year
            $exists = \DB::table('teachers')
                ->where('advisor_branch', $branch)
                ->where('advisor_year', $academicYear)
                ->exists();

            if (!$exists) {
                // Find a teacher in the same department/branch
                $teacher = \DB::table('teachers')
                    ->join('users', 'teachers.user_id', '=', 'users.user_id')
                    ->where('users.department', 'LIKE', '%' . $branch . '%')
                    ->select('teachers.teacher_id')
                    ->first();

                if (!$teacher) {
                    $studentDept = \DB::table('users')->where('user_id', $student->user_id)->value('department');
                    if ($studentDept) {
                        $teacher = \DB::table('teachers')
                            ->join('users', 'teachers.user_id', '=', 'users.user_id')
                            ->where('users.department', $studentDept)
                            ->select('teachers.teacher_id')
                            ->first();
                    }
                }

                if (!$teacher) {
                    $teacher = \DB::table('teachers')->select('teachers.teacher_id')->first();
                }

                if ($teacher) {
                    \DB::table('teachers')
                        ->where('teacher_id', $teacher->teacher_id)
                        ->update([
                            'advisor_branch' => $branch,
                            'advisor_year' => $academicYear,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    public static function autoEnrollCourses($studentId)
    {
        $student = static::with(['user', 'program'])->find($studentId);
        if (!$student) return;

        $user = $student->user;
        $programId = $student->program_id;

        $query = Course::query();

        if ($programId) {
            $query->whereHas('programs', function($pQuery) use ($programId) {
                $pQuery->where('programs.id', $programId);
            });
        }

        // استخراج سنة الطالب كـ رقم
        $studentLevel = trim($student->level ?? $user->academic_year ?? 'السنة الأولى');
        $map = [
            'السنة الأولى' => 1, 'أولى' => 1, '1' => 1,
            'السنة الثانية' => 2, 'ثانية' => 2, '2' => 2,
            'السنة الثالثة' => 3, 'ثالثة' => 3, '3' => 3,
            'السنة الرابعة' => 4, 'رابعة' => 4, '4' => 4,
            'السنة الخامسة' => 5, 'خامسة' => 5, '5' => 5
        ];
        $studentYearInt = $map[$studentLevel] ?? 1;

        // تسجيل الطالب فقط في مواد سنته الدراسية والمواد العامة
        $query->where(function($q) use ($studentYearInt) {
            $q->where('year', $studentYearInt)
              ->orWhereNull('year');
        });

        $courses = $query->get();

        foreach ($courses as $course) {
            Enrollment::firstOrCreate([
                'student_id' => $student->student_id,
                'course_id'  => $course->course_id,
            ], [
                'status'          => 'active',
                'enrollment_date' => now(),
            ]);
        }
    }
}

