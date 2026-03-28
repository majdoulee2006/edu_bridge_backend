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
       Schema::create('enrollments', function (Blueprint $table) {
               $table->id('enrollment_id');
               $table->foreignId('student_id')->constrained('students', 'student_id')->onDelete('cascade');
               $table->foreignId('course_id')->constrained('courses', 'course_id')->onDelete('cascade');
               $table->date('enrollment_date');
               $table->string('status')->default('active'); // active, dropped, completed
               $table->timestamps();
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_enrollments');
    }
};
