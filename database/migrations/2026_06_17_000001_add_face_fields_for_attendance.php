<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // حقول الوجه في جدول الطلاب
        Schema::table('students', function (Blueprint $table) {
            $table->json('face_embedding')->nullable()->after('level');
            $table->boolean('requires_face_reset')->default(false)->after('face_embedding');
        });

        // حقول الوجه في جدول الحضور
        Schema::table('attendance', function (Blueprint $table) {
            $table->string('face_image')->nullable()->after('reject_reason');
            $table->float('face_score')->nullable()->after('face_image');
            $table->enum('face_status', ['first_time', 'verified', 'suspicious', 'rejected'])
                  ->nullable()->after('face_score');
        });

        // وقت انتهاء الجلسة الكاملة (10 دقائق)
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->timestamp('session_expires_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['face_embedding', 'requires_face_reset']);
        });
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['face_image', 'face_score', 'face_status']);
        });
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn('session_expires_at');
        });
    }
};
