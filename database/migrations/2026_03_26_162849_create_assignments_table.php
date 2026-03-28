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
        Schema::create('assignments', function (Blueprint $table) {
    $table->id('assignment_id');
    $table->foreignId('course_id')->constrained('courses', 'course_id')->onDelete('cascade');
    $table->string('title');
    $table->text('description');
    $table->dateTime('due_date'); // موعد التسليم النهائي
    $table->integer('max_points')->default(100);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
