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
    Schema::create('teachers', function (Blueprint $table) {
        $table->id('teacher_id'); // Primary Key
        // ربط مع جدول users (تأكدي أن حقل الـ ID في جدول users اسمه user_id)
        $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
        $table->string('specialization');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
