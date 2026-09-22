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
        if (!Schema::hasColumn('courses', 'code')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->after('course_id')->comment('رمز المادة الدراسية');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('courses', 'code')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
