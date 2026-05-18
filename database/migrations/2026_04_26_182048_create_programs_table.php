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
        // 1. تنظيف أي بقايا للجدول
        Schema::dropIfExists('programs');

        // 2. إنشاء الجدول من جديد
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الدورة
            
            // 3. الربط الصحيح بناءً على اكتشافنا
            $table->unsignedBigInteger('department_id');
            $table->foreign('department_id')
                  ->references('department_id') // غيرناها للاسم اللي بيستخدمه فريقك
                  ->on('departments')
                  ->cascadeOnDelete();
                  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
