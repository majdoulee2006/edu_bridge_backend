<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Message;
use App\Models\User;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        // 🌟 جلب أول طالب وأول مدرب من قاعدة البيانات
        $student = User::where('role', 'student')->first();
        $teacher = User::where('role', 'teacher')->first();

        // التأكد من وجودهم لتجنب الأخطاء
        if (!$student || !$teacher) {
            $this->command->info('⚠️ يرجى إضافة طالب ومدرب واحد على الأقل في جدول users أولاً.');
            return;
        }

        // 🌟 إنشاء محادثة تجريبية (سيناريو واقعي)
        $messages = [
            [
                'sender_id' => $student->user_id,
                'receiver_id' => $teacher->user_id,
                'message' => 'السلام عليكم دكتور، هل يمكنني الاستفسار عن تفاصيل الوظيفة؟',
                'is_read' => true,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'sender_id' => $teacher->user_id,
                'receiver_id' => $student->user_id,
                'message' => 'وعليكم السلام، تفضل اسأل.',
                'is_read' => true,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ],
            [
                'sender_id' => $student->user_id,
                'receiver_id' => $teacher->user_id,
                'message' => 'هل تسليم الوظيفة سيكون ورقياً أم عبر التطبيق؟',
                'is_read' => true,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'sender_id' => $teacher->user_id,
                'receiver_id' => $student->user_id,
                'message' => 'التسليم سيكون عبر التطبيق حصراً، قسم الوظائف.',
                'is_read' => false, // رسالة جديدة لم يقرأها الطالب بعد
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // إدخال الرسائل لقاعدة البيانات
        foreach ($messages as $msg) {
            Message::create($msg);
        }

        $this->command->info('✅ تمت إضافة رسائل تجريبية بنجاح! 🚀');
    }
}