<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `attendance` MODIFY `reject_reason` ENUM(
            'expired_qr',
            'device_mismatch',
            'location_too_far',
            'already_marked',
            'session_closed',
            'face_mismatch'
        ) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `attendance` MODIFY `reject_reason` ENUM(
            'expired_qr',
            'device_mismatch',
            'location_too_far',
            'already_marked',
            'session_closed'
        ) NULL");
    }
};
