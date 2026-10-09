<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // قاعات حقيقية (اختيارية): إن عُرّفت للقسم يمنع المولّد تعارضها ويراعي سعتها
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->unsignedBigInteger('department_id')->nullable()->index();   // null = قاعة مشتركة بين الأقسام
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'department_id']);
        });

        // توافر المعلم: وقت لا يستطيعه (صلب) أو يفضّل تجنبه (ناعم)
        Schema::create('teacher_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_user_id');
            $table->string('day', 16);
            $table->unsignedTinyInteger('period');
            $table->enum('kind', ['unavailable', 'avoid']);
            $table->timestamps();

            $table->unique(['teacher_user_id', 'day', 'period']);
            $table->foreign('teacher_user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });

        Schema::create('schedule_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->index();
            $table->unsignedBigInteger('created_by');
            $table->string('name', 120);
            $table->enum('status', ['draft', 'approved', 'discarded'])->default('draft')->index();
            $table->json('settings');            // الأيام، عدد الفترات، البذرة، الأوزان
            $table->json('stats')->nullable();   // نتيجة الجودة والتقرير (غير المجدول وأسبابه...)
            $table->json('backup')->nullable();  // الحصص التي استبدلها الاعتماد (للتراجع)
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('user_id')->on('users')->cascadeOnDelete();
        });

        Schema::create('schedule_draft_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained('schedule_drafts')->cascadeOnDelete();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('teacher_user_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedTinyInteger('year');
            $table->string('class_group');
            $table->string('day', 16);
            $table->unsignedTinyInteger('period');
            $table->string('room');
            $table->boolean('locked')->default(false);
            $table->enum('source', ['auto', 'manual'])->default('auto');
            $table->timestamps();

            $table->index(['draft_id', 'class_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_draft_entries');
        Schema::dropIfExists('schedule_drafts');
        Schema::dropIfExists('teacher_availability');
        Schema::dropIfExists('rooms');
    }
};
