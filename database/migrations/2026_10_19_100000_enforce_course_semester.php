<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * لكل مقرر فصل دراسي، دون استثناء (قرار رئيسة المشروع). المولّدات (جدول أسبوعي وامتحاني) تعتمد على هذه القاعدة.
 *
 *  1. المقررات التي بلا فصل تُسند إلى الفصل الفعّال (وإن لم يوجد فعّال فأقدم فصل)، وإن لم يوجد أي فصل يُرفض الترحيل.
 *  2. العمود يصير NOT NULL.
 *  3. المفتاح الأجنبي يتحول من SET NULL إلى RESTRICT: لا يُحذف فصل له مقررات (SET NULL كان يعيد المقرر بلا فصل بصمت).
 */
return new class extends Migration
{
    public function up(): void
    {
        $fallback = DB::table('semesters')->where('is_active', true)->orderByDesc('semester_id')->value('semester_id')
            ?? DB::table('semesters')->orderBy('semester_id')->value('semester_id');

        if ($fallback !== null) {
            DB::table('courses')->whereNull('semester_id')->update(['semester_id' => $fallback, 'updated_at' => now()]);
        }

        if (DB::table('courses')->whereNull('semester_id')->exists()) {
            throw new RuntimeException('توجد مقررات بلا فصل ولا يوجد أي فصل دراسي لإسنادها إليه. أنشئ فصلاً ثم أعد الترحيل.');
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreign('semester_id')->references('semester_id')->on('semesters')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreign('semester_id')->references('semester_id')->on('semesters')->nullOnDelete();
        });
    }
};
