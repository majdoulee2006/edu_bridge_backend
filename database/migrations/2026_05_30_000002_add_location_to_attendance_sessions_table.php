<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * ALTER TABLE attendance_sessions
 *   ADD latitude       DECIMAL(10,7) NULL  -- خط عرض موقع المعلم
 *   ADD longitude      DECIMAL(10,7) NULL  -- خط طول موقع المعلم
 *   ADD radius_meters  SMALLINT UNSIGNED NOT NULL DEFAULT 50  -- نصف قطر القبول بالمتر
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('is_active')
                  ->comment('خط عرض موقع المعلم عند فتح الجلسة');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude')
                  ->comment('خط طول موقع المعلم عند فتح الجلسة');
            $table->unsignedSmallInteger('radius_meters')->default(50)->after('longitude')
                  ->comment('الحد الأقصى للمسافة المسموح بها بالمتر (افتراضي 50م)');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'radius_meters']);
        });
    }
};
