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
    Schema::create('leave_requests', function (Blueprint $table) {
        $table->id();
        
        // 🌟 ربط مع جدول المستخدمين
        $table->unsignedBigInteger('student_id');
        $table->foreign('student_id')->references('user_id')->on('users')->onDelete('cascade');
        
        $table->enum('type', ['full_day', 'hourly']); // إجازة يوم كامل أو ساعية
        $table->date('date'); // التاريخ
        $table->text('reason'); // سبب الإجازة
        
        // حالة الطلب (قيد المراجعة، مقبول، مرفوض)
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
