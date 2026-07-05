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

    public function parentStudents() {
    return $this->hasMany(StudentParent::class, 'student_id');
}

    public function parents() {
        return $this->belongsToMany(Parents::class, 'parent_students', 'student_id', 'parent_id');
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
                            'is_advisor' => 1,
                            'advisor_branch' => $branch,
                            'advisor_year' => $academicYear,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }
}
