<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('report_requests')) {
            DB::statement('ALTER TABLE report_requests MODIFY teacher_id BIGINT UNSIGNED NULL;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_requests')) {
            DB::statement('ALTER TABLE report_requests MODIFY teacher_id BIGINT UNSIGNED NOT NULL;');
        }
    }
};
