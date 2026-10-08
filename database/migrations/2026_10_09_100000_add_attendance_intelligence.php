<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // بصمة كل عملية مسح (للتحليل لاحقًا): من أين، وبأي جهاز، وبأي عمر للـ QR
        Schema::table('attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance', 'session_id')) {
                $table->unsignedBigInteger('session_id')->nullable()->after('lesson_id')->index();
            }
            if (!Schema::hasColumn('attendance', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('attendance', 'user_agent')) {
                $table->string('user_agent', 255)->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('attendance', 'qr_age_seconds')) {
                $table->unsignedSmallInteger('qr_age_seconds')->nullable()->after('user_agent');
            }
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_sessions', 'analyzed_at')) {
                $table->timestamp('analyzed_at')->nullable()->after('closed_at');
            }
        });

        Schema::create('attendance_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendance', 'attendance_id')->cascadeOnDelete();
            $table->unsignedBigInteger('session_id')->nullable()->index();
            $table->foreignId('student_id')->constrained('students', 'student_id')->cascadeOnDelete();
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->string('rule', 40);
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->json('details')->nullable();
            $table->enum('status', ['open', 'confirmed', 'dismissed'])->default('open')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();

            // لا تتكرر نفس القاعدة على نفس سجل الحضور عند إعادة التحليل
            $table->unique(['attendance_id', 'rule'], 'attendance_flags_unique_rule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_flags');

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'analyzed_at')) {
                $table->dropColumn('analyzed_at');
            }
        });

        Schema::table('attendance', function (Blueprint $table) {
            foreach (['qr_age_seconds', 'user_agent', 'ip_address', 'session_id'] as $col) {
                if (Schema::hasColumn('attendance', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
