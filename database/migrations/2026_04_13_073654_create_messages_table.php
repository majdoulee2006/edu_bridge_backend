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
    Schema::create('messages', function (Blueprint $table) {
        $table->id(); // المعرف الخاص بالرسالة
        
        // 🌟 المرسل (مربوط بجدول users)
        $table->unsignedBigInteger('sender_id');
        $table->foreign('sender_id')->references('user_id')->on('users')->onDelete('cascade');
        
        // 🌟 المستقبل (مربوط بجدول users وممكن يكون فارغ في حال رسالة جروب)
        $table->unsignedBigInteger('receiver_id')->nullable();
        $table->foreign('receiver_id')->references('user_id')->on('users')->onDelete('cascade');
        
        // 🌟 المادة / الجروب
        $table->unsignedBigInteger('course_id')->nullable(); 
        // ملاحظة: إذا عندك جدول للمواد (courses)، فيك تفعلي السطر اللي تحته لاحقاً
        // $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');

        $table->text('message')->nullable();
        $table->string('attachment')->nullable();
        $table->boolean('is_read')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
