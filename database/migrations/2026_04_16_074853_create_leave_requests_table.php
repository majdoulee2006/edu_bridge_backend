<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            
            // 🌟 ربط مع جدول المستخدمين
            $table->unsignedBigInteger('student_id')->nullable();
            $table->foreign('student_id')->references('user_id')->on('users')->onDelete('cascade');
            
            $table->enum('type', ['full_day', 'hourly']); // إجازة يوم كامل أو ساعية
            $table->date('date'); // التاريخ
            $table->text('reason'); // سبب الإجازة
            
            // 🌟 إضافة حقل للمرفقات (لأنك استخدمتيه بالكنترولر)
            $table->string('attachment')->nullable(); 
            
            // 🌟 تحديث حالات الطلب لتناسب السلسلة الجديدة
            $table->enum('status', [
                'pending', 
                'pending_hod', 
                'pending_affairs', 
                'pending_parent', 
                'approved', 
                'rejected'
            ])->default('pending_hod');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};