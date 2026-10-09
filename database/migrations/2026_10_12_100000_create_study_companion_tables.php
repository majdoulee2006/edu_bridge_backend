<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مادة مذاكرة لكل محاضرة: pending (قيد التوليد) | draft (بانتظار المعلم) | approved | failed
        Schema::create('study_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons', 'lesson_id')->cascadeOnDelete();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('teacher_id');
            $table->string('status', 16)->default('pending');
            $table->text('summary')->nullable();
            $table->json('key_terms')->nullable();
            $table->string('source_hash', 64)->nullable();   // لتجنب إعادة التوليد لنفس الملف
            $table->unsignedInteger('source_chars')->default(0);
            $table->string('model', 80)->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->unique('lesson_id');
            $table->index(['course_id', 'status']);
        });

        Schema::create('study_flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('study_materials')->cascadeOnDelete();
            $table->string('front', 500);
            $table->text('back');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('study_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('study_materials')->cascadeOnDelete();
            $table->text('question');
            $table->json('options');                      // 4 خيارات نصية
            $table->unsignedTinyInteger('correct_index'); // 0..3
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // محاولات التدريب: لا علاقة لها بالعلامات
        Schema::create('study_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('study_materials')->cascadeOnDelete();
            $table->unsignedBigInteger('student_user_id');
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('total');
            $table->json('answers');                      // [question_id => chosen_index]
            $table->timestamps();

            $table->index(['student_user_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_attempts');
        Schema::dropIfExists('study_questions');
        Schema::dropIfExists('study_flashcards');
        Schema::dropIfExists('study_materials');
    }
};
