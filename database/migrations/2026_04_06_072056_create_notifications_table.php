<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     *Run the migrations.
     */
    public function up(): void
    {Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id'); // الشخص الذي سيستلم الإشعار (الأب مثلاً)
        $table->string('title');               // عنوان الإشعار (مثلاً: تنبيه غياب)
        $table->text('message');               // نص الإشعار (مثلاً: ابنك أحمد غائب اليوم)
        $table->string('type');                // نوع الإشعار (attendance, marks, general)
        $table->boolean('is_read')->default(false); // هل قرأه المستخدم أم لا؟
        $table->timestamps();

        // ربط الإشعار بجدول المستخدمين
        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
