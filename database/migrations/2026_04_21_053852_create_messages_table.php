<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // id للرسالة
            
            // sender_id - بنستخدم user_id كـ foreign key
            $table->unsignedBigInteger('sender_id');
            $table->foreign('sender_id')->references('user_id')->on('users')->onDelete('cascade');
            
            // receiver_id
            $table->unsignedBigInteger('receiver_id');
            $table->foreign('receiver_id')->references('user_id')->on('users')->onDelete('cascade');
            
            // محتوى الرسالة
            $table->text('message');
            
            // هل تمت القراءة؟
            $table->boolean('is_read')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};