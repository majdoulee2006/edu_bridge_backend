<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // إضافة تصنيف الإشعار للواجهة
            $table->enum('category', ['academic', 'administrative'])->default('administrative')->after('type');
            
            // إضافة المرسل (مين بعت الإشعار)
            $table->unsignedBigInteger('sender_id')->nullable()->after('user_id');
            $table->foreign('sender_id')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn(['category', 'sender_id']);
        });
    }
};