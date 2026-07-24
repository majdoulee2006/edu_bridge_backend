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
        if (!Schema::hasTable('head_schedule_entries')) {
            Schema::create('head_schedule_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('head_user_id');
                $table->string('day');
                $table->string('class_name');
                $table->string('content');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('head_schedule_entries');
    }
};
