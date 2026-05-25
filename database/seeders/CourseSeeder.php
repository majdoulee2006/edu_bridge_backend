<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['title' => 'برمجة موبايل', 'description' => 'تطوير تطبيقات الموبايل باستخدام Flutter', 'level' => 'متوسط'],
            ['title' => 'قواعد البيانات', 'description' => 'تصميم وإدارة قواعد البيانات', 'level' => 'مبتدئ'],
            ['title' => 'الذكاء الاصطناعي', 'description' => 'مبادئ الذكاء الاصطناعي وتعلم الآلة', 'level' => 'متقدم'],
            ['title' => 'تطوير الويب', 'description' => 'بناء تطبيقات الويب باستخدام Laravel', 'level' => 'متوسط'],
        ];

        foreach ($courses as $course) {
            DB::table('courses')->insertOrIgnore($course);
        }
    }
}
