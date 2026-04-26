<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        // 🌟 1. التأكد من وجود كورس، إذا مافي بنعمل كورس وهمي
        $course = DB::table('courses')->first();
        
        if (!$course) {
            $courseId = DB::table('courses')->insertGetId([
                'title' => 'كورس البرمجة التجريبي',
                'description' => 'كورس مخصص لاختبار واجهات التطبيق',
                'level' => 'المستوى الأول', // 🌟 شوفيه؟ هون مكانه الصحيح بجدول الكورسات!
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            $courseId = isset($course->course_id) ? $course->course_id : $course->id;
        }

        // 🌟 2. بيانات الدروس الوهمية (بدون level لأنها مو بهالجدول)
        $lessons = [
            [
                'course_id' => $courseId,
                'title' => 'مقدمة في المادة',
                'description' => 'الدرس الأول للتعرف على أساسيات المادة وتوزيع العلامات.',
                'content_url' => 'https://example.com/lesson1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => $courseId,
                'title' => 'الفصل الأول: المفاهيم الأساسية',
                'description' => 'شرح تفصيلي لأهم المصطلحات التي سنستخدمها.',
                'content_url' => 'https://example.com/lesson2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => $courseId,
                'title' => 'مراجعة وتطبيقات عملية',
                'description' => 'حل أسئلة وتمارين على ما سبق دراسته.',
                'content_url' => 'https://example.com/lesson3',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        // 🌟 3. إدخال البيانات في الجدول
        DB::table('lessons')->insert($lessons);

        $this->command->info('✅ تم زرع بيانات الدروس والكورسات بنجاح للفريق! 🚀');
    }
}