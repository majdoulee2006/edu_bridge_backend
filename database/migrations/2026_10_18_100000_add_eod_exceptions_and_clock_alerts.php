<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // استثناء المعلم: سبب وكاتب القرار عند تسجيل حضور يدويًا بعد إغلاق الجلسة
        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'exception_reason')) {
                $table->string('exception_reason', 500)->nullable()->after('review_status');
            }
            if (!Schema::hasColumn('attendance', 'exception_by')) {
                $table->unsignedBigInteger('exception_by')->nullable()->after('exception_reason');
            }
        });

        // تنبيهات التلاعب بساعة الهاتف: تنبيه للطالب، وتصعيد لرئيس القسم إن تكرر دون رد
        if (!Schema::hasTable('attendance_clock_alerts')) {
            Schema::create('attendance_clock_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('session_id')->nullable()->index();
                $table->string('kind', 32);               // clock_skew | scan_after_send
                $table->integer('skew_seconds')->nullable(); // ساعة الهاتف ناقص ساعة السيرفر (موجب = الهاتف متقدم)
                $table->timestamp('acknowledged_at')->nullable();
                $table->string('ack_note', 500)->nullable();
                $table->timestamp('escalated_at')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'session_id', 'kind'], 'clock_alert_once_per_session');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_clock_alerts');

        Schema::table('attendance', function (Blueprint $table) {
            foreach (['exception_by', 'exception_reason'] as $col) {
                if (Schema::hasColumn('attendance', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
