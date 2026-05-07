<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        // 0. Create a Student User and Student record
        // 0. Create a Student User and Student record
        $user = \App\Models\User::firstOrCreate(
            ['username' => '2026100'],
            [
                'full_name' => 'أحمد الطالب',
                'email' => 'student@test.com',
                'password' => bcrypt('123456'),
                'role' => 'student',
                'status' => 'active',
                'department' => 'هندسة حاسوب',
            ]
        );

        $student = \App\Models\Student::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'student_code' => '2026100',
                'level' => 'سنة ثالثة',
            ]
        );

        $studentId = $student->student_id;

        // --- Link to first parent automatically for testing ---
        $parent = \DB::table('parents')->first();
        if ($parent) {
            $student->parent_id = $parent->parent_id;
            $student->save();
        }
        // -----------------------------------------------------


        // 1. Create a Course if not exists
        $courseId = DB::table('courses')->insertGetId([
            'title' => 'تصميم تجربة المستخدم',
            'description' => 'مادة تعنى بتصميم واجهات وتجربة المستخدم',
            'level' => 'سنة ثالثة',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Enroll the student 

        DB::table('enrollments')->updateOrInsert([
            'student_id' => $studentId,
            'course_id' => $courseId,
        ], [
            'enrollment_date' => now()->subMonths(2),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create Assignments
        $assignment1 = DB::table('assignments')->insertGetId([
            'course_id' => $courseId,
            'title' => 'مشروع تصميم واجهة',
            'description' => 'قم بتصميم واجهة تطبيق موبايل',
            'due_date' => Carbon::now()->addDays(5),
            'max_points' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignment2 = DB::table('assignments')->insertGetId([
            'course_id' => $courseId,
            'title' => 'واجب الإحصاء #3',
            'description' => 'حل التمارين من 1 إلى 5',
            'due_date' => Carbon::now()->subDays(2),
            'max_points' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignment3 = DB::table('assignments')->insertGetId([
            'course_id' => $courseId,
            'title' => 'بحث التاريخ المعاصر',
            'description' => 'بحث حول تاريخ الحواسيب',
            'due_date' => Carbon::now()->subDays(10),
            'max_points' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Create Submission for Assignment 2 (Completed)
        DB::table('assignment_submissions')->insert([
            'assignment_id' => $assignment2,
            'student_id' => $studentId,
            'file_path' => '/storage/assignments/answer.pdf',
            'grade' => 10,
            'feedback' => 'عمل ممتاز',
            'submitted_at' => Carbon::now()->subDays(3),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Create Absence Requests
        DB::table('absence_requests')->insert([
            [
                'student_id' => $studentId,
                'date' => Carbon::now()->subDays(1)->toDateString(),
                'reason' => 'يعاني الطالب من وعكة صحية مفاجئة ويحتاج للراحة. مرفق صورة عن التقرير الطبي.',
                'document' => null,
                'status' => 'pending',
                'reviewed_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $studentId,
                'date' => Carbon::now()->subDays(5)->toDateString(),
                'reason' => 'خروج مبكر لظرف عائلي',
                'document' => null,
                'status' => 'approved',
                'reviewed_by' => 1,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ]
        ]);
        
        // 6. Create subjects, exams and grades
        $subjectId = DB::table('subjects')->insertGetId([
            'name' => 'برمجة ويب',
            'doctor_name' => 'د. أحمد',
            'room' => 'A1',
            'time' => '10:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $examId = DB::table('exams')->insertGetId([
            'course_id' => $courseId,
            'exam_name' => 'مذاكرة منتصف الفصل',
            'exam_date' => now()->subDays(10),
            'max_score' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7. Create Grades
        DB::table('grades')->insert([
            'student_id' => $studentId,
            'exam_id' => $examId,
            'score' => 28,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 8. Create Attendance
        $lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $courseId,
            'title' => 'مقدمة في تصميم واجهة المستخدم',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        DB::table('attendance')->insert([
            [
                'student_id' => $studentId,
                'lesson_id' => $lessonId,
                'status' => 'present',
                'attendance_date' => now()->subDays(5),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $studentId,
                'lesson_id' => $lessonId,
                'status' => 'absent',
                'attendance_date' => now()->subDays(12),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
