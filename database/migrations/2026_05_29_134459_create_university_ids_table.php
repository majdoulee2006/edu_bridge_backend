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
        Schema::create('university_ids', function (Blueprint $table) {
            $table->id();
            $table->string('university_id')->unique();
            $table->string('full_name');
            $table->enum('role', ['student', 'parent'])->default('student');
            $table->boolean('is_used')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('university_ids');
    }
};
