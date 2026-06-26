<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة 'oral' للـ enum وجعل course_id nullable
        DB::statement("ALTER TABLE grade_events MODIFY type ENUM('exam','quiz','oral') NOT NULL");
        DB::statement("ALTER TABLE grade_events MODIFY course_id BIGINT UNSIGNED NULL");

        Schema::table('grade_events', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->after('course_id');
            $table->tinyInteger('year_level')->nullable()->after('program_id');
            $table->text('notes')->nullable()->after('max_score');
        });
    }

    public function down(): void
    {
        Schema::table('grade_events', function (Blueprint $table) {
            $table->dropColumn(['program_id', 'year_level', 'notes']);
        });
        DB::statement("ALTER TABLE grade_events MODIFY course_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE grade_events MODIFY type ENUM('exam','quiz') NOT NULL");
    }
};
