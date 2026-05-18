<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HodDummySeeder extends Seeder
{
    public function run(): void
    {
        $hodId = DB::table('users')->where('role_id', 5)->first()->user_id ?? 1;

        // إعلانات
        DB::table('announcements')->insert([
            [
                'user_id' => $hodId,
                'title' => 'تحديث جدول الاجتماعات',
                'content' => 'تم نقل اجتماع أعضاء هيئة التدريس إلى القاعة ب. يرجى الحضور قبل الموعد بـ 10 دقائق لمناقشة المستجدات.',
                'category' => 'إعلان هام',
                'target_audience' => 'all',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $hodId,
                'title' => 'تسليم النتائج النهائية',
                'content' => 'نذكر جميع الزملاء بضرورة الالتزام بتسليم نتائج الفصل الدراسي القادم والالتزام بالمعايير.',
                'category' => 'إداري',
                'target_audience' => 'teachers',
                'type' => 'general',
                'created_at' => now()->subDays(1),
                'updated_at' => now(),
            ],
        ]);

        // بعض الإجازات الوهمية لتظهر
        // نحتاج مدرب
        $teacherId = DB::table('teachers')->first()->teacher_id ?? null;
        if (!$teacherId) {
            $userId = DB::table('users')->insertGetId([
                'full_name' => 'د. محمد العمري',
                'username' => 'teacher_mohammad',
                'password' => bcrypt('123456'),
                'role_id' => 2, // Teacher
                'created_at' => now()
            ]);
            $teacherId = DB::table('teachers')->insertGetId([
                'user_id' => $userId,
                'specialization' => 'علوم الحاسوب',
            ]);
        }

        DB::table('leave_requests')->insert([
            [
                'teacher_id' => $teacherId,
                'student_id' => null,
                'type' => 'full_day',
                'leave_category' => 'daily',
                'date' => now()->addDays(1)->format('Y-m-d'),
                'reason' => 'ظرف عائلي طارئ يتطلب تواجدي خارج المدينة.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
        
        // محادثات وهمية
        DB::table('messages')->insert([
            [
                'sender_id' => DB::table('users')->where('role_id', 2)->first()->user_id ?? 1,
                'receiver_id' => $hodId,
                'message' => 'السلام عليكم، هل يمكننا مراجعة النقاط الأخيرة في الاجتماع بخصوص التقرير؟',
                'created_at' => now()->subHours(2),
                'updated_at' => now(),
            ]
        ]);

        // إضافة كورسات وهمية إن لم توجد
        $courseId1 = DB::table('courses')->first()->course_id ?? null;
        if (!$courseId1) {
            $courseId1 = DB::table('courses')->insertGetId([
                'title' => 'برمجة الويب باستخدام Laravel',
                'description' => 'كورس برمجة متكامل لبناء مواقع الويب.',
                'level' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $courseId2 = DB::table('courses')->skip(1)->first()->course_id ?? null;
        if (!$courseId2) {
            $courseId2 = DB::table('courses')->insertGetId([
                'title' => 'قواعد البيانات MySQL',
                'description' => 'كورس تفصيلي في تصميم وتطوير قواعد البيانات.',
                'level' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // جدول دراسي أسبوعي وهمي
        if (DB::table('schedules')->count() == 0) {
            DB::table('schedules')->insert([
                [
                    'course_id' => $courseId1,
                    'teacher_id' => $teacherId,
                    'day' => 'Sunday',
                    'start_time' => '10:00:00',
                    'end_time' => '12:00:00',
                    'room' => 'A1',
                    'class_group' => 'شعبة 1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'course_id' => $courseId2,
                    'teacher_id' => $teacherId,
                    'day' => 'Tuesday',
                    'start_time' => '08:30:00',
                    'end_time' => '10:30:00',
                    'room' => 'B3',
                    'class_group' => 'شعبة 2',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }

        // امتحانات وهمية
        if (DB::table('exams')->count() == 0) {
            DB::table('exams')->insert([
                [
                    'course_id' => $courseId1,
                    'exam_name' => 'الامتحان النصفي الأول',
                    'exam_date' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
                    'max_score' => 100,
                    'room' => 'مدرج 1',
                    'class_group' => 'شعبة 1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'course_id' => $courseId2,
                    'exam_name' => 'الامتحان العملي النهائي',
                    'exam_date' => now()->addWeeks(3)->format('Y-m-d H:i:s'),
                    'max_score' => 50,
                    'room' => 'مختبر 2',
                    'class_group' => 'شعبة 2',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
