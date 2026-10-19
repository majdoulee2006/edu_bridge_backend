<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قاعة الشعبة: لكل دورة (برنامج) ولكل سنة قاعة خاصة بها. program_id + year يربطان القاعة بشعبة،
 * وغيابهما = قاعة مشتركة (مخبر، قاعة كبرى) تستعملها أي شعبة. type: classroom | lab | hall (للتصنيف والعرض).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->after('department_id');
            $table->unsignedTinyInteger('year')->nullable()->after('program_id');
            $table->string('type', 12)->default('classroom')->after('year');

            $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            $table->index(['program_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex(['program_id', 'year']);
            $table->dropForeign(['program_id']);
            $table->dropColumn(['program_id', 'year', 'type']);
        });
    }
};
