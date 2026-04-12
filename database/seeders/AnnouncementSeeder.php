<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 🌟 البحث عن الطالب باستخدام الرقم الجامعي (2026100) بدلاً من الإيميل
        $user = User::where('university_id', '2026100')->first();

        // تأكدنا إنو الطالب موجود؟ هلق بنزرع كل الإعلانات باسمه
        if ($user) {
            
            // اختياري: تنظيف الإعلانات القديمة مشان ما تتكرر وتتكوم فوق بعض كل ما نعمل seed
            Announcement::truncate();

            // الإعلان الأول
            Announcement::create([
                'user_id' => $user->user_id ?? $user->id,
                'title' => 'تم إصدار جدول الامتحانات النهائية',
                'content' => 'يرجى مراجعة الجدول الدراسي والتأكد من التوقيت والقاعات.',
                'type' => 'course_specific',
                'created_at' => now(),
            ]);

            // الإعلان الثاني
            Announcement::create([
                'user_id' => $user->user_id ?? $user->id,
                'title' => 'ورشة عمل حول مهارات البحث العلمي',
                'content' => 'يدعوكم قسم تقانة المعلومات لحضور الورشة التفاعلية يوم الخميس القادم في مدرج الكلية.',
                'type' => 'general',
                'created_at' => now()->subDays(1),
            ]);

            // الإعلان الثالث
            Announcement::create([
                'user_id' => $user->user_id ?? $user->id,
                'title' => 'تحديث نظام استعارة الكتب',
                'content' => 'تم إضافة مجموعة جديدة من المراجع البرمجية إلى مكتبة المعهد، يمكنكم استعارتها ابتداءً من الغد.',
                'type' => 'general',
                'created_at' => now()->subDays(3),
            ]);
        }
    }
}