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
        Schema::create('admin_generated_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('report_type'); // attendance or performance
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department_name')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->string('program_name')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->string('semester_name')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_generated_reports');
    }
};
