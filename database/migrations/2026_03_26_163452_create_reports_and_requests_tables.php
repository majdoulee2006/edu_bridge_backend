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
        // تقارير الأداء
Schema::create('performance_reports', function (Blueprint $table) {
    $table->id('report_id');
    $table->foreignId('student_id')->constrained('students', 'student_id')->onDelete('cascade');
    $table->decimal('attendance_rate', 5, 2);
    $table->decimal('average_grade', 5, 2);
    $table->text('recommendations')->nullable();
    $table->timestamp('generated_at');
    $table->timestamps();
});

// طلبات الغياب
Schema::create('absence_requests', function (Blueprint $table) {
    $table->id('request_id');
    $table->foreignId('student_id')->constrained('students', 'student_id')->onDelete('cascade');
    $table->date('date');
    $table->text('reason');
    $table->string('document')->nullable(); // مسار ملف التبرير
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->foreignId('reviewed_by')->nullable()->constrained('users', 'user_id');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports_and_requests_tables');
    }
};
