<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الرموز السابقة للجلسة مع وقت استبدال كلٍّ منها: لتحمّل بطء خطوة الوجه بين قراءة الـ QR وإرسال الحضور
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_sessions', 'previous_tokens')) {
                $table->json('previous_tokens')->nullable()->after('qr_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'previous_tokens')) {
                $table->dropColumn('previous_tokens');
            }
        });
    }
};
