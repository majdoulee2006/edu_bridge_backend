<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        
        // رح نجيب أول مستخدم بالداتابيز (غالباً المدير أو الشؤون) ليكون هو الناشر
        $userId = DB::table('users')->first()->user_id ?? 1;

        DB::table('announcements')->insert([
            [
                'user_id' => $userId,
                'title' => 'تم إصدار جدول الامتحانات النهائية للفصل الدراسي الأول',
                'content' => 'يرجى من جميع الطلاب مراجعة الجدول الدراسي والتأكد من توقيت الامتحانات والقاعات المخصصة.',
                'type' => 'general',
                'course_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $userId,
                'title' => 'ورشة عمل حول مهارات البحث العلمي',
                'content' => 'ندعو جميع الطلاب للحضور والمشاركة في ورشة العمل التي ستقام في مبنى الأنشطة.',
                'type' => 'general',
                'course_id' => null,
                'created_at' => Carbon::now()->subDays(1), // إعلان من مبارح
                'updated_at' => Carbon::now()->subDays(1),
            ]
        ]);
        
        $this->command->info('✅ تم زراعة الإعلانات بنجاح!');
    }
}