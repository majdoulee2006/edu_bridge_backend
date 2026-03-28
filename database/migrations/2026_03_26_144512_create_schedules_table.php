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
        Schema::create('schedules', function (Blueprint $table) {
    $table->id('schedule_id');
    $table->foreignId('course_id')->constrained('courses', 'course_id')->onDelete('cascade');
    $table->string('day'); // مثلاً: Monday
    $table->time('start_time');
    $table->time('end_time');
    $table->string('room'); // رقم القاعة
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
