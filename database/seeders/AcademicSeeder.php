<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. إضافة فصل دراسي
        $semesterId = DB::table('semesters')->insertGetId([
            'name' => 'الفصل الأول 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-15',
            'created_at' => $now, 'updated_at' => $now
        ]);

        // 2. إضافة قسم
        $deptId = DB::table('departments')->insertGetId([
            'name' => 'قسم هندسة البرمجيات',
            'description' => 'قسم يهتم بتطوير البرمجيات والتطبيقات',
            'created_at' => $now, 'updated_at' => $now
        ]);

        // 3. إضافة مسارات/دورات
        $prog1 = DB::table('programs')->insertGetId(['name' => 'دبلوم تطوير الويب', 'department_id' => $deptId, 'created_at' => $now, 'updated_at' => $now]);
        $prog2 = DB::table('programs')->insertGetId(['name' => 'دبلوم الذكاء الاصطناعي', 'department_id' => $deptId, 'created_at' => $now, 'updated_at' => $now]);

        // 4. إضافة مواد
        $course1 = DB::table('courses')->insertGetId(['title' => 'أساسيات البرمجة', 'description' => 'مقدمة في البرمجة','level' =>'مبتدئ' ,'semester_id' => $semesterId, 'created_at' => $now, 'updated_at' => $now]);
        $course2 = DB::table('courses')->insertGetId(['title' => 'قواعد البيانات', 'description' => 'تصميم قواعد البيانات', 'level' =>'متوسط','semester_id' => $semesterId, 'created_at' => $now, 'updated_at' => $now]);

        // 5. ربط المواد بالمسارات (تطبيق فكرة المادة المشتركة)
        DB::table('course_program')->insert([
            ['course_id' => $course1, 'program_id' => $prog1],
            ['course_id' => $course1, 'program_id' => $prog2], // مادة مشتركة
            ['course_id' => $course2, 'program_id' => $prog1],
        ]);

        // 6. إضافة محاضرات للمواد
        DB::table('lessons')->insert([
            ['course_id' => $course1, 'title' => 'المحاضرة الأولى: المتغيرات', 'description' => 'شرح أساسي', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $course1, 'title' => 'المحاضرة الثانية: الشروط', 'description' => 'If Statement', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $course2, 'title' => 'مقدمة في الجداول', 'description' => 'SQL Basics', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 7. تسجيل الطالب التجريبي (عمر) بالمادة
        $studentId = DB::table('students')->where('student_code', '2026100')->value('student_id');
        if ($studentId) {
            DB::table('enrollments')->insert([
                'student_id' => $studentId, 
                'course_id' => $course1, 
                'enrollment_date' => $now,
                'status' => 'active', 
                'created_at' => $now, 
                'updated_at' => $now
            ]);
        }

        // 8. ربط المدرس التجريبي بالمادة
        $teacherId = DB::table('teachers')->first()->teacher_id ?? null;
        if ($teacherId) {
            DB::table('course_teachers')->insert([
                'course_id' => $course1, 
                'teacher_id' => $teacherId, 
                'role' => 'primary', 
                'created_at' => $now, 
                'updated_at' => $now
            ]);
        }
        
        $this->command->info('✅ تم زراعة الهيكلية الأكاديمية (فصول، أقسام، دورات، مواد، محاضرات) بنجاح!');
    }
}