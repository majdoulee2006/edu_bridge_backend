<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الجدول الامتحاني (مذاكرة + امتحان):
 *  - exams: نوع التقييم ومدته وقائمة قاعاته (room يبقى للتوافق مع كل ما يقرؤه اليوم).
 *  - exam_seatings: قاعة كل طالب لكل تقييم (توزيع طلاب الشعبة على عدة قاعات).
 *  - exam_invigilators: مراقب كل قاعة (يختاره رئيس القسم من أساتذة القسم).
 *  - exam_draft_entries: مسودة الجدول الامتحاني قبل الاعتماد (schedule_drafts.type = 'exam').
 *
 * مهم: grades.exam_id حذفه متسلسل (CASCADE)، لذلك الاعتماد يحدّث صفوف exams ولا يحذف أي صف له علامات.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('exam_type', 12)->default('final')->after('exam_name');      // final = امتحان، midterm = مذاكرة
            $table->unsignedSmallInteger('duration_minutes')->default(120)->after('exam_date');
            $table->json('rooms')->nullable()->after('room');                           // أسماء القاعات المستعملة
        });

        Schema::create('exam_seatings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('student_id');
            $table->string('room', 60);
            $table->unsignedSmallInteger('seat')->nullable();
            $table->timestamps();

            $table->foreign('exam_id')->references('exam_id')->on('exams')->cascadeOnDelete();
            $table->unique(['exam_id', 'student_id']);
            $table->index(['student_id']);
        });

        Schema::create('exam_invigilators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->string('room', 60);
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('exam_id')->references('exam_id')->on('exams')->cascadeOnDelete();
            $table->unique(['exam_id', 'room']);
            $table->index(['user_id']);
        });

        Schema::create('exam_draft_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('draft_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedTinyInteger('year');
            $table->string('class_group', 120);
            $table->string('kind', 12);                  // midterm | final
            $table->date('exam_date');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->unsignedSmallInteger('duration_minutes');
            $table->json('rooms');                       // [{name, capacity, students:[ids], proctor: user_id|null}]
            $table->boolean('locked')->default(false);
            $table->string('source', 12)->default('auto');
            $table->timestamps();

            $table->foreign('draft_id')->references('id')->on('schedule_drafts')->cascadeOnDelete();
            $table->index(['draft_id', 'exam_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_draft_entries');
        Schema::dropIfExists('exam_invigilators');
        Schema::dropIfExists('exam_seatings');

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['exam_type', 'duration_minutes', 'rooms']);
        });
    }
};
