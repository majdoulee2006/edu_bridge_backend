<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasColumn('performance_reports', 'report_type')) {
            Schema::table('performance_reports', function (Blueprint $table) {
                $table->enum('report_type', ['academic', 'behavioral'])->default('academic')->after('student_id');
            });
        }
    }

    public function down()
    {
        Schema::table('performance_reports', function (Blueprint $table) {
            $table->dropColumn('report_type');
        });
    }
};