<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ChatController::sendMessage يحفظ category = 'chat' لإشعارات الرسائل،
        // بس هاي القيمة ما كانت مضافة لتعريف enum الأصلي فكانت تسبب خطأ
        // "Data truncated for column 'category'" عند إرسال أي رسالة شات
        DB::statement("ALTER TABLE notifications MODIFY category ENUM('academic', 'administrative', 'chat') DEFAULT 'administrative'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY category ENUM('academic', 'administrative') DEFAULT 'administrative'");
    }
};
