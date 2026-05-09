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
        // 1. مسح الجدول المعلق من المحاولة الماضية
        Schema::dropIfExists('course_program');

        Schema::create('course_program', function (Blueprint $table) {
            $table->id();
            
            // 2. ربط مخصص مع جدول الكورسات (لأن فريقك مسمي المفتاح course_id)
            $table->unsignedBigInteger('course_id');
            $table->foreign('course_id')
                  ->references('course_id')
                  ->on('courses')
                  ->cascadeOnDelete();
            
            // 3. ربط مخصص مع جدول المسارات (اللي نحن لسا عاملينه بمفتاح اسمه id)
            $table->unsignedBigInteger('program_id');
            $table->foreign('program_id')
                  ->references('id')
                  ->on('programs')
                  ->cascadeOnDelete();
                  
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_program');
    }
};
