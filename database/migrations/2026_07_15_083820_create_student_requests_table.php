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
        Schema::create('student_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id'); // معرف الطالب
            
            // نوع الطلب: استرحام mercy، وثيقة document، إكمال makeup
            $table->string('type'); 
            
            // تفاصيل الطلب التي يدخلها الطالب
            $table->text('details');
            
            // حالة الطلب (التسلسل الهرمي للوصول)
            // الحالات: pending_affairs -> pending_hod -> pending_admin -> completed
            $table->string('status')->default('pending_affairs');
            
            // قرارات الإداريين (موافق أو مرفوض)
            $table->enum('affairs_decision', ['approved', 'rejected'])->nullable();
            $table->enum('hod_decision', ['approved', 'rejected'])->nullable();
            $table->enum('admin_decision', ['approved', 'rejected'])->nullable();
            
            // ملاحظات الإداريين
            $table->text('affairs_notes')->nullable(); // الشؤون
            $table->text('hod_notes')->nullable();     // رئيس القسم
            $table->text('admin_notes')->nullable();   // الإدارة
            
            $table->timestamps();

            // العلاقات
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_requests');
    }
};
