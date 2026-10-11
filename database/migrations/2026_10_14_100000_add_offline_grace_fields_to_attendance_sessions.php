<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_sessions', 'offline_mode')) {
                $table->string('offline_mode', 20)->default('none')->after('is_active');
            }
            if (!Schema::hasColumn('attendance_sessions', 'sync_deadline_at')) {
                $table->timestamp('sync_deadline_at')->nullable()->after('closed_at');
            }
            if (!Schema::hasColumn('attendance_sessions', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable()->after('sync_deadline_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            foreach (['finalized_at', 'sync_deadline_at', 'offline_mode'] as $col) {
                if (Schema::hasColumn('attendance_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
