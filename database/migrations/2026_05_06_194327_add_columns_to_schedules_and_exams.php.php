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
        // ── 2. تعديل جدول exams (الجدول الامتحاني) ──
        Schema::table('exams', function (Blueprint $table) {
            // القاعة التي يُقام فيها الامتحان (مثال: "A1", "قاعة 3") بعد التحقق من عدم وجودها
            if (!Schema::hasColumn('exams', 'room')) {
                $table->string('room')
                      ->nullable()
                      ->after('exam_date');
            }

            // الشعبة المعنية بهذا الامتحان بعد التحقق من عدم وجودها
            if (!Schema::hasColumn('exams', 'class_group')) {
                $table->string('class_group')
                      ->nullable()
                      ->after('room');
            }
        });
    }

    /**
     * Reverse the migrations.
     * تُستخدم عند التراجع عن هذا الـ migration بـ: php artisan migrate:rollback
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('exams', 'room')) {
                $columns[] = 'room';
            }
            if (Schema::hasColumn('exams', 'class_group')) {
                $columns[] = 'class_group';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};