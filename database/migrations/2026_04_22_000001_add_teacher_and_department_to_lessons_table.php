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
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->constrained('teachers', 'teacher_id')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments', 'department_id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['teacher_id', 'department_id']);
        });
    }
};

