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
        // ── 1. تعديل جدول schedules (الجدول الدراسي الأسبوعي) ──
        Schema::table('schedules', function (Blueprint $table) {
            // الأستاذ المسؤول عن هذه الحصة
            if (!Schema::hasColumn('schedules', 'teacher_id')) {
                $table->foreignId('teacher_id')
                      ->nullable()
                      ->after('course_id')
                      ->constrained('teachers', 'teacher_id')
                      ->onDelete('set null');
            }

            // الشعبة التي تخص هذه الحصة (مثال: "معلوماتية 1", "معلوماتية 2")
            if (!Schema::hasColumn('schedules', 'class_group')) {
                $table->string('class_group')
                      ->nullable()
                      ->after('teacher_id');
            }
        });

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
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'class_group')) {
                $table->dropColumn(['class_group']);
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['room', 'class_group']);
        });
    }
};
