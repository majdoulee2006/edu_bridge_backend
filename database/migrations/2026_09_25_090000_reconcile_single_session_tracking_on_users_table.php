<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * يوحّد تتبّع الجلسة الوحيدة على users بعد دمج تنفيذين مستقلين لنفس
 * الميزة: يبقي current_session_id (ويب) و current_token_id (موبايل)،
 * يضيف session_last_active_at (نافذة خمول 20 دقيقة للويب)، ويحذف أعمدة
 * الجلسة المكررة (active_web_session_id, web_last_active_at,
 * web_active_device_ip) لو كانت موجودة من تنفيذ سابق.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'session_last_active_at')) {
                $table->timestamp('session_last_active_at')->nullable()->after('current_token_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $toDrop = array_filter(
                ['active_web_session_id', 'web_last_active_at', 'web_active_device_ip'],
                fn ($column) => Schema::hasColumn('users', $column)
            );

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'session_last_active_at')) {
                $table->dropColumn('session_last_active_at');
            }
        });
    }
};
