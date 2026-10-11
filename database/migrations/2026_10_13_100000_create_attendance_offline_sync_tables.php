<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول الخط الزمني لرموز QR للجلسة (يحل المشكلة P1 بحفظ كل الرموز وفترة صلاحيتها)
        if (!Schema::hasTable('attendance_session_tokens')) {
            Schema::create('attendance_session_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('attendance_sessions')->cascadeOnDelete();
                $table->string('token', 64)->index();
                $table->timestamp('issued_at')->index();
                $table->timestamp('replaced_at')->nullable()->index();
                $table->timestamps();
            });
        }

        // 2. تحديث جدول attendance لوسم الحضور الأوفلاين ومنع التكرار
        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'source')) {
                $table->string('source', 16)->default('online')->after('status');
            }
            if (!Schema::hasColumn('attendance', 'client_scan_id')) {
                $table->string('client_scan_id', 64)->nullable()->after('source')->index();
            }
            if (!Schema::hasColumn('attendance', 'time_confidence')) {
                $table->string('time_confidence', 16)->default('high')->after('client_scan_id');
            }
            if (!Schema::hasColumn('attendance', 'synced_at')) {
                $table->timestamp('synced_at')->nullable()->after('time_confidence');
            }
            if (!Schema::hasColumn('attendance', 'countersigned')) {
                $table->boolean('countersigned')->default(false)->after('synced_at');
            }
            if (!Schema::hasColumn('attendance', 'review_status')) {
                $table->string('review_status', 16)->default('none')->after('countersigned');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_session_tokens');

        Schema::table('attendance', function (Blueprint $table) {
            foreach (['review_status', 'countersigned', 'synced_at', 'time_confidence', 'client_scan_id', 'source'] as $col) {
                if (Schema::hasColumn('attendance', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
