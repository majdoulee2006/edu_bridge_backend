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
        // جدول أولياء الأمور
Schema::create('parents', function (Blueprint $table) {
    $table->id('parent_id');
    $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
    $table->timestamps();
});
        // جدول المدراء
Schema::create('admins', function (Blueprint $table) {
    $table->id('admin_id');
    $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
    $table->timestamps();
});



// جدول رؤساء الأقسام
Schema::create('heads', function (Blueprint $table) {
    $table->id('head_id');
    $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
    $table->foreignId('department_id')->constrained('departments', 'department_id')->onDelete('cascade');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrative_tables');
    }
};
