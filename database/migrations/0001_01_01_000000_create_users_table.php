<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id'); 
            $table->string('username')->unique(); // 👈 الرقم الجامعي (تمت إضافته هنا)
            $table->string('full_name');
            $table->string('email')->unique()->nullable(); // الإيميل أصبح اختيارياً
            $table->string('password');
            $table->string('phone')->nullable();
            $table->enum('role', ['admin', 'teacher', 'student', 'parent', 'head'])->default('student');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};