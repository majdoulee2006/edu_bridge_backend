<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الجلسات التي سبقت قاعدة "الإنهاء النهائي عند 12 ليلاً" تُعدّ منتهية كما هي.
 *
 * بدون هذا الترحيل ستلتقطها المهمة المجدولة attendance:finalize-sessions أول مرة تعمل، فتسجّل غيابات متأخرة
 * بأثر رجعي (وتُطلق إنذارات غياب وإشعارات للطلاب والأهل عن أيام مضت). الجلسة التي أُنهيت بالآلية القديمة
 * سُجِّل غيابها وقتها، والتي لم تُنهَ تبقى بحالها دون اختلاق غياب لها بعد أيام.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance_sessions') || !Schema::hasColumn('attendance_sessions', 'finalized_at')) {
            return;
        }

        DB::table('attendance_sessions')
            ->whereNull('finalized_at')
            ->where('created_at', '<', now()->startOfDay())
            ->update(['finalized_at' => now(), 'is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // لا رجعة: لا نعرف أي الجلسات كانت غير منتهية قبل الترحيل
    }
};
