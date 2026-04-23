<?php
// database/migrations/xxxx_create_parent_students_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->enum('relationship', ['father', 'mother', 'guardian'])->default('father');
            $table->unique(['parent_id', 'student_id']); // منع التكرار
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_students');
    }
};
