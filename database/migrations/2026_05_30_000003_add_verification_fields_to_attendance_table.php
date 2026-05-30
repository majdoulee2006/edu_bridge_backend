<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * ALTER TABLE attendance
 *   ADD device_id      VARCHAR(255)  NULL  -- الجهاز الذي سجّل الحضور
 *   ADD latitude       DECIMAL(10,7) NULL  -- موقع الطالب عند المسح
 *   ADD longitude      DECIMAL(10,7) NULL
 *   ADD reject_reason  ENUM(...)     NULL  -- سبب الرفض إن وُجد
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('status')
                  ->comment('معرّف الجهاز الذي سجّل الحضور');
            $table->decimal('latitude', 10, 7)->nullable()->after('device_id')
                  ->comment('خط عرض موقع الطالب لحظة المسح');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude')
                  ->comment('خط طول موقع الطالب لحظة المسح');
            $table->enum('reject_reason', [
                'expired_qr',       // QR منتهي الصلاحية
                'device_mismatch',  // الجهاز لا يطابق المسجّل
                'location_too_far', // الموقع بعيد عن قاعة المحاضرة
                'already_marked',   // تم تسجيل الحضور مسبقاً
                'session_closed',   // الجلسة مغلقة
            ])->nullable()->after('longitude')
              ->comment('سبب رفض تسجيل الحضور');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'latitude', 'longitude', 'reject_reason']);
        });
    }
};
