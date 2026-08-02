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
        // 1. طلبات المواعيد من الأهل للإدارة
        Schema::create('parent_meeting_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_user_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('subject');
            $table->text('reason');
            $table->date('preferred_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('admin_response')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamps();

            $table->foreign('parent_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('set null');
        });

        // 2. استدعاء ولي أمر من المعلم أو رئيس القسم
        Schema::create('parent_summons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_user_id'); // معلم أو رئيس قسم
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('parent_user_id')->nullable();
            $table->string('reason_title');
            $table->text('details');
            $table->date('summon_date')->nullable();
            $table->enum('status', ['sent', 'acknowledged', 'attended', 'cancelled'])->default('sent');
            $table->timestamps();

            $table->foreign('sender_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_summons');
        Schema::dropIfExists('parent_meeting_requests');
    }
};
