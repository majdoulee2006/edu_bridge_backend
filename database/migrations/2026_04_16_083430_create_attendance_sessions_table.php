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
    Schema::create('attendance_sessions', function (Blueprint $table) {
        $table->id();
        
        // 🌟 ربط مع جدول الدروس
        $table->unsignedBigInteger('lesson_id');
        // تأكدي من اسم المفتاح الأساسي لجدول الدروس عندك، هل هو id أو lesson_id؟
        $table->foreign('lesson_id')->references('lesson_id')->on('lessons')->onDelete('cascade');
        
        $table->string('qr_token')->unique(); // الكود العشوائي اللي رح يتحول لباركود
        $table->timestamp('expires_at'); // وقت انتهاء الصلاحية
        $table->boolean('is_active')->default(true); // لسهولة الإيقاف اليدوي
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
