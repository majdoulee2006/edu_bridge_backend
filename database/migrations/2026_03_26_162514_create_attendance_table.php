<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->foreignId('student_id')->constrained('students', 'student_id')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('lessons', 'lesson_id')->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'late']);
            $table->date('attendance_date');
            
            // 🌟 الحقول الخاصة بتقديم العذر (مضافة لتتطابق مع الداتا بيز)
            $table->text('excuse_text')->nullable();
            $table->string('excuse_attachment')->nullable();
            $table->enum('excuse_status', ['none', 'pending', 'approved', 'rejected'])->default('none');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};