<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // الإعلان الأول
        Announcement::create([
            'user_id' => 1, // 🌟 هذا هو السطر الذي حل المشكلة (ربط الإعلان بالأدمن)
            'title' => 'تم إصدار جدول الامتحانات النهائية',
            'content' => 'يرجى من جميع الطلاب مراجعة الجدول الدراسي والتأكد من توقيت الامتحانات والقاعات المخصصة.',
            'type' => 'course_specific',
            'created_at' => now()->subHours(2),
        ]);

        // الإعلان الثاني
        Announcement::create([
            'user_id' => 1,
            'title' => 'ورشة عمل حول مهارات البحث العلمي',
            'content' => 'يدعوكم قسم تقانة المعلومات لحضور الورشة التفاعلية يوم الخميس القادم في مدرج الكلية.',
            'type' => 'general',
            'created_at' => now()->subDays(1),
        ]);

        // الإعلان الثالث
        Announcement::create([
            'user_id' => 1,
            'title' => 'تحديث نظام استعارة الكتب',
            'content' => 'تم إضافة مجموعة جديدة من المراجع البرمجية إلى مكتبة المعهد، يمكنكم استعارتها ابتداءً من الغد.',
            'type' => 'general',
            'created_at' => now()->subDays(3),
        ]);
    }
}