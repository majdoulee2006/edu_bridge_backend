<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_digests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_user_id');
            $table->unsignedBigInteger('student_id');
            $table->date('week_start');
            $table->date('week_end');
            $table->json('facts');                       // الحقائق الخام التي بُني عليها النص (مصدر الحقيقة)
            $table->string('tone', 16)->default('good'); // good | attention | concern
            $table->string('title');
            $table->text('body');
            $table->string('source', 16)->default('template'); // template | ai
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // ملخص واحد فقط لكل (ولي أمر، طالب، أسبوع): يمنع التكرار عند إعادة تشغيل الأمر
            $table->unique(['parent_user_id', 'student_id', 'week_start'], 'parent_digests_unique_week');
            $table->index(['parent_user_id', 'week_start']);

            $table->foreign('parent_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });

        Schema::table('parents', function (Blueprint $table) {
            if (!Schema::hasColumn('parents', 'digest_enabled')) {
                $table->boolean('digest_enabled')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'digest_enabled')) {
                $table->dropColumn('digest_enabled');
            }
        });
        Schema::dropIfExists('parent_digests');
    }
};
