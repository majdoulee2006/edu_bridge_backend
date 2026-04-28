<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. حذف جدول course_departments (لأننا اعتمدنا الهيكلية: قسم -> مسار -> مادة)
        Schema::dropIfExists('course_departments');

        // 2. حذف جدول subjects المهجور
        Schema::dropIfExists('subjects');

        // 3. حذف حقل parent_id من جدول الطلاب (لأننا نعتمد على الجدول الوسيط parent_students)
        if (Schema::hasColumn('students', 'parent_id')) {
            Schema::table('students', function (Blueprint $table) {
                // يجب إسقاط المفتاح الأجنبي أولاً (Foreign Key) ثم حذف العمود
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }

    public function down(): void
    {
        // إرجاع الجداول في حال أردنا التراجع
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('course_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('parents')->onDelete('cascade');
        });
    }
};