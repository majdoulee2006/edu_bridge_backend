<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('report_requests', 'course_id')) {
                $table->unsignedBigInteger('course_id')->nullable()->after('student_id');
                $table->foreign('course_id')->references('course_id')->on('courses')->nullOnDelete();
            }
            if (!Schema::hasColumn('report_requests', 'year')) {
                $table->tinyInteger('year')->nullable()->after('course_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_requests', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn(['course_id', 'year']);
        });
    }
};
