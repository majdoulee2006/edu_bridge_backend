<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ⚠️ تم إزالة التعديلات الخاصة بجدول schedules من هذا الملف
        // لأن زميلتك أضافت teacher_id و section في ملف الـ Migration رقم 104117
        // إبقاء الكود هنا كان سيؤدي إلى خطأ: Column 'teacher_id' already exists.

        // ── 2. تعديل جدول exams (الجدول الامتحاني) ──
        Schema::table('exams', function (Blueprint $table) {
            // القاعة التي يُقام فيها الامتحان (مثال: "A1", "قاعة 3")
            $table->string('room')
                  ->nullable()
                  ->after('exam_date');

            // الشعبة المعنية بهذا الامتحان
            $table->string('class_group')
                  ->nullable()
                  ->after('room');
        });
    }

    /**
     * Reverse the migrations.
     * تُستخدم عند التراجع عن هذا الـ migration بـ: php artisan migrate:rollback
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['room', 'class_group']);
        });
    }
};