<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        // 1. البحث عن ولي الأمر ورئيس القسم بالمسميات الجديدة
        $parent = User::where('role', 'parent')->first();
        $head = User::where('role', 'head')->first(); // 👈 تعديل: من head_of_department إلى head

        if ($parent) {
            Notification::create([
                'user_id' => $parent->user_id, // 👈 تعديل: استخدام user_id بدلاً من id
                'title' => 'تنبيه غياب',
                'message' => 'نحيطكم علماً بغياب ابنكم عمر عن محاضرة البرمجة اليوم.',
                'type' => 'attendance',
            ]);
        }

        if ($head) {
            Notification::create([
                'user_id' => $head->user_id, // 👈 تعديل: استخدام user_id بدلاً من id
                'title' => 'طلب إجازة جديد',
                'message' => 'هناك طلب إجازة معلق مقدم من المدرس سامر المحمد بانتظار موافقتك.',
                'type' => 'leave_request',
            ]);
        }
    }
}
