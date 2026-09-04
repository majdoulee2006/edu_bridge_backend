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
        Schema::create('student_warnings', function (Blueprint $table) {
            $table->id('warning_id');
            $table->foreignId('student_id')->constrained('students', 'student_id')->onDelete('cascade');
            $table->enum('warning_level', ['first', 'second', 'final']); // first: 7 days, second: 10 days, final: 15 days
            $table->unsignedInteger('absence_days');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->json('action_data')->nullable(); // e.g. {"parent_summon_id": 12, "referred_to": ["affairs", "hod"]}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_warnings');
    }
};
