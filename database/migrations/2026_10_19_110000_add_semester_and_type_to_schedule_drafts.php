<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * كل مسودة جدول لفصل دراسي محدد، ولها نوع (أسبوعي الآن، وامتحاني لاحقاً).
 * المسودات القائمة تُنسب إلى الفصل الفعّال (وإن لم يوجد فأقدم فصل) لأنها وُلّدت بلا فصل، فهي من الفصل الجاري عملياً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_drafts', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->after('department_id');
            $table->string('type', 16)->default('weekly')->after('semester_id');
        });

        $semester = DB::table('semesters')->where('is_active', true)->orderByDesc('semester_id')->value('semester_id')
            ?? DB::table('semesters')->orderBy('semester_id')->value('semester_id');
        if ($semester !== null) {
            DB::table('schedule_drafts')->whereNull('semester_id')->update(['semester_id' => $semester]);
        }

        Schema::table('schedule_drafts', function (Blueprint $table) {
            $table->foreign('semester_id')->references('semester_id')->on('semesters')->restrictOnDelete();
            $table->index(['department_id', 'semester_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('schedule_drafts', function (Blueprint $table) {
            $table->dropIndex(['department_id', 'semester_id', 'type']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['semester_id', 'type']);
        });
    }
};
