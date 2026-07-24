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

        // 6. ربط المدرس التجريبي بالمادة (نحتاج teacherId قبل إضافة المحاضرات)
        $teacherId = DB::table('teachers')->first()->teacher_id ?? null;
        if ($teacherId) {
            DB::table('course_teachers')->insert([
                ['course_id' => $course1, 'teacher_id' => $teacherId, 'role' => 'primary', 'created_at' => $now, 'updated_at' => $now],
                ['course_id' => $course2, 'teacher_id' => $teacherId, 'role' => 'primary', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // 7. إضافة محاضرات مع teacher_id
        DB::table('lessons')->insert([
            ['course_id' => $course1, 'teacher_id' => $teacherId, 'title' => 'المحاضرة الأولى: المتغيرات', 'description' => 'شرح أساسي للمتغيرات وأنواع البيانات', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $course1, 'teacher_id' => $teacherId, 'title' => 'المحاضرة الثانية: الشروط', 'description' => 'If Statement والحلقات التكرارية', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $course2, 'teacher_id' => null,       'title' => 'مقدمة في الجداول', 'description' => 'SQL Basics', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 8. تسجيل الطالب التجريبي (عمر) بالمادة
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

        // 9. إضافة جداول دراسية للمادة (بالأسماء العربية التي يتوقعها التطبيق)
        DB::table('schedules')->insert([
            ['course_id' => $course1, 'teacher_id' => $teacherId, 'day' => 'الاثنين',   'start_time' => '08:00:00', 'end_time' => '09:30:00', 'room' => 'قاعة 101', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $course1, 'teacher_id' => $teacherId, 'day' => 'الأربعاء',  'start_time' => '10:00:00', 'end_time' => '11:30:00', 'room' => 'قاعة 101', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $course1, 'teacher_id' => $teacherId, 'day' => 'الخميس',    'start_time' => '14:00:00', 'end_time' => '15:30:00', 'room' => 'قاعة 203', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $course2, 'teacher_id' => $teacherId, 'day' => 'الثلاثاء',  'start_time' => '09:00:00', 'end_time' => '10:30:00', 'room' => 'قاعة 305', 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => $course2, 'teacher_id' => $teacherId, 'day' => 'الجمعة',    'start_time' => '08:00:00', 'end_time' => '09:30:00', 'room' => 'قاعة 305', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 10. إضافة واجبات للمادة
        DB::table('assignments')->insert([
            [
                'course_id'   => $course1,
                'title'       => 'واجب 1: تمارين المتغيرات',
                'description' => 'حل التمارين من الصفحة 10 إلى 15 وتطبيق أنواع البيانات المختلفة',
                'due_date'    => $now->copy()->addDays(7),
                'max_points'  => 100,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'course_id'   => $course1,
                'title'       => 'واجب 2: برنامج الحاسبة',
                'description' => 'كتابة برنامج حاسبة بسيط يدعم العمليات الأربع الأساسية',
                'due_date'    => $now->copy()->addDays(14),
                'max_points'  => 100,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);

        $this->command->info('✅ تم زراعة الهيكلية الأكاديمية (فصول، أقسام، دورات، جداول، محاضرات، واجبات) بنجاح!');
    }
}