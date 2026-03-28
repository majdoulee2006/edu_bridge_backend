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
       Schema::create('resources', function (Blueprint $table) {
               $table->id('resource_id');
               $table->foreignId('course_id')->constrained('courses', 'course_id')->onDelete('cascade');
               $table->string('resource_name');
               $table->string('file_path');
               $table->timestamps();

         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
