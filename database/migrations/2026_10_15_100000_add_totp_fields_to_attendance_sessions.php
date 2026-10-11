<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_sessions', 'session_uuid')) {
                $table->string('session_uuid', 36)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('attendance_sessions', 'session_secret')) {
                $table->string('session_secret', 64)->nullable()->after('session_uuid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            foreach (['session_secret', 'session_uuid'] as $col) {
                if (Schema::hasColumn('attendance_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
