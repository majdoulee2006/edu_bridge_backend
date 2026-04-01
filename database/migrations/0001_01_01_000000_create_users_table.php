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
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->string('phone')->nullable();

            // حقول الطالب
            $table->string('university_id')->nullable();
            $table->string('department')->nullable();
            $table->string('branch')->nullable();

            // حقل الأهل
            $table->text('children_ids')->nullable();

            $table->enum('role', ['admin', 'teacher', 'student', 'parent', 'head'])->default('student');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamp('last_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    } // إغلاق دالة up

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
}; // إغلاق الكلاس
