<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grade_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('course_id');
            $table->enum('type', ['exam', 'quiz']); // امتحان / مذاكرة
            $table->string('title');
            $table->decimal('max_score', 5, 2)->default(100);
            $table->date('date');
            $table->timestamps();

            $table->foreign('teacher_id')->references('teacher_id')->on('teachers')->onDelete('cascade');
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
        });

        Schema::create('grade_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grade_event_id');
            $table->unsignedBigInteger('student_id');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('grade_event_id')->references('id')->on('grade_events')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->unique(['grade_event_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_entries');
        Schema::dropIfExists('grade_events');
    }
};
