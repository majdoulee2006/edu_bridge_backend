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
    Schema::create('course_teachers', function (Blueprint $table) {
        $table->id('course_teacher_id'); // Primary Key
        // ربط مع جدول الكورسات
        $table->foreignId('course_id')->constrained('courses', 'course_id')->onDelete('cascade');
        // ربط مع جدول المعلمين
        $table->foreignId('teacher_id')->constrained('teachers', 'teacher_id')->onDelete('cascade');
        $table->string('role')->nullable(); // حقل الـ role الموجود في الصورة
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_teachers');
    }
};
