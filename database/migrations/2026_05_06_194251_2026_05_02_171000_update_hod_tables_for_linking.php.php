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
        // تحديث جدول الإجازات ليدعم المدربين أيضاً
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->change();
            $table->foreignId('teacher_id')->nullable()->after('student_id')->constrained('teachers', 'teacher_id')->onDelete('cascade');
            $table->enum('leave_category', ['hourly', 'daily'])->default('daily')->after('type');
        });

        // إنشاء جدول طلبات التقارير (من رئيس القسم للمدرب)
        Schema::create('report_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('head_id')->constrained('users', 'user_id')->onDelete('cascade'); // رئيس القسم
            $table->foreignId('teacher_id')->constrained('teachers', 'teacher_id')->onDelete('cascade'); // المدرب المطلوب منه
            $table->foreignId('student_id')->constrained('students', 'student_id')->onDelete('cascade'); // الطالب المعني
            $table->enum('report_type', ['academic', 'behavioral']);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_requests');
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn(['teacher_id', 'leave_category']);
        });
    }
};