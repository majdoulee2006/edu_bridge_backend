<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الدرجة الحالية لكل طالب مع أسبابها (صف واحد لكل طالب)
        Schema::create('student_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students', 'student_id')->cascadeOnDelete();
            $table->decimal('score', 5, 1)->default(0);
            $table->enum('level', ['low', 'medium', 'high', 'unknown'])->default('unknown')->index();
            $table->decimal('confidence', 3, 2)->default(0);      // نسبة الأوزان التي توفرت بياناتها
            $table->json('factors');                             // تفصيل كل عامل: نقاطه ووزنه وسببه وحقائقه
            $table->json('reasons');                             // أهم الأسباب بنص عربي مفهوم
            $table->decimal('previous_score', 5, 1)->nullable();
            $table->string('previous_level', 10)->nullable();
            $table->timestamp('level_changed_at')->nullable();
            $table->string('notified_level', 10)->nullable();    // آخر مستوى أُرسل عنه تنبيه (لا نكرر التنبيه)
            $table->date('computed_on');
            $table->timestamps();

            $table->unique('student_id');
            $table->index(['level', 'score']);
        });

        // لقطة يومية لتتبع الاتجاه (لقطة واحدة لكل طالب في اليوم)
        Schema::create('student_risk_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students', 'student_id')->cascadeOnDelete();
            $table->date('computed_on');
            $table->decimal('score', 5, 1);
            $table->enum('level', ['low', 'medium', 'high', 'unknown']);
            $table->json('reasons');
            $table->timestamps();

            $table->unique(['student_id', 'computed_on']);
        });

        // سجل متابعة المرشد: ماذا فُعل بعد التنبيه، لإغلاق الحلقة وقياس أثر التدخل
        Schema::create('risk_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students', 'student_id')->cascadeOnDelete();
            $table->unsignedBigInteger('author_user_id');
            $table->enum('type', ['call', 'meeting', 'message', 'parent_contact', 'referral', 'note', 'no_action']);
            $table->text('note')->nullable();
            $table->date('follow_up_on')->nullable();
            $table->decimal('score_at_time', 5, 1)->nullable();
            $table->string('level_at_time', 10)->nullable();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->foreign('author_user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_interventions');
        Schema::dropIfExists('student_risk_history');
        Schema::dropIfExists('student_risk_scores');
    }
};
