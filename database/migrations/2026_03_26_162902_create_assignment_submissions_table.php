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
        Schema::create('assignment_submissions', function (Blueprint $table) {
    $table->id('submission_id');
    $table->foreignId('assignment_id')->constrained('assignments', 'assignment_id')->onDelete('cascade');
    $table->foreignId('student_id')->constrained('students', 'student_id')->onDelete('cascade');
    $table->string('file_path'); // مسار الملف المرفوع (PDF, Docx, etc.)
    $table->decimal('grade', 5, 2)->nullable(); // العلامة اللي رح يحطها المعلم بعدين
    $table->text('feedback')->nullable(); // ملاحظات المعلم على الحل
    $table->dateTime('submitted_at');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
