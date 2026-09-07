<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Department;
use App\Models\Program;
use App\Models\Course;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ITStudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. التأكد من وجود القسم (لأن أمر migrate:fresh قام بمسح الداتابيز بالكامل)
        $department = Department::firstOrCreate(
            ['name' => 'نظم المعلومات الحاسوبية'],
            ['description' => 'قسم نظم المعلومات الحاسوبية (CIS)']
        );
        
        // 2. التأكد من وجود برنامج دراسي تابع للقسم
        $program = Program::firstOrCreate(
            ['name' => 'برنامج نظم المعلومات', 'department_id' => $department->department_id]
        );

        // 3. التأكد من وجود دورات تابعة للقسم
        $courseIds = [];
        for ($i = 1; $i <= 4; $i++) {
            $course = Course::firstOrCreate(
                ['title' => "دورة نظم معلومات $i"],
                [
                    'description' => "دورة رقم $i تابعة لقسم نظم المعلومات الحاسوبية",
                    'level' => 'مبتدئ',
                    'year' => 1,
                    'hours' => 3
                ]
            );
            $course->programs()->syncWithoutDetaching([$program->id]);
            $courseIds[] = $course->course_id;
        }

        $password = Hash::make('12345678');
        $startId = 2026200;
        
        $firstNames = ['محمد', 'أحمد', 'عمر', 'خالد', 'عبدالله', 'فاطمة', 'عائشة', 'مريم', 'سارة', 'نورة', 'يوسف', 'طارق', 'حسن', 'حسين', 'منى', 'ريم', 'ندى', 'محمود', 'علي', 'ليلى'];
        $lastNames = ['المحمد', 'الخالد', 'العلي', 'الحسن', 'العبدالله', 'السالم', 'النجار', 'الحداد', 'العبيد', 'المحمود', 'اليوسف', 'الطارق', 'الحسين', 'العمر', 'الصالح', 'الأحمد', 'الناصر', 'الدوسري', 'القحطاني', 'الزهراني'];

        $students = [];

        // 3. إنشاء 20 طالب
        for ($i = 0; $i < 20; $i++) {
            $uniId = (string)($startId + $i);
            
            $fName = $firstNames[$i % count($firstNames)];
            $lName = $lastNames[$i % count($lastNames)];
            
            // إنشاء حساب User
            $user = User::create([
                'role_id' => 3, // Student Role
                'full_name' => "$fName $lName",
                'first_name' => $fName,
                'last_name' => $lName,
                'username' => "student_$uniId",
                'email' => "student{$uniId}@cis.edu",
                'password' => $password,
                'status' => 'active',
                'university_id' => $uniId,
                'department' => $department->name,
            ]);

            // إنشاء ملف Student
            $student = Student::create([
                'user_id' => $user->user_id,
                'student_code' => $uniId,
                'level' => 'السنة الأولى',
                'program_id' => $program->id,
            ]);

            $students[] = $student;
        }

        // 4. توزيع الطلاب على الدورات
        $studentChunks = array_chunk($students, 5); 

        foreach ($studentChunks as $index => $chunk) {
            $courseId = $courseIds[$index] ?? $courseIds[0];

            foreach ($chunk as $student) {
                $student->courses()->attach($courseId, [
                    'status' => 'active',
                    'enrollment_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('✅ تم إنشاء قسم نظم المعلومات والدورات الخاصة به، وإضافة 20 طالب بنجاح!');
    }
}
