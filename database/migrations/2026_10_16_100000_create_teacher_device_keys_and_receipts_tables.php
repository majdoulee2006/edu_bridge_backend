<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول مفاتيح أجهزة المعلمين (المرحلة 4: التوقيع الرقمي Ed25519)
        if (!Schema::hasTable('teacher_device_keys')) {
            Schema::create('teacher_device_keys', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teacher_user_id');
                $table->foreign('teacher_user_id')->references('user_id')->on('users')->cascadeOnDelete();
                $table->string('device_id', 255);
                $table->text('public_key'); // المفتاح العام Ed25519
                $table->timestamp('registered_at')->useCurrent();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->unique(['teacher_user_id', 'device_id']);
            });
        }

        // جدول إيصالات المعلم الموقعة محلياً لإثبات الحضور الصارم
        if (!Schema::hasTable('attendance_teacher_receipts')) {
            Schema::create('attendance_teacher_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('attendance_sessions')->cascadeOnDelete();
                $table->unsignedBigInteger('student_id');
                $table->foreign('student_id')->references('student_id')->on('students')->cascadeOnDelete();
                $table->string('client_scan_id', 64)->index();
                $table->string('device_id', 255);
                $table->timestamp('teacher_time');
                $table->text('signature');
                $table->boolean('verified')->default(true);
                $table->timestamps();

                $table->unique(['session_id', 'client_scan_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_teacher_receipts');
        Schema::dropIfExists('teacher_device_keys');
    }
};
