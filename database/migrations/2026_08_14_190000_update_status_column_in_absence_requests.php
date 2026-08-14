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
        try {
            DB::statement("ALTER TABLE absence_requests MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending_parent'");
        } catch (\Throwable $e) {
            // Ignore if already modified or fails
        }

        try {
            DB::statement("ALTER TABLE leave_requests MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending_parent'");
        } catch (\Throwable $e) {
            // Ignore if already modified or fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
