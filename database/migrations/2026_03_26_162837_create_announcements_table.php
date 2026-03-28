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
       Schema::create('announcements', function (Blueprint $table) {
    $table->id('announcement_id');
    $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade'); // مين كتب الإعلان
    $table->string('title');
    $table->text('content');
    $table->enum('type', ['general', 'course_specific'])->default('general');
    $table->foreignId('course_id')->nullable()->constrained('courses', 'course_id')->onDelete('cascade'); // إذا كان خاص بمادة
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
