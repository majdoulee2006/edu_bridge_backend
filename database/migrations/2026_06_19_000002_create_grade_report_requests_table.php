<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grade_report_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boss_user_id');
            $table->unsignedBigInteger('teacher_user_id');
            $table->unsignedBigInteger('course_id');
            $table->string('status')->default('pending'); // pending / completed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_report_requests');
    }
};
