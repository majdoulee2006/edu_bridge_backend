<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * ALTER TABLE students
 *   ADD device_id        VARCHAR(255) NULL  -- معرّف الجهاز المرتبط بالطالب
 *   ADD is_device_locked TINYINT(1)   NOT NULL DEFAULT 0  -- هل قُفل الجهاز؟
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('student_code')
                  ->comment('معرّف الجهاز الوحيد المسموح له بتسجيل الحضور');
            $table->boolean('is_device_locked')->default(false)->after('device_id')
                  ->comment('true = الجهاز مقفّل ولا يمكن تغييره إلا من الأدمن');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'is_device_locked']);
        });
    }
};
