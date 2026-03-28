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
        // المحادثات
Schema::create('chats', function (Blueprint $table) {
    $table->id('chat_id');
    $table->foreignId('sender_id')->constrained('users', 'user_id');
    $table->foreignId('receiver_id')->constrained('users', 'user_id');
    $table->text('content');
    $table->timestamp('sent_at');
    $table->timestamps();
});

// سجل نشاط المستخدمين
Schema::create('user_activity', function (Blueprint $table) {
    $table->id('activity_id');
    $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
    $table->string('activity_type'); // مثلاً: Login, Upload Assignment
    $table->timestamp('activity_time');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_tables');
    }
};
