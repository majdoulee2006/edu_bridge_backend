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
        Schema::table('report_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('report_requests', 'hod_notes')) {
                $table->text('hod_notes')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_requests', function (Blueprint $table) {
            if (Schema::hasColumn('report_requests', 'hod_notes')) {
                $table->dropColumn('hod_notes');
            }
        });
    }
};
