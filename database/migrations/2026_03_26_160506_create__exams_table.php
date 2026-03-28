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
       Schema::create('exams', function (Blueprint $table) {
               $table->id('exam_id');
               $table->foreignId('course_id')->constrained('courses', 'course_id')->onDelete('cascade');
               $table->string('exam_name'); // مثلاً: Exam 1, Final Exam
               $table->dateTime('exam_date');
               $table->integer('max_score')->default(100);
               $table->timestamps();
                 });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_exams');
    }
};
