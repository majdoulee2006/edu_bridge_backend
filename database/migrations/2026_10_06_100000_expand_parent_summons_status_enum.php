<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * عمود parent_summons.status كان enum('sent','acknowledged','attended','cancelled') بينما
 * الكود يكتب ويقرأ مراحل سير العمل: pending_hod و pending_affairs (استدعاء المعلّم ← رئيس القسم ← الشؤون)
 * و approved / rejected / completed. النتيجة: خطأ "Data truncated" (أو حفظ قيمة فارغة بالوضع غير الصارم).
 * نوسّع الـ enum ليشمل كل القيم المستخدمة، ونُبقي الافتراضي 'sent'.
 */
return new class extends Migration
{
    private const NEW_VALUES = [
        'pending_hod', 'pending_affairs', 'sent', 'acknowledged',
        'attended', 'cancelled', 'approved', 'rejected', 'completed',
    ];

    private const OLD_VALUES = ['sent', 'acknowledged', 'attended', 'cancelled'];

    public function up(): void
    {
        DB::statement($this->alter(self::NEW_VALUES));
    }

    public function down(): void
    {
        // القيم الجديدة تُحوَّل إلى 'sent' قبل تضييق الـ enum حتى لا يفشل التراجع
        DB::table('parent_summons')
            ->whereNotIn('status', self::OLD_VALUES)
            ->update(['status' => 'sent']);

        DB::statement($this->alter(self::OLD_VALUES));
    }

    private function alter(array $values): string
    {
        $list = implode(',', array_map(fn ($v) => "'" . $v . "'", $values));

        return "ALTER TABLE `parent_summons` MODIFY `status` ENUM($list) NOT NULL DEFAULT 'sent'";
    }
};
