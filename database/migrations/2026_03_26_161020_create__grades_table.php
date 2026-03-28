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
        Schema::create('grades', function (Blueprint $table) {
                $table->id('grade_id');
                $table->foreignId('student_id')->constrained('students', 'student_id')->onDelete('cascade');
                $table->foreignId('exam_id')->constrained('exams', 'exam_id')->onDelete('cascade');
                $table->decimal('score', 5, 2); // درجة الطالب
                $table->text('remarks')->nullable(); // ملاحظات المعلم
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_grades');
    }
};
