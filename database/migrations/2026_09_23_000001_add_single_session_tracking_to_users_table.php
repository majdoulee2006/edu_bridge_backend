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
        Schema::table('users', function (Blueprint $table) {
            $table->string('active_web_session_id', 255)->nullable()->after('remember_token')
                  ->comment('معرّف الجلسة النشطة الوحيدة المسموح بها للأدمن في الويب');
            $table->timestamp('web_last_active_at')->nullable()->after('active_web_session_id')
                  ->comment('وقت آخر نشاط للجلسة للتحقق من عدم الخمول أو انتهاء الجلسة');
            $table->string('web_active_device_ip', 45)->nullable()->after('web_last_active_at')
                  ->comment('عنوان IP الخاص بالجهاز النشط حالياً');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['active_web_session_id', 'web_last_active_at', 'web_active_device_ip']);
        });
    }
};
